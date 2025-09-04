<?php
// Inspeção de permissões de módulos por perfil para um módulo (ex: plugin) específico
// Uso: php inspect-plugin-permissions.php --modulo=plugin-demo
// Se --modulo não for passado, usa 'plugin-demo'

declare(strict_types=1);

$mod = 'plugin-demo';
foreach ($argv as $a) {
    if (preg_match('/^--modulo=(.+)$/', $a, $m)) { $mod = $m[1]; }
}

$base = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR; // gestor/
require $base . 'config.php'; // define $_BANCO

$host = $_BANCO['host'] ?? 'localhost';
$db   = $_BANCO['nome'] ?? '';
$user = $_BANCO['usuario'] ?? '';
$pass = $_BANCO['senha'] ?? '';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function out($label, $rows) {
    echo "\n=== $label (" . count($rows) . ") ===\n";
    foreach ($rows as $r) { echo ' - ' . implode(' | ', $r) . "\n"; }
}

$stmt = $pdo->prepare("SELECT perfil, modulo FROM usuarios_perfis_modulos WHERE modulo = :m ORDER BY perfil");
$stmt->execute([':m'=>$mod]);
$perfisMod = $stmt->fetchAll(PDO::FETCH_ASSOC);
out('usuarios_perfis_modulos', $perfisMod);

$stmt2 = $pdo->prepare("SELECT perfil, operacao FROM usuarios_perfis_modulos_operacoes WHERE operacao LIKE :mop ORDER BY perfil");
$stmt2->execute([':mop'=>$mod.'%']);
$perfisOps = $stmt2->fetchAll(PDO::FETCH_ASSOC);
out('usuarios_perfis_modulos_operacoes', $perfisOps);

echo "\nConcluído.\n";
