<?php

/**
 * Script para extrair metadados de tradução dos módulos JSON
 * Gera dicionários temporários para análise e tradução manual
 */

// Configurações
$modulosPath = __DIR__ . '/../../../gestor/modulos/';
$dictionariesPath = __DIR__ . '/dictionaries/';
$analiseFile = __DIR__ . '/../../prompts/translates/dictionaries/dicionarios-para-analisar.md';

// Criar pasta dictionaries se não existir
if (!is_dir($dictionariesPath)) {
    mkdir($dictionariesPath, 0755, true);
}

// Inicializar conteúdo do arquivo de análise
$analiseContent = "# Dicionários para Análise de Tradução\n\n";
$analiseContent .= "## 📊 Estatísticas Gerais\n";
$analiseContent .= "- **Total de Arquivos**: {total}\n";
$analiseContent .= "- **Arquivos JSON**: {json_count}\n";
$analiseContent .= "\n*Última atualização: " . date('d/m/Y H:i:s') . "*\n\n";
$analiseContent .= "## 📋 Lista de Arquivos para Análise\n\n";

$totalFiles = 0;
$jsonCount = 0;

// Percorrer módulos
$modulos = scandir($modulosPath);
$modulos = array_filter($modulos, function($item) {
    return $item !== '.' && $item !== '..' && is_dir(__DIR__ . '/../../../gestor/modulos/' . $item);
});

foreach ($modulos as $modulo) {
    $jsonFile = $modulosPath . $modulo . '/' . $modulo . '.json';

    if (file_exists($jsonFile)) {
        echo "Processando módulo: $modulo\n";

        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            echo "Erro ao decodificar JSON: $jsonFile\n";
            continue;
        }

        // Verificar se tem seções de recursos
        if (isset($data['resources'])) {
            $languages = ['pt-br', 'en'];

            foreach ($languages as $lang) {
                if (isset($data['resources'][$lang])) {
                    $langData = $data['resources'][$lang];

                    // Extrair valores de variáveis
                    $variables = [];
                    if (isset($langData['variables'])) {
                        foreach ($langData['variables'] as $var) {
                            if (isset($var['id']) && isset($var['value'])) {
                                $variables[$var['id']] = $var['value'];
                            }
                        }
                    }

                    // Salvar em arquivo separado
                    $dictFile = $dictionariesPath . $modulo . '_' . $lang . '.json';
                    $dictData = [
                        'module' => $modulo,
                        'language' => $lang,
                        'variables' => $variables,
                        'source_file' => $jsonFile,
                        'generated_at' => date('Y-m-d H:i:s')
                    ];

                    file_put_contents($dictFile, json_encode($dictData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // Adicionar à lista de análise
                    $analiseContent .= "- [ ] `ai-workspace/scripts/translates/dictionaries/{$modulo}_{$lang}.json`\n";

                    $totalFiles++;
                    $jsonCount++;
                }
            }
        }
    }
}

// Atualizar estatísticas
$analiseContent = str_replace('{total}', $totalFiles, $analiseContent);
$analiseContent = str_replace('{json_count}', $jsonCount, $analiseContent);

// Salvar arquivo de análise
file_put_contents($analiseFile, $analiseContent);

echo "\n✅ Processo concluído!\n";
echo "Total de arquivos gerados: $totalFiles\n";
echo "Arquivo de análise: $analiseFile\n";

?>
