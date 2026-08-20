<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class InstallerBuildCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'installer:build';
    }

    public function getDescription(): string
    {
        return 'Build local standalone manager installer package.';
    }

    public function getAliases(): array
    {
        return ['build:installer'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f installer:build\n\nRuns build-local-manager-installer.sh";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Build Local Manager Installer');
        $script = $this->rootPath . '/ai-workspace/en/scripts/updates/build-local-manager-installer.sh';

        if (!file_exists($script)) {
            $output->error("Installer build script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script), $output);
    }
}
