<?php
/**
 * Script de teste para um módulo específico
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);
$test_module = 'admin-arquivos';
$module_path = $base_dir . '/gestor/modulos/' . $test_module;
$php_file = $module_path . '/' . $test_module . '.php';

echo "🧪 TESTE DE MIGRAÇÃO - MÓDULO: $test_module\n";
echo "============================================\n\n";

if (!file_exists($php_file)) {
    echo "❌ Arquivo não encontrado: $php_file\n";
    exit(1);
}

echo "📁 Arquivo encontrado: $php_file\n";

// Ler conteúdo original
$content = file_get_contents($php_file);
echo "📄 Conteúdo lido: " . strlen($content) . " bytes\n\n";

// Buscar a variável
$pattern = '/\$_GESTOR\[\'modulo#\'\.\$_GESTOR\[\'modulo-id\'\]\]\s*=\s*Array\s*\((.*?)\);/s';

if (preg_match($pattern, $content, $matches)) {
    echo "✅ Variável encontrada!\n";
    echo "📋 Tamanho do array capturado: " . strlen($matches[1]) . " bytes\n\n";
    
    // Tentar extrair o array
    $array_content = $matches[1];
    
    // Criar arquivo temporário para avaliar
    $temp_file = tempnam(sys_get_temp_dir(), 'php_array_test_');
    $php_code = "<?php return Array($array_content);";
    file_put_contents($temp_file, $php_code);
    
    echo "🔧 Arquivo temporário criado: $temp_file\n";
    
    try {
        $array_data = include $temp_file;
        unlink($temp_file);
        
        echo "✅ Array PHP avaliado com sucesso!\n";
        echo "📊 Elementos do array: " . count($array_data) . "\n";
        echo "🔑 Chaves principais: " . implode(', ', array_keys($array_data)) . "\n\n";
        
        // Converter para JSON
        $json_data = json_encode($array_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json_data !== false) {
            echo "✅ Conversão para JSON bem-sucedida!\n";
            echo "📄 Tamanho do JSON: " . strlen($json_data) . " bytes\n\n";
            
            // Mostrar primeiras linhas do JSON
            $json_lines = explode("\n", $json_data);
            echo "📋 Primeiras 10 linhas do JSON:\n";
            echo "--------------------------------\n";
            for ($i = 0; $i < min(10, count($json_lines)); $i++) {
                echo ($i + 1) . ": " . $json_lines[$i] . "\n";
            }
            echo "...\n\n";
            
            echo "🎯 TESTE CONCLUÍDO COM SUCESSO!\n";
            echo "✅ O módulo pode ser migrado sem problemas.\n";
            
        } else {
            echo "❌ Falha na conversão para JSON!\n";
        }
        
    } catch (Exception $e) {
        unlink($temp_file);
        echo "❌ Erro ao avaliar array PHP: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Variável não encontrada no arquivo!\n";
    echo "📝 Padrão usado: $pattern\n";
}

echo "\n🏁 Teste finalizado.\n";
?>
