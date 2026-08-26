<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Closure;
use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use RuntimeException;

final class MotionCommand implements CommandInterface
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'motion:status' => 'status',
        'motion:get' => 'status',
        'anim:status' => 'status',
        'motion:on' => 'on',
        'anim:on' => 'on',
        'motion:off' => 'off',
        'anim:off' => 'off',
        'motion:toggle' => 'toggle',
        'anim:toggle' => 'toggle',
    ];

    private string $platform;
    private Closure $processRunner;

    public function __construct(?string $platform = null, ?callable $processRunner = null)
    {
        $this->platform = $this->normalizePlatform($platform ?? PHP_OS_FAMILY);
        $this->processRunner = $processRunner !== null
            ? Closure::fromCallable($processRunner)
            : Closure::fromCallable([$this, 'runProcess']);
    }

    public function getName(): string
    {
        return 'motion:status';
    }

    public function getDescription(): string
    {
        return 'Inspect or change the operating-system animation preference used by prefers-reduced-motion.';
    }

    public function getAliases(): array
    {
        return [
            'motion:get',
            'anim:status',
            'motion:on',
            'anim:on',
            'motion:off',
            'anim:off',
            'motion:toggle',
            'anim:toggle',
        ];
    }

    public function getHelp(): string
    {
        return <<<'HELP'
Usage:
  c2f motion:status
  c2f motion:on
  c2f motion:off
  c2f motion:toggle

Controls the operating-system animation preference read by browsers as
prefers-reduced-motion. Reload the browser tab (F5) after changing the state.

Aliases:
  motion:get, anim:status, anim:on, anim:off, anim:toggle
HELP;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandName = $input->getCommandName() ?? $this->getName();
        $action = self::ACTIONS[$commandName] ?? null;

        if ($action === null) {
            $output->error("Unsupported motion action: {$commandName}");
            return 1;
        }

        $output->title('Conn2Flow — Operating System Motion');

        if (!in_array($this->platform, ['windows', 'linux', 'macos'], true)) {
            $output->warning('Platform not supported for motion control.');
            return 0;
        }

        try {
            if ($action === 'status') {
                $output->info($this->formatStatus($this->readState()));
                return 0;
            }

            $enabled = match ($action) {
                'on' => true,
                'off' => false,
                'toggle' => !$this->readState(),
            };

            $this->writeState($enabled);
            $output->success($this->formatStatus($enabled));
            $output->info('Reload the browser tab (F5) so it can read the updated media query.');

            return 0;
        } catch (RuntimeException $exception) {
            $output->error($exception->getMessage());
            return 1;
        }
    }

    private function readState(): bool
    {
        $command = match ($this->platform) {
            'windows' => $this->windowsCommand(false),
            'linux' => ['gsettings', 'get', 'org.gnome.desktop.interface', 'enable-animations'],
            'macos' => ['defaults', 'read', 'com.apple.universalaccess', 'reduceMotion'],
        };

        $result = $this->callProcess($command);
        if ($result['code'] !== 0) {
            if ($this->platform === 'macos' && $this->isMissingMacPreference($result['stderr'])) {
                return true;
            }

            throw new RuntimeException($this->processFailureMessage('read', $result));
        }

        $value = $this->parseBoolean($result['stdout']);

        // macOS stores the inverse preference: reduceMotion=true means animations are off.
        return $this->platform === 'macos' ? !$value : $value;
    }

    private function writeState(bool $enabled): void
    {
        $command = match ($this->platform) {
            'windows' => $this->windowsCommand(true, $enabled),
            'linux' => [
                'gsettings',
                'set',
                'org.gnome.desktop.interface',
                'enable-animations',
                $enabled ? 'true' : 'false',
            ],
            'macos' => [
                'defaults',
                'write',
                'com.apple.universalaccess',
                'reduceMotion',
                '-bool',
                $enabled ? 'false' : 'true',
            ],
        };

        $result = $this->callProcess($command);
        if ($result['code'] !== 0) {
            throw new RuntimeException($this->processFailureMessage('update', $result));
        }
    }

    /** @return list<string> */
    private function windowsCommand(bool $write, bool $enabled = false): array
    {
        $script = <<<'POWERSHELL'
$signature = @'
using System;
using System.Runtime.InteropServices;

public static class C2FMotionNative
{
    [DllImport("user32.dll", EntryPoint="SystemParametersInfoW", ExactSpelling=true, SetLastError=true)]
    [return: MarshalAs(UnmanagedType.Bool)]
    public static extern bool GetClientAreaAnimation(
        uint uiAction,
        uint uiParam,
        [MarshalAs(UnmanagedType.Bool)] out bool pvParam,
        uint fWinIni
    );

    [DllImport("user32.dll", EntryPoint="SystemParametersInfoW", ExactSpelling=true, SetLastError=true)]
    [return: MarshalAs(UnmanagedType.Bool)]
    public static extern bool SetClientAreaAnimation(
        uint uiAction,
        uint uiParam,
        IntPtr pvParam,
        uint fWinIni
    );
}
'@

Add-Type -TypeDefinition $signature
POWERSHELL;

        if ($write) {
            $nativeValue = $enabled ? '1' : '0';
            $script .= <<<POWERSHELL

\$value = [IntPtr]{$nativeValue}
if (-not [C2FMotionNative]::SetClientAreaAnimation(0x1043, 0, \$value, 3)) {
    \$errorCode = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    throw "SystemParametersInfoW SPI_SETCLIENTAREAANIMATION failed with Win32 error \$errorCode."
}
POWERSHELL;
        } else {
            $script .= <<<'POWERSHELL'

$enabled = $false
if (-not [C2FMotionNative]::GetClientAreaAnimation(0x1042, 0, [ref]$enabled, 0)) {
    $errorCode = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    throw "SystemParametersInfoW SPI_GETCLIENTAREAANIMATION failed with Win32 error $errorCode."
}
[Console]::Out.WriteLine($enabled.ToString().ToLowerInvariant())
POWERSHELL;
        }

        return [
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-Command',
            $script,
        ];
    }

    private function parseBoolean(string $value): bool
    {
        $normalized = strtolower(trim($value));

        if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['false', '0', 'no', 'off'], true)) {
            return false;
        }

        throw new RuntimeException("Motion status returned an unexpected value: {$value}");
    }

    private function isMissingMacPreference(string $stderr): bool
    {
        $normalized = strtolower($stderr);

        return str_contains($normalized, 'does not exist')
            || str_contains($normalized, 'not exist');
    }

    /** @param array{code: int, stdout: string, stderr: string} $result */
    private function processFailureMessage(string $operation, array $result): string
    {
        $detail = trim($result['stderr']) !== '' ? trim($result['stderr']) : trim($result['stdout']);
        $suffix = $detail !== '' ? ": {$detail}" : '';

        return "Unable to {$operation} the operating-system motion preference (exit {$result['code']}){$suffix}";
    }

    private function formatStatus(bool $enabled): string
    {
        return $enabled
            ? 'ON - prefers-reduced-motion: no-preference'
            : 'OFF - prefers-reduced-motion: reduce';
    }

    private function normalizePlatform(string $platform): string
    {
        $normalized = strtolower(trim($platform));

        return match (true) {
            str_starts_with($normalized, 'win') => 'windows',
            in_array($normalized, ['darwin', 'mac', 'macos', 'osx'], true) => 'macos',
            str_contains($normalized, 'linux') => 'linux',
            default => $normalized,
        };
    }

    /**
     * @param list<string> $command
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function callProcess(array $command): array
    {
        $runner = $this->processRunner;
        $result = $runner($command);

        if (!is_array($result) || !isset($result['code'], $result['stdout'], $result['stderr'])) {
            throw new RuntimeException('Process runner returned an invalid result.');
        }

        return [
            'code' => (int)$result['code'],
            'stdout' => (string)$result['stdout'],
            'stderr' => (string)$result['stderr'],
        ];
    }

    /**
     * @param list<string> $command
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runProcess(array $command): array
    {
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            return ['code' => 1, 'stdout' => '', 'stderr' => 'Failed to start process.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return [
            'code' => proc_close($process),
            'stdout' => $stdout !== false ? $stdout : '',
            'stderr' => $stderr !== false ? $stderr : '',
        ];
    }
}
