<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class InstallerNewCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'installer:new';
    }

    public function getDescription(): string
    {
        return 'Create a new local installation instance via create-new-installation.sh.';
    }

    public function getAliases(): array
    {
        return ['new:installation'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f installer:new\n\nRuns create-new-installation.sh";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Create New Local Installation');
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/create-new-installation.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script), $output);
    }
}
