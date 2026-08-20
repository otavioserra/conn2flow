<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PluginReleaseCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'plugin:release';
    }

    public function getDescription(): string
    {
        return 'Execute standardized release routine on active plugin.';
    }

    public function getAliases(): array
    {
        return ['release:plugin'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f plugin:release <type> <tagMsg> <commitMsg> [private|public]\n\n" .
               "Arguments:\n" .
               "  type       Release type (patch, minor, major)\n" .
               "  tagMsg     Tag annotation message\n" .
               "  commitMsg  Release commit message\n" .
               "  pluginType private (default) or public";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument(0);
        $tagMsg = $input->getArgument(1);
        $commitMsg = $input->getArgument(2);
        $pluginType = $input->getArgument(3, 'private');

        if (!$type || !$tagMsg || !$commitMsg) {
            $output->error("Missing required arguments. Usage: c2f plugin:release <type> <tagMsg> <commitMsg> [private|public]");
            return 1;
        }

        $output->title("Conn2Flow — Standard GIT Release on {$pluginType} Plugin");
        $script = $this->rootPath . "/dev-plugins/plugins/{$pluginType}/scripts/releases/release.sh";

        if (!file_exists($script)) {
            $output->error("Release script not found at: {$script}");
            return 1;
        }

        $cmd = sprintf(
            'bash %s %s %s %s',
            escapeshellarg($script),
            escapeshellarg($type),
            escapeshellarg($tagMsg),
            escapeshellarg($commitMsg)
        );

        return $this->runShell($cmd, $output);
    }
}
