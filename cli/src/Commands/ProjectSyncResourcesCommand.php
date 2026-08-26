<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectSyncResourcesCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:sync-resources';
    }

    public function getDescription(): string
    {
        return 'Compile and synchronize resource data for a specific project.';
    }

    public function getAliases(): array
    {
        return ['sync:project-resources'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:sync-resources [projectID]\n\nRuns update-resource-data.sh [--project projectID]";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        $title = $project ? "Project [{$project}]" : "Current Project";
        $output->title("Conn2Flow — Synchronize Resources for {$title}");

        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/update-resource-data.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        $arg = $project ? "--project " . escapeshellarg($project) : "";
        return $this->runShell("bash " . escapeshellarg($script) . " {$arg}", $output);
    }
}
