<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-189 / BATCH-193 — segurança do `interface` (achados A1 e A2 da req-181).
 *
 * A1: a listagem AJAX montava ORDER BY/WHERE com `columns[i][data]`, `columnsExtraSearch` e o termo de
 *     busca do `$_REQUEST`. Agora só vale a configuração guardada na sessão, e o termo é escapado.
 * A2: excluir e status por GET exigem o token CSRF da sessão na query.
 *
 * O `interface.php` roda num processo PHP separado, com dublês de banco e sessão, porque o bootstrap
 * da suíte já carrega `banco.php` e `gestor.php` (as funções não podem ser redefinidas aqui).
 */
final class InterfaceSegurancaReq189Test extends TestCase
{
    /** @return array{sql: list<string>, redirecionou: bool, alerta: string|null, retorno: mixed} */
    private function rodar(string $cenario, array $request, string $metodo = 'GET'): array
    {
        $script = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req189-' . bin2hex(random_bytes(4)) . '.php';
        $interface = var_export(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php', true);
        $req = var_export($request, true);
        $metodo = var_export($metodo, true);
        $cenario = var_export($cenario, true);

        file_put_contents($script, <<<PHP
<?php
\$_SERVER['REQUEST_METHOD'] = {$metodo};
\$_REQUEST = {$req};
\$GLOBALS['sql'] = [];
\$GLOBALS['saida'] = ['redirecionou' => false, 'alerta' => null];
\$_GESTOR = ['modulo' => 'm', 'opcao' => 'listar', 'usuario-id' => 1];
function banco_escape_field(\$v) { return addslashes((string) \$v); }
function banco_campos_virgulas(\$c) { return implode(',', \$c); }
function banco_select_name(\$campos, \$tabela, \$extra) { \$GLOBALS['sql'][] = \$extra; return []; }
function gestor_sessao_variavel(\$k, \$v = null) {
    if (\$v !== null) { if (\$k === 'alerta') \$GLOBALS['saida']['alerta'] = is_array(\$v) ? (string) reset(\$v) : (string) \$v; return null; }
    return [
        'banco' => ['nome' => 'usuarios_teste', 'id' => 'id', 'status' => 'status', 'campos' => ['nome'], 'order' => ' ORDER BY nome asc'],
        'tabela' => ['colunas' => [['id' => 'nome', 'nome' => 'Nome']]],
        'columns' => [
            ['data' => '_gestor_acoes_id', 'orderable' => false, 'searchable' => false],
            ['data' => 'nome'],
            ['data' => 'segredo', 'searchable' => false, 'orderable' => false],
            ['data' => 'status', 'searchable' => false, 'orderable' => false, 'visible' => false],
        ],
        'columnsExtraSearch' => ['id'],
        'registroInicial' => 0, 'registrosPorPagina' => 25, 'totalRegistros' => 0,
    ];
}
function gestor_asset_version() { return '1'; }
function existe(\$v) { return !empty(\$v); }
function gestor_csrf_token() { return 'tok123'; }
function gestor_csrf_validar(\$t) { return is_string(\$t) && hash_equals('tok123', \$t); }
function seguranca_csrf_token_requisicao() { return (string) (\$_REQUEST['_csrf_token'] ?? ''); }
function gestor_variaveis(\$p) { return 'msg'; }
function gestor_redirecionar_raiz() { \$GLOBALS['saida']['redirecionou'] = true; throw new RuntimeException('redirect'); }
require {$interface};
\$retorno = null;
try {
    switch ({$cenario}) {
        case 'listar': \$retorno = interface_listar_ajax(); break;
        case 'excluir': interface_excluir_iniciar(); \$retorno = \$_GESTOR['modulo-registro-id'] ?? null; break;
        case 'status': interface_status_iniciar(); \$retorno = \$_GESTOR['modulo-registro-id'] ?? null; break;
        case 'url': \$retorno = [interface_url_csrf('?opcao=excluir&id=7'), interface_url_csrf('editar/?id=7'), interface_url_csrf('?opcao=status&status=I&id=7&_csrf_token=x'), interface_listar_coluna_segura('nome'), interface_listar_coluna_segura('t.nome'), interface_listar_coluna_segura('nome) OR (1=1'), interface_listar_coluna_segura('_gestor_acoes_id')]; break;
    }
} catch (RuntimeException \$e) {}
echo json_encode(['sql' => \$GLOBALS['sql'], 'redirecionou' => \$GLOBALS['saida']['redirecionou'], 'alerta' => \$GLOBALS['saida']['alerta'], 'retorno' => \$retorno]);
PHP);

        $saida = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1');
        unlink($script);
        $saida = (string) $saida;
        $dados = json_decode(substr($saida, (int) strpos($saida, '{"sql"')), true);
        self::assertIsArray($dados, 'saída do processo: ' . $saida);

        return $dados;
    }

    public function testOrdenacaoUsaSoColunaDeclaradaENaoAceitaColunaDoRequest(): void
    {
        $r = $this->rodar('listar', ['draw' => '1',
            'columns' => [['data' => '_gestor_acoes_id'], ['data' => '(SELECT senha FROM usuarios LIMIT 1)']],
            'order' => [['column' => '1', 'dir' => 'asc']],
        ]);
        $sql = implode("\n", $r['sql']);
        self::assertStringContainsString('ORDER BY nome asc', $sql);
        self::assertStringNotContainsString('SELECT senha', $sql);

        // Coluna não ordenável e índice inexistente são ignorados (fica a ordem padrão).
        $r = $this->rodar('listar', ['draw' => '1', 'order' => [['column' => '2', 'dir' => 'asc'], ['column' => '99', 'dir' => 'desc']]]);
        self::assertStringNotContainsString('segredo', implode("\n", $r['sql']));
    }

    public function testBuscaEscapaOTermoEIgnoraColunasDoRequest(): void
    {
        $r = $this->rodar('listar', ['draw' => '1',
            'columns' => [['data' => 'x'], ['data' => 'senha', 'searchable' => 'true']],
            'columnsExtraSearch' => ['senha', '1) OR (1=1'],
            'search' => ['value' => "a%' OR '1'='1"],
        ]);
        $sql = implode("\n", $r['sql']);
        self::assertStringContainsString("UCASE(nome) LIKE UCASE('%a\\\\%\\' OR \\'1\\'=\\'1%')", $sql, $sql);
        self::assertStringContainsString('UCASE(id) LIKE', $sql);
        self::assertStringNotContainsString('senha', $sql);
        self::assertStringNotContainsString('1) OR (1=1', $sql);
        self::assertStringNotContainsString('segredo', $sql, 'coluna não pesquisável fica fora da busca');
    }

    public function testExcluirEStatusPorGetExigemOTokenDaSessao(): void
    {
        foreach (['excluir', 'status'] as $acao) {
            $sem = $this->rodar($acao, ['id' => '7', 'status' => 'I']);
            self::assertTrue($sem['redirecionou'], "{$acao} sem token redireciona");
            self::assertNotNull($sem['alerta'], "{$acao} sem token avisa o operador");
            self::assertNull($sem['retorno'], "{$acao} sem token não define o registro");

            $errado = $this->rodar($acao, ['id' => '7', 'status' => 'I', '_csrf_token' => 'outro']);
            self::assertTrue($errado['redirecionou'], "{$acao} com token errado redireciona");

            $certo = $this->rodar($acao, ['id' => '7', 'status' => 'I', '_csrf_token' => 'tok123']);
            self::assertFalse($certo['redirecionou'], "{$acao} com token segue");
            self::assertSame('7', $certo['retorno']);
        }
    }

    public function testLinksDoPainelRecebemOTokenEColunaSeguraSoAceitaIdentificador(): void
    {
        $r = $this->rodar('url', []);
        [$excluir, $editar, $jaTem, $nome, $qualificado, $expressao, $acoes] = $r['retorno'];
        self::assertSame('?opcao=excluir&id=7&_csrf_token=tok123', $excluir);
        self::assertSame('editar/?id=7', $editar, 'link que não é excluir/status fica igual');
        self::assertSame('?opcao=status&status=I&id=7&_csrf_token=x', $jaTem, 'não duplica o token');
        self::assertTrue($nome);
        self::assertTrue($qualificado);
        self::assertFalse($expressao);
        self::assertFalse($acoes);
    }

    public function testJavascriptDoPainelAcrescentaOTokenNosLinksDeAcao(): void
    {
        foreach (['interface/interface.js', 'interface-v2/interface-v2.js'] as $arquivo) {
            $js = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/assets/' . $arquivo);
            self::assertStringContainsString('window.interfaceUrlCsrf = function', $js, $arquivo);
            self::assertStringNotContainsString("href=\"?opcao=' + opcoes.opcao", $js, $arquivo);
            self::assertStringNotContainsString('href="?opcao=${opc.opcao}', $js, $arquivo);
        }
    }
}
