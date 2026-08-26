<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\MotionCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Contracts\OutputInterface;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Contracts/CommandInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/InputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/OutputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Console/Input.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/MotionCommand.php';

final class CliMotionCommandTest extends TestCase
{
    public function testNomeEAliasesCobremContratoDoCli(): void
    {
        $command = new MotionCommand('FreeBSD', $this->runner());

        self::assertSame('motion:status', $command->getName());
        self::assertSame(
            [
                'motion:get',
                'anim:status',
                'motion:on',
                'anim:on',
                'motion:off',
                'anim:off',
                'motion:toggle',
                'anim:toggle',
            ],
            $command->getAliases()
        );
        self::assertStringContainsString('motion:toggle', $command->getHelp());
        self::assertStringContainsString('F5', $command->getHelp());
    }

    public function testWindowsStatusUsaSomenteClientAreaAnimationViaEntryPointW(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;
            return ['code' => 0, 'stdout' => "true\n", 'stderr' => ''];
        };
        $output = new CliMotionTestOutput();

        $code = (new MotionCommand('Windows', $runner))->execute(
            new Input(['c2f', 'motion:status']),
            $output
        );

        self::assertSame(0, $code);
        self::assertStringContainsString('ON - prefers-reduced-motion: no-preference', $output->rendered);
        self::assertCount(1, $commands);
        self::assertSame('powershell.exe', $commands[0][0]);

        $script = $commands[0][6];
        self::assertStringContainsString('EntryPoint="SystemParametersInfoW"', $script);
        self::assertStringContainsString('GetClientAreaAnimation(0x1042, 0', $script);
        self::assertStringNotContainsString('MinAnimate', $script);
        self::assertStringNotContainsString('UserPreferencesMask', $script);
        self::assertStringNotContainsString('Registry', $script);
    }

    public function testWindowsOffUsaSpiSetComPersistenciaENotificacao(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;
            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };
        $output = new CliMotionTestOutput();

        $code = (new MotionCommand('WINNT', $runner))->execute(
            new Input(['c2f', 'motion:off']),
            $output
        );

        self::assertSame(0, $code);
        self::assertStringContainsString('OFF - prefers-reduced-motion: reduce', $output->rendered);
        self::assertStringContainsString('F5', $output->rendered);

        $script = $commands[0][6];
        self::assertStringContainsString('$value = [IntPtr]0', $script);
        self::assertStringContainsString('SetClientAreaAnimation(0x1043, 0, $value, 3)', $script);
    }

    public function testLinuxStatusEAliasAnimOnUsamGsettings(): void
    {
        $statusOutput = new CliMotionTestOutput();
        $status = new MotionCommand('Linux', static fn (array $command): array => [
            'code' => 0,
            'stdout' => "false\n",
            'stderr' => '',
        ]);

        self::assertSame(0, $status->execute(new Input(['c2f', 'anim:status']), $statusOutput));
        self::assertStringContainsString('OFF - prefers-reduced-motion: reduce', $statusOutput->rendered);

        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;
            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };

        self::assertSame(
            0,
            (new MotionCommand('Linux', $runner))->execute(
                new Input(['c2f', 'anim:on']),
                new CliMotionTestOutput()
            )
        );
        self::assertSame(
            ['gsettings', 'set', 'org.gnome.desktop.interface', 'enable-animations', 'true'],
            $commands[0]
        );
    }

    public function testLinuxToggleLeEstadoAntesDeGravarInverso(): void
    {
        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;
            if (($command[1] ?? '') === 'get') {
                return ['code' => 0, 'stdout' => "false\n", 'stderr' => ''];
            }
            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };
        $output = new CliMotionTestOutput();

        $code = (new MotionCommand('Linux', $runner))->execute(
            new Input(['c2f', 'motion:toggle']),
            $output
        );

        self::assertSame(0, $code);
        self::assertCount(2, $commands);
        self::assertSame(
            ['gsettings', 'get', 'org.gnome.desktop.interface', 'enable-animations'],
            $commands[0]
        );
        self::assertSame(
            ['gsettings', 'set', 'org.gnome.desktop.interface', 'enable-animations', 'true'],
            $commands[1]
        );
        self::assertStringContainsString('ON - prefers-reduced-motion: no-preference', $output->rendered);
    }

    public function testMacInverteReduceMotionNaLeituraENaEscrita(): void
    {
        $statusOutput = new CliMotionTestOutput();
        $status = new MotionCommand('Darwin', static fn (array $command): array => [
            'code' => 0,
            'stdout' => "1\n",
            'stderr' => '',
        ]);

        self::assertSame(0, $status->execute(new Input(['c2f', 'motion:get']), $statusOutput));
        self::assertStringContainsString('OFF - prefers-reduced-motion: reduce', $statusOutput->rendered);

        $commands = [];
        $runner = function (array $command) use (&$commands): array {
            $commands[] = $command;
            return ['code' => 0, 'stdout' => '', 'stderr' => ''];
        };
        self::assertSame(
            0,
            (new MotionCommand('macOS', $runner))->execute(
                new Input(['c2f', 'motion:on']),
                new CliMotionTestOutput()
            )
        );
        self::assertSame(
            ['defaults', 'write', 'com.apple.universalaccess', 'reduceMotion', '-bool', 'false'],
            $commands[0]
        );
    }

    public function testMacSemChaveExplicitaAssumePreferenciaPadraoComAnimacoes(): void
    {
        $output = new CliMotionTestOutput();
        $command = new MotionCommand('Darwin', static fn (array $args): array => [
            'code' => 1,
            'stdout' => '',
            'stderr' => 'The domain/default pair does not exist',
        ]);

        self::assertSame(0, $command->execute(new Input(['c2f', 'motion:status']), $output));
        self::assertStringContainsString('ON - prefers-reduced-motion: no-preference', $output->rendered);
    }

    public function testPlataformaNaoSuportadaEInformativaERetornaZero(): void
    {
        $called = false;
        $runner = function (array $command) use (&$called): array {
            $called = true;
            return ['code' => 1, 'stdout' => '', 'stderr' => 'should not run'];
        };
        $output = new CliMotionTestOutput();

        $code = (new MotionCommand('FreeBSD', $runner))->execute(
            new Input(['c2f', 'motion:status']),
            $output
        );

        self::assertSame(0, $code);
        self::assertFalse($called);
        self::assertStringContainsString('Platform not supported for motion control', $output->rendered);
    }

    public function testFalhaDoComandoDoSistemaRetornaUmComDiagnostico(): void
    {
        $output = new CliMotionTestOutput();
        $command = new MotionCommand('Linux', static fn (array $args): array => [
            'code' => 2,
            'stdout' => '',
            'stderr' => 'gsettings unavailable',
        ]);

        $code = $command->execute(new Input(['c2f', 'motion:status']), $output);

        self::assertSame(1, $code);
        self::assertStringContainsString('exit 2', $output->rendered);
        self::assertStringContainsString('gsettings unavailable', $output->rendered);
    }

    private function runner(): Closure
    {
        return static fn (array $command): array => ['code' => 0, 'stdout' => '', 'stderr' => ''];
    }
}

final class CliMotionTestOutput implements OutputInterface
{
    public string $rendered = '';

    public function write(string $message): void
    {
        $this->rendered .= $message;
    }

    public function writeln(string $message = ''): void
    {
        $this->rendered .= $message . "\n";
    }

    public function success(string $message): void
    {
        $this->writeln($message);
    }

    public function info(string $message): void
    {
        $this->writeln($message);
    }

    public function warning(string $message): void
    {
        $this->writeln($message);
    }

    public function error(string $message): void
    {
        $this->writeln($message);
    }

    public function title(string $title): void
    {
        $this->writeln($title);
    }

    public function section(string $section): void
    {
        $this->writeln($section);
    }

    public function table(array $headers, array $rows): void
    {
        foreach ($rows as $row) {
            $this->writeln(implode('=', array_map(static fn (mixed $value): string => (string)$value, $row)));
        }
    }
}
