<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\AuthCookieCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Console\Output;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Contracts/CommandInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/InputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/OutputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Console/Input.php';
require_once CONN2FLOW_ROOT . '/cli/src/Console/Output.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/ProjectEnvironmentResolver.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/AuthCookieCommand.php';

final class CliProjectEnvironmentTest extends TestCase
{
    private string $testRoot;
    private string $mirrorPath;

    protected function setUp(): void
    {
        $this->testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_project_env_' . bin2hex(random_bytes(6));
        $this->mirrorPath = $this->testRoot . '/dev-environment/data/sites/localhost/photon';

        mkdir($this->mirrorPath . '/autenticacoes/localhost', 0755, true);
        mkdir($this->mirrorPath . '/temp', 0755, true);
        mkdir($this->testRoot . '/cli/scripts', 0755, true);
        file_put_contents($this->mirrorPath . '/config.php', "<?php\n");
        file_put_contents($this->mirrorPath . '/autenticacoes/localhost/.env', "DEVELOPMENT_ENV=false\n");
        file_put_contents($this->testRoot . '/cli/scripts/auth-cookie-generator.php', "<?php\n");

        $environment = [
            'devProjects' => [
                'snapphoton-local' => [
                    'path' => $this->testRoot . '/source/gestor',
                    'path_tests' => $this->mirrorPath,
                    'url' => 'http://localhost/photon/',
                ],
                'target-only' => [
                    'target' => $this->mirrorPath,
                    'url' => 'http://localhost/photon/',
                ],
                'source-only' => [
                    'path' => $this->mirrorPath,
                    'url' => 'http://localhost/photon/',
                ],
                'ssh-cookie' => [
                    'path' => $this->testRoot . '/source/gestor',
                    'url' => 'https://exemplo.local/',
                    'deploy_mode' => 'ssh',
                    'ssh_host' => '192.0.2.10',
                    'ssh_user' => 'deploy',
                    'ssh_port' => 2222,
                    'ssh_target_path' => '/home/tenant/web/exemplo.local/conn2flow-gestor',
                    'ssh_run_as' => 'tenant',
                ],
            ],
        ];
        file_put_contents(
            $this->testRoot . '/dev-environment/data/environment.json',
            json_encode($environment, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->testRoot);
    }

    public function testResolverPreferePathTestsEDerivaMountDocker(): void
    {
        $resolved = (new ProjectEnvironmentResolver($this->testRoot))->resolve('snapphoton-local');

        self::assertSame(realpath($this->mirrorPath), $resolved['gestorPath']);
        self::assertSame('/var/www/sites/localhost/photon/', $resolved['dockerPath']);
        self::assertSame(
            realpath($this->mirrorPath . '/autenticacoes/localhost/.env'),
            realpath($resolved['envFile'])
        );
        self::assertSame('localhost', $resolved['host']);
    }

    public function testResolverAceitaTargetEPathComoFallbacks(): void
    {
        $resolver = new ProjectEnvironmentResolver($this->testRoot);

        self::assertSame(realpath($this->mirrorPath), $resolver->resolve('target-only')['gestorPath']);
        self::assertSame(realpath($this->mirrorPath), $resolver->resolve('source-only')['gestorPath']);
    }

    public function testResolverUsaEnvNaRaizQuandoEnvDoHostNaoExiste(): void
    {
        unlink($this->mirrorPath . '/autenticacoes/localhost/.env');
        $rootEnv = $this->mirrorPath . '/.env';
        file_put_contents($rootEnv, "DEVELOPMENT_ENV=true\n");

        $resolved = (new ProjectEnvironmentResolver($this->testRoot))->resolve('snapphoton-local');

        self::assertSame(realpath($rootEnv), realpath($resolved['envFile']));
    }

    public function testAuthCookieDespachaGeradorParaDockerQuandoContainerEstaAtivo(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;

            if (($command[1] ?? '') === 'inspect') {
                return ['code' => 0, 'stdout' => "true\n", 'stderr' => ''];
            }

            if (($command[1] ?? '') === 'exec' && ($command[3] ?? '') === 'php') {
                $dockerResult = $this->optionValue($command, '--result=');
                $hostResult = $this->mirrorPath . '/temp/' . basename($dockerResult);
                file_put_contents($hostResult, json_encode($this->generatorResult(), JSON_THROW_ON_ERROR));
            }

            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };

        $outPath = $this->testRoot . '/temp/agent-cookies.txt';
        $command = new AuthCookieCommand($this->testRoot, $runner);
        $input = new Input(['c2f', 'auth:cookie', '--project=snapphoton-local', '--out=' . $outPath]);

        ob_start();
        $code = $command->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);
        self::assertFileExists($outPath);
        self::assertStringContainsString("localhost\tFALSE\t/", (string)file_get_contents($outPath));
        self::assertTrue($this->containsCommand($commands, ['docker', 'exec', 'conn2flow-app', 'php']));
        self::assertTrue($this->containsArgument($commands, '--gestor=/var/www/sites/localhost/photon'));
    }

    public function testAuthCookieUsaPhpLocalQuandoContainerNaoEstaAtivo(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;

            if (($command[0] ?? '') === 'docker') {
                return ['code' => 1, 'stdout' => '', 'stderr' => 'container unavailable'];
            }

            $resultPath = $this->optionValue($command, '--result=');
            file_put_contents($resultPath, json_encode($this->generatorResult(), JSON_THROW_ON_ERROR));

            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };

        $outPath = $this->testRoot . '/temp/local-agent-cookies.txt';
        $command = new AuthCookieCommand($this->testRoot, $runner);
        $input = new Input(['c2f', 'auth:cookie', '--project=snapphoton-local', '--out=' . $outPath]);

        ob_start();
        $code = $command->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);
        self::assertSame(PHP_BINARY, $commands[1][0]);
        self::assertFalse($this->containsCommand($commands, ['docker', 'exec', 'conn2flow-app', 'php']));
    }

    public function testAuthCookieEncapsulaCdEPhpNaShellElevadaViaSsh(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;

            if (($command[0] ?? '') === 'scp') {
                $origem = $command[count($command) - 2] ?? '';
                if (is_string($origem) && str_contains($origem, '.json')) {
                    $destino = $command[count($command) - 1];
                    file_put_contents($destino, json_encode($this->generatorResult(), JSON_THROW_ON_ERROR));
                }
            }

            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };

        $outPath = $this->testRoot . '/temp/ssh-agent-cookies.txt';
        $command = new AuthCookieCommand($this->testRoot, $runner);
        $input = new Input(['c2f', 'auth:cookie', '--project=ssh-cookie', '--out=' . $outPath]);

        ob_start();
        $code = $command->execute($input, new Output());
        ob_end_clean();

        self::assertSame(0, $code);
        self::assertFileExists($outPath);

        $execucao = null;
        foreach ($commands as $processo) {
            $remoto = $processo[count($processo) - 1] ?? '';
            if (($processo[0] ?? '') === 'ssh' && is_string($remoto) && str_contains($remoto, 'sudo chmod')) {
                $execucao = $remoto;
                break;
            }
        }

        self::assertIsString($execucao);
        self::assertStringStartsWith('sudo -u ', $execucao);
        self::assertStringContainsString(' sh -c ', $execucao);
        self::assertStringContainsString('cd ', $execucao);
        self::assertStringContainsString('php ', $execucao);
        self::assertLessThan(strpos($execucao, 'cd '), strpos($execucao, ' sh -c '));
        self::assertLessThan(strpos($execucao, 'php '), strpos($execucao, 'cd '));
        self::assertStringNotContainsString('cd "/home/tenant/web/exemplo.local/conn2flow-gestor" && sudo -u', $execucao);
    }

    /** @return array<string, mixed> */
    private function generatorResult(): array
    {
        return [
            'userId' => '1',
            'userName' => 'admin',
            'expiration' => time() + 3600,
            'domain' => 'localhost',
            'cookieAuthName' => '_C2FCID',
            'sessionAuthName' => '_C2FSID',
            'tokenJWT' => 'test-jwt',
            'sessionId' => 'test-session',
        ];
    }

    /** @param list<string> $command */
    private function optionValue(array $command, string $prefix): string
    {
        foreach ($command as $argument) {
            if (str_starts_with($argument, $prefix)) {
                return substr($argument, strlen($prefix));
            }
        }

        self::fail("Option {$prefix} not found in process command.");
    }

    /** @param list<list<string>> $commands
     *  @param list<string> $prefix
     */
    private function containsCommand(array $commands, array $prefix): bool
    {
        foreach ($commands as $command) {
            if (array_slice($command, 0, count($prefix)) === $prefix) {
                return true;
            }
        }

        return false;
    }

    /** @param list<list<string>> $commands */
    private function containsArgument(array $commands, string $argument): bool
    {
        foreach ($commands as $command) {
            if (in_array($argument, $command, true)) {
                return true;
            }
        }

        return false;
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}
