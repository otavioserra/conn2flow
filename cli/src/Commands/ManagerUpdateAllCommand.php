<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Console\Input;
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
        $output->section('1/6 Sincronizando Recursos (Data.json)');
        $resCmd = new ResourcesSyncCommand($this->rootPath);
        $code = $resCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Resource synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 2. Files
        $output->section('2/6 Sincronizando Arquivos com Ambiente de Testes');
        $filesCmd = new ManagerSyncFilesCommand($this->rootPath);
        $code = $filesCmd->execute($input, $output);
        if ($code !== 0) {
            $output->error('Files synchronization failed. Aborting pipeline.');
            return $code;
        }

        // 3. Database
        $output->section('3/6 Sincronizando Banco de Dados');
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
        $output->section('4/6 Regenerando CSS derivado');
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

        // 5. Minificação do JavaScript de autoria (req-145).
        //
        // O derivado minificado é recalculável a partir do fonte, como `css_precompiled`. Fica no
        // pipeline pelo mesmo motivo da etapa anterior: fora dele viraria "alguém precisa lembrar",
        // e um derivado velho serviria código antigo com cara de novo. Sem `terser` a etapa apenas
        // avisa — o sistema volta a servir o arquivo de autoria, maior porém correto.
        $output->section('5/6 Minificando JavaScript de autoria');
        $minCmd = new AssetsMinifyCommand($this->rootPath);
        $code = $minCmd->execute($input, $output);
        if ($code !== 0) {
            $output->warning(
                'A minificação não completou. O sistema continua servindo o JavaScript de autoria.'
            );
        }

        // 6. Publicação dos assets em `public_html/dist/` (req-028).
        //
        // Última etapa por dependência real: publica o resultado da minificação e do CSS derivado,
        // não o estado anterior a eles. Sem DocumentRoot declarado a etapa apenas informa — a
        // instalação continua servindo tudo pelo controlador `arquivo-estatico`.
        $output->section('6/6 Publicando assets estáticos em dist/');
        $publishCmd = new AssetsPublishCommand($this->rootPath);
        $code = $publishCmd->execute(new Input(['--opcional']), $output);
        if ($code !== 0) {
            $output->warning(
                'A publicação de assets não completou. O site segue funcionando: as URLs caem no '
                . 'controlador arquivo-estatico, apenas sem a entrega direta pelo servidor web.'
            );
        }

        $output->success('Full manager update pipeline completed successfully!');
        return 0;
    }
}
