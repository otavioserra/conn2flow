<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-143 / BATCH-146 — biblioteca de assets externos.
 *
 * O inventário que motivou esta biblioteca: 11 bibliotecas de terceiros em 27 arquivos, com
 * `sortablejs@latest` repetido 16 vezes. Versão flutuante significa que qualquer publicação no npm
 * entra em produção sem revisão — um release quebrado do Sortable derrubaria os CRUDs de ordenação
 * sem que ninguém tivesse alterado o projeto.
 */
final class AssetsExternosTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'assets-externos.php';
    }

    public function testNenhumaBibliotecaUsaVersaoFlutuante(): void
    {
        // `latest`, `*` e faixas (`^1.2`) reintroduzem exatamente o risco que a biblioteca elimina.
        foreach (assets_externos_registro() as $nome => $lib) {
            $versao = (string)($lib['versao'] ?? '');

            self::assertNotSame('', $versao, $nome . ' sem versão declarada');
            self::assertDoesNotMatchRegularExpression(
                '/latest|\*|\^|~/i',
                $versao,
                $nome . ': versão flutuante (' . $versao . ')'
            );
        }
    }

    public function testPrefereOArquivoLocalQuandoEleExiste(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-vendor-' . uniqid() . DIRECTORY_SEPARATOR;
        @mkdir($dir . 'sortablejs' . DIRECTORY_SEPARATOR . '1.15.6', 0777, true);
        file_put_contents($dir . 'sortablejs/1.15.6/Sortable.min.js', '// local');

        $lib = assets_externos_registro()['sortablejs'];
        $url = assets_externos_url($lib, 'sortablejs', 'Sortable.min.js', $dir, '/vendor/');

        self::assertSame('/vendor/sortablejs/1.15.6/Sortable.min.js', $url);

        @unlink($dir . 'sortablejs/1.15.6/Sortable.min.js');
        @rmdir($dir . 'sortablejs' . DIRECTORY_SEPARATOR . '1.15.6');
        @rmdir($dir . 'sortablejs');
        @rmdir($dir);
    }

    public function testCaiNoCdnQuandoOArquivoLocalNaoExiste(): void
    {
        // O fallback é deliberado: instalação que ainda não baixou os arquivos continua funcionando,
        // e a migração pode ser feita biblioteca por biblioteca.
        $lib = assets_externos_registro()['sortablejs'];
        $url = assets_externos_url($lib, 'sortablejs', 'Sortable.min.js', '/caminho/inexistente/', '/vendor/');

        self::assertStringStartsWith('https://', $url);
        self::assertStringContainsString('sortablejs@1.15.6', $url);
        self::assertStringNotContainsString('@latest', $url);
    }

    public function testTagsSaoMontadasParaCadaTipoDeArquivo(): void
    {
        $tags = assets_externos_tags('sortablejs', '/inexistente/', '/vendor/');

        self::assertCount(1, $tags['js']);
        self::assertStringContainsString('<script src="', $tags['js'][0]);
        self::assertSame([], $tags['css']);
    }

    public function testBibliotecaDesconhecidaNaoQuebra(): void
    {
        // Erro de digitação no nome não pode derrubar a tela: devolve vazio e quem chama decide.
        $tags = assets_externos_tags('nao-registrada', '', '');

        self::assertSame([], $tags['css']);
        self::assertSame([], $tags['js']);
    }

    public function testNenhumModuloReferenciaOSortableDiretamente(): void
    {
        // A regressão provável é alguém colar de novo a tag do CDN num módulo novo.
        $arquivos = glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php') ?: [];

        self::assertNotEmpty($arquivos);

        $reincidentes = [];
        foreach ($arquivos as $arquivo) {
            $codigo = (string)file_get_contents($arquivo);
            if (strpos($codigo, 'cdn.jsdelivr.net/npm/sortablejs') !== false) {
                $reincidentes[] = basename($arquivo);
            }
        }

        self::assertSame([], $reincidentes, 'inclusão direta do Sortable em: ' . implode(', ', $reincidentes));
    }

    public function testNenhumAssetDeTerceiroUsaLatestNoCore(): void
    {
        // Varre além do Sortable: qualquer `@latest` novo em módulo ou biblioteca falha aqui.
        $arquivos = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        $flutuantes = [];
        foreach ($arquivos as $arquivo) {
            $codigo = (string)file_get_contents($arquivo);
            if (preg_match('#(cdn\.jsdelivr\.net|unpkg\.com)/[^"\']*@latest#', $codigo)) {
                $flutuantes[] = basename($arquivo);
            }
        }

        self::assertSame([], $flutuantes, 'versão flutuante em: ' . implode(', ', $flutuantes));
    }
}
