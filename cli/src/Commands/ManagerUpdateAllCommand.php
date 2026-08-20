<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ManagerUpdateAllCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'manager:update-all';
    }

    public function getDescription(): string
    {
        return 'Run complete manager update pipeline: Resources Sync -> Files Sync -> Database Sync.';
    }

    public function getAliases(): array
    {
        return ['update:all', 'manager:update'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f manager:update-all\n\nExecutes sequential update of resources, files, and database.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Full Manager Update Pipeline');

        // 1. Resources
        $output->section('1/3 Sincronizando Recursos (Data.json)');
        $resCmd = new ResourcesSyncCommand($this->rootPath);
        $code = $resCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Resource synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 2. Files
        $output->section('2/3 Sincronizando Arquivos com Ambiente de Testes');
        $filesCmd = new ManagerSyncFilesCommand($this->rootPath);
        $code = $filesCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Files synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 3. Database
        $output->section('3/3 Sincronizando Banco de Dados');
        $dbCmd = new DbUpdateCommand($this->rootPath);
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Database synchronization failed.');
            return $code;
        }

        $output->success('Full manager update pipeline completed successfully!');
        return 0;
    }
}
