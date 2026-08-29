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
        return 'Run complete manager update pipeline: Resources Sync -> Files Sync -> Database Sync -> CSS rebuild.';
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
        $output->section('1/4 Sincronizando Recursos (Data.json)');
        $resCmd = new ResourcesSyncCommand($this->rootPath);
        $code = $resCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Resource synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 2. Files
        $output->section('2/4 Sincronizando Arquivos com Ambiente de Testes');
        $filesCmd = new ManagerSyncFilesCommand($this->rootPath);
        $code = $filesCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Files synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 3. Database
        $output->section('3/4 Sincronizando Banco de Dados');
        $dbCmd = new DbUpdateCommand($this->rootPath);
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Database synchronization failed.');
            return $code;
        }

        // 4. Regeneração do CSS derivado (req-141 / CR-002).
        //
        // A etapa 3 preserva a autoria de quem editou pelo gestor (`user_modified`) e sobrescreve o
        // CSS derivado com o que veio do disco: o registro fica com HTML de uma origem e CSS de
        // outra. Regenerar aqui fecha o ciclo no mesmo comando, em vez de depender de alguém
        // lembrar de rodar depois.
        $output->section('4/4 Regenerando CSS derivado');
        $cssCmd = new CssRebuildCommand($this->rootPath);
        $code = $cssCmd->execute($input, $output);
        if ($code !== 0) {
            $output->warning(
                'A regeneração do CSS não completou. As demais etapas foram aplicadas, mas recursos '
                . "editados pelo gestor podem estar servindo CSS que não corresponde ao HTML. "
                . "Rode 'c2f css:audit' para ver o que ficou stale."
            );
            // Não aborta: as etapas essenciais já foram aplicadas e o aviso acima é o sinal.
        }

        $output->success('Full manager update pipeline completed successfully!');
        return 0;
    }
}
