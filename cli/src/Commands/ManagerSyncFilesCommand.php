<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ManagerSyncFilesCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'manager:sync-files';
    }

    public function getDescription(): string
    {
        return 'Synchronize manager source files to the local test environment via checksum.';
    }

    public function getAliases(): array
    {
        return ['sync:files'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f manager:sync-files\n\nRuns ai-workspace/en/scripts/dev-environment/synchronize-manager.sh checksum";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Synchronize Files to Test Environment');
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/synchronize-manager.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " checksum", $output);
    }
}
