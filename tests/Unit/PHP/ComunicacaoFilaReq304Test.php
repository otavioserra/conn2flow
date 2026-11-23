<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/PHPMailer/src/Exception.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/PHPMailer/src/PHPMailer.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/PHPMailer/src/SMTP.php';

/**
 * PHPMailer sem rede: registra o estado de cada `send()` e falha quando pedido.
 */
final class ComunicacaoReq304MailerDuble extends PHPMailer
{
    /** @var array<int, array<string, mixed>> */
    public static array $enviados = [];
    public static bool $falhar = false;
    public static string $erro = 'SMTP Error: Could not authenticate.';

    public function send()
    {
        if (self::$falhar) {
            $this->ErrorInfo = self::$erro;
            throw new PHPMailerException(self::$erro);
        }

        self::$enviados[] = [
            'to' => array_column($this->getToAddresses(), 0),
            'cc' => array_column($this->getCcAddresses(), 0),
            'bcc' => array_column($this->getBccAddresses(), 0),
            'subject' => $this->Subject,
            'altBody' => $this->AltBody,
            'timeout' => $this->Timeout,
            'headers' => $this->getCustomHeaders(),
            'password' => $this->Password,
        ];

        return true;
    }
}

/**
 * REQ-304 / BATCH-313 — mensageria do Core: segredo fora do log, redirecionamento de teste,
 * AltBody, timeout, fila híbrida, idempotência, worker (reserva atômica, rate limit, backoff,
 * travados, retenção) e contrato multi-canal `comunicacao.drivers`.
 *
 * O banco é um SQLite em memória injetado como executor da fila (`$_GESTOR['comunicacao-fila-db']`)
 * e o relógio é controlado (`$_GESTOR['comunicacao-relogio']`), então nada depende de MySQL,
 * SMTP real ou do horário da máquina.
 */
final class ComunicacaoFilaReq304Test extends TestCase
{
    private const SENHA = 'S3nh@-Sup3r-Secreta';

    private PDO $pdo;
    private int $agora;
    private string $logs;
    private array $gestorOriginal;
    private array $configOriginal;

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/comunicacao.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/log.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/hooks.php';
    }

    protected function setUp(): void
    {
        global $_GESTOR, $_CONFIG;

        $this->gestorOriginal = $_GESTOR;
        $this->configOriginal = $_CONFIG;

        ComunicacaoReq304MailerDuble::$enviados = [];
        ComunicacaoReq304MailerDuble::$falhar = false;

        $this->agora = strtotime('2026-11-23 10:00:10');
        $this->logs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req304-' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR;
        mkdir($this->logs, 0777, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE comunicacao_mensagens (
                id_comunicacao_mensagens INTEGER PRIMARY KEY AUTOINCREMENT,
                id_hosts INTEGER NULL,
                canal VARCHAR(32) NOT NULL DEFAULT \'email\',
                status VARCHAR(24) NOT NULL DEFAULT \'pendente\',
                prioridade INTEGER NOT NULL DEFAULT 2,
                destinatario VARCHAR(255) NULL,
                destinatario_original VARCHAR(255) NULL,
                assunto VARCHAR(255) NULL,
                payload_json TEXT NULL,
                chave_idempotencia VARCHAR(128) NULL,
                tentativas INTEGER NOT NULL DEFAULT 0,
                max_tentativas INTEGER NOT NULL DEFAULT 4,
                proxima_tentativa_em DATETIME NULL,
                reservado_em DATETIME NULL,
                erro_ultimo TEXT NULL,
                enviado_em DATETIME NULL,
                data_criacao DATETIME NOT NULL,
                data_modificacao DATETIME NOT NULL
            )'
        );
        $this->pdo->exec('CREATE UNIQUE INDEX idx_idem ON comunicacao_mensagens (chave_idempotencia)');

        $pdo = $this->pdo;
        $_GESTOR['comunicacao-fila-db'] = [
            'consultar' => static function (string $sql) use ($pdo): array {
                if (str_starts_with($sql, 'SELECT GET_LOCK(')) return [['adquirida' => 1]];
                if (str_starts_with($sql, 'SELECT RELEASE_LOCK(')) return [];
                return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            },
            'executar' => static fn(string $sql): int => (int)$pdo->exec($sql),
            'inserir' => static function (string $sql) use ($pdo) {
                $pdo->exec($sql);
                return (int)$pdo->lastInsertId();
            },
            'escapar' => static fn(string $v): string => str_replace("'", "''", $v),
            'disponivel' => static fn(): bool => true,
        ];
        $_GESTOR['comunicacao-relogio'] = fn(): int => $this->agora;
        $_GESTOR['comunicacao-mailer-factory'] = static fn() => new ComunicacaoReq304MailerDuble(true);
        $_GESTOR['logs-path'] = $this->logs;
        $_GESTOR['debug'] = false;
        unset($_GESTOR['comunicacao-fila-disponivel'], $_GESTOR['comunicacao-redirect-aviso']);
        $this->definirFiltroDrivers('');

        $_CONFIG['email'] = [
            'ativo' => true,
            'server' => ['host' => 'smtp.exemplo.test', 'user' => 'noreply@exemplo.test', 'pass' => self::SENHA, 'port' => 465, 'secure' => true],
            'sender' => ['from' => 'noreply@exemplo.test', 'fromName' => 'Conn2Flow'],
            'ambiente' => 'development',
            'redirect_to' => '',
            'timeout' => 15,
            'fila' => ['modo' => 'hibrido', 'rate_limit' => 30, 'max_tentativas' => 4, 'retencao_dias' => 90],
        ];
    }

    protected function tearDown(): void
    {
        global $_GESTOR, $_CONFIG;

        $this->definirFiltroDrivers(null);
        $_GESTOR = $this->gestorOriginal;
        $_CONFIG = $this->configOriginal;

        foreach (glob($this->logs . '*') ?: [] as $f) @unlink($f);
        @rmdir($this->logs);
    }

    // ===================================================== auxiliares

    private function payload(array $destinatarios, string $assunto = 'Pedido confirmado', string $html = '<h1>Olá</h1><p>Seu pedido <strong>#42</strong> foi pago.</p>'): array
    {
        $envio = comunicacao_email_montar_envio([
            'server' => comunicacao_email_servidor()['server'],
            'sender' => ['from' => 'noreply@exemplo.test', 'fromName' => 'Conn2Flow'],
            'recipients' => $destinatarios,
            'message' => ['subject' => $assunto, 'body' => $html],
            'altBody' => '',
            'id_hosts' => null,
            'hostPersonalizacao' => false,
            'credencial_efemera' => false,
            'fila' => false,
            'prioridade' => 'normal',
            'chave_idempotencia' => '',
        ]);

        return $envio['payload'];
    }

    private function linhas(): array
    {
        return $this->pdo->query('SELECT * FROM comunicacao_mensagens ORDER BY id_comunicacao_mensagens')->fetchAll(PDO::FETCH_ASSOC);
    }

    private function linha(int $id): array
    {
        return $this->pdo->query('SELECT * FROM comunicacao_mensagens WHERE id_comunicacao_mensagens=' . $id)->fetch(PDO::FETCH_ASSOC);
    }

    private function logEmail(): string
    {
        $conteudo = '';
        foreach (glob($this->logs . 'email-*.log') ?: [] as $f) $conteudo .= (string)file_get_contents($f);
        return $conteudo;
    }

    /**
     * Injeta um filtro em memória no HookManager real, sem banco: '' = evento carregado e sem
     * filtros; null = devolve o evento ao estado original (tearDown).
     */
    private function definirFiltroDrivers(?string $callback): void
    {
        $manager = HookManager::getInstance();
        $ref = new ReflectionClass($manager);

        $loaded = $ref->getProperty('loaded');
        $filters = $ref->getProperty('filters');

        $l = $loaded->getValue($manager);
        $f = $filters->getValue($manager);

        if ($callback === null) {
            unset($f['comunicacao']['drivers'], $l['comunicacao']['drivers'], $l['*']['drivers']);
        } else {
            $l['comunicacao']['drivers'] = true;
            $l['*']['drivers'] = true;
            $f['comunicacao']['drivers'] = $callback === '' ? [] : [['callback' => $callback, 'prioridade' => 10]];
        }

        $loaded->setValue($manager, $l);
        $filters->setValue($manager, $f);
    }

    // ===================================================== CA-1: segredo

    public function testFalhaSmtpNaoGravaSenhaEmClaroNoLog(): void
    {
        ComunicacaoReq304MailerDuble::$falhar = true;
        ComunicacaoReq304MailerDuble::$erro = 'SMTP Error: autenticacao recusada para ' . self::SENHA;

        $r = comunicacao_email_transmitir($this->payload([['email' => 'cliente@exemplo.test']]), comunicacao_email_servidor()['server']);

        self::assertFalse($r['ok']);
        self::assertStringNotContainsString(self::SENHA, $r['erro'], 'O erro devolvido (vai para erro_ultimo) nao pode trazer a senha.');

        $log = $this->logEmail();
        self::assertNotSame('', $log, 'A falha precisa continuar registrada no log de e-mail.');
        self::assertStringNotContainsString(self::SENHA, $log);
        self::assertStringContainsString('[pass] => ***', $log);
        self::assertStringContainsString('smtp.exemplo.test', $log, 'O restante do servidor continua no log para diagnostico.');
    }

    public function testPayloadPersistidoNaoTemCredencialNemComServidorEfemero(): void
    {
        $preparado = comunicacao_email_montar_envio([
            'server' => array_merge(comunicacao_email_servidor()['server'], ['pass' => 'efemera-123']),
            'sender' => ['from' => 'noreply@exemplo.test'],
            'recipients' => [['email' => 'cliente@exemplo.test']],
            'message' => ['subject' => 'Teste SMTP', 'body' => '<p>oi</p>'],
            'altBody' => '',
            'id_hosts' => null,
            'hostPersonalizacao' => false,
            'credencial_efemera' => true,
            'fila' => false,
            'prioridade' => 'normal',
            'chave_idempotencia' => '',
        ]);

        // Payload com campo de senha "vazado" por um chamador: a máscara cobre também.
        $preparado['payload']['EMAIL_PASS'] = self::SENHA;

        $r = comunicacao_enviar('email', $preparado['payload'], $preparado['opcoes']);

        self::assertTrue($r['aceito']);
        self::assertSame('efemera-123', ComunicacaoReq304MailerDuble::$enviados[0]['password'], 'A credencial efemera e usada no envio...');

        $linha = $this->linhas()[0];
        self::assertStringNotContainsString('efemera-123', $linha['payload_json'], '...mas nunca persistida.');
        self::assertStringNotContainsString(self::SENHA, $linha['payload_json']);
        self::assertSame('1', (string)$linha['max_tentativas'], 'Credencial efemera nao pode ter retry.');
        self::assertSame('enviado', $linha['status']);
    }

    public function testMascaraCobreChavesDeCredencialEmQualquerNivel(): void
    {
        $m = comunicacao_mascarar_segredos(['host' => 'h', 'pass' => 'x', 'n' => ['senha' => 'y', 'api_token' => 'z', 'client_secret' => 'w'], 'vazio' => ['password' => '']]);

        self::assertSame('h', $m['host']);
        self::assertSame('***', $m['pass']);
        self::assertSame('***', $m['n']['senha']);
        self::assertSame('***', $m['n']['api_token']);
        self::assertSame('***', $m['n']['client_secret']);
        self::assertSame('', $m['vazio']['password']);
    }

    // ===================================================== CA-2: MAIL_REDIRECT_TO

    public function testRedirecionamentoSubstituiToCcBccPrefixaAssuntoEOriginalTo(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';

        $r = comunicacao_email_transmitir($this->payload([
            ['email' => 'test_user_validacao@testuser.com', 'nome' => 'Comprador'],
            ['email' => 'financeiro@exemplo.test', 'tipo' => 'cc'],
            ['email' => 'auditoria@exemplo.test', 'tipo' => 'bcc'],
            ['email' => 'test_user_validacao@testuser.com', 'tipo' => 'bcc'],
        ]), comunicacao_email_servidor()['server']);

        self::assertTrue($r['ok']);
        $enviado = ComunicacaoReq304MailerDuble::$enviados[0];

        self::assertSame(['otavio@conn2flow.com'], $enviado['to'], 'Um unico destinatario: a caixa de teste.');
        self::assertSame([], $enviado['cc']);
        self::assertSame([], $enviado['bcc']);
        self::assertSame('[TESTE → test_user_validacao@testuser.com] Pedido confirmado', $enviado['subject']);
        self::assertContains(
            ['X-Original-To', 'test_user_validacao@testuser.com, financeiro@exemplo.test, auditoria@exemplo.test'],
            $enviado['headers'],
            'Todos os originais, sem repetir.'
        );
        self::assertSame('otavio@conn2flow.com', $r['destinatario']);
        self::assertSame('test_user_validacao@testuser.com, financeiro@exemplo.test, auditoria@exemplo.test', $r['destinatario_original']);
    }

    public function testRedirecionamentoNaoEmpilhaPrefixoEmReenvio(): void
    {
        $r = comunicacao_redirecionar([['email' => 'a@x.test']], '[TESTE → a@x.test] Pedido', 'qa@x.test');
        self::assertSame('[TESTE → a@x.test] Pedido', $r['subject']);
    }

    public function testRedirecionamentoBloqueadoEmProducaoComAviso(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';
        $_CONFIG['email']['ambiente'] = 'production';

        $status = comunicacao_redirecionamento_status();
        self::assertTrue($status['bloqueado']);
        self::assertFalse($status['ativo']);

        $r = comunicacao_email_transmitir($this->payload([['email' => 'cliente@exemplo.test']]), comunicacao_email_servidor()['server']);

        self::assertTrue($r['ok']);
        $enviado = ComunicacaoReq304MailerDuble::$enviados[0];
        self::assertSame(['cliente@exemplo.test'], $enviado['to'], 'Em producao o destinatario real e mantido.');
        self::assertSame('Pedido confirmado', $enviado['subject']);
        self::assertSame([], $enviado['headers']);
        self::assertNull($r['destinatario_original']);
        self::assertStringContainsString('MAIL_REDIRECT_TO preenchido em producao foi IGNORADO', $this->logEmail());
    }

    public function testSemAppEnvESemDevelopmentEnvContaComoProducao(): void
    {
        global $_CONFIG, $_GESTOR;
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';
        unset($_CONFIG['email']['ambiente']);
        $appEnv = $_ENV['APP_ENV'] ?? null;
        unset($_ENV['APP_ENV']);
        $_GESTOR['development-env'] = false;

        try {
            self::assertSame('production', comunicacao_config()['ambiente']);
            self::assertFalse(comunicacao_redirecionamento_status()['ativo']);

            $_GESTOR['development-env'] = true;
            self::assertTrue(comunicacao_redirecionamento_status()['ativo']);
        } finally {
            if ($appEnv !== null) $_ENV['APP_ENV'] = $appEnv;
        }
    }

    public function testFlagExplicitaLiberaRedirecionamentoEmProducao(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';
        $_CONFIG['email']['ambiente'] = 'production';
        $_CONFIG['email']['redirect_permitir_producao'] = true;

        self::assertTrue(comunicacao_redirecionamento_status()['ativo']);
    }

    public function testRedirecionamentoInvalidoFicaInativo(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['redirect_to'] = 'nao-e-email';

        self::assertFalse(comunicacao_redirecionamento_status()['ativo']);
        self::assertSame('', comunicacao_redirecionamento_destino(false));
    }

    public function testHistoricoRegistraDestinatarioOriginalQuandoRedirecionado(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';

        comunicacao_enviar('email', $this->payload([['email' => 'test_user_validacao@testuser.com']]));

        $linha = $this->linhas()[0];
        self::assertSame('enviado', $linha['status']);
        self::assertSame('otavio@conn2flow.com', $linha['destinatario']);
        self::assertSame('test_user_validacao@testuser.com', $linha['destinatario_original']);
        self::assertSame('Pedido confirmado', $linha['assunto'], 'O assunto do historico e o original; o prefixo vale so no envio.');
    }

    // ===================================================== AltBody e timeout

    public function testAltBodyDerivadoDoHtmlETimeoutAplicado(): void
    {
        $html = '<html><head><title>x</title><style>p{color:red}</style></head><body>'
            . '<h1>Olá&nbsp;Maria</h1><p>Seu pedido <strong>#42</strong> foi pago.</p>'
            . '<ul><li>Plano Pro</li><li>R$ 99,00</li></ul>'
            . '<p><a href="https://conn2flow.com/pedido/42">Ver pedido</a></p><script>alert(1)</script></body></html>';

        comunicacao_email_transmitir($this->payload([['email' => 'c@x.test']], 'Pago', $html), comunicacao_email_servidor()['server']);
        $enviado = ComunicacaoReq304MailerDuble::$enviados[0];

        self::assertSame(15, $enviado['timeout']);
        self::assertStringContainsString('Olá Maria', $enviado['altBody']);
        self::assertStringContainsString('Seu pedido #42 foi pago.', $enviado['altBody']);
        self::assertStringContainsString('- Plano Pro', $enviado['altBody']);
        self::assertStringContainsString('Ver pedido (https://conn2flow.com/pedido/42)', $enviado['altBody']);
        self::assertStringNotContainsString('<', $enviado['altBody']);
        self::assertStringNotContainsString('color:red', $enviado['altBody']);
        self::assertStringNotContainsString('alert(1)', $enviado['altBody']);
        self::assertStringNotContainsString('programa de emails compatível', $enviado['altBody'], 'A frase generica antiga nao e mais o AltBody.');
    }

    public function testAltBodyExplicitoDoChamadorPrevalece(): void
    {
        $payload = $this->payload([['email' => 'c@x.test']]);
        $payload['altBody'] = 'Texto escolhido pelo modulo';

        comunicacao_email_transmitir($payload, comunicacao_email_servidor()['server']);

        self::assertSame('Texto escolhido pelo modulo', ComunicacaoReq304MailerDuble::$enviados[0]['altBody']);
    }

    public function testTimeoutConfiguravel(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['timeout'] = 7;

        comunicacao_email_transmitir($this->payload([['email' => 'c@x.test']]), comunicacao_email_servidor()['server']);

        self::assertSame(7, ComunicacaoReq304MailerDuble::$enviados[0]['timeout']);
    }

    // ===================================================== CA-3: modo híbrido

    public function testEnvioImediatoComSucessoFicaNoHistorico(): void
    {
        $r = comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]));

        self::assertTrue($r['aceito']);
        self::assertSame('enviado', $r['status']);
        self::assertCount(1, ComunicacaoReq304MailerDuble::$enviados);

        $linha = $this->linhas()[0];
        self::assertSame('enviado', $linha['status']);
        self::assertSame('1', (string)$linha['tentativas']);
        self::assertSame('2026-11-23 10:00:10', $linha['enviado_em']);
        self::assertSame('c@x.test', $linha['destinatario']);
    }

    public function testFalhaSmtpNoEnvioImediatoCaiNaFilaComRetryDeUmMinuto(): void
    {
        ComunicacaoReq304MailerDuble::$falhar = true;

        $r = comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]));

        self::assertTrue($r['aceito'], 'A mensagem foi aceita para nova tentativa: retrocompativel com quem testa o retorno.');
        self::assertSame('pendente', $r['status']);

        $linha = $this->linhas()[0];
        self::assertSame('pendente', $linha['status']);
        self::assertSame('1', (string)$linha['tentativas']);
        self::assertSame('2026-11-23 10:01:10', $linha['proxima_tentativa_em']);
        self::assertStringContainsString('Could not authenticate', (string)$linha['erro_ultimo']);
    }

    public function testFilaExplicitaEModoSempreFilaNaoTentamAgora(): void
    {
        global $_CONFIG;

        $r = comunicacao_enviar('email', $this->payload([['email' => 'a@x.test']]), ['fila' => true]);
        self::assertSame('pendente', $r['status']);

        $_CONFIG['email']['fila']['modo'] = 'sempre_fila';
        $r = comunicacao_enviar('email', $this->payload([['email' => 'b@x.test']]));
        self::assertSame('pendente', $r['status']);

        self::assertCount(0, ComunicacaoReq304MailerDuble::$enviados, 'Nada sai na hora: a requisicao HTTP nao espera o SMTP.');
        self::assertSame(['pendente', 'pendente'], array_column($this->linhas(), 'status'));
    }

    public function testRateLimitCheioEnfileiraEOWorkerRespeitaOMinuto(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['fila']['rate_limit'] = 2;

        comunicacao_enviar('email', $this->payload([['email' => 'a@x.test']]));
        comunicacao_enviar('email', $this->payload([['email' => 'b@x.test']]));
        $r = comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]));

        self::assertSame('pendente', $r['status'], 'O terceiro envio do minuto vai para a fila.');
        self::assertCount(2, ComunicacaoReq304MailerDuble::$enviados);

        $resumo = comunicacao_fila_processar();
        self::assertSame(0, $resumo['processadas'], 'O worker conta os envios imediatos do mesmo minuto.');

        $this->agora += 60;
        $resumo = comunicacao_fila_processar();
        self::assertSame(1, $resumo['enviadas']);
        self::assertSame(['c@x.test'], ComunicacaoReq304MailerDuble::$enviados[2]['to']);
    }

    public function testReservaImediataConsomeCapacidadeAntesDeChamarDriver(): void
    {
        global $_GESTOR, $_CONFIG;
        $_CONFIG['email']['fila']['rate_limit'] = 1;
        $payload = $this->payload([['email' => 'a@x.test']]);
        $pendente = comunicacao_enviar('email', $payload, ['fila' => true]);
        $consulta = $_GESTOR['comunicacao-fila-db']['consultar'];
        $travaAtiva = false;
        $_GESTOR['comunicacao-fila-db']['consultar'] = static function (string $sql) use ($consulta, &$travaAtiva): array {
            if (str_starts_with($sql, 'SELECT GET_LOCK(')) {
                self::assertFalse($travaAtiva);
                $travaAtiva = true;
            }
            if (str_starts_with($sql, 'SELECT RELEASE_LOCK(')) {
                self::assertTrue($travaAtiva);
                $travaAtiva = false;
            }
            return $consulta($sql);
        };
        $_GESTOR['comunicacao-mailer-factory'] = static function () use ($pendente, &$travaAtiva) {
            self::assertFalse($travaAtiva, 'O SMTP roda após a liberação do lock.');
            self::assertNull(comunicacao_fila_processar_mensagem($pendente['id']));
            return new ComunicacaoReq304MailerDuble(true);
        };

        self::assertSame('enviado', comunicacao_enviar('email', $payload)['status']);
        self::assertSame('pendente', $this->linha($pendente['id'])['status']);
        self::assertCount(1, ComunicacaoReq304MailerDuble::$enviados);
    }

    public function testWorkerRevalidaVencimentoEReservaExpiradaNaoFinaliza(): void
    {
        global $_GESTOR;
        $payload = $this->payload([['email' => 'a@x.test']]);
        $id = comunicacao_enviar('email', $payload, ['fila' => true])['id'];
        $this->pdo->exec("UPDATE comunicacao_mensagens SET proxima_tentativa_em='2026-11-23 10:01:00' WHERE id_comunicacao_mensagens=" . $id);
        self::assertNull(comunicacao_fila_processar_mensagem($id), 'Uma candidata obsoleta não fura o backoff.');
        $this->agora += 60;

        $_GESTOR['comunicacao-mailer-factory'] = function () use ($id) {
            global $_GESTOR;
            $this->agora += COMUNICACAO_FILA_RESERVA_EXPIRA + 1;
            self::assertSame(1, comunicacao_fila_recuperar_travados()['recuperadas']);
            $_GESTOR['comunicacao-mailer-factory'] = static fn() => new ComunicacaoReq304MailerDuble(true);
            ComunicacaoReq304MailerDuble::$falhar = true;
            try {
                self::assertSame('pendente', comunicacao_fila_processar_mensagem($id)['status']);
            } finally {
                ComunicacaoReq304MailerDuble::$falhar = false;
            }
            return new ComunicacaoReq304MailerDuble(true);
        };

        self::assertNull(comunicacao_fila_processar_mensagem($id));
        $linha = $this->linha($id);
        self::assertSame('pendente', $linha['status']);
        self::assertSame(2, (int)$linha['tentativas']);
        self::assertNull($linha['enviado_em']);
    }

    public function testComunicacaoEmailPublicaContinuaRetrocompativel(): void
    {
        global $_GESTOR, $_CONFIG;
        $_GESTOR['bibliotecas-dados']['formulario'] = ['formulario.php'];
        $_GESTOR['bibliotecas-dados']['comunicacao'] = ['comunicacao.php'];
        $_CONFIG['email']['redirect_to'] = 'otavio@conn2flow.com';

        // Mesma forma de chamada dos módulos existentes (perfil-usuario, forms, 2fa).
        $ok = comunicacao_email([
            'destinatarios' => [['email' => 'test_user_validacao@testuser.com', 'nome' => 'Comprador']],
            'mensagem' => [
                'assunto' => 'Pagamento aprovado',
                'htmlCompleto' => '<p>Pagamento <b>aprovado</b>.</p>',
            ],
        ]);

        self::assertTrue($ok);
        $enviado = ComunicacaoReq304MailerDuble::$enviados[0];
        self::assertSame(['otavio@conn2flow.com'], $enviado['to']);
        self::assertSame('[TESTE → test_user_validacao@testuser.com] Pagamento aprovado', $enviado['subject']);
        self::assertSame('Pagamento aprovado.', $enviado['altBody']);
        self::assertSame(self::SENHA, $enviado['password'], 'A senha do .env e resolvida na hora do envio.');

        $linha = $this->linhas()[0];
        self::assertSame('enviado', $linha['status']);
        self::assertStringNotContainsString(self::SENHA, $linha['payload_json']);

        // Falha definitiva (sem tentativas restantes) volta a devolver false, como antes.
        ComunicacaoReq304MailerDuble::$falhar = true;
        $_CONFIG['email']['fila']['max_tentativas'] = 1;
        self::assertFalse(comunicacao_email([
            'destinatarios' => [['email' => 'c@x.test']],
            'mensagem' => ['assunto' => 'x', 'htmlCompleto' => '<p>x</p>'],
        ]));
    }

    public function testComunicacaoEmailDesativadoRetornaFalseSemTocarNaFila(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['ativo'] = false;

        self::assertFalse(comunicacao_email(['destinatarios' => [['email' => 'c@x.test']]]));
        self::assertSame([], $this->linhas());
    }

    public function testSemTabelaOEnvioSegueSincronoComoAntes(): void
    {
        global $_GESTOR;
        $_GESTOR['comunicacao-fila-db']['disponivel'] = static fn(): bool => false;
        unset($_GESTOR['comunicacao-fila-disponivel']);

        $r = comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]), ['fila' => true]);

        self::assertTrue($r['aceito']);
        self::assertSame('enviado', $r['status']);
        self::assertCount(1, ComunicacaoReq304MailerDuble::$enviados);
    }

    // ===================================================== idempotência

    public function testChaveDeIdempotenciaNaoDuplica(): void
    {
        $p = $this->payload([['email' => 'c@x.test']]);

        $a = comunicacao_enviar('email', $p, ['chave_idempotencia' => 'pedido-42-pago']);
        $b = comunicacao_enviar('email', $p, ['chave_idempotencia' => 'pedido-42-pago']);

        self::assertFalse($a['duplicado']);
        self::assertTrue($b['duplicado']);
        self::assertTrue($b['aceito']);
        self::assertSame($a['id'], $b['id']);
        self::assertCount(1, $this->linhas());
        self::assertCount(1, ComunicacaoReq304MailerDuble::$enviados);
    }

    // ===================================================== worker

    public function testReservaAtomicaImpedeEnvioDuplicado(): void
    {
        $r = comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]), ['fila' => true]);
        $id = (int)$r['id'];

        // Outra execução reservou a linha primeiro.
        $this->pdo->exec("UPDATE comunicacao_mensagens SET status='enviando', reservado_em='2026-11-23 10:00:10' WHERE id_comunicacao_mensagens=" . $id);

        self::assertNull(comunicacao_fila_processar_mensagem($id));
        self::assertCount(0, ComunicacaoReq304MailerDuble::$enviados);

        $this->pdo->exec("UPDATE comunicacao_mensagens SET status='pendente' WHERE id_comunicacao_mensagens=" . $id);
        self::assertSame('enviado', comunicacao_fila_processar_mensagem($id)['status']);
        self::assertNull(comunicacao_fila_processar_mensagem($id), 'Uma segunda chamada nao reenvia.');
        self::assertCount(1, ComunicacaoReq304MailerDuble::$enviados);
    }

    public function testBackoffExponencialAteFalhouEApagaTemporarios(): void
    {
        ComunicacaoReq304MailerDuble::$falhar = true;

        $tmp = $this->logs . 'anexo-tmp.txt';
        file_put_contents($tmp, 'x');
        $payload = $this->payload([['email' => 'c@x.test']]);
        $payload['tmp_files'] = [$tmp];

        $id = (int)comunicacao_enviar('email', $payload)['id'];
        $inicio = $this->agora;

        self::assertSame(date('Y-m-d H:i:s', $inicio + 60), $this->linha($id)['proxima_tentativa_em'], '1a falha: +1 min');

        $this->agora = $inicio + 30;
        self::assertSame(0, comunicacao_fila_processar()['processadas'], 'Antes da hora nao tenta.');

        $this->agora = $inicio + 60;
        comunicacao_fila_processar();
        self::assertSame(date('Y-m-d H:i:s', $this->agora + 300), $this->linha($id)['proxima_tentativa_em'], '2a falha: +5 min');

        $this->agora += 300;
        comunicacao_fila_processar();
        self::assertSame(date('Y-m-d H:i:s', $this->agora + 900), $this->linha($id)['proxima_tentativa_em'], '3a falha: +15 min');
        self::assertFileExists($tmp, 'Enquanto ha retry, o anexo temporario fica.');

        $this->agora += 900;
        $resumo = comunicacao_fila_processar();
        self::assertSame(1, $resumo['falhas']);

        $linha = $this->linha($id);
        self::assertSame('falhou', $linha['status']);
        self::assertSame('4', (string)$linha['tentativas']);
        self::assertNull($linha['proxima_tentativa_em']);
        self::assertFileDoesNotExist($tmp, 'No estado final o temporario e apagado.');
    }

    public function testBackoffChegaASessentaMinutosComMaisTentativas(): void
    {
        self::assertSame([60, 300, 900, 3600, 3600], array_map('comunicacao_fila_backoff', [1, 2, 3, 4, 5]));
    }

    public function testPrioridadeAltaSaiPrimeiro(): void
    {
        global $_CONFIG;
        $_CONFIG['email']['fila']['rate_limit'] = 1;

        comunicacao_enviar('email', $this->payload([['email' => 'baixa@x.test']]), ['fila' => true, 'prioridade' => 'baixa']);
        comunicacao_enviar('email', $this->payload([['email' => 'alta@x.test']]), ['fila' => true, 'prioridade' => 'alta']);

        comunicacao_fila_processar();

        self::assertSame(['alta@x.test'], ComunicacaoReq304MailerDuble::$enviados[0]['to']);
    }

    public function testRecuperaTravadosEMarcaFalhaQuandoEsgotado(): void
    {
        $velho = date('Y-m-d H:i:s', $this->agora - 700);
        $recente = date('Y-m-d H:i:s', $this->agora - 60);
        foreach ([[0, $velho], [3, $velho], [0, $recente]] as [$tentativas, $reserva]) {
            $this->pdo->exec(
                "INSERT INTO comunicacao_mensagens (canal,status,prioridade,destinatario,assunto,payload_json,tentativas,max_tentativas,reservado_em,data_criacao,data_modificacao)"
                . " VALUES ('email','enviando',2,'c@x.test','x','{}',$tentativas,4,'$reserva','$velho','$velho')"
            );
        }

        $r = comunicacao_fila_recuperar_travados($this->agora);

        self::assertSame(1, $r['recuperadas']);
        self::assertSame(1, $r['esgotadas']);
        self::assertSame(['pendente', 'falhou', 'enviando'], array_column($this->linhas(), 'status'), 'Reserva recente nao e tocada.');
        self::assertSame(['1', '4', '0'], array_map('strval', array_column($this->linhas(), 'tentativas')));
    }

    public function testRetencaoRemoveSoEnviadosECanceladosAntigos(): void
    {
        $antigo = date('Y-m-d H:i:s', $this->agora - 91 * 86400);
        $recente = date('Y-m-d H:i:s', $this->agora - 10 * 86400);
        foreach ([['enviado', $antigo], ['cancelado', $antigo], ['falhou', $antigo], ['enviado', $recente]] as [$status, $data]) {
            $this->pdo->exec(
                "INSERT INTO comunicacao_mensagens (canal,status,prioridade,assunto,payload_json,data_criacao,data_modificacao)"
                . " VALUES ('email','$status',2,'x','{}','$data','$data')"
            );
        }

        self::assertSame(2, comunicacao_fila_processar()['removidas']);
        self::assertSame(['falhou', 'enviado'], array_column($this->linhas(), 'status'));

        self::assertSame(0, comunicacao_fila_aplicar_retencao($this->agora + 400 * 86400, 0), 'Retencao 0 desliga a limpeza.');
    }

    public function testRotinaCronDevolveContratoDoAgendador(): void
    {
        comunicacao_enviar('email', $this->payload([['email' => 'c@x.test']]), ['fila' => true]);

        $r = comunicacao_fila_cron([]);

        self::assertSame('sucesso', $r['status']);
        self::assertStringContainsString('enviadas=1', $r['log']);
    }

    // ===================================================== painel: cancelar e reenviar

    public function testCancelarSoAfetaPendenteEReenviarCriaNovaLinha(): void
    {
        $pendente = (int)comunicacao_enviar('email', $this->payload([['email' => 'a@x.test']]), ['fila' => true])['id'];
        $enviado = (int)comunicacao_enviar('email', $this->payload([['email' => 'b@x.test']]))['id'];

        self::assertTrue(comunicacao_fila_cancelar($pendente));
        self::assertFalse(comunicacao_fila_cancelar($enviado), 'Mensagem ja enviada nao e cancelavel.');
        self::assertSame('cancelado', $this->linha($pendente)['status']);

        $r = comunicacao_fila_reenviar($enviado);
        self::assertTrue($r['aceito']);
        self::assertNotSame($enviado, $r['id']);
        self::assertSame('enviado', $this->linha($enviado)['status'], 'O historico original fica intacto.');
        self::assertSame('1', (string)$this->linha((int)$r['id'])['prioridade'], 'Reenvio manual entra como prioridade alta.');
        self::assertCount(2, ComunicacaoReq304MailerDuble::$enviados);

        self::assertFalse(comunicacao_fila_reenviar($pendente)['aceito'], 'Cancelada nao se reenvia.');
    }

    public function testListagemFiltraEPaginaEEstatisticas(): void
    {
        for ($i = 1; $i <= 3; $i++) comunicacao_enviar('email', $this->payload([['email' => "cliente$i@x.test"]], "Pedido $i"));
        comunicacao_enviar('email', $this->payload([['email' => 'fila@x.test']], 'Na fila'), ['fila' => true]);

        $l = comunicacao_fila_listar(['status' => 'enviado', 'por_pagina' => 2]);
        self::assertSame(3, $l['total']);
        self::assertSame(2, $l['paginas']);
        self::assertCount(2, $l['itens']);

        self::assertSame(1, comunicacao_fila_listar(['busca' => 'cliente2'])['total']);
        self::assertSame(0, comunicacao_fila_listar(['busca' => "O'Neil"])['total'], 'Aspas na busca nao quebram o SQL.');
        self::assertSame(4, comunicacao_fila_listar(['data_de' => '2026-11-23', 'data_ate' => '2026-11-23'])['total']);
        self::assertSame(0, comunicacao_fila_listar(['data_de' => '2026-11-24'])['total']);
        self::assertSame(4, comunicacao_fila_listar(['status' => 'invalido'])['total'], 'Filtro invalido e ignorado.');

        $e = comunicacao_fila_estatisticas();
        self::assertSame(3, $e['enviados_hoje']);
        self::assertSame(1, $e['na_fila']);
        self::assertSame(0, $e['falhas']);
        self::assertSame(30, $e['limite_minuto']);
        self::assertSame(3, $e['usados_minuto']);
    }

    // ===================================================== CA-4: drivers multi-canal

    public function testContratoDeDriversAceitaCanalNovoPorHook(): void
    {
        self::assertSame(['email'], array_keys(comunicacao_drivers()));

        $GLOBALS['comunicacao_req304_whatsapp_envios'] = [];
        $this->definirFiltroDrivers('comunicacao_req304_teste_registrar_whatsapp');

        $drivers = comunicacao_drivers();
        self::assertArrayHasKey('email', $drivers);
        self::assertArrayHasKey('whatsapp', $drivers);
        self::assertArrayNotHasKey('Canal Invalido!', $drivers, 'Nome de canal fora do padrao e descartado.');
        self::assertArrayNotHasKey('semenviar', $drivers, 'Driver sem a operacao enviar e descartado.');

        $r = comunicacao_enviar('whatsapp', ['numero' => '+5511999990000', 'texto' => 'Seu pedido saiu'], ['fila' => true]);
        self::assertSame('pendente', $r['status']);

        $linha = $this->linhas()[0];
        self::assertSame('whatsapp', $linha['canal']);
        self::assertSame('+5511999990000', $linha['destinatario']);

        comunicacao_fila_processar();
        self::assertSame('enviado', $this->linha((int)$r['id'])['status']);
        self::assertSame([['numero' => '+5511999990000', 'texto' => 'Seu pedido saiu']], $GLOBALS['comunicacao_req304_whatsapp_envios']);
        self::assertCount(0, ComunicacaoReq304MailerDuble::$enviados);
    }

    public function testCanalSemDriverNaoEAceito(): void
    {
        $r = comunicacao_enviar('telegram', ['x' => 1]);

        self::assertFalse($r['aceito']);
        self::assertSame([], $this->linhas());
    }
}

/** Filtro de teste: registra um canal `whatsapp` e duas entradas inválidas. */
function comunicacao_req304_teste_registrar_whatsapp(array $drivers): array
{
    $drivers['whatsapp'] = [
        'rotulo' => 'WhatsApp',
        'enviar' => static function (array $payload, array $contexto): array {
            $GLOBALS['comunicacao_req304_whatsapp_envios'][] = $payload;
            return ['ok' => true, 'erro' => ''];
        },
        'destinatario' => static fn(array $p): string => (string)($p['numero'] ?? ''),
        'assunto' => static fn(array $p): string => mb_substr((string)($p['texto'] ?? ''), 0, 60),
    ];
    $drivers['Canal Invalido!'] = ['enviar' => 'strlen'];
    $drivers['semenviar'] = ['rotulo' => 'x'];

    return $drivers;
}
