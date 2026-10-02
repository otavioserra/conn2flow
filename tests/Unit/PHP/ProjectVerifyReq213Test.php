<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\ProjectVerifyCommand;
use PHPUnit\Framework\TestCase;

foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php', 'Commands/BaseProcessCommand.php', 'Commands/ProjectVerifyCommand.php'] as $arquivo) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $arquivo;
}

/**
 * req-213: conferência por hash entre a origem (core + projeto) e o destino de um deploy.
 */
final class ProjectVerifyReq213Test extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . '/c2f-verify-' . bin2hex(random_bytes(4));
        foreach (['modulos/a', 'vendor/x', 'temp', 'db/data', 'assets/vendor/lib', 'modulos/a/node_modules/y'] as $pasta) {
            mkdir($this->raiz . '/' . $pasta, 0777, true);
        }
        file_put_contents($this->raiz . '/gestor.php', "<?php\r\necho 1;\r\n");
        file_put_contents($this->raiz . '/modulos/a/a.js', "var a = 1;\n");
        file_put_contents($this->raiz . '/modulos/a/a.html', '<p>recurso</p>');
        file_put_contents($this->raiz . '/vendor/x/x.php', '<?php');
        file_put_contents($this->raiz . '/temp/t.php', '<?php');
        file_put_contents($this->raiz . '/db/data/D.php', '<?php');
        file_put_contents($this->raiz . '/assets/vendor/lib/l.js', 'x');
        file_put_contents($this->raiz . '/modulos/a/node_modules/y/y.js', 'x');
    }

    protected function tearDown(): void
    {
        $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->raiz, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($itens as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->raiz);
    }

    public function testMapaLocalSoLevaCodigoDeAutoria(): void
    {
        $mapa = ProjectVerifyCommand::mapaLocal($this->raiz);

        $arquivos = array_keys($mapa);
        sort($arquivos);

        self::assertSame(['gestor.php', 'modulos/a/a.js'], $arquivos);
    }

    public function testFimDeLinhaDoWindowsNaoContaComoDiferenca(): void
    {
        $origem = ProjectVerifyCommand::mapaLocal($this->raiz);
        $destino = ['gestor.php' => hash('sha256', "<?php\necho 1;\n"), 'modulos/a/a.js' => hash('sha256', "var a = 1;\n")];

        $r = ProjectVerifyCommand::comparar($origem, $destino);

        self::assertSame(2, $r['comparados']);
        self::assertSame([], $r['diferentes']);
        self::assertSame([], $r['sobras']);
    }

    public function testApontaConteudoDiferenteESobraSoEmPastaGerida(): void
    {
        $origem = ProjectVerifyCommand::mapaLocal($this->raiz);
        $destino = [
            'gestor.php' => hash('sha256', 'versão de outra árvore'),
            'modulos/a/a.js' => hash('sha256', "var a = 1;\n"),
            'modulos/a/a.min.js' => hash('sha256', 'minificado antigo'),      // sobra: módulo que deixou de ter o arquivo
            'db/migrations/20260101000000_velha.php' => hash('sha256', 'x'),  // sobra
            'config-ambiente.php' => hash('sha256', 'x'),                     // fora de pasta gerida: não é sobra
            'vendor/x/x.php' => hash('sha256', 'outro'),                      // dependência: fora da comparação
            'modulos/a/a.html' => hash('sha256', 'outro'),                    // recurso: fora da comparação
        ];

        $r = ProjectVerifyCommand::comparar($origem, $destino);

        self::assertSame(['gestor.php'], $r['diferentes']);
        self::assertSame(['db/migrations/20260101000000_velha.php', 'modulos/a/a.min.js'], $r['sobras']);
        self::assertSame(2, $r['comparados']);
    }

    public function testLeASaidaDoSha256sum(): void
    {
        $h = str_repeat('a', 64);
        $mapa = ProjectVerifyCommand::lerSha256sum($h . "  ./gestor.php\r\n" . $h . " *./modulos/a b/c.js\nlinha solta\n");

        self::assertSame(['gestor.php' => $h, 'modulos/a b/c.js' => $h], $mapa);
    }

    public function testOPipelineTerminaComAConferenciaEPermitePular(): void
    {
        $fonte = (string)file_get_contents(dirname(__DIR__, 3) . '/cli/src/Commands/ProjectUpdateAllCommand.php');

        self::assertStringContainsString("new ProjectVerifyCommand(\$this->rootPath)", $fonte);
        self::assertStringContainsString("hasOption('no-verify')", $fonte);
        self::assertStringContainsString('new ProjectVerifyCommand($this->rootPath)', (string)file_get_contents(dirname(__DIR__, 3) . '/cli/src/Console/Application.php'));
    }
}
