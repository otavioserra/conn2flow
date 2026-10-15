<?php
/**
 * Trava de deploy por ambiente — req-197 / BATCH-201 (BL-028, fase 1).
 *
 * Um arquivo de trava por ambiente impede dois deploys ao mesmo tempo (atualização do sistema, deploy
 * de projeto por API e pipeline `project:update-all`). Biblioteca PURA: não depende do Gestor, porque
 * roda no atualizador independente, na API e no CLI.
 *
 * - Criação atômica (`fopen` com `x`): só um processo consegue criar o arquivo.
 * - O conteúdo diz quem está rodando: dono, execução, máquina, PID, início e validade (TTL).
 * - Trava vencida (processo que morreu sem liberar) é assumida: o arquivo é renomeado de forma atômica
 *   para um nome único e a criação é tentada de novo; quem assumiu recebe `stale` para registrar.
 * - Só quem tem o token libera; `deploy_lock_refresh` estende a validade em execuções longas.
 */

/** Validade padrão de uma trava (segundos). */
const DEPLOY_LOCK_TTL = 7200;

/**
 * Lê a trava atual.
 *
 * @return array|null Conteúdo da trava ou null quando não existe ou está ilegível.
 */
function deploy_lock_read(string $arquivo): ?array {
    if (!is_file($arquivo)) return null;
    $dados = json_decode((string)@file_get_contents($arquivo), true);
    return is_array($dados) ? $dados : null;
}

/** A trava está vencida? (sem validade legível conta como vencida; dono morto também — req-277) */
function deploy_lock_expired(?array $trava, ?int $agora = null): bool {
    if (!$trava) return true;
    if ((int)($trava['expires_at'] ?? 0) <= ($agora ?? time())) return true;
    return deploy_lock_orphan($trava);
}

/**
 * req-277: o processo dono morreu? Só decide no mesmo servidor e em Linux (`/proc`): em outra máquina
 * ou sem `/proc` (Windows, macOS) não há como saber, e a trava segue até vencer.
 *
 * Na atualização pelo painel o PID é o do worker do PHP-FPM, que sobrevive a um erro fatal — esse caso
 * é coberto pela liberação no desligamento da etapa (`atualizacoes_web_trava_no_fatal`), não por aqui.
 *
 * @param array       $trava   Conteúdo da trava.
 * @param string|null $host    Servidor atual (padrão: `gethostname()`).
 * @param string      $proc    Raiz do procfs (teste).
 */
function deploy_lock_orphan(array $trava, ?string $host = null, string $proc = '/proc', ?callable $sinal = null): bool {
    $pid = (int)($trava['pid'] ?? 0);
    $dono = (string)($trava['host'] ?? '');
    $host = $host ?? (function_exists('gethostname') ? (string)gethostname() : '');
    if ($pid <= 0 || $dono === '' || $dono !== $host) return false;
    // req-280: no HestiaCP o PHP-FPM roda com `open_basedir` e não enxerga o `/proc` (temp.conn2flow.com,
    // 15/10): a trava de um processo morto esperava as 2 h. `kill(pid, 0)` não depende do `/proc`; o
    // worker dono é do mesmo usuário, então "não existe" (ESRCH) é a única resposta que conta como morto.
    $sinal = $sinal ?? deploy_lock_sinal_padrao();
    if ($sinal !== null) {
        $resposta = $sinal($pid);
        if ($resposta !== null) return $resposta === 'morto';
    }
    if (!@is_dir($proc . '/self')) return false;
    return !@is_dir($proc . '/' . $pid);
}

/**
 * req-280: consulta "o processo existe?" pela extensão `posix`. Devolve 'vivo', 'morto' ou null quando
 * não dá para saber (sem `posix`, ou sem permissão para sinalizar o processo — ele existe, mas é de outro
 * usuário, então não conta como morto).
 */
function deploy_lock_sinal_padrao(): ?callable {
    if (!function_exists('posix_kill') || !function_exists('posix_get_last_error')) return null;
    return function (int $pid): ?string {
        if (@posix_kill($pid, 0)) return 'vivo';
        return posix_get_last_error() === 3 ? 'morto' : null; // 3 = ESRCH (processo inexistente)
    };
}

/**
 * Tenta pegar a trava.
 *
 * @param string $arquivo Caminho do arquivo de trava (a pasta é criada se faltar).
 * @param array  $dono    ['owner' => quem (ex.: 'update-system', 'api-project-update', 'pipeline'),
 *                         'execution' => id da execução (opcional), 'detail' => texto livre (opcional)].
 * @param int    $ttl     Validade em segundos.
 * @return array ['ok' => true, 'token' => string, 'lock' => array, 'stale' => array|null]
 *               ou ['ok' => false, 'holder' => array|null, 'error' => 'busy' | 'io']
 */
function deploy_lock_acquire(string $arquivo, array $dono, int $ttl = DEPLOY_LOCK_TTL): array {
    $pasta = dirname($arquivo);
    if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) return ['ok' => false, 'holder' => null, 'error' => 'io'];

    $assumida = null;
    for ($tentativa = 0; $tentativa < 2; $tentativa++) {
        $agora = time();
        $token = bin2hex(random_bytes(16));
        $trava = [
            'token' => $token,
            'owner' => (string)($dono['owner'] ?? 'desconhecido'),
            'execution' => isset($dono['execution']) ? (string)$dono['execution'] : null,
            'detail' => isset($dono['detail']) ? (string)$dono['detail'] : null,
            'host' => (string)(function_exists('gethostname') ? gethostname() : ''),
            'pid' => function_exists('getmypid') ? (int)getmypid() : 0,
            'started_at' => $agora,
            'expires_at' => $agora + max(60, $ttl),
        ];
        $h = @fopen($arquivo, 'x');
        if ($h !== false) {
            fwrite($h, json_encode($trava, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            fclose($h);
            return ['ok' => true, 'token' => $token, 'lock' => $trava, 'stale' => $assumida];
        }
        $atual = deploy_lock_read($arquivo);
        if (!deploy_lock_expired($atual, $agora)) return ['ok' => false, 'holder' => $atual, 'error' => 'busy'];
        // Vencida: tira do caminho com rename atômico (só um processo consegue) e tenta de novo.
        $lixo = $arquivo . '.stale-' . bin2hex(random_bytes(4));
        if (@rename($arquivo, $lixo)) {
            $assumida = $atual ?: ['owner' => 'ilegível'];
            @unlink($lixo);
        }
    }
    return ['ok' => false, 'holder' => deploy_lock_read($arquivo), 'error' => 'busy'];
}

/** Libera a trava, só se o token for o dela. */
function deploy_lock_release(string $arquivo, string $token): bool {
    $atual = deploy_lock_read($arquivo);
    if (!$atual || !hash_equals((string)($atual['token'] ?? ''), $token)) return false;
    return @unlink($arquivo);
}

/**
 * req-277: libera à força uma trava de um dono específico (botão "Liberar trava" da atualização pelo
 * painel). Recusa trava de outro dono — pipeline e API seguem intocáveis — e trava já trocada.
 *
 * @param string $arquivo Caminho da trava.
 * @param string $owner   Dono exigido (ex.: 'update-system').
 * @param string $detail  Detalhe exigido (ex.: 'web').
 * @return array ['ok' => bool, 'released' => array|null, 'error' => 'none'|'owner'|'io'|null]
 */
function deploy_lock_force_release(string $arquivo, string $owner, string $detail): array {
    $atual = deploy_lock_read($arquivo);
    if (!$atual) return ['ok' => true, 'released' => null, 'error' => 'none'];
    if ((string)($atual['owner'] ?? '') !== $owner || (string)($atual['detail'] ?? '') !== $detail) {
        return ['ok' => false, 'released' => $atual, 'error' => 'owner'];
    }
    // Rename atômico: se outra requisição trocou a trava nesse meio-tempo, a conferida não é a removida.
    $lixo = $arquivo . '.force-' . bin2hex(random_bytes(4));
    if (!@rename($arquivo, $lixo)) return ['ok' => false, 'released' => $atual, 'error' => 'io'];
    $removida = deploy_lock_read($lixo);
    if (!$removida || !hash_equals((string)($atual['token'] ?? ''), (string)($removida['token'] ?? ''))) {
        @rename($lixo, $arquivo);
        return ['ok' => false, 'released' => $removida, 'error' => 'owner'];
    }
    @unlink($lixo);
    return ['ok' => true, 'released' => $atual, 'error' => null];
}

/** Estende a validade (execuções longas). */
function deploy_lock_refresh(string $arquivo, string $token, int $ttl = DEPLOY_LOCK_TTL): bool {
    $atual = deploy_lock_read($arquivo);
    if (!$atual || !hash_equals((string)($atual['token'] ?? ''), $token)) return false;
    $atual['expires_at'] = time() + max(60, $ttl);
    return @file_put_contents($arquivo, json_encode($atual, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

/** Texto curto de quem está com a trava (mensagens de recusa). */
function deploy_lock_describe(?array $trava): string {
    if (!$trava) return 'trava sem dono legível';
    $partes = [(string)($trava['owner'] ?? '?')];
    if (!empty($trava['detail'])) $partes[] = (string)$trava['detail'];
    if (!empty($trava['execution'])) $partes[] = 'execução ' . $trava['execution'];
    if (!empty($trava['host'])) $partes[] = 'em ' . $trava['host'] . (!empty($trava['pid']) ? ' (PID ' . $trava['pid'] . ')' : '');
    if (!empty($trava['started_at'])) $partes[] = 'desde ' . date('Y-m-d H:i:s', (int)$trava['started_at']);
    if (!empty($trava['expires_at'])) $partes[] = 'vence ' . date('H:i:s', (int)$trava['expires_at']);
    return implode(', ', $partes);
}
