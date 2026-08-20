<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ManagerCommitCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'manager:commit';
    }

    public function getDescription(): string
    {
        return 'Execute standardized commit routine using ai-workspace commit.sh.';
    }

    public function getAliases(): array
    {
        return ['commit'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f manager:commit <commit-message>\n\nExecutes commit.sh with standard message conventions.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $msg = $input->getArgument(0);
        if (!$msg) {
            $output->error("Commit message is required. Example: c2f manager:commit \"feat: my feature\"");
            return 1;
        }

        $output->title('Conn2Flow — Standard Git Commit');
        $script = $this->rootPath . '/ai-workspace/en/scripts/commits/commit.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " " . escapeshellarg($msg), $output);
    }
}
