<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectApiClient;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
use Throwable;

/**
 * req-198 / BATCH-204: volta uma entrega pelo snapshot, a partir da máquina de desenvolvimento.
 *
 * - Projeto `deploy_mode: ssh` (Lab): roda `atualizacoes-sistema.php --rollback=<id>` no servidor, pelo SSH.
 * - Os outros: `POST /_api/project/rollback` com o token do projeto (`api.access_token`; `api_resolve_ip` opcional).
 *
 * O id vem da resposta do deploy (`snapshot`, ex.: `api-20260930-120000-ab12`) ou do relatório da
 * atualização do sistema (`exec-12`, ou só `12`).
 */
final class UpdateRollbackCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'update:rollback';
    }

    public function getDescription(): string
    {
        return 'Roll back a deploy or system update from its snapshot (files; database with --com-banco).';
    }

    public function getAliases(): array
    {
        return ['rollback'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f update:rollback <projectID> <snapshot> [--com-banco] [--dry-run]\n\n"
            . "  <snapshot>     id from the deploy response (api-...) or the system update (exec-<id> or <id>)\n"
            . "  --com-banco    also restores the database dump taken before the delivery\n"
            . "  --dry-run      prints what would run, without running it\n\n"
            . "SSH projects (deploy_mode=ssh) run the updater's --rollback over SSH; the others call\n"
            . "POST /_api/project/rollback with the project's OAuth token.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (string)($input->getOption('project') ?? $input->getArgument(0) ?? '');
        $snapshot = (string)($input->getOption('snapshot') ?? $input->getArgument(1) ?? '');
        $comBanco = $input->hasOption('com-banco');
        $dry = $input->hasOption('dry-run');
        if ($projectId === '' || !self::snapshotValido($snapshot)) {
            $output->error('Informe o projeto e o id do snapshot. ' . $this->getHelp());
            return 1;
        }
        $output->title("Conn2Flow — Rollback {$snapshot} [{$projectId}]");

        try {
            $projeto = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }

        if ($projeto['deployMode'] === 'ssh' && is_array($projeto['ssh'])) {
            $transport = new SshRemoteTransport($projeto['ssh'], $projeto['config']);
            $cmd = $transport->buildRemoteCommand(self::argvSsh($snapshot, $projeto['host'], $comBanco), $transport->remotePath());
            $output->info('SSH: ' . $transport->describe());
            if ($dry) {
                $output->writeln($cmd);
                return 0;
            }
            return $this->runShell($cmd, $output);
        }

        try {
            $api = new ProjectApiClient($projeto);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }
        $output->info('API: ' . $api->url('_api/project/rollback'));
        if ($dry) {
            $output->writeln((string)json_encode(self::corpoApi($snapshot, $comBanco)));
            return 0;
        }
        $r = $api->request('POST', '_api/project/rollback', self::corpoApi($snapshot, $comBanco));
        if ($r['http'] !== 200) {
            $output->error(ProjectApiClient::describeError($r));
            return 1;
        }
        $dados = (array)($r['json']['data'] ?? []);
        $output->success(sprintf(
            '%s: %d arquivo(s) restaurado(s), %d novo(s) removido(s); banco %s.',
            $dados['snapshot'] ?? '',
            (int)($dados['restaurados'] ?? 0),
            (int)($dados['removidos_novos'] ?? 0),
            (string)($dados['banco'] ?? '?')
        ));
        return 0;
    }

    /** Id aceito: `exec-<n>`, `<n>`, `api-…` e afins, só com `[A-Za-z0-9_-]`. */
    public static function snapshotValido(string $id): bool
    {
        return $id !== '' && (bool)preg_match('/^[A-Za-z0-9_-]{1,80}$/', $id);
    }

    /**
     * Argumentos do atualizador no servidor (executado a partir da raiz do Gestor).
     *
     * @return list<string>
     */
    public static function argvSsh(string $snapshot, string $dominio, bool $comBanco): array
    {
        $argv = ['php', 'controladores/atualizacoes/atualizacoes-sistema.php', '--rollback=' . $snapshot, '--domain=' . $dominio];
        if ($comBanco) {
            $argv[] = '--com-banco';
        }
        return $argv;
    }

    /** @return array{snapshot: string, com_banco: bool} */
    public static function corpoApi(string $snapshot, bool $comBanco): array
    {
        return ['snapshot' => $snapshot, 'com_banco' => $comBanco];
    }
}
