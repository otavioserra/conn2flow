<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class InstallerSyncCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'installer:sync';
    }

    public function getDescription(): string
    {
        return 'Synchronize manager installer files and checksums.';
    }

    public function getAliases(): array
    {
        return ['sync:installer'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f installer:sync\n\nRuns synchronize-manager-installer.sh checksum";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Synchronize Manager Installer');
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/synchronize-manager-installer.sh';

        if (!file_exists($script)) {
            $output->error("Installer script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " checksum", $output);
    }
}
