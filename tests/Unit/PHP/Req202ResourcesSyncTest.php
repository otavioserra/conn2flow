<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\ResourcesSyncCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Contracts\OutputInterface;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Contracts/CommandInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/InputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/OutputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Console/Input.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/ResourcesSyncCommand.php';

final class Req202ResourcesSyncTest extends TestCase
{
    public function testEncaminhaOpcoesParaOCompiladorDeRecursos(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req202-' . bin2hex(random_bytes(6));
        $scriptDirectory = $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura';
        mkdir($scriptDirectory, 0777, true);
        $scriptPath = $scriptDirectory . DIRECTORY_SEPARATOR . 'atualizacao-dados-recursos.php';
        $capturePath = $scriptDirectory . DIRECTORY_SEPARATOR . 'captured.json';
        file_put_contents(
            $scriptPath,
            '<?php file_put_contents(__DIR__ . \'/captured.json\', json_encode(array_slice($argv, 1))); exit(7);'
        );

        try {
            $code = (new ResourcesSyncCommand($root))->execute(
                new Input([
                    'c2f',
                    'resources:sync',
                    '--only=modulos',
                    '--skip-css',
                    '--resource=modulos',
                    '--force',
                    '--no-origin-update',
                    '--no-assets',
                ]),
                new Req202ResourcesSyncOutput()
            );

            self::assertSame(7, $code);
            self::assertSame(
                ['--only=modulos', '--skip-css', '--resource=modulos', '--tailwind-force', '--no-origin-update', '--no-assets'],
                json_decode((string) file_get_contents($capturePath), true, 512, JSON_THROW_ON_ERROR)
            );
        } finally {
            if (is_file($capturePath)) unlink($capturePath);
            if (is_file($scriptPath)) unlink($scriptPath);
            if (is_dir($scriptDirectory)) rmdir($scriptDirectory);
            $agentsDirectory = dirname($scriptDirectory);
            if (is_dir($agentsDirectory)) rmdir($agentsDirectory);
            $controladoresDirectory = dirname($agentsDirectory);
            if (is_dir($controladoresDirectory)) rmdir($controladoresDirectory);
            $gestorDirectory = dirname($controladoresDirectory);
            if (is_dir($gestorDirectory)) rmdir($gestorDirectory);
            if (is_dir($root)) rmdir($root);
        }
    }
}

final class Req202ResourcesSyncOutput implements OutputInterface
{
    public function write(string $message): void {}
    public function writeln(string $message = ''): void {}
    public function success(string $message): void {}
    public function info(string $message): void {}
    public function warning(string $message): void {}
    public function error(string $message): void {}
    public function title(string $title): void {}
    public function section(string $section): void {}
    public function table(array $headers, array $rows): void {}
}
