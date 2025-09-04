<?php
/**
 * Script para análise de dados ausentes na migração JSON
 * Compara dados originais dos arquivos SQL com os dados atuais em JSON
 */

echo "=== ANALISANDO DADOS AUSENTES NA MIGRAÇÃO JSON ===\n\n";

// Caminhos dos arquivos
$basePath = realpath(__DIR__ . '/../../../'); // raiz do repositório (dinâmico)
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
 * Extrai dados do arquivo SQL
 */
function extractSqlData($filePath, $tableName) {
    if (!file_exists($filePath)) {
        echo "ERRO: Arquivo SQL não encontrado: $filePath\n";
        return [];
    }
    
    $content = file_get_contents($filePath);
    $data = [];
    
    // Padrão para extrair INSERT statements com múltiplos VALUES
    $pattern = '/INSERT INTO `' . $tableName . '`[^V]*VALUES\s*(.*?);/s';
    
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[1] as $valuesBlock) {
            // Divide por blocos de valores (...), (...), ...
            $valueGroups = [];
            $current = '';
            $level = 0;
            $inString = false;
            $escape = false;
            
            for ($i = 0; $i < strlen($valuesBlock); $i++) {
                $char = $valuesBlock[$i];
                
                if ($escape) {
                    $current .= $char;
                    $escape = false;
                    continue;
                }
                
                if ($char === '\\') {
                    $escape = true;
                    $current .= $char;
                    continue;
                }
                
                if ($char === "'" && !$escape) {
                    $inString = !$inString;
                }
                
                if (!$inString) {
                    if ($char === '(') {
                        $level++;
                        if ($level === 1) {
                            $current = '';
                            continue;
                        }
                    } elseif ($char === ')') {
                        $level--;
                        if ($level === 0) {
                            $valueGroups[] = $current;
                            $current = '';
                            continue;
                        }
                    }
                }
                
                $current .= $char;
            }
            
            // Processa cada grupo de valores
            foreach ($valueGroups as $group) {
                $values = parseValuesString($group);
                if (!empty($values)) {
                    $data[] = $values;
                }
            }
        }
    }
    
    return $data;
}

/**
 * Analisa string de valores SQL
 */
function parseValuesString($valuesStr) {
    $values = [];
    $current = '';
    $inString = false;
    $escape = false;
    
    for ($i = 0; $i < strlen($valuesStr); $i++) {
        $char = $valuesStr[$i];
        
        if ($escape) {
            $current .= $char;
            $escape = false;
            continue;
        }
        
        if ($char === '\\') {
            $escape = true;
            $current .= $char;
            continue;
        }
        
        if ($char === "'") {
            $inString = !$inString;
            $current .= $char;
            continue;
        }
        
        if ($char === ',' && !$inString) {
            $values[] = cleanSqlValue(trim($current));
            $current = '';
            continue;
        }
        
        $current .= $char;
    }
    
    if ($current !== '') {
        $values[] = cleanSqlValue(trim($current));
    }
    
    return $values;
}

/**
 * Limpa valor SQL
 */
function cleanSqlValue($val) {
    $val = trim($val);
    if (($val[0] ?? '') === "'" && ($val[strlen($val)-1] ?? '') === "'") {
        return substr($val, 1, -1);
    }
    return $val === 'NULL' ? null : $val;
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
    $sqlData = extractSqlData($sqlFile, $type);
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
    
    foreach ($sqlData as $row) {
        // Assume que o ID está na 4ª posição (índice 3) baseado na estrutura SQL
        $sqlId = $row[3] ?? null; // Campo 'id' 
        $status = $row[12] ?? 'A'; // Campo 'status'
        
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

file_put_contents($basePath . '/missing_data_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Relatório detalhado salvo em: missing_data_report.json\n";

echo "Análise concluída!\n";
?>
