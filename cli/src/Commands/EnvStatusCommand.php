<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class EnvStatusCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'env:status';
    }

    public function getDescription(): string
    {
        return 'Display current environment mode (development/production) and active flags.';
    }

    public function getAliases(): array
    {
        return ['env:get'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f env:status\n\nShows the current DEVELOPMENT_ENV flag, project target, and related configuration.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Environment Status');

        // Read .env file
        $envFile = $this->rootPath . DIRECTORY_SEPARATOR . '.env';
        $envVars = [];
        if (is_file($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $envVars[trim($key)] = trim($val);
                }
            }
        }

        // Read environment.json
        $envJsonPath = $this->rootPath . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'environment.json';
        $envJson = null;
        if (is_file($envJsonPath)) {
            $envJson = json_decode(file_get_contents($envJsonPath), true);
        }

        // Determine mode
        $devEnvRaw = $envVars['DEVELOPMENT_ENV'] ?? 'false';
        $isDev = filter_var($devEnvRaw, FILTER_VALIDATE_BOOLEAN);
        $mode = $isDev ? 'development' : 'production';

        $output->section('Environment Mode');
        if ($isDev) {
            $output->warning("Mode: DEVELOPMENT (DEVELOPMENT_ENV={$devEnvRaw})");
            $output->writeln('  Resources are read from disk (resources/ files).');
            $output->writeln('  Cookies may be emitted without Secure flag on HTTP.');
        } else {
            $output->success("Mode: PRODUCTION (DEVELOPMENT_ENV={$devEnvRaw})");
            $output->writeln('  Resources are read from database.');
            $output->writeln('  Cookies always emitted with Secure flag.');
        }

        // Show flags
        $output->section('Active Flags');
        $flags = [
            'HTML_SANITIZE' => $envVars['HTML_SANITIZE'] ?? 'auto',
            'HTML_SANITIZE_JS' => $envVars['HTML_SANITIZE_JS'] ?? 'auto',
            'COOKIE_SECURE' => $envVars['COOKIE_SECURE'] ?? '(not set, uses gestor_cookie_is_secure())',
        ];
        $rows = [];
        foreach ($flags as $name => $value) {
            $rows[] = [$name, $value];
        }
        $output->table(['Flag', 'Value'], $rows);

        // Show project target
        if ($envJson) {
            $output->section('Project Target');
            $projectTarget = $envJson['devEnvironment']['projectTarget'] ?? '(not set)';
            $accessUrl = $envJson['devEnvironment']['accessURL'] ?? '(not set)';
            $output->writeln("  Project: {$projectTarget}");
            $output->writeln("  Access URL: {$accessUrl}");

            if (isset($envJson['devProjects']) && is_array($envJson['devProjects'])) {
                $output->section('Registered Projects');
                $projRows = [];
                foreach ($envJson['devProjects'] as $id => $proj) {
                    $projRows[] = [$id, $proj['name'] ?? '', $proj['url'] ?? '', $proj['local'] ? 'local' : 'remote'];
                }
                if ($projRows) {
                    $output->table(['ID', 'Name', 'URL', 'Type'], $projRows);
                }
            }
        }

        $output->writeln('');
        $output->info(".env path: {$envFile}");
        if ($envJson) {
            $output->info("environment.json path: {$envJsonPath}");
        }

        return 0;
    }
}
