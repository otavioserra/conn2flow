<?php
/**
 * Limpeza das migrações obsoletas no ambiente em execução — req-194 / BATCH-198.
 *
 * `db/migrations` no servidor recebe arquivos de DOIS donos: o core (atualização do sistema, sync do
 * core) e o projeto (deploy por API, pipeline por rsync). Nenhum dos caminhos apagava nada, então uma
 * migração renomeada deixava a cópia antiga para trás e o Phinx parava com "Duplicate migration"
 * (mesma versão) ou com classe repetida — travando a etapa de banco de TODOS os deploys seguintes.
 *
 * Apagar a pasta inteira antes de copiar não serve: o pacote de um dono não traz as migrações do
 * outro. A limpeza é por dono:
 * 1. cada dono grava `db/.c2f-migrations-<dono>.json` com a lista completa que entregou;
 * 2. o que o mesmo dono entregou antes e não entrega mais é removido;
 * 3. arquivo com a MESMA CLASSE de uma migração que chega, com outro nome (a cópia antiga de uma
 *    migração renomeada), é removido — vale também na primeira vez, sem manifesto anterior;
 * 4. mesma VERSÃO com classe diferente é choque entre donos (ou entre agentes): nada é removido, o
 *    choque é devolvido para o log e o Phinx recusa com a mensagem de sempre.
 *
 * Só arquivos `AAAAMMDDHHMMSS_nome.php` dentro da pasta informada são tocados.
 *
 * Uso por CLI (pipeline por rsync, rodando no próprio servidor):
 *   php controladores/atualizacoes/atualizacoes-migracoes.php --dir=db/migrations --dono=projeto --lista=a.php,b.php
 */

const ATUALIZACOES_MIGRACOES_PADRAO = '/^(\d{14})_([a-z0-9_]+)\.php$/';
const ATUALIZACOES_MIGRACOES_DONOS = ['core', 'projeto'];

/** Versão e classe (convenção do Phinx: CamelCase do nome) de um arquivo de migração, ou null. */
function atualizacoes_migracoes_info(string $arquivo): ?array {
    if (!preg_match(ATUALIZACOES_MIGRACOES_PADRAO, $arquivo, $m)) return null;
    return ['versao' => $m[1], 'classe' => str_replace(' ', '', ucwords(str_replace('_', ' ', $m[2])))];
}

/** Arquivos de migração válidos de uma pasta (só o nome). */
function atualizacoes_migracoes_listar(string $dir): array {
    if (!is_dir($dir)) return [];
    $lista = [];
    foreach (scandir($dir) ?: [] as $f) {
        if (atualizacoes_migracoes_info($f) && is_file($dir . DIRECTORY_SEPARATOR . $f)) $lista[] = $f;
    }
    sort($lista);
    return $lista;
}

function atualizacoes_migracoes_manifesto_caminho(string $dirMigracoes, string $dono): string {
    return dirname(rtrim($dirMigracoes, '/\\')) . DIRECTORY_SEPARATOR . '.c2f-migrations-' . $dono . '.json';
}

/**
 * Remove as migrações obsoletas de um dono.
 *
 * @param string     $dir       Pasta `db/migrations` do ambiente em execução.
 * @param string     $dono      'core' | 'projeto'
 * @param array|null $completa  Lista COMPLETA de migrações do dono nesta entrega (null = desconhecida:
 *                              pacote parcial ou sem manifesto; aí só a regra da classe vale).
 * @param array      $chegando  Arquivos que chegam nesta entrega (usados na regra da classe).
 * @param bool       $gravar    Grava o manifesto novo quando `$completa` é conhecida.
 * @return array ['removidos' => [...], 'choques' => [...], 'manifesto' => bool]
 */
function atualizacoes_migracoes_limpar(string $dir, string $dono, ?array $completa, array $chegando = [], bool $gravar = true): array {
    $relatorio = ['removidos' => [], 'choques' => [], 'manifesto' => false];
    if (!in_array($dono, ATUALIZACOES_MIGRACOES_DONOS, true) || !is_dir($dir)) return $relatorio;

    $valida = function ($lista) {
        return array_values(array_unique(array_filter(array_map('basename', (array)$lista), 'atualizacoes_migracoes_info')));
    };
    $completa = $completa === null ? null : $valida($completa);
    $entrega = $valida(array_merge($chegando, $completa ?? []));
    if (!$entrega && $completa === null) return $relatorio;

    $classes = [];
    $versoes = [];
    foreach ($entrega as $f) {
        $i = atualizacoes_migracoes_info($f);
        $classes[$i['classe']] = $f;
        $versoes[$i['versao']] = $f;
    }

    $caminhoManifesto = atualizacoes_migracoes_manifesto_caminho($dir, $dono);
    $anterior = [];
    if (is_file($caminhoManifesto)) {
        $json = json_decode((string)file_get_contents($caminhoManifesto), true);
        if (is_array($json['files'] ?? null)) $anterior = $valida($json['files']);
    }

    // Arquivos declarados pelo OUTRO dono nunca são apagados aqui: um projeto pode estender ou
    // sobrepor o core; o conflito vira choque registrado, não remoção.
    $doOutro = [];
    foreach (ATUALIZACOES_MIGRACOES_DONOS as $outro) {
        if ($outro === $dono) continue;
        $cam = atualizacoes_migracoes_manifesto_caminho($dir, $outro);
        $json = is_file($cam) ? json_decode((string)file_get_contents($cam), true) : null;
        if (is_array($json['files'] ?? null)) $doOutro = array_merge($doOutro, $valida($json['files']));
    }

    $remover = function ($f, $motivo) use ($dir, &$relatorio) {
        $alvo = $dir . DIRECTORY_SEPARATOR . $f;
        if (is_file($alvo) && @unlink($alvo)) $relatorio['removidos'][] = ['arquivo' => $f, 'motivo' => $motivo];
    };

    foreach (atualizacoes_migracoes_listar($dir) as $f) {
        if (in_array($f, $entrega, true)) continue;
        $i = atualizacoes_migracoes_info($f);
        if ($completa !== null && in_array($f, $anterior, true)) { $remover($f, 'retirada-pelo-dono'); continue; }
        $doOutroDono = in_array($f, $doOutro, true);
        if (isset($classes[$i['classe']]) && !$doOutroDono) { $remover($f, 'renomeada:' . $classes[$i['classe']]); continue; }
        if (isset($classes[$i['classe']]) || isset($versoes[$i['versao']])) {
            $relatorio['choques'][] = ['arquivo' => $f, 'com' => $classes[$i['classe']] ?? $versoes[$i['versao']], 'versao' => $i['versao'], 'outro_dono' => $doOutroDono];
        }
    }

    if ($completa !== null && $gravar) {
        $relatorio['manifesto'] = (bool)@file_put_contents($caminhoManifesto, json_encode([
            'owner' => $dono,
            'updated_at' => date('c'),
            'files' => $completa,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    return $relatorio;
}

/** Linhas de log de um relatório (vazio quando nada aconteceu). */
function atualizacoes_migracoes_log(array $relatorio, string $dono): array {
    $linhas = [];
    foreach ($relatorio['removidos'] as $r) $linhas[] = "Migração obsoleta removida ({$dono}): {$r['arquivo']} [{$r['motivo']}]";
    foreach ($relatorio['choques'] as $c) $linhas[] = "CHOQUE de migração ({$dono}): {$c['arquivo']} e {$c['com']} usam a versão {$c['versao']} — renomeie uma delas";
    return $linhas;
}

// ----------------------------------------------------------------------------- CLI
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $opts = [];
    foreach (array_slice($argv, 1) as $a) {
        if (preg_match('/^--([a-z-]+)=(.*)$/', $a, $m)) $opts[$m[1]] = $m[2];
    }
    $dir = $opts['dir'] ?? '';
    $dono = $opts['dono'] ?? '';
    if ($dir === '' || !in_array($dono, ATUALIZACOES_MIGRACOES_DONOS, true) || !isset($opts['lista'])) {
        fwrite(STDERR, "Uso: php atualizacoes-migracoes.php --dir=db/migrations --dono=core|projeto --lista=a.php,b.php\n");
        exit(2);
    }
    $lista = array_filter(array_map('trim', explode(',', $opts['lista'])), 'strlen');
    $r = atualizacoes_migracoes_limpar($dir, $dono, $lista, $lista);
    foreach (atualizacoes_migracoes_log($r, $dono) as $linha) echo $linha, PHP_EOL;
    echo 'Migrações (' . $dono . '): ' . count($r['removidos']) . ' removida(s), ' . count($r['choques']) . ' choque(s).', PHP_EOL;
    exit($r['choques'] ? 3 : 0);
}
