<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ProjectUpdateAllCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:update-all';
    }

    public function getDescription(): string
    {
        return 'Run complete sequential project synchronization: Core -> DB -> Resources -> Files -> DB.';
    }

    public function getAliases(): array
    {
        return ['project:update'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:update-all <projectID> [--contents=Sim|Não]\n\nExecutes full 5-stage synchronization pipeline.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument(0);
        if (!$project) {
            $output->error("Project ID is required. Example: c2f project:update-all lumix");
            return 1;
        }

        $contents = $input->getOption('contents', 'Sim');
        $output->title("Conn2Flow — Full Update Pipeline for Project [{$project}]");

        // 1. Sync Core -> ID
        $output->section("1/5 Sincronizando Core -> {$project}");
        $coreCmd = new ProjectSyncCoreCommand($this->rootPath);
        $code = $coreCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 2. Sync DB
        $output->section("2/5 Atualizando Banco de Dados ({$project})");
        $dbCmd = new ProjectSyncDbCommand($this->rootPath);
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 3. Sync Resources
        $output->section("3/5 Sincronizando Recursos ({$project})");
        $resCmd = new ProjectSyncResourcesCommand($this->rootPath);
        $code = $resCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 4. Sync Files
        $output->section("4/5 Sincronizando Arquivos ({$project})");
        $filesCmd = new ProjectSyncFilesCommand($this->rootPath);
        $code = $filesCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 5. Final DB sync
        $output->section("5/5 Validação Final do Banco ({$project})");
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) return $code;

        $output->success("Full update pipeline for project '{$project}' completed successfully!");
        return 0;
    }
}
