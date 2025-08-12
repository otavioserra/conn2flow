<?php
/**
 * Script para migrar dados globais para JSON
 * Desenvolvedor: Otavio Serra
 * Projeto: Conn2Flow v1.11.0
 * Data: 9 de agosto de 2025
 */

// Configurações
$base_dir = dirname(__DIR__, 3);
$resources_dir = $base_dir . '/gestor/resources';
$pt_br_source_file = $resources_dir . '/resources.map.pt-br.php';
$pt_br_target_dir = $resources_dir . '/pt-br';
$main_mapping_file = $resources_dir . '/resources.map.php';

echo "🚀 MIGRADOR DE DADOS GLOBAIS PARA JSON - CONN2FLOW v1.11.0\n";
echo "===========================================================\n\n";

/**
 * Função para incluir o arquivo e obter os dados
 */
function loadResourcesData($file_path) {
    echo "📁 Carregando dados de: " . basename($file_path) . "\n";
    
    if (!file_exists($file_path)) {
        echo "❌ Arquivo não encontrado: $file_path\n";
        return false;
    }
    
    // Incluir o arquivo e capturar o retorno
    $resources = include $file_path;
    
    if (!is_array($resources)) {
        echo "❌ Dados inválidos no arquivo\n";
        return false;
    }
    
    echo "✅ Dados carregados com sucesso\n";
    echo "📊 Tipos encontrados: " . implode(', ', array_keys($resources)) . "\n\n";
    
    return $resources;
}

/**
 * Função para criar arquivo JSON
 */
function createJsonFile($data, $target_file) {
    echo "📄 Criando arquivo JSON: " . basename($target_file) . "\n";
    
    // Converter para JSON com formatação
    $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json_data === false) {
        echo "❌ Falha na conversão para JSON\n";
        return false;
    }
    
    // Criar diretório se não existir
    $target_dir = dirname($target_file);
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            echo "❌ Falha ao criar diretório: $target_dir\n";
            return false;
        }
    }
    
    // Salvar arquivo
    if (file_put_contents($target_file, $json_data) === false) {
        echo "❌ Falha ao salvar arquivo: $target_file\n";
        return false;
    }
    
    echo "✅ Arquivo JSON criado com sucesso\n";
    echo "📊 Tamanho: " . strlen($json_data) . " bytes\n";
    echo "📊 Registros: " . count($data) . "\n\n";
    
    return true;
}

/**
 * Função para atualizar o arquivo de mapeamento principal
 */
function updateMainMapping($main_file) {
    echo "🔧 Atualizando arquivo de mapeamento principal\n";
    
    if (!file_exists($main_file)) {
        echo "❌ Arquivo principal não encontrado: $main_file\n";
        return false;
    }
    
    $content = file_get_contents($main_file);
    
    // Nova estrutura
    $new_mapping = "<?php

/**********
	Description: resources mapping.
**********/

// ===== Variable definition.

\$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
            ],
            'version' => '1',
        ],
    ],
];

// ===== Return the variable.

return \$resources;";

    if (file_put_contents($main_file, $new_mapping) === false) {
        echo "❌ Falha ao atualizar arquivo principal\n";
        return false;
    }
    
    echo "✅ Arquivo de mapeamento principal atualizado\n\n";
    return true;
}

/**
 * Função para deletar arquivo antigo
 */
function deleteOldFile($file_path) {
    echo "🗑️  Removendo arquivo antigo: " . basename($file_path) . "\n";
    
    if (!file_exists($file_path)) {
        echo "⚠️  Arquivo já não existe\n";
        return true;
    }
    
    if (unlink($file_path)) {
        echo "✅ Arquivo removido com sucesso\n\n";
        return true;
    } else {
        echo "❌ Falha ao remover arquivo\n";
        return false;
    }
}

// ===== EXECUÇÃO PRINCIPAL =====

echo "🎯 INICIANDO MIGRAÇÃO DOS DADOS GLOBAIS\n";
echo "=======================================\n\n";

// Passo 1: Carregar dados do arquivo original
echo "📋 PASSO 1 - CARREGAMENTO DOS DADOS\n";
echo "===================================\n";

$resources_data = loadResourcesData($pt_br_source_file);

if ($resources_data === false) {
    echo "❌ Falha crítica no carregamento dos dados\n";
    exit(1);
}

// Verificar se tem os 3 tipos esperados
$expected_types = ['layouts', 'pages', 'components'];
$missing_types = [];

foreach ($expected_types as $type) {
    if (!isset($resources_data[$type])) {
        $missing_types[] = $type;
    }
}

if (!empty($missing_types)) {
    echo "❌ Tipos obrigatórios não encontrados: " . implode(', ', $missing_types) . "\n";
    exit(1);
}

echo "✅ Todos os tipos obrigatórios encontrados\n\n";

// Passo 2: Criar arquivos JSON
echo "📋 PASSO 2 - CRIAÇÃO DOS ARQUIVOS JSON\n";
echo "======================================\n";

$success_count = 0;
$total_count = 0;

foreach ($expected_types as $type) {
    $total_count++;
    $target_file = $pt_br_target_dir . '/' . $type . '.json';
    
    if (createJsonFile($resources_data[$type], $target_file)) {
        $success_count++;
    }
}

echo "📊 Arquivos JSON criados: $success_count/$total_count\n\n";

if ($success_count !== $total_count) {
    echo "❌ Falha na criação de alguns arquivos JSON\n";
    exit(1);
}

// Passo 3: Atualizar arquivo de mapeamento principal
echo "📋 PASSO 3 - ATUALIZAÇÃO DO MAPEAMENTO PRINCIPAL\n";
echo "================================================\n";

if (!updateMainMapping($main_mapping_file)) {
    echo "❌ Falha na atualização do mapeamento principal\n";
    exit(1);
}

// Passo 4: Remover arquivo antigo
echo "📋 PASSO 4 - REMOÇÃO DO ARQUIVO ANTIGO\n";
echo "======================================\n";

if (!deleteOldFile($pt_br_source_file)) {
    echo "❌ Falha na remoção do arquivo antigo\n";
    exit(1);
}

// ===== RELATÓRIO FINAL =====
echo "🏁 MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "==================================\n\n";

echo "✅ ARQUIVOS CRIADOS:\n";
echo "📄 $pt_br_target_dir/layouts.json\n";
echo "📄 $pt_br_target_dir/pages.json\n";
echo "📄 $pt_br_target_dir/components.json\n\n";

echo "✅ ARQUIVOS ATUALIZADOS:\n";
echo "🔧 $main_mapping_file\n\n";

echo "✅ ARQUIVOS REMOVIDOS:\n";
echo "🗑️  $pt_br_source_file\n\n";

echo "🎉 MIGRAÇÃO DOS DADOS GLOBAIS FINALIZADA!\n";
echo "📁 Estrutura JSON criada com sucesso\n";
echo "🔧 Sistema pronto para usar os novos arquivos JSON\n\n";

echo "📊 ESTATÍSTICAS:\n";
echo "================\n";
echo "🔢 Layouts migrados: " . count($resources_data['layouts']) . "\n";
echo "🔢 Páginas migradas: " . count($resources_data['pages']) . "\n";
echo "🔢 Componentes migrados: " . count($resources_data['components']) . "\n\n";

echo "✨ Desenvolvido por: Otavio Serra\n";
echo "📅 Data: 9 de agosto de 2025\n";
echo "🏷️ Versão: Conn2Flow v1.11.0\n";
?>
