<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido.php';

/**
 * req-217: confirmação de origem (retorno ao endereço cadastrado) e chave de sessão no canal.
 *
 * As duas pontas rodam aqui, cada uma com o próprio banco; o transporte entrega a requisição ao lado
 * dono do endereço, como faria a rede. O "atacante" tem o segredo da instalação, mas não o endereço.
 */
final class ModuloDistribuidoOrigemReq217Test extends TestCase
{
    private const CENTRAL = 'https://central.example/_api';
    private const CLIENTE = 'https://cliente.example/_api';
    private const APP = 'req217-app';
    private const SEGREDO = 'segredo-217';

    private array $antes = [];
    /** @var array<string, PDO> */
    private array $bancos = [];
    private array $chamadas = [];

    protected function setUp(): void
    {
        global $_CONFIG;
        $this->antes = [$_CONFIG['modulo-distribuido'] ?? null, $GLOBALS['_MODULO_DISTRIBUIDO_HTTP_STATUS'] ?? null];
        $_CONFIG['modulo-distribuido'] = ['secret' => self::SEGREDO, 'confirmacao-origem' => true, 'central-url' => 'https://central.example'];
        foreach (['central', 'cliente', 'atacante'] as $lado) {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE distributed_exchanges (id TEXT PRIMARY KEY, kind TEXT, payload TEXT, expires_at INTEGER, consumed INTEGER)');
            $this->bancos[$lado] = $pdo;
        }
        $this->chamadas = [];
    }

    protected function tearDown(): void
    {
        global $_CONFIG;
        $_CONFIG['modulo-distribuido'] = $this->antes[0];
        $GLOBALS['_MODULO_DISTRIBUIDO_HTTP_STATUS'] = $this->antes[1];
    }

    /** Roda `$fn` com a configuração de um dos lados (o cliente tem `app-id`; Central e atacante não). */
    private function como(string $lado, callable $fn)
    {
        global $_CONFIG;
        $antes = $_CONFIG['modulo-distribuido']['app-id'] ?? '';
        $_CONFIG['modulo-distribuido']['app-id'] = $lado === 'cliente' ? self::APP : '';
        try {
            return $fn();
        } finally {
            $_CONFIG['modulo-distribuido']['app-id'] = $antes;
        }
    }

    private static function cabecalhos(array $linhas): array
    {
        $mapa = [];
        foreach ($linhas as $linha) {
            [$nome, $valor] = array_map('trim', explode(':', $linha, 2));
            $mapa[strtolower($nome)] = $valor;
        }
        return $mapa;
    }

    /** A "rede": entrega ao lado dono do endereço, que responde como os handlers do core. */
    private function rede(): callable
    {
        return function ($url, $corpo, $linhas) {
            $acao = basename(parse_url($url, PHP_URL_PATH));
            $lado = strpos($url, self::CENTRAL) === 0 ? 'central' : 'cliente';
            $this->chamadas[] = $lado . ':' . $acao;
            $h = self::cabecalhos($linhas);
            $GLOBALS['_MODULO_DISTRIBUIDO_HTTP_STATUS'] = 200;
            return $this->como($lado, function () use ($lado, $acao, $corpo, $h) {
                $pdo = $this->bancos[$lado];
                $payload = json_decode($corpo, true);
                $peer = $lado === 'central' ? (string)($payload['app_id'] ?? '') : 'central';
                if (in_array($acao, MODULO_DISTRIBUIDO_ACOES_ABERTURA, true)) {
                    if ($lado === 'central' && $peer !== self::APP) return $this->recusa('distributed-installation-invalid');
                    $dados = modulo_distribuido_validar_envelope($corpo, $h['x-c2f-signature'] ?? '', self::SEGREDO, MODULO_DISTRIBUIDO_SLUG_CONTA, $pdo);
                    if (!$dados) return $this->recusa('hmac');
                    if ($acao === 'confirmar') return json_encode(['status' => 'success', 'data' => modulo_distribuido_confirmar_origem($dados, self::SEGREDO, $peer, $pdo)]);
                    // O retorno vai SEMPRE ao endereço cadastrado do outro lado, nunca a um informado na requisição.
                    $retorno = ['endpoint' => $lado === 'central' ? self::CLIENTE : self::CENTRAL, 'secret' => self::SEGREDO, 'transporte' => $this->rede()];
                    $sessao = modulo_distribuido_sessao_conceder($dados, self::SEGREDO, $peer, $retorno, $pdo);
                    return $sessao ? json_encode(['status' => 'success', 'data' => $sessao]) : $this->recusa('distributed-origin-unconfirmed');
                }
                $dados = modulo_distribuido_receber($corpo, $h['x-c2f-signature'] ?? '', $h['x-c2f-session'] ?? '', self::SEGREDO,
                    (string)($payload['modulo'] ?? ''), $peer, $pdo);
                return $dados ? json_encode(['status' => 'ok', 'acao' => $acao, 'sessao' => $h['x-c2f-session'] ?? null]) : $this->recusa('distributed-session-invalid');
            });
        };
    }

    private function recusa(string $mensagem): string
    {
        $GLOBALS['_MODULO_DISTRIBUIDO_HTTP_STATUS'] = 401;
        return json_encode(['status' => 'error', 'message' => $mensagem]);
    }

    private function pedido(string $modulo = '_conta'): array
    {
        return ['modulo' => $modulo, 'app_id' => self::APP, 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
    }

    /** Configuração de quem envia: endereço, segredo, quem está do outro lado e o próprio banco. */
    private function canal(string $de, string $para, string $acao): array
    {
        return ['endpoint' => $para === 'central' ? self::CENTRAL : self::CLIENTE, 'slug' => '_conta', 'secret' => self::SEGREDO,
            'peer' => $para === 'central' ? 'central' : self::APP, 'acao' => $acao, 'transporte' => $this->rede(), 'pdo' => $this->bancos[$de]];
    }

    private function sessoes(string $lado): int
    {
        return (int)$this->bancos[$lado]->query("SELECT COUNT(*) FROM distributed_exchanges WHERE kind='sessao'")->fetchColumn();
    }

    public function testClientToCentralConfirmsOriginOpensSessionAndReusesIt(): void
    {
        $resposta = $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        self::assertSame('ok', $resposta['status'] ?? null, json_encode($resposta));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string)$resposta['sessao']);
        // O Central ligou de volta para o endereço cadastrado do cliente antes de abrir a sessão.
        self::assertSame(['central:abrir', 'cliente:confirmar', 'central:estado'], $this->chamadas);
        self::assertSame(1, $this->sessoes('central'));

        $this->chamadas = [];
        $segunda = $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        self::assertSame('ok', $segunda['status']);
        self::assertSame(['central:estado'], $this->chamadas, 'a sessão é reaproveitada');
        self::assertSame($resposta['sessao'], $segunda['sessao']);
    }

    public function testCentralToClientConfirmsOriginTheOtherWay(): void
    {
        $resposta = $this->como('central', fn () => modulo_distribuido_enviar($this->pedido('orders'), array_merge($this->canal('central', 'cliente', 'db'), ['slug' => 'orders'])));
        self::assertSame('ok', $resposta['status'] ?? null, json_encode($resposta));
        self::assertSame(['cliente:abrir', 'central:confirmar', 'cliente:db'], $this->chamadas);
        self::assertSame(1, $this->sessoes('cliente'));
    }

    public function testStolenSecretUsedFromElsewhereOpensNothingInEitherDirection(): void
    {
        // Atacante com o segredo fingindo ser o Central: o cliente pergunta ao Central de verdade, que não criou o desafio.
        $comoCentral = $this->como('atacante', fn () => modulo_distribuido_enviar($this->pedido('orders'),
            array_merge($this->canal('atacante', 'cliente', 'db'), ['slug' => 'orders'])));
        self::assertSame('distributed-origin-unconfirmed', $comoCentral['message'] ?? null);
        self::assertSame(['cliente:abrir', 'central:confirmar'], $this->chamadas);
        self::assertSame(0, $this->sessoes('cliente'));

        // Fingindo ser o cliente: o Central pergunta ao site cadastrado, que não criou o desafio.
        $this->chamadas = [];
        global $_CONFIG;
        $_CONFIG['modulo-distribuido']['app-id'] = self::APP;
        try {
            $comoCliente = modulo_distribuido_enviar($this->pedido(), $this->canal('atacante', 'central', 'estado'));
        } finally {
            $_CONFIG['modulo-distribuido']['app-id'] = '';
        }
        self::assertSame('distributed-origin-unconfirmed', $comoCliente['message'] ?? null);
        self::assertSame(['central:abrir', 'cliente:confirmar'], $this->chamadas);
        self::assertSame(0, $this->sessoes('central'));

        // E sem sessão, a chave da instalação sozinha não passa mais.
        $corpo = json_encode($this->pedido());
        self::assertFalse(modulo_distribuido_receber($corpo, modulo_distribuido_assinar($corpo, self::SEGREDO), '', self::SEGREDO, '_conta', self::APP, $this->bancos['central']));
    }

    public function testOrdinaryRequestNeedsAValidSessionOfThatPeer(): void
    {
        $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        $pdo = $this->bancos['central'];
        $linha = $pdo->query("SELECT id, payload FROM distributed_exchanges WHERE kind='sessao'")->fetch(PDO::FETCH_ASSOC);
        $chave = base64_decode(modulo_distribuido_decifrar($linha['payload'], self::SEGREDO, 'sessao')['chave']);
        $sessao = $this->como('cliente', fn () => modulo_distribuido_sessao_saida($this->canal('cliente', 'central', 'estado')))['sessao'];
        $assinado = function () use ($chave) {
            $corpo = json_encode($this->pedido());
            return [$corpo, modulo_distribuido_assinar($corpo, $chave)];
        };

        [$corpo, $assinatura] = $assinado();
        self::assertIsArray(modulo_distribuido_receber($corpo, $assinatura, $sessao, self::SEGREDO, '_conta', self::APP, $pdo));
        [$corpo, $assinatura] = $assinado();
        self::assertFalse(modulo_distribuido_receber($corpo, $assinatura, $sessao, self::SEGREDO, '_conta', 'outra-instalacao', $pdo), 'sessão de outra instalação');
        [$corpo, $assinatura] = $assinado();
        self::assertFalse(modulo_distribuido_receber($corpo, $assinatura, str_repeat('a', 64), self::SEGREDO, '_conta', self::APP, $pdo), 'sessão forjada');
        [$corpo] = $assinado();
        self::assertFalse(modulo_distribuido_receber($corpo, modulo_distribuido_assinar($corpo, self::SEGREDO), $sessao, self::SEGREDO, '_conta', self::APP, $pdo),
            'assinada com o segredo da instalação em vez da chave da sessão');
        [$corpo, $assinatura] = $assinado();
        self::assertFalse(modulo_distribuido_receber($corpo, $assinatura, $sessao, 'segredo-trocado', '_conta', self::APP, $pdo), 'segredo trocado derruba a sessão');
        $pdo->prepare('UPDATE distributed_exchanges SET expires_at=? WHERE id=?')->execute([time() - 1, $linha['id']]);
        [$corpo, $assinatura] = $assinado();
        self::assertFalse(modulo_distribuido_receber($corpo, $assinatura, $sessao, self::SEGREDO, '_conta', self::APP, $pdo), 'sessão vencida');
    }

    public function testOldChannelStillWorksWhenTheCheckIsOff(): void
    {
        global $_CONFIG;
        $_CONFIG['modulo-distribuido']['confirmacao-origem'] = false;
        $resposta = $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        self::assertSame('ok', $resposta['status'] ?? null);
        self::assertSame(['central:estado'], $this->chamadas);
        self::assertNull($resposta['sessao']);
    }

    public function testSessionForgottenByTheOtherSideIsReopenedOnce(): void
    {
        $primeira = $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        $this->bancos['central']->exec("DELETE FROM distributed_exchanges WHERE kind='sessao'");
        $this->chamadas = [];
        $segunda = $this->como('cliente', fn () => modulo_distribuido_enviar($this->pedido(), $this->canal('cliente', 'central', 'estado')));
        self::assertSame('ok', $segunda['status'] ?? null, json_encode($segunda));
        self::assertSame(['central:estado', 'central:abrir', 'cliente:confirmar', 'central:estado'], $this->chamadas);
        self::assertNotSame($primeira['sessao'], $segunda['sessao']);
    }

    public function testChallengeIsSingleUseAndOnlyForWhomItWasMade(): void
    {
        $pdo = $this->bancos['cliente'];
        $desafio = modulo_distribuido_registro_emitir('origem', ['peer' => 'central'], self::SEGREDO, 60, $pdo);
        $abrir = static function (array $envelope) {
            return json_decode(base64_decode($envelope['body']), true)['confirmado'];
        };
        self::assertFalse($abrir(modulo_distribuido_confirmar_origem(['desafio' => $desafio], self::SEGREDO, 'outra-instalacao', $pdo)), 'feito para outro lado');
        $desafio = modulo_distribuido_registro_emitir('origem', ['peer' => 'central'], self::SEGREDO, 60, $pdo);
        self::assertTrue($abrir(modulo_distribuido_confirmar_origem(['desafio' => $desafio], self::SEGREDO, 'central', $pdo)));
        self::assertFalse($abrir(modulo_distribuido_confirmar_origem(['desafio' => $desafio], self::SEGREDO, 'central', $pdo)), 'uso único');
    }

    public function testHandlersRouteOpeningByRoleAndRequireTheSession(): void
    {
        $api = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php');
        self::assertStringContainsString("(\$abertura && !\$cliente)", $api);
        foreach (['api-module-central.php', 'api-module-distributed.php'] as $arquivo) {
            $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/' . $arquivo);
            self::assertStringContainsString('modulo_distribuido_receber(', $fonte, $arquivo);
            self::assertStringContainsString('modulo_distribuido_sessao_conceder(', $fonte, $arquivo);
            self::assertStringNotContainsString("\$payload['endpoint']", $fonte, $arquivo . ': o retorno nunca vem da requisição');
        }
        $config = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/config.php');
        self::assertStringContainsString("MODULO_DISTRIBUIDO_ORIGIN_CHECK'] ?? 'true'", $config);
    }
}
