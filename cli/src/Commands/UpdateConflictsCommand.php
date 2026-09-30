<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectApiClient;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-199 / BATCH-205: choques das entregas de um projeto, pela API (`/_api/project/conflicts`).
 *
 * Sem id, lista. Com id, baixa as duas versões para `temp/conflicts/<projeto>/<id>/` — `no-ar.<ext>`,
 * `nova.<ext>` e `mesclado.<ext>` (começa como a versão no ar) — para comparar e mesclar no editor;
 * `--abrir` chama `code --diff`. A decisão vai com `c2f update:resolve`.
 */
final class UpdateConflictsCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'update:conflicts';
    }

    public function getDescription(): string
    {
        return 'List a project\'s delivery clashes, or download both versions of one clash to diff and merge.';
    }

    public function getAliases(): array
    {
        return ['conflicts'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f update:conflicts <projectID> [clashID] [--todos] [--abrir]\n\n"
            . "  (no clashID)  lists pending clashes (--todos includes resolved ones)\n"
            . "  <clashID>     downloads live and new versions to temp/conflicts/<project>/<id>/\n"
            . "                (no-ar.<ext>, nova.<ext>, mesclado.<ext>)\n"
            . "  --abrir       opens `code --diff no-ar nova` after downloading\n\n"
            . "Then decide with: c2f update:resolve <projectID> <clashID> --acao=sobrescrever|manter|mesclar";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (string)($input->getOption('project') ?? $input->getArgument(0) ?? '');
        $id = (int)($input->getOption('id') ?? $input->getArgument(1) ?? 0);
        if ($projectId === '') {
            $output->error($this->getHelp());
            return 1;
        }
        try {
            $projeto = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
            $api = new ProjectApiClient($projeto);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }

        if ($id <= 0) {
            $r = $api->request('POST', '_api/project/conflicts', ['todos' => $input->hasOption('todos')]);
            if ($r['http'] !== 200) {
                $output->error(ProjectApiClient::describeError($r));
                return 1;
            }
            $lista = $r['json']['data']['choques'] ?? [];
            $output->title("Choques de {$projectId}: " . count($lista));
            if ($lista) {
                $output->table(['id', 'motivo', 'camada', 'arquivo', 'versão', 'resolução', 'decisões'], array_map(function ($c) {
                    return [(string)$c['id'], (string)$c['motivo'], (string)$c['camada'], (string)$c['caminho'], (string)($c['versao'] ?? ''),
                        (string)($c['resolucao'] ?? '—'), implode(', ', (array)($c['acoes'] ?? []))];
                }, $lista));
            }
            return 0;
        }

        $r = $api->request('POST', '_api/project/conflicts', ['id' => $id]);
        if ($r['http'] !== 200) {
            $output->error(ProjectApiClient::describeError($r));
            return 1;
        }
        $d = (array)($r['json']['data'] ?? []);
        $pasta = self::gravarVersoes($this->rootPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'conflicts', $projectId, $id, $d);
        $c = (array)($d['choque'] ?? []);
        $output->title("Choque {$id}: " . ($c['caminho'] ?? ''));
        $output->writeln('Motivo: ' . ($c['motivo'] ?? '') . ' | camada: ' . ($c['camada'] ?? '') . ' | dona: ' . ($c['camada_dona'] ?? '—'));
        $output->writeln('Decisões possíveis: ' . implode(', ', (array)($c['acoes'] ?? [])));
        foreach ($pasta['arquivos'] as $nome => $f) {
            $output->writeln("  {$nome}: {$f}");
        }
        if (!empty($c['resolucao'])) {
            $output->warning('Já resolvido: ' . $c['resolucao']);
        }
        if (isset($pasta['arquivos']['no-ar'], $pasta['arquivos']['nova'])) {
            $cmd = 'code --diff ' . escapeshellarg($pasta['arquivos']['no-ar']) . ' ' . escapeshellarg($pasta['arquivos']['nova']);
            if ($input->hasOption('abrir')) {
                return $this->runShell($cmd, $output);
            }
            $output->info('Comparar: ' . $cmd);
        }
        return 0;
    }

    /**
     * Grava as versões do detalhe da API e devolve os caminhos. `mesclado` só é criado se ainda não existe
     * (uma mescla em andamento não é apagada por um novo download).
     *
     * @return array{pasta: string, arquivos: array<string, string>}
     */
    public static function gravarVersoes(string $raiz, string $projectId, int $id, array $detalhe): array
    {
        $pasta = rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_.-]/', '_', $projectId) . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        $ext = pathinfo((string)($detalhe['choque']['caminho'] ?? ''), PATHINFO_EXTENSION);
        $ext = preg_match('/^[A-Za-z0-9]{1,10}$/', $ext) ? '.' . $ext : '';
        $b64 = ($detalhe['codificacao'] ?? 'texto') === 'base64';
        $arquivos = [];
        foreach (['no-ar' => 'no_ar', 'nova' => 'nova'] as $nome => $campo) {
            if (!isset($detalhe[$campo]) || $detalhe[$campo] === null) {
                continue;
            }
            $conteudo = $b64 ? (string)base64_decode((string)$detalhe[$campo]) : (string)$detalhe[$campo];
            file_put_contents($pasta . $nome . $ext, $conteudo);
            $arquivos[$nome] = $pasta . $nome . $ext;
        }
        if (isset($arquivos['no-ar'])) {
            if (!is_file($pasta . 'mesclado' . $ext)) {
                copy($arquivos['no-ar'], $pasta . 'mesclado' . $ext);
            }
            $arquivos['mesclado'] = $pasta . 'mesclado' . $ext;
        }
        file_put_contents($pasta . 'choque.json', (string)json_encode($detalhe['choque'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return ['pasta' => $pasta, 'arquivos' => $arquivos];
    }
}
