<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Contracts' . DIRECTORY_SEPARATOR . 'CommandInterface.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Contracts' . DIRECTORY_SEPARATOR . 'InputInterface.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Contracts' . DIRECTORY_SEPARATOR . 'OutputInterface.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Input.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Output.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'EnvStatusCommand.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'EnvSetCommand.php';

use Conn2Flow\Cli\Commands\EnvStatusCommand;
use Conn2Flow\Cli\Commands\EnvSetCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Console\Output;

/**
 * Testes unitários para os comandos env:status e env:set — REQ-133
 *
 * Valida:
 *  1. EnvStatusCommand implementa CommandInterface e retorna nome correto
 *  2. EnvSetCommand implementa CommandInterface, nome e aliases corretos
 *  3. EnvSetCommand reconhece aliases dev:on, dev:off, env:toggle
 */
final class CliEnvCommandsTest extends TestCase
{
    private string $testRoot;

    protected function setUp(): void
    {
        $this->testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_env_test_' . getmypid();
        if (!is_dir($this->testRoot)) {
            mkdir($this->testRoot, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $envFile = $this->testRoot . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envFile)) {
            unlink($envFile);
        }
        if (is_dir($this->testRoot)) {
            rmdir($this->testRoot);
        }
    }

    public function testEnvStatusCommandNomeEDescricao(): void
    {
        $cmd = new EnvStatusCommand($this->testRoot);

        self::assertSame('env:status', $cmd->getName());
        self::assertNotEmpty($cmd->getDescription());
        self::assertContains('env:get', $cmd->getAliases());
    }

    public function testEnvSetCommandNomeEAliases(): void
    {
        $cmd = new EnvSetCommand($this->testRoot);

        self::assertSame('env:set', $cmd->getName());
        self::assertContains('dev:on', $cmd->getAliases());
        self::assertContains('dev:off', $cmd->getAliases());
        self::assertContains('env:toggle', $cmd->getAliases());
    }

    public function testEnvStatusRetornaCodigo0ComEnvExistente(): void
    {
        // Criar um .env mínimo
        file_put_contents(
            $this->testRoot . DIRECTORY_SEPARATOR . '.env',
            "DEVELOPMENT_ENV=true\nHTML_SANITIZE=auto\n"
        );

        $cmd = new EnvStatusCommand($this->testRoot);
        $input = new Input(['c2f', 'env:status']);

        // Capturar output (Output escreve para stdout, mas o teste valida o código de retorno)
        ob_start();
        $code = $cmd->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);
    }

    public function testEnvSetAlteraDevelopmentEnvParaTrue(): void
    {
        $envFile = $this->testRoot . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envFile, "APP_NAME=Conn2Flow\nDEVELOPMENT_ENV=false\n");

        $cmd = new EnvSetCommand($this->testRoot);
        $input = new Input(['c2f', 'env:set', 'development']);

        ob_start();
        $code = $cmd->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);

        $content = file_get_contents($envFile);
        self::assertStringContainsString('DEVELOPMENT_ENV=true', $content);
        // APP_NAME deve permanecer intacto
        self::assertStringContainsString('APP_NAME=Conn2Flow', $content);
    }

    public function testEnvSetAlteraDevelopmentEnvParaFalse(): void
    {
        $envFile = $this->testRoot . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envFile, "DEVELOPMENT_ENV=true\n");

        $cmd = new EnvSetCommand($this->testRoot);
        $input = new Input(['c2f', 'env:set', 'production']);

        ob_start();
        $code = $cmd->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);

        $content = file_get_contents($envFile);
        self::assertStringContainsString('DEVELOPMENT_ENV=false', $content);
    }

    public function testEnvSetSemEnvRetornaErro(): void
    {
        // Sem .env criado
        $cmd = new EnvSetCommand($this->testRoot);
        $input = new Input(['c2f', 'env:set', 'development']);

        ob_start();
        $code = $cmd->execute($input, new Output());
        ob_end_clean();

        self::assertSame(1, $code);
    }

    public function testEnvSetSemArgumentoRetornaErro(): void
    {
        $envFile = $this->testRoot . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envFile, "DEVELOPMENT_ENV=false\n");

        $cmd = new EnvSetCommand($this->testRoot);
        $input = new Input(['c2f', 'env:set']);

        ob_start();
        $code = $cmd->execute($input, new Output());
        ob_end_clean();

        self::assertSame(1, $code);
    }
}
