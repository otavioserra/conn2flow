<?php

/**
 * Script para aplicar traduções dos dicionários de volta aos arquivos JSON dos módulos
 */

// Caminhos
$modulosPath = __DIR__ . '/../../../gestor/modulos/';
$dictionariesPath = __DIR__ . '/dictionaries/';

echo "🔄 Aplicando traduções dos dicionários aos módulos...\n\n";

// Percorrer módulos
$modulos = scandir($modulosPath);
$modulos = array_filter($modulos, function($item) {
    return $item !== '.' && $item !== '..' && is_dir(__DIR__ . '/../../../gestor/modulos/' . $item);
});

foreach ($modulos as $modulo) {
    $jsonFile = $modulosPath . $modulo . '/' . $modulo . '.json';
    $dictFilePt = $dictionariesPath . $modulo . '_pt-br.json';
    $dictFileEn = $dictionariesPath . $modulo . '_en.json';

    if (file_exists($jsonFile)) {
        echo "Processando módulo: $modulo\n";

        // Carregar arquivo original
        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            echo "  ❌ Erro ao decodificar JSON: $jsonFile\n";
            continue;
        }

        $modified = false;

        // Aplicar traduções do dicionário pt-br se existir
        if (file_exists($dictFilePt)) {
            $dictPt = json_decode(file_get_contents($dictFilePt), true);
            if ($dictPt) {
                // Aplicar variables pt-br
                if (isset($dictPt['variables']) && isset($data['resources']['pt-br']['variables'])) {
                    foreach ($dictPt['variables'] as $varId => $translatedValue) {
                        foreach ($data['resources']['pt-br']['variables'] as &$variable) {
                            if ($variable['id'] === $varId) {
                                if ($variable['value'] !== $translatedValue) {
                                    $variable['value'] = $translatedValue;
                                    $modified = true;
                                }
                                break;
                            }
                        }
                    }
                }
                // Aplicar layouts pt-br
                if (isset($dictPt['layouts']) && isset($data['resources']['pt-br']['layouts'])) {
                    $data['resources']['pt-br']['layouts'] = $dictPt['layouts'];
                    $modified = true;
                }
                // Aplicar pages pt-br
                if (isset($dictPt['pages']) && isset($data['resources']['pt-br']['pages'])) {
                    $data['resources']['pt-br']['pages'] = $dictPt['pages'];
                    $modified = true;
                }
                // Aplicar components pt-br
                if (isset($dictPt['components']) && isset($data['resources']['pt-br']['components'])) {
                    $data['resources']['pt-br']['components'] = $dictPt['components'];
                    $modified = true;
                }
                echo "  ✅ Aplicadas traduções pt-br\n";
            }
        }

        // Aplicar traduções do dicionário en se existir
        if (file_exists($dictFileEn)) {
            $dictEn = json_decode(file_get_contents($dictFileEn), true);
            if ($dictEn) {
                // Aplicar variables en
                if (isset($dictEn['variables']) && isset($data['resources']['en']['variables'])) {
                    foreach ($dictEn['variables'] as $varId => $translatedValue) {
                        foreach ($data['resources']['en']['variables'] as &$variable) {
                            if ($variable['id'] === $varId) {
                                if ($variable['value'] !== $translatedValue) {
                                    $variable['value'] = $translatedValue;
                                    $modified = true;
                                }
                                break;
                            }
                        }
                    }
                }
                // Aplicar layouts en
                if (isset($dictEn['layouts']) && isset($data['resources']['en']['layouts'])) {
                    $data['resources']['en']['layouts'] = $dictEn['layouts'];
                    $modified = true;
                }
                // Aplicar pages en
                if (isset($dictEn['pages']) && isset($data['resources']['en']['pages'])) {
                    $data['resources']['en']['pages'] = $dictEn['pages'];
                    $modified = true;
                }
                // Aplicar components en
                if (isset($dictEn['components']) && isset($data['resources']['en']['components'])) {
                    $data['resources']['en']['components'] = $dictEn['components'];
                    $modified = true;
                }
                echo "  ✅ Aplicadas traduções en\n";
            }
        }

        // Salvar arquivo se foi modificado
        if ($modified) {
            $newJsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($jsonFile, $newJsonContent);
            echo "  💾 Arquivo salvo: $modulo.json\n";
        } else {
            echo "  ℹ️  Nenhuma modificação necessária\n";
        }

        echo "\n";
    }
}

echo "✅ Processo concluído! Traduções aplicadas aos módulos.\n";

?>
