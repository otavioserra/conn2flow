<?php

/**
 * Script para gerar um arquivo SQL a partir das migrações do Phinx
 * Usado como fallback quando o Composer/Phinx não está disponível no servidor
 */

require_once __DIR__ . '/../config.php';

// Configurações
$outputFile = __DIR__ . '/../db/conn2flow-schema.sql';
$migrationsDir = __DIR__ . '/../db/migrations/';
$seedsDir = __DIR__ . '/../db/seeds/';

echo "🔄 Gerando arquivo SQL a partir das migrações...\n";

// Inicia o buffer de saída
$sqlContent = [];
$sqlContent[] = "-- Conn2Flow Database Schema";
$sqlContent[] = "-- Generated from Phinx migrations on " . date('Y-m-d H:i:s');
$sqlContent[] = "-- This file is used as fallback when Composer/Phinx is not available";
$sqlContent[] = "";
$sqlContent[] = "SET FOREIGN_KEY_CHECKS = 0;";
$sqlContent[] = "";

// Processa todas as migrações em ordem
$migrationFiles = glob($migrationsDir . '*.php');
sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile, '.php');
    echo "📄 Processando: $migrationName\n";
    
    // Simula a execução da migração para extrair SQL
    $migrationSql = extractSqlFromMigration($migrationFile);
    
    if (!empty($migrationSql)) {
        $sqlContent[] = "-- Migration: $migrationName";
        $sqlContent[] = $migrationSql;
        $sqlContent[] = "";
    }
}

// Adiciona tabela de controle do Phinx
$sqlContent[] = "-- Phinx migration table";
$sqlContent[] = "CREATE TABLE IF NOT EXISTS `phinxlog` (";
$sqlContent[] = "  `version` bigint(20) NOT NULL,";
$sqlContent[] = "  `migration_name` varchar(100) DEFAULT NULL,";
$sqlContent[] = "  `start_time` timestamp NULL DEFAULT NULL,";
$sqlContent[] = "  `end_time` timestamp NULL DEFAULT NULL,";
$sqlContent[] = "  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',";
$sqlContent[] = "  PRIMARY KEY (`version`)";
$sqlContent[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$sqlContent[] = "";

// Adiciona dados dos seeders
echo "🌱 Processando seeders...\n";
$seedFiles = glob($seedsDir . '*.php');
sort($seedFiles);

foreach ($seedFiles as $seedFile) {
    $seederName = basename($seedFile, '.php');
    echo "📊 Processando seeder: $seederName\n";
    
    $seederSql = extractSqlFromSeeder($seedFile);
    
    if (!empty($seederSql)) {
        $sqlContent[] = "-- Seeder: $seederName";
        $sqlContent[] = $seederSql;
        $sqlContent[] = "";
    }
}

$sqlContent[] = "SET FOREIGN_KEY_CHECKS = 1;";

// Salva o arquivo
$finalSql = implode("\n", $sqlContent);
file_put_contents($outputFile, $finalSql);

echo "✅ Arquivo SQL gerado com sucesso: $outputFile\n";
echo "📊 Tamanho: " . number_format(strlen($finalSql)) . " bytes\n";

/**
 * Extrai SQL de um arquivo de migração
 */
function extractSqlFromMigration($file) {
    $content = file_get_contents($file);
    
    // Usa regex para extrair comandos CREATE TABLE mais comuns
    if (preg_match('/public function up\(\).*?\{(.*?)\}/s', $content, $matches)) {
        $upMethod = $matches[1];
        
        // Procura por padrões de criação de tabela
        if (preg_match_all('/\$table\s*=\s*\$this->table\([\'\"](.*?)[\'\"]\);(.*?)(?=\$table\s*=|\$this->|$)/s', $upMethod, $tableMatches, PREG_SET_ORDER)) {
            $sql = [];
            
            foreach ($tableMatches as $match) {
                $tableName = $match[1];
                $tableDefinition = $match[2];
                
                $sql[] = generateCreateTableSql($tableName, $tableDefinition);
            }
            
            return implode("\n", $sql);
        }
    }
    
    return '';
}

/**
 * Gera SQL CREATE TABLE a partir da definição do Phinx
 */
function generateCreateTableSql($tableName, $definition) {
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
    $columns = [];
    $indexes = [];
    
    // Padrões básicos de colunas do Phinx
    $patterns = [
        '/addColumn\([\'\"](.*?)[\'\"],\s*[\'\"](.*?)[\'\"](?:,\s*\[(.*?)\])?\)/' => 'column',
        '/addPrimaryKey\([\'\"](.*?)[\'\"]\)/' => 'primary',
        '/addIndex\([\'\"](.*?)[\'\"](?:,\s*\[(.*?)\])?\)/' => 'index',
        '/addForeignKey\([\'\"](.*?)[\'\"],\s*[\'\"](.*?)[\'\"],\s*[\'\"](.*?)[\'\"]\)/' => 'foreign'
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (preg_match_all($pattern, $definition, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                switch ($type) {
                    case 'column':
                        $columnName = $match[1];
                        $columnType = $match[2];
                        $options = isset($match[3]) ? $match[3] : '';
                        
                        $mysqlType = convertPhinxTypeToMysql($columnType, $options);
                        $columns[] = "  `$columnName` $mysqlType";
                        break;
                        
                    case 'primary':
                        $indexes[] = "  PRIMARY KEY (`{$match[1]}`)";
                        break;
                        
                    case 'index':
                        $indexName = "idx_{$tableName}_{$match[1]}";
                        $indexes[] = "  KEY `$indexName` (`{$match[1]}`)";
                        break;
                }
            }
        }
    }
    
    // Se não encontrou colunas, cria uma estrutura básica
    if (empty($columns)) {
        $columns = [
            "  `id` int(11) NOT NULL AUTO_INCREMENT",
            "  `created_at` timestamp NULL DEFAULT NULL",
            "  `updated_at` timestamp NULL DEFAULT NULL"
        ];
        $indexes = ["  PRIMARY KEY (`id`)"];
    }
    
    $sql .= implode(",\n", array_merge($columns, $indexes));
    $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    
    return $sql;
}

/**
 * Converte tipos do Phinx para MySQL
 */
function convertPhinxTypeToMysql($type, $options = '') {
    $typeMap = [
        'integer' => 'int(11)',
        'biginteger' => 'bigint(20)',
        'string' => 'varchar(255)',
        'text' => 'text',
        'datetime' => 'datetime',
        'timestamp' => 'timestamp',
        'boolean' => 'tinyint(1)',
        'decimal' => 'decimal(10,2)',
        'float' => 'float',
        'binary' => 'blob'
    ];
    
    $mysqlType = $typeMap[$type] ?? 'varchar(255)';
    
    // Adiciona modificadores baseados nas opções
    if (strpos($options, "'null' => false") !== false) {
        $mysqlType .= ' NOT NULL';
    }
    
    if (strpos($options, "'default'") !== false) {
        if (preg_match("/'default'\s*=>\s*'(.*?)'/", $options, $matches)) {
            $mysqlType .= " DEFAULT '{$matches[1]}'";
        }
    }
    
    if (strpos($options, "'identity' => true") !== false || strpos($options, "'auto_increment' => true") !== false) {
        $mysqlType .= ' AUTO_INCREMENT';
    }
    
    return $mysqlType;
}

/**
 * Extrai SQL de um arquivo de seeder
 */
function extractSqlFromSeeder($file) {
    // Para seeders, retorna comentário indicando que deve ser processado manualmente
    $seederName = basename($file, '.php');
    return "-- Seeder $seederName should be executed manually or via Phinx when available";
}

?>
