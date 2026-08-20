<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectSyncFilesCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:sync-files';
    }

    public function getDescription(): string
    {
        return 'Synchronize project files via checksum with optional contents/ folder.';
    }

    public function getAliases(): array
    {
        return ['sync:project-files'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:sync-files <projectID> [--contents=Sim|Não]\n\nRuns synchronize-project.sh";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument(0);
        if (!$project) {
            $output->error("Project ID is required. Example: c2f project:sync-files lumix");
            return 1;
        }

        $contents = $input->getOption('contents', 'Sim');
        $output->title("Conn2Flow — Synchronize Files for Project [{$project}]");

        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/synchronize-project.sh';

        if (!file_exists($script)) {
            $output->error("Script not found at: {$script}");
            return 1;
        }

        $cmd = sprintf(
            'bash %s --project %s checksum --contents %s',
            escapeshellarg($script),
            escapeshellarg($project),
            escapeshellarg((string)$contents)
        );

        return $this->runShell($cmd, $output);
    }
}
