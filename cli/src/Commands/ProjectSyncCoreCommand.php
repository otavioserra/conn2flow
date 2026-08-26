<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectSyncCoreCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:sync-core';
    }

    public function getDescription(): string
    {
        return 'Synchronize updated core system files into a project directory.';
    }

    public function getAliases(): array
    {
        return ['sync:project-core'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:sync-core <projectID>\n\nRuns sync-core-to-project.sh --project <projectID>";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        if (!$project) {
            $output->error("Project ID is required. Example: c2f project:sync-core lumix");
            return 1;
        }

        $output->title("Conn2Flow — Sync Core -> Project [{$project}]");
        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/sync-core-to-project.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " --project " . escapeshellarg($project), $output);
    }
}
