<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectApiClient;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-199 / BATCH-205: decisão sobre um choque de um projeto, pela API (`/_api/project/resolve`).
 *
 * `mesclar` envia o arquivo mesclado — por padrão o `mesclado.<ext>` baixado pelo `update:conflicts` — e,
 * com `--local`, grava o mesmo conteúdo no repositório local do projeto (o caminho do choque dentro do
 * Gestor do projeto), para a próxima entrega já levar a mescla.
 */
final class UpdateResolveCommand extends BaseProcessCommand
{
    private const ACOES = ['sobrescrever', 'manter', 'mesclar'];

    public function getName(): string
    {
        return 'update:resolve';
    }

    public function getDescription(): string
    {
        return 'Resolve a delivery clash: overwrite with the new version, keep the live one, or send a merge.';
    }

    public function getAliases(): array
    {
        return ['resolve'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f update:resolve <projectID> <clashID> --acao=sobrescrever|manter|mesclar [--arquivo=PATH] [--local]\n\n"
            . "  --acao      the decision (the clash lists which ones apply)\n"
            . "  --arquivo   merged file for --acao=mesclar (default: temp/conflicts/<project>/<id>/mesclado.<ext>)\n"
            . "  --local     with mesclar, also writes the merge into the local project repository";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (string)($input->getOption('project') ?? $input->getArgument(0) ?? '');
        $id = (int)($input->getOption('id') ?? $input->getArgument(1) ?? 0);
        $acao = (string)($input->getOption('acao') ?? '');
        if ($projectId === '' || $id <= 0 || !in_array($acao, self::ACOES, true)) {
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

        $corpo = ['id' => $id, 'acao' => $acao];
        $conteudo = null;
        $caminho = null;
        if ($acao === 'mesclar') {
            $arquivo = (string)($input->getOption('arquivo') ?? '');
            if ($arquivo === '') {
                $arquivo = self::mescladoPadrao($this->rootPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'conflicts', $projectId, $id) ?? '';
            }
            if ($arquivo === '' || !is_file($arquivo)) {
                $output->error('Arquivo mesclado não encontrado. Baixe com `c2f update:conflicts ' . $projectId . ' ' . $id . '` ou passe --arquivo.');
                return 1;
            }
            $conteudo = (string)file_get_contents($arquivo);
            $corpo += self::corpoConteudo($conteudo);
            $meta = dirname($arquivo) . DIRECTORY_SEPARATOR . 'choque.json';
            $caminho = is_file($meta) ? (string)(json_decode((string)file_get_contents($meta), true)['caminho'] ?? '') : null;
            $output->info('Mescla: ' . $arquivo);
        }

        $r = $api->request('POST', '_api/project/resolve', $corpo);
        if ($r['http'] !== 200) {
            $output->error(ProjectApiClient::describeError($r));
            return 1;
        }
        $dados = (array)($r['json']['data'] ?? []);
        $output->success(sprintf('Choque %d: %s (%d linha(s) resolvida(s)).', $id, $acao, (int)($dados['resolvidos'] ?? 1)));

        if ($acao === 'mesclar' && $input->hasOption('local') && $conteudo !== null && $caminho) {
            $local = self::caminhoLocal((string)$projeto['gestorPath'], $caminho);
            if ($local === null) {
                $output->warning('Caminho do choque inválido para gravar no repositório local: ' . $caminho);
            } else {
                if (!is_dir(dirname($local))) {
                    @mkdir(dirname($local), 0775, true);
                }
                file_put_contents($local, $conteudo);
                $output->info('Mescla gravada no repositório local: ' . $local);
            }
        }
        return 0;
    }

    /** Corpo do conteúdo mesclado: texto como está; binário em base64. */
    public static function corpoConteudo(string $conteudo): array
    {
        return strpos($conteudo, "\0") !== false
            ? ['conteudo' => base64_encode($conteudo), 'codificacao' => 'base64']
            : ['conteudo' => $conteudo, 'codificacao' => 'texto'];
    }

    /** `mesclado.<ext>` baixado pelo update:conflicts, se existir. */
    public static function mescladoPadrao(string $raiz, string $projectId, int $id): ?string
    {
        $pasta = rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_.-]/', '_', $projectId) . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        $achados = glob($pasta . 'mesclado*') ?: [];
        return $achados[0] ?? null;
    }

    /** Caminho do arquivo no repositório local do projeto; null quando o caminho tenta sair da pasta. */
    public static function caminhoLocal(string $gestorPath, string $caminho): ?string
    {
        $caminho = str_replace('\\', '/', $caminho);
        if ($caminho === '' || strpos($caminho, '..') !== false || $caminho[0] === '/') {
            return null;
        }
        return rtrim($gestorPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminho);
    }
}
