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
        'host' => $_ENV['PHINX_DB_HOST'] ?? getenv('PHINX_DB_HOST') ?? 'localhost',
        'name' => $_ENV['PHINX_DB_NAME'] ?? getenv('PHINX_DB_NAME') ?? '',
        'user' => $_ENV['PHINX_DB_USER'] ?? getenv('PHINX_DB_USER') ?? '',
        'pass' => $_ENV['PHINX_DB_PASS'] ?? getenv('PHINX_DB_PASS') ?? '',
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
        'host' => $_BANCO['host'] ?? 'localhost',
        'name' => $_BANCO['nome'] ?? '',
        'user' => $_BANCO['usuario'] ?? '',
        'pass' => $_BANCO['senha'] ?? '',
    ];
}

return [
    'paths' => [
        // Como o arquivo de config está na raiz do gestor, vamos para a pasta /db.
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'gestor', // Usaremos este como nosso único ambiente
        'gestor' => [
            'adapter' => 'mysql',
            'host'    => $dbConfig['host'],
            'name'    => $dbConfig['name'],
            'user'    => $dbConfig['user'],
            'pass'    => $dbConfig['pass'],
            'port'    => 3306,
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];