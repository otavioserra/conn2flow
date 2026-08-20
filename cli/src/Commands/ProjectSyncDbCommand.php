<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectSyncDbCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:sync-db';
    }

    public function getDescription(): string
    {
        return 'Synchronize and update database for a specific project.';
    }

    public function getAliases(): array
    {
        return ['sync:project-db'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:sync-db <projectID>\n\nRuns updates-manager-database.sh --project <projectID>";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument(0);
        if (!$project) {
            $output->error("Project ID is required. Example: c2f project:sync-db lumix");
            return 1;
        }

        $output->title("Conn2Flow — Update Database for Project [{$project}]");
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell("bash " . escapeshellarg($script) . " --project " . escapeshellarg($project), $output);
    }
}
