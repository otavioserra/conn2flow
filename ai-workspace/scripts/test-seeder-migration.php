<?php
/**
 * Script de teste para um seeder específico
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 2);
$test_seeder = 'CategoriasSeeder.php';
$seeder_path = $base_dir . '/gestor/db/seeds/' . $test_seeder;

echo "🧪 TESTE DE MIGRAÇÃO - SEEDER: $test_seeder\n";
echo "==========================================\n\n";

if (!file_exists($seeder_path)) {
    echo "❌ Arquivo não encontrado: $seeder_path\n";
    exit(1);
}

echo "📁 Arquivo encontrado: $seeder_path\n";

// Ler conteúdo original
$content = file_get_contents($seeder_path);
echo "📄 Conteúdo lido: " . strlen($content) . " bytes\n\n";

// Buscar a variável $data
$pattern = '/\$data\s*=\s*\[(.*?)\];/s';

if (preg_match($pattern, $content, $matches)) {
    echo "✅ Variável \$data encontrada!\n";
    echo "📋 Tamanho do array capturado: " . strlen($matches[1]) . " bytes\n\n";
    
    // Tentar extrair o array
    $array_content = $matches[1];
    
    // Criar arquivo temporário para avaliar
    $temp_file = tempnam(sys_get_temp_dir(), 'php_array_seeder_test_');
    $php_code = "<?php return [$array_content];";
    file_put_contents($temp_file, $php_code);
    
    echo "🔧 Arquivo temporário criado: $temp_file\n";
    
    try {
        $array_data = include $temp_file;
        unlink($temp_file);
        
        echo "✅ Array PHP avaliado com sucesso!\n";
        echo "📊 Registros no array: " . count($array_data) . "\n";
        
        if (count($array_data) > 0) {
            echo "🔑 Chaves do primeiro registro: " . implode(', ', array_keys($array_data[0])) . "\n\n";
        }
        
        // Converter para JSON
        $json_data = json_encode($array_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json_data !== false) {
            echo "✅ Conversão para JSON bem-sucedida!\n";
            echo "📄 Tamanho do JSON: " . strlen($json_data) . " bytes\n\n";
            
            // Mostrar primeiras linhas do JSON
            $json_lines = explode("\n", $json_data);
            echo "📋 Primeiras 15 linhas do JSON:\n";
            echo "--------------------------------\n";
            for ($i = 0; $i < min(15, count($json_lines)); $i++) {
                echo ($i + 1) . ": " . $json_lines[$i] . "\n";
            }
            echo "...\n\n";
            
            echo "🎯 TESTE CONCLUÍDO COM SUCESSO!\n";
            echo "✅ O seeder pode ser migrado sem problemas.\n";
            
        } else {
            echo "❌ Falha na conversão para JSON!\n";
        }
        
    } catch (Exception $e) {
        unlink($temp_file);
        echo "❌ Erro ao avaliar array PHP: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Variável \$data não encontrada no arquivo!\n";
    echo "📝 Padrão usado: $pattern\n";
    
    // Mostrar as primeiras linhas do arquivo para debug
    $lines = explode("\n", $content);
    echo "\n📋 Primeiras 20 linhas do arquivo:\n";
    echo "------------------------------------\n";
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        echo ($i + 1) . ": " . $lines[$i] . "\n";
    }
}

echo "\n🏁 Teste finalizado.\n";
?>
