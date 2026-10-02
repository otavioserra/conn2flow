<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
use Throwable;

/**
 * req-213: confere por hash se o código do destino é o da origem (core + projeto).
 *
 * O envio usa `rsync -u`, que não repõe arquivo com data mais nova no destino, e nunca apaga. Depois
 * de dois deploys de árvores diferentes no mesmo destino, sobrava código de uma delas sem nenhum
 * aviso. Este comando não altera nada: lista o que diverge e o que só existe no destino.
 */
final class ProjectVerifyCommand extends BaseProcessCommand
{
    /** Código que decide comportamento. Recursos compilados têm conferência própria no banco. */
    private const EXTENSOES = ['php', 'js', 'sh'];

    /** Fora da comparação: dependências, estado do ambiente e saídas geradas no destino. */
    private const FORA = ['vendor/', 'node_modules/', 'temp/', 'logs/', 'autenticacoes/', 'contents/', 'db/data/', 'db/orphans/', 'dist/', 'assets/vendor/', 'cli/', '.git/'];

    /** Pastas em que arquivo só no destino é sobra de deploy, e não dado do ambiente. */
    private const GERIDAS = ['modulos/', 'bibliotecas/', 'controladores/', 'db/migrations/', 'assets/'];

    public function getName(): string
    {
        return 'project:verify';
    }

    public function getDescription(): string
    {
        return 'Compare by hash the code deployed at a project target with the source (core + project).';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getHelp(): string
    {
        return "Usage: c2f project:verify <projectID> [--strict]\n\n"
            . "Read-only. Lists code files (php, js, sh) whose content at the target differs from the source and "
            . "files that exist only at the target inside managed folders. --strict exits 1 when anything is listed.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        if (!$project) {
            $output->error('Project ID is required. Example: c2f project:verify lumix');
            return 1;
        }

        try {
            $resolved = (new ProjectEnvironmentResolver($this->rootPath))->resolve((string)$project);
        } catch (Throwable $e) {
            $output->error('Projeto não resolvido: ' . $e->getMessage());
            return 1;
        }

        $origem = self::mapaLocal($this->rootPath . '/gestor');
        $projeto = (string)($resolved['gestorPath'] ?? '');
        if ($projeto !== '' && is_dir($projeto)) {
            // O projeto se sobrepõe ao core no destino.
            $origem = array_merge($origem, self::mapaLocal($projeto));
        }

        if (is_array($resolved['ssh'] ?? null)) {
            $destino = $this->mapaRemoto($resolved, $output);
        } else {
            $alvo = (string)(($resolved['config']['target'] ?? '') ?: ($resolved['config']['path_tests'] ?? ''));
            $destino = $alvo !== '' && is_dir($alvo) ? array_map(static fn(array $h): string => $h[0], self::mapaLocal($alvo)) : null;
        }
        if ($destino === null) {
            $output->warning('Conferência por hash não realizada: não foi possível ler os arquivos do destino.');
            return $input->hasOption('strict') ? 1 : 0;
        }

        $r = self::comparar($origem, $destino);
        if ($r['diferentes'] === [] && $r['sobras'] === []) {
            $output->success(sprintf('Destino confere com a origem: %d arquivo(s) de código comparado(s).', $r['comparados']));
            return 0;
        }

        if ($r['diferentes'] !== []) {
            $output->warning(sprintf('%d arquivo(s) no destino com conteúdo diferente da origem (o rsync não repõe arquivo mais novo no destino):', count($r['diferentes'])));
            foreach (array_slice($r['diferentes'], 0, 40) as $arquivo) {
                $output->write('  DIFERENTE  ' . $arquivo . "\n");
            }
        }
        if ($r['sobras'] !== []) {
            $output->warning(sprintf('%d arquivo(s) só no destino, em pasta gerida pelo deploy (o rsync não apaga):', count($r['sobras'])));
            foreach (array_slice($r['sobras'], 0, 40) as $arquivo) {
                $output->write('  SOBRA      ' . $arquivo . "\n");
            }
        }
        $output->info('Para repor um arquivo diferente: atualize a data dele na origem (touch) e publique de novo. Sobra se remove no destino.');

        return $input->hasOption('strict') ? 1 : 0;
    }

    /**
     * @return array<string, array{0: string, 1: string}> caminho relativo => [hash do arquivo, hash com fim de linha LF]
     */
    public static function mapaLocal(string $raiz): array
    {
        $raiz = rtrim(str_replace('\\', '/', $raiz), '/');
        $mapa = [];
        if (!is_dir($raiz)) {
            return $mapa;
        }
        $iterador = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS),
            static function (\SplFileInfo $item) use ($raiz): bool {
                $rel = ltrim(substr(str_replace('\\', '/', $item->getPathname()), strlen($raiz)), '/');
                return $item->isDir() ? !self::fora($rel . '/') : self::comparavel($rel);
            }
        ));
        foreach ($iterador as $arquivo) {
            $rel = ltrim(substr(str_replace('\\', '/', $arquivo->getPathname()), strlen($raiz)), '/');
            $conteudo = (string)file_get_contents($arquivo->getPathname());
            $mapa[$rel] = [hash('sha256', $conteudo), hash('sha256', str_replace("\r\n", "\n", $conteudo))];
        }
        return $mapa;
    }

    public static function fora(string $relativo): bool
    {
        foreach (self::FORA as $prefixo) {
            if (str_starts_with($relativo, $prefixo) || str_contains($relativo, '/' . $prefixo)) {
                return true;
            }
        }
        return false;
    }

    public static function comparavel(string $relativo): bool
    {
        return in_array(strtolower(pathinfo($relativo, PATHINFO_EXTENSION)), self::EXTENSOES, true) && !self::fora($relativo);
    }

    /**
     * @param array<string, array{0: string, 1: string}> $origem
     * @param array<string, string> $destino caminho relativo => hash
     * @return array{comparados: int, diferentes: list<string>, sobras: list<string>}
     */
    public static function comparar(array $origem, array $destino): array
    {
        $diferentes = [];
        $sobras = [];
        $comparados = 0;
        foreach ($destino as $arquivo => $hash) {
            if (!self::comparavel($arquivo)) {
                continue;
            }
            if (!isset($origem[$arquivo])) {
                foreach (self::GERIDAS as $pasta) {
                    if (str_starts_with($arquivo, $pasta)) {
                        $sobras[] = $arquivo;
                        break;
                    }
                }
                continue;
            }
            $comparados++;
            if (!in_array($hash, $origem[$arquivo], true)) {
                $diferentes[] = $arquivo;
            }
        }
        sort($diferentes);
        sort($sobras);
        return ['comparados' => $comparados, 'diferentes' => $diferentes, 'sobras' => $sobras];
    }

    /**
     * Saída de `sha256sum` (`<hash>  ./caminho`) em mapa caminho => hash.
     *
     * @return array<string, string>
     */
    public static function lerSha256sum(string $saida): array
    {
        $mapa = [];
        foreach (preg_split('/\r?\n/', $saida) ?: [] as $linha) {
            if (preg_match('/^([a-f0-9]{64})\s+\*?(?:\.\/)?(.+)$/', $linha, $m)) {
                $mapa[$m[2]] = $m[1];
            }
        }
        return $mapa;
    }

    /**
     * @param array<string, mixed> $resolved
     * @return array<string, string>|null
     */
    private function mapaRemoto(array $resolved, OutputInterface $output): ?array
    {
        try {
            $transport = new SshRemoteTransport($resolved['ssh'], is_array($resolved['config'] ?? null) ? $resolved['config'] : []);
        } catch (Throwable) {
            return null;
        }
        $nomes = implode(' -o ', array_map(static fn(string $e): string => "-name '*." . $e . "'", self::EXTENSOES));
        $podas = implode(' ', array_map(static fn(string $p): string => "-not -path './" . $p . "*' -not -path '*/" . $p . "*'", self::FORA));
        $shell = 'find . -type f \( ' . $nomes . ' \) ' . $podas . ' -print0 | xargs -0 -r sha256sum';
        $comando = $transport->buildRemoteCommand(['sh', '-c', $shell], $transport->remotePath());

        $processo = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->rootPath);
        if (!is_resource($processo)) {
            return null;
        }
        $saida = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($processo);

        $mapa = self::lerSha256sum($saida);
        return $mapa === [] ? null : $mapa;
    }
}
