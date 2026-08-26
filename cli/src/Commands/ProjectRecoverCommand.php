<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectRecoverCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:recover';
    }

    public function getDescription(): string
    {
        return 'Recover remote project database, configurations and assets locally.';
    }

    public function getAliases(): array
    {
        return ['recover:project', 'recover'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:recover [projectID] [--contents]\n\n" .
               "Downloads and recovers remote project data into the local environment.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        $withContents = $input->hasOption('contents');
        $title = $project ? "Project [{$project}]" : "Current Project";
        $output->title("Conn2Flow — Recover {$title}");

        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/recover-project.sh';

        if (!file_exists($script)) {
            $output->error("Recover script not found at: {$script}");
            return 1;
        }

        $projectArg = $project ? "--project " . escapeshellarg($project) : "";
        $contentsArg = $withContents ? "--contents" : "";

        return $this->runShell("bash " . escapeshellarg($script) . " {$projectArg} {$contentsArg}", $output);
    }
}
