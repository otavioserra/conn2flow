<?php
/**
 * Choques das entregas no banco (tabela `atualizacoes_choques`) — req-199 / BATCH-205.
 *
 * Liga o motor puro de `instalacao-manifesto.php` ao registro: lista, detalhe com as duas versões e
 * resolução. Usada pelo painel (`admin-atualizacoes`) e pela API (`/_api/project/conflicts` e `/resolve`),
 * para que a decisão seja a mesma venha de onde vier (painel, CLI, extensão do VS Code).
 */

require_once __DIR__ . '/instalacao-manifesto.php';

/** A tabela existe (a migração pode não ter rodado ainda). */
function atualizacoes_choques_disponivel(): bool {
    if (!function_exists('banco_query')) return false;
    $r = @banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    return $r && banco_num_rows($r) > 0;
}

/**
 * Choques do registro, mais recentes primeiro.
 *
 * @param bool $pendentes Só os sem resolução.
 * @return array Linhas sem o diff (id, data_criacao, origem, camada, versao, caminho, tipo, motivo,
 *               camada_dona, copia, resolucao, resolvido_em, resolvido_por, acoes).
 */
function atualizacoes_choques_listar(bool $pendentes = true, int $limite = 100): array {
    if (!atualizacoes_choques_disponivel()) return [];
    $limite = max(1, min(500, $limite));
    $res = banco_query('SELECT id_atualizacoes_choques AS id, data_criacao, execucao, origem, camada, versao, caminho, tipo, motivo, camada_dona, copia, resolucao, resolvido_em, resolvido_por'
        . ' FROM atualizacoes_choques' . ($pendentes ? ' WHERE resolucao IS NULL' : '') . ' ORDER BY id_atualizacoes_choques DESC LIMIT ' . $limite);
    $out = [];
    if ($res) while ($r = banco_fetch_assoc($res)) {
        $r['id'] = (int)$r['id'];
        $r['acoes'] = $r['resolucao'] ? [] : instalacao_choque_acoes($r);
        $out[] = $r;
    }
    return $out;
}

/** Uma linha do registro (com o diff), ou null. */
function atualizacoes_choques_obter(int $id): ?array {
    if (!atualizacoes_choques_disponivel()) return null;
    $res = banco_query('SELECT * FROM atualizacoes_choques WHERE id_atualizacoes_choques=' . (int)$id . ' LIMIT 1');
    $r = $res ? banco_fetch_assoc($res) : null;
    if (!$r) return null;
    $r['id'] = (int)$r['id_atualizacoes_choques'];
    $r['acoes'] = $r['resolucao'] ? [] : instalacao_choque_acoes($r);
    return $r;
}

/**
 * Detalhe para quem decide: a linha, as duas versões (no ar e nova) e o diff. Binário vai em base64.
 *
 * @return array|null ['choque' => linha, 'no_ar' => string|null, 'nova' => string|null, 'binario' => bool, 'codificacao' => 'texto'|'base64']
 */
function atualizacoes_choques_detalhe(string $base, int $id): ?array {
    $c = atualizacoes_choques_obter($id);
    if (!$c) return null;
    $v = instalacao_choque_versoes($base, $c);
    $b64 = $v['binario'];
    $cod = function ($t) use ($b64) { return $t === null ? null : ($b64 ? base64_encode($t) : $t); };
    return ['choque' => $c, 'no_ar' => $cod($v['no_ar']), 'nova' => $cod($v['nova']), 'binario' => $b64, 'codificacao' => $b64 ? 'base64' : 'texto'];
}

/**
 * Aplica a decisão (motor) e registra: a linha e as outras pendentes do mesmo arquivo e camada (versões
 * anteriores do mesmo choque) recebem a resolução, a data e quem decidiu.
 *
 * @return array ['ok' => bool, 'erro' => string, 'acao' => string, 'resolvidos' => int]
 */
function atualizacoes_choques_resolver(string $base, int $id, string $acao, ?string $mesclado, string $quem): array {
    $c = atualizacoes_choques_obter($id);
    if (!$c) return ['ok' => false, 'erro' => 'choque não encontrado', 'acao' => $acao, 'resolvidos' => 0];
    if ($c['resolucao']) return ['ok' => false, 'erro' => 'choque já resolvido (' . $c['resolucao'] . ')', 'acao' => $acao, 'resolvidos' => 0];
    $r = instalacao_choque_resolver($base, $c, $acao, $mesclado);
    if (!$r['ok']) return ['ok' => false, 'erro' => $r['erro'], 'acao' => $acao, 'resolvidos' => 0];
    $e = function ($v) { return banco_escape_field((string)$v); };
    banco_query("UPDATE atualizacoes_choques SET resolucao='" . $e($acao) . "', resolvido_em=NOW(), resolvido_por='" . $e(substr($quem, 0, 150)) . "'"
        . " WHERE resolucao IS NULL AND caminho='" . $e($c['caminho']) . "' AND camada='" . $e($c['camada']) . "'");
    global $_BANCO;
    $n = !empty($_BANCO['conexao']) ? (int)mysqli_affected_rows($_BANCO['conexao']) : 1;
    return ['ok' => true, 'erro' => '', 'acao' => $acao, 'resolvidos' => max(1, $n)];
}
