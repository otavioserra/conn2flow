<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ResourcesSyncCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'resources:sync';
    }

    public function getDescription(): string
    {
        return 'Compile and synchronize all native Conn2Flow resources (pages, layouts, components, variables, AI modes/prompts) into Data.json.';
    }

    public function getAliases(): array
    {
        return ['resources', 'sync:resources'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f resources:sync [options]\n\n" .
               "Executes the full pipeline of resource compilation, scanning layouts, pages, components,\n" .
               "variables, AI modes, and forms, verifying checksums and updating gestor/db/data/*Data.json.\n\n" .
               "Options:\n" .
               "  --force       Force rebuild of precompiled CSS and assets cache.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Resource Compilation & Synchronization');
        $output->info('Scanning resources and compiling Data.json...');

        $scriptPath = $this->rootPath . '/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php';

        if (!file_exists($scriptPath)) {
            $output->error("Resource synchronization script not found at: {$scriptPath}");
            return 1;
        }

        $cmd = [PHP_BINARY, $scriptPath];

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Failed to spawn resource compilation subprocess.');
            return 1;
        }

        fclose($pipes[0]);

        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            $output->success('All native resources successfully synchronized into Data.json!');
            return 0;
        }

        if (!empty($stderr)) {
            $output->error("Errors encountered during resource synchronization:\n{$stderr}");
        } else {
            $output->error("Resource synchronization process exited with code {$exitCode}.");
        }

        return $exitCode;
    }
}
