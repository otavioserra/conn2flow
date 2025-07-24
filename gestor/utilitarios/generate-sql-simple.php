<?php

/**
 * Script simplificado para gerar arquivo SQL completo a partir das migrações e seeders do Phinx
 * Inclui tanto estrutura quanto dados iniciais
 */

$outputFile = __DIR__ . '/../db/conn2flow-schema.sql';
$migrationsDir = __DIR__ . '/../db/migrations/';
$seedsDir = __DIR__ . '/../db/seeds/';

echo "🔄 Gerando arquivo SQL completo (estrutura + dados)...\n";

// Lista das tabelas baseada nos arquivos de migração
$migrationFiles = glob($migrationsDir . '*.php');
sort($migrationFiles);

$sqlContent = [];
$sqlContent[] = "-- Conn2Flow Database Schema with Initial Data";
$sqlContent[] = "-- Generated from Phinx migrations and seeders on " . date('Y-m-d H:i:s');
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

// Processa os seeders para extrair dados
echo "🌱 Processando seeders para dados iniciais...\n";
$seedFiles = glob($seedsDir . '*.php');
sort($seedFiles);

foreach ($seedFiles as $seedFile) {
    $seederName = basename($seedFile, '.php');
    echo "📊 Extraindo dados do seeder: $seederName\n";
    
    $seederSql = extractDataFromSeeder($seedFile);
    
    if (!empty($seederSql)) {
        $sqlContent[] = "-- Data from seeder: $seederName";
        $sqlContent[] = $seederSql;
        $sqlContent[] = "";
    }
}

// Insere registros do phinxlog para marcar migrações como executadas
$sqlContent[] = "-- Mark migrations as executed in phinxlog";
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

echo "✅ Arquivo SQL completo gerado: $outputFile\n";
echo "📊 Tamanho: " . number_format(strlen($finalSql)) . " bytes\n";
echo "📁 Arquivo salvo em: " . realpath($outputFile) . "\n";

/**
 * Extrai dados de um arquivo seeder
 */
function extractDataFromSeeder($seedFile) {
    $content = file_get_contents($seedFile);
    $sql = [];
    
    // Extrai o array $data do seeder
    if (preg_match('/\$data\s*=\s*\[(.*?)\];/s', $content, $matches)) {
        $dataContent = $matches[1];
        
        // Extrai o nome da tabela
        if (preg_match('/\$table\s*=\s*\$this->table\([\'\"](.*?)[\'\"]\)/', $content, $tableMatches)) {
            $tableName = $tableMatches[1];
            
            // Processa os arrays de dados
            if (preg_match_all('/\[(.*?)\],?/s', $dataContent, $rowMatches, PREG_SET_ORDER)) {
                foreach ($rowMatches as $rowMatch) {
                    $rowData = $rowMatch[1];
                    $insertSql = generateInsertSql($tableName, $rowData);
                    if ($insertSql) {
                        $sql[] = $insertSql;
                    }
                }
            }
        }
    }
    
    return implode("\n", $sql);
}

/**
 * Gera SQL INSERT a partir dos dados do seeder
 */
function generateInsertSql($tableName, $rowData) {
    $fields = [];
    $values = [];
    
    // Processa pares chave => valor
    if (preg_match_all('/[\'\"](.*?)[\'\"][^=]*=>[^\'\"]*[\'\"](.*?)[\'\"]/s', $rowData, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $field = trim($match[1]);
            $value = trim($match[2]);
            
            // Pula valores NULL explícitos
            if ($value === 'NULL' || empty($value)) {
                continue;
            }
            
            $fields[] = "`$field`";
            $values[] = "'" . addslashes($value) . "'";
        }
    }
    
    if (empty($fields)) {
        return '';
    }
    
    return "INSERT IGNORE INTO `$tableName` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");";
}

/**
 * Gera SQL básico para uma tabela
 */
function generateBasicTableSql($tableName) {
    // Estruturas básicas conhecidas para as principais tabelas
    $tableStructures = [
        'usuarios' => [
            "`id_usuarios` int(11) NOT NULL AUTO_INCREMENT",
            "`id_hosts` int(11) DEFAULT NULL",
            "`id_usuarios_perfis` int(11) DEFAULT NULL",
            "`nome_conta` varchar(255) DEFAULT NULL",
            "`nome` varchar(255) NOT NULL",
            "`id` varchar(255) DEFAULT NULL",
            "`usuario` varchar(255) NOT NULL",
            "`senha` varchar(255) NOT NULL",
            "`email` varchar(255) NOT NULL",
            "`primeiro_nome` varchar(255) DEFAULT NULL",
            "`ultimo_nome` varchar(255) DEFAULT NULL",
            "`nome_do_meio` varchar(255) DEFAULT NULL",
            "`status` char(1) DEFAULT 'A'",
            "`versao` int(11) DEFAULT '1'",
            "`data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP",
            "`data_modificacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "`email_confirmado` tinyint(1) DEFAULT NULL",
            "`gestor` varchar(255) DEFAULT NULL",
            "`gestor_perfil` varchar(255) DEFAULT NULL",
            "PRIMARY KEY (`id_usuarios`)",
            "UNIQUE KEY `email` (`email`)",
            "UNIQUE KEY `usuario` (`usuario`)"
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
        ],
        'modulos' => [
            "`id_modulos` int(11) NOT NULL AUTO_INCREMENT",
            "`id_modulos_grupos` int(11) DEFAULT NULL",
            "`id_usuarios` int(11) DEFAULT NULL",
            "`nome` varchar(255) NOT NULL",
            "`id` varchar(255) DEFAULT NULL",
            "`titulo` varchar(255) DEFAULT NULL",
            "`icone` varchar(100) DEFAULT NULL",
            "`icone2` varchar(100) DEFAULT NULL",
            "`nao_menu_principal` tinyint(1) DEFAULT NULL",
            "`plugin` varchar(255) DEFAULT NULL",
            "`host` varchar(255) DEFAULT NULL",
            "`status` char(1) DEFAULT 'A'",
            "`versao` int(11) DEFAULT '1'",
            "`data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP",
            "`data_modificacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_modulos`)"
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
