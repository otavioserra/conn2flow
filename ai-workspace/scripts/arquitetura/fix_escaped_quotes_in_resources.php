<?php
/**
 * Varrre todos os resources (global e de módulos) e converte \" para ",
 * bem como outras sequências escapadas (\n, \r, \\) usando stripcslashes.
 * Aplica-se a arquivos .html e .css dentro de resources/pt-br/{pages,layouts,components}.
 */

echo "=== CORREÇÃO DE ASPAS ESCAPADAS EM RESOURCES ===\n\n";

$basePath = "c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow";
$targets = [
    "$basePath/gestor/resources/pt-br/pages",
    "$basePath/gestor/resources/pt-br/layouts",
    "$basePath/gestor/resources/pt-br/components",
    "$basePath/gestor/modulos",
];

function shouldProcessFile($file)
{
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['html', 'css'])) return false;
    // Evita mexer fora dos diretórios de resources
    return (strpos($file, '/resources/pt-br/pages/') !== false) ||
           (strpos($file, '/resources/pt-br/layouts/') !== false) ||
           (strpos($file, '/resources/pt-br/components/') !== false) ||
           (strpos($file, '\\resources\\pt-br\\pages\\') !== false) ||
           (strpos($file, '\\resources\\pt-br\\layouts\\') !== false) ||
           (strpos($file, '\\resources\\pt-br\\components\\') !== false);
}

$processed = 0;
$modified = 0;
$skipped = 0;

foreach ($targets as $target) {
    if (!is_dir($target)) continue;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileinfo) {
        $file = $fileinfo->getPathname();
        if (!shouldProcessFile($file)) { $skipped++; continue; }
        $processed++;
        $content = file_get_contents($file);
        if ($content === false) continue;
        // Substitui explicitamente \" por " (não altera outras sequências)
        $new = str_replace('\"', '"', $content);
        // Normaliza quebras de linha somente se houver alteração
        if ($new !== $content) {
            $new = str_replace(["\r\n", "\r"], "\n", $new);
        }
        if ($new !== $content) {
            file_put_contents($file, $new);
            $modified++;
            echo "Corrigido: $file\n";
        }
    }
}

echo "\n=== RESUMO ===\n";
$summary = [
    'processed' => $processed,
    'modified' => $modified,
    'skipped' => $skipped,
];
print_r($summary);

echo "\nConcluído.\n";
?>
