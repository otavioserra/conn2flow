<?php

// Script to translate admin-plugins_en.json
$file = __DIR__ . '/dictionaries/admin-plugins_en.json';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$translations = [
    "Teste do Sistema de Descoberta Automática" => "Automatic Discovery System Test",
    "Origem" => "Origin",
    "Reference" => "Reference",
    "Branch/Tag" => "Branch/Tag",
    "Credencial Ref" => "Credential Ref",
    "Versão Instalada" => "Installed Version",
    "Checksum" => "Checksum",
    "Status Execução" => "Execution Status",
    "Última Atualização" => "Last Update"
];

foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Translated: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Translation completed!\n";

?>
