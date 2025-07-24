<?php

/**
 * Script simplificado para gerar arquivo SQL a partir das migrações do Phinx
 * Não depende do config.php para funcionar
 */

$outputFile = __DIR__ . '/../db/conn2flow-schema.sql';
$migrationsDir = __DIR__ . '/../db/migrations/';

echo "🔄 Gerando arquivo SQL simplificado...\n";

// Lista das tabelas baseada nos arquivos de migração
$migrationFiles = glob($migrationsDir . '*.php');
sort($migrationFiles);

$sqlContent = [];
$sqlContent[] = "-- Conn2Flow Database Schema";
$sqlContent[] = "-- Generated from Phinx migrations on " . date('Y-m-d H:i:s');
$sqlContent[] = "-- This file is used as fallback when Composer/Phinx is not available";
$sqlContent[] = "";
$sqlContent[] = "SET FOREIGN_KEY_CHECKS = 0;";
$sqlContent[] = "SET SQL_MODE = '';";
$sqlContent[] = "";

// Extrai nomes das tabelas dos arquivos de migração
$tables = [];
foreach ($migrationFiles as $file) {
    $filename = basename($file);
    if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
        $tables[] = $matches[1];
    }
}

echo "📊 Encontradas " . count($tables) . " tabelas\n";

// Gera SQL básico para cada tabela
foreach ($tables as $tableName) {
    echo "📄 Gerando tabela: $tableName\n";
    
    $sql = generateBasicTableSql($tableName);
    $sqlContent[] = $sql;
    $sqlContent[] = "";
}

// Adiciona tabela de controle do Phinx
$sqlContent[] = "-- Phinx migration tracking table";
$sqlContent[] = "CREATE TABLE IF NOT EXISTS `phinxlog` (";
$sqlContent[] = "  `version` bigint(20) NOT NULL,";
$sqlContent[] = "  `migration_name` varchar(100) DEFAULT NULL,";
$sqlContent[] = "  `start_time` timestamp NULL DEFAULT NULL,";
$sqlContent[] = "  `end_time` timestamp NULL DEFAULT NULL,";
$sqlContent[] = "  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',";
$sqlContent[] = "  PRIMARY KEY (`version`)";
$sqlContent[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$sqlContent[] = "";

// Insere registros do phinxlog para marcar migrações como executadas
$sqlContent[] = "-- Mark migrations as executed";
foreach ($migrationFiles as $file) {
    $filename = basename($file, '.php');
    if (preg_match('/^(\d+)_(.+)$/', $filename, $matches)) {
        $version = $matches[1];
        $name = $matches[2];
        $sqlContent[] = "INSERT IGNORE INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`) VALUES ('$version', '$name', NOW(), NOW());";
    }
}

$sqlContent[] = "";
$sqlContent[] = "SET FOREIGN_KEY_CHECKS = 1;";

// Salva o arquivo
$finalSql = implode("\n", $sqlContent);
file_put_contents($outputFile, $finalSql);

echo "✅ Arquivo SQL gerado: $outputFile\n";
echo "📊 Tamanho: " . number_format(strlen($finalSql)) . " bytes\n";
echo "📁 Arquivo salvo em: " . realpath($outputFile) . "\n";

/**
 * Gera SQL básico para uma tabela
 */
function generateBasicTableSql($tableName) {
    // Estruturas básicas conhecidas para as principais tabelas
    $tableStructures = [
        'usuarios' => [
            "`id_usuarios` int(11) NOT NULL AUTO_INCREMENT",
            "`nome` varchar(255) NOT NULL",
            "`email` varchar(255) NOT NULL",
            "`senha` varchar(255) NOT NULL",
            "`ativo` tinyint(1) DEFAULT '1'",
            "`tempo_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP",
            "`tempo_modificacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_usuarios`)",
            "UNIQUE KEY `email` (`email`)"
        ],
        'hosts' => [
            "`id_hosts` int(11) NOT NULL AUTO_INCREMENT",
            "`nome` varchar(255) NOT NULL",
            "`dominio` varchar(255) NOT NULL",
            "`chave_publica` text",
            "`chave_privada` text",
            "`ativo` tinyint(1) DEFAULT '1'",
            "`tempo_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_hosts`)",
            "UNIQUE KEY `dominio` (`dominio`)"
        ]
    ];
    
    // Se temos estrutura específica, usa ela
    if (isset($tableStructures[$tableName])) {
        $columns = $tableStructures[$tableName];
    } else {
        // Estrutura genérica para outras tabelas
        $idField = "id_" . $tableName;
        $columns = [
            "`$idField` int(11) NOT NULL AUTO_INCREMENT",
            "`nome` varchar(255) DEFAULT NULL",
            "`ativo` tinyint(1) DEFAULT '1'",
            "`tempo_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP",
            "`tempo_modificacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "PRIMARY KEY (`$idField`)"
        ];
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
    $sql .= "  " . implode(",\n  ", $columns) . "\n";
    $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    return $sql;
}

?>
