<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/deploy-lock.php';

/**
 * req-197 / BATCH-201: trava de deploy por ambiente.
 */
final class DeployLockTest extends TestCase
{
    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-lock-' . uniqid();
        $this->file = $this->dir . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'deploy.lock';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @rmdir(dirname($this->file));
        @rmdir($this->dir);
    }

    public function testPegaELiberaComCriacaoDaPasta(): void
    {
        $r = deploy_lock_acquire($this->file, ['owner' => 'pipeline', 'detail' => 'site']);
        $this->assertTrue($r['ok']);
        $this->assertFileExists($this->file);
        $this->assertSame('pipeline', deploy_lock_read($this->file)['owner']);
        $this->assertNull($r['stale']);
        $this->assertTrue(deploy_lock_release($this->file, $r['token']));
        $this->assertFileDoesNotExist($this->file);
    }

    public function testSegundoDonoRecusadoComIdentificacao(): void
    {
        $a = deploy_lock_acquire($this->file, ['owner' => 'update-system', 'detail' => 'CLI', 'execution' => '42']);
        $b = deploy_lock_acquire($this->file, ['owner' => 'api-project-update']);
        $this->assertTrue($a['ok']);
        $this->assertFalse($b['ok']);
        $this->assertSame('busy', $b['error']);
        $this->assertSame('update-system', $b['holder']['owner']);
        $texto = deploy_lock_describe($b['holder']);
        $this->assertStringContainsString('update-system', $texto);
        $this->assertStringContainsString('execução 42', $texto);
    }

    public function testSoQuemTemOTokenLibera(): void
    {
        $a = deploy_lock_acquire($this->file, ['owner' => 'pipeline']);
        $this->assertFalse(deploy_lock_release($this->file, 'token-errado'));
        $this->assertFileExists($this->file);
        $this->assertTrue(deploy_lock_release($this->file, $a['token']));
    }

    public function testTravaVencidaEAssumida(): void
    {
        mkdir(dirname($this->file), 0777, true);
        file_put_contents($this->file, json_encode(['token' => 'velho', 'owner' => 'pipeline', 'expires_at' => time() - 10]));
        $r = deploy_lock_acquire($this->file, ['owner' => 'update-system']);
        $this->assertTrue($r['ok']);
        $this->assertSame('pipeline', $r['stale']['owner']);
        $this->assertSame('update-system', deploy_lock_read($this->file)['owner']);
        $this->assertCount(0, glob($this->file . '.stale-*') ?: [], 'Sobra da trava vencida removida.');
    }

    public function testTravaIlegivelContaComoVencida(): void
    {
        mkdir(dirname($this->file), 0777, true);
        file_put_contents($this->file, 'lixo');
        $this->assertTrue(deploy_lock_acquire($this->file, ['owner' => 'pipeline'])['ok']);
    }

    public function testRefreshEstendeAValidade(): void
    {
        $a = deploy_lock_acquire($this->file, ['owner' => 'pipeline'], 60);
        $antes = deploy_lock_read($this->file)['expires_at'];
        $this->assertTrue(deploy_lock_refresh($this->file, $a['token'], 3600));
        $this->assertGreaterThan($antes, deploy_lock_read($this->file)['expires_at']);
        $this->assertFalse(deploy_lock_refresh($this->file, 'outro', 3600));
    }
}
