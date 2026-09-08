<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-040 / BATCH-167 — escape do cgroup do PHP-FPM no disparo desacoplado.
 *
 * O BATCH-166 resolveu metade do problema e a metade errada. Ele tirou o processo da SESSÃO do
 * worker (`setsid`), e a conta do Transforma MP continuou congelada em `provisioning`: sessão não
 * é cgroup. O filho seguia dentro de `php8.5-fpm.service`, e o `systemctl restart php8.5-fpm` que
 * o próprio HestiaCP dispara ao criar o domínio web mata TODO o cgroup — inclusive o instalador
 * no meio de `v-add-web-domain`.
 *
 * O que este teste protege:
 *
 * 1. A distinção entre estratégia que isola e estratégia que só desacopla. Anunciar `setsid` como
 *    proteção é pior que não ter proteção, porque esconde a exposição.
 * 2. A escolha por SONDAGEM. `systemd-run --scope` depende de autorização do systemd e o pool roda
 *    sem privilégio; supor que funciona reproduz a falha silenciosa que o lote existe para acabar.
 * 3. A separação entre montar o comando e verificar disponibilidade — sem ela, nada disso é
 *    verificável num host sem systemd.
 * 4. A configuração do operador não pode ser mascarada pelo cache da sondagem.
 */
final class AdminCronReq040Test extends TestCase
{
    private const MODULO = 'admin-cron';

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'cron.php';

        // NUNCA `admin-cron.php`: termina em `admin_cron_start()` e abriria a interface.
        require_once self::moduloPath('includes' . DIRECTORY_SEPARATOR . 'admin-cron-dispatch.php');
    }

    private static function moduloPath(string $arquivo = ''): string
    {
        $base = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . self::MODULO;
        return $arquivo === '' ? $base : $base . DIRECTORY_SEPARATOR . $arquivo;
    }

    private static function fonteDespacho(): string
    {
        return (string)file_get_contents(
            self::moduloPath('includes' . DIRECTORY_SEPARATOR . 'admin-cron-dispatch.php')
        );
    }

    /** Roda o caso com uma config isolada, restaurando o estado global no fim. */
    private function comConfig(array $config, callable $caso): void
    {
        global $_GESTOR;

        $original = $_GESTOR['config'] ?? null;
        $_GESTOR['config'] = $config;

        try {
            $caso();
        } finally {
            if ($original === null) unset($_GESTOR['config']); else $_GESTOR['config'] = $original;
        }
    }

    // ============ 1. Isolar não é a mesma coisa que desacoplar ============

    public function testSetsidNaoContaComoIsolamentoDeCgroup(): void
    {
        // A correção de rumo do BATCH-166: `setsid` cria sessão nova, e sessão não é cgroup.
        self::assertFalse(
            admin_cron_disparo_isola_cgroup('setsid'),
            'setsid nao isola cgroup: anunciar protecao aqui esconde a exposicao ao restart.'
        );

        self::assertTrue(admin_cron_disparo_isola_cgroup('systemd-run'));
        self::assertTrue(admin_cron_disparo_isola_cgroup('ssh'));
        self::assertFalse(admin_cron_disparo_isola_cgroup('inventada'));
    }

    public function testEstrategiasVaoDaMaisIsoladaParaAMenos(): void
    {
        self::assertSame(
            ['systemd-run', 'ssh', 'setsid'],
            admin_cron_disparo_estrategias(),
            'A ordem e a politica: a alternativa sem isolamento so entra por ultimo.'
        );
    }

    // ============ 2. Montagem dos comandos ============

    public function testSystemdRunCriaEscopoForaDoPoolEVoltaOWorkerDeImediato(): void
    {
        $this->comConfig([], function () {
            $linha = admin_cron_disparo_montar_linha('systemd-run', 'CMD');

            self::assertStringContainsString('systemd-run --scope --quiet', $linha);
            self::assertStringContainsString('system-cron.slice', $linha);
            // `--scope` roda em primeiro plano: sem o `&` a resposta HTTP ficaria presa até o fim
            // do provisionamento, que é justamente o que não pode acontecer.
            self::assertStringEndsWith('&', $linha);
            self::assertStringContainsString('> /dev/null 2>&1', $linha);
        });
    }

    public function testSliceInvalidoNaoEntraNaLinhaDeComando(): void
    {
        // O valor vem de configuração e é concatenado num comando de shell.
        $this->comConfig(['cron_dispatch_slice' => 'foo; rm -rf /'], function () {
            self::assertStringNotContainsString('rm -rf', admin_cron_disparo_systemd_montar());
            self::assertStringNotContainsString('--slice', admin_cron_disparo_systemd_montar());
        });

        $this->comConfig(['cron_dispatch_slice' => 'minha-fila.slice'], function () {
            self::assertStringContainsString('minha-fila.slice', admin_cron_disparo_systemd_montar());
        });
    }

    public function testSshExigeHostConfiguradoEValido(): void
    {
        $this->comConfig([], function () {
            self::assertSame('', admin_cron_disparo_ssh_montar(), 'Sem host, o ssh nao pode ser montado.');
            self::assertSame('', admin_cron_disparo_montar_linha('ssh', 'CMD'));
        });

        $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1 ; touch /tmp/x'], function () {
            self::assertSame('', admin_cron_disparo_ssh_montar(), 'Host invalido precisa ser recusado.');
        });
    }

    public function testSshNaoTravaOWorkerNumPromptDeSenha(): void
    {
        $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1', 'cron_dispatch_ssh_user' => 'otavio'], function () {
            $prefixo = admin_cron_disparo_ssh_montar();

            // Sem BatchMode, um host sem chave instalada pendura a requisição num prompt.
            self::assertStringContainsString('-o BatchMode=yes', $prefixo);
            self::assertStringContainsString('-o ConnectTimeout=3', $prefixo);
            self::assertStringContainsString('otavio@127.0.0.1', $prefixo);
        });
    }

    public function testSshSoltaOProcessoRemotoDaSessaoDoCanal(): void
    {
        $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1'], function () {
            $linha = admin_cron_disparo_montar_linha('ssh', 'CMD');

            // `-f` devolve o ssh local; o `nohup ... &` remoto impede que fechar o canal derrube
            // o instalador do outro lado.
            self::assertStringContainsString(' -f ', $linha);
            self::assertStringContainsString('nohup CMD', $linha);
        });
    }

    public function testPortaEIdentidadeEntramSomenteQuandoValidas(): void
    {
        $this->comConfig([
            'cron_dispatch_ssh_host' => '127.0.0.1',
            'cron_dispatch_ssh_port' => 2222,
            'cron_dispatch_ssh_identity' => '/home/otavio/.ssh/id_cron',
        ], function () {
            $prefixo = admin_cron_disparo_ssh_montar();
            self::assertStringContainsString('-p 2222', $prefixo);
            self::assertStringContainsString('id_cron', $prefixo);
        });

        $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1', 'cron_dispatch_ssh_port' => 99999], function () {
            self::assertStringNotContainsString('-p 99999', admin_cron_disparo_ssh_montar());
        });
    }

    public function testEstrategiaDesconhecidaNaoProduzComando(): void
    {
        self::assertSame('', admin_cron_disparo_montar_linha('inventada', 'CMD'));
    }

    // ============ 3. Sondagem e configuração ============

    public function testEscolhaForcadaNaoEMascaradaPeloCacheDaSondagem(): void
    {
        // A sondagem é memorizada por requisição; a decisão do operador não pode ficar presa a
        // um resultado anterior.
        admin_cron_disparo_estrategia();

        $this->comConfig(['cron_dispatch_strategy' => 'ssh'], function () {
            self::assertSame('ssh', admin_cron_disparo_estrategia());
        });

        $this->comConfig(['cron_dispatch_strategy' => 'inexistente'], function () {
            self::assertNotSame('inexistente', admin_cron_disparo_estrategia(), 'Valor fora da lista nao pode ser aceito.');
        });
    }

    public function testSondagemUsaOMesmoPrefixoDoDisparoReal(): void
    {
        // Sondar com flags diferentes das usadas no disparo aprovaria uma configuração que
        // falharia adiante — e em silêncio, porque o comando real vai para background.
        // Regex e não substring: o arquivo é gravado com CRLF nesta árvore, e um `\n` literal
        // no padrão faria o teste reprovar por fim de linha em vez de por conteúdo.
        self::assertMatchesRegularExpression(
            '/admin_cron_disparo_prefixo_systemd\(\);\s*if\(\$prefixo === \'\'\) return false;/',
            self::fonteDespacho()
        );
    }

    public function testConfiguracaoDoDespachoChegaPeloEnv(): void
    {
        // O núcleo NÃO popula `$_GESTOR['config']` — essa chave é uma convenção que o
        // config-loader do Host Manager cria para si. Ler só dali deixaria a estratégia por SSH
        // e o binário do PHP inertes, sem erro visível.
        $original = $_ENV['CRON_DISPATCH_SSH_HOST'] ?? null;
        $_ENV['CRON_DISPATCH_SSH_HOST'] = '10.0.0.9';

        try {
            $this->comConfig([], function () {
                self::assertStringContainsString('10.0.0.9', admin_cron_disparo_ssh_montar());
            });

            // O `.env` tem precedência sobre a config em memória.
            $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1'], function () {
                self::assertStringContainsString('10.0.0.9', admin_cron_disparo_ssh_montar());
            });
        } finally {
            if ($original === null) unset($_ENV['CRON_DISPATCH_SSH_HOST']); else $_ENV['CRON_DISPATCH_SSH_HOST'] = $original;
        }

        // Sem env, a config em memória continua valendo.
        $this->comConfig(['cron_dispatch_ssh_host' => '127.0.0.1'], function () {
            self::assertStringContainsString('127.0.0.1', admin_cron_disparo_ssh_montar());
        });
    }

    public function testExecSincronoReportaFalhaDeComandoInexistente(): void
    {
        $r = admin_cron_exec_sincrono('c2f-comando-que-nao-existe-req040');

        self::assertIsInt($r['codigo']);
        self::assertNotSame(0, $r['codigo'], 'Comando inexistente precisa reprovar a sondagem.');
    }

    // ============ 4. Contrato com a interface ============

    public function testDisparoInformaEstrategiaEIsolamento(): void
    {
        $fonte = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        self::assertStringContainsString("'estrategia' => \$disparo['estrategia']", $fonte);
        self::assertStringContainsString("'isolado' => (bool)\$disparo['isolado']", $fonte);
        // Sem isolamento, o operador precisa saber que o processo pode ser interrompido.
        self::assertStringContainsString("admin_cron_var('msg-run-detached-no-isolation')", $fonte);
    }

    public function testAvisoDeAusenciaDeIsolamentoExisteNosDoisIdiomas(): void
    {
        $manifesto = json_decode((string)file_get_contents(self::moduloPath('admin-cron.json')), true);
        self::assertIsArray($manifesto);

        foreach (['pt-br', 'en'] as $lang) {
            $ids = array_column($manifesto['resources'][$lang]['variables'] ?? [], 'id');
            self::assertContains('msg-run-detached-no-isolation', $ids, "[{$lang}] aviso nao declarado.");
        }
    }

    public function testFalhaDeDisparoDevolveEnvelopeCompleto(): void
    {
        // O chamador lê `estrategia` e `isolado` sem checar `ok` primeiro: o envelope de erro
        // precisa trazer as mesmas chaves, senão a leitura vira warning de índice indefinido.
        $fonte = self::fonteDespacho();

        // Quatro retornos de `admin_cron_disparar_em_background`, mais o de "nenhuma estrategia"
        // e a linha da assinatura do isolador.
        self::assertSame(
            6,
            preg_match_all("/'isolado' => /", $fonte),
            'Todos os retornos de admin_cron_disparar_em_background devem declarar isolado.'
        );
    }
}
