<?php

// Script to translate dashboard_en.json
$file = __DIR__ . '/dictionaries/dashboard_en.json';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$translations = [
    "Sair" => "Logout",
    "Atualização Disponível" => "Update Available",
    "Há uma atualização disponível. Deseja atualizar agora?" => "There is an update available. Do you want to update now?",
    "Atualizar Agora" => "Update Now",
    "Você será redirecionado para a tela de atualizações..." => "You will be redirected to the updates screen...",
    "Não Atualizar" => "Don't Update",
    "O sistema armazenará sua escolha. Se mudar de ideia acesse: <b>#url#</b>." => "The system will store your choice. If you change your mind, access: <b>#url#</b>."
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
