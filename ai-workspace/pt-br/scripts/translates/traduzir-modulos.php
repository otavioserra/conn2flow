<?php

// Script para traduzir modulos_en.json
$file = __DIR__ . '/dictionaries/modulos_en.json';

if (!file_exists($file)) {
    die("Arquivo não encontrado: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Erro ao decodificar JSON\n");
}

$translations = [
    "Adicionar Variável" => "Add Variable",
    "Linguagem" => "Language",
    "Ícone" => "Icon",
    "Selecione um grupo" => "Select a group",
    "Ícone Ancorado" => "Anchored Icon",
    "Visualizar no Menu Principal" => "View in Main Menu",
    "Selecione para visualizar no menu principal" => "Select to view in main menu",
    "Título" => "Title",
    "Identificador" => "Identifier",
    "Value" => "Value",
    "Selecione um plugin" => "Select a plugin",
    "None" => "None",
    "Variables" => "Variables",
    "Host" => "Host"
];

foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Traduzido: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Tradução concluída!\n";

?>