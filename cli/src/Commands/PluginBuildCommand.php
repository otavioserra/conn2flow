<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PluginBuildCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'plugin:build';
    }

    public function getDescription(): string
    {
        return 'Build local manager plugin assets (private or public).';
    }

    public function getAliases(): array
    {
        return ['build:plugin'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f plugin:build [private|public]\n\nDefaults to 'private'.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument(0, 'private');
        $output->title("Conn2Flow — Build {$type} Plugin");

        $script = $this->rootPath . '/ai-workspace/en/scripts/updates/build-local-manager-plugin.sh';

        if (!file_exists($script)) {
            $output->error("Build script not found at: {$script}");
            return 1;
        }

        $arg = ($type === 'private') ? '--type=private' : '';
        return $this->runShell("bash " . escapeshellarg($script) . " {$arg}", $output);
    }
}
