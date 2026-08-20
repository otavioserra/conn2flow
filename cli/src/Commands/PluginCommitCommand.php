<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PluginCommitCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'plugin:commit';
    }

    public function getDescription(): string
    {
        return 'Execute standardized commit routine inside active plugin.';
    }

    public function getAliases(): array
    {
        return ['commit:plugin'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f plugin:commit <message> [private|public]\n\nDefaults to 'private'.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $msg = $input->getArgument(0);
        $type = $input->getArgument(1, 'private');

        if (!$msg) {
            $output->error("Commit message is required. Example: c2f plugin:commit \"feat: plugin feature\"");
            return 1;
        }

        $output->title("Conn2Flow — Git Commit on {$type} Plugin");
        $script = $this->rootPath . "/dev-plugins/plugins/{$type}/scripts/commits/commit.sh";

        if (!file_exists($script)) {
            $output->error("Commit script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " " . escapeshellarg($msg), $output);
    }
}
