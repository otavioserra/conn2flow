<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectDeployCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:deploy';
    }

    public function getDescription(): string
    {
        return 'Deploy project to production or staging server via deploy-project-v2.sh.';
    }

    public function getAliases(): array
    {
        return ['deploy:project', 'deploy'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:deploy [projectID] [--contents=Sim|Não]\n\n" .
               "Deploys current project or specified project ID to the remote server.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument(0);
        $contents = $input->getOption('contents', 'Sim');
        $title = $project ? "Project [{$project}]" : "Current Project";
        $output->title("Conn2Flow — Deploy {$title}");

        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/deploy-project-v2.sh';

        if (!file_exists($script)) {
            $output->error("Deploy script not found at: {$script}");
            return 1;
        }

        $projectArg = $project ? "--project " . escapeshellarg($project) : "";
        $contentsArg = "--contents " . escapeshellarg((string)$contents);

        return $this->runShell("bash " . escapeshellarg($script) . " {$projectArg} {$contentsArg}", $output);
    }
}
