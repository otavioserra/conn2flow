<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectApiClient;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use RuntimeException;
use Throwable;
use ZipArchive;

/** Desce arquivos divergentes do servidor e decide choques pelo motor de instalação. */
final class ProjectRecoverFilesCommand extends BaseProcessCommand
{
    public function getName(): string { return 'project:recover-files'; }
    public function getDescription(): string { return 'Inventory and recover server-edited files, with local clash decisions.'; }
    public function getAliases(): array { return []; }
    public function getHelp(): string
    {
        return "Usage: c2f project:recover-files <projectID> [--simular|--aplicar] [--camada=projeto|core|plugin:<id>] [--caminho=PATH --acao=sobrescrever|manter|mesclar --arquivo=PATH] [--json]\n"
            . "Default is simulation. --aplicar writes intact project files; for a local clash, select one path and action. Core and plugin files are downloaded only.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $json = $input->hasOption('json');
        $fail = static function (string $message) use ($json, $output): int {
            if ($json) $output->writeln((string)json_encode(['ok' => false, 'erro' => $message], JSON_UNESCAPED_UNICODE));
            else $output->error($message);
            return 1;
        };
        $projectId = (string)($input->getOption('project') ?? $input->getArgument(0) ?? '');
        $camada = (string)($input->getOption('camada') ?? '');
        $caminho = (string)($input->getOption('caminho') ?? '');
        $acao = (string)($input->getOption('acao') ?? '');
        $aplicar = $input->hasOption('aplicar');
        if ($projectId === '' || ($camada !== '' && $camada !== 'core' && $camada !== 'projeto' && !preg_match('/^plugin:[a-z0-9_.-]+$/i', $camada))
            || ($acao !== '' && (!in_array($acao, ['sobrescrever', 'manter', 'mesclar'], true) || $caminho === '' || !$aplicar))) return $fail($this->getHelp());
        require_once $this->rootPath . '/gestor/bibliotecas/instalacao-manifesto.php';
        if ($caminho !== '' && !\instalacao_recuperacao_caminho_valido($caminho)) return $fail('Caminho inválido.');
        try {
            $project = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
            $api = new ProjectApiClient($project);
            $body = $camada === '' ? [] : ['camadas' => [$camada]];
            if ($caminho !== '') $body['caminhos'] = [$caminho];
            $r = $api->request('POST', '_api/project/files', $body);
            if ($r['http'] !== 200) throw new RuntimeException(ProjectApiClient::describeError($r));
            $items = (array)($r['json']['data']['arquivos'] ?? []);
            if ($caminho !== '' && count($items) !== 1) throw new RuntimeException('Caminho não encontrado no inventário: ' . $caminho);
            $dir = $this->rootPath . '/temp/recover-files/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $projectId)
                . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Não foi possível criar a pasta da execução.');
            $download = array_values(array_map(static function ($item) { return $item['caminho']; },
                array_filter($items, static function ($item) { return ($item['hash_disco'] ?? null) !== null; })));
            if ($download) {
                $body['baixar'] = true;
                $body['caminhos'] = $download;
                $z = $api->request('POST', '_api/project/files', $body);
                if ($z['http'] !== 200 || substr($z['corpo'], 0, 2) !== 'PK') throw new RuntimeException(ProjectApiClient::describeError($z));
                $zipPath = $dir . '/arquivos.zip';
                if (file_put_contents($zipPath, $z['corpo']) === false) throw new RuntimeException('Não foi possível gravar o ZIP.');
                self::extrairSeguro($zipPath, $dir . '/servidor', $items);
            }
            $report = ['projeto' => $projectId, 'em' => date('c'), 'modo' => $aplicar ? 'aplicar' : 'simular', 'arquivos' => []];
            foreach ($items as $item) {
                $rel = (string)$item['caminho'];
                $row = $item;
                $row['decisao'] = null;
                $row['resultado'] = 'analise';
                if ($item['camada'] === 'projeto' && $item['estado'] === 'editado') {
                    $local = self::localSeguro((string)$project['gestorPath'], $rel);
                    $server = $dir . '/servidor/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                    $hashLocal = is_file($local) ? hash_file('sha256', $local) : null;
                    $row['hash_local'] = $hashLocal;
                    $row['choque'] = $hashLocal !== $item['hash_manifesto'];
                    $row['acoes'] = $row['choque'] ? \instalacao_choque_acoes(['motivo' => 'editado']) : ['sobrescrever'];
                    if (is_file($local)) {
                        $copy = $dir . '/local/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                        if (!is_dir(dirname($copy))) mkdir(dirname($copy), 0775, true);
                        copy($local, $copy);
                        $row['copia_local'] = $copy;
                    }
                    $row['copia_servidor'] = $server;
                    $row['diff'] = \instalacao_diff(is_file($local) ? (string)file_get_contents($local) : '', (string)file_get_contents($server), $rel);
                    if ($aplicar && (!$row['choque'] || $rel === $caminho && $acao !== '')) {
                        $decision = $row['choque'] ? $acao : 'sobrescrever';
                        $merge = null;
                        if ($decision === 'mesclar') {
                            $mergePath = (string)($input->getOption('arquivo') ?? '');
                            if ($mergePath === '' || !is_file($mergePath)) throw new RuntimeException('Mescla exige --arquivo existente.');
                            $merge = (string)file_get_contents($mergePath);
                        }
                        $result = \instalacao_choque_resolver((string)$project['gestorPath'], [
                            'caminho' => $rel, 'camada' => 'projeto', 'motivo' => 'editado', 'copia_externa' => $server,
                            'hash_novo' => $item['hash_disco'],
                        ], $decision, $merge, false);
                        if (!$result['ok']) throw new RuntimeException($rel . ': ' . $result['erro']);
                        $row['decisao'] = $decision;
                        $row['resultado'] = $decision === 'manter' ? 'mantido' : 'aplicado';
                    } else $row['resultado'] = $row['choque'] ? 'choque_pendente' : 'pronto';
                }
                $report['arquivos'][] = $row;
            }
            $report['total'] = count($items);
            $report['pasta'] = $dir;
            file_put_contents($dir . '/relatorio.json', (string)json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if ($json) $output->writeln((string)json_encode(['ok' => true] + $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            else {
                $output->title('Recuperação de arquivos: ' . $projectId);
                $output->info(count($items) . ' divergência(s); relatório: ' . $dir . '/relatorio.json');
                foreach ($report['arquivos'] as $row) $output->writeln(($row['camada'] ?? 'sem camada') . ' ' . $row['estado'] . ' ' . $row['caminho'] . ' — ' . $row['resultado']);
            }
            return 0;
        } catch (Throwable $e) { return $fail($e->getMessage()); }
    }

    /** Extrai só nomes esperados e confere hash, inclusive contra ZIP malicioso. */
    public static function extrairSeguro(string $zipPath, string $target, array $items): void
    {
        $expected = [];
        foreach ($items as $item) if (($item['hash_disco'] ?? null) !== null) $expected[$item['caminho']] = $item['hash_disco'];
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) throw new RuntimeException('ZIP inválido.');
        if ($zip->numFiles !== count($expected)) { $zip->close(); throw new RuntimeException('ZIP contém arquivos inesperados.'); }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name) || !\instalacao_recuperacao_caminho_valido($name) || !isset($expected[$name])) {
                $zip->close(); throw new RuntimeException('Caminho inseguro no ZIP.');
            }
            $stream = $zip->getStream($name);
            if (!$stream) { $zip->close(); throw new RuntimeException('Arquivo indisponível no ZIP.'); }
            $dest = rtrim($target, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
            if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0775, true) && !is_dir(dirname($dest))) throw new RuntimeException('Falha ao criar pasta.');
            $out = fopen($dest, 'wb');
            if (!$out) throw new RuntimeException('Falha ao gravar arquivo.');
            stream_copy_to_stream($stream, $out);
            fclose($out); fclose($stream);
            if (!hash_equals($expected[$name], (string)hash_file('sha256', $dest))) { $zip->close(); throw new RuntimeException('Hash divergente no ZIP: ' . $name); }
            unset($expected[$name]);
        }
        $zip->close();
        if ($expected) throw new RuntimeException('ZIP incompleto.');
    }

    /** Rejeita links simbólicos no caminho local, inclusive nos diretórios intermediários. */
    public static function localSeguro(string $root, string $rel): string
    {
        if (!\instalacao_recuperacao_caminho_valido($rel) || !is_dir($root)) throw new RuntimeException('Raiz ou caminho local inválido.');
        $path = rtrim($root, '/\\');
        foreach (explode('/', $rel) as $part) {
            $path .= DIRECTORY_SEPARATOR . $part;
            if (is_link($path)) throw new RuntimeException('Link simbólico no caminho local: ' . $rel);
        }
        return $path;
    }
}
