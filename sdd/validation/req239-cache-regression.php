<?php
// Demonstra o defeito no fingerprint original sem alterar o checkout compartilhado.
declare(strict_types=1);
require_once __DIR__ . '/../../gestor/controladores/agents/arquitetura/tailwind-recursos.php';
$process = proc_open(['git', 'show', 'HEAD:gestor/controladores/agents/arquitetura/tailwind-recursos.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
if (!is_resource($process)) exit(1);
$original = stream_get_contents($pipes[1]);
fclose($pipes[1]); fclose($pipes[2]);
if (proc_close($process) !== 0) exit(1);
if (!preg_match('/function tailwind_recursos_fingerprint\(.*?(?=function tailwind_recursos_output_valido)/s', $original, $match)) exit(1);
eval(str_replace('function tailwind_recursos_fingerprint(', 'function req239_fingerprint_antes(', $match[0]));
$directory = sys_get_temp_dir() . '/req239-cache-' . bin2hex(random_bytes(6));
mkdir($directory);
$html = $directory . '/card.html'; $css = $directory . '/input.css';
try {
    file_put_contents($html, "<div>\ncard\n</div>\n");
    file_put_contents($css, "@import \"tailwindcss\";\n");
    $resource = ['html' => $html, 'sources' => [], 'layout' => false, 'safelist' => []];
    $beforeLf = req239_fingerprint_antes($resource, hash_file('sha256', $css), '4.3.3');
    $afterLf = tailwind_recursos_fingerprint($resource, tailwind_recursos_hash_arquivo($css), '4.3.3');
    foreach ([$html, $css] as $file) file_put_contents($file, str_replace("\n", "\r\n", file_get_contents($file)));
    $beforeCrlf = req239_fingerprint_antes($resource, hash_file('sha256', $css), '4.3.3');
    $afterCrlf = tailwind_recursos_fingerprint($resource, tailwind_recursos_hash_arquivo($css), '4.3.3');
    $evidence = ['old_recompiles_on_crlf' => $beforeLf !== $beforeCrlf, 'new_preserves_cache' => $afterLf === $afterCrlf];
    file_put_contents(__DIR__ . '/evidence-req239/cache-regression.json', json_encode($evidence, JSON_PRETTY_PRINT) . "\n");
    echo json_encode($evidence) . "\n";
    $exit = $evidence['old_recompiles_on_crlf'] && $evidence['new_preserves_cache'] ? 0 : 1;
} finally {
    unlink($html); unlink($css); rmdir($directory);
}
exit($exit);
