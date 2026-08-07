<?php

/**
 * Configuração do Phinx que funciona tanto durante a instalação quanto em produção
 * 
 * CONTEXTO DE INSTALAÇÃO:
 * - Durante a instalação, o config.php ainda não existe
 * - Usamos variáveis de ambiente definidas pelo instalador
 * 
 * CONTEXTO DE PRODUÇÃO:
 * - Após a instalação, o config.php existe e contém $_BANCO
 * - Usamos as configurações normais do sistema
 */

// Detecta se estamos em contexto de instalação ou produção
$configPath = __DIR__ . '/config.php';
$isInstalling = !file_exists($configPath);

if ($isInstalling) {
    // CONTEXTO DE INSTALAÇÃO: Usa variáveis de ambiente do instalador
    $dbConfig = [
        'type' => $_ENV['PHINX_DB_ADAPTER'] ?? getenv('PHINX_DB_ADAPTER') ?: ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'mysql'),
        'host' => $_ENV['PHINX_DB_HOST'] ?? getenv('PHINX_DB_HOST') ?? 'localhost',
        'port' => $_ENV['PHINX_DB_PORT'] ?? getenv('PHINX_DB_PORT') ?: ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: null),
        'name' => $_ENV['PHINX_DB_NAME'] ?? getenv('PHINX_DB_NAME') ?? '',
        'user' => $_ENV['PHINX_DB_USER'] ?? getenv('PHINX_DB_USER') ?? '',
        'pass' => $_ENV['PHINX_DB_PASS'] ?? getenv('PHINX_DB_PASS') ?? '',
        'schema' => $_ENV['PHINX_DB_SCHEMA'] ?? getenv('PHINX_DB_SCHEMA') ?: ($_ENV['DB_SCHEMA'] ?? getenv('DB_SCHEMA') ?: 'public'),
    ];
    
    // Validação básica para evitar erros durante instalação
    if (empty($dbConfig['host']) || empty($dbConfig['name']) || empty($dbConfig['user'])) {
        throw new Exception("Configurações de banco não definidas para instalação. Verifique as variáveis PHINX_DB_*");
    }
} else {
    // CONTEXTO DE PRODUÇÃO: Carrega config.php normalmente
    require_once $configPath;

    global $_BANCO;
    
    $dbConfig = [
        'type' => $_BANCO['tipo'] ?? 'mysql',
        'host' => $_BANCO['host'] ?? 'localhost',
        'port' => $_BANCO['porta'] ?? null,
        'name' => $_BANCO['nome'] ?? '',
        'user' => $_BANCO['usuario'] ?? '',
        'pass' => $_BANCO['senha'] ?? '',
        'schema' => $_BANCO['schema'] ?? 'public',
    ];
}

$dbType = strtolower(trim((string)($dbConfig['type'] ?? 'mysql')));
$isPostgreSql = in_array($dbType, ['pgsql', 'pdo_pgsql', 'postgres', 'postgresql'], true);
$dbAdapter = $isPostgreSql ? 'pgsql' : 'mysql';
$dbPort = (int)($dbConfig['port'] ?: ($isPostgreSql ? 5432 : 3306));

$environment = [
    'adapter' => $dbAdapter,
    'host'    => $dbConfig['host'],
    'name'    => $dbConfig['name'],
    'user'    => $dbConfig['user'],
    'pass'    => $dbConfig['pass'],
    'port'    => $dbPort,
];

if ($isPostgreSql) {
    $environment['schema'] = $dbConfig['schema'] ?: 'public';
} else {
    $environment['charset'] = 'utf8mb4';
}

return [
    'paths' => [
        // Como o arquivo de config está na raiz do gestor, vamos para a pasta /db.
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'gestor', // Usaremos este como nosso único ambiente
        'gestor' => $environment,
    ],
    'version_order' => 'creation'
];
