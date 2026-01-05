<?php

// Script to duplicate records and create English versions
$files = [
    __DIR__ . '/../../../../gestor/db/data/ModulosData.json',
    __DIR__ . '/../../../../gestor/db/data/ModulosGruposData.json',
    __DIR__ . '/../../../../gestor/db/data/ModulosOperacoesData.json',
    __DIR__ . '/../../../../gestor/db/data/UsuariosPerfisData.json'
];

// Translations for names
$translations = [
    // Modules
    "Módulos" => "Modules",
    "Módulos Grupos" => "Modules Groups",
    "Admin Layouts" => "Admin Layouts",
    "Admin Páginas" => "Admin Pages",
    "Admin Arquivos" => "Admin Files",
    "Admin Categorias" => "Admin Categories",
    "Admin Componentes" => "Admin Components",
    "Admin Plugins" => "Admin Plugins",
    "Admin Atualizações" => "Admin Updates",
    "Admin Environment" => "Admin Environment",
    "Dashboard" => "Dashboard",
    "Usuários" => "Users",
    "Usuários Perfis" => "Users Profiles",
    "Perfil Usuário" => "User Profile",
    "Interface" => "Interface",
    "Módulos Operações" => "Modules Operations",
    "Contatos" => "Contacts",

    // Module groups
    "Bibliotecas" => "Libraries",
    "Administração Gestor" => "Manager Administration",
    "Geral" => "General",
    "Administração Usuários" => "Users Administration",
    "Administração Sistema" => "System Administration",

    // Operations
    "Modificar Permissão da Página" => "Modify Page Permission",

    // Profiles
    "Administradores" => "Administrators",
    "Consumidores" => "Consumers"
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if ($data === null) {
        echo "Error decoding JSON: $file\n";
        continue;
    }

    $newRecords = [];

    // Duplicate each record to create English version
    foreach ($data as $record) {
        // Keep original record in pt-br
        $newRecords[] = $record;

        // Create English version
        $englishRecord = $record;
        $englishRecord['language'] = 'en';

        // Translate name if translation exists
        if (isset($record['nome']) && isset($translations[$record['nome']])) {
            $englishRecord['nome'] = $translations[$record['nome']];
        }

        $newRecords[] = $englishRecord;
    }

    $newContent = json_encode($newRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $newContent);

    echo "✅ Records duplicated with English versions in: " . basename($file) . "\n";
}

echo "✅ Process completed!\n";
