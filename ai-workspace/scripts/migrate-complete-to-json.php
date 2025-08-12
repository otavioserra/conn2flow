<?php
/**
 * Script completo para migrar dados para JSON
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);
$modules_dir = $base_dir . '/gestor/modulos';
$seeds_dir = $base_dir . '/gestor/db/seeds';
$data_dir = $base_dir . '/gestor/db/data';

echo "🚀 MIGRADOR COMPLETO DE DADOS PARA JSON - CONN2FLOW v1.11.0\n";
echo "============================================================\n\n";

/**
 * Função para extrair array PHP de uma string
 */
function extractPHPArray($content, $variable_pattern) {
    if (preg_match($variable_pattern, $content, $matches)) {
        // Capturar todo o array
        $array_content = $matches[1];
        
        // Criar um arquivo temporário para avaliar o array
        $temp_file = tempnam(sys_get_temp_dir(), 'php_array_');
        $php_code = "<?php return Array($array_content);";
        file_put_contents($temp_file, $php_code);
        
        try {
            $array_data = include $temp_file;
            unlink($temp_file);
            return $array_data;
        } catch (Exception $e) {
            unlink($temp_file);
            echo "❌ Erro ao avaliar array PHP: " . $e->getMessage() . "\n";
            return false;
        }
    }
    return false;
}

/**
 * Função para migrar módulo específico
 */
function migrateModule($module_id, $module_path, $php_file) {
    echo "🔄 Migrando módulo: $module_id\n";
    
    $content = file_get_contents($php_file);
    
    // Padrão para capturar a variável do módulo
    $pattern = '/\$_GESTOR\[\'modulo#\'\.\$_GESTOR\[\'modulo-id\'\]\]\s*=\s*Array\s*\((.*?)\);/s';
    
    // Extrair o array PHP
    $array_data = extractPHPArray($content, $pattern);
    
    if ($array_data === false) {
        echo "❌ Falha ao extrair dados do módulo: $module_id\n";
        return false;
    }
    
    // Converter para JSON
    $json_data = json_encode($array_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Criar arquivo JSON
    $json_file = $module_path . '/' . $module_id . '.json';
    if (file_put_contents($json_file, $json_data) === false) {
        echo "❌ Falha ao criar arquivo JSON: $json_file\n";
        return false;
    }
    
    // Atualizar o arquivo PHP
    $new_php_content = preg_replace(
        $pattern,
        '$_GESTOR[\'modulo#\'.$_GESTOR[\'modulo-id\']] = json_decode(file_get_contents(__DIR__ . \'/' . $module_id . '.json\'), true);',
        $content
    );
    
    if (file_put_contents($php_file, $new_php_content) === false) {
        echo "❌ Falha ao atualizar arquivo PHP: $php_file\n";
        return false;
    }
    
    echo "✅ Módulo migrado com sucesso: $module_id\n";
    echo "   📄 JSON criado: $json_file\n";
    echo "   🔧 PHP atualizado: $php_file\n\n";
    
    return true;
}

/**
 * Função para migrar seeder específico
 */
function migrateSeeder($seeder_filename, $seeder_path, $data_dir) {
    echo "🔄 Migrando seeder: $seeder_filename\n";
    
    $content = file_get_contents($seeder_path);
    
    // Padrão para capturar a variável $data
    $pattern = '/\$data\s*=\s*\[(.*?)\];/s';
    
    // Extrair o array PHP
    $array_data = extractPHPArray($content, $pattern);
    
    if ($array_data === false) {
        echo "❌ Falha ao extrair dados do seeder: $seeder_filename\n";
        return false;
    }
    
    // Criar pasta data se não existir
    if (!is_dir($data_dir)) {
        if (!mkdir($data_dir, 0755, true)) {
            echo "❌ Falha ao criar pasta data: $data_dir\n";
            return false;
        }
    }
    
    // Converter para JSON
    $json_data = json_encode($array_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Nome do arquivo JSON
    $json_filename = str_replace('Seeder.php', 'Data.json', $seeder_filename);
    $json_file = $data_dir . '/' . $json_filename;
    
    if (file_put_contents($json_file, $json_data) === false) {
        echo "❌ Falha ao criar arquivo JSON: $json_file\n";
        return false;
    }
    
    // Atualizar o arquivo PHP do seeder
    $new_php_content = preg_replace(
        $pattern,
        '$data = json_decode(file_get_contents(__DIR__ . \'/../data/' . $json_filename . '\'), true);',
        $content
    );
    
    if (file_put_contents($seeder_path, $new_php_content) === false) {
        echo "❌ Falha ao atualizar arquivo seeder: $seeder_path\n";
        return false;
    }
    
    echo "✅ Seeder migrado com sucesso: $seeder_filename\n";
    echo "   📄 JSON criado: $json_file\n";
    echo "   🔧 Seeder atualizado: $seeder_path\n\n";
    
    return true;
}

/**
 * Função principal
 */
function main() {
    global $modules_dir, $seeds_dir, $data_dir;
    
    echo "📋 INICIANDO MIGRAÇÃO COMPLETA\n";
    echo "==============================\n\n";
    
    // Contadores
    $modules_migrated = 0;
    $modules_failed = 0;
    $seeders_migrated = 0;
    $seeders_failed = 0;
    
    // ===== TAREFA 1: MIGRAR MÓDULOS =====
    echo "🎯 TAREFA 1 - MIGRANDO MÓDULOS\n";
    echo "===============================\n\n";
    
    if (!is_dir($modules_dir)) {
        echo "❌ Pasta de módulos não encontrada: $modules_dir\n";
        return;
    }
    
    $dirs = scandir($modules_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $module_path = $modules_dir . '/' . $dir;
        if (!is_dir($module_path)) continue;
        
        $php_file = $module_path . '/' . $dir . '.php';
        
        if (file_exists($php_file)) {
            if (migrateModule($dir, $module_path, $php_file)) {
                $modules_migrated++;
            } else {
                $modules_failed++;
            }
        } else {
            echo "⚠️  Módulo sem arquivo PHP: $dir\n";
            $modules_failed++;
        }
    }
    
    // ===== TAREFA 2: MIGRAR SEEDERS =====
    echo "🎯 TAREFA 2 - MIGRANDO SEEDERS\n";
    echo "===============================\n\n";
    
    if (!is_dir($seeds_dir)) {
        echo "❌ Pasta de seeds não encontrada: $seeds_dir\n";
        return;
    }
    
    $files = scandir($seeds_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (!str_ends_with($file, '.php')) continue;
        if (!str_contains($file, 'Seeder')) continue;
        
        $seeder_path = $seeds_dir . '/' . $file;
        
        if (migrateSeeder($file, $seeder_path, $data_dir)) {
            $seeders_migrated++;
        } else {
            $seeders_failed++;
        }
    }
    
    // ===== RELATÓRIO FINAL =====
    echo "🏁 MIGRAÇÃO CONCLUÍDA - RELATÓRIO FINAL\n";
    echo "=======================================\n\n";
    
    echo "📊 MÓDULOS:\n";
    echo "✅ Migrados com sucesso: $modules_migrated\n";
    echo "❌ Falhas: $modules_failed\n\n";
    
    echo "📊 SEEDERS:\n";
    echo "✅ Migrados com sucesso: $seeders_migrated\n";
    echo "❌ Falhas: $seeders_failed\n\n";
    
    if ($modules_failed === 0 && $seeders_failed === 0) {
        echo "🎉 MIGRAÇÃO 100% CONCLUÍDA COM SUCESSO!\n";
        echo "🔧 Todos os dados foram migrados para JSON.\n";
        echo "📁 Arquivos JSON criados e códigos PHP atualizados.\n\n";
    } else {
        echo "⚠️  MIGRAÇÃO CONCLUÍDA COM ALGUNS PROBLEMAS!\n";
        echo "📝 Verifique os logs acima para detalhes dos erros.\n\n";
    }
    
    echo "✨ Obrigado por usar o migrador Conn2Flow!\n";
}

// Executar migração
main();
?>
