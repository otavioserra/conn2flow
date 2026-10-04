<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class InterfaceListarTailwindTest extends TestCase
{
    public function testConfiguracaoVaziaPreservaAllowlistIdiomaEContratoAjax(): void
    {
        $script = CONN2FLOW_ROOT.'/tests/Fixtures/interface-listar-tailwind.php';
        $saida = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' 2>&1');
        $dados = json_decode((string)$saida, true);
        self::assertIsArray($dados, (string)$saida);
        self::assertSame([['1', 'desc']], array_map(fn($o) => [(string)$o[0], $o[1]], $dados['config']['order']));
        self::assertFalse($dados['config']['columns'][2]['orderable']);
        self::assertFalse($dados['config']['columns'][2]['visible']);
        self::assertSame(25, $dados['config']['registrosPorPagina']);
        self::assertSame(0, $dados['config']['registroInicial']);
        self::assertSame([], $dados['result']['data']);
        self::assertSame(0, $dados['result']['recordsFiltered']);
        self::assertSame(0, $dados['result']['recordsTotal']);
        self::assertSame(0, $dados['session']['registroInicial']);
        self::assertSame(100, $dados['session']['registrosPorPagina']);
        self::assertStringContainsString("language='en'", implode(' ', $dados['sql']));
        self::assertStringNotContainsString('senha', implode(' ', $dados['sql']));
        self::assertStringContainsString('interface-listar-tailwind', $dados['gestor']['pagina']);
        self::assertContains('interface/interface-listar-tailwind.js', $dados['gestor']['javascript']);
        self::assertStringNotContainsString('datatables', json_encode($dados['gestor']));
        self::assertStringNotContainsString('interface/interface.js', json_encode($dados['gestor']));
    }

    public function testPilotoDeclaraDependenciasExistentesNosDoisIdiomas(): void
    {
        $modulo = json_decode(file_get_contents(CONN2FLOW_GESTOR_ROOT.'/modulos/modulos-grupos/modulos-grupos.json'), true);
        foreach (['pt-br', 'en'] as $lang) {
            foreach ($modulo['resources'][$lang]['pages'] as $p) {
                self::assertSame('layout-administrativo-tailwind', $p['layout']);
                self::assertSame('tailwindcss', $p['framework_css']);
                foreach ($p['tailwind_dependencies'] as $dep) {
                    self::assertFileExists(CONN2FLOW_GESTOR_ROOT.'/resources/'.$lang.'/components/'.$dep['id'].'/'.$dep['id'].'.html');
                }
            }
        }
    }
}
