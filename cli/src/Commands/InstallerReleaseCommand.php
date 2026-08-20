<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class InstallerReleaseCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'installer:release';
    }

    public function getDescription(): string
    {
        return 'Execute standardized release routine for standalone installer package.';
    }

    public function getAliases(): array
    {
        return ['release:installer'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f installer:release <type> <tagMsg> <commitMsg> [mode]\n\n" .
               "Runs release-installer.sh with version bumps and changelog.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument(0);
        $tagMsg = $input->getArgument(1);
        $commitMsg = $input->getArgument(2);
        $mode = $input->getArgument(3, 'automatic');

        if (!$type || !$tagMsg || !$commitMsg) {
            $output->error("Missing required arguments. Usage: c2f installer:release <type> <tagMsg> <commitMsg> [mode]");
            return 1;
        }

        $output->title('Conn2Flow — Standard GIT Release for Installer');
        $script = $this->rootPath . '/ai-workspace/en/scripts/releases/release-installer.sh';

        if (!file_exists($script)) {
            $output->error("Release script not found at: {$script}");
            return 1;
        }

        $cmd = sprintf(
            'bash %s %s %s %s %s',
            escapeshellarg($script),
            escapeshellarg($type),
            escapeshellarg($tagMsg),
            escapeshellarg($commitMsg),
            escapeshellarg($mode)
        );

        return $this->runShell($cmd, $output);
    }
}
