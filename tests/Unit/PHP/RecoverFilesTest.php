<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\ProjectRecoverFilesCommand;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/instalacao-manifesto.php';
foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php',
    'Commands/BaseProcessCommand.php', 'Support/ProjectEnvironmentResolver.php', 'Support/ProjectApiClient.php',
    'Commands/ProjectRecoverFilesCommand.php'] as $file) require_once CONN2FLOW_ROOT . '/cli/src/' . $file;

final class RecoverFilesTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/c2f-recover-' . bin2hex(random_bytes(5));
        mkdir($this->base, 0775, true);
    }

    protected function tearDown(): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($this->base);
    }

    private function write(string $rel, string $data): void
    {
        $path = $this->base . '/' . $rel;
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        file_put_contents($path, $data);
    }

    public function testInventarioAtribuiDonoEFiltraSemVazarPastasProtegidas(): void
    {
        $this->write('a.php', 'projeto');
        $this->write('b.php', 'core');
        instalacao_manifesto_gravar($this->base, 'core', ['a.php' => hash('sha256', 'core'), 'b.php' => hash('sha256', 'core')], '1');
        instalacao_manifesto_gravar($this->base, 'projeto', ['a.php' => hash('sha256', 'projeto')], '1');
        $this->write('a.php', 'hotfix');
        $this->write('c.php', 'novo');
        $this->write('.env', 'private');
        foreach (['autenticacoes', 'logs', 'backups', 'temp'] as $dir) $this->write($dir . '/x.php', 'private');
        $items = instalacao_recuperacao_inventario($this->base);
        $this->assertSame(['a.php', 'c.php'], array_column($items, 'caminho'));
        $this->assertSame('projeto', $items[0]['camada']);
        $this->assertSame('editado', $items[0]['estado']);
        $this->assertSame('fora-do-manifesto', $items[1]['estado']);
        $this->assertSame([$items[0]], instalacao_recuperacao_inventario($this->base, ['projeto'], ['a.php'], ['editado']));
        unlink($this->base . '/b.php');
        $this->assertSame('ausente', instalacao_recuperacao_inventario($this->base, ['core'])[0]['estado']);
    }

    public function testZipRejeitaCaminhosPrivadosTravessiaEHashDivergente(): void
    {
        foreach (['.env', 'autenticacoes/a', 'logs/a', 'backups/a', 'temp/a', '../a', 'a/../b', '/etc/passwd', 'a\\b'] as $rel) {
            $this->assertFalse(instalacao_recuperacao_caminho_valido($rel), $rel);
            $this->assertNull(instalacao_recuperacao_arquivo_seguro($this->base, $rel, hash('sha256', 'secret')), $rel);
            $zipPath = $this->base . '/bad.zip';
            $zip = new ZipArchive(); $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFromString($rel, 'secret'); $zip->close();
            try {
                ProjectRecoverFilesCommand::extrairSeguro($zipPath, $this->base . '/out', [['caminho' => $rel, 'hash_disco' => hash('sha256', 'secret')]]);
                $this->fail('ZIP aceito: ' . $rel);
            } catch (RuntimeException $expected) {
                $this->assertStringContainsString('inseguro', $expected->getMessage());
            }
        }
        $zipPath = $this->base . '/good.zip';
        $this->write('a.php', 'server');
        $this->assertSame(realpath($this->base . '/a.php'), instalacao_recuperacao_arquivo_seguro($this->base, 'a.php', hash('sha256', 'server')));
        $this->assertNull(instalacao_recuperacao_arquivo_seguro($this->base, 'a.php', hash('sha256', 'wrong')));
        $zip = new ZipArchive(); $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('a.php', 'server'); $zip->close();
        ProjectRecoverFilesCommand::extrairSeguro($zipPath, $this->base . '/out', [['caminho' => 'a.php', 'hash_disco' => hash('sha256', 'server')]]);
        $this->assertSame('server', file_get_contents($this->base . '/out/a.php'));
    }

    public function testMotorResolveAsTresDecisoesDaDescidaSemGravarRegra(): void
    {
        $this->write('server.php', 'server');
        $this->write('local/a.php', 'local');
        $copy = $this->base . '/server.php';
        $clash = ['caminho' => 'a.php', 'camada' => 'projeto', 'motivo' => 'editado', 'copia_externa' => $copy, 'hash_novo' => hash('sha256', 'server')];
        $local = $this->base . '/local';
        $this->assertSame(['sobrescrever', 'manter', 'mesclar'], instalacao_choque_acoes($clash));
        $this->assertTrue(instalacao_choque_resolver($local, $clash, 'manter', null, false)['ok']);
        $this->assertSame('local', file_get_contents($local . '/a.php'));
        $this->assertTrue(instalacao_choque_resolver($local, $clash, 'mesclar', 'merged', false)['ok']);
        $this->assertSame('merged', file_get_contents($local . '/a.php'));
        $this->assertTrue(instalacao_choque_resolver($local, $clash, 'sobrescrever', null, false)['ok']);
        $this->assertSame('server', file_get_contents($local . '/a.php'));
        $this->assertFileDoesNotExist($local . '/installation/regras.json');
    }
}
