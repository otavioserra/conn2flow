<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DbUpdateCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'db:update';
    }

    public function getDescription(): string
    {
        return 'Synchronize and update database schema and seeds for development/test environment.';
    }

    public function getAliases(): array
    {
        return ['db:sync', 'db:migrate'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f db:update\n\nSynchronizes data schemas and seeds into the local database.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Database Update & Synchronization');
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh';

        if (file_exists($script)) {
            $cmd = "bash " . escapeshellarg($script);
            $process = proc_open($cmd, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ], $pipes, $this->rootPath);

            if (!is_resource($process)) {
                $output->error("Failed to run command: {$cmd}");
                return 1;
            }

            fclose($pipes[0]);
            while ($line = fgets($pipes[1])) {
                $output->write($line);
            }
            fclose($pipes[1]);

            $err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            return proc_close($process);
        }

        $output->info('Updating Data.json caches...');
        $syncCmd = new ResourcesSyncCommand($this->rootPath);
        return $syncCmd->execute($input, $output);
    }
}
