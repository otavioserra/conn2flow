<?php
/**
 * Script para criar os recursos ausentes identificados na análise
 * Cria arquivos HTML/CSS e atualiza arquivos JSON conforme necessário
 */

echo "=== CRIANDO RECURSOS AUSENTES ===\n\n";

// Carrega o relatório de análise
$basePath = realpath(__DIR__ . '/../../../'); // raiz dinâmica
$reportFile = "$basePath/missing_data_complete_report.json";

if (!file_exists($reportFile)) {
    echo "ERRO: Relatório de análise não encontrado. Execute primeiro o script de análise.\n";
    exit(1);
}

$report = json_decode(file_get_contents($reportFile), true);
$resourcesPath = "$basePath/gestor/resources/pt-br";
$modulesPath = "$basePath/gestor/modulos";

// Mapeamento de tipos
$resourceMapping = [
    'paginas' => 'pages',
    'layouts' => 'layouts',
    'componentes' => 'components'
];

/**
 * Cria diretório se não existe
 */
function createDirectory($path) {
    if (!is_dir($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

/**
 * Cria arquivo HTML/CSS se tem conteúdo
 */
function createResourceFile($filePath, $content) {
    if ($content === null || trim($content) === '') {
        return false;
    }
    
    // Cria diretório pai se necessário
    $dir = dirname($filePath);
    createDirectory($dir);
    
    // Desescapa conteúdo que veio com sequências de escape (\" \n \r etc.)
    // Ex.: transforma \" em ", \n em nova linha, \\ em \
    $content = stripcslashes($content);
    // Normaliza quebras de linha para \n
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    
    return file_put_contents($filePath, $content) !== false;
}

/**
 * Atualiza arquivo JSON global
 */
function updateGlobalJson($filePath, $newData) {
    $existingData = [];
    
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $existingData = json_decode($content, true) ?: [];
    }
    
    // Adiciona o novo item
    $existingData[] = $newData;
    
    // Cria diretório se necessário
    createDirectory(dirname($filePath));
    
    return file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Atualiza arquivo JSON de módulo
 */
function updateModuleJson($filePath, $resourceType, $newData) {
    $existingData = [];
    
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $existingData = json_decode($content, true) ?: [];
    }
    
    // Garante que a estrutura existe
    if (!isset($existingData['resources'])) {
        $existingData['resources'] = [];
    }
    if (!isset($existingData['resources']['pt-br'])) {
        $existingData['resources']['pt-br'] = [];
    }
    if (!isset($existingData['resources']['pt-br'][$resourceType])) {
        $existingData['resources']['pt-br'][$resourceType] = [];
    }
    
    // Adiciona o novo item
    $existingData['resources']['pt-br'][$resourceType][] = $newData;
    
    // Cria diretório se necessário
    createDirectory(dirname($filePath));
    
    return file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// Contadores
$created = [
    'global_json' => 0,
    'module_json' => 0,
    'html_files' => 0,
    'css_files' => 0,
    'directories' => 0
];

$errors = [];

// Processa cada tipo de recurso
foreach ($report['details'] as $type => $missingItems) {
    if (empty($missingItems)) continue;
    
    echo "=== PROCESSANDO $type ===\n";
    
    $resourceType = $resourceMapping[$type];
    
    foreach ($missingItems as $item) {
        $data = $item['converted_data'];
        $module = $item['module'];
        $location = $item['expected_location'];
        $htmlContent = $item['html_content'] ?? null;
        $cssContent = $item['css_content'] ?? null;
        
        echo "Criando recurso: {$data['id']} ({$data['name']})\n";
        
        try {
            if ($location === 'global') {
                // Recurso global
                $resourceDir = "$resourcesPath/$resourceType/{$data['id']}";
                createDirectory($resourceDir);
                $created['directories']++;
                
                // Cria arquivos HTML/CSS se necessário
                if ($htmlContent !== null && trim($htmlContent) !== '') {
                    $htmlFile = "$resourceDir/{$data['id']}.html";
                    if (createResourceFile($htmlFile, $htmlContent)) {
                        $created['html_files']++;
                        echo "  ✓ Arquivo HTML criado\n";
                    }
                }
                
                if ($cssContent !== null && trim($cssContent) !== '') {
                    $cssFile = "$resourceDir/{$data['id']}.css";
                    if (createResourceFile($cssFile, $cssContent)) {
                        $created['css_files']++;
                        echo "  ✓ Arquivo CSS criado\n";
                    }
                }
                
                // Atualiza JSON global
                $jsonFile = "$resourcesPath/$resourceType.json";
                if (updateGlobalJson($jsonFile, $data)) {
                    $created['global_json']++;
                    echo "  ✓ JSON global atualizado\n";
                }
                
            } else {
                // Recurso de módulo
                $moduleId = str_replace('module:', '', $location);
                $moduleDir = "$modulesPath/$moduleId";
                $resourceDir = "$moduleDir/resources/pt-br/$resourceType/{$data['id']}";
                
                createDirectory($resourceDir);
                $created['directories']++;
                
                // Cria arquivos HTML/CSS se necessário
                if ($htmlContent !== null && trim($htmlContent) !== '') {
                    $htmlFile = "$resourceDir/{$data['id']}.html";
                    if (createResourceFile($htmlFile, $htmlContent)) {
                        $created['html_files']++;
                        echo "  ✓ Arquivo HTML criado no módulo $moduleId\n";
                    }
                }
                
                if ($cssContent !== null && trim($cssContent) !== '') {
                    $cssFile = "$resourceDir/{$data['id']}.css";
                    if (createResourceFile($cssFile, $cssContent)) {
                        $created['css_files']++;
                        echo "  ✓ Arquivo CSS criado no módulo $moduleId\n";
                    }
                }
                
                // Atualiza JSON do módulo
                $jsonFile = "$moduleDir/$moduleId.json";
                if (updateModuleJson($jsonFile, $resourceType, $data)) {
                    $created['module_json']++;
                    echo "  ✓ JSON do módulo atualizado\n";
                }
            }
            
            echo "  ✓ Recurso {$data['id']} criado com sucesso\n\n";
            
        } catch (Exception $e) {
            $error = "Erro ao criar recurso {$data['id']}: " . $e->getMessage();
            $errors[] = $error;
            echo "  ✗ $error\n\n";
        }
    }
}

// Relatório final
echo "=== RELATÓRIO FINAL ===\n";
echo "Recursos criados:\n";
echo "- Diretórios: {$created['directories']}\n";
echo "- Arquivos HTML: {$created['html_files']}\n";
echo "- Arquivos CSS: {$created['css_files']}\n";
echo "- JSONs globais atualizados: {$created['global_json']}\n";
echo "- JSONs de módulos atualizados: {$created['module_json']}\n";

if (!empty($errors)) {
    echo "\nErros encontrados:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
} else {
    echo "\n✓ Todos os recursos foram criados com sucesso!\n";
}

// Salva relatório de criação
$creationReport = [
    'timestamp' => date('Y-m-d H:i:s'),
    'created' => $created,
    'errors' => $errors,
    'total_resources_created' => array_sum($created)
];

file_put_contents("$basePath/resource_creation_report.json", json_encode($creationReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nRelatório de criação salvo em: resource_creation_report.json\n";

echo "\nProcesso de criação concluído!\n";
?>
