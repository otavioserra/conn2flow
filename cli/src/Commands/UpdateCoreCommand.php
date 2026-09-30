<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectApiClient;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-201 / BATCH-209: atualização do sistema (core) de uma instalação, disparada pela API
 * (`POST /_api/system/update`, `action=run`) e acompanhada por `run-status`.
 *
 * O pacote vem do GitHub (última release ou `--tag`); o servidor roda o mesmo atualizador do CLI (trava,
 * snapshot, dump, verificação e volta automática). Uma instalação por chamada: a atualização em massa fica
 * na operação de cada rede (no Conn2Flow, no `conn2flow-site`).
 */
final class UpdateCoreCommand extends BaseProcessCommand
{
    /** Flags do comando → opção da API. */
    private const OPCOES = [
        'tag' => 'tag', 'only-files' => 'only_files', 'only-db' => 'only_db', 'no-db' => 'no_db', 'dry-run' => 'dry_run',
        'backup' => 'backup', 'no-verify' => 'no_verify', 'no-health' => 'no_health', 'no-rollback' => 'no_rollback',
        'health-url' => 'health_url', 'health-ip' => 'health_ip', 'force-all' => 'force_all', 'tables' => 'tables',
        'local-artifact' => 'local_artifact', 'debug' => 'debug',
    ];

    public function getName(): string
    {
        return 'update:core';
    }

    public function getDescription(): string
    {
        return 'Trigger the system (core) update of one installation through its API and follow it.';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getHelp(): string
    {
        return "Usage: c2f update:core <projectID> [--tag=TAG] [--wait] [--status=RUN] [--runs] [--json] [update options]\n\n"
            . "  --wait          follows the run until it ends (polls run-status)\n"
            . "  --status=RUN    shows the state of a previous run\n"
            . "  --runs          lists the latest runs of the installation\n"
            . "  --json          prints JSON (one line per poll with --wait)\n"
            . "  update options  --tag --only-files --only-db --no-db --dry-run --backup --no-verify --no-health\n"
            . "                  --no-rollback --health-url --health-ip --force-all --tables --local-artifact --debug\n\n"
            . "Without --tag the server downloads the latest gestor release from GitHub. Roll back with\n"
            . "c2f update:rollback <projectID> exec-<id> [--com-banco].";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (string)($input->getOption('project') ?? $input->getArgument(0) ?? '');
        $json = $input->hasOption('json');
        $falhar = function (string $mensagem) use ($json, $output): int {
            if ($json) {
                $output->writeln((string)json_encode(['ok' => false, 'erro' => $mensagem], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $output->error($mensagem);
            }
            return 1;
        };
        if ($projectId === '') {
            return $falhar($this->getHelp());
        }
        try {
            $projeto = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
            $api = new ProjectApiClient($projeto);
        } catch (Throwable $e) {
            return $falhar($e->getMessage());
        }

        if ($input->hasOption('runs')) {
            $r = $api->request('POST', '_api/system/update', ['action' => 'runs']);
            if ($r['http'] !== 200) {
                return $falhar(ProjectApiClient::describeError($r));
            }
            $lista = (array)($r['json']['data']['runs'] ?? []);
            if ($json) {
                $output->writeln((string)json_encode(['ok' => true, 'runs' => $lista], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return 0;
            }
            $output->table(['run', 'início', 'estado', 'código', 'snapshot'], array_map(function ($x) {
                return [(string)$x['run'], (string)($x['iniciado_em'] ?? ''), (string)$x['status'], (string)($x['codigo'] ?? '—'), (string)($x['snapshot'] ?? '—')];
            }, $lista));
            return 0;
        }

        $run = (string)($input->getOption('status') ?? '');
        if ($run === '') {
            $opcoes = self::opcoesDaEntrada(function (string $n) use ($input) { return $input->getOption($n); }, function (string $n) use ($input) { return $input->hasOption($n); });
            $r = $api->request('POST', '_api/system/update', ['action' => 'run', 'opcoes' => $opcoes], 60);
            if ($r['http'] !== 202 && $r['http'] !== 200) {
                return $falhar(ProjectApiClient::describeError($r));
            }
            $run = (string)($r['json']['data']['run'] ?? '');
            if ($json) {
                $output->writeln((string)json_encode(['ok' => true, 'run' => $run, 'status' => 'running'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $output->success("Atualização disparada em {$projectId}: {$run}");
            }
            if (!$input->hasOption('wait')) {
                if (!$json) {
                    $output->info("Acompanhar: c2f update:core {$projectId} --status={$run}");
                }
                return 0;
            }
        }

        $ultimo = '';
        $indisponivel = 0;
        do {
            $r = $api->request('POST', '_api/system/update', ['action' => 'run-status', 'run' => $run], 60);
            // A API passa pelo mesmo Gestor que está sendo atualizado: durante a troca de arquivos (ou até a
            // volta automática) ela pode responder 5xx ou nem responder. Com --wait, espera até 5 minutos.
            if (($r['http'] === 0 || $r['http'] >= 500) && $input->hasOption('wait') && $indisponivel < 60) {
                if ($indisponivel === 0 && !$json) {
                    $output->writeln('  (API indisponível durante a atualização: ' . ProjectApiClient::describeError($r) . '; aguardando…)');
                }
                $indisponivel++;
                sleep(5);
                continue;
            }
            if ($r['http'] !== 200) {
                return $falhar(ProjectApiClient::describeError($r));
            }
            $indisponivel = 0;
            $e = (array)($r['json']['data'] ?? []);
            $status = (string)($e['status'] ?? 'error');
            if ($json) {
                $output->writeln((string)json_encode(['ok' => true] + $e, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $linha = end($e['fim_do_log']) ?: '';
                if ($linha !== $ultimo) {
                    $output->writeln('  ' . $linha);
                    $ultimo = (string)$linha;
                }
            }
            if ($status !== 'running' || !$input->hasOption('wait')) {
                break;
            }
            sleep(5);
        } while (true);

        if (!$json) {
            $output->writeln('');
            $output->writeln('Estado: ' . $status . (isset($e['codigo']) ? ' (código ' . $e['codigo'] . ')' : ''));
            foreach (['snapshot' => 'Snapshot', 'saude' => 'Verificação', 'rollback' => 'Volta automática'] as $k => $rotulo) {
                if (!empty($e[$k])) {
                    $output->writeln($rotulo . ': ' . $e[$k]);
                }
            }
            if (!empty($e['snapshot']) && $status === 'success') {
                $output->info("Para voltar: c2f update:rollback {$projectId} {$e['snapshot']} [--com-banco]");
            }
        }
        return in_array($status, ['success', 'running'], true) ? 0 : 1;
    }

    /**
     * Opções da API a partir das flags do comando.
     *
     * @param callable(string): mixed $valor
     * @param callable(string): bool $tem
     * @return array<string, string|bool>
     */
    public static function opcoesDaEntrada(callable $valor, callable $tem): array
    {
        $out = [];
        foreach (self::OPCOES as $flag => $opcao) {
            if (!$tem($flag)) {
                continue;
            }
            $v = $valor($flag);
            $out[$opcao] = ($v === null || $v === true || $v === '') ? true : (string)$v;
        }
        return $out;
    }
}
