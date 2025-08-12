<?php
/**
 * Script melhorado para análise de dados ausentes na migração JSON
 * Compara dados originais dos arquivos SQL com os dados atuais em JSON
 */

echo "=== ANALISANDO DADOS AUSENTES NA MIGRAÇÃO JSON (V2) ===\n\n";

// Caminhos dos arquivos
$basePath = "c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow";
$sqlPath = "$basePath/gestor/db/old";
$resourcesPath = "$basePath/gestor/resources/pt-br";

// Arquivos SQL para análise
$sqlFiles = [
    'paginas' => "$sqlPath/paginas.sql",
    'layouts' => "$sqlPath/layouts.sql", 
    'componentes' => "$sqlPath/componentes.sql"
];

// Arquivos JSON correspondentes
$jsonFiles = [
    'paginas' => "$resourcesPath/pages.json",
    'layouts' => "$resourcesPath/layouts.json",
    'componentes' => "$resourcesPath/components.json"
];

// Pastas de recursos
$resourceFolders = [
    'paginas' => "$resourcesPath/pages",
    'layouts' => "$resourcesPath/layouts", 
    'componentes' => "$resourcesPath/components"
];

/**
 * Extrai dados do arquivo SQL usando método mais simples
 */
function extractSqlDataSimple($filePath, $tableName) {
    if (!file_exists($filePath)) {
        echo "ERRO: Arquivo SQL não encontrado: $filePath\n";
        return [];
    }
    
    $content = file_get_contents($filePath);
    $data = [];
    
    // Remove quebras de linha dentro de strings SQL
    $content = preg_replace('/\r?\n/', ' ', $content);
    
    // Encontra posições de cada registro começando com número
    preg_match_all('/\((\d+),\s*([^)]+)\)(?=,\s*\(|\s*;)/', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $fullMatch = $match[0];
        // Remove parênteses externos
        $values = substr($fullMatch, 1, -1);
        
        // Separa os valores considerando strings e escape
        $parts = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $level = 0;
        
        for ($i = 0; $i < strlen($values); $i++) {
            $char = $values[$i];
            $prev = $i > 0 ? $values[$i-1] : '';
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && $prev !== '\\') {
                $inString = false;
                $current .= $char;
            } elseif (!$inString && $char === '(') {
                $level++;
                $current .= $char;
            } elseif (!$inString && $char === ')') {
                $level--;
                $current .= $char;
            } elseif (!$inString && $char === ',' && $level === 0) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $parts[] = trim($current);
        }
        
        // Limpa os valores
        $cleanParts = array_map(function($val) {
            $val = trim($val);
            if ($val === 'NULL') return null;
            if (preg_match('/^[\'"](.*)[\'"]\s*$/s', $val, $m)) {
                return $m[1];
            }
            return $val;
        }, $parts);
        
        $data[] = $cleanParts;
    }
    
    return $data;
}

/**
 * Carrega dados JSON
 */
function loadJsonData($filePath) {
    if (!file_exists($filePath)) {
        echo "AVISO: Arquivo JSON não encontrado: $filePath\n";
        return [];
    }
    
    $content = file_get_contents($filePath);
    return json_decode($content, true) ?: [];
}

/**
 * Mapeia campos SQL para campos JSON
 */
function getFieldMapping($type) {
    switch ($type) {
        case 'paginas':
            return [
                'id_paginas' => 'id_record',
                'id_usuarios' => 'user_id', 
                'id_layouts' => 'layout',
                'nome' => 'name',
                'id' => 'id',
                'caminho' => 'path',
                'tipo' => 'type',
                'modulo' => 'module',
                'opcao' => 'option',
                'raiz' => 'root',
                'sem_permissao' => 'without_permission',
                'html' => 'html',
                'css' => 'css',
                'status' => 'status',
                'versao' => 'version'
            ];
        case 'layouts':
            return [
                'id_layouts' => 'id_record',
                'id_usuarios' => 'user_id',
                'nome' => 'name', 
                'id' => 'id',
                'html' => 'html',
                'css' => 'css',
                'status' => 'status',
                'versao' => 'version'
            ];
        case 'componentes':
            return [
                'id_componentes' => 'id_record',
                'id_usuarios' => 'user_id',
                'nome' => 'name',
                'id' => 'id', 
                'modulo' => 'module',
                'html' => 'html',
                'css' => 'css',
                'status' => 'status',
                'versao' => 'version'
            ];
        default:
            return [];
    }
}

/**
 * Converte valores do SQL para JSON
 */
function convertValue($value, $field) {
    if ($value === null || $value === 'NULL') {
        return null;
    }
    
    // Conversões específicas
    switch ($field) {
        case 'tipo':
            return $value === 'sistema' ? 'system' : 'page';
        case 'status':
            return $value === 'A' ? 'active' : 'inactive';
        case 'raiz':
        case 'sem_permissao':
            return $value === '1' || $value === 1;
        case 'versao':
            return floatval($value) ?: 1.0;
        default:
            return $value;
    }
}

// Análise principal
$results = [];

foreach ($sqlFiles as $type => $sqlFile) {
    echo "=== ANALISANDO $type ===\n";
    
    // Extrai dados do SQL
    $sqlData = extractSqlDataSimple($sqlFile, $type);
    echo "Registros no SQL: " . count($sqlData) . "\n";
    
    // Carrega dados JSON
    $jsonData = loadJsonData($jsonFiles[$type]);
    echo "Registros no JSON: " . count($jsonData) . "\n";
    
    // Cria índice de IDs no JSON
    $jsonIds = [];
    foreach ($jsonData as $item) {
        if (isset($item['id'])) {
            $jsonIds[$item['id']] = true;
        }
    }
    
    // Verifica arquivos na pasta de recursos
    $resourceFiles = [];
    if (is_dir($resourceFolders[$type])) {
        $files = scandir($resourceFolders[$type]);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_dir($resourceFolders[$type] . '/' . $file)) {
                $resourceFiles[$file] = true;
            }
        }
    }
    echo "Pastas de recursos: " . count($resourceFiles) . "\n";
    
    // Identifica dados ausentes
    $missing = [];
    $fieldMapping = getFieldMapping($type);
    $fieldKeys = array_keys($fieldMapping);
    
    foreach ($sqlData as $row) {
        // Encontra a posição do campo 'id' e 'status'
        $idIndex = array_search('id', $fieldKeys);
        $statusIndex = array_search('status', $fieldKeys);
        
        $sqlId = $row[$idIndex] ?? null;
        $status = $row[$statusIndex] ?? 'A';
        
        // Só considera registros ativos
        if ($status !== 'A') {
            continue;
        }
        
        if ($sqlId && !isset($jsonIds[$sqlId])) {
            // Converte dados SQL para formato JSON
            $convertedData = [];
            $i = 0;
            foreach ($fieldMapping as $sqlField => $jsonField) {
                $value = $row[$i] ?? null;
                $convertedData[$jsonField] = convertValue($value, $sqlField);
                $i++;
            }
            
            $missing[] = [
                'sql_data' => $row,
                'converted_data' => $convertedData,
                'has_resource_folder' => isset($resourceFiles[$sqlId])
            ];
        }
    }
    
    echo "Dados ausentes: " . count($missing) . "\n";
    
    if (!empty($missing)) {
        echo "\nDados ausentes encontrados:\n";
        foreach ($missing as $item) {
            $data = $item['converted_data'];
            $hasFolder = $item['has_resource_folder'] ? 'SIM' : 'NÃO';
            echo "- ID: {$data['id']} | Nome: {$data['name']} | Pasta: $hasFolder\n";
        }
    }
    
    $results[$type] = $missing;
    echo "\n";
}

// Resumo final
echo "=== RESUMO FINAL ===\n";
$totalMissing = 0;
foreach ($results as $type => $missing) {
    $count = count($missing);
    $totalMissing += $count;
    echo "$type: $count registros ausentes\n";
}
echo "Total geral: $totalMissing registros ausentes\n\n";

// Salva relatório detalhado
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_missing' => $totalMissing,
        'by_type' => []
    ],
    'details' => $results
];

foreach ($results as $type => $missing) {
    $report['summary']['by_type'][$type] = count($missing);
}

file_put_contents("$basePath/missing_data_report_v2.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Relatório detalhado salvo em: missing_data_report_v2.json\n";

echo "Análise concluída!\n";
?>
