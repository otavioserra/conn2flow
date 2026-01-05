<?php

/**
 * Script to apply dictionary translations back to module JSON files
 */

// Paths
$modulosPath = __DIR__ . '/../../../gestor/modulos/';
$dictionariesPath = __DIR__ . '/dictionaries/';

echo "🔄 Applying dictionary translations to modules...\n\n";

// Iterate modules
$modulos = scandir($modulosPath);
$modulos = array_filter($modulos, function($item) {
    return $item !== '.' && $item !== '..' && is_dir(__DIR__ . '/../../../gestor/modulos/' . $item);
});

foreach ($modulos as $modulo) {
    $jsonFile = $modulosPath . $modulo . '/' . $modulo . '.json';
    $dictFilePt = $dictionariesPath . $modulo . '_pt-br.json';
    $dictFileEn = $dictionariesPath . $modulo . '_en.json';

    if (file_exists($jsonFile)) {
        echo "Processing module: $modulo\n";

        // Load original file
        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if ($data === null) {
            echo "  ❌ Error decoding JSON: $jsonFile\n";
            continue;
        }

        $modified = false;

        // Apply pt-br dictionary translations if exists
        if (file_exists($dictFilePt)) {
            $dictPt = json_decode(file_get_contents($dictFilePt), true);
            if ($dictPt) {
                // Apply pt-br variables
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
                // Apply pt-br layouts
                if (isset($dictPt['layouts']) && isset($data['resources']['pt-br']['layouts'])) {
                    $data['resources']['pt-br']['layouts'] = $dictPt['layouts'];
                    $modified = true;
                }
                // Apply pt-br pages
                if (isset($dictPt['pages']) && isset($data['resources']['pt-br']['pages'])) {
                    $data['resources']['pt-br']['pages'] = $dictPt['pages'];
                    $modified = true;
                }
                // Apply pt-br components
                if (isset($dictPt['components']) && isset($data['resources']['pt-br']['components'])) {
                    $data['resources']['pt-br']['components'] = $dictPt['components'];
                    $modified = true;
                }
                echo "  ✅ Applied pt-br translations\n";
            }
        }

        // Apply en dictionary translations if exists
        if (file_exists($dictFileEn)) {
            $dictEn = json_decode(file_get_contents($dictFileEn), true);
            if ($dictEn) {
                // Apply en variables
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
                // Apply en layouts
                if (isset($dictEn['layouts']) && isset($data['resources']['en']['layouts'])) {
                    $data['resources']['en']['layouts'] = $dictEn['layouts'];
                    $modified = true;
                }
                // Apply en pages
                if (isset($dictEn['pages']) && isset($data['resources']['en']['pages'])) {
                    $data['resources']['en']['pages'] = $dictEn['pages'];
                    $modified = true;
                }
                // Apply en components
                if (isset($dictEn['components']) && isset($data['resources']['en']['components'])) {
                    $data['resources']['en']['components'] = $dictEn['components'];
                    $modified = true;
                }
                echo "  ✅ Applied en translations\n";
            }
        }

        // Save file if modified
        if ($modified) {
            $newJsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($jsonFile, $newJsonContent);
            echo "  💾 File saved: $modulo.json\n";
        } else {
            echo "  ℹ️  No modification needed\n";
        }

        echo "\n";
    }
}

echo "✅ Process completed! Translations applied to modules.\n";

?>
