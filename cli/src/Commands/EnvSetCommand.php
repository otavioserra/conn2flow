<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class EnvSetCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'env:set';
    }

    public function getDescription(): string
    {
        return 'Set environment mode (development/production) in .env file.';
    }

    public function getAliases(): array
    {
        return ['dev:on', 'dev:off', 'env:toggle'];
    }

    public function getHelp(): string
    {
        return <<<HELP
Usage:
  c2f env:set development    Set DEVELOPMENT_ENV=true
  c2f env:set production     Set DEVELOPMENT_ENV=false
  c2f dev:on                 Shortcut for env:set development
  c2f dev:off                Shortcut for env:set production
  c2f env:toggle             Toggle current value
HELP;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Set Environment Mode');

        $envFile = $this->rootPath . DIRECTORY_SEPARATOR . '.env';

        if (!is_file($envFile)) {
            $output->error(".env file not found at: {$envFile}");
            $output->writeln('Create it from .env.example or run the installer first.');
            return 1;
        }

        $content = file_get_contents($envFile);

        // Read current value
        $currentValue = 'false';
        if (preg_match('/^DEVELOPMENT_ENV\s*=\s*(.+)$/m', $content, $m)) {
            $currentValue = trim($m[1]);
        }
        $currentIsDev = filter_var($currentValue, FILTER_VALIDATE_BOOLEAN);

        // Determine command name used (to handle aliases)
        $commandName = $input->getCommandName();
        $argument = $input->getArgument(0);

        // Resolve target mode
        $targetIsDev = null;
        if ($commandName === 'dev:on') {
            $targetIsDev = true;
        } elseif ($commandName === 'dev:off') {
            $targetIsDev = false;
        } elseif ($commandName === 'env:toggle') {
            $targetIsDev = !$currentIsDev;
        } elseif ($argument !== null) {
            $arg = strtolower($argument);
            if (in_array($arg, ['development', 'dev', 'true', '1', 'on'], true)) {
                $targetIsDev = true;
            } elseif (in_array($arg, ['production', 'prod', 'false', '0', 'off'], true)) {
                $targetIsDev = false;
            } else {
                $output->error("Invalid argument: '{$argument}'. Use 'development' or 'production'.");
                return 1;
            }
        } else {
            $output->error("Missing argument. Usage: c2f env:set [development|production]");
            return 1;
        }

        $newValue = $targetIsDev ? 'true' : 'false';

        // Update .env
        if (preg_match('/^DEVELOPMENT_ENV\s*=.*$/m', $content)) {
            $content = preg_replace('/^DEVELOPMENT_ENV\s*=.*$/m', "DEVELOPMENT_ENV={$newValue}", $content);
        } else {
            // Append if not present
            $content = rtrim($content) . "\nDEVELOPMENT_ENV={$newValue}\n";
        }

        file_put_contents($envFile, $content);

        $modeLabel = $targetIsDev ? 'DEVELOPMENT' : 'PRODUCTION';
        $output->success("Environment mode set to: {$modeLabel} (DEVELOPMENT_ENV={$newValue})");

        if ($targetIsDev) {
            $output->warning('Development mode active: resources read from disk, cookies may lack Secure flag on HTTP.');
        } else {
            $output->info('Production mode active: resources read from database, cookies always Secure.');
        }

        return 0;
    }
}
