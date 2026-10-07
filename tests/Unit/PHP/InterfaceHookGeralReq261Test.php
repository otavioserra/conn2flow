<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-261 — ponto de extensão geral das telas do painel: `interface` / `pagina`, disparado junto com os pontos
 * por módulo, com o módulo e a opção da tela como argumentos.
 */
final class InterfaceHookGeralReq261Test extends TestCase
{
    private function trecho(): string
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/interface.php');
        // O mesmo título abre dois blocos; o da tela pronta é o último.
        $inicio = strrpos($fonte, '// ===== Disparar hook de página');
        $this->assertNotFalse($inicio);

        return substr($fonte, $inicio, 1200);
    }

    public function testPontoGeralEDisparadoDepoisDosPontosDoModuloESoForaDeEnvio(): void
    {
        $trecho = $this->trecho();
        $condicao = strpos($trecho, "if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {");
        $doModulo = strpos($trecho, "hook_do_action(\$_GESTOR['modulo-id'], \$_GESTOR['opcao'] . '.pagina');");
        $geral = strpos($trecho, "hook_do_action('interface', 'pagina',");

        $this->assertNotFalse($condicao);
        $this->assertNotFalse($doModulo);
        $this->assertNotFalse($geral);
        $this->assertTrue($condicao < $doModulo && $doModulo < $geral);
        // O disparo geral fica dentro do mesmo bloco: nenhuma chave fecha entre a condição e ele além da do `if` interno.
        $entre = substr($trecho, $condicao, $geral - $condicao);
        $this->assertSame(substr_count($entre, '{') - 1, substr_count($entre, '}'));
    }

    public function testModuloEOpcaoChegamComoArgumentos(): void
    {
        $stubs = <<<'PHP'
<?php
$_GESTOR = ['modulo-id' => 'admin-paginas', 'opcao' => 'editar', 'interface-opcao' => 'editar-extra'];
$_SERVER['REQUEST_METHOD'] = $argv[1];
$GLOBALS['disparos'] = [];
function hook_do_action(string $ns, string $evento, mixed ...$args): void { $GLOBALS['disparos'][] = [$ns, $evento, $args]; }
PHP;
        $trecho = $this->trecho();
        $inicio = strpos($trecho, "if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {");
        $fim = strpos($trecho, "hook_do_action('interface', 'pagina',");
        $fim = strpos($trecho, "\n\t}", $fim) + 3;
        $bloco = substr($trecho, $inicio, $fim - $inicio);

        $arquivo = tempnam(sys_get_temp_dir(), 'req261');
        file_put_contents($arquivo, $stubs . "\n" . $bloco . "\necho json_encode(\$GLOBALS['disparos']);\n");
        $saidas = [];
        foreach (['GET', 'POST'] as $metodo) {
            $linhas = [];
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' ' . $metodo, $linhas);
            $saidas[$metodo] = json_decode((string)end($linhas), true);
        }
        @unlink($arquivo);

        $this->assertSame([
            ['admin-paginas', 'editar.pagina', []],
            ['admin-paginas', 'editar-extra.pagina', []],
            ['interface', 'pagina', ['admin-paginas', 'editar']],
        ], $saidas['GET']);
        $this->assertSame([], $saidas['POST']);
    }

    public function testDocumentacaoCitaOPontoGeralNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $idioma) {
            $doc = (string)file_get_contents(dirname(CONN2FLOW_GESTOR_ROOT) . "/ai-workspace/$idioma/docs/concepts/hooks.md");
            $this->assertStringContainsString('| `interface` | `pagina` |', $doc, $idioma);
            $this->assertStringContainsString('`$modulo`, `$opcao`', $doc, $idioma);
        }
    }
}
