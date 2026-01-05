<?php

// Script to translate admin-categorias_en.json
$file = __DIR__ . '/dictionaries/admin-categorias_en.json';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$translations = [
    "Name" => "Name",
    "Name" => "Name",
    "Module" => "Module",
    "All modules" => "All modules",
    "Child Categories" => "Child Categories",
    "Nenhuma categoria filho definida!" => "No child categories defined!",
    "Clique para Adicionar Categoria Filho." => "Click to Add Child Category.",
    "Add" => "Add",
    "Categoria Pai" => "Parent Category",
    "Categoria Pai não encontrada. Não é possível adicionar categoria filho sem um pai referente." => "Parent Category not found. It is not possible to add child category without a corresponding parent.",
    "Nenhum(a)" => "None",
    "Categoria Localização" => "Category Location",
    "Raiz" => "Root",
    "Plugin" => "Plugin",
    "Plugin" => "Plugin"
];

// Translate variables
foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Translated variable: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Translation completed!\n";

?>
