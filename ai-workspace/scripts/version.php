<?php

// O caminho agora sobe um nível ('..') para encontrar o config.php na raiz do gestor.
$configPath = __DIR__ . '/../../gestor/config.php';
$lines = file($configPath); // Lê o arquivo como um array de linhas, preservando a formatação

if ($lines === false) {
    fwrite(STDERR, "Erro: Não foi possível ler o arquivo config.php em: $configPath\n");
    exit(1);
}

$updateType = $argv[1] ?? 'patch'; // 'patch', 'minor', 'major'

$versionUpdated = false;
$newVersion = '';

foreach ($lines as $i => $line) {
    // Usa strpos para uma busca flexível da linha que contém a definição da versão
    if (strpos($line, "\$_GESTOR['versao']") !== false) {
        // Uma vez que a linha é encontrada, usa um regex simples para extrair o número da versão
        $pattern = "/(')(\d+\.\d+\.\d+)(')/";
        
        if (preg_match($pattern, $line, $matches)) {
            $currentVersion = $matches[2];
            list($major, $minor, $patch) = array_map('intval', explode('.', $currentVersion));

            switch ($updateType) {
                case 'major':
                    $major++;
                    $minor = 0;
                    $patch = 0;
                    break;
                case 'minor':
                    $minor++;
                    $patch = 0;
                    break;
                case 'patch':
                default:
                    $patch++;
                    break;
            }
         
            $newVersion = "$major.$minor.$patch";
         
            // Substitui apenas o número da versão, preservando o resto da linha
            $lines[$i] = preg_replace($pattern, '${1}' . $newVersion . '${3}', $line, 1);
            $versionUpdated = true;
            
            // Para o loop pois a versão já foi encontrada e atualizada
            break;
        }
    }
}
 
if ($versionUpdated) {
    file_put_contents($configPath, implode('', $lines));
    // Imprime a nova versão para que o script de release possa capturá-la
    echo $newVersion;
} else {
    fwrite(STDERR, "Erro: Padrão de versão não encontrado no config.php.\n");
    exit(1);
}