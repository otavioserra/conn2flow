<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PluginSyncCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'plugin:sync';
    }

    public function getDescription(): string
    {
        return 'Synchronize active private or public plugin files via checksum.';
    }

    public function getAliases(): array
    {
        return ['sync:plugin'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f plugin:sync [private|public]\n\nDefaults to 'private'.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument(0, 'private');
        $output->title("Conn2Flow — Synchronize {$type} Plugin");

        $script = $this->rootPath . "/dev-plugins/plugins/{$type}/scripts/dev/synchronizes.sh";

        if (!file_exists($script)) {
            $output->error("Plugin script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " checksum", $output);
    }
}
