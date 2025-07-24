<?php

// Durante a instalação, usamos os parâmetros diretamente do .env se estiver disponível
// ou usa variáveis de ambiente padrão do sistema
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_DATABASE'] ?? '';
$dbUser = $_ENV['DB_USERNAME'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

// Se não encontrou no ambiente, tenta carregar do config.php se existir
if (empty($dbName) && file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    $dbHost = $_BANCO['host'] ?? $dbHost;
    $dbName = $_BANCO['nome'] ?? $dbName;
    $dbUser = $_BANCO['usuario'] ?? $dbUser;
    $dbPass = $_BANCO['senha'] ?? $dbPass;
}

return [
    'paths' => [
        // Como o arquivo de config está em /utilitarios, subimos um nível para encontrar a pasta /db.
        'migrations' => '%%PHINX_CONFIG_DIR%%/../db/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/../db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'gestor', // Usaremos este como nosso único ambiente
        'gestor' => [
            'adapter' => 'mysql',
            'host'    => $dbHost,
            'name'    => $dbName,
            'user'    => $dbUser,
            'pass'    => $dbPass,
            'port'    => 3306,
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];