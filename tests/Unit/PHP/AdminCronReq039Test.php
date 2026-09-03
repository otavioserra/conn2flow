<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-039 / BATCH-166 — disparo desacoplado do "Disparar agora".
 *
 * O defeito: a esteira de provisionamento do HestiaCP chama `systemctl restart php8.5-fpm`.
 * Disparada pelo painel, ela roda DENTRO do pool FPM — a reinicialização derruba o próprio worker
 * que atende a requisição, o nginx devolve 502 Bad Gateway e o provisionamento morre no meio,
 * deixando um tenant parcial no painel.
 *
 * O que este teste protege:
 *
 * 1. A decisão de desacoplar vem de DECLARAÇÃO, não de uma lista de ids fixa no núcleo: quem sabe
 *    que uma rotina reinicia serviço é o módulo dono dela.
 * 2. O manifesto do módulo vale como fonte além do banco. `parametros` só é ressincronizado
 *    quando `user_modified` está vazio, e basta o operador ter pausado a tarefa uma vez para
 *    congelar a versão antiga — reexpondo o 502 em silêncio.
 * 3. O binário do CLI nunca sai de `PHP_BINARY`, que sob FPM aponta para o binário do pool.
 * 4. A falta de CLI degrada para o caminho síncrono anterior, em vez de recusar o disparo.
 */
final class AdminCronReq039Test extends TestCase
{
    private const MODULO = 'admin-cron';

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'cron.php';

        // NUNCA `admin-cron.php`: ele termina em `admin_cron_start()` e abriria a interface.
        // O despacho vive num include sem efeito colateral exatamente por isso.
        require_once self::moduloPath('includes' . DIRECTORY_SEPARATOR . 'admin-cron-dispatch.php');
    }

    private static function moduloPath(string $arquivo = ''): string
    {
        $base = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . self::MODULO;
        return $arquivo === '' ? $base : $base . DIRECTORY_SEPARATOR . $arquivo;
    }

    /** Fonte do controlador (fluxo do disparo). */
    private static function fonte(): string
    {
        return (string)file_get_contents(self::moduloPath('admin-cron.php'));
    }

    /** Fonte do include de despacho (decisão e execução desacoplada). */
    private static function fonteDespacho(): string
    {
        return (string)file_get_contents(
            self::moduloPath('includes' . DIRECTORY_SEPARATOR . 'admin-cron-dispatch.php')
        );
    }

    // ===================== 1. Decisão de desacoplamento =====================

    public function testTarefaComumSegueNoCaminhoSincrono(): void
    {
        $tarefa = ['id' => 'expiracao-trials', 'modulo' => 'subscriptions', 'parametros' => '{"lote":5}'];

        self::assertFalse(
            admin_cron_tarefa_desacoplada($tarefa),
            'Sem declaração, a tarefa precisa continuar rodando no processo web.'
        );
    }

    public function testDeclaracaoNaTarefaAtivaODesacoplamento(): void
    {
        $comExecucao = ['id' => 'x', 'modulo' => 'y', 'parametros' => '{"execucao":"desacoplada"}'];
        $comBackground = ['id' => 'x', 'modulo' => 'y', 'parametros' => '{"background":true}'];

        self::assertTrue(admin_cron_tarefa_desacoplada($comExecucao));
        self::assertTrue(admin_cron_tarefa_desacoplada($comBackground));
    }

    public function testParametrosInvalidosNaoDerrubamADecisao(): void
    {
        // `parametros` é texto livre no banco: um JSON quebrado não pode virar exceção no disparo.
        $tarefa = ['id' => 'x', 'modulo' => 'inexistente', 'parametros' => '{isso nao e json'];

        self::assertFalse(admin_cron_tarefa_desacoplada($tarefa));
    }

    public function testNucleoNaoConheceModuloDeProjetoPeloNome(): void
    {
        // A regra é genérica de propósito: o núcleo não pode carregar uma lista com o id de uma
        // tarefa que vive no repositório do site.
        self::assertStringNotContainsString(
            'host-manager-provisionamento',
            self::fonte() . self::fonteDespacho(),
            'O núcleo não deve nomear tarefas de módulos de projeto.'
        );
    }

    // ===================== 2. Manifesto como fonte de segurança =====================

    public function testManifestoDoModuloValeQuandoOBancoEstaDesatualizado(): void
    {
        global $_GESTOR;

        $raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req039-' . uniqid('', false);
        $moduloDir = $raiz . DIRECTORY_SEPARATOR . 'fake-modulo';
        mkdir($moduloDir, 0777, true);

        file_put_contents($moduloDir . DIRECTORY_SEPARATOR . 'fake-modulo.json', json_encode([
            'cron' => [
                ['id' => 'rotina-perigosa', 'funcao' => 'x', 'parametros' => ['execucao' => 'desacoplada']],
                ['id' => 'rotina-comum', 'funcao' => 'y', 'parametros' => ['lote' => 3]],
            ],
        ]));

        $original = $_GESTOR['modulos-path'] ?? null;
        $_GESTOR['modulos-path'] = $raiz . DIRECTORY_SEPARATOR;

        try {
            // Linha do banco SEM a declaração (congelada por `user_modified`).
            $perigosa = ['id' => 'rotina-perigosa', 'modulo' => 'fake-modulo', 'parametros' => '{"lote":3}'];
            $comum = ['id' => 'rotina-comum', 'modulo' => 'fake-modulo', 'parametros' => '{"lote":3}'];

            self::assertTrue(
                admin_cron_tarefa_desacoplada($perigosa),
                'O manifesto precisa recuperar a declaração que o banco não tem.'
            );
            self::assertFalse(
                admin_cron_tarefa_desacoplada($comum),
                'O fallback do manifesto não pode desacoplar tarefa que não pediu.'
            );
        } finally {
            if ($original === null) unset($_GESTOR['modulos-path']); else $_GESTOR['modulos-path'] = $original;
            @unlink($moduloDir . DIRECTORY_SEPARATOR . 'fake-modulo.json');
            @rmdir($moduloDir);
            @rmdir($raiz);
        }
    }

    public function testModuloComNomeInvalidoNaoViraLeituraDeArquivo(): void
    {
        global $_GESTOR;

        $original = $_GESTOR['modulos-path'] ?? null;
        $_GESTOR['modulos-path'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR;

        try {
            // O nome do módulo entra na montagem de um caminho: travessia não pode passar.
            self::assertSame([], admin_cron_parametros_do_manifesto(['id' => 'x', 'modulo' => '../../etc']));
            self::assertSame([], admin_cron_parametros_do_manifesto(['id' => 'x', 'modulo' => '']));
        } finally {
            if ($original === null) unset($_GESTOR['modulos-path']); else $_GESTOR['modulos-path'] = $original;
        }
    }

    // ===================== 3. Binário do CLI =====================

    public function testBinarioDoCliNaoSaiDePhpBinarySobFpm(): void
    {
        // Sob PHP-FPM, PHP_BINARY é o binário do POOL. Usá-lo rodaria o cron no SAPI errado.
        $fonte = self::fonteDespacho();

        self::assertStringContainsString(
            "PHP_SAPI === 'cli' && defined('PHP_BINARY')",
            $fonte,
            'PHP_BINARY só pode ser usado quando o próprio processo já é CLI.'
        );
        self::assertStringContainsString("PHP_BINDIR . '/php'", $fonte);
    }

    public function testBinarioConfiguradoTemPrecedencia(): void
    {
        global $_GESTOR;

        $original = $_GESTOR['config'] ?? null;
        $_GESTOR['config'] = ['cron_php_binary' => '/opt/php/8.5/bin/php'];

        try {
            self::assertSame('/opt/php/8.5/bin/php', admin_cron_php_binario());
        } finally {
            if ($original === null) unset($_GESTOR['config']); else $_GESTOR['config'] = $original;
        }
    }

    // ===================== 4. Disparo e degradação =====================

    public function testDisparoEmBackgroundUsaSessaoPropria(): void
    {
        // `setsid` é a peça central: sem uma sessão nova o filho continua no grupo de processos do
        // pool e o `systemctl restart php8.5-fpm` o mata junto com o pai.
        $fonte = self::fonteDespacho();

        self::assertStringContainsString("'setsid ' . \$comando", $fonte);
        self::assertStringContainsString("< /dev/null > /dev/null 2>&1 &", $fonte);
        self::assertStringContainsString("array_map('escapeshellarg', \$argumentos)", $fonte);
    }

    public function testAmbienteSemCliDegradaParaOCaminhoSincrono(): void
    {
        $fonte = self::fonte();

        // A recusa nunca pode ser o resultado final: o comportamento anterior continua valendo.
        self::assertStringContainsString('cron_log_admin_fallback($tarefa[\'id\'], $disparo[\'erro\']);', $fonte);
        self::assertMatchesRegularExpression(
            '/cron_log_admin_fallback\(.*\);\s*\}\s*\$resultado = cron_tarefa_executar\(\$tarefa\);/s',
            $fonte,
            'Depois do fallback, o disparo síncrono precisa continuar.'
        );

        if (DIRECTORY_SEPARATOR !== '/') {
            $r = admin_cron_disparar_em_background(['id' => 'x', 'modulo' => 'y']);
            self::assertFalse($r['ok'], 'Fora de POSIX o disparo desacoplado precisa recusar, não tentar.');
            self::assertNotSame('', $r['erro']);
        }
    }

    public function testResultadoNaoEGravadoNoDisparoDesacoplado(): void
    {
        // Quem registra duração e status é o processo CLI ao terminar; escrever agora
        // sobrescreveria o resultado real por um placeholder.
        self::assertMatchesRegularExpression(
            '/\$disparo = admin_cron_disparar_em_background\(\$tarefa\);(?:(?!cron_tarefa_registrar).)*?return;/s',
            self::fonte(),
            'O caminho desacoplado não pode chamar cron_tarefa_registrar().'
        );
    }

    // ===================== 5. i18n =====================

    public function testMensagemDeDisparoDesacopladoExisteNosDoisIdiomas(): void
    {
        $manifesto = json_decode((string)file_get_contents(self::moduloPath('admin-cron.json')), true);
        self::assertIsArray($manifesto);

        foreach (['pt-br', 'en'] as $lang) {
            $ids = array_column($manifesto['resources'][$lang]['variables'] ?? [], 'id');
            self::assertContains('msg-run-detached', $ids, "[{$lang}] mensagem de disparo desacoplado não declarada.");
        }
    }

    public function testDisparoNaoTemTextoLiteralNaResposta(): void
    {
        self::assertStringContainsString(
            "admin_cron_var('msg-run-detached')",
            self::fonte(),
            'A mensagem precisa vir do sistema de variáveis, não de literal.'
        );
    }
}
