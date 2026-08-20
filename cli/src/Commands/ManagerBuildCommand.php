<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ManagerBuildCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'manager:build';
    }

    public function getDescription(): string
    {
        return 'Build local manager production assets and bundles.';
    }

    public function getAliases(): array
    {
        return ['build:manager', 'build'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f manager:build\n\nRuns ai-workspace/en/scripts/updates/build-local-manager.sh";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Build Local Manager');
        $script = $this->rootPath . '/ai-workspace/en/scripts/updates/build-local-manager.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script), $output);
    }
}
