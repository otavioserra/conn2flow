<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
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
        return "Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto] [--no-wait] [--lock-wait=<minutes>] [--no-maintenance]\n\n"
            . "Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked "
            . "local=true receives remote confirmation automatically; production remains explicit.\n"
            . "req-197: checks migrations first (db:check-migrations) and runs under a deploy lock per target "
            . "(SSH host+path or local folder, in GIT/.c2f-deploy-locks/). A second pipeline waits for the lock (default 30 min); --no-wait fails at once.";
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

        // req-197: migração com versão ou classe duplicada travaria o Phinx no servidor (e todos os
        // deploys seguintes). A checagem roda antes de qualquer envio.
        $check = new DbCheckMigrationsCommand($this->rootPath);
        if ($check->execute(new Input(['db:check-migrations', '--project=' . $project]), $output) !== 0) {
            $output->error('Pipeline interrompido antes de enviar qualquer coisa: corrija as migrações acima.');
            return 1;
        }

        // req-197: trava de deploy por projeto. Dois pipelines do mesmo projeto ao mesmo tempo (agentes
        // em paralelo) cruzavam etapas no mesmo ambiente — o MariaDB chegou a devolver erro 1020. O
        // segundo espera a trava liberar (com aviso periódico e limite), ou falha na hora com --no-wait.
        $lock = $this->acquireLock((string)$project, $input, $output);
        if ($lock === null) {
            return 1;
        }
        // req-210: enquanto as etapas trocam arquivos e sincronizam o banco, o destino responde com a
        // tela de atualização em vez de erro 500. `--no-maintenance` mantém o comportamento antigo.
        $manutencao = !$input->hasOption('no-maintenance') && $this->maintenance((string)$project, true, $output);
        try {
            return $this->runStages((string)$project, $contents, $confirmarRemoto, $input, $output);
        } finally {
            if ($manutencao) {
                $this->maintenance((string)$project, false, $output);
            }
            deploy_lock_release($lock['file'], $lock['token']);
        }
    }

    /**
     * Liga ou desliga a manutenção no Gestor de destino (`temp/maintenance.json`).
     *
     * Não é fatal: sem conseguir ligar, o pipeline segue como antes, só que sem a tela. A validade do
     * arquivo (30 min) cobre o pipeline que morre antes de desligar.
     */
    private function maintenance(string $project, bool $on, OutputInterface $output): bool
    {
        try {
            $resolved = (new ProjectEnvironmentResolver($this->rootPath))->resolve($project);
        } catch (Throwable) {
            return false;
        }

        $agora = time();
        $json = (string)json_encode(['owner' => 'pipeline', 'detail' => 'project:update-all ' . $project, 'started_at' => $agora, 'expires_at' => $agora + 1800]);

        if (is_array($resolved['ssh'] ?? null)) {
            try {
                $transport = new SshRemoteTransport($resolved['ssh'], is_array($resolved['config'] ?? null) ? $resolved['config'] : []);
            } catch (Throwable) {
                return false;
            }
            // O conteúdo vai em base64: no Windows o `escapeshellarg` do PHP troca aspas e `%` por
            // espaço, e o JSON chegava ilegível ao destino (a manutenção era ignorada).
            $shell = $on
                ? 'mkdir -p temp && echo ' . base64_encode($json) . ' | base64 -d > temp/maintenance.json'
                : 'rm -f temp/maintenance.json';
            $code = $this->runShell($transport->buildRemoteCommand(['sh', '-c', $shell], $transport->remotePath()), $output);
            $ok = $code === 0;
        } else {
            require_once $this->rootPath . '/gestor/bibliotecas/manutencao.php';
            $base = (string)$resolved['gestorPath'];
            $ok = $on ? manutencao_ligar($base, ['owner' => 'pipeline', 'detail' => 'project:update-all ' . $project], 1800) : manutencao_desligar($base);
        }

        if ($on) {
            $ok ? $output->info('Manutenção ligada no destino: o site mostra a tela de atualização até o fim do pipeline.')
                : $output->warning('Não foi possível ligar a manutenção no destino; o pipeline segue sem a tela de atualização.');
        } elseif (!$ok) {
            $output->warning('Não foi possível desligar a manutenção no destino. Ela vence sozinha em até 30 minutos; para desligar já, apague temp/maintenance.json no Gestor.');
        } else {
            $output->info('Manutenção desligada no destino.');
        }

        return $ok;
    }

    /**
     * @return array{file: string, token: string}|null
     */
    private function acquireLock(string $project, InputInterface $input, OutputInterface $output): ?array
    {
        require_once $this->rootPath . '/gestor/bibliotecas/deploy-lock.php';
        // A trava é do DESTINO, não do id: ids diferentes podem apontar para o mesmo ambiente (o mesmo
        // Lab por SSH ou a mesma pasta local), e é o ambiente que não aguenta dois deploys juntos.
        $target = $project;
        try {
            $resolved = (new ProjectEnvironmentResolver($this->rootPath))->resolve($project);
            $ssh = $resolved['ssh'] ?? null;
            $target = is_array($ssh)
                ? 'ssh:' . $ssh['user'] . '@' . $ssh['host'] . ':' . $ssh['port'] . $ssh['path']
                : 'local:' . strtolower(str_replace('\\', '/', (string)($resolved['gestorPath'] ?? $project)));
        } catch (Throwable) {
            // Sem configuração resolvível, a trava fica pelo id (as etapas reportam o erro real).
        }
        // Pasta comum a todos os clones e worktrees do core (irmã do repositório, a mesma no Windows e
        // no WSL): cada worktree tem o seu `dev-environment/data/`, e as travas precisam se enxergar.
        $dir = getenv('C2F_LOCK_DIR') ?: dirname($this->rootPath) . '/.c2f-deploy-locks';
        $file = rtrim($dir, '/\\') . '/deploy-' . substr(sha1($target), 0, 16) . '.lock';
        $owner = ['owner' => 'pipeline', 'detail' => 'project:update-all ' . $project . ' -> ' . $target];
        $waitMinutes = $input->hasOption('no-wait') ? 0 : max(0, (int)$input->getOption('lock-wait', 30));
        $deadline = time() + $waitMinutes * 60;
        $lastNotice = 0;

        while (true) {
            $result = deploy_lock_acquire($file, $owner, 3 * 3600);
            if ($result['ok']) {
                if (!empty($result['stale'])) {
                    $output->warning('Trava vencida assumida: ' . deploy_lock_describe($result['stale']));
                }
                return ['file' => $file, 'token' => $result['token']];
            }
            $holder = deploy_lock_describe($result['holder'] ?? null);
            if (time() >= $deadline) {
                $output->error("Outro pipeline deste projeto está em execução: {$holder}. "
                    . ($waitMinutes > 0 ? "Esperei {$waitMinutes} min. " : '')
                    . 'Tente de novo quando ele terminar.');
                return null;
            }
            if (time() - $lastNotice >= 60) {
                $output->info("Aguardando a trava do projeto ({$holder})...");
                $lastNotice = time();
            }
            sleep(15);
        }
    }

    private function runStages(string $project, mixed $contents, bool $confirmarRemoto, InputInterface $input, OutputInterface $output): int
    {
        // 1. Sync Core -> ID
        $output->section("1/8 Sincronizando Core -> {$project}");
        $coreCmd = new ProjectSyncCoreCommand($this->rootPath);
        $code = $coreCmd->execute($input, $output);
        if ($code !== 0) return $code;

        // req-194: a etapa 2 roda o Phinx ANTES de os arquivos do projeto chegarem (etapa 4). Uma cópia
        // antiga de migração renomeada, deixada no destino por um sync anterior, travava o Phinx aqui
        // ("Duplicate migration") em todos os pipelines seguintes. A limpeza por dono roda no destino
        // antes do banco; não é fatal (choques ficam no log e o Phinx dá a mensagem final).
        $script = $this->rootPath . '/ai-workspace/en/scripts/projects/synchronize-project.sh';
        if (file_exists($script)) {
            $this->runShell(sprintf('bash %s --project %s --migrations-only', escapeshellarg($script), escapeshellarg($project)), $output);
        }

        // 2. Sync DB
        $output->section("2/8 Atualizando Banco de Dados ({$project})");
        $dbCmd = new ProjectSyncDbCommand($this->rootPath);
        // Neste ponto os Data.json enviados são do core. O projeto identifica o destino,
        // mas não pode virar dono desses recursos nem substituir seu manifesto de retirada.
        $coreDbInput = new Input(['c2f', 'project:sync-db', $project, '--core-resources']);
        $code = $dbCmd->execute($coreDbInput, $output);
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
