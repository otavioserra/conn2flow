<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\UpdateConflictsCommand;
use Conn2Flow\Cli\Commands\UpdateResolveCommand;
use Conn2Flow\Cli\Support\ProjectApiClient;
use PHPUnit\Framework\TestCase;

foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php', 'Commands/BaseProcessCommand.php',
    'Support/ProjectEnvironmentResolver.php', 'Support/ProjectApiClient.php', 'Commands/UpdateConflictsCommand.php', 'Commands/UpdateResolveCommand.php'] as $f) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $f;
}

/** req-199 / BATCH-205: `c2f update:conflicts` / `update:resolve` e o cliente da API do projeto. */
final class UpdateConflictsCommandTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-conf-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->raiz)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->raiz, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($this->raiz);
    }

    public function testResolveDoHostParaOIpForcado(): void
    {
        $this->assertSame('site.test:8443:127.0.0.1', ProjectApiClient::resolveEntry('https://site.test:8443/_api/x', '127.0.0.1'));
        $this->assertSame('site.test:443:10.0.0.1', ProjectApiClient::resolveEntry('https://site.test/', '10.0.0.1'));
        $this->assertNull(ProjectApiClient::resolveEntry('https://site.test/', null));
    }

    public function testClienteExigeToken(): void
    {
        $this->expectException(RuntimeException::class);
        new ProjectApiClient(['accessUrl' => 'https://site.test/', 'id' => 'p', 'config' => []]);
    }

    public function testGravaAsVersoesSemApagarMesclaEmAndamento(): void
    {
        $d = ['choque' => ['caminho' => 'bibliotecas/a.php'], 'no_ar' => "servidor\n", 'nova' => "nova\n", 'codificacao' => 'texto'];
        $r = UpdateConflictsCommand::gravarVersoes($this->raiz, 'meu projeto', 7, $d);
        $this->assertSame("servidor\n", file_get_contents($r['arquivos']['no-ar']));
        $this->assertSame("nova\n", file_get_contents($r['arquivos']['nova']));
        $this->assertStringEndsWith('mesclado.php', $r['arquivos']['mesclado']);
        $this->assertStringContainsString('meu_projeto', $r['pasta']);
        file_put_contents($r['arquivos']['mesclado'], "minha mescla\n");
        UpdateConflictsCommand::gravarVersoes($this->raiz, 'meu projeto', 7, $d);
        $this->assertSame("minha mescla\n", file_get_contents($r['arquivos']['mesclado']), 'Novo download não apaga a mescla.');
        $this->assertSame($r['arquivos']['mesclado'], UpdateResolveCommand::mescladoPadrao($this->raiz, 'meu projeto', 7));
    }

    public function testVersoesBinarias(): void
    {
        $bin = "\x89PNG\0\1";
        $d = ['choque' => ['caminho' => 'x.png'], 'no_ar' => base64_encode($bin), 'nova' => null, 'codificacao' => 'base64'];
        $r = UpdateConflictsCommand::gravarVersoes($this->raiz, 'p', 1, $d);
        $this->assertSame($bin, file_get_contents($r['arquivos']['no-ar']));
        $this->assertArrayNotHasKey('nova', $r['arquivos']);
        $this->assertSame(['conteudo' => base64_encode($bin), 'codificacao' => 'base64'], UpdateResolveCommand::corpoConteudo($bin));
        $this->assertSame(['conteudo' => 'txt', 'codificacao' => 'texto'], UpdateResolveCommand::corpoConteudo('txt'));
    }

    public function testCaminhoLocalNaoSaiDoProjeto(): void
    {
        $s = DIRECTORY_SEPARATOR;
        $this->assertSame('/proj/gestor' . $s . 'modulos' . $s . 'a.php', UpdateResolveCommand::caminhoLocal('/proj/gestor/', 'modulos/a.php'));
        $this->assertNull(UpdateResolveCommand::caminhoLocal('/proj/gestor', '../fora.php'));
        $this->assertNull(UpdateResolveCommand::caminhoLocal('/proj/gestor', '/etc/passwd'));
    }
}
