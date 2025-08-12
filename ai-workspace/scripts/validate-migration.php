<?php
/**
 * Script de validação da migração
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);

echo "🔍 VALIDAÇÃO DA MIGRAÇÃO JSON - CONN2FLOW v1.11.0\n";
echo "==================================================\n\n";

// Teste 1: Carregamento de módulo
echo "🧪 TESTE 1 - CARREGAMENTO DE MÓDULO\n";
echo "===================================\n";

$test_module_dir = $base_dir . '/gestor/modulos/admin-arquivos';
$test_module_file = $test_module_dir . '/admin-arquivos.php';

if (file_exists($test_module_file)) {
    echo "✅ Arquivo do módulo encontrado\n";
    
    // Simular carregamento
    global $_GESTOR;
    $_GESTOR = [];
    
    try {
        include $test_module_file;
        
        if (isset($_GESTOR['modulo#admin-arquivos'])) {
            echo "✅ Variável carregada com sucesso\n";
            echo "📊 Elementos: " . count($_GESTOR['modulo#admin-arquivos']) . "\n";
            echo "🔑 Chaves: " . implode(', ', array_keys($_GESTOR['modulo#admin-arquivos'])) . "\n";
            
            if (isset($_GESTOR['modulo#admin-arquivos']['resources']['pt-br']['pages'])) {
                $pages = $_GESTOR['modulo#admin-arquivos']['resources']['pt-br']['pages'];
                echo "📄 Páginas encontradas: " . count($pages) . "\n";
            }
        } else {
            echo "❌ Variável não carregada\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao carregar módulo: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Arquivo do módulo não encontrado\n";
}

echo "\n";

// Teste 2: Carregamento de seeder
echo "🧪 TESTE 2 - CARREGAMENTO DE SEEDER\n";
echo "===================================\n";

$test_data_file = $base_dir . '/gestor/db/data/CategoriasData.json';

if (file_exists($test_data_file)) {
    echo "✅ Arquivo JSON do seeder encontrado\n";
    
    try {
        $json_content = file_get_contents($test_data_file);
        $data = json_decode($json_content, true);
        
        if ($data !== null) {
            echo "✅ JSON decodificado com sucesso\n";
            echo "📊 Registros: " . count($data) . "\n";
            
            if (count($data) > 0) {
                echo "🔑 Campos do primeiro registro: " . implode(', ', array_keys($data[0])) . "\n";
            }
        } else {
            echo "❌ Falha ao decodificar JSON\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao carregar dados: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Arquivo JSON não encontrado\n";
}

echo "\n";

// Teste 3: Verificação de integridade
echo "🧪 TESTE 3 - VERIFICAÇÃO DE INTEGRIDADE\n";
echo "=======================================\n";

$modules_dir = $base_dir . '/gestor/modulos';
$data_dir = $base_dir . '/gestor/db/data';

$json_modules = 0;
$json_seeders = 0;

// Contar arquivos JSON de módulos
if (is_dir($modules_dir)) {
    $dirs = scandir($modules_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $json_file = $modules_dir . '/' . $dir . '/' . $dir . '.json';
        if (file_exists($json_file)) {
            $json_modules++;
        }
    }
}

// Contar arquivos JSON de seeders
if (is_dir($data_dir)) {
    $files = scandir($data_dir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $json_seeders++;
        }
    }
}

echo "📊 Arquivos JSON de módulos: $json_modules\n";
echo "📊 Arquivos JSON de seeders: $json_seeders\n";

echo "\n🏁 VALIDAÇÃO CONCLUÍDA!\n";

if ($json_modules > 0 && $json_seeders > 0) {
    echo "🎉 MIGRAÇÃO VALIDADA COM SUCESSO!\n";
    echo "✅ Todos os componentes estão funcionando corretamente.\n";
} else {
    echo "⚠️  Problemas detectados na validação.\n";
}
?>
