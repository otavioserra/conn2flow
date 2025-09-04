<?php
// Atualiza a versão no manifest.json do plugin
$manifestPath = __DIR__ . '/../../../plugin/manifest.json';
if (!file_exists($manifestPath)) {
    fwrite(STDERR, "Manifest não encontrado: $manifestPath\n");
    exit(1);
}
$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    fwrite(STDERR, "Falha ao parsear manifest.json\n");
    exit(1);
}
$type = $argv[1] ?? 'patch';
list($maj,$min,$pat) = array_map('intval', explode('.', $manifest['versao']));
switch($type){
    case 'major': $maj++; $min=0; $pat=0; break;
    case 'minor': $min++; $pat=0; break;
    default: $pat++; break;
}
$manifest['versao'] = $new = "$maj.$min.$pat";
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo $new;
