<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-187 / BATCH-191: correções simples achadas na reescrita da documentação e no deploy da req-186.
 * A correção do `auth:cookie` por SSH está coberta em ProjectSshDeployReq034Test.
 */
final class CorrecoesSimplesReq187Test extends TestCase
{
    private static function ler(string $relativo): string
    {
        return (string) file_get_contents(CONN2FLOW_ROOT . '/' . $relativo);
    }

    public function testTodaBibliotecaRegistradaTemArquivo(): void
    {
        $config = self::ler('gestor/config.php');
        $inicio = strpos($config, "\$_GESTOR['bibliotecas-dados'] = Array(");
        self::assertNotFalse($inicio);
        $fim = strpos($config, ');', (int) $inicio);
        $registro = substr($config, (int) $inicio, (int) $fim - (int) $inicio);

        preg_match_all("/'([\\w.\\/-]+\\.php)'/", $registro, $arquivos);
        self::assertGreaterThan(30, count($arquivos[1]));

        // `api-cliente.php` e `cpanel.php` estavam registrados sem existir: pedi-los era erro fatal.
        foreach ($arquivos[1] as $arquivo) {
            self::assertFileExists(CONN2FLOW_ROOT . '/gestor/bibliotecas/' . $arquivo, "Biblioteca registrada sem arquivo: {$arquivo}");
        }
    }

    public function testDetalheDoPlanoUsaAPastaDeLogs(): void
    {
        $codigo = self::ler('gestor/modulos/admin-atualizacoes/admin-atualizacoes.php');
        $inicio = (int) strpos($codigo, 'function admin_atualizacoes_detalhe(');
        $corpo = substr($codigo, $inicio, 4000);

        // `$dir` não existia neste escopo: todo `?plano=` caía em "plano inválido".
        self::assertStringNotContainsString('realpath($dir.', $corpo);
        self::assertStringContainsString('realpath($dirLogs.$plano)', $corpo);
    }

    public function testVariavelGlobalPrefereAGlobalEDepoisOModuloAtual(): void
    {
        $codigo = self::ler('gestor/bibliotecas/gestor.php');
        $inicio = (int) strpos($codigo, 'function gestor_variaveis_globais(');
        $corpo = substr($codigo, $inicio, 2500);

        // Sem ordem, `module-title` de um módulo qualquer (ex.: checkout) aparecia em outro módulo.
        self::assertStringContainsString('ORDER BY (modulo IS NULL) DESC', $corpo);
        self::assertStringContainsString("(modulo='\".banco_escape_field(\$_GESTOR['modulo-id']).\"') DESC", $corpo);
    }
}
