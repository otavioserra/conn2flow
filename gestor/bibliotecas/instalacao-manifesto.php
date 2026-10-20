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

/**
 * req-278: arquivos do próprio atualizador. Na camada `core` eles são SEMPRE escritos quando a versão nova
 * é outra, mesmo que o disco pareça editado — senão um manifesto desatualizado (deploy por SSH, que não
 * grava o manifesto do core) segura o atualizador antigo e a correção dele nunca chega (temp.conn2flow.com,
 * 15/10: a 3.0.5 preservou o próprio `atualizacoes-sistema.php`). O snapshot da entrega guarda o anterior.
 * Sobreposição por camada superior (projeto, plugin) continua respeitada.
 */
const INSTALACAO_CORE_SEMPRE_ESCREVER = [
    'controladores/atualizacoes/',
    'modulos/admin-atualizacoes/',
    'bibliotecas/deploy-lock.php',
    'bibliotecas/db-data.php',
    'bibliotecas/instalacao-',
    'bibliotecas/atualizacoes-',
    // REQ-286: o que é da instalação mora no `.env`; um `config.php` preservado como "editado" segurava a
    // versão (selo "v3.0.8" depois da 3.0.11) e a lista de bibliotecas (sem `api-crud`) no Lab.
    'config.php',
];

/** req-278: o arquivo do core é do atualizador (sempre escrito)? Função PURA. */
function instalacao_core_sempre_escrever(string $camada, string $rel): bool {
    if ($camada !== 'core') return false;
    $rel = str_replace('\\', '/', $rel);
    foreach (INSTALACAO_CORE_SEMPRE_ESCREVER as $prefixo) {
        if ($rel === $prefixo || strpos($rel, $prefixo) === 0) return true;
    }
    return false;
}

/**
 * REQ-286: artefatos de sistema — saída de build ou transporte de dados, que ninguém edita à mão. A camada dona
 * SEMPRE os escreve (a cópia anterior fica no snapshot) e eles nunca viram choque "editado": no Lab, centenas de
 * choques eram `vendor/`, `*.precompiled.css` e `db/data/` regravados por sincronizações de teste. Sobreposto por
 * camada superior continua preservado, mas o choque já nasce resolvido (`automatico`). `db/data/` é transporte:
 * o dado que a pessoa edita mora no banco, protegido por `user_modified`.
 */
const INSTALACAO_ARTEFATOS_PREFIXOS = ['vendor/', 'db/data/'];
const INSTALACAO_ARTEFATOS_SUFIXOS = ['.min.js', '.precompiled.css'];
const INSTALACAO_ARTEFATOS_EXATOS = ['assets/asset-versions.json', 'assets/modulos/covers/manifest.json', 'resources/.tailwind-build-manifest.json'];

/** REQ-286: o caminho é artefato de sistema? Função PURA. */
function instalacao_artefato_sistema(string $rel): bool {
    $rel = str_replace('\\', '/', $rel);
    if (in_array($rel, INSTALACAO_ARTEFATOS_EXATOS, true)) return true;
    foreach (INSTALACAO_ARTEFATOS_PREFIXOS as $p) if (strpos($rel, $p) === 0) return true;
    foreach (INSTALACAO_ARTEFATOS_SUFIXOS as $s) if (substr($rel, -strlen($s)) === $s) return true;
    return false;
}

/** REQ-286: extensões de texto, em que diferença só de quebra de linha (CRLF x LF) não é edição. */
const INSTALACAO_EXTENSOES_TEXTO = ['php', 'js', 'mjs', 'css', 'json', 'html', 'htm', 'md', 'txt', 'xml', 'svg', 'sh', 'yml', 'yaml', 'ini', 'sql', 'env', 'example', 'htaccess', 'conf', 'sample'];

/** REQ-286: sha256 do arquivo de texto com `\r\n` trocado por `\n` (null: binário, ausente ou grande demais). */
function instalacao_hash_disco_lf(string $base, string $rel): ?string {
    $ext = strtolower((string)pathinfo($rel, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, INSTALACAO_EXTENSOES_TEXTO, true)) return null;
    $f = instalacao_caminho_base($base) . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($f) || filesize($f) > 64 * 1024 * 1024) return null;
    $t = (string)file_get_contents($f);
    return strpos($t, "\r\n") === false ? null : hash('sha256', str_replace("\r\n", "\n", $t));
}

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

/**
 * REQ-285: pasta de dados que a etapa de banco da atualização do core deve ler.
 *
 * `db/data/` tem dois donos com os mesmos nomes de arquivo (core e projeto). Quando o projeto reivindica um
 * `*Data.json`, o core preserva o do projeto e guarda o seu em `backups/overrides/<versão>/`; e um projeto
 * pode deixar no disco o conjunto particionado de uma tabela (`.manifest.json` + partes) que encobre o
 * arquivo único entregue pelo core. Ler `db/data/` nesses casos sincronizava os dados do projeto como se
 * fossem do core, e a retirada por dono desativava páginas e módulos do core (Lab, 3.0.8 → 3.0.9: a página
 * do próprio `admin-atualizacoes` saiu do ar no meio da atualização).
 *
 * Sem conflito, devolve null (a etapa lê `db/data/` como sempre). Com conflito, monta `$destino` só com os
 * arquivos de dados que o core entregou nesta versão — a cópia guardada em `overrides/` quando o disco
 * ficou com a do projeto — e devolve a pasta (com separador no fim).
 */
function instalacao_core_dados_dir(string $base, string $versao, string $destino): ?string {
    $base = instalacao_caminho_base($base);
    $manifesto = instalacao_manifesto_ler($base, 'core');
    if (!$manifesto) return null;
    $ds = DIRECTORY_SEPARATOR;
    $dados = $base . 'db' . $ds . 'data' . $ds;
    $overrides = $base . 'backups' . $ds . 'overrides' . $ds . preg_replace('/[^A-Za-z0-9._-]/', '_', $versao) . $ds . 'db' . $ds . 'data' . $ds;
    $tabela = function (string $nome): ?string {
        return preg_match('/^([A-Za-z][A-Za-z0-9_]*Data)(?:\.manifest|\.part-\d+)?\.json$/D', $nome, $m) ? $m[1] : null;
    };
    $fontes = []; $tabelasCore = []; $conflito = false;
    foreach (array_keys($manifesto['arquivos']) as $rel) {
        $rel = str_replace('\\', '/', (string)$rel);
        if (strpos($rel, 'db/data/') !== 0) continue;
        $nome = substr($rel, 8);
        if ($nome === '' || strpos($nome, '/') !== false) continue;
        if (($t = $tabela($nome)) !== null) $tabelasCore[$t] = true;
        if ($versao !== '' && is_file($overrides . $nome)) { $fontes[$nome] = $overrides . $nome; $conflito = true; }
        elseif (is_file($dados . $nome)) $fontes[$nome] = $dados . $nome;
    }
    if (!$fontes) return null;
    // Arquivo de uma tabela do core que o core não entregou (ex.: particionado do projeto) encobre o do core.
    foreach (glob($dados . '*.json') ?: [] as $f) {
        $nome = basename($f);
        if (isset($fontes[$nome])) continue;
        $t = $tabela($nome);
        if ($t !== null && isset($tabelasCore[$t])) { $conflito = true; break; }
    }
    if (!$conflito) return null;
    $destino = instalacao_caminho_base($destino);
    if (!is_dir($destino) && !@mkdir($destino, 0775, true) && !is_dir($destino)) return null;
    foreach (glob($destino . '*') ?: [] as $velho) if (is_file($velho)) @unlink($velho);
    foreach ($fontes as $nome => $origem) {
        if (!@copy($origem, $destino . $nome)) return null;
    }
    return $destino;
}

/** Caminho relativo com `/`. */
function instalacao_rel(string $raiz, string $caminho): string {
    return str_replace('\\', '/', ltrim(substr($caminho, strlen(instalacao_caminho_base($raiz))), '/\\'));
}

/** O caminho relativo fica fora do manifesto? (pastas protegidas e `installation/`) */
function instalacao_fora(string $rel): bool {
    $topo = explode('/', $rel, 2)[0];
    // REQ-286: controle do servidor (dono das migrações, req-194) não é entrega de nenhuma camada.
    if (preg_match('#^db/\.c2f-migrations-[^/]*\.json$#', $rel)) return true;
    return in_array($topo, INSTALACAO_PASTAS_FORA, true);
}

/** Caminho seguro para leitura ou extração: sem segmentos especiais, ocultos ou pastas privadas. */
function instalacao_recuperacao_caminho_valido(string $rel): bool {
    if ($rel === '' || strpos($rel, '\\') !== false || strpos($rel, "\0") !== false || $rel[0] === '/') return false;
    foreach (explode('/', $rel) as $parte) {
        if ($parte === '' || $parte === '.' || $parte === '..' || $parte[0] === '.' || strpos($parte, ':') !== false) return false;
    }
    return !instalacao_fora($rel);
}

/** Confere contenção, destino real e hash antes de colocar um arquivo no ZIP. */
function instalacao_recuperacao_arquivo_seguro(string $base, string $rel, string $hash): ?string {
    if (!instalacao_recuperacao_caminho_valido($rel)) return null;
    $baseReal = realpath($base);
    if (!$baseReal) return null;
    $path = instalacao_caminho_base($base) . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $real = realpath($path);
    if (!$real || !is_file($real) || is_link($path) || strpos($real, rtrim($baseReal, '/\\') . DIRECTORY_SEPARATOR) !== 0) return null;
    $resolvedRel = str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen(rtrim($baseReal, '/\\')) + 1));
    if (!instalacao_recuperacao_caminho_valido($resolvedRel)) return null;
    return hash_equals($hash, (string)hash_file('sha256', $real)) ? $real : null;
}

/** Inventário de divergências, atribuído à camada dona de cada caminho. */
function instalacao_recuperacao_inventario(string $base, array $camadas = [], array $caminhos = [], array $estados = []): array {
    $manifestos = instalacao_manifestos($base);
    $donos = [];
    foreach ($manifestos as $camada => $manifesto) {
        foreach ($manifesto['arquivos'] as $rel => $hash) {
            if (!is_string($rel) || !instalacao_recuperacao_caminho_valido($rel)) continue;
            if (!isset($donos[$rel]) || instalacao_nivel($camada) > instalacao_nivel($donos[$rel]['camada'])) {
                $donos[$rel] = ['camada' => $camada, 'hash_manifesto' => $hash];
            }
        }
    }
    $disco = [];
    $baseNormal = instalacao_caminho_base($base);
    if (is_dir($baseNormal)) {
        $tree = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($baseNormal, FilesystemIterator::SKIP_DOTS),
            static function ($file) use ($baseNormal) {
                return !$file->isLink() && instalacao_recuperacao_caminho_valido(instalacao_rel($baseNormal, $file->getPathname()));
            }
        );
        $it = new RecursiveIteratorIterator($tree);
        foreach ($it as $file) {
            $rel = instalacao_rel($baseNormal, $file->getPathname());
            if (!$file->isFile()) continue;
            $disco[$rel] = hash_file('sha256', $file->getPathname());
        }
    }
    $out = [];
    foreach ($donos as $rel => $d) {
        $hashDisco = $disco[$rel] ?? null;
        if ($hashDisco === $d['hash_manifesto']) continue;
        $out[$rel] = ['camada' => $d['camada'], 'caminho' => $rel, 'estado' => $hashDisco === null ? 'ausente' : 'editado',
            'hash_disco' => $hashDisco, 'hash_manifesto' => $d['hash_manifesto']];
    }
    foreach ($disco as $rel => $hash) {
        if (isset($donos[$rel]) || !instalacao_recuperacao_caminho_valido($rel)) continue;
        $out[$rel] = ['camada' => null, 'caminho' => $rel, 'estado' => 'fora-do-manifesto',
            'hash_disco' => $hash, 'hash_manifesto' => null];
    }
    $out = array_filter($out, static function ($r) use ($camadas, $caminhos, $estados) {
        if ($camadas && !in_array($r['camada'], $camadas, true)) return false;
        if ($estados && !in_array($r['estado'], $estados, true)) return false;
        foreach ($caminhos as $prefixo) if ($prefixo !== '' && ($r['caminho'] === $prefixo || strpos($r['caminho'], rtrim($prefixo, '/') . '/') === 0)) return true;
        return !$caminhos;
    });
    ksort($out);
    return array_values($out);
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
 * @param bool $retirarConfirmado REQ-285: o operador pediu a retirada. Sem isso, o arquivo intacto que a camada
 *                   deixou de entregar fica no disco e vira choque `retirado` (manter é o padrão).
 * @return array ['primeira' => bool, 'escrever' => rel[], 'preservar' => [rel => info], 'retirar' => rel[],
 *               'restaurar' => [rel => camada inferior], 'originais' => rel[], 'retirar_choque' => [rel => info],
 *               'manifesto' => mapa final da camada]
 */
function instalacao_planejar(string $base, string $camada, array $pacote, bool $completo, ?array $manifestos = null, ?array $listaCompleta = null, bool $retirarConfirmado = false): array {
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
    // req-199: "manter" decidido antes para este arquivo, com o disco ainda igual ao da decisão, vale de
    // novo: o choque já nasce resolvido (a versão nova continua guardada em backups/overrides/).
    $regras = instalacao_regras_ler($base);
    $regra = function (string $rel, ?string $hDisco) use ($regras): array {
        $r = $regras[$rel] ?? null;
        return ($r && ($r['acao'] ?? '') === 'manter' && ($r['hash_disco'] ?? null) === $hDisco) ? ['resolucao' => 'manter-regra'] : [];
    };

    $plano = ['primeira' => $anterior === null, 'escrever' => [], 'preservar' => [], 'retirar' => [], 'restaurar' => [],
        'originais' => [], 'retirar_choque' => [], 'manifesto' => [], 'forcados' => []];

    foreach ($pacote as $rel => $hNovo) {
        if (instalacao_fora($rel)) continue;
        $hDisco = instalacao_hash_disco($base, $rel);
        $sup = $dono($superiores, $rel);
        $artefato = instalacao_artefato_sistema($rel);
        // REQ-286: diferença só de quebra de linha não é edição. Igual ao novo: escreve (normaliza) sem choque;
        // igual ao que a camada entregou antes: está intacto.
        if ($sup === null && $hDisco !== null && $hDisco !== $hNovo && ($lf = instalacao_hash_disco_lf($base, $rel)) !== null) {
            if ($lf === $hNovo) { $plano['escrever'][] = $rel; continue; }
            if ($anterior !== null && ($anterior[$rel] ?? null) === $lf) $hDisco = $lf;
        }
        // req-278: atualizador do core sempre chega (exceto se uma camada superior o sobrepõe).
        // REQ-286: artefatos de sistema também, em qualquer camada.
        if ($sup === null && $hDisco !== $hNovo && ($artefato || instalacao_core_sempre_escrever($camada, $rel))) {
            $plano['escrever'][] = $rel;
            if ($anterior !== null && $hDisco !== null && ($anterior[$rel] ?? null) !== $hDisco) $plano['forcados'][] = $rel;
            continue;
        }
        // REQ-286: artefato sobreposto por camada superior fica com ela (a versão nova vai para overrides/, onde a
        // etapa de banco do core a encontra), mas o choque nasce resolvido: não há o que decidir.
        if ($sup !== null && $artefato) {
            if ($hDisco !== null && $hDisco !== $hNovo) $plano['preservar'][$rel] = ['motivo' => 'sobreposto', 'camada_dona' => $sup, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo, 'resolucao' => 'automatico'];
            continue;
        }
        // req-199: a camada entrega o mesmo conteúdo da entrega anterior e o disco é outro. Só não há nada novo a
        // decidir quando a divergência JÁ foi decidida (regra gravada para este disco) ou quando a camada de cima é
        // a dona. REQ-286: antes pulava sempre — disco mudado por fora (sync de teste, edição) ficava velho em
        // silêncio para sempre (316 arquivos do projeto no Lab; 10 de `db/data` na produção).
        if ($anterior !== null && $hDisco !== null && $hDisco !== $hNovo && ($anterior[$rel] ?? null) === $hNovo) {
            if ($sup !== null || (($regras[$rel]['hash_disco'] ?? null) === $hDisco)) continue;
            $inf = $dono($inferiores, $rel);
            if ($inf !== null && ($inferiores[$inf][$rel] ?? null) === $hDisco) {
                // O disco voltou para a versão da camada de baixo: a de cima reescreve a sua (e guarda a original).
                $plano['originais'][] = $rel;
                $plano['escrever'][] = $rel;
                continue;
            }
            $plano['preservar'][$rel] = ['motivo' => 'editado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo];
            continue;
        }
        if ($sup !== null && $hDisco !== null && $hDisco !== $hNovo) {
            $plano['preservar'][$rel] = ['motivo' => 'sobreposto', 'camada_dona' => $sup, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo] + $regra($rel, $hDisco);
            continue;
        }
        if ($anterior !== null && $hDisco !== null && $hDisco !== $hNovo && ($anterior[$rel] ?? null) !== $hDisco && !isset($todos[$rel][$hDisco])) {
            $plano['preservar'][$rel] = ['motivo' => 'editado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => $hNovo] + $regra($rel, $hDisco);
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
                $plano['retirar_choque'][$rel] = ['motivo' => 'retirado-editado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => null] + $regra($rel, $hDisco);
                continue;
            }
            $inf = $dono($inferiores, $rel);
            if ($inf !== null) $plano['restaurar'][$rel] = $inf;
            elseif ($retirarConfirmado) $plano['retirar'][] = $rel;
            else $plano['retirar_choque'][$rel] = ['motivo' => 'retirado', 'camada_dona' => null, 'hash_disco' => $hDisco, 'hash_novo' => null] + $regra($rel, $hDisco);
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
 * @param array|null $registro REQ-285: `['origem' => ..., 'execucao' => ...]` registra os choques antes de
 *                   gravar o manifesto (null: o chamador registra; legado).
 * @return array ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
 *               'choques' => [['caminho','motivo','camada_dona','hash_disco','hash_novo','copia','diff']],
 *               'choques_arquivo' => string|null, 'manifesto_gravado' => bool]
 */
function instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar', ?array $registro = null): array {
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
        $diff = instalacao_diff_arquivos($abs($base, $r), $abs($pacoteRaiz, $r), $r);
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

    // REQ-285: choques registrados ANTES do manifesto. O manifesto diz "esta versão foi entregue"; gravado
    // sem os choques (fatal ou falha no registro), a entrega seguinte via o mesmo conteúdo e não pedia mais
    // decisão: os arquivos preservados ficavam velhos em silêncio. Falhou o registro, o manifesto fica.
    $rel['choques_arquivo'] = null;
    if ($registro !== null && $rel['choques']) {
        $rel['choques_arquivo'] = instalacao_choques_registrar($base, (string)($registro['origem'] ?? ''), $camada, $versao, $registro['execucao'] ?? null, $rel['choques']);
        if ($rel['choques_arquivo'] === null) { $rel['manifesto_gravado'] = false; return $rel; }
    }
    // REQ-286: choque "editado" registrado e ainda sem decisão marca o disco como `pendente`. A mesma entrega de
    // novo não repete o choque (ele está visível na tela); a divergência que nunca virou choque continua aparecendo.
    $pendentes = [];
    foreach ($plano['preservar'] as $r => $info) {
        if (($info['motivo'] ?? '') === 'editado' && empty($info['resolucao']) && !empty($info['hash_disco'])) $pendentes[$r] = $info;
    }
    if ($pendentes) {
        $regras = instalacao_regras_ler($base);
        foreach ($pendentes as $r => $info) {
            if (in_array($regras[$r]['acao'] ?? '', ['manter', 'mesclar'], true) && ($regras[$r]['hash_disco'] ?? null) === $info['hash_disco']) continue;
            $regras[$r] = ['acao' => 'pendente', 'camada' => $camada, 'motivo' => 'editado', 'hash_disco' => $info['hash_disco'], 'hash_novo' => $info['hash_novo'] ?? null, 'em' => date('c')];
        }
        instalacao_regras_gravar($base, $regras);
    }
    $rel['manifesto_gravado'] = instalacao_manifesto_gravar($base, $camada, $plano['manifesto'], $versao);
    return $rel;
}

/**
 * Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só
 * o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
 */
/**
 * Diff de dois arquivos sem carregá-los quando passam do limite (req-284).
 *
 * O `instalacao_diff` já desiste acima de INSTALACAO_DIFF_MAX_BYTES, mas quem chamava lia os dois lados
 * inteiros antes: um seed de 83 MB marcado como editado estourava o memory_limit de 128 MB e a API de
 * deploy do projeto respondia 500 (`PaginasData.part-001.json` do conn2flow-site no Lab).
 */
function instalacao_diff_arquivos(string $antes, string $depois, string $rotulo = ''): ?string {
    $tamanhoAntes = is_file($antes) ? (int)@filesize($antes) : 0;
    $tamanhoDepois = is_file($depois) ? (int)@filesize($depois) : 0;
    if ($tamanhoAntes > INSTALACAO_DIFF_MAX_BYTES || $tamanhoDepois > INSTALACAO_DIFF_MAX_BYTES) {
        return '(arquivo grande demais para o diff)';
    }
    return instalacao_diff((string)@file_get_contents($antes), (string)@file_get_contents($depois), $rotulo);
}

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
    // REQ-285: um choque por vez. Centenas de choques com diff num só `json_encode` (e com PRETTY_PRINT)
    // passavam dos 128 MB do PHP-FPM no deploy de projeto pela API.
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
    $tmp = $f . '.tmp';
    $h = @fopen($tmp, 'wb');
    if (!$h) return null;
    $cabecalho = json_encode(['origem' => $origem, 'camada' => $camada, 'versao' => $versao, 'execucao' => $execucao, 'registrado_em' => date('c')], $flags);
    $ok = $cabecalho !== false && fwrite($h, substr($cabecalho, 0, -1) . ',"choques":[') !== false;
    $primeiro = true;
    foreach ($choques as $c) {
        if (!$ok) break;
        $j = json_encode($c, $flags);
        $ok = $j !== false && fwrite($h, ($primeiro ? "\n" : ",\n") . $j) !== false;
        $primeiro = false;
    }
    $ok = $ok && fwrite($h, "\n]}\n") !== false;
    fclose($h);
    if (!$ok || !@rename($tmp, $f)) { @unlink($tmp); return null; }
    return $f;
}

/**
 * REQ-289: republica em `dist/` os assets que mudaram desde a última publicação.
 *
 * O `public_html/dist/` (req-028) é servido direto pelo servidor web: o `.manifest.json` lista cada arquivo publicado,
 * sua `fonte` (relativa ao gestor) e o hash, e a URL leva `?v=<hash publicado>`. Nenhuma entrega (atualização do
 * sistema, deploy de projeto) republicava: no Lab e na produção o navegador seguia recebendo o JS de 16/10 — a aba
 * de choques da REQ-286 nunca chegou. Aqui, para cada item do manifesto, a fonte atual (o `.min.js` quando a publicação
 * é minificada e ele não está vazio, como no `assets:publish`) é comparada pelo sha1; mudou, o arquivo publicado e o
 * registro (`sha1`, `bytes`, `v`) são atualizados. Não publica arquivos novos nem remove nada: o que não está no
 * manifesto segue servido pelo controlador estático (sempre atual). Função PURA (sem `$_GESTOR`).
 *
 * @return array ['verificados' => n, 'atualizados' => n, 'sem_fonte' => n, 'falhas' => n]
 */
function instalacao_dist_ressincronizar(string $gestorRaiz, string $distPath): array {
    $res = ['verificados' => 0, 'atualizados' => 0, 'sem_fonte' => 0, 'falhas' => 0];
    $distPath = rtrim($distPath, '/\\');
    $arqManifesto = $distPath . DIRECTORY_SEPARATOR . '.manifest.json';
    if ($distPath === '' || !is_file($arqManifesto)) return $res;
    $m = json_decode((string)@file_get_contents($arqManifesto), true);
    if (!is_array($m) || !is_array($m['arquivos'] ?? null)) return $res;
    $raiz = instalacao_caminho_base($gestorRaiz);
    $preferirMin = !empty($m['minificado']);
    foreach ($m['arquivos'] as $url => $registro) {
        $fonte = str_replace('\\', '/', (string)($registro['fonte'] ?? ''));
        if ($fonte === '' || strpos($fonte, '..') !== false || strpos((string)$url, '..') !== false) continue;
        $res['verificados']++;
        // A fonte registrada pode ser o próprio .min.js: volta ao de autoria e escolhe de novo.
        $autoria = substr($fonte, -7) === '.min.js' ? substr($fonte, 0, -7) . '.js' : $fonte;
        $fisico = $raiz . str_replace('/', DIRECTORY_SEPARATOR, $autoria);
        if ($preferirMin && substr($autoria, -3) === '.js') {
            $min = substr($fisico, 0, -3) . '.min.js';
            if (is_file($min) && filesize($min) > 0) $fisico = $min;
        }
        if (!is_file($fisico)) { $res['sem_fonte']++; continue; }
        $conteudo = (string)file_get_contents($fisico);
        $hash = sha1($conteudo);
        if ($hash === ($registro['sha1'] ?? null)) continue;
        $destino = $distPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$url);
        if (!is_dir(dirname($destino))) @mkdir(dirname($destino), 0775, true);
        $tmp = $destino . '.' . bin2hex(random_bytes(3)) . '.tmp';
        if (@file_put_contents($tmp, $conteudo) === false || !@rename($tmp, $destino)) { @unlink($tmp); $res['falhas']++; continue; }
        $m['arquivos'][$url] = ['sha1' => $hash, 'bytes' => strlen($conteudo), 'v' => substr($hash, 0, 16), 'fonte' => instalacao_rel($raiz, $fisico)] + $registro;
        $res['atualizados']++;
    }
    if ($res['atualizados']) {
        $m['gerado_em'] = date('c');
        $tmp = $arqManifesto . '.' . bin2hex(random_bytes(3)) . '.tmp';
        if (@file_put_contents($tmp, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") === false || !@rename($tmp, $arqManifesto)) {
            @unlink($tmp);
            $res['falhas']++;
        }
    }
    return $res;
}

/**
 * REQ-286: o choque de arquivo perdeu o objeto? Edição/sobreposição em que o disco já tem a versão nova do choque
 * (ou a última que a camada entregou), e retirada cujo arquivo já não existe. Choque de registro (`db:`) não entra.
 * Função PURA sobre o disco e os manifestos (`$manifestos` = `instalacao_manifestos()`).
 */
function instalacao_choque_superado(string $base, array $choque, array $manifestos): bool {
    $rel = (string)($choque['caminho'] ?? '');
    if ($rel === '' || strpos($rel, 'db:') === 0 || strpos($rel, '..') !== false) return false;
    // REQ-286 r3: caminho que deixou de fazer parte de qualquer entrega não tem o que decidir.
    if (preg_match('#^db/\.c2f-migrations-[^/]*\.json$#', $rel)) return true;
    $tipo = (string)($choque['tipo'] ?? 'arquivo');
    $disco = instalacao_hash_disco($base, $rel);
    if ($tipo === 'retirada') return $disco === null;
    if ($tipo !== 'arquivo' || $disco === null) return false;
    if (!empty($choque['hash_novo']) && $disco === $choque['hash_novo']) return true;
    $camada = (string)($choque['camada'] ?? '');
    $entregue = $manifestos[$camada]['arquivos'][$rel] ?? null;
    // Sobreposto: o disco é da camada de cima por definição — não é superado por estar diferente.
    return ($choque['motivo'] ?? '') !== 'sobreposto' && $entregue !== null && $disco === $entregue;
}

/**
 * Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem
 * resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por
 * atualização. `$escapar` escapa um valor para SQL.
 */
function instalacao_choque_pendente_filtro(array $linha, callable $escapar): string {
    $w = [empty($linha['resolucao']) ? 'resolucao IS NULL' : "resolucao='" . $escapar((string)$linha['resolucao']) . "'"];
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
            if (!empty($c['resolucao'])) $linha['resolucao'] = (string)$c['resolucao'];
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

// ============================================================================ banco e verificação (req-198 / BATCH-204)
// Compartilhado pela atualização do sistema (CLI) e pelo deploy de projeto por API.

/**
 * Pasta do snapshot de uma execução. Id só com dígitos vira `exec-<id>` (atualização do sistema); os
 * outros (ex.: `api-20260930-120000-ab12`) valem como estão. Caracteres fora de `[A-Za-z0-9_-]` saem.
 */
function instalacao_snapshot_dir(string $base, string $id): ?string {
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    if ($id === '') return null;
    if (ctype_digit($id)) $id = 'exec-' . $id;
    return instalacao_caminho_base($base) . 'backups' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'snapshots' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
}

/**
 * Comando de shell com `pipefail` (a falha do `mysqldump` não some atrás do `gzip`). O `/bin/sh` do Debian
 * e do Ubuntu é o dash, que não tem `pipefail` e sai com código 2 no `set -o pipefail`; por isso usa o bash
 * quando existe e, sem ele, o `sh` sem `pipefail` (o tamanho do arquivo ainda é conferido).
 */
function instalacao_shell_pipefail(string $cmd): array {
    foreach (['/bin/bash', '/usr/bin/bash'] as $bash) if (is_executable($bash)) return [$bash, '-o', 'pipefail', '-c', $cmd];
    return ['/bin/sh', '-c', $cmd];
}

/** Roda um pipe de shell com a senha do banco em `MYSQL_PWD`. @return array ['codigo' => int, 'erro' => string] */
function instalacao_banco_processo(string $cmd, array $banco): array {
    if (!function_exists('proc_open')) return ['codigo' => -1, 'erro' => 'proc_open indisponível'];
    $env = array_merge($_ENV ?: [], ['MYSQL_PWD' => (string)($banco['senha'] ?? ''), 'PATH' => getenv('PATH') ?: '/usr/bin:/bin']);
    $proc = @proc_open(instalacao_shell_pipefail($cmd), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    if (!is_resource($proc)) return ['codigo' => -1, 'erro' => 'não foi possível iniciar o processo'];
    stream_get_contents($pipes[1]);
    $erro = trim((string)stream_get_contents($pipes[2]));
    fclose($pipes[1]); fclose($pipes[2]);
    return ['codigo' => proc_close($proc), 'erro' => $erro];
}

/**
 * Dump do banco (`mysqldump --single-transaction`, gzip) em `<dir>/banco.sql.gz`, anotado no `snapshot.json`.
 * `$banco`: host, usuario, senha, nome (o `$_BANCO` do Gestor).
 *
 * @return array ['ok' => bool, 'arquivo' => string|null, 'mb' => float, 'erro' => string]
 */
function instalacao_banco_dump(string $dir, array $banco): array {
    if (empty($banco['nome'])) return ['ok' => false, 'arquivo' => null, 'mb' => 0.0, 'erro' => 'configuração do banco ausente'];
    $arquivo = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'banco.sql.gz';
    $cmd = 'mysqldump --single-transaction --quick --routines --no-tablespaces -h ' . escapeshellarg((string)($banco['host'] ?? 'localhost'))
        . ' -u ' . escapeshellarg((string)($banco['usuario'] ?? '')) . ' ' . escapeshellarg((string)$banco['nome']) . ' | gzip -c > ' . escapeshellarg($arquivo);
    $p = instalacao_banco_processo($cmd, $banco);
    if ($p['codigo'] !== 0 || !is_file($arquivo) || filesize($arquivo) < 20) {
        @unlink($arquivo);
        return ['ok' => false, 'arquivo' => null, 'mb' => 0.0, 'erro' => 'código ' . $p['codigo'] . ($p['erro'] !== '' ? ': ' . substr($p['erro'], 0, 300) : '')];
    }
    instalacao_snapshot_anotar($dir, ['dump_banco' => 'banco.sql.gz']);
    return ['ok' => true, 'arquivo' => $arquivo, 'mb' => round(filesize($arquivo) / 1048576, 2), 'erro' => ''];
}

/** Restaura `banco.sql.gz` no banco. @return array ['ok' => bool, 'erro' => string] */
function instalacao_banco_restaurar(string $dump, array $banco): array {
    if (!is_file($dump)) return ['ok' => false, 'erro' => 'dump do banco ausente no snapshot'];
    if (empty($banco['nome'])) return ['ok' => false, 'erro' => 'configuração do banco ausente'];
    $cmd = 'gunzip -c ' . escapeshellarg($dump) . ' | mysql -h ' . escapeshellarg((string)($banco['host'] ?? 'localhost'))
        . ' -u ' . escapeshellarg((string)($banco['usuario'] ?? '')) . ' ' . escapeshellarg((string)$banco['nome']);
    $p = instalacao_banco_processo($cmd, $banco);
    return ['ok' => $p['codigo'] === 0, 'erro' => $p['codigo'] === 0 ? '' : 'código ' . $p['codigo'] . ($p['erro'] !== '' ? ': ' . substr($p['erro'], 0, 300) : '')];
}

/** Tamanho atual do log de erros do PHP (para achar fatais novos depois). */
function instalacao_saude_log_offset(string $base): int {
    $f = instalacao_caminho_base($base) . 'logs' . DIRECTORY_SEPARATOR . 'php-error.log';
    return is_file($f) ? (int)filesize($f) : 0;
}

/**
 * Verificação depois de uma entrega: sem erro fatal novo no log do PHP e a raiz do site respondendo
 * abaixo de 500.
 *
 * A requisição HTTP vai para `https://<domínio>/<URL_RAIZ>` (ou `$opcoes['url']`).
 * - Com `$opcoes['ip']`, o nome é resolvido só para esse IP.
 * - Sem ele, tenta o DNS normal e, se não conectar, `127.0.0.1` (servidor sem DNS para o próprio nome).
 * Sem conexão em nenhuma tentativa, o HTTP fica inconclusivo: vira aviso, não falha. Um servidor web
 * pode escutar só no IP público (HestiaCP), e reprovar por isso voltaria toda entrega.
 * Antes do HTTP espera `$opcoes['espera']` segundos (padrão 3): o OPcache do PHP-FPM revalida os arquivos
 * a cada `opcache.revalidate_freq` (2 s por padrão) e, antes disso, serviria o código antigo.
 * Sem cURL, só o log vale. `$opcoes['http']` substitui a requisição nos testes: fn(url, ip|null): int.
 *
 * @return array ['ok' => bool, 'motivos' => string[], 'http' => int|null, 'avisos' => string[]]
 */
function instalacao_saude_verificar(string $base, int $offsetLog, string $dominio, array $opcoes = []): array {
    $motivos = []; $avisos = []; $http = null;
    $f = instalacao_caminho_base($base) . 'logs' . DIRECTORY_SEPARATOR . 'php-error.log';
    if (is_file($f) && filesize($f) > $offsetLog) {
        $h = @fopen($f, 'r');
        if ($h) {
            fseek($h, $offsetLog); $novo = (string)stream_get_contents($h, 1048576); fclose($h);
            if (preg_match_all('/PHP Fatal error:[^\n]*/', $novo, $m)) $motivos[] = 'Erro fatal novo no log: ' . substr($m[0][0], 0, 300);
        }
    }
    $pedir = $opcoes['http'] ?? (function_exists('curl_init') ? 'instalacao_saude_http' : null);
    $url = (string)($opcoes['url'] ?? '');
    if ($url === '' && $dominio !== '' && $dominio !== 'localhost') {
        $raiz = '/' . trim((string)($_ENV['URL_RAIZ'] ?? '/'), '/');
        $url = 'https://' . $dominio . ($raiz === '/' ? '/' : $raiz . '/');
    }
    if ($url !== '' && $pedir) {
        $espera = (int)($opcoes['espera'] ?? (isset($opcoes['http']) ? 0 : 3));
        if ($espera > 0) sleep($espera);
        $ips = !empty($opcoes['ip']) ? [(string)$opcoes['ip']] : [null, '127.0.0.1'];
        foreach ($ips as $ip) {
            $http = (int)$pedir($url, $ip);
            if ($http !== 0) break;
        }
        if ($http === 0) {
            $avisos[] = 'HTTP não verificado: sem conexão com ' . $url . ' (' . implode(', ', array_map(function ($i) { return $i ?? 'DNS'; }, $ips)) . '); use --health-url/--health-ip';
            $http = null;
        } elseif ($http >= 500) {
            $motivos[] = 'HTTP ' . $http . ' em ' . $url;
        }
    }
    return ['ok' => !$motivos, 'motivos' => $motivos, 'http' => $http, 'avisos' => $avisos];
}

/** Código HTTP de `$url` (0 sem conexão). Com `$ip`, o host da URL é resolvido para ele. */
function instalacao_saude_http(string $url, ?string $ip): int {
    $ch = curl_init($url);
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_FOLLOWLOCATION => false];
    if ($ip !== null) {
        $u = parse_url($url);
        $porta = (int)($u['port'] ?? ((($u['scheme'] ?? 'https') === 'http') ? 80 : 443));
        $o[CURLOPT_RESOLVE] = [($u['host'] ?? '') . ':' . $porta . ':' . $ip];
    }
    curl_setopt_array($ch, $o);
    curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return $codigo;
}

// ============================================================================ resolução de choques (req-199 / BATCH-205)
// Um motor para os dois sentidos: a entrega (core, plugin, projeto) registra o choque; a decisão por
// arquivo — sobrescrever, manter ou mesclar — é aplicada aqui, venha do painel, da API/CLI ou da extensão.

/** Regras que as resoluções deixam (`installation/regras.json`): `rel → [acao, camada, motivo, hash_disco, hash_novo, em]`. */
function instalacao_regras_ler(string $base): array {
    $f = instalacao_caminho_base($base) . 'installation' . DIRECTORY_SEPARATOR . 'regras.json';
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    return is_array($d['regras'] ?? null) ? $d['regras'] : [];
}

/** Grava as regras de forma atômica. */
function instalacao_regras_gravar(string $base, array $regras): bool {
    $dir = instalacao_caminho_base($base) . 'installation' . DIRECTORY_SEPARATOR;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) return false;
    ksort($regras);
    $tmp = $dir . 'regras.json.' . bin2hex(random_bytes(3)) . '.tmp';
    if (@file_put_contents($tmp, json_encode(['atualizado_em' => date('c'), 'regras' => $regras], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) return false;
    return @rename($tmp, $dir . 'regras.json');
}

/**
 * Decisões possíveis para um choque, pelo motivo:
 * - `editado` (mudança no servidor): sobrescrever com a versão nova, manter a do servidor ou mesclar;
 * - `sobreposto` (a camada de cima é a dona): manter ou mesclar. Sobrescrever não vale aqui: a próxima
 *   entrega da camada dona escreveria de novo; o lugar de desfazer a sobreposição é o repositório dela;
 * - `retirado-editado`: sobrescrever (aceita a retirada, o arquivo sai) ou manter;
 * - `retirado` (REQ-285): a camada deixou de entregar; nada sai sem o operador — sobrescrever (sai) ou manter;
 * - `original-ausente`: manter.
 */
function instalacao_choque_acoes(array $choque): array {
    switch ((string)($choque['motivo'] ?? '')) {
        case 'editado': return ['sobrescrever', 'manter', 'mesclar'];
        case 'sobreposto': return ['manter', 'mesclar'];
        case 'retirado-editado':
        case 'retirado': return ['sobrescrever', 'manter'];
        default: return ['manter'];
    }
}

/**
 * As duas versões de um choque: a que está no ar (disco) e a nova (cópia guardada em `backups/overrides/`).
 *
 * @return array ['no_ar' => string|null, 'nova' => string|null, 'binario' => bool]
 */
function instalacao_choque_versoes(string $base, array $choque): array {
    $base = instalacao_caminho_base($base);
    $ler = function (?string $rel) use ($base) {
        if ($rel === null || $rel === '' || strpos($rel, '..') !== false) return null;
        $f = $base . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($f) ? (string)file_get_contents($f) : null;
    };
    $noAr = $ler((string)($choque['caminho'] ?? ''));
    $nova = $ler($choque['copia'] ?? null);
    $binario = ($noAr !== null && strpos($noAr, "\0") !== false) || ($nova !== null && strpos($nova, "\0") !== false);
    return ['no_ar' => $noAr, 'nova' => $nova, 'binario' => $binario];
}

/**
 * Aplica a decisão sobre um choque no disco e guarda a regra que vale nas próximas entregas.
 * - `sobrescrever`: a versão nova (cópia) vai para o lugar; numa retirada, o arquivo sai;
 * - `manter`: nada muda no disco; a regra faz a próxima entrega do mesmo conteúdo já nascer resolvida;
 * - `mesclar`: `$mesclado` vai para o lugar (a próxima entrega com versão nova volta a pedir decisão).
 *
 * @param array $choque Linha do registro: caminho, camada, motivo, copia, hash_novo.
 * @return array ['ok' => bool, 'erro' => string, 'acao' => string, 'hash_disco' => string|null]
 */
function instalacao_choque_resolver(string $base, array $choque, string $acao, ?string $mesclado = null, bool $gravarRegras = true): array {
    $rel = (string)($choque['caminho'] ?? '');
    $falha = function (string $m) use ($acao) { return ['ok' => false, 'erro' => $m, 'acao' => $acao, 'hash_disco' => null]; };
    // REQ-286 r3: choque antigo de um caminho que saiu das entregas (ex.: `db/.c2f-migrations-*.json`). "Manter" não
    // mexe no disco nem precisa de regra: vale. As outras decisões continuam recusadas para caminho protegido.
    if ($acao === 'manter' && preg_match('#^db/\.c2f-migrations-[^/]*\.json$#', $rel)) return ['ok' => true, 'erro' => '', 'acao' => $acao, 'hash_disco' => null];
    if ($rel === '' || strpos($rel, '..') !== false || instalacao_fora($rel)
        || ((!$gravarRegras || isset($choque['copia_externa'])) && !instalacao_recuperacao_caminho_valido($rel))) return $falha('caminho inválido');
    if (!in_array($acao, instalacao_choque_acoes($choque), true)) return $falha('decisão "' . $acao . '" não vale para um choque "' . ($choque['motivo'] ?? '') . '"');
    $b = instalacao_caminho_base($base);
    $alvo = $b . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $escrever = function (string $conteudo) use ($alvo) {
        if (!is_dir(dirname($alvo))) @mkdir(dirname($alvo), 0775, true);
        return @file_put_contents($alvo, $conteudo) !== false;
    };

    if ($acao === 'sobrescrever') {
        if (in_array(($choque['motivo'] ?? ''), ['retirado-editado', 'retirado'], true)) {
            if (is_file($alvo) && !@unlink($alvo)) return $falha('não foi possível remover o arquivo');
        } else {
            if (isset($choque['copia_externa'])) {
                $origem = (string)$choque['copia_externa'];
                if (!is_file($origem) || !hash_equals((string)($choque['hash_novo'] ?? ''), (string)hash_file('sha256', $origem))) return $falha('cópia externa ausente ou alterada');
                $nova = file_get_contents($origem);
            } else {
                $v = instalacao_choque_versoes($base, $choque);
                $nova = $v['nova'];
            }
            if ($nova === null || $nova === false) return $falha('a versão nova não está mais guardada');
            if (!$escrever($nova)) return $falha('não foi possível escrever o arquivo');
        }
    } elseif ($acao === 'mesclar') {
        if ($mesclado === null) return $falha('conteúdo mesclado ausente');
        if (!$escrever($mesclado)) return $falha('não foi possível escrever o arquivo');
    }

    $hash = instalacao_hash_disco($base, $rel);
    if (!$gravarRegras) return ['ok' => true, 'erro' => '', 'acao' => $acao, 'hash_disco' => $hash];
    $regras = instalacao_regras_ler($base);
    if ($acao === 'manter') {
        $regras[$rel] = ['acao' => 'manter', 'camada' => (string)($choque['camada'] ?? ''), 'motivo' => (string)($choque['motivo'] ?? ''),
            'hash_disco' => $hash, 'hash_novo' => $choque['hash_novo'] ?? null, 'em' => date('c')];
    } elseif ($acao === 'mesclar') {
        // REQ-286: a mescla também é decisão sobre este disco. Sem a regra, a mesma entrega voltaria a chocar
        // (o planejamento só deixa de lado divergência decidida). Entrega com versão nova pede decisão de novo.
        $regras[$rel] = ['acao' => 'mesclar', 'camada' => (string)($choque['camada'] ?? ''), 'motivo' => (string)($choque['motivo'] ?? ''),
            'hash_disco' => $hash, 'hash_novo' => $choque['hash_novo'] ?? null, 'em' => date('c')];
    } else {
        unset($regras[$rel]); // o disco mudou: uma regra antiga de "manter" não vale mais
    }
    instalacao_regras_gravar($base, $regras);
    return ['ok' => true, 'erro' => '', 'acao' => $acao, 'hash_disco' => $hash];
}
