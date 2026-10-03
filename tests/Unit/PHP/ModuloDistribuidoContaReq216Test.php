<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido.php';

/**
 * req-216: catálogo local, estado da conta pelo canal e painel só de visualização.
 */
final class ModuloDistribuidoContaReq216Test extends TestCase
{
    private array $antes = [];

    protected function setUp(): void
    {
        global $_CONFIG, $_GESTOR;
        $this->antes = [$_CONFIG['modulo-distribuido'] ?? null, $_GESTOR['ROOT_PATH'] ?? null, $_GESTOR['modulos-path'] ?? null];
        $_CONFIG['modulo-distribuido']['app-id'] = 'req216-app';
        $_CONFIG['modulo-distribuido']['secret'] = 'segredo-216';
        $_CONFIG['modulo-distribuido']['central-url'] = 'https://central.example';
        unset($_CONFIG['modulo-distribuido']['modules'], $_CONFIG['modulo-distribuido']['tables'], $_CONFIG['modulo-distribuido']['account-provider']);
    }

    protected function tearDown(): void
    {
        global $_CONFIG, $_GESTOR;
        $_CONFIG['modulo-distribuido'] = $this->antes[0];
        $_GESTOR['ROOT_PATH'] = $this->antes[1];
        $_GESTOR['modulos-path'] = $this->antes[2];
        unset($GLOBALS['_BANCO']['distribuido']);
    }

    private function pdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE distributed_exchanges (id TEXT PRIMARY KEY, kind TEXT, payload TEXT, expires_at INTEGER, consumed INTEGER)');
        return $pdo;
    }

    /** Central de mentira: responde o estado assinado com o segredo da instalação. */
    private function central(array &$chamadas, array $dados, string $segredo = 'segredo-216'): callable
    {
        return static function ($url, $corpo, $headers) use (&$chamadas, $dados, $segredo) {
            $chamadas[] = $url;
            $body = json_encode($dados + ['modulo' => '_conta', 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))]);
            return json_encode(['status' => 'success', 'data' => ['body' => base64_encode($body), 'signature' => modulo_distribuido_assinar($body, $segredo)]]);
        };
    }

    public function testLocalCatalogReplacesTheEnvListsAndTheEnvStillWins(): void
    {
        global $_GESTOR, $_CONFIG;
        $raiz = sys_get_temp_dir() . '/c2f-req216-' . bin2hex(random_bytes(5)) . '/';
        mkdir($raiz . 'project', 0777, true);
        file_put_contents($raiz . 'project/distributed-modules.json', json_encode(['modules' => ['orders', 'products', '../x'], 'tables' => ['orders', 'products', 'x y']]));
        $_GESTOR['ROOT_PATH'] = $raiz;
        self::assertSame(['orders', 'products'], modulo_distribuido_modulos_locais());
        self::assertSame(['orders', 'products'], modulo_distribuido_tabelas_locais());
        $_CONFIG['modulo-distribuido']['modules'] = ['coupons'];
        self::assertSame(['coupons'], modulo_distribuido_modulos_locais());
        // No Central (sem `app-id`) o catálogo não faz dele um host distribuído.
        unset($_CONFIG['modulo-distribuido']['modules']);
        $_CONFIG['modulo-distribuido']['app-id'] = '';
        self::assertSame([], modulo_distribuido_modulos_locais());
        self::assertSame([], modulo_distribuido_tabelas_locais());
        unlink($raiz . 'project/distributed-modules.json'); rmdir($raiz . 'project'); rmdir($raiz);
    }

    public function testCentralAccountComesFromTheProjectProviderAndFailsOpenToActive(): void
    {
        global $_CONFIG;
        self::assertSame(['estado' => 'ativo', 'modulos' => null, 'destino' => null, 'mensagem' => ''], modulo_distribuido_conta('req216-app'));
        $_CONFIG['modulo-distribuido']['account-provider'] = static function ($app) {
            return ['estado' => 'suspenso', 'modulos' => ['orders', 7], 'destino' => 'https://central.example/user-subscription/', 'mensagem' => 'Pague'];
        };
        self::assertSame(['estado' => 'suspenso', 'modulos' => ['orders'], 'destino' => 'https://central.example/user-subscription/', 'mensagem' => 'Pague'], modulo_distribuido_conta('req216-app'));
        $_CONFIG['modulo-distribuido']['account-provider'] = static function () { return ['estado' => 'pirata', 'destino' => 'javascript:alert(1)']; };
        $conta = modulo_distribuido_conta('req216-app');
        self::assertSame('ativo', $conta['estado']);
        self::assertNull($conta['destino']);
        $_CONFIG['modulo-distribuido']['account-provider'] = static function () { throw new RuntimeException('cobrança fora do ar'); };
        self::assertSame('ativo', modulo_distribuido_conta('req216-app')['estado']);
    }

    public function testCustomerAsksTheCentralCachesAndKeepsActivatedModules(): void
    {
        $pdo = $this->pdo();
        $chamadas = [];
        $conta = modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $pdo, 'transporte' => $this->central($chamadas, ['estado' => 'ativo', 'modulos' => ['products', 'orders'], 'destino' => null])]);
        self::assertSame('ativo', $conta['estado']);
        self::assertSame(['products', 'orders'], $conta['ativados']);
        self::assertSame('https://central.example/_api/modulo-distribuido/_conta/estado', $chamadas[0]);

        // Dentro da validade não pergunta de novo.
        modulo_distribuido_conta_local(['pdo' => $pdo, 'transporte' => $this->central($chamadas, ['estado' => 'suspenso', 'modulos' => []])]);
        self::assertCount(1, $chamadas);

        // O plano perdeu os módulos e a conta foi suspensa: o que já foi ativado continua ativado.
        $conta = modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $pdo, 'transporte' => $this->central($chamadas, ['estado' => 'suspenso', 'modulos' => ['subscriptions'], 'destino' => 'https://central.example/user-subscription/'])]);
        self::assertSame('suspenso', $conta['estado']);
        self::assertSame(['subscriptions'], $conta['modulos']);
        self::assertSame(['products', 'orders', 'subscriptions'], $conta['ativados']);
        self::assertFalse(modulo_distribuido_aceita_venda_nova());
        self::assertSame('suspenso', modulo_distribuido_conta_estado());
    }

    public function testCentralDownOrForgedAnswerKeepsTheLastKnownState(): void
    {
        $pdo = $this->pdo();
        $chamadas = [];
        modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $pdo, 'transporte' => $this->central($chamadas, ['estado' => 'carencia', 'modulos' => ['orders']])]);
        $fora = modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $pdo, 'transporte' => static function () { return false; }]);
        self::assertSame('carencia', $fora['estado']);
        self::assertSame(['orders'], $fora['ativados']);
        $forjada = modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $pdo, 'transporte' => $this->central($chamadas, ['estado' => 'encerrado', 'modulos' => []], 'outro-segredo')]);
        self::assertSame('carencia', $forjada['estado'], 'resposta assinada com outro segredo é ignorada');
        self::assertTrue(modulo_distribuido_aceita_venda_nova());
        // A linha guardada não é varrida com as trocas vencidas.
        self::assertGreaterThan(time() + 86400 * 365, (int)$pdo->query("SELECT expires_at FROM distributed_exchanges WHERE kind='conta'")->fetchColumn());
        // A coluna `id` da tabela tem 64 caracteres no MySQL.
        self::assertLessThanOrEqual(64, (int)$pdo->query("SELECT LENGTH(id) FROM distributed_exchanges WHERE kind='conta'")->fetchColumn());
    }

    public function testNeverAnsweredAndNoEnvMeansNothingActivated(): void
    {
        $conta = modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $this->pdo(), 'transporte' => static function () { return false; }]);
        self::assertFalse($conta['conhecido']);
        self::assertSame([], $conta['ativados']);
        self::assertTrue(modulo_distribuido_aceita_venda_nova(), 'sem resposta não se presume inadimplência');
    }

    public function testExecutionCopyRunsWhenInThePlanOrActivatedBefore(): void
    {
        global $_GESTOR;
        $raiz = sys_get_temp_dir() . '/c2f-req216m-' . bin2hex(random_bytes(5)) . '/';
        foreach (['req216-loja' => ['scope' => 'distributed-execution', 'panel' => false, 'active_with' => ['req216-produtos']],
                  'req216-avaliacoes' => ['scope' => 'distributed-execution']] as $id => $m) {
            mkdir($raiz . $id, 0777, true);
            file_put_contents($raiz . $id . '/' . $id . '.json', json_encode($m));
        }
        $_GESTOR['modulos-path'] = $raiz;
        $chamadas = [];
        modulo_distribuido_conta_local(['forcar' => true, 'pdo' => $this->pdo(), 'transporte' => $this->central($chamadas, ['estado' => 'suspenso', 'modulos' => ['req216-produtos']])]);
        self::assertTrue(modulo_distribuido_execucao_ativa('req216-loja'), 'ativa junto com o módulo do plano, mesmo suspensa');
        self::assertFalse(modulo_distribuido_execucao_ativa('req216-avaliacoes'), 'fora do plano e nunca ativada');
        foreach (['req216-loja', 'req216-avaliacoes'] as $id) { unlink($raiz . $id . '/' . $id . '.json'); rmdir($raiz . $id); }
        rmdir($raiz);
    }

    public function testReadOnlyPanelRefusesWritesAndNonReadingRoutinesInTheChannel(): void
    {
        $enviados = [];
        banco_distribuido_iniciar(['slug' => 'orders', 'endpoint' => 'https://cliente.example/_api', 'secret' => 's', 'somente-leitura' => true,
            'transporte' => static function ($url, $corpo) use (&$enviados) {
                $enviados[] = json_decode($corpo, true);
                return json_encode(['status' => 'ok', 'tipo' => 'select', 'fields' => ['id'], 'rows' => [['1']]]);
            }]);
        self::assertFalse(banco_distribuido_query("UPDATE orders SET admin_notes='x' WHERE id_orders=1"));
        self::assertFalse(banco_distribuido_query("DELETE FROM orders WHERE id_orders=1"));
        self::assertFalse(banco_distribuido_query("INSERT INTO orders (id) VALUES ('x')"));
        self::assertCount(0, $enviados, 'nenhuma escrita sai para o site do cliente');
        self::assertNotFalse(banco_distribuido_query('SELECT id FROM orders'));
        self::assertCount(1, $enviados);
        self::assertSame(['ok' => false, 'erro' => 'read-only'], modulo_distribuido_rotina('fn.ecommerce_order_refund', []));
        self::assertNotSame(['ok' => false, 'erro' => 'read-only'], modulo_distribuido_rotina('site.select', [], ['leitura' => true]));
        banco_distribuido_finalizar();
    }

    public function testWriteRequestDetection(): void
    {
        global $_GESTOR;
        $antes = [$_GESTOR['opcao'] ?? null, $_GESTOR['ajax'] ?? null, $_SERVER['REQUEST_METHOD'] ?? null];
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GESTOR['ajax'] = false; $_GESTOR['opcao'] = 'listar';
        self::assertFalse(modulo_distribuido_pedido_de_escrita());
        $_GESTOR['opcao'] = 'excluir';
        self::assertTrue(modulo_distribuido_pedido_de_escrita());
        $_GESTOR['opcao'] = 'editar'; $_SERVER['REQUEST_METHOD'] = 'POST';
        self::assertTrue(modulo_distribuido_pedido_de_escrita());
        $_GESTOR['ajax'] = true;
        self::assertFalse(modulo_distribuido_pedido_de_escrita(), 'AJAX de leitura passa; a escrita é recusada no canal');
        [$_GESTOR['opcao'], $_GESTOR['ajax'], $_SERVER['REQUEST_METHOD']] = $antes;
    }

    public function testCentralRefusesAccountActionWithoutInstallationAndAnswersSigned(): void
    {
        $fonte = file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api-module-central.php');
        $pos = strpos($fonte, "case 'estado':");
        self::assertNotFalse($pos);
        $trecho = substr($fonte, $pos, 900);
        self::assertStringContainsString('!$instalacao || $slug !== MODULO_DISTRIBUIDO_SLUG_CONTA', $trecho);
        self::assertStringContainsString("modulo_distribuido_assinar(\$body, \$secret)", $trecho);
        self::assertStringContainsString("'estado'], true)", file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php'));
    }
}
