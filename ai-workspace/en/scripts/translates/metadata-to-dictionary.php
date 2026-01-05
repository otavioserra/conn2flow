<?php

/**
 * Script to extract translation metadata from JSON modules
 * Generates temporary dictionaries for analysis and manual translation
 */

// Settings
$modulosPath = __DIR__ . '/../../../../gestor/modulos/';
$dictionariesPath = __DIR__ . '/dictionaries/';
$analiseFile = __DIR__ . '/../../prompts/translates/dictionaries/dictionaries-to-analyze.md';

// Create dictionaries folder if not exists
if (!is_dir($dictionariesPath)) {
    mkdir($dictionariesPath, 0755, true);
}

// Initialize analysis file content
$analiseContent = "# Dictionaries for Translation Analysis\n\n";
$analiseContent .= "## 📊 General Statistics\n";
$analiseContent .= "- **Total Files**: {total}\n";
$analiseContent .= "- **JSON Files**: {json_count}\n";
$analiseContent .= "\n*Last update: " . date('d/m/Y H:i:s') . "*\n\n";
$analiseContent .= "## 📋 List of Files for Analysis\n\n";

$totalFiles = 0;
$jsonCount = 0;

// Iterate modules
$modulos = scandir($modulosPath);
$modulos = array_filter($modulos, function($item) {
    return $item !== '.' && $item !== '..' && is_dir(__DIR__ . '/../../../../gestor/modulos/' . $item);
});

foreach ($modulos as $modulo) {
    $jsonFile = $modulosPath . $modulo . '/' . $modulo . '.json';

    if (file_exists($jsonFile)) {
        echo "Processing module: $modulo\n";

        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            echo "Error decoding JSON: $jsonFile\n";
            continue;
        }

        // Check if it has resource sections
        if (isset($data['resources'])) {
            $languages = ['pt-br', 'en'];

            foreach ($languages as $lang) {
                if (isset($data['resources'][$lang])) {
                    $langData = $data['resources'][$lang];

                    // Extract variable values
                    $variables = [];
                    if (isset($langData['variables'])) {
                        foreach ($langData['variables'] as $var) {
                            if (isset($var['id']) && isset($var['value'])) {
                                $variables[$var['id']] = $var['value'];
                            }
                        }
                    }

                    // Save to separate file
                    $dictFile = $dictionariesPath . $modulo . '_' . $lang . '.json';
                    $dictData = [
                        'module' => $modulo,
                        'language' => $lang,
                        'variables' => $variables,
                        'source_file' => $jsonFile,
                        'generated_at' => date('Y-m-d H:i:s')
                    ];

                    file_put_contents($dictFile, json_encode($dictData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // Add to analysis list
                    $analiseContent .= "- [ ] `ai-workspace/scripts/translates/dictionaries/{$modulo}_{$lang}.json`\n";

                    $totalFiles++;
                    $jsonCount++;
                }
            }
        }
    }
}

// Update statistics
$analiseContent = str_replace('{total}', $totalFiles, $analiseContent);
$analiseContent = str_replace('{json_count}', $jsonCount, $analiseContent);

// Save analysis file
file_put_contents($analiseFile, $analiseContent);

echo "\n✅ Process completed!\n";
echo "Total files generated: $totalFiles\n";
echo "Analysis file: $analiseFile\n";

?>
