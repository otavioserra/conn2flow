<?php
// Inspeção rápida de recursos por plugin.
// Uso: php scripts/inspect-plugin-resources.php --plugin=example-plugin-test

declare(strict_types=1);

$args = [];
foreach ($argv as $a) {
    if (preg_match('/^--([^=]+)=(.+)$/',$a,$m)) { $args[$m[1]]=$m[2]; }
}
$plugin = $args['plugin'] ?? null;
if (!$plugin) {
    fwrite(STDERR, "Informe --plugin=slug\n");
    exit(1);
}

$base = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR; // gestor/
$configFile = $base . 'config.php';
if (!file_exists($configFile)) { fwrite(STDERR, "config.php não encontrado\n"); exit(2); }
require $configFile; // define $_BANCO

$host = $_BANCO['host'] ?? 'localhost';
$db   = $_BANCO['nome'] ?? '';
$user = $_BANCO['usuario'] ?? '';
$pass = $_BANCO['senha'] ?? '';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function tableExists(PDO $pdo, string $t): bool {
    $st = $pdo->prepare('SHOW TABLES LIKE :t');
    $st->execute([':t'=>$t]);
    return (bool)$st->fetch(PDO::FETCH_NUM);
}

function hasColumn(PDO $pdo, string $t, string $col): bool {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM `$t`");
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
            if ($c['Field'] === $col) return true;
        }
    } catch (Throwable $e) { return false; }
    return false;
}

$tables = ['paginas','layouts','componentes','variaveis'];
$report = ['plugin'=>$plugin,'tables'=>[]];
foreach ($tables as $t) {
    if (!tableExists($pdo,$t)) { $report['tables'][$t] = ['exists'=>false]; continue; }
    $entry = ['exists'=>true];
    $entry['has_plugin_col'] = hasColumn($pdo,$t,'plugin');
    $entry['total'] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    if ($entry['has_plugin_col']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE plugin = :p");
        $stmt->execute([':p'=>$plugin]);
        $entry['with_plugin'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM `$t` WHERE plugin = :p LIMIT 3");
        $stmt->execute([':p'=>$plugin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Reduzir colunas grandes
        foreach ($rows as &$r) {
            foreach ($r as $k=>$v) {
                if (is_string($v) && strlen($v) > 80) { $r[$k] = substr($v,0,77).'...'; }
            }
        }
        $entry['sample'] = $rows;
    }
    $report['tables'][$t] = $entry;
}

echo json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";