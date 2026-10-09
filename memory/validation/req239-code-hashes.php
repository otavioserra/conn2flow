<?php
/** Read-only companion to project:verify: normalize CRLF in both source and target. */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Conn2Flow\\Cli\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/../../cli/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
$root = dirname(__DIR__, 2);
$resolved = (new Conn2Flow\Cli\Support\ProjectEnvironmentResolver($root))->resolve('conn2flow-site-local');
$class = Conn2Flow\Cli\Commands\ProjectVerifyCommand::class;
$command = new $class($root);
$remote = (new ReflectionMethod($class, 'mapaRemoto'))->invoke($command, $resolved, new Conn2Flow\Cli\Console\Output());
if (!$remote) {
    exit(1);
}
$source = array_merge($class::mapaLocal($root . '/gestor'), $class::mapaLocal($resolved['gestorPath']));
$raw = $class::comparar($source, $remote);
$transport = new Conn2Flow\Cli\Support\SshRemoteTransport($resolved['ssh'], $resolved['config']);
$php = 'foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(".",FilesystemIterator::SKIP_DOTS)) as $f){if(in_array(strtolower($f->getExtension()),["php","js","sh"]))echo hash("sha256",str_replace("\r\n","\n",file_get_contents($f->getPathname())))."  ".$f->getPathname()."\n";}';
// Base64 keeps PHP quotes intact across Windows -> SSH -> POSIX shell. No remote file is written.
$process = proc_open($transport->buildRemoteCommand(['php', '-r', "eval(base64_decode('" . base64_encode($php) . "'));"], $transport->remotePath()), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) {
    exit(1);
}
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) {
    fwrite(STDERR, $stderr);
    exit(1);
}
$canonical = $class::comparar($source, $class::lerSha256sum($stdout));
file_put_contents(__DIR__ . '/evidence-req239/code-hashes.json', json_encode(['checked_at' => gmdate('c'), 'raw' => $raw, 'canonical_lf' => $canonical], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($canonical) . "\n";
exit($canonical['comparados'] > 0 && !$canonical['diferentes'] && !$canonical['sobras'] ? 0 : 1);
