<?php

// Script to add language field to data files
$files = [
    __DIR__ . '/../../../gestor/db/data/ModulosData.json',
    __DIR__ . '/../../../gestor/db/data/ModulosGruposData.json',
    __DIR__ . '/../../../gestor/db/data/ModulosOperacoesData.json',
    __DIR__ . '/../../../gestor/db/data/UsuariosPerfisData.json'
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

    // Add language field to all records
    foreach ($data as &$record) {
        $record['language'] = 'pt-br';
    }

    $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $newContent);

    echo "✅ 'language' field added in: " . basename($file) . "\n";
}

echo "✅ Process completed!\n";
