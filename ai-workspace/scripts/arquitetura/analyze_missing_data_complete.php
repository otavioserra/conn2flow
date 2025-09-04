<?php
/**
 * Script corrigido para análise de dados ausentes considerando recursos globais E de módulos
 * Compara dados originais dos arquivos SQL com os dados atuais em JSON (globais e módulos)
 */

echo "=== ANALISANDO DADOS AUSENTES: GLOBAIS E MÓDULOS ===\n\n";

// Caminhos dos arquivos
$basePath = realpath(__DIR__ . '/../../../');
$sqlPath = "$basePath/gestor/db/old";
$resourcesPath = "$basePath/gestor/resources/pt-br";
$modulesPath = "$basePath/gestor/modulos";

// Arquivos SQL para análise
$sqlFiles = [
    'paginas' => "$sqlPath/paginas.sql",
    'layouts' => "$sqlPath/layouts.sql", 
    'componentes' => "$sqlPath/componentes.sql"
];

// Mapeamento de tipos SQL para nomes de recursos
$resourceMapping = [
    'paginas' => 'pages',
    'layouts' => 'layouts',
    'componentes' => 'components'
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
    
    // Remove quebras de linha dentro de strings SQL
    $content = preg_replace('/\r?\n/', ' ', $content);
    
    // Encontra registros SQL
    preg_match_all('/\((\d+),\s*([^)]+)\)(?=,\s*\(|\s*;)/', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $fullMatch = $match[0];
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
 * Mapeia campos SQL para indices
 */
function getFieldIndices($type) {
    switch ($type) {
        case 'paginas':
            return [
                'id_record' => 0,     // id_paginas
                'user_id' => 1,       // id_usuarios
                'layout_id' => 2,     // id_layouts
                'name' => 3,          // nome
                'id' => 4,            // id
                'path' => 5,          // caminho
                'type' => 6,          // tipo
                'module' => 7,        // modulo
                'option' => 8,        // opcao
                'root' => 9,          // raiz
                'without_permission' => 10, // sem_permissao
                'html' => 11,         // html
                'css' => 12,          // css
                'status' => 13,       // status
                'version' => 14       // versao
            ];
        case 'layouts':
            return [
                'id_record' => 0,     // id_layouts
                'user_id' => 1,       // id_usuarios
                'name' => 2,          // nome
                'id' => 3,            // id
                'html' => 4,          // html
                'css' => 5,           // css
                'status' => 6,        // status
                'version' => 7        // versao
            ];
        case 'componentes':
            return [
                'id_record' => 0,     // id_componentes
                'user_id' => 1,       // id_usuarios
                'name' => 2,          // nome
                'id' => 3,            // id
                'module' => 4,        // modulo
                'html' => 5,          // html
                'css' => 6,           // css
                'status' => 7,        // status
                'version' => 8        // versao
            ];
        default:
            return [];
    }
}

/**
 * Carrega dados JSON global
 */
function loadGlobalJsonData($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $content = file_get_contents($filePath);
    return json_decode($content, true) ?: [];
}

/**
 * Carrega dados JSON de módulos
 */
function loadModuleJsonData($modulesPath, $resourceType) {
    $moduleData = [];
    
    if (!is_dir($modulesPath)) {
        return $moduleData;
    }
    
    $modules = scandir($modulesPath);
    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        
        $moduleDir = "$modulesPath/$module";
        if (!is_dir($moduleDir)) continue;
        
        $jsonFile = "$moduleDir/$module.json";
        if (file_exists($jsonFile)) {
            $content = file_get_contents($jsonFile);
            $data = json_decode($content, true);
            
            if (isset($data['resources']['pt-br'][$resourceType])) {
                foreach ($data['resources']['pt-br'][$resourceType] as $item) {
                    if (isset($item['id'])) {
                        $moduleData[$item['id']] = $module;
                    }
                }
            }
        }
    }
    
    return $moduleData;
}

/**
 * Verifica se existem pastas de recursos
 */
function checkResourceFolders($basePath, $resourceType, $resourceId) {
    $globalPath = "$basePath/gestor/resources/pt-br/$resourceType/$resourceId";
    $hasGlobal = is_dir($globalPath);
    
    $moduleFolders = [];
    $modulesPath = "$basePath/gestor/modulos";
    
    if (is_dir($modulesPath)) {
        $modules = scandir($modulesPath);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;
            
            $modulePath = "$modulesPath/$module/resources/pt-br/$resourceType/$resourceId";
            if (is_dir($modulePath)) {
                $moduleFolders[] = $module;
            }
        }
    }
    
    return [
        'global' => $hasGlobal,
        'modules' => $moduleFolders
    ];
}

/**
 * Busca layout por ID numérico
 */
function findLayoutById($layoutsData, $numericId) {
    foreach ($layoutsData as $layout) {
        if ($layout[0] == $numericId) { // id_layouts
            return $layout[3]; // id
        }
    }
    return null;
}

// Carrega dados de layouts para referência
$layoutsData = extractSqlData($sqlFiles['layouts'], 'layouts');

// Análise principal
$results = [];

foreach ($sqlFiles as $type => $sqlFile) {
    echo "=== ANALISANDO $type ===\n";
    
    $resourceType = $resourceMapping[$type];
    
    // Extrai dados do SQL
    $sqlData = extractSqlData($sqlFile, $type);
    echo "Registros no SQL: " . count($sqlData) . "\n";
    
    // Carrega dados JSON globais
    $globalJsonFile = "$resourcesPath/$resourceType.json";
    $globalData = loadGlobalJsonData($globalJsonFile);
    echo "Registros globais no JSON: " . count($globalData) . "\n";
    
    // Carrega dados JSON de módulos
    $moduleData = loadModuleJsonData($modulesPath, $resourceType);
    echo "Registros de módulos no JSON: " . count($moduleData) . "\n";
    
    // Cria índice de IDs existentes
    $existingIds = [];
    
    // IDs globais
    foreach ($globalData as $item) {
        if (isset($item['id'])) {
            $existingIds[$item['id']] = 'global';
        }
    }
    
    // IDs de módulos
    foreach ($moduleData as $id => $module) {
        $existingIds[$id] = "module:$module";
    }
    
    // Identifica dados ausentes
    $missing = [];
    $fieldIndices = getFieldIndices($type);
    
    foreach ($sqlData as $row) {
        $resourceId = $row[$fieldIndices['id']] ?? null;
        $status = $row[$fieldIndices['status']] ?? 'A';
        $moduleField = $fieldIndices['module'] ?? null;
        $module = $moduleField !== null ? $row[$moduleField] : null;
        
        // Só considera registros ativos
        if ($status !== 'A') {
            continue;
        }
        
        if ($resourceId && !isset($existingIds[$resourceId])) {
            // Verifica pastas de recursos
            $folders = checkResourceFolders($basePath, $resourceType, $resourceId);
            
            // Prepara dados convertidos
            $convertedData = [
                'name' => $row[$fieldIndices['name']] ?? '',
                'id' => $resourceId,
                'version' => '1.0',
                'checksum' => [
                    'html' => '',
                    'css' => '',
                    'combined' => ''
                ]
            ];
            
            // Campos específicos por tipo
            if ($type === 'paginas') {
                $layoutId = $row[$fieldIndices['layout_id']] ?? null;
                $layoutName = findLayoutById($layoutsData, $layoutId);
                
                $convertedData['layout'] = $layoutName ?: '';
                $convertedData['path'] = $row[$fieldIndices['path']] ?? '';
                $convertedData['type'] = ($row[$fieldIndices['type']] === 'sistema') ? 'system' : 'page';
                
                if (!empty($row[$fieldIndices['option']])) {
                    $convertedData['option'] = $row[$fieldIndices['option']];
                }
                
                if ($row[$fieldIndices['root']] == '1') {
                    $convertedData['root'] = true;
                }
                
                if ($row[$fieldIndices['without_permission']] == '1') {
                    $convertedData['without_permission'] = true;
                }
            }
            
            $missing[] = [
                'sql_data' => $row,
                'converted_data' => $convertedData,
                'module' => $module,
                'expected_location' => $module ? "module:$module" : 'global',
                'folders' => $folders,
                'html_content' => $row[$fieldIndices['html']] ?? null,
                'css_content' => $row[$fieldIndices['css']] ?? null
            ];
        }
    }
    
    echo "Dados ausentes: " . count($missing) . "\n";
    
    if (!empty($missing)) {
        echo "\nDados ausentes encontrados:\n";
        $globalMissing = 0;
        $moduleMissing = 0;
        
        foreach ($missing as $item) {
            $data = $item['converted_data'];
            $location = $item['expected_location'];
            $folders = $item['folders'];
            
            $folderStatus = [];
            if ($folders['global']) $folderStatus[] = 'Global:SIM';
            if (!empty($folders['modules'])) $folderStatus[] = 'Módulos:' . implode(',', $folders['modules']);
            if (empty($folderStatus)) $folderStatus[] = 'Pastas:NÃO';
            
            echo "- ID: {$data['id']} | Nome: {$data['name']} | Local: $location | " . implode(' | ', $folderStatus) . "\n";
            
            if ($location === 'global') {
                $globalMissing++;
            } else {
                $moduleMissing++;
            }
        }
        
        echo "\nResumo $type:\n";
        echo "- Globais ausentes: $globalMissing\n";
        echo "- Módulos ausentes: $moduleMissing\n";
    }
    
    $results[$type] = $missing;
    echo "\n";
}

// Resumo final
echo "=== RESUMO FINAL ===\n";
$totalMissing = 0;
$totalGlobal = 0;
$totalModule = 0;

foreach ($results as $type => $missing) {
    $count = count($missing);
    $totalMissing += $count;
    
    $globalCount = 0;
    $moduleCount = 0;
    
    foreach ($missing as $item) {
        if ($item['expected_location'] === 'global') {
            $globalCount++;
        } else {
            $moduleCount++;
        }
    }
    
    $totalGlobal += $globalCount;
    $totalModule += $moduleCount;
    
    echo "$type: $count registros ausentes (Global: $globalCount, Módulos: $moduleCount)\n";
}

echo "\nTotal geral: $totalMissing registros ausentes\n";
echo "- Recursos globais ausentes: $totalGlobal\n";
echo "- Recursos de módulos ausentes: $totalModule\n\n";

// Salva relatório detalhado
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_missing' => $totalMissing,
        'global_missing' => $totalGlobal,
        'module_missing' => $totalModule,
        'by_type' => []
    ],
    'details' => $results
];

foreach ($results as $type => $missing) {
    $globalCount = 0;
    $moduleCount = 0;
    
    foreach ($missing as $item) {
        if ($item['expected_location'] === 'global') {
            $globalCount++;
        } else {
            $moduleCount++;
        }
    }
    
    $report['summary']['by_type'][$type] = [
        'total' => count($missing),
        'global' => $globalCount,
        'modules' => $moduleCount
    ];
}

file_put_contents("$basePath/missing_data_complete_report.json", json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Relatório detalhado salvo em: missing_data_complete_report.json\n";

echo "Análise completa concluída!\n";
?>
