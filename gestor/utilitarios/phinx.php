<?php

// Carrega a configuração principal da sua aplicação para ter acesso às credenciais do banco.
// O caminho sobe um nível ('../') para encontrar o config.php na raiz do gestor.
require_once __DIR__ . '/../config.php';

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
            'adapter' => $_ENV['DB_ADAPTER'] ?? 'mysql', // Usa a variável de ambiente específica para o Phinx
            'host'    => $_BANCO['host'] ?? 'localhost',
            'name'    => $_BANCO['nome'] ?? '',
            'user'    => $_BANCO['usuario'] ?? '',
            'pass'    => $_BANCO['senha'] ?? '',
            'port'    => 3306,
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];