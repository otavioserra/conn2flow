<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-197 / BATCH-201: `backupTotal()` e a recusa da atualização pelo CLI com a trava viva.
 *
 * O atualizador é carregado sem executar (`ATUALIZACOES_SISTEMA_SEM_EXECUCAO`) sobre uma instalação
 * falsa num diretório temporário (`$_GESTOR['ROOT_PATH']`), para logs e temp não caírem no repositório.
 */
final class AtualizacoesSistemaTravaBackupTest extends TestCase
{
    private static string $base;

    public static function setUpBeforeClass(): void
    {
        self::$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-upd-' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir(self::$base . 'bibliotecas', 0777, true);
        copy(CONN2FLOW_ROOT . '/gestor/bibliotecas/deploy-lock.php', self::$base . 'bibliotecas/deploy-lock.php');
        global $_GESTOR;
        $_GESTOR = is_array($_GESTOR ?? null) ? $_GESTOR : [];
        $_GESTOR['ROOT_PATH'] = self::$base;
        if (!defined('ATUALIZACOES_SISTEMA_SEM_EXECUCAO')) define('ATUALIZACOES_SISTEMA_SEM_EXECUCAO', true);
        require_once CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-sistema.php';
    }

    public static function tearDownAfterClass(): void
    {
        self::remover(self::$base);
    }

    private static function remover(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($dir);
    }

    public function testBackupTotalRespeitaPastasProtegidas(): void
    {
        $inst = self::$base . 'inst' . DIRECTORY_SEPARATOR;
        foreach (['index.php' => 'a', 'modulos/x/x.php' => 'bb', 'contents/foto.jpg' => 'c', 'logs/l.log' => 'd',
                  'autenticacoes/site/.env' => 'e', 'backups/antigo.zip' => 'f', 'temp/t.tmp' => 'g', 'db/migrations/m.php' => 'h'] as $rel => $conteudo) {
            @mkdir(dirname($inst . $rel), 0777, true);
            file_put_contents($inst . $rel, $conteudo);
        }
        $destino = $inst . 'backups/atualizacoes/full/teste/';
        $excludes = ['#^contents/#', '#^logs/#', '#^backups/#', '#^temp/#', '#^autenticacoes/#'];
        [$arquivos, $bytes] = backupTotal($inst, $destino, $excludes);
        $this->assertSame(3, $arquivos, 'index.php, modulos/x/x.php e db/migrations/m.php');
        $this->assertSame(4, $bytes);
        $this->assertFileExists($destino . 'modulos/x/x.php');
        $this->assertFileExists($destino . 'db/migrations/m.php');
        foreach (['contents/foto.jpg', 'logs/l.log', 'autenticacoes/site/.env', 'backups/antigo.zip', 'temp/t.tmp'] as $fora) {
            $this->assertFileDoesNotExist($destino . $fora);
        }
    }

    public function testCliRecusaComTravaViva(): void
    {
        $this->assertTrue(atualizacoes_trava_carregar());
        $arquivo = atualizacoes_trava_arquivo();
        $this->assertStringEndsWith('temp' . DIRECTORY_SEPARATOR . 'deploy.lock', $arquivo);
        $outro = deploy_lock_acquire($arquivo, ['owner' => 'api-project-update', 'detail' => 'site']);
        $this->assertTrue($outro['ok']);
        ob_start();
        $codigo = main_update(['atualizacoes-sistema.php', '--domain=localhost']);
        $saida = ob_get_clean();
        $this->assertSame(EXIT_LOCKED, $codigo);
        $this->assertStringContainsString('ERRO TRAVA', $saida);
        $this->assertStringContainsString('api-project-update', $saida);
        $this->assertSame('api-project-update', deploy_lock_read($arquivo)['owner'], 'A trava do outro dono continua lá.');
        deploy_lock_release($arquivo, $outro['token']);
    }

    public function testWebStartRecusaComTravaVivaESimulacaoNaoTrava(): void
    {
        $arquivo = atualizacoes_trava_arquivo();
        $outro = deploy_lock_acquire($arquivo, ['owner' => 'pipeline', 'detail' => 'site']);
        $r = webStart(['domain' => 'localhost']);
        $this->assertTrue($r['locked'] ?? false);
        $this->assertStringContainsString('pipeline', $r['error']);
        $this->assertSame($outro['token'], deploy_lock_read($arquivo)['token']);
        deploy_lock_release($arquivo, $outro['token']);
        // Liberar a trava de uma sessão sem trava não faz nada nem dá erro.
        webLiberarTrava('sessao-inexistente');
        $this->assertFileDoesNotExist($arquivo);
    }

    public function testFilhoDoBootstrapAdotaATravaDoPai(): void
    {
        $arquivo = atualizacoes_trava_arquivo();
        $pai = deploy_lock_acquire($arquivo, ['owner' => 'update-system', 'detail' => 'CLI']);
        // Com o token do pai, o filho não tenta pegar a trava (não recusa) e segue para a execução, que
        // aqui para na ajuda: nada é baixado nem aplicado.
        ob_start();
        $codigo = main_update(['atualizacoes-sistema.php', '--lock-token=' . $pai['token'], '--help']);
        ob_get_clean();
        $this->assertSame(EXIT_OK, $codigo);
        $this->assertSame($pai['token'], deploy_lock_read($arquivo)['token'], 'O filho não libera a trava do pai.');
        deploy_lock_release($arquivo, $pai['token']);
    }
}
