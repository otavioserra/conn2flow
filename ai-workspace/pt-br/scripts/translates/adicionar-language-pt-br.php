<?php

// Script para adicionar campo language aos arquivos de dados
$files = [
    __DIR__ . '/../../../gestor/db/data/ModulosData.json',
    __DIR__ . '/../../../gestor/db/data/ModulosGruposData.json',
    __DIR__ . '/../../../gestor/db/data/ModulosOperacoesData.json',
    __DIR__ . '/../../../gestor/db/data/UsuariosPerfisData.json'
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

    // Adicionar campo language a todos os registros
    foreach ($data as &$record) {
        $record['language'] = 'pt-br';
    }

    $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $newContent);

    echo "✅ Campo 'language' adicionado em: " . basename($file) . "\n";
}

echo "✅ Processo concluído!\n";