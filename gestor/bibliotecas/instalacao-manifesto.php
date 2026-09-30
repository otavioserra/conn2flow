<?php
/**
 * Manifesto de instalação por camada e precedência no disco — req-198 / BATCH-202 (BL-028 A e B.1).
 *
 * O core é "canibalizável": um projeto (ou plugin) pode sobrepor qualquer arquivo do core e continuar
 * recebendo atualizações. Para isso, cada entrega grava o que entregou (`caminho → sha256`) em
 * `installation/manifests/<camada>.json`, e a entrega seguinte usa esses manifestos para decidir:
 *
 * - **escrever**: arquivo novo, intacto (o disco tem o que a própria camada entregou) ou igual ao novo;
 * - **preservar** (choque): uma camada SUPERIOR entregou o mesmo caminho (`sobreposto`), ou o arquivo
 *   foi mudado no servidor e ninguém entregou esse conteúdo (`editado`). A cópia nova vai para
 *   `backups/overrides/<versão>/<caminho>` e o choque é registrado com o diff;
 * - **retirar**: a camada entregou antes e não entrega mais, e o disco está intacto. Se uma camada
 *   superior sobrepunha, ela é a dona agora (nada sai); se o disco foi editado, vira choque;
 * - **originais**: o projeto vai sobrescrever um arquivo que está com a versão de uma camada inferior —
 *   essa versão é guardada em `installation/originals/<caminho>` para voltar ao ar se o projeto deixar
 *   de sobrepor (`restaurar`).
 *
 * Precedência: `projeto` > `plugin:<id>` > `core`. Primeira entrega de uma camada (sem manifesto):
 * comportamento antigo — escreve tudo — e grava a linha de base.
 *
 * Biblioteca PURA (sem Gestor): roda no atualizador independente (inclusive a partir do staging) e na
 * API. As pastas protegidas (`contents/`, `logs/`, `backups/`, `temp/`, `autenticacoes/`) e a própria
 * `installation/` nunca entram no manifesto.
 */

const INSTALACAO_PASTAS_FORA = ['contents', 'logs', 'backups', 'temp', 'autenticacoes', 'installation'];

/** Diff guardado no choque: até este tamanho por lado. */
const INSTALACAO_DIFF_MAX_BYTES = 262144;

function instalacao_caminho_base(string $base): string { return rtrim($base, '/\\') . DIRECTORY_SEPARATOR; }

function instalacao_manifesto_arquivo(string $base, string $camada): string {
    $nome = preg_replace('/[^a-z0-9._-]/i', '_', $camada);
    return instalacao_caminho_base($base) . 'installation' . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $nome . '.json';
}

/** Nível de precedência de uma camada (maior vence). */
function instalacao_nivel(string $camada): int {
    if ($camada === 'projeto') return 2;
    if (strpos($camada, 'plugin:') === 0) return 1;
    return 0;
}

/** @return array|null ['camada','versao','gerado_em','arquivos' => [rel => sha256]] */
function instalacao_manifesto_ler(string $base, string $camada): ?array {
    $f = instalacao_manifesto_arquivo($base, $camada);
    if (!is_file($f)) return null;
    $d = json_decode((string)@file_get_contents($f), true);
    return (is_array($d) && is_array($d['arquivos'] ?? null)) ? $d : null;
}

/** Todos os manifestos gravados na instalação, por camada. */
function instalacao_manifestos(string $base): array {
    $dir = dirname(instalacao_manifesto_arquivo($base, 'x'));
    $out = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
        $d = json_decode((string)@file_get_contents($f), true);
        if (is_array($d) && !empty($d['camada']) && is_array($d['arquivos'] ?? null)) $out[(string)$d['camada']] = $d;
    }
    return $out;
}

function instalacao_manifesto_gravar(string $base, string $camada, array $arquivos, string $versao): bool {
    $f = instalacao_manifesto_arquivo($base, $camada);
    if (!is_dir(dirname($f)) && !@mkdir(dirname($f), 0775, true) && !is_dir(dirname($f))) return false;
    ksort($arquivos);
    $dados = ['camada' => $camada, 'versao' => $versao, 'gerado_em' => date('c'), 'arquivos' => $arquivos];
    $tmp = $f . '.tmp-' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, json_encode($dados, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) return false;
    return @rename($tmp, $f);
}

/** Caminho relativo com `/`. */
function instalacao_rel(string $raiz, string $caminho): string {
    return str_replace('\\', '/', ltrim(substr($caminho, strlen(instalacao_caminho_base($raiz))), '/\\'));
}

/** O caminho relativo fica fora do manifesto? (pastas protegidas e `installation/`) */
function instalacao_fora(string $rel): bool {
    $topo = explode('/', $rel, 2)[0];
    return in_array($topo, INSTALACAO_PASTAS_FORA, true);
}

/**
 * Mapa `rel → sha256` de uma árvore (pacote no staging ou instalação).
 *
 * @param string[] $ignorar Caminhos relativos exatos a ignorar (ex.: artefatos do próprio pacote).
 */
function instalacao_mapa(string $raiz, array $ignorar = []): array {
    $raiz = instalacao_caminho_base($raiz);
    if (!is_dir($raiz)) return [];
    $mapa = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $item) {
        if (!$item->isFile()) continue;
        $rel = instalacao_rel($raiz, $item->getPathname());
        if (instalacao_fora($rel) || in_array($rel, $ignorar, true)) continue;
        $mapa[$rel] = hash_file('sha256', $item->getPathname());
    }
    ksort($mapa);
    return $mapa;
}

function instalacao_hash_disco(string $base, string $rel): ?string {
    $f = instalacao_caminho_base($base) . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    return is_file($f) ? hash_file('sha256', $f) : null;
}

/**
 * Plano de uma entrega.
 *
 * @param string $base     Raiz da instalação.
 * @param string $camada   'core' | 'plugin:<id>' | 'projeto'.
 * @param array  $pacote   Mapa `rel → sha256` do que chega.
 * @param bool   $completo O pacote traz TUDO que a camada entrega (senão não há retirada).
 * @param array|null $manifestos Manifestos atuais (null = lê do disco).
 * @param array|null $listaCompleta Mapa `rel → sha256` de TUDO que a camada entrega, quando o pacote
 *                   físico é parcial (gitDeploy): decide as retiradas e vira o manifesto; os arquivos
 *                   escritos continuam sendo só os do pacote.
 * @return array ['primeira' => bool, 'escrever' => rel[], 'preservar' => [rel => info], 'retirar' => rel[],
 *               'restaurar' => [rel => camada inferior], 'originais' => rel[], 'retirar_choque' => [rel => info],
 *               'manifesto' => mapa final da camada]
 */
function instalacao_planejar(string $base, string $camada, array $pacote, bool $completo, ?array $manifestos = null, ?array $listaCompleta = null): array {
    $manifestos = $manifestos ?? instalacao_manifestos($base);
    $anterior = $manifestos[$camada]['arquivos'] ?? null;
    $nivel = instalacao_nivel($camada);
    $superiores = []; $inferiores = []; $todos = [];
    foreach ($manifestos as $c => $m) {
        if ($c === $camada) continue;
        if (instalacao_nivel($c) > $nivel) $superiores[$c] = $m['arquivos'];
        elseif (instalacao_nivel($c) < $nivel) $inferiores[$c] = $m['arquivos'];
        foreach ($m['arquivos'] as $rel => $h) $todos[$rel][$h] = $c;
    }
    $dono = function (array $grupo, string $rel): ?string { foreach ($grupo as $c => $arqs) if (isset($arqs[$rel])) return $c; return null; };

    $plano = ['primeira' => $anterior === null, 'escrever' => [], 'preservar' => [], 'retirar' => [], 'restaurar' => [],
        'originais' => [], 'retirar_choque' => [], 'manifesto' => []];

    foreach ($pacote as $rel => $hNovo) {
        if (instalacao_fora($rel)) continue;
        $hDisco = instalacao_hash_disco($base, $rel);
        $sup = $dono($superiores, $rel);
        if ($sup !== null && $hDisco !== null && $hDisco !== $hNovo) {
            $plano['preservar'][$rel] = ['motivo' => 'sobreposto', 'camada_dona' => $sup, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo];
            continue;
        }
        if ($anterior !== null && $hDisco !== null && $hDisco !== $hNovo && ($anterior[$rel] ?? null) !== $hDisco && !isset($todos[$rel][$hDisco])) {
            $plano['preservar'][$rel] = ['motivo' => 'editado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo];
            continue;
        }
        // Camada superior sobrescrevendo a versão de uma inferior: guarda a original para restaurar.
        $inf = $dono($inferiores, $rel);
        if ($inf !== null && $hDisco !== null && $hDisco !== $hNovo && ($inferiores[$inf][$rel] ?? null) === $hDisco) $plano['originais'][] = $rel;
        if ($hDisco !== $hNovo) $plano['escrever'][] = $rel;
    }

    $entrega = $listaCompleta ?? $pacote;
    if (($completo || $listaCompleta !== null) && $anterior !== null) {
        foreach ($anterior as $rel => $hAnterior) {
            if (isset($entrega[$rel]) || instalacao_fora($rel)) continue;
            if ($dono($superiores, $rel) !== null) continue; // a camada de cima é a dona agora
            $hDisco = instalacao_hash_disco($base, $rel);
            if ($hDisco === null) continue;
            if ($hDisco !== $hAnterior) {
                $plano['retirar_choque'][$rel] = ['motivo' => 'retirado-editado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => null];
                continue;
            }
            $inf = $dono($inferiores, $rel);
            if ($inf !== null) $plano['restaurar'][$rel] = $inf; else $plano['retirar'][] = $rel;
        }
    }

    if ($listaCompleta !== null) $plano['manifesto'] = array_filter(array_merge($listaCompleta, $pacote), function ($r) { return !instalacao_fora((string)$r); }, ARRAY_FILTER_USE_KEY);
    else $plano['manifesto'] = ($completo || $anterior === null) ? $pacote : array_merge($anterior, $pacote);
    ksort($plano['manifesto']);
    return $plano;
}

/**
 * Aplica o plano: guarda originais, escreve, preserva (cópia nova em `backups/overrides/`), retira,
 * restaura e grava o manifesto. Os arquivos a escrever são COPIADOS do pacote (`$modo = 'copiar'`) ou
 * apenas removidos do pacote quando preservados, para quem aplica por conta própria (`'preparar'`,
 * usado pela atualização do sistema, que move o staging depois).
 *
 * @return array ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
 *               'choques' => [['caminho','motivo','camada_dona','hash_disco','hash_novo','copia','diff']]]
 */
function instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar'): array {
    $base = instalacao_caminho_base($base);
    $pacoteRaiz = instalacao_caminho_base($pacoteRaiz);
    $ds = DIRECTORY_SEPARATOR;
    $abs = function (string $raiz, string $rel) use ($ds) { return $raiz . str_replace('/', $ds, $rel); };
    $copiar = function (string $de, string $para) {
        if (!is_dir(dirname($para))) @mkdir(dirname($para), 0775, true);
        return @copy($de, $para);
    };
    $versaoPasta = preg_replace('/[^A-Za-z0-9._-]/', '_', $versao !== '' ? $versao : date('Ymd-His'));
    $rel = ['escritos' => 0, 'preservados' => 0, 'retirados' => 0, 'restaurados' => 0, 'choques' => []];

    foreach ($plano['originais'] as $r) $copiar($abs($base, $r), $abs($base . 'installation' . $ds . 'originals' . $ds, $r));

    foreach ($plano['preservar'] as $r => $info) {
        $copia = $abs($base . 'backups' . $ds . 'overrides' . $ds . $versaoPasta . $ds, $r);
        $copiar($abs($pacoteRaiz, $r), $copia);
        $diff = instalacao_diff((string)@file_get_contents($abs($base, $r)), (string)@file_get_contents($abs($pacoteRaiz, $r)), $r);
        if ($modo === 'preparar') @unlink($abs($pacoteRaiz, $r)); // não vai para o disco
        $rel['choques'][] = ['caminho' => $r, 'tipo' => 'arquivo'] + $info + ['copia' => instalacao_rel($base, $copia), 'diff' => $diff];
        $rel['preservados']++;
    }

    if ($modo === 'copiar') {
        foreach ($plano['escrever'] as $r) { if ($copiar($abs($pacoteRaiz, $r), $abs($base, $r))) $rel['escritos']++; }
    } else {
        $rel['escritos'] = count($plano['escrever']);
    }

    foreach ($plano['retirar'] as $r) { if (@unlink($abs($base, $r))) $rel['retirados']++; }
    foreach ($plano['restaurar'] as $r => $inf) {
        $orig = $abs($base . 'installation' . $ds . 'originals' . $ds, $r);
        if (is_file($orig) && $copiar($orig, $abs($base, $r))) { @unlink($orig); $rel['restaurados']++; }
        else $rel['choques'][] = ['caminho' => $r, 'tipo' => 'arquivo', 'motivo' => 'original-ausente', 'camada_dona' => $inf,
            'hash_disco' => instalacao_hash_disco($base, $r), 'hash_novo' => null, 'copia' => null, 'diff' => null];
    }
    foreach ($plano['retirar_choque'] as $r => $info) $rel['choques'][] = ['caminho' => $r, 'tipo' => 'retirada'] + $info + ['copia' => null, 'diff' => null];

    instalacao_manifesto_gravar($base, $camada, $plano['manifesto'], $versao);
    return $rel;
}

/**
 * Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só
 * o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
 */
function instalacao_diff(string $antes, string $depois, string $rotulo = ''): ?string {
    if ($antes === $depois) return null;
    if (strlen($antes) > INSTALACAO_DIFF_MAX_BYTES || strlen($depois) > INSTALACAO_DIFF_MAX_BYTES) return '(arquivo grande demais para o diff)';
    if (strpos($antes, "\0") !== false || strpos($depois, "\0") !== false) return '(arquivo binário)';
    $a = preg_split('/\r\n|\n|\r/', $antes); $b = preg_split('/\r\n|\n|\r/', $depois);
    $n = count($a); $m = count($b);
    if ($n * $m > 4000000) return '(arquivo com linhas demais para o diff)';
    $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
    for ($i = $n - 1; $i >= 0; $i--) for ($j = $m - 1; $j >= 0; $j--)
        $lcs[$i][$j] = $a[$i] === $b[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
    $out = ['--- no ar/' . $rotulo, '+++ nova/' . $rotulo];
    $i = 0; $j = 0;
    while ($i < $n || $j < $m) {
        if ($i < $n && $j < $m && $a[$i] === $b[$j]) { $i++; $j++; continue; }
        if ($j < $m && ($i >= $n || $lcs[$i][$j + 1] >= $lcs[$i + 1][$j])) { $out[] = '@' . ($j + 1) . ' + ' . $b[$j]; $j++; }
        else { $out[] = '@' . ($i + 1) . ' - ' . $a[$i]; $i++; }
    }
    return implode("\n", $out);
}

// ============================================================================ choques pendentes

/**
 * Guarda os choques de uma entrega num JSON pendente (`installation/choques/*.pendente.json`). A gravação
 * na tabela `atualizacoes_choques` acontece depois da etapa de banco (a tabela pode ainda não existir na
 * etapa de arquivos) por `instalacao_choques_gravar_pendentes()`.
 */
function instalacao_choques_registrar(string $base, string $origem, string $camada, string $versao, ?string $execucao, array $choques): ?string {
    if (!$choques) return null;
    $dir = instalacao_caminho_base($base) . 'installation' . DIRECTORY_SEPARATOR . 'choques' . DIRECTORY_SEPARATOR;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) return null;
    $f = $dir . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.pendente.json';
    $dados = ['origem' => $origem, 'camada' => $camada, 'versao' => $versao, 'execucao' => $execucao, 'registrado_em' => date('c'), 'choques' => $choques];
    return @file_put_contents($f, json_encode($dados, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false ? $f : null;
}

/**
 * Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem
 * resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por
 * atualização. `$escapar` escapa um valor para SQL.
 */
function instalacao_choque_pendente_filtro(array $linha, callable $escapar): string {
    $w = ['resolucao IS NULL'];
    foreach (['caminho', 'camada', 'motivo', 'hash_disco', 'hash_novo'] as $k) {
        $v = $linha[$k] ?? null;
        $w[] = $v === null ? $k . ' IS NULL' : $k . "='" . $escapar((string)$v) . "'";
    }
    return implode(' AND ', $w);
}

/**
 * Grava os choques pendentes com `$inserir(array $linha): bool` (colunas de `atualizacoes_choques`) e
 * renomeia cada arquivo para `.gravado.json`. Arquivo com falha continua pendente para a próxima vez.
 *
 * @return int Choques gravados.
 */
function instalacao_choques_gravar_pendentes(string $base, callable $inserir): int {
    $dir = instalacao_caminho_base($base) . 'installation' . DIRECTORY_SEPARATOR . 'choques' . DIRECTORY_SEPARATOR;
    $total = 0;
    foreach (glob($dir . '*.pendente.json') ?: [] as $f) {
        $d = json_decode((string)@file_get_contents($f), true);
        if (!is_array($d)) continue;
        $ok = true;
        foreach ((array)($d['choques'] ?? []) as $c) {
            $linha = [
                'execucao' => $d['execucao'] ?? null, 'origem' => (string)($d['origem'] ?? ''), 'camada' => (string)($d['camada'] ?? ''),
                'versao' => $d['versao'] ?? null, 'caminho' => (string)($c['caminho'] ?? ''), 'tipo' => (string)($c['tipo'] ?? 'arquivo'),
                'motivo' => (string)($c['motivo'] ?? ''), 'camada_dona' => $c['camada_dona'] ?? null, 'hash_disco' => $c['hash_disco'] ?? null,
                'hash_novo' => $c['hash_novo'] ?? null, 'copia' => $c['copia'] ?? null, 'diff' => $c['diff'] ?? null,
            ];
            if ($inserir($linha)) $total++; else $ok = false;
        }
        if ($ok) @rename($f, substr($f, 0, -strlen('.pendente.json')) . '.gravado.json');
    }
    return $total;
}

// ============================================================================ snapshot e rollback (req-198 / BATCH-203)

/**
 * Snapshot seletivo antes de aplicar um plano: guarda só o que vai ser sobrescrito, removido ou
 * restaurado (não a instalação inteira), a lista do que é novo (sai no rollback) e os manifestos
 * atuais. Grava `<dir>/snapshot.json`.
 *
 * @return array ['sobrescritos' => n, 'removidos' => n, 'novos' => n]
 */
function instalacao_snapshot_criar(string $base, array $plano, string $dir, array $meta = []): array {
    $base = instalacao_caminho_base($base);
    $dir = instalacao_caminho_base($dir);
    $ds = DIRECTORY_SEPARATOR;
    $guardar = function (string $rel) use ($base, $dir, $ds) {
        $de = $base . str_replace('/', $ds, $rel);
        $para = $dir . 'files' . $ds . str_replace('/', $ds, $rel);
        if (!is_dir(dirname($para))) @mkdir(dirname($para), 0775, true);
        return @copy($de, $para);
    };
    $snap = ['criado_em' => date('c'), 'meta' => $meta, 'sobrescritos' => [], 'removidos' => [], 'novos' => []];
    foreach (array_merge($plano['escrever'] ?? [], array_keys($plano['restaurar'] ?? [])) as $rel) {
        if (is_file($base . str_replace('/', $ds, $rel))) { if ($guardar($rel)) $snap['sobrescritos'][] = $rel; }
        else $snap['novos'][] = $rel;
    }
    foreach ($plano['retirar'] ?? [] as $rel) { if ($guardar($rel)) $snap['removidos'][] = $rel; }
    $manifestos = $base . 'installation' . $ds . 'manifests' . $ds;
    foreach (glob($manifestos . '*.json') ?: [] as $m) {
        if (!is_dir($dir . 'manifests')) @mkdir($dir . 'manifests', 0775, true);
        @copy($m, $dir . 'manifests' . $ds . basename($m));
    }
    $snap['manifestos_anteriores'] = array_map('basename', glob($manifestos . '*.json') ?: []);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . 'snapshot.json', json_encode($snap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return ['sobrescritos' => count($snap['sobrescritos']), 'removidos' => count($snap['removidos']), 'novos' => count($snap['novos'])];
}

/** Acrescenta dados ao `snapshot.json` (ex.: caminho do dump do banco). */
function instalacao_snapshot_anotar(string $dir, array $dados): void {
    $f = instalacao_caminho_base($dir) . 'snapshot.json';
    $s = json_decode((string)@file_get_contents($f), true);
    if (!is_array($s)) return;
    @file_put_contents($f, json_encode(array_merge($s, $dados), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * Volta os arquivos ao estado do snapshot: tira os novos, devolve os sobrescritos e os removidos e
 * restaura os manifestos. O banco é à parte (dump anotado no snapshot).
 *
 * @return array ['restaurados' => n, 'removidos_novos' => n, 'falhas' => rel[]] ou ['erro' => texto]
 */
function instalacao_snapshot_restaurar(string $base, string $dir): array {
    $base = instalacao_caminho_base($base);
    $dir = instalacao_caminho_base($dir);
    $ds = DIRECTORY_SEPARATOR;
    $snap = json_decode((string)@file_get_contents($dir . 'snapshot.json'), true);
    if (!is_array($snap)) return ['erro' => 'snapshot.json ausente ou ilegível em ' . $dir];
    $r = ['restaurados' => 0, 'removidos_novos' => 0, 'falhas' => []];
    foreach ($snap['novos'] ?? [] as $rel) { $f = $base . str_replace('/', $ds, $rel); if (is_file($f) && @unlink($f)) $r['removidos_novos']++; }
    foreach (array_merge($snap['sobrescritos'] ?? [], $snap['removidos'] ?? []) as $rel) {
        $de = $dir . 'files' . $ds . str_replace('/', $ds, $rel);
        $para = $base . str_replace('/', $ds, $rel);
        if (!is_dir(dirname($para))) @mkdir(dirname($para), 0775, true);
        if (is_file($de) && @copy($de, $para)) $r['restaurados']++; else $r['falhas'][] = $rel;
    }
    $manifestos = $base . 'installation' . $ds . 'manifests' . $ds;
    $anteriores = $snap['manifestos_anteriores'] ?? [];
    foreach (glob($manifestos . '*.json') ?: [] as $m) if (!in_array(basename($m), $anteriores, true)) @unlink($m);
    foreach ($anteriores as $nome) { if (is_file($dir . 'manifests' . $ds . $nome)) @copy($dir . 'manifests' . $ds . $nome, $manifestos . $nome); }
    return $r;
}

/** Mantém só os `$manter` snapshots mais recentes numa pasta. */
function instalacao_snapshot_podar(string $raiz, int $manter = 5): int {
    $pastas = glob(instalacao_caminho_base($raiz) . '*', GLOB_ONLYDIR) ?: [];
    usort($pastas, function ($a, $b) { return filemtime($b) <=> filemtime($a); });
    $removidas = 0;
    foreach (array_slice($pastas, max(0, $manter)) as $p) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        if (@rmdir($p)) $removidas++;
    }
    return $removidas;
}
