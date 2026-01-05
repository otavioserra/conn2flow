<?php

// Script para duplicar registros e criar versões em inglês
$files = [
    __DIR__ . '/../../../../gestor/db/data/ModulosData.json',
    __DIR__ . '/../../../../gestor/db/data/ModulosGruposData.json',
    __DIR__ . '/../../../../gestor/db/data/ModulosOperacoesData.json',
    __DIR__ . '/../../../../gestor/db/data/UsuariosPerfisData.json'
];

// Traduções para nomes
$translations = [
    // Módulos
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

    // Grupos de módulos
    "Bibliotecas" => "Libraries",
    "Administração Gestor" => "Manager Administration",
    "Geral" => "General",
    "Administração Usuários" => "Users Administration",
    "Administração Sistema" => "System Administration",

    // Operações
    "Modificar Permissão da Página" => "Modify Page Permission",

    // Perfis
    "Administradores" => "Administrators",
    "Consumidores" => "Consumers"
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Arquivo não encontrado: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if ($data === null) {
        echo "Erro ao decodificar JSON: $file\n";
        continue;
    }

    $newRecords = [];

    // Duplicar cada registro para criar versão em inglês
    foreach ($data as $record) {
        // Manter o registro original em pt-br
        $newRecords[] = $record;

        // Criar versão em inglês
        $englishRecord = $record;
        $englishRecord['language'] = 'en';

        // Traduzir o nome se existir tradução
        if (isset($record['nome']) && isset($translations[$record['nome']])) {
            $englishRecord['nome'] = $translations[$record['nome']];
        }

        $newRecords[] = $englishRecord;
    }

    $newContent = json_encode($newRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $newContent);

    echo "✅ Registros duplicados com versões em inglês em: " . basename($file) . "\n";
}

echo "✅ Processo concluído!\n";