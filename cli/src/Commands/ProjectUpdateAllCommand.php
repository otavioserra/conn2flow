<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

final class ProjectUpdateAllCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'project:update-all';
    }

    public function getDescription(): string
    {
        return 'Run complete sequential project synchronization: Core -> DB -> Resources -> Files -> DB -> CSS rebuild -> JS minify.';
    }

    public function getAliases(): array
    {
        return ['project:update'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto]\n\n"
            . "Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked "
            . "local=true receives remote confirmation automatically; production remains explicit.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        if (!$project) {
            $output->error("Project ID is required. Example: c2f project:update-all lumix");
            return 1;
        }

        $contents = $input->getOption('contents', 'Sim');

        // Uma VM declarada como `local: true` é ambiente de teste sob controle do operador. O
        // pipeline completo pode autorizar suas duas etapas remotas (CSS e assets) sem produzir o
        // falso warning que interrompia a etapa 6/8. Produção (`local: false`) continua dependendo
        // de `--confirmar-remoto` explícito.
        $autorizarVmLocal = false;
        try {
            $resolvido = (new ProjectEnvironmentResolver($this->rootPath))->resolve((string)$project);
            $config = is_array($resolvido['config'] ?? null) ? $resolvido['config'] : [];
            $autorizarVmLocal = ($resolvido['deployMode'] ?? '') === 'ssh'
                && filter_var($config['local'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } catch (Throwable) {
            // Os comandos de cada etapa mantêm a responsabilidade de reportar configuração inválida.
        }

        $confirmarRemoto = $input->hasOption('confirmar-remoto') || $autorizarVmLocal;
        if ($autorizarVmLocal && !$input->hasOption('confirmar-remoto')) {
            $output->info("Projeto VM local '{$project}': --confirmar-remoto autorizado pelo pipeline.");
        }
        $output->title("Conn2Flow — Full Update Pipeline for Project [{$project}]");

        // 1. Sync Core -> ID
        $output->section("1/8 Sincronizando Core -> {$project}");
        $coreCmd = new ProjectSyncCoreCommand($this->rootPath);
        $code = $coreCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 2. Sync DB
        $output->section("2/8 Atualizando Banco de Dados ({$project})");
        $dbCmd = new ProjectSyncDbCommand($this->rootPath);
        $code = $dbCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 3. Sync Resources
        $output->section("3/8 Sincronizando Recursos ({$project})");
        $resCmd = new ProjectSyncResourcesCommand($this->rootPath);
        $code = $resCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 4. Sync Files
        $output->section("4/8 Sincronizando Arquivos ({$project})");
        $filesCmd = new ProjectSyncFilesCommand($this->rootPath);
        $code = $filesCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // 5. Final DB sync
        $output->section("5/8 Validação Final do Banco ({$project})");
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
        $output->section("6/8 Regenerando CSS derivado ({$project})");

        // O projeto chega a este comando como ARGUMENTO (`c2f project:update-all transformamp-local`),
        // mas o `css:rebuild` o lê como OPÇÃO (`--project=`). Repassar o mesmo `$input` fazia a etapa
        // cair no default — o ambiente de teste do SISTEMA — e regenerar a base errada: o pipeline
        // do projeto reportava sucesso sem ter tocado no CSS do projeto. Aqui o id é declarado.
        $argv = ['css:rebuild', '--project=' . $project];
        if ($confirmarRemoto) {
            $argv[] = '--confirmar-remoto';
        }

        $cssCmd = new CssRebuildCommand($this->rootPath);
        $code = $cssCmd->execute(new Input($argv), $output);
        if ($code !== 0) {
            $output->warning(
                'A regeneração do CSS não completou. As demais etapas foram aplicadas, mas recursos '
                . 'editados online podem estar servindo CSS que não corresponde ao HTML. '
                . "Rode 'c2f css:audit --project={$project}' para ver o que ficou stale."
            );
            // Não aborta: as etapas essenciais já foram aplicadas e o aviso acima é o sinal.
        }

        // 7. Minificação do JavaScript de autoria (req-145).
        //
        // O derivado minificado é recalculável a partir do fonte, como `css_precompiled`. Fica no
        // pipeline pelo mesmo motivo da etapa anterior: fora dele viraria "alguém precisa lembrar",
        // e um derivado velho serviria código antigo com cara de novo. Sem `terser` a etapa apenas
        // avisa — o sistema volta a servir o arquivo de autoria, maior porém correto.
        $output->section('7/8 Minificando JavaScript de autoria');
        $minCmd = new AssetsMinifyCommand($this->rootPath);
        $code = $minCmd->execute(new Input([]), $output);
        if ($code !== 0) {
            $output->warning(
                'A minificação não completou. O sistema continua servindo o JavaScript de autoria.'
            );
        }

        // 8. Publicação dos assets em `public_html/dist/` (req-028).
        //
        // Última etapa por dependência real: publica o resultado da minificação e do CSS derivado,
        // não o estado anterior a eles. Sem DocumentRoot declarado a etapa apenas informa — o
        // projeto continua servindo tudo pelo controlador `arquivo-estatico`.
        //
        // req-050: o id do projeto é declarado aqui pelo mesmo motivo da etapa 6/8 — sem ele a
        // publicação lia o `PUBLIC_PATH` do CORE e mandava o `dist/` do projeto para o
        // DocumentRoot de outro site. Com `deploy_mode: "ssh"`, o destino é o `ssh_public_path`
        // da VM, e o envio continua exigindo `--confirmar-remoto`.
        $output->section('8/8 Publicando assets estáticos em dist/');
        $argvPublish = ['assets:publish', '--opcional', '--project=' . $project];
        if ($confirmarRemoto) {
            $argvPublish[] = '--confirmar-remoto';
        }

        $publishCmd = new AssetsPublishCommand($this->rootPath);
        $code = $publishCmd->execute(new Input($argvPublish), $output);
        if ($code !== 0) {
            $output->warning(
                'A publicação de assets não completou. O projeto segue funcionando: as URLs caem no '
                . 'controlador arquivo-estatico, apenas sem a entrega direta pelo servidor web.'
            );
        }

        $output->success("Full update pipeline for project '{$project}' completed successfully!");
        return 0;
    }
}
