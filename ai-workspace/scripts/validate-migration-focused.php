<?php
/**
 * Script de validação focada da migração JSON
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);

echo "🔍 VALIDAÇÃO FOCADA DA MIGRAÇÃO JSON - CONN2FLOW v1.11.0\n";
echo "=========================================================\n\n";

// Teste 1: Validação dos arquivos JSON de módulos
echo "🧪 TESTE 1 - VALIDAÇÃO DOS ARQUIVOS JSON DE MÓDULOS\n";
echo "===================================================\n";

$modules_dir = $base_dir . '/gestor/modulos';
$valid_modules = 0;
$invalid_modules = 0;

if (is_dir($modules_dir)) {
    $dirs = scandir($modules_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $module_path = $modules_dir . '/' . $dir;
        if (!is_dir($module_path)) continue;
        
        $json_file = $module_path . '/' . $dir . '.json';
        $php_file = $module_path . '/' . $dir . '.php';
        
        if (file_exists($json_file)) {
            // Verificar se o JSON é válido
            $json_content = file_get_contents($json_file);
            $data = json_decode($json_content, true);
            
            if ($data !== null && json_last_error() === JSON_ERROR_NONE) {
                // Verificar se o PHP contém a referência JSON
                if (file_exists($php_file)) {
                    $php_content = file_get_contents($php_file);
                    if (strpos($php_content, 'json_decode(file_get_contents(__DIR__ . \'/' . $dir . '.json\')') !== false) {
                        echo "✅ $dir: Módulo migrado corretamente\n";
                        $valid_modules++;
                    } else {
                        echo "⚠️  $dir: JSON existe mas PHP não atualizado\n";
                        $invalid_modules++;
                    }
                } else {
                    echo "⚠️  $dir: JSON existe mas arquivo PHP não encontrado\n";
                    $invalid_modules++;
                }
            } else {
                echo "❌ $dir: JSON inválido\n";
                $invalid_modules++;
            }
        } else {
            echo "❌ $dir: JSON não encontrado\n";
            $invalid_modules++;
        }
    }
}

echo "\n📊 Módulos válidos: $valid_modules\n";
echo "📊 Módulos com problemas: $invalid_modules\n\n";

// Teste 2: Validação dos arquivos JSON de seeders
echo "🧪 TESTE 2 - VALIDAÇÃO DOS ARQUIVOS JSON DE SEEDERS\n";
echo "===================================================\n";

$seeds_dir = $base_dir . '/gestor/db/seeds';
$data_dir = $base_dir . '/gestor/db/data';
$valid_seeders = 0;
$invalid_seeders = 0;

if (is_dir($seeds_dir)) {
    $files = scandir($seeds_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (!str_ends_with($file, '.php')) continue;
        if (!str_contains($file, 'Seeder')) continue;
        
        $seeder_file = $seeds_dir . '/' . $file;
        $json_name = str_replace('Seeder.php', 'Data.json', $file);
        $json_file = $data_dir . '/' . $json_name;
        
        if (file_exists($json_file)) {
            // Verificar se o JSON é válido
            $json_content = file_get_contents($json_file);
            $data = json_decode($json_content, true);
            
            if ($data !== null && json_last_error() === JSON_ERROR_NONE) {
                // Verificar se o seeder contém a referência JSON
                $php_content = file_get_contents($seeder_file);
                if (strpos($php_content, 'json_decode(file_get_contents(__DIR__ . \'/../data/' . $json_name . '\')') !== false) {
                    echo "✅ $file: Seeder migrado corretamente\n";
                    $valid_seeders++;
                } else {
                    echo "⚠️  $file: JSON existe mas seeder não atualizado\n";
                    $invalid_seeders++;
                }
            } else {
                echo "❌ $file: JSON inválido\n";
                $invalid_seeders++;
            }
        } else {
            echo "❌ $file: JSON não encontrado ($json_name)\n";
            $invalid_seeders++;
        }
    }
}

echo "\n📊 Seeders válidos: $valid_seeders\n";
echo "📊 Seeders com problemas: $invalid_seeders\n\n";

// Teste 3: Teste de carregamento simples
echo "🧪 TESTE 3 - TESTE DE CARREGAMENTO SIMPLES\n";
echo "==========================================\n";

// Testar carregamento de um JSON de módulo
$test_json = $base_dir . '/gestor/modulos/admin-arquivos/admin-arquivos.json';
if (file_exists($test_json)) {
    $data = json_decode(file_get_contents($test_json), true);
    if ($data && isset($data['versao'])) {
        echo "✅ JSON de módulo carregado: versão " . $data['versao'] . "\n";
    } else {
        echo "❌ Falha ao carregar JSON de módulo\n";
    }
} else {
    echo "❌ JSON de teste não encontrado\n";
}

// Testar carregamento de um JSON de seeder
$test_data_json = $base_dir . '/gestor/db/data/CategoriasData.json';
if (file_exists($test_data_json)) {
    $data = json_decode(file_get_contents($test_data_json), true);
    if ($data && count($data) > 0) {
        echo "✅ JSON de seeder carregado: " . count($data) . " registros\n";
    } else {
        echo "❌ Falha ao carregar JSON de seeder\n";
    }
} else {
    echo "❌ JSON de dados não encontrado\n";
}

echo "\n🏁 VALIDAÇÃO CONCLUÍDA!\n";
echo "========================\n\n";

if ($invalid_modules === 0 && $invalid_seeders === 0) {
    echo "🎉 MIGRAÇÃO 100% VALIDADA COM SUCESSO!\n";
    echo "✅ Todos os $valid_modules módulos foram migrados corretamente\n";
    echo "✅ Todos os $valid_seeders seeders foram migrados corretamente\n";
    echo "🔧 Os arquivos JSON estão sendo carregados corretamente\n";
    echo "📁 Estrutura de dados migrada para JSON com sucesso!\n\n";
    
    echo "🎯 PRÓXIMOS PASSOS:\n";
    echo "- ✅ Tarefa 1 (Módulos): Concluída\n";
    echo "- ✅ Tarefa 2 (Seeders): Concluída\n";
    echo "- 📝 Tarefa 3: Aguardando definição\n";
} else {
    echo "⚠️  MIGRAÇÃO CONCLUÍDA COM ALGUNS PROBLEMAS!\n";
    echo "📊 Módulos com problemas: $invalid_modules\n";
    echo "📊 Seeders com problemas: $invalid_seeders\n";
    echo "📝 Verifique os logs acima para mais detalhes\n";
}

echo "\n✨ Migração de dados para JSON finalizada!\n";
?>
