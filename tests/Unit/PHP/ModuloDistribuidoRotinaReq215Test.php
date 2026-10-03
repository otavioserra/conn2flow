<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido.php';

/**
 * req-215: rotina local do módulo de execução, pedida pelo Central pelo canal assinado.
 */
final class ModuloDistribuidoRotinaReq215Test extends TestCase
{
    private string $modulos;

    protected function setUp(): void
    {
        $this->modulos = sys_get_temp_dir() . '/c2f-req215-' . bin2hex(random_bytes(6)) . '/';
        mkdir($this->modulos . 'orders', 0777, true);
        file_put_contents($this->modulos . 'orders/orders.json', json_encode([
            'scope' => 'distributed-execution',
            'routines' => [
                'soma' => ['function' => 'req215_soma', 'file' => 'orders.rotinas.php'],
                'eco' => ['function' => 'req215_eco', 'file' => 'orders.rotinas.php'],
                'falha' => ['function' => 'req215_falha', 'file' => 'orders.rotinas.php'],
                'idioma' => ['function' => 'req215_idioma', 'file' => 'orders.rotinas.php'],
                'sem-funcao' => ['function' => 'req215_nao_existe', 'file' => 'orders.rotinas.php'],
                'sem-arquivo' => ['function' => 'req215_soma', 'file' => 'ausente.php'],
                'fora-da-pasta' => ['function' => 'req215_soma', 'file' => '../orders.rotinas.php'],
                'funcao-ruim' => ['function' => 'phpinfo();'],
            ],
        ]));
        file_put_contents($this->modulos . 'orders/orders.rotinas.php', <<<'PHP'
<?php
if (!function_exists('req215_soma')) {
    function req215_soma($a, $b) { return ['soma' => $a + $b]; }
    function req215_eco($texto) { echo 'saída que não pode vazar'; return $texto; }
    function req215_falha() { throw new RuntimeException('segredo do cliente em /home/cliente'); }
    function req215_idioma() { return [$GLOBALS['_GESTOR']['linguagem-codigo'] ?? null, $GLOBALS['_GESTOR']['distributed-routine'] ?? null]; }
}
PHP);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->modulos . 'orders/*') ?: [] as $arquivo) unlink($arquivo);
        @rmdir($this->modulos . 'orders');
        @rmdir($this->modulos);
        unset($GLOBALS['_BANCO']['distribuido'], $GLOBALS['_GESTOR']['distributed-routine']);
    }

    public function testOnlyDeclaredRoutinesResolve(): void
    {
        $manifesto = json_decode(file_get_contents($this->modulos . 'orders/orders.json'), true);
        self::assertSame(['function' => 'req215_soma', 'file' => 'orders.rotinas.php', 'libraries' => []],
            modulo_distribuido_rotina_resolver($manifesto, 'soma'));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, 'req215_soma'));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, 'phpinfo'));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, ''));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, ['soma']));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, '../soma'));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, 'fora-da-pasta'));
        self::assertNull(modulo_distribuido_rotina_resolver($manifesto, 'funcao-ruim'));
        self::assertNull(modulo_distribuido_rotina_resolver(['routines' => ['x' => ['function' => 'f', 'libraries' => ['../banco']]]], 'x'));
        self::assertNull(modulo_distribuido_rotina_resolver([], 'soma'));
    }

    public function testDeclaredRoutineRunsAndReturns(): void
    {
        $resposta = modulo_distribuido_rotina_executar('orders', ['rotina' => 'soma', 'args' => [2, 3]], $this->modulos);
        self::assertSame(['status' => 'ok', 'retorno' => ['soma' => 5]], $resposta);
    }

    public function testNamedArgumentsFromTheCentralBecomePositional(): void
    {
        $resposta = modulo_distribuido_rotina_executar('orders', ['rotina' => 'soma', 'args' => ['b' => 1, 'a' => 4]], $this->modulos);
        self::assertSame(['soma' => 5], $resposta['retorno']);
    }

    public function testUndeclaredOrBrokenRoutinesAreDenied(): void
    {
        foreach (['req215_soma', 'nao-declarada', 'sem-funcao', 'sem-arquivo', 'fora-da-pasta', 'funcao-ruim'] as $nome) {
            self::assertSame(['status' => 'error', 'message' => 'routine-denied'],
                modulo_distribuido_rotina_executar('orders', ['rotina' => $nome, 'args' => []], $this->modulos), $nome);
        }
        self::assertSame('routine-denied', modulo_distribuido_rotina_executar('products', ['rotina' => 'soma', 'args' => []], $this->modulos)['message']);
        self::assertSame('routine-invalid', modulo_distribuido_rotina_executar('orders', ['rotina' => 'soma', 'args' => 'x'], $this->modulos)['message']);
    }

    public function testFailureAndOutputDoNotLeak(): void
    {
        $nivel = ob_get_level();
        $falha = modulo_distribuido_rotina_executar('orders', ['rotina' => 'falha', 'args' => []], $this->modulos);
        self::assertSame(['status' => 'error', 'message' => 'routine-failed'], $falha);
        ob_start();
        $eco = modulo_distribuido_rotina_executar('orders', ['rotina' => 'eco', 'args' => ['olá']], $this->modulos);
        self::assertSame('', ob_get_clean());
        self::assertSame('olá', $eco['retorno']);
        self::assertSame($nivel, ob_get_level());
    }

    public function testLanguageAndRequesterReachTheRoutineAndAreCleared(): void
    {
        global $_GESTOR;
        $antes = $_GESTOR['linguagem-codigo'] ?? null;
        $_GESTOR['languages'] = ['pt-br', 'en'];
        $resposta = modulo_distribuido_rotina_executar('orders', ['rotina' => 'idioma', 'args' => [], 'linguagem' => 'en',
            'usuario' => ['id' => '7', 'nome' => 'Operadora', 'senha' => 'x']], $this->modulos);
        self::assertSame('en', $resposta['retorno'][0]);
        self::assertSame(['modulo' => 'orders', 'rotina' => 'idioma', 'usuario' => ['id' => 7, 'nome' => 'Operadora']], $resposta['retorno'][1]);
        self::assertArrayNotHasKey('distributed-routine', $_GESTOR);

        $_GESTOR['linguagem-codigo'] = 'pt-br';
        $resposta = modulo_distribuido_rotina_executar('orders', ['rotina' => 'idioma', 'args' => [], 'linguagem' => 'xx'], $this->modulos);
        self::assertSame('pt-br', $resposta['retorno'][0]);
        $resposta = modulo_distribuido_rotina_executar('orders', ['rotina' => 'idioma', 'args' => [], 'linguagem' => '../en'], $this->modulos);
        self::assertSame('pt-br', $resposta['retorno'][0]);
        $_GESTOR['linguagem-codigo'] = $antes;
        unset($_GESTOR['languages']);
    }

    public function testCentralKeepsLocalBehaviourOutsideADistributedContext(): void
    {
        unset($GLOBALS['_BANCO']['distribuido']);
        self::assertNull(modulo_distribuido_rotina('soma', [1, 2]));
    }

    public function testCentralSendsASignedRequestAndReadsTheReturn(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE distributed_exchanges (id TEXT PRIMARY KEY, kind TEXT, payload TEXT, expires_at INTEGER, consumed INTEGER)');
        $modulos = $this->modulos;
        $visto = [];
        banco_distribuido_iniciar(['slug' => 'orders', 'endpoint' => 'https://cliente.example/_api', 'secret' => 'segredo', 'tables' => ['orders'],
            'transporte' => static function ($url, $corpo, $headers) use ($pdo, $modulos, &$visto) {
                $visto[] = $url;
                $assinatura = '';
                foreach ($headers as $h) if (stripos($h, 'X-C2F-Signature: ') === 0) $assinatura = substr($h, 17);
                $payload = modulo_distribuido_validar_envelope($corpo, $assinatura, 'segredo', 'orders', $pdo);
                if (!$payload) return json_encode(['status' => 'error', 'message' => 'assinatura']);
                return json_encode(modulo_distribuido_rotina_executar('orders', $payload, $modulos));
            }]);

        self::assertSame(['ok' => true, 'retorno' => ['soma' => 9]], modulo_distribuido_rotina('soma', [4, 5]));
        self::assertSame('https://cliente.example/_api/modulo-distribuido/orders/rotina', $visto[0]);
        self::assertSame(['ok' => false, 'erro' => 'routine-denied'], modulo_distribuido_rotina('nao-declarada'));
        self::assertSame(['ok' => false, 'erro' => 'routine-failed'], modulo_distribuido_rotina('falha'));
        banco_distribuido_finalizar();
    }

    public function testReplayedRoutineRequestIsRefused(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE distributed_exchanges (id TEXT PRIMARY KEY, kind TEXT, payload TEXT, expires_at INTEGER, consumed INTEGER)');
        $corpo = json_encode(['modulo' => 'orders', 'rotina' => 'soma', 'args' => [1, 1], 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))]);
        $assinatura = modulo_distribuido_assinar($corpo, 'segredo');
        self::assertIsArray(modulo_distribuido_validar_envelope($corpo, $assinatura, 'segredo', 'orders', $pdo));
        self::assertFalse(modulo_distribuido_validar_envelope($corpo, $assinatura, 'segredo', 'orders', $pdo));
        self::assertFalse(modulo_distribuido_validar_envelope($corpo, modulo_distribuido_assinar($corpo, 'outro'), 'segredo', 'orders', $pdo));
    }

    public function testTransportFailureFailsClosed(): void
    {
        banco_distribuido_iniciar(['slug' => 'orders', 'endpoint' => 'https://cliente.example/_api', 'secret' => 'segredo',
            'transporte' => static function () { return false; }]);
        $resposta = modulo_distribuido_rotina('soma', [1, 2]);
        self::assertFalse($resposta['ok']);
        banco_distribuido_finalizar();
    }

    public function testExecutionCopyOnlyActsWhenTheInstallationContractedTheModule(): void
    {
        global $_GESTOR, $_CONFIG;
        $antes = [$_GESTOR['modulos-path'] ?? null, $_CONFIG['modulo-distribuido']['modules'] ?? null];
        $_GESTOR['modulos-path'] = $this->modulos;
        foreach (['store' => ['scope' => 'distributed-execution', 'panel' => false, 'active_with' => ['products']],
                  'coupons' => ['scope' => 'distributed-execution'],
                  'comum' => ['versao' => '1.0.0']] as $id => $manifesto) {
            mkdir($this->modulos . $id);
            file_put_contents($this->modulos . $id . '/' . $id . '.json', json_encode($manifesto));
        }

        $_CONFIG['modulo-distribuido']['modules'] = ['orders', 'products'];
        self::assertTrue(modulo_distribuido_execucao_ativa('orders'), 'contratado');
        self::assertTrue(modulo_distribuido_execucao_ativa('store'), 'ativo junto com products');
        self::assertFalse(modulo_distribuido_execucao_ativa('coupons'), 'cópia de execução não contratada');
        self::assertTrue(modulo_distribuido_execucao_ativa('comum'), 'módulo comum não é cópia de execução');
        self::assertTrue(modulo_distribuido_execucao_ativa('nao-existe'));
        self::assertTrue(modulo_distribuido_execucao_ativa(''));
        self::assertTrue(modulo_distribuido_execucao_ativa('coupons', 'algum-plugin'), 'módulo de plugin não é cópia de execução');
        self::assertTrue(modulo_distribuido_execucao_ativa('../orders'));

        foreach (['store', 'coupons', 'comum'] as $id) { unlink($this->modulos . $id . '/' . $id . '.json'); rmdir($this->modulos . $id); }
        $_GESTOR['modulos-path'] = $antes[0];
        $_CONFIG['modulo-distribuido']['modules'] = $antes[1];
    }

    public function testEveryEntryPointAsksBeforeRunningAnExecutionCopy(): void
    {
        foreach ([
            '/gestor.php' => 'unset($paginas);',
            '/bibliotecas/widgets.php' => "return '';",
            '/bibliotecas/hooks.php' => 'continue;',
            '/bibliotecas/cron.php' => "'status' => 'aviso'",
            '/controladores/plataforma-gateways/plataforma-gateways.php' => 'continue;',
        ] as $arquivo => $efeito) {
            $fonte = file_get_contents(CONN2FLOW_GESTOR_ROOT . $arquivo);
            $pos = strpos($fonte, '!modulo_distribuido_execucao_ativa(');
            self::assertNotFalse($pos, $arquivo);
            self::assertStringContainsString($efeito, substr($fonte, $pos, 320), $arquivo);
        }
        $proxy = file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido-protocolo.php');
        self::assertStringContainsString("(\$copia['panel'] ?? true) === false) return false;", $proxy);
    }

    public function testPanelQueryStringIsRebuiltWithoutRouterOrAjaxParameters(): void
    {
        self::assertSame('id=260928-K7Q2MX', modulo_distribuido_consulta_canonica(['id' => '260928-K7Q2MX']));
        self::assertSame('code=a%20b&state=x%26y', modulo_distribuido_consulta_canonica(['code' => 'a b', 'state' => 'x&y']));
        self::assertSame('id=1', modulo_distribuido_consulta_canonica(['id' => '1', '_gestor-caminho' => 'orders/', 'ajax' => 'sim', 'ajaxOpcao' => 'x']));
        self::assertSame('', modulo_distribuido_consulta_canonica(['lista' => ['a', 'b'], '1x' => 'y', 'a"b' => 'c']));
        self::assertSame('', modulo_distribuido_consulta_canonica(['x' => str_repeat('a', 2100)]));
        self::assertSame('', modulo_distribuido_consulta_canonica('id=1'));
        // O Central reconstrói o que chegou: parse_str + a mesma limpeza.
        parse_str('id=9&_gestor-caminho=../x&b[]=1', $p);
        self::assertSame('id=9', modulo_distribuido_consulta_canonica($p));
    }

    public function testClientHandlerOnlyServesContractedModules(): void
    {
        $fonte = file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api-module-distributed.php');
        $inicio = strpos($fonte, 'function api_module_distributed_rotina(');
        self::assertNotFalse($inicio);
        $corpo = substr($fonte, $inicio, 1400);
        self::assertStringContainsString("modulo_distribuido_config_get('modulo-distribuido.modules'", $corpo);
        self::assertStringContainsString("case 'rotina':", $fonte);
        // A assinatura é conferida antes de qualquer ação, inclusive esta.
        self::assertLessThan(strpos($fonte, "case 'rotina':"), strpos($fonte, 'modulo_distribuido_validar_envelope('));
    }
}
