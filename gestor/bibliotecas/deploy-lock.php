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

/** A trava está vencida? (sem validade legível conta como vencida) */
function deploy_lock_expired(?array $trava, ?int $agora = null): bool {
    if (!$trava) return true;
    return (int)($trava['expires_at'] ?? 0) <= ($agora ?? time());
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
