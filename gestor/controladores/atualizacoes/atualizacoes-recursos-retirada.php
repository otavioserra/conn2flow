<?php
/**
 * Exclusão declarativa de dados no ambiente em execução — req-199 / BATCH-207 (BL-028 C).
 *
 * Cada entrega grava, por dono (`core` ou o id do projeto), a chave natural de cada registro que entregou
 * em `installation/manifests/recursos-<dono>.json`. Na entrega seguinte, o que saiu do manifesto do mesmo
 * dono sai do banco: `status='D'` quando a tabela tem `status`, `DELETE` quando não tem. Registro editado
 * online (`user_modified=1`) não é apagado: vira choque `retirado-editado` (tipo `registro`), decidido
 * pelo motor da req-199. Registro criado pelo usuário nunca entrou num manifesto, então nunca sai.
 *
 * Só tabelas de estratégia `natural_key` (a identidade é a mesma em qualquer instalação). A primeira
 * entrega de um dono numa tabela só grava a linha de base. A lista imperativa `deletar` do
 * `schema-metadata.json` continua valendo para casos pontuais.
 */

/**
 * Chamada do sincronizador (`comparacaoDados()` em `atualizacoes-banco-de-dados.php`) para cada tabela
 * sincronizada: resolve dono, base e colunas, roda a passada e registra no log do banco.
 * `--no-resource-removal` desliga; `--dry-run` só simula.
 */
function recursos_retirada_passada(PDO $pdo, string $tabela, array $registros): ?array {
    $opts = $GLOBALS['CLI_OPTS'] ?? [];
    if (!empty($opts['no-resource-removal']) || !function_exists('naturalKeyColumns')) return null;
    $base = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
    $lib = $base . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'instalacao-manifesto.php';
    if (!function_exists('instalacao_choques_registrar') && is_file($lib)) require_once $lib;
    $projeto = isset($opts['project']) && $opts['project'] !== '' ? (string)$opts['project'] : null;
    try {
        $r = recursos_retirada_tabela($pdo, $base, $tabela, $registros, naturalKeyColumns($tabela), $projeto, !empty($opts['dry-run']));
    } catch (Throwable $e) {
        if (function_exists('log_unificado')) log_unificado('RECURSOS_RETIRADA_ERRO tabela=' . $tabela . ' msg=' . $e->getMessage(), $GLOBALS['LOG_FILE_DB'] ?? 'atualizacoes-bd');
        return null;
    }
    if ($r && function_exists('log_unificado') && ($r['saidos'] || $r['primeira'])) {
        log_unificado(sprintf('RECURSOS_RETIRADA tabela=%s dono=%s primeira=%d saidos=%d marcados=%d removidos=%d choques=%d ausentes=%d simulados=%d',
            $tabela, $projeto ?? 'core', $r['primeira'] ? 1 : 0, $r['saidos'], $r['marcados'], $r['retirados'], count($r['choques']), $r['ausentes'], $r['simulados']),
            $GLOBALS['LOG_FILE_DB'] ?? 'atualizacoes-bd');
    }
    return $r;
}

/** Arquivo do manifesto de recursos de um dono. */
function recursos_retirada_manifesto_arquivo(string $base, string $dono): string {
    $dono = preg_replace('/[^A-Za-z0-9_.-]/', '_', $dono !== '' ? $dono : 'core');
    return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'installation' . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . 'recursos-' . $dono . '.json';
}

/** Manifesto de recursos do dono: `tabela → [chave → [coluna → valor]]`. */
function recursos_retirada_manifesto_ler(string $base, string $dono): array {
    $f = recursos_retirada_manifesto_arquivo($base, $dono);
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    return is_array($d['tabelas'] ?? null) ? $d['tabelas'] : [];
}

/** Grava o manifesto de recursos (atômico). */
function recursos_retirada_manifesto_gravar(string $base, string $dono, array $tabelas): bool {
    $f = recursos_retirada_manifesto_arquivo($base, $dono);
    if (!is_dir(dirname($f)) && !@mkdir(dirname($f), 0775, true) && !is_dir(dirname($f))) return false;
    ksort($tabelas);
    $tmp = $f . '.' . bin2hex(random_bytes(3)) . '.tmp';
    if (@file_put_contents($tmp, json_encode(['dono' => $dono, 'gerado_em' => date('c'), 'tabelas' => $tabelas], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) return false;
    return @rename($tmp, $f);
}

/**
 * Chaves naturais dos registros entregues numa tabela: `chave → [coluna → valor]`. A chave segue a regra do
 * sincronizador (valores em minúsculas, separados por `|`; `modulo`/`module`/`grupo` opcionais; `language`
 * aceita `linguagem_codigo`). Registro sem coluna obrigatória fica de fora.
 */
function recursos_retirada_chaves(array $registros, array $colunas): array {
    static $opcionais = ['modulo' => true, 'module' => true, 'grupo' => true];
    $out = [];
    foreach ($registros as $r) {
        if (!is_array($r)) continue;
        $partes = []; $valores = []; $ok = true;
        foreach ($colunas as $c) {
            $v = $r[$c] ?? null;
            if (($v === null || $v === '') && $c === 'language') $v = $r['linguagem_codigo'] ?? null;
            if ($v === null || $v === '') {
                if (isset($opcionais[$c])) { $partes[] = ''; $valores[$c] = null; continue; }
                $ok = false; break;
            }
            $partes[] = strtolower((string)$v);
            $valores[$c] = (string)$v;
        }
        if ($ok) $out[implode('|', $partes)] = $valores;
    }
    return $out;
}

/** O que o dono entregou antes e não entrega mais: `chave → [coluna → valor]`. */
function recursos_retirada_planejar(array $anterior, array $atual): array {
    return array_diff_key($anterior, $atual);
}

/**
 * Aplica a retirada de uma tabela no banco.
 *
 * @param array $itens `chave → [coluna → valor]` (de `recursos_retirada_planejar`).
 * @param array $colunasBanco Colunas reais da tabela (`coluna → true`).
 * @param string|null $projeto Id do projeto dono (null = core): só casa registros do mesmo dono.
 * @return array ['retirados' => n, 'marcados' => n, 'choques' => [..], 'ausentes' => n, 'simulados' => n]
 */
function recursos_retirada_aplicar(PDO $pdo, string $tabela, array $itens, array $colunasBanco, ?string $projeto, bool $simular): array {
    $res = ['retirados' => 0, 'marcados' => 0, 'choques' => [], 'ausentes' => 0, 'simulados' => 0];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela) || !$itens) return $res;
    $temStatus = isset($colunasBanco['status']);
    $temUser = isset($colunasBanco['user_modified']);
    $temProjeto = isset($colunasBanco['project']);
    foreach ($itens as $chave => $valores) {
        $conds = []; $params = [];
        foreach ((array)$valores as $col => $v) {
            if ($col === 'language' && !isset($colunasBanco['language']) && isset($colunasBanco['linguagem_codigo'])) $col = 'linguagem_codigo';
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$col) || !isset($colunasBanco[$col])) continue;
            if ($v === null) { $conds[] = "(`$col` IS NULL OR `$col` = '')"; continue; }
            $p = 'k' . count($params);
            $conds[] = "`$col` = :$p"; $params[$p] = $v;
        }
        if (!$conds) continue;
        if ($temProjeto) {
            if ($projeto === null) $conds[] = "(`project` IS NULL OR `project` = '')";
            else { $conds[] = '`project` = :__proj'; $params['__proj'] = $projeto; }
        }
        if ($temStatus) $conds[] = "(`status` IS NULL OR `status` <> 'D')";
        $where = implode(' AND ', $conds);
        $st = $pdo->prepare("SELECT * FROM `$tabela` WHERE $where");
        $st->execute($params);
        $linhas = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$linhas) { $res['ausentes']++; continue; }
        $editado = false;
        foreach ($linhas as $l) if ($temUser && (int)($l['user_modified'] ?? 0) === 1) $editado = true;
        if ($editado) {
            $res['choques'][] = ['caminho' => 'db:' . $tabela . '?' . http_build_query(array_filter((array)$valores, function ($v) { return $v !== null; })),
                'tipo' => 'registro', 'motivo' => 'retirado-editado', 'camada_dona' => null, 'hash_disco' => null, 'hash_novo' => null, 'copia' => null,
                'diff' => 'Registro que a camada deixou de entregar, mas foi editado online (user_modified=1). Chave: ' . $chave];
            continue;
        }
        if ($simular) { $res['simulados'] += count($linhas); continue; }
        if ($temStatus) {
            $u = $pdo->prepare("UPDATE `$tabela` SET `status` = 'D' WHERE $where");
            $u->execute($params); $res['marcados'] += $u->rowCount();
        } else {
            $d = $pdo->prepare("DELETE FROM `$tabela` WHERE $where");
            $d->execute($params); $res['retirados'] += $d->rowCount();
        }
    }
    return $res;
}

/**
 * Passada da retirada para uma tabela, chamada pelo sincronizador depois de `sincronizarTabela()`.
 * Grava o manifesto do dono para a tabela (menos em simulação) e registra os choques pendentes.
 *
 * @param array $colunasNaturais Colunas da chave natural (vazio: tabela fora da retirada).
 * @return array|null Resumo, ou null quando a tabela não participa.
 */
function recursos_retirada_tabela(PDO $pdo, string $base, string $tabela, array $registros, array $colunasNaturais, ?string $projeto, bool $simular): ?array {
    if (!$colunasNaturais || !$registros) return null;
    $dono = ($projeto !== null && $projeto !== '') ? $projeto : 'core';
    $manifesto = recursos_retirada_manifesto_ler($base, $dono);
    $atual = recursos_retirada_chaves($registros, $colunasNaturais);
    $primeira = !isset($manifesto[$tabela]);
    $itens = $primeira ? [] : recursos_retirada_planejar((array)$manifesto[$tabela], $atual);
    $colunasBanco = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC) as $c) $colunasBanco[$c['Field']] = true;
    $r = recursos_retirada_aplicar($pdo, $tabela, $itens, $colunasBanco, ($projeto !== null && $projeto !== '') ? $projeto : null, $simular);
    if (!$simular) {
        $manifesto[$tabela] = $atual;
        recursos_retirada_manifesto_gravar($base, $dono, $manifesto);
        if ($r['choques'] && function_exists('instalacao_choques_registrar')) {
            instalacao_choques_registrar($base, 'db-sync', $dono === 'core' ? 'core' : 'projeto', date('Ymd-His'), null, $r['choques']);
        }
    }
    return $r + ['primeira' => $primeira, 'saidos' => count($itens)];
}
