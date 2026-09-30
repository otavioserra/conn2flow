<?php
/**
 * Complete file manifest of a project package — req-198 / BATCH-202 (BL-028 A).
 *
 * Prints JSON `{"camada":"projeto","versao":...,"arquivos":{rel: sha256}}` with EVERY file a full
 * package would carry, even when `gitDeploy` ships only the changed ones. The server
 * (`api_project_update`) uses it to know what the project still delivers: a file the project stopped
 * delivering leaves the server (or the core original comes back), and core updates never overwrite
 * what the project overrides.
 *
 * Same exclusions as `deploy-project-v2.sh` (`.git*`, `*.tmp`, `*.log`, `temp/`, `logs/`,
 * `resources/`) plus the folders the server never takes from a manifest (`contents/`, `backups/`,
 * `autenticacoes/`, `installation/`).
 *
 * Usage: php project-file-manifest.php <project gestor path> [version]
 */

$raiz = rtrim($argv[1] ?? '', '/\\');
if ($raiz === '' || !is_dir($raiz)) {
    fwrite(STDERR, "Project path not found: {$raiz}\n");
    exit(1);
}
$versao = $argv[2] ?? date('Ymd-His');
$foraTopo = ['temp', 'logs', 'resources', 'contents', 'backups', 'autenticacoes', 'installation'];

$arquivos = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
foreach ($it as $item) {
    if (!$item->isFile()) continue;
    $rel = str_replace('\\', '/', ltrim(substr($item->getPathname(), strlen($raiz)), '/\\'));
    $partes = explode('/', $rel);
    if (in_array($partes[0], $foraTopo, true)) continue;
    $nome = end($partes);
    $git = false;
    foreach ($partes as $p) if (strpos($p, '.git') === 0) $git = true;
    if ($git || preg_match('/\.(tmp|log)$/i', $nome)) continue;
    $arquivos[$rel] = hash_file('sha256', $item->getPathname());
}
ksort($arquivos);
echo json_encode(['camada' => 'projeto', 'versao' => $versao, 'arquivos' => $arquivos], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
