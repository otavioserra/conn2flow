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
        return 'Run complete sequential project synchronization: Core -> DB -> Resources -> Files -> DB -> CSS rebuild.';
    }

    public function getAliases(): array
    {
        return ['project:update'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:update-all <projectID> [--contents=Sim|Não]\n\nExecutes full 6-stage synchronization pipeline (the last stage rebuilds derived CSS).";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
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
        $output->section("5/6 Validação Final do Banco ({$project})");
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 6. Regeneração do CSS derivado (req-141 / CR-002).
        //
        // As etapas anteriores PRESERVAM a autoria de quem editou online (`user_modified`) e
        // SOBRESCREVEM o CSS derivado com o que veio do disco. O resultado é um registro com HTML de
        // uma origem e CSS de outra — medido no `template-artigo` logo após este pipeline: o HTML
        // gravado usava `border-r-2` e o `css_precompiled` que entrou não continha a regra.
        //
        // Deixar esta etapa fora do pipeline seria transformá-la em "alguém precisa lembrar de
        // rodar", que é exatamente a classe de falha que o req-141 existe para eliminar. Ela é
        // condicionada: sem Tailwind CLI ou sem a coluna de procedência, apenas avisa e segue.
        $output->section("6/6 Regenerando CSS derivado ({$project})");
        $cssCmd = new CssRebuildCommand($this->rootPath);
        $code = $cssCmd->execute($input, $output);
        if ($code !== 0) {
            $output->warning(
                'A regeneração do CSS não completou. As demais etapas foram aplicadas, mas recursos '
                . 'editados online podem estar servindo CSS que não corresponde ao HTML. '
                . "Rode 'c2f css:audit --project={$project}' para ver o que ficou stale."
            );
            // Não aborta: as etapas essenciais já foram aplicadas e o aviso acima é o sinal.
        }

        $output->success("Full update pipeline for project '{$project}' completed successfully!");
        return 0;
    }
}
