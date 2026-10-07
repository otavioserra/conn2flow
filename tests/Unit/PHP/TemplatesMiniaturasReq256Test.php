<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-256 — todo modelo que aparece na lista de modelos do painel tem miniatura, e a miniatura existe.
 * Modelo novo sem miniatura faz este teste falhar: gere com `sdd/validation/req256/gerar-miniaturas.cjs`.
 */
final class TemplatesMiniaturasReq256Test extends TestCase
{
    /** Alvos que não aparecem na lista de modelos do painel. */
    private const SEM_LISTA = ['galleries-estados'];

    /** @return array<int, array{arquivo: string, lingua: string, modelo: array}> */
    private function modelos(): array
    {
        $saida = [];
        foreach (glob(CONN2FLOW_GESTOR_ROOT . '/modulos/*/*.json') ?: [] as $arquivo) {
            $dados = json_decode((string) file_get_contents($arquivo), true);
            foreach (is_array($dados['resources'] ?? null) ? $dados['resources'] : [] as $lingua => $recursos) {
                foreach (is_array($recursos['templates'] ?? null) ? $recursos['templates'] : [] as $modelo) {
                    $saida[] = ['arquivo' => basename($arquivo), 'lingua' => (string) $lingua, 'modelo' => $modelo];
                }
            }
        }
        foreach (glob(CONN2FLOW_GESTOR_ROOT . '/resources/*/templates.json') ?: [] as $arquivo) {
            foreach (json_decode((string) file_get_contents($arquivo), true) ?: [] as $modelo) {
                $saida[] = ['arquivo' => 'templates.json', 'lingua' => basename(dirname($arquivo)), 'modelo' => $modelo];
            }
        }
        return $saida;
    }

    public function testTodoModeloDaListaTemMiniaturaQueExiste(): void
    {
        $modelos = $this->modelos();
        self::assertGreaterThan(150, count($modelos));
        $conferidos = 0;
        foreach ($modelos as $item) {
            $modelo = $item['modelo'];
            $quem = $item['arquivo'] . ' ' . $item['lingua'] . ' ' . ($modelo['id'] ?? '?');
            if (in_array($modelo['target'] ?? '', self::SEM_LISTA, true)) {
                continue;
            }
            self::assertNotEmpty($modelo['thumbnail'] ?? '', 'modelo sem miniatura: ' . $quem);
            $caminho = (string) $modelo['thumbnail'];
            self::assertMatchesRegularExpression('#^templates/images/' . preg_quote($item['lingua'], '#') . '/[a-z0-9-]+\.webp$#', $caminho, $quem);
            $arquivo = CONN2FLOW_GESTOR_ROOT . '/assets/' . $caminho;
            self::assertFileExists($arquivo, $quem);
            // WebP de verdade e leve: a lista de modelos carrega várias de uma vez.
            $cabecalho = (string) file_get_contents($arquivo, false, null, 0, 12);
            self::assertSame(['RIFF', 'WEBP'], [substr($cabecalho, 0, 4), substr($cabecalho, 8, 4)], $quem);
            self::assertLessThanOrEqual(100 * 1024, filesize($arquivo), $quem);
            $conferidos++;
        }
        self::assertGreaterThan(150, $conferidos);
    }

    public function testMiniaturasNovasTemAProporcaoDasAntigas(): void
    {
        if (!function_exists('getimagesize')) {
            self::markTestSkipped('sem a extensão de imagem');
        }
        // Antigas: 290 x 197. Novas: o dobro, 580 x 394.
        foreach (['pt-br/menus-horizontal-navbar', 'en/publisher-index-grid', 'pt-br/layout-landing-page', 'en/componente-hero-banner'] as $nome) {
            $medida = @getimagesize(CONN2FLOW_GESTOR_ROOT . '/assets/templates/images/' . $nome . '.webp');
            self::assertIsArray($medida, $nome);
            self::assertSame([580, 394], [$medida[0], $medida[1]], $nome);
        }
    }

    public function testGeradorVersionado(): void
    {
        $pasta = dirname(CONN2FLOW_GESTOR_ROOT) . '/sdd/validation/req256/';
        foreach (['gerar-miniaturas.cjs', 'montar.py', 'integrar.py', 'README.md'] as $arquivo) {
            self::assertFileExists($pasta . $arquivo);
        }
    }
}
