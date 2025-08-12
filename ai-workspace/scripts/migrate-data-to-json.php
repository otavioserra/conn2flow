<?php
/**
 * Script para migrar dados para JSON
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);
$modules_dir = $base_dir . '/gestor/modulos';
$seeds_dir = $base_dir . '/gestor/db/seeds';
$data_dir = $base_dir . '/gestor/db/data';

echo "🚀 MIGRADOR DE DADOS PARA JSON - CONN2FLOW v1.11.0\n";
echo "====================================================\n\n";

/**
 * Função para analisar módulos
 */
function analyzeModules($modules_dir) {
    echo "📁 Analisando módulos em: $modules_dir\n";
    echo "--------------------------------------------\n";
    
    if (!is_dir($modules_dir)) {
        echo "❌ Pasta de módulos não encontrada!\n";
        return [];
    }
    
    $modules = [];
    $dirs = scandir($modules_dir);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $module_path = $modules_dir . '/' . $dir;
        if (!is_dir($module_path)) continue;
        
        $php_file = $module_path . '/' . $dir . '.php';
        
        if (file_exists($php_file)) {
            echo "✅ Módulo encontrado: $dir\n";
            $modules[] = [
                'id' => $dir,
                'path' => $module_path,
                'php_file' => $php_file
            ];
        } else {
            echo "⚠️  Módulo sem arquivo PHP: $dir\n";
        }
    }
    
    echo "\n📊 Total de módulos válidos: " . count($modules) . "\n\n";
    return $modules;
}

/**
 * Função para analisar seeders
 */
function analyzeSeeders($seeds_dir) {
    echo "📁 Analisando seeders em: $seeds_dir\n";
    echo "----------------------------------------\n";
    
    if (!is_dir($seeds_dir)) {
        echo "❌ Pasta de seeds não encontrada!\n";
        return [];
    }
    
    $seeders = [];
    $files = scandir($seeds_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (!str_ends_with($file, '.php')) continue;
        if (!str_contains($file, 'Seeder')) continue;
        
        $seeder_file = $seeds_dir . '/' . $file;
        echo "✅ Seeder encontrado: $file\n";
        
        $seeders[] = [
            'filename' => $file,
            'path' => $seeder_file,
            'json_name' => str_replace('Seeder.php', 'Data.json', $file)
        ];
    }
    
    echo "\n📊 Total de seeders válidos: " . count($seeders) . "\n\n";
    return $seeders;
}

/**
 * Função para extrair variável do módulo
 */
function extractModuleVariable($php_file, $module_id) {
    echo "🔍 Extraindo variável do módulo: $module_id\n";
    
    $content = file_get_contents($php_file);
    
    // Buscar a definição da variável usando regex
    $pattern = '/\$_GESTOR\[\'modulo#\'\.\$_GESTOR\[\'modulo-id\'\]\]\s*=\s*Array\s*\((.*?)\);/s';
    
    if (preg_match($pattern, $content, $matches)) {
        echo "✅ Variável encontrada no módulo: $module_id\n";
        return $matches[0]; // Retorna toda a definição da variável
    } else {
        echo "❌ Variável não encontrada no módulo: $module_id\n";
        return false;
    }
}

/**
 * Função para extrair dados do seeder
 */
function extractSeederData($seeder_file) {
    echo "🔍 Extraindo dados do seeder: " . basename($seeder_file) . "\n";
    
    $content = file_get_contents($seeder_file);
    
    // Buscar a definição da variável $data usando regex
    $pattern = '/\$data\s*=\s*\[(.*?)\];/s';
    
    if (preg_match($pattern, $content, $matches)) {
        echo "✅ Variável \$data encontrada no seeder: " . basename($seeder_file) . "\n";
        return $matches[0]; // Retorna toda a definição da variável
    } else {
        echo "❌ Variável \$data não encontrada no seeder: " . basename($seeder_file) . "\n";
        return false;
    }
}

// Executar análises
echo "🔍 FASE 1 - ANÁLISE DA ESTRUTURA ATUAL\n";
echo "=====================================\n\n";

$modules = analyzeModules($modules_dir);
$seeders = analyzeSeeders($seeds_dir);

echo "📋 RESUMO DA ANÁLISE:\n";
echo "====================\n";
echo "✅ Módulos encontrados: " . count($modules) . "\n";
echo "✅ Seeders encontrados: " . count($seeders) . "\n\n";

if (count($modules) > 0 && count($seeders) > 0) {
    echo "🎯 Estrutura válida! Pronto para migração.\n\n";
    
    // Testar extração em um módulo
    if (count($modules) > 0) {
        echo "🧪 TESTE DE EXTRAÇÃO - PRIMEIRO MÓDULO\n";
        echo "=====================================\n";
        $first_module = $modules[0];
        $variable = extractModuleVariable($first_module['php_file'], $first_module['id']);
        if ($variable) {
            echo "✅ Teste de extração do módulo bem-sucedido!\n";
        }
        echo "\n";
    }
    
    // Testar extração em um seeder
    if (count($seeders) > 0) {
        echo "🧪 TESTE DE EXTRAÇÃO - PRIMEIRO SEEDER\n";
        echo "=====================================\n";
        $first_seeder = $seeders[0];
        $data = extractSeederData($first_seeder['path']);
        if ($data) {
            echo "✅ Teste de extração do seeder bem-sucedido!\n";
        }
        echo "\n";
    }
    
} else {
    echo "❌ Estrutura inválida! Verifique os diretórios.\n";
}

echo "🏁 Análise concluída!\n";
?>
