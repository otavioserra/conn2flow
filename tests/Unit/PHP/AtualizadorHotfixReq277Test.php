<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/deploy-lock.php';

/**
 * req-277 (BATCH-285) — hotfix do atualizador pelo painel: trava órfã depois de erro fatal (temp.conn2flow.com,
 * 15/10: falta de memória em db-data.php) e memória das etapas pesadas.
 */
final class AtualizadorHotfixReq277Test extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-277-' . uniqid();
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
            if (is_file($f)) @unlink($f);
        }
        foreach (['/proc/self', '/proc/111', '/proc'] as $d) @rmdir($this->dir . $d);
        @rmdir($this->dir);
    }

    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(CONN2FLOW_ROOT . '/' . $relativo);
    }

    // ===== Processo dono morto

    public function testDonoMortoNoMesmoServidorEhOrfao(): void
    {
        $proc = $this->dir . '/proc';
        mkdir($proc . '/self', 0777, true);
        mkdir($proc . '/111', 0777, true);

        self::assertTrue(deploy_lock_orphan(['pid' => 222, 'host' => 'srv'], 'srv', $proc), 'PID 222 não existe');
        self::assertFalse(deploy_lock_orphan(['pid' => 111, 'host' => 'srv'], 'srv', $proc), 'PID 111 vivo');
        self::assertFalse(deploy_lock_orphan(['pid' => 222, 'host' => 'outro'], 'srv', $proc), 'outro servidor: não dá para saber');
        self::assertFalse(deploy_lock_orphan(['pid' => 0, 'host' => 'srv'], 'srv', $proc));
        self::assertFalse(deploy_lock_orphan(['pid' => 222, 'host' => 'srv'], 'srv', $this->dir . '/sem-proc'), 'sem /proc (Windows): não decide');
    }

    public function testTravaDeDonoMortoEhAssumida(): void
    {
        $arquivo = $this->dir . '/deploy.lock';
        $morta = ['token' => 'x', 'owner' => 'update-system', 'detail' => 'web', 'host' => (string)gethostname(),
            'pid' => 999999999, 'started_at' => time(), 'expires_at' => time() + 7200];
        file_put_contents($arquivo, json_encode($morta));

        $r = deploy_lock_acquire($arquivo, ['owner' => 'pipeline']);
        if (is_dir('/proc/self')) {
            self::assertTrue($r['ok'], 'Linux: dono morto não segura a trava por 2 h');
            self::assertSame('update-system', $r['stale']['owner']);
        } else {
            self::assertFalse($r['ok'], 'sem /proc segue esperando vencer');
        }
    }

    // ===== Liberação pelo painel

    public function testLiberarAForcaSoATravaDoPainel(): void
    {
        $arquivo = $this->dir . '/deploy.lock';

        self::assertSame(['ok' => true, 'released' => null, 'error' => 'none'], deploy_lock_force_release($arquivo, 'update-system', 'web'));

        $p = deploy_lock_acquire($arquivo, ['owner' => 'pipeline', 'detail' => 'conn2flow-site-temp']);
        $r = deploy_lock_force_release($arquivo, 'update-system', 'web');
        self::assertFalse($r['ok']);
        self::assertSame('owner', $r['error']);
        self::assertFileExists($arquivo, 'trava do pipeline continua');
        deploy_lock_release($arquivo, $p['token']);

        deploy_lock_acquire($arquivo, ['owner' => 'update-system', 'detail' => 'web']);
        $r = deploy_lock_force_release($arquivo, 'update-system', 'web');
        self::assertTrue($r['ok']);
        self::assertSame('update-system', $r['released']['owner']);
        self::assertFileDoesNotExist($arquivo);
    }

    // ===== Erro fatal de verdade, em processo separado

    public function testErroFatalNaEtapaLiberaATrava(): void
    {
        $arquivo = $this->dir . '/deploy.lock';
        $script = $this->dir . '/fatal.php';
        file_put_contents($script, '<?php
            $GLOBALS["_GESTOR"] = ["ROOT_PATH" => ' . var_export($this->dir . '/', true) . '];
            define("ATUALIZACOES_SISTEMA_SEM_EXECUCAO", true);
            require ' . var_export(CONN2FLOW_ROOT . '/gestor/bibliotecas/deploy-lock.php', true) . ';
            require ' . var_export(CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-sistema.php', true) . ';
            $r = deploy_lock_acquire(' . var_export($arquivo, true) . ', ["owner" => "update-system", "detail" => "web"]);
            atualizacoes_web_trava_no_fatal("", ' . var_export($arquivo, true) . ', $r["token"]);
            ini_set("memory_limit", "32M");
            $x = []; while (true) { $x[] = str_repeat("a", 1048576); }
        ');
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1', $saida, $codigo);

        self::assertNotSame(0, $codigo, 'o processo morreu de verdade');
        self::assertStringContainsString('Allowed memory size', implode("\n", $saida));
        self::assertFileDoesNotExist($arquivo, 'a trava não ficou para trás');
    }

    // ===== Funções puras do atualizador e contrato

    public function testBytesDoIniEErroFatal(): void
    {
        if (!defined('ATUALIZACOES_SISTEMA_SEM_EXECUCAO')) define('ATUALIZACOES_SISTEMA_SEM_EXECUCAO', true);
        global $_GESTOR;
        $_GESTOR = is_array($_GESTOR ?? null) ? $_GESTOR : [];
        $_GESTOR['ROOT_PATH'] = $_GESTOR['ROOT_PATH'] ?? $this->dir . '/';
        require_once CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-sistema.php';

        self::assertSame(128 * 1024 * 1024, atualizacoes_bytes_ini('128M'));
        self::assertSame(1024 * 1024 * 1024, atualizacoes_bytes_ini('1G'));
        self::assertSame(512 * 1024, atualizacoes_bytes_ini('512k'));
        self::assertSame(-1, atualizacoes_bytes_ini('-1'));
        self::assertTrue(atualizacoes_erro_fatal(['type' => E_ERROR, 'message' => 'Allowed memory size']));
        self::assertFalse(atualizacoes_erro_fatal(['type' => E_WARNING]));
        self::assertFalse(atualizacoes_erro_fatal(null));
    }

    public function testEtapasPesadasUsamRecursosELiberacaoNoFatal(): void
    {
        $f = self::fonte('gestor/controladores/atualizacoes/atualizacoes-sistema.php');

        self::assertStringContainsString("case 'deploy': atualizacoes_web_recursos(); atualizacoes_web_trava_no_fatal(", $f);
        self::assertStringContainsString("case 'db': atualizacoes_web_recursos(); atualizacoes_web_trava_no_fatal(", $f);
        self::assertStringContainsString("case 'unlock': jsonResponse(webLiberarTravaOrfa());", $f);
        self::assertStringContainsString("'unlockable' => \$liberavel", $f);
        self::assertStringContainsString("atualizacoes_web_trava_no_fatal('', \$arquivo, \$r['token']);", $f);
        self::assertStringContainsString("deploy_lock_force_release(atualizacoes_trava_arquivo(), 'update-system', 'web')", $f);
    }

    public function testPainelOfereceLiberarETentarDeNovo(): void
    {
        self::assertStringContainsString("case 'unlock':", self::fonte('gestor/modulos/admin-atualizacoes/admin-atualizacoes.php'));
        $js = self::fonte('gestor/modulos/admin-atualizacoes/admin-atualizacoes.js');
        self::assertStringContainsString('resp.data && resp.data.unlockable', $js);
        self::assertStringContainsString("ajax({ acao: 'unlock' })", $js);

        $json = json_decode(self::fonte('gestor/modulos/admin-atualizacoes/admin-atualizacoes.json'), true);
        foreach (['pt-br', 'en'] as $idioma) {
            $html = self::fonte("gestor/modulos/admin-atualizacoes/resources/$idioma/components/atualizacoes-lista-tailwind/atualizacoes-lista-tailwind.html");
            self::assertStringContainsString('data-msg-unlock-confirm="@[[updates-unlock-confirm]]@"', $html);
            $vars = array_column($json['resources'][$idioma]['variables'], 'value', 'id');
            self::assertNotSame('', trim((string)($vars['updates-unlock-confirm'] ?? '')), $idioma);
        }
    }
}
