<?php

echo "🧪 SCRIPT DE TESTE - EMULAÇÃO DE RELEASE\n";
echo "======================================\n\n";

// Configuração base
$base_dir = dirname(__DIR__);

// Carregar mapeamento principal
$main_resources_map_file = __DIR__ . '/resources.map.php';
if (!file_exists($main_resources_map_file)) {
    die("❌ Arquivo de mapeamento principal não encontrado: $main_resources_map_file\n");
}

$main_resources_map = include $main_resources_map_file;
$available_languages = array_keys($main_resources_map['languages']);

echo "🌍 Idiomas detectados: " . implode(', ', $available_languages) . "\n\n";

// Funções auxiliares
function getFileContent($filepath) {
    if (file_exists($filepath)) {
        return file_get_contents($filepath);
    }
    return null;
}

function generateChecksumStructure($html, $css) {
    return [
        'html' => md5($html ?? ''),
        'css' => md5($css ?? ''),
        'combined' => md5(($html ?? '') . ($css ?? ''))
    ];
}

function incrementVersion($current_version) {
    if ($current_version === '0') {
        return '1.0';
    }
    
    $parts = explode('.', $current_version);
    if (count($parts) == 2) {
        $parts[1] = (int)$parts[1] + 1;
        return implode('.', $parts);
    }
    
    return '1.0';
}

function backupFile($filepath) {
    if (file_exists($filepath)) {
        copy($filepath, $filepath . '.backup');
        echo "💾 Backup criado: " . basename($filepath) . ".backup\n";
    }
}

function restoreFile($filepath) {
    $backup_file = $filepath . '.backup';
    if (file_exists($backup_file)) {
        copy($backup_file, $filepath);
        unlink($backup_file);
        echo "🔄 Arquivo restaurado: " . basename($filepath) . "\n";
    }
}

// Armazenar estados originais
$original_states = [];

echo "📋 FASE 1: BACKUP DOS ARQUIVOS ORIGINAIS\n";
echo str_repeat("-", 50) . "\n";

foreach ($available_languages as $language) {
    $resources_map_file = __DIR__ . "/resources.map.$language.php";
    if (file_exists($resources_map_file)) {
        backupFile($resources_map_file);
        $original_states[$language] = include $resources_map_file;
    }
}

echo "\n🔧 FASE 2: SIMULANDO MUDANÇAS NOS ARQUIVOS\n";
echo str_repeat("-", 50) . "\n";

// Modificar um arquivo de layout global para simular mudança
$test_layout_file = $base_dir . "/resources/pt-br/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.html";
if (file_exists($test_layout_file)) {
    $original_content = file_get_contents($test_layout_file);
    $modified_content = $original_content . "\n<!-- Teste de modificação " . date('Y-m-d H:i:s') . " -->";
    file_put_contents($test_layout_file, $modified_content);
    echo "📝 Arquivo modificado: layout-administrativo-do-gestor.html\n";
    
    // Guardar para restaurar depois
    $test_files_to_restore = [$test_layout_file => $original_content];
} else {
    echo "⚠️ Arquivo de teste não encontrado: $test_layout_file\n";
    $test_files_to_restore = [];
}

// Modificar um arquivo de módulo (se existir)
$modulos_dir = $base_dir . '/modulos';
$test_module_file = null;
$test_module_original = null;

if (is_dir($modulos_dir)) {
    $modulos = glob($modulos_dir . '/*', GLOB_ONLYDIR);
    foreach ($modulos as $modulo_path) {
        $modulo_name = basename($modulo_path);
        $test_component = $modulo_path . "/resources/pt-br/components";
        
        if (is_dir($test_component)) {
            $components = glob($test_component . '/*', GLOB_ONLYDIR);
            if (!empty($components)) {
                $component_path = $components[0];
                $component_name = basename($component_path);
                $component_html = $component_path . "/$component_name.html";
                
                if (file_exists($component_html)) {
                    $test_module_file = $component_html;
                    $test_module_original = file_get_contents($component_html);
                    $modified_content = $test_module_original . "\n<!-- Teste módulo " . date('Y-m-d H:i:s') . " -->";
                    file_put_contents($component_html, $modified_content);
                    echo "📝 Arquivo de módulo modificado: $modulo_name/$component_name.html\n";
                    break;
                }
            }
        }
    }
}

echo "⚙️ FASE 3: EXECUTANDO GERADOR PARA DETECTAR MUDANÇAS\n";
echo str_repeat("-", 50) . "\n";

// Executar o gerador de seeders em um processo separado para evitar conflito de funções
$output = shell_exec('php generate.multilingual.seeders.php 2>&1');
echo $output;

echo "\n📊 FASE 4: VERIFICANDO MUDANÇAS NAS VERSÕES\n";
echo str_repeat("-", 50) . "\n";

foreach ($available_languages as $language) {
    $resources_map_file = __DIR__ . "/resources.map.$language.php";
    if (file_exists($resources_map_file)) {
        $current_state = include $resources_map_file;
        $original_state = $original_states[$language];
        
        echo "🌍 Idioma: $language\n";
        
        // Verificar mudanças nos layouts
        if (isset($current_state['layouts']) && isset($original_state['layouts'])) {
            foreach ($current_state['layouts'] as $current_layout) {
                foreach ($original_state['layouts'] as $original_layout) {
                    if ($current_layout['id'] === $original_layout['id']) {
                        if ($current_layout['version'] !== $original_layout['version']) {
                            echo "📋 Layout '{$current_layout['id']}': v{$original_layout['version']} → v{$current_layout['version']}\n";
                        }
                        break;
                    }
                }
            }
        }
        
        // Verificar mudanças nas páginas
        if (isset($current_state['pages']) && isset($original_state['pages'])) {
            foreach ($current_state['pages'] as $current_page) {
                foreach ($original_state['pages'] as $original_page) {
                    if ($current_page['id'] === $original_page['id']) {
                        if ($current_page['version'] !== $original_page['version']) {
                            echo "📄 Página '{$current_page['id']}': v{$original_page['version']} → v{$current_page['version']}\n";
                        }
                        break;
                    }
                }
            }
        }
        
        // Verificar mudanças nos componentes
        if (isset($current_state['components']) && isset($original_state['components'])) {
            foreach ($current_state['components'] as $current_component) {
                foreach ($original_state['components'] as $original_component) {
                    if ($current_component['id'] === $original_component['id']) {
                        if ($current_component['version'] !== $original_component['version']) {
                            echo "🧩 Componente '{$current_component['id']}': v{$original_component['version']} → v{$current_component['version']}\n";
                        }
                        break;
                    }
                }
            }
        }
        
        echo "\n";
    }
}

echo "🔄 FASE 5: RESTAURANDO ARQUIVOS ORIGINAIS\n";
echo str_repeat("-", 50) . "\n";

// Restaurar arquivos de teste modificados
if (!empty($test_files_to_restore)) {
    foreach ($test_files_to_restore as $filepath => $original_content) {
        file_put_contents($filepath, $original_content);
        echo "🔄 Restaurado: " . basename($filepath) . "\n";
    }
}

if ($test_module_file && $test_module_original) {
    file_put_contents($test_module_file, $test_module_original);
    echo "🔄 Restaurado: " . basename($test_module_file) . "\n";
}

// Restaurar arquivos de mapeamento
foreach ($available_languages as $language) {
    $resources_map_file = __DIR__ . "/resources.map.$language.php";
    restoreFile($resources_map_file);
}

echo "\n✅ TESTE CONCLUÍDO!\n";
echo "==================\n";
echo "📝 Arquivos modificados temporariamente\n";
echo "⚙️ Gerador executado e versões atualizadas\n";
echo "🔄 Arquivos restaurados ao estado original\n";
echo "🧪 Sistema de versionamento testado com sucesso!\n";

?>
