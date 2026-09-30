<?php
/**
 * Atualização do sistema disparada pela API, em segundo plano — req-201 / BATCH-209.
 *
 * Biblioteca pura (sem Gestor): monta os argumentos do atualizador do CLI a partir das opções da API (lista
 * branca), acha o PHP de linha de comando, dispara em segundo plano e lê o estado de uma execução pelo log e
 * pelo código de saída. O atualizador que roda é o mesmo do CLI (trava, snapshot, dump, verificação e volta
 * automática, req-197/198), então o comportamento é o validado nele.
 */

/** Opções aceitas pela API → flag do atualizador. `true` = flag sem valor; `'valor'` = `--flag=valor`. */
const ATUALIZACOES_EXECUCAO_OPCOES = [
    'tag' => 'valor', 'only_files' => true, 'only_db' => true, 'no_db' => true, 'dry_run' => true, 'backup' => true,
    'no_verify' => true, 'no_health' => true, 'no_rollback' => true, 'health_url' => 'valor', 'health_ip' => 'valor',
    'force_all' => true, 'tables' => 'valor', 'logs_retention_days' => 'valor', 'local_artifact' => true, 'debug' => true,
];

/** Significado dos códigos de saída do atualizador (`atualizacoes-sistema.php`). */
const ATUALIZACOES_EXECUCAO_CODIGOS = [
    0 => 'success', 1 => 'error', 2 => 'error-download', 3 => 'error-extraction', 4 => 'error-env', 5 => 'error-database',
    6 => 'rolled_back', 7 => 'error-integrity', 8 => 'locked',
];

/** Id de execução válido (`run-<data>-<sufixo>`). */
function atualizacoes_execucao_id_valido(string $id): bool {
    return (bool)preg_match('/^run-\d{8}-\d{6}-[a-f0-9]{4,8}$/', $id);
}

/** Novo id de execução. */
function atualizacoes_execucao_novo_id(): string {
    return 'run-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
}

/** Pasta das execuções (`temp/atualizacoes/runs/`). */
function atualizacoes_execucao_pasta(string $base): string {
    return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'runs' . DIRECTORY_SEPARATOR;
}

/**
 * Argumentos do atualizador a partir das opções da API. Só entram as opções da lista branca; valores são
 * limpos (tag, URL, IP, tabelas, dias). Opção desconhecida ou valor inválido vai para `recusadas`.
 *
 * @return array ['argv' => string[], 'recusadas' => string[]]
 */
function atualizacoes_execucao_argv(array $opcoes, string $dominio): array {
    $argv = ['--domain=' . preg_replace('/[^A-Za-z0-9.-]/', '', $dominio), '--log-stdout'];
    $recusadas = [];
    foreach ($opcoes as $k => $v) {
        $k = (string)$k;
        if (!isset(ATUALIZACOES_EXECUCAO_OPCOES[$k])) { $recusadas[] = $k; continue; }
        $flag = '--' . str_replace('_', '-', $k);
        if (ATUALIZACOES_EXECUCAO_OPCOES[$k] === true) {
            if (filter_var($v, FILTER_VALIDATE_BOOLEAN)) $argv[] = $flag;
            continue;
        }
        $v = trim((string)$v);
        $ok = false;
        switch ($k) {
            case 'tag': $ok = (bool)preg_match('/^[A-Za-z0-9._-]{1,80}$/', $v); break;
            // Só caracteres de URL simples (sem `&`, `;`, aspas, espaço): o valor vai para uma linha de comando.
            case 'health_url': $ok = (bool)filter_var($v, FILTER_VALIDATE_URL) && preg_match('#^https?://[A-Za-z0-9.:/_%\[\]-]+$#', $v); break;
            case 'health_ip': $ok = (bool)filter_var($v, FILTER_VALIDATE_IP); break;
            case 'tables': $ok = (bool)preg_match('/^[a-z0-9_]+(,[a-z0-9_]+)*$/', $v); break;
            case 'logs_retention_days': $ok = ctype_digit($v) && (int)$v <= 3650; break;
        }
        if ($ok) $argv[] = $flag . '=' . $v; else $recusadas[] = $k;
    }
    return ['argv' => $argv, 'recusadas' => $recusadas];
}

/**
 * PHP de linha de comando. Dentro do PHP-FPM, `PHP_BINARY` é o próprio FPM; procura, na ordem:
 * `$preferido` (ex.: `ATUALIZACOES_PHP_CLI`), `php<maior>.<menor>` e `php` na pasta dos binários do PHP,
 * e por fim `php` no PATH.
 */
function atualizacoes_execucao_php_cli(?string $preferido = null): string {
    if ($preferido !== null && $preferido !== '' && is_executable($preferido)) return $preferido;
    $dir = defined('PHP_BINDIR') ? PHP_BINDIR : '/usr/bin';
    foreach ([$dir . '/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, $dir . '/php'] as $c) {
        if (is_executable($c)) return $c;
    }
    if (PHP_SAPI === 'cli' && PHP_BINARY !== '') return PHP_BINARY;
    return 'php';
}

/**
 * Dispara o atualizador em segundo plano, desligado da requisição (`nohup` + `setsid` quando existe). A
 * saída vai para `<id>.log` e o código de saída para `<id>.exit`; `<id>.json` guarda o pedido.
 *
 * @return array ['ok' => bool, 'erro' => string, 'id' => string, 'log' => string]
 */
function atualizacoes_execucao_disparar(string $base, string $id, array $argv, array $meta, string $php): array {
    if (!function_exists('proc_open')) return ['ok' => false, 'erro' => 'proc_open indisponível no PHP do servidor', 'id' => $id, 'log' => ''];
    $pasta = atualizacoes_execucao_pasta($base);
    if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) return ['ok' => false, 'erro' => 'não foi possível criar ' . $pasta, 'id' => $id, 'log' => ''];
    $log = $pasta . $id . '.log';
    $exit = $pasta . $id . '.exit';
    @file_put_contents($pasta . $id . '.json', json_encode($meta + ['id' => $id, 'iniciado_em' => date('c'), 'argv' => $argv], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $script = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'atualizacoes-sistema.php';
    $cmd = 'cd ' . escapeshellarg(rtrim($base, '/\\')) . ' && ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' '
        . implode(' ', array_map('escapeshellarg', $argv)) . ' > ' . escapeshellarg($log) . ' 2>&1; echo $? > ' . escapeshellarg($exit);
    $prefixo = is_executable('/usr/bin/setsid') ? 'setsid ' : '';
    $fundo = 'nohup ' . $prefixo . 'sh -c ' . escapeshellarg($cmd) . ' > /dev/null 2>&1 &';
    // Pipes, não `['file', '/dev/null']`: com `open_basedir` (HestiaCP), abrir `/dev/null` pelo PHP falha. O
    // redirecionamento para /dev/null fica com o shell, e o processo em segundo plano não segura os pipes.
    $proc = @proc_open(['/bin/sh', '-c', $fundo], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($proc)) {
        // Sem isto a execução ficaria "running" para sempre no run-status.
        $erro = error_get_last();
        @file_put_contents($log, 'ERRO DISPARO: não foi possível iniciar o atualizador' . ($erro ? ' (' . $erro['message'] . ')' : '') . "\n");
        @file_put_contents($exit, "1\n");
        return ['ok' => false, 'erro' => 'não foi possível iniciar o atualizador', 'id' => $id, 'log' => $log];
    }
    fclose($pipes[0]);
    stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($proc);
    return ['ok' => true, 'erro' => '', 'id' => $id, 'log' => $log];
}

/**
 * Estado de uma execução a partir do log e do código de saída (sem o código: ainda rodando).
 *
 * @return array ['status' => string, 'codigo' => int|null, 'snapshot' => string|null, 'saude' => string|null,
 *                'rollback' => string|null, 'erros' => string[], 'fim_do_log' => string[]]
 */
function atualizacoes_execucao_estado(string $log, ?string $exit): array {
    $codigo = ($exit !== null && trim($exit) !== '' && ctype_digit(trim($exit))) ? (int)trim($exit) : null;
    $snapshot = null; $saude = null; $rollback = null; $erros = [];
    foreach (preg_split('/\r\n|\n|\r/', $log) as $l) {
        if (preg_match('/Snapshot (exec-[A-Za-z0-9_-]+)/', $l, $m)) $snapshot = $m[1];
        if (strpos($l, 'Verificação pós-atualização') !== false) $saude = trim(preg_replace('/^\[[^\]]*\]\[[A-Z]+\]\s*/', '', $l));
        if (strpos($l, 'Rollback automático') !== false || strpos($l, 'ROLLBACK:') === 0) $rollback = trim(preg_replace('/^\[[^\]]*\]\[[A-Z]+\]\s*/', '', $l));
        if (preg_match('/^(ERRO|ARGUMENTO INVÁLIDO)|\[ERROR\]/u', $l)) $erros[] = trim($l);
    }
    $linhas = array_values(array_filter(preg_split('/\r\n|\n|\r/', $log), function ($l) { return trim($l) !== ''; }));
    return [
        'status' => $codigo === null ? 'running' : (ATUALIZACOES_EXECUCAO_CODIGOS[$codigo] ?? 'error'),
        'codigo' => $codigo,
        'snapshot' => $snapshot,
        'saude' => $saude,
        'rollback' => $rollback,
        'erros' => array_slice($erros, -10),
        'fim_do_log' => array_slice($linhas, -40),
    ];
}
