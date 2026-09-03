<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectUpdateSystemCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:update-system';
    }

    public function getDescription(): string
    {
        return 'Update system dependencies, composer packages and migrations in project.';
    }

    public function getAliases(): array
    {
        return ['update:system'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:update-system [projectID] [--insecure]\n\n" .
            "Runs update-system.sh [--project projectID]. The --insecure flag is restricted to " .
            "self-signed TLS endpoints in local development.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        $title = $project ? "Project [{$project}]" : "Current Project";
        $output->title("Conn2Flow — Update System for {$title}");

        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/update-system.sh';

        if (!file_exists($script)) {
            $output->error("Update script not found at: {$script}");
            return 1;
        }

        $projectArg = $project ? "--project " . escapeshellarg($project) : "";
        $insecureArg = $input->hasOption('insecure') ? ' --insecure' : '';
        return $this->runShell("bash " . escapeshellarg($script) . " {$projectArg}{$insecureArg}", $output);
    }
}
