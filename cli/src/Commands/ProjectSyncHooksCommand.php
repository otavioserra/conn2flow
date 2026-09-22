<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectSyncHooksCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:sync-hooks';
    }

    public function getDescription(): string
    {
        return 'Synchronize hook registrations for a specific project.';
    }

    public function getAliases(): array
    {
        return ['sync:project-hooks'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:sync-hooks <projectID>\n\n"
            . "Runs the project database transport in hooks-only mode.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        if (!$project) {
            $output->error('Project ID is required. Example: c2f project:sync-hooks lumix');
            return 1;
        }

        $output->title("Conn2Flow — Synchronize Hooks for Project [{$project}]");
        $script = $this->rootPath . '/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        return $this->runShell(
            'bash ' . escapeshellarg($script)
            . ' --project ' . escapeshellarg((string) $project)
            . ' --hooks-only',
            $output
        );
    }
}
