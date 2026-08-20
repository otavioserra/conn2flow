<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class PluginResourcesCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'plugin:resources';
    }

    public function getDescription(): string
    {
        return 'Update and compile resource data for active plugin (private or public).';
    }

    public function getAliases(): array
    {
        return ['resources:plugin'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f plugin:resources [private|public]\n\nDefaults to 'private'.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument(0, 'private');
        $output->title("Conn2Flow — Compile Resources for {$type} Plugin");

        $script = $this->rootPath . "/dev-plugins/plugins/{$type}/scripts/resources/update-data-resources-plugin.php";

        if (!file_exists($script)) {
            $output->error("Plugin resource script not found at: {$script}");
            return 1;
        }

        return $this->runShell(PHP_BINARY . " " . escapeshellarg($script), $output);
    }
}
