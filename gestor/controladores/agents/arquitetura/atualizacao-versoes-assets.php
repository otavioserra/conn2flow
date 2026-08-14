<?php

declare(strict_types=1);

const ASSET_VERSION_MANIFEST_VERSION = 1;

/** @return list<string> */
function asset_version_extensions(): array
{
    return ['js', 'mjs', 'css', 'map', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'pdf', 'mp3', 'mp4', 'webm'];
}

function asset_version_ignored_directory(string $name): bool
{
    return in_array(strtolower($name), ['resources', 'node_modules', 'vendor', 'tests', 'logs', '.git', '.tailwind-build'], true);
}

/** @return array<string,string> Caminho relativo => SHA-256. */
function asset_version_files(string $directory, ?callable $filter = null): array
{
    if (!is_dir($directory)) return [];
    $directory = rtrim($directory, '/\\');
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $current): bool {
                return !$current->isDir() || !asset_version_ignored_directory($current->getFilename());
            }
        )
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) continue;
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, asset_version_extensions(), true)) continue;
        $path = $file->getPathname();
        $relative = ltrim(str_replace('\\', '/', substr($path, strlen($directory))), '/');
        if ($relative === 'asset-versions.json' || $relative === 'asset-version.json') continue;
        if ($filter !== null && !$filter($relative, $path)) continue;
        $files[$relative] = hash_file('sha256', $path) ?: '';
    }
    ksort($files, SORT_STRING);
    return $files;
}

function asset_version_token(array $files): string
{
    return substr(hash('sha256', json_encode($files, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 16);
}

function asset_version_write_json(string $path, array $data, bool $check): bool
{
    $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    $current = is_file($path) ? (string)file_get_contents($path) : null;
    if ($current === $content) return false;
    if ($check) throw new RuntimeException("Versao de assets desatualizada: {$path}");

    $temporary = $path . '.new-' . getmypid() . '-' . bin2hex(random_bytes(3));
    if (file_put_contents($temporary, $content, LOCK_EX) === false) {
        @unlink($temporary);
        throw new RuntimeException("Falha ao atualizar versao de assets: {$path}");
    }
    $backup = null;
    if (is_file($path)) {
        $backup = $path . '.bak-' . getmypid() . '-' . bin2hex(random_bytes(3));
        if (!rename($path, $backup)) {
            @unlink($temporary);
            throw new RuntimeException("Falha ao preparar versao de assets: {$path}");
        }
    }
    if (!rename($temporary, $path)) {
        if ($backup !== null) @rename($backup, $path);
        @unlink($temporary);
        throw new RuntimeException("Falha ao atualizar versao de assets: {$path}");
    }
    if ($backup !== null) @unlink($backup);
    return true;
}

/**
 * Atualiza tokens determinísticos sem executar Git, rede ou deploy.
 *
 * @return array{modules_scanned:int,modules_changed:int,system_changed:bool,project_changed:bool,system_token:string,project_token:?string}
 */
function asset_versions_update(string $gestorRoot, bool $check = false, bool $quiet = false): array
{
    $gestorRoot = rtrim($gestorRoot, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($gestorRoot)) throw new RuntimeException("Raiz do gestor inexistente: {$gestorRoot}");

    $stats = ['modules_scanned' => 0, 'modules_changed' => 0, 'system_changed' => false, 'project_changed' => false, 'system_token' => '', 'project_token' => null];
    $modulesDirectory = $gestorRoot . 'modulos';
    $modulePlans = [];

    // Primeiro valida todos os JSONs; somente depois inicia qualquer gravação.
    foreach (glob($modulesDirectory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $moduleDirectory) {
        $moduleId = basename($moduleDirectory);
        $jsonPath = $moduleDirectory . DIRECTORY_SEPARATOR . $moduleId . '.json';
        if (!is_file($jsonPath)) continue;
        $decoded = json_decode((string)file_get_contents($jsonPath), true);
        if (!is_array($decoded)) throw new RuntimeException("JSON de modulo invalido: {$jsonPath}");

        $files = asset_version_files($moduleDirectory);
        $stats['modules_scanned']++;
        if ($files === []) {
            if (array_key_exists('asset_version', $decoded)) {
                unset($decoded['asset_version']);
                $modulePlans[] = [$jsonPath, $decoded];
            }
            continue;
        }

        $token = asset_version_token($files);
        if (($decoded['asset_version'] ?? null) !== $token) {
            $decoded['asset_version'] = $token;
            $modulePlans[] = [$jsonPath, $decoded];
        }
    }
    foreach ($modulePlans as [$jsonPath, $decoded]) {
        if (asset_version_write_json($jsonPath, $decoded, $check)) $stats['modules_changed']++;
    }

    $assetsDirectory = $gestorRoot . 'assets';
    $systemFiles = asset_version_files($assetsDirectory);
    $owners = [];
    foreach ($systemFiles as $relative => $hash) {
        $owner = explode('/', $relative, 2)[0];
        $owners[$owner][$relative] = $hash;
    }
    foreach ($owners as $owner => $files) $owners[$owner] = asset_version_token($files);
    ksort($owners, SORT_STRING);
    $stats['system_token'] = asset_version_token($systemFiles);
    $systemManifest = [
        'version' => ASSET_VERSION_MANIFEST_VERSION,
        'system' => $stats['system_token'],
        'owners' => $owners,
    ];
    if (is_dir($assetsDirectory)) {
        $stats['system_changed'] = asset_version_write_json($assetsDirectory . DIRECTORY_SEPARATOR . 'asset-versions.json', $systemManifest, $check);
    }

    $contentsDirectory = $gestorRoot . 'contents';
    if (is_dir($contentsDirectory)) {
        // `resources`, uploads e arquivos do usuário não entram. Assets globais do projeto
        // vivem em `contents/project`; arquivos estáticos diretamente em `contents` também entram.
        $projectFiles = asset_version_files($contentsDirectory, static function (string $relative): bool {
            return !str_contains($relative, '/') || str_starts_with($relative, 'project/');
        });
        $stats['project_token'] = asset_version_token($projectFiles);
        $stats['project_changed'] = asset_version_write_json(
            $contentsDirectory . DIRECTORY_SEPARATOR . 'asset-version.json',
            ['version' => ASSET_VERSION_MANIFEST_VERSION, 'project' => $stats['project_token']],
            $check
        );
    }

    if (!$quiet) {
        echo 'Assets: ' . $stats['modules_scanned'] . ' modulos verificados; '
            . $stats['modules_changed'] . ' tokens de modulo alterados; sistema=' . $stats['system_token']
            . ($stats['project_token'] !== null ? '; projeto=' . $stats['project_token'] : '') . PHP_EOL;
    }
    return $stats;
}

if (!defined('ASSET_VERSIONS_NO_AUTORUN') && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $args = [];
    foreach (array_slice($argv ?? [], 1) as $argument) {
        if (preg_match('/^--([^=]+)=(.*)$/', $argument, $match)) $args[$match[1]] = $match[2];
        elseif (str_starts_with($argument, '--')) $args[substr($argument, 2)] = true;
    }
    $defaultRoot = realpath(__DIR__ . '/../../..') ?: (__DIR__ . '/../../..');
    try {
        asset_versions_update((string)($args['root'] ?? $defaultRoot), isset($args['check']), isset($args['quiet']));
        exit(0);
    } catch (Throwable $error) {
        fwrite(STDERR, 'ERRO assets: ' . $error->getMessage() . PHP_EOL);
        exit(1);
    }
}
