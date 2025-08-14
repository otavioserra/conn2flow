<?php
/**
 * Rotina de Atualização de Banco de Dados
 * - Executa migrações Phinx
 * - (Removido) Execução de seeders durante atualização: seeders agora só na instalação.
 * - Compara dados atuais das tabelas com arquivos JSON em gestor/db/data (inserindo/atualizando conforme necessário)
 * - Gera relatório final
 *
 * Estrutura atual: migracoes -> comparacaoDados -> relatorioFinal -> main
 * Multilíngue via _() e logs via log_disco().
 */

declare(strict_types=1);

// $BASE_PATH: raiz do módulo gestor (pasta que contém bibliotecas/, db/, controladores/)
$BASE_PATH = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR; // .../conn2flow/gestor/
// $REPO_ROOT: raiz do repositório (um nível acima de gestor/)
$REPO_ROOT = realpath($BASE_PATH . '..') . DIRECTORY_SEPARATOR;

require_once $BASE_PATH . 'bibliotecas/lang.php';
@require_once $BASE_PATH . 'bibliotecas/log.php';

// =====================
// Configuração Global
// =====================
$LOG_FILE    = 'atualizacoes-bd';
$DB_DATA_DIR = $BASE_PATH . 'db/data/';
$GESTOR_DIR  = $BASE_PATH; // agora corretamente aponta para gestor/
$PHINX_BIN   = $BASE_PATH . 'vendor/bin/phinx'; // vendor dentro de gestor/
// Fallback: caso vendor esteja na raiz do repositório (cenário legado)
if (!file_exists($PHINX_BIN) && file_exists($REPO_ROOT . 'vendor/bin/phinx')) {
    $PHINX_BIN = $REPO_ROOT . 'vendor/bin/phinx';
}
$BACKUP_DIR_BASE = $REPO_ROOT . 'backups/atualizacoes/'; // conforme prompt

// Ajuste ambiente log
global $_GESTOR; if (!isset($_GESTOR)) $_GESTOR = []; if (!isset($_GESTOR['logs-path'])) $_GESTOR['logs-path'] = $BASE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR; if (!is_dir($_GESTOR['logs-path'])) @mkdir($_GESTOR['logs-path'], 0775, true);
set_lang('pt-br');
// Mesclar dicionário local de atualizações (prioridade para chaves locais)
$localLangDir = __DIR__ . '/lang/';
if (is_dir($localLangDir)) {
    $localFile = $localLangDir . $GLOBALS['lang'] . '.json';
    if (file_exists($localFile)) {
        $localDict = json_decode(file_get_contents($localFile), true) ?: [];
        $GLOBALS['dicionario'] = array_merge($GLOBALS['dicionario'], $localDict);
    }
}

/** Helper simples substituição de placeholders :chave */
function tr(string $key, array $vars = []): string { $msg = _($key); foreach ($vars as $k => $v) { $msg = str_replace(':' . $k, (string)$v, $msg); } return $msg; }

/** Executa comando shell retornando [exitCode, output] */
function runCmd(string $cmd): array {
    $descriptor = [1 => ['pipe','w'], 2 => ['pipe','w']];
    $p = proc_open($cmd, $descriptor, $pipes, null, null);
    if (!is_resource($p)) return [1, 'proc_open fail'];
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($p);
    return [$code, trim($out . PHP_EOL . $err)];
}

/** Conexão PDO reutilizável */
function db(): PDO {
    static $pdo = null; if ($pdo) return $pdo;
    // Reaproveita lógica do phinx.php para config
    $configPath = __DIR__ . '/../../config.php';
    if (!file_exists($configPath)) throw new RuntimeException('config.php não encontrado para conectar banco.');
    require $configPath; // define $_BANCO
    $host = $_BANCO['host'] ?? 'localhost';
    $name = $_BANCO['nome'] ?? '';
    $user = $_BANCO['usuario'] ?? '';
    $pass = $_BANCO['senha'] ?? '';
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}

// (Removido) Função listaTabelasData não necessária neste momento.

/**
 * Executa migrações usando phinx.
 */
function migracoes(): array {
    global $PHINX_BIN, $GESTOR_DIR, $LOG_FILE;
    log_disco(tr('_migrations_start'), $LOG_FILE);
    $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($PHINX_BIN) . ' migrate -c ' . escapeshellarg($GESTOR_DIR . 'phinx.php') . ' -e gestor';
    log_disco('DEBUG CMD MIGRACOES: ' . $cmd, $LOG_FILE);
    [$code, $out] = runCmd($cmd);
    log_disco($out, $LOG_FILE);
    if ($code !== 0) {
            log_disco('Erro migrações exitCode=' . $code, $LOG_FILE);
        throw new RuntimeException('Falha migrações');
    }
    log_disco(tr('_migrations_done'), $LOG_FILE);
    return ['output' => $out];
}


/** Carrega JSON Data file */
function loadDataFile(string $file): array { $d = json_decode(file_get_contents($file), true); return is_array($d) ? $d : []; }

/**
 * Obtém nome da tabela a partir do arquivo *Data.json.
 * Regras:
 *  - Remove sufixo Data.json
 *  - Se já contiver underscore, apenas converte para minúsculo (mantém snake_case existente)
 *  - Caso contrário, converte CamelCase/PascalCase para snake_case
 * Exemplos:
 *  HostsConfiguracoesData.json => hosts_configuracoes
 *  PaginasData.json            => paginas
 *  hosts_configuracoesData.json (legado) => hosts_configuracoes
 */
function tabelaFromDataFile(string $file): string {
    $base = preg_replace('/Data\.json$/', '', basename($file));
    if ($base === '') return '';
    if (strpos($base, '_') !== false) {
        return strtolower($base);
    }
    // Inserir underscore antes de cada letra maiúscula que não é inicial
    $snake = preg_replace('/(?<!^)([A-Z])/', '_$1', $base);
    return strtolower($snake);
}

/** Insere registros ausentes e atualiza divergentes */
function sincronizarTabela(PDO $pdo, string $tabela, array $registros, bool $logDiffs = true): array {
    if (empty($registros)) return ['inserted'=>0,'updated'=>0,'same'=>0];
    $pk = descobrirPK($tabela, $registros[0]);
    $columns = array_keys($registros[0]);
    $sel = $pdo->prepare("SELECT * FROM `$tabela` WHERE `$pk` = :pk LIMIT 1");
    // Seleção alternativa por (id, language) se existir
    $hasAlt = in_array('id', $columns, true) && in_array('language', $columns, true);
    $selAlt = $hasAlt ? $pdo->prepare("SELECT * FROM `$tabela` WHERE `id` = :id AND `language` = :language LIMIT 1") : null;
    $selIdOnly = in_array('id', $columns, true) ? $pdo->prepare("SELECT * FROM `$tabela` WHERE `id` = :id LIMIT 1") : null;
    $inserted = $updated = $same = 0;
    $debug = !empty($GLOBALS['CLI_OPTS']['debug']);
    if ($debug) log_disco("DEBUG_SYNC_START tabela=$tabela pk=$pk hasAlt=".($hasAlt?'1':'0'), $GLOBALS['LOG_FILE']);
    foreach ($registros as $row) {
        $pkVal = $row[$pk] ?? null; if ($pkVal === null) continue; // ignora sem PK
        $sel->execute([':pk' => $pkVal]);
        $exist = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$exist && $hasAlt) {
            $selAlt->execute([':id'=>$row['id'], ':language'=>$row['language']]);
            $exist = $selAlt->fetch(PDO::FETCH_ASSOC);
            if ($exist) { $pkVal = $exist[$pk]; }
        }
        if (!$exist && $selIdOnly) {
            $selIdOnly->execute([':id'=>$row['id']]);
            $exist = $selIdOnly->fetch(PDO::FETCH_ASSOC);
            if ($exist) { $pkVal = $exist[$pk]; log_disco("ALT_ID_MATCH $tabela id=" . encLog($row['id']) . " -> pk=".$pkVal, $GLOBALS['LOG_FILE']); }
        }
        if (!$exist) {
            // INSERT
            $cols = array_keys($row);
            $place = ':' . implode(',:', $cols);
            $sql = "INSERT INTO `$tabela` (" . implode(',', array_map(fn($c)=>"`$c`", $cols)) . ") VALUES ($place)";
            // Se tabela possui colunas id + language, usar ON DUPLICATE KEY UPDATE para evitar erro de índice único
            if ($hasAlt) {
                $updateSet = implode(',', array_map(fn($c)=>"`$c`=VALUES(`$c`)", array_filter($cols, fn($c)=>$c!==$pk)));
                $sql .= " ON DUPLICATE KEY UPDATE $updateSet";
            }
            $stmt = $pdo->prepare($sql);
            try {
                if ($debug) log_disco("DEBUG_INSERT_SQL $tabela pk=$pkVal sql=".substr($sql,0,200), $GLOBALS['LOG_FILE']);
                $stmt->execute(array_combine(array_map(fn($c)=>":".$c, $cols), array_values($row)));
                $inserted++;
            } catch (PDOException $ex) {
                if (stripos($ex->getMessage(), 'Duplicate entry') !== false) {
                    log_disco("DUP_SKIP $tabela pk=$pkVal id=" . encLog($row['id'] ?? 'n/a') . " msg=" . encLog($ex->getMessage()), $GLOBALS['LOG_FILE']);
                    $same++;
                } else {
                    log_disco("ERROR_INSERT $tabela pk=$pkVal ex=".encLog($ex->getMessage()), $GLOBALS['LOG_FILE']);
                    throw $ex;
                }
            }
        } else {
            // Comparar diferenças campo a campo
            $diff = [];
            $oldVals = [];
            foreach ($columns as $c) {
                if (!array_key_exists($c, $row)) continue;
                // Não tentar alterar PK se proveniente de alt-match
                if ($c === $pk) continue;
                $vNew = $row[$c]; $vOld = $exist[$c] ?? null;
                if ($vNew !== $vOld) { $diff[$c] = $vNew; $oldVals[$c] = $vOld; }
            }
            if ($diff) {
                $sets = implode(',', array_map(fn($c)=>"`$c` = :$c", array_keys($diff)));
                $diff[":pk"] = $pkVal;
                $sql = "UPDATE `$tabela` SET $sets WHERE `$pk` = :pk";
                $stmt = $pdo->prepare($sql);
                $params = [];
                foreach ($diff as $k=>$v) { $params[ $k[0]===':'?$k:':'.$k ] = $v; }
                $stmt->execute($params);
                if ($logDiffs) {
                    // Montar mensagem detalhada com até 10 campos (para evitar logs gigantes)
                    $pairs = [];
                    $lim = 0;
                    foreach ($diff as $c=>$v) {
                        if ($c === ':pk') continue;
                        $old = $oldVals[$c] ?? null;
                        $pairs[] = $c . ' [' . encLog($old) . ' => ' . encLog($v) . ']';
                        if (++$lim >= 10) { $pairs[] = '...'; break; }
                    }
                    log_disco(tr('_diff_update_detail',[ 'tabela'=>$tabela, 'pk'=>$pkVal, 'campos'=>implode(', ', $pairs) ]), $GLOBALS['LOG_FILE']);
                }
                $updated++;
            } else {
                $same++;
            }
        }
    }
    if ($debug) log_disco("DEBUG_SYNC_END tabela=$tabela inserted=$inserted updated=$updated same=$same", $GLOBALS['LOG_FILE']);
    return ['inserted'=>$inserted,'updated'=>$updated,'same'=>$same];
}

/** Escapa valores para log: limita tamanho e substitui quebras */
function encLog($v): string {
    if ($v === null) return 'NULL';
    $s = (string)$v;
    $s = str_replace(["\n","\r"], ['\\n',''], $s);
    if (strlen($s) > 60) $s = substr($s,0,57) . '...';
    return $s;
}

/** Descobre uma PK improvisada procurando chaves que começam com id_ ou terminam _id */
function descobrirPK(string $tabela, array $row): string {
    foreach ($row as $k=>$v) { if (preg_match('/^id_/',$k)) return $k; }
    foreach ($row as $k=>$v) { if (preg_match('/_id$/',$k)) return $k; }
    return array_key_first($row); // fallback
}

function comparacaoDados(): array {
    global $DB_DATA_DIR, $LOG_FILE, $CLI_OPTS;
    log_disco(tr('_compare_start'), $LOG_FILE);
    $arquivos = glob($DB_DATA_DIR . '*Data.json');
    // Filtrar por --tables se fornecido (usa mesma lógica de derivação)
    if (!empty($CLI_OPTS['tables'])) {
        $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
        $arquivos = array_filter($arquivos, function($f) use ($filter){
            $t = tabelaFromDataFile($f);
            return in_array($t, $filter, true);
        });
        log_disco(tr('_filter_tables',[ 'lista'=>implode(',', $filter)]), $LOG_FILE);
    }
    $pdo = db();
    $resumo = [];
    foreach ($arquivos as $file) {
        $tabela = tabelaFromDataFile($file);
        $registros = loadDataFile($file);
        if (!$registros) { log_disco(tr('_compare_no_changes',['tabela'=>$tabela]), $LOG_FILE); continue; }
        log_disco(tr('_executing_table',['tabela'=>$tabela]), $LOG_FILE);
        $resultado = !empty($CLI_OPTS['dry-run']) ? ['inserted'=>0,'updated'=>0,'same'=>count($registros)] : sincronizarTabela($pdo, $tabela, $registros, !empty($CLI_OPTS['log-diff']));
        log_disco(tr('_compare_summary', ['tabela'=>$tabela,'ins'=>$resultado['inserted'],'upd'=>$resultado['updated'],'same'=>$resultado['same']]), $LOG_FILE);
        $resumo[$tabela] = $resultado;
    }
    return $resumo;
}

/** Executa backup JSON de tabelas antes das alterações. */
function executarBackup(PDO $pdo, array $tabelas, string $dirBase): string {
    global $LOG_FILE;
    $timestampDir = $dirBase . date('Ymd-His') . '/';
    if (!is_dir($timestampDir) && !@mkdir($timestampDir, 0775, true)) {
        log_disco(tr('_backup_error',[ 'msg'=>'mkdir fail '.$timestampDir ]), $LOG_FILE);
        return '';
    }
    log_disco(tr('_backup_start',[ 'dir'=>$timestampDir ]), $LOG_FILE);
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents($timestampDir . $t . '.json', json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_disco(tr('_backup_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]), $LOG_FILE);
        } catch (Throwable $e) {
            log_disco(tr('_backup_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]), $LOG_FILE);
        }
    }
    log_disco(tr('_backup_complete'), $LOG_FILE);
    return $timestampDir;
}

/** Exporta dados do banco para arquivos *Data.json (modo reverso) */
function reverseExport(PDO $pdo, array $tabelas, string $dataDir): void {
    global $LOG_FILE;
    log_disco(tr('_reverse_start'), $LOG_FILE);
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) { log_disco(tr('_reverse_empty',[ 'tabela'=>$t ]), $LOG_FILE); continue; }
            $fileName = dataFileNameFromTable($t);
            $dest = $dataDir . $fileName;
            // backup antigo se existir
            if (file_exists($dest)) {
                @rename($dest, $dest . '.bak.' . date('Ymd-His'));
            }
            file_put_contents($dest, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_disco(tr('_reverse_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]), $LOG_FILE);
        } catch (Throwable $e) {
            log_disco(tr('_reverse_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]), $LOG_FILE);
        }
    }
    log_disco(tr('_reverse_complete'), $LOG_FILE);
}

/** Converte nome de tabela snake_case para PascalCase *Data.json */
function dataFileNameFromTable(string $tabela): string {
    $pascal = preg_replace_callback('/(^|_)([a-z])/', function($m){ return strtoupper($m[2]); }, strtolower($tabela));
    return $pascal . 'Data.json';
}

function relatorioFinal(array $resumo): void {
    global $LOG_FILE;
    $totalIns=$totalUpd=$totalSame=0; foreach ($resumo as $r){$totalIns+=$r['inserted'];$totalUpd+=$r['updated'];$totalSame+=$r['same'];}
    $msg = "📝 " . tr('_final_report') . PHP_EOL
        . str_repeat('═',50) . PHP_EOL;
    foreach ($resumo as $tab=>$r) {
        $msg .= sprintf("📦 %s => +%d ~%d =%d" . PHP_EOL, $tab, $r['inserted'],$r['updated'],$r['same']);
    }
    $msg .= "Σ TOTAL => +$totalIns ~${totalUpd} =${totalSame}" . PHP_EOL;
    log_disco($msg, $LOG_FILE); echo $msg;
}

function main(): void {
    global $LOG_FILE, $CLI_OPTS, $BACKUP_DIR_BASE, $DB_DATA_DIR;
    try {
        log_disco(tr('_process_start'), $LOG_FILE);
        // Verificar .env para ambiente de testes (parametrizado)
        $envFolder = $CLI_OPTS['env-dir'] ?? 'localhost';
        $envDir = __DIR__ . '/../../autenticacoes/' . $envFolder . '/';
        if (!file_exists($envDir . '.env')) {
            log_disco(tr('_env_missing'), $LOG_FILE);
            log_disco(tr('_hint_sync'), $LOG_FILE);
            throw new RuntimeException('Ambiente não sincronizado (.env ausente).');
        }
        if (!empty($CLI_OPTS['reverse'])) {
            // Modo reverso exporta dados e encerra
            $pdo = db();
            $arquivos = glob($DB_DATA_DIR . '*Data.json');
            $tabelas = [];
            foreach ($arquivos as $f) { $tabelas[] = tabelaFromDataFile($f); }
            if (!empty($CLI_OPTS['tables'])) {
                $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
                $tabelas = array_values(array_filter($tabelas, fn($t)=>in_array(strtolower($t), $filter, true)));
            }
            reverseExport($pdo, $tabelas, $DB_DATA_DIR);
            log_disco(tr('_process_end_success'), $LOG_FILE);
            return;
        }
        if (!empty($CLI_OPTS['dry-run'])) log_disco(tr('_dry_run_mode'), $LOG_FILE);
    if (empty($CLI_OPTS['skip-migrate'])) { migracoes(); } else { log_disco(tr('_skip_migrations'), $LOG_FILE); }
    // (Removido) Execução de seeders durante atualização
        // Backup opcional
        if (!empty($CLI_OPTS['backup'])) {
            $pdo = db();
            $arquivos = glob($DB_DATA_DIR . '*Data.json');
            $tabelas = [];
            foreach ($arquivos as $f) { $tabelas[] = tabelaFromDataFile($f); }
            if (!empty($CLI_OPTS['tables'])) {
                $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
                $tabelas = array_values(array_filter($tabelas, fn($t)=>in_array(strtolower($t), $filter, true)));
            }
            executarBackup($pdo, $tabelas, $BACKUP_DIR_BASE);
        }
        $resumo = comparacaoDados();
        relatorioFinal($resumo);
        log_disco(tr('_process_end_success'), $LOG_FILE);
    } catch (Throwable $e) {
        log_disco(tr('_process_error',['msg'=>$e->getMessage()]), $LOG_FILE);
        echo 'Erro: ' . $e->getMessage() . PHP_EOL;
    }
}

// Parse argumentos CLI simples
function parseArgs(array $argv): array {
    $out = [];
    foreach ($argv as $a) {
        if (preg_match('/^--([^=]+)=(.+)$/',$a,$m)) { $out[$m[1]] = $m[2]; }
        elseif (substr($a,0,2)=='--') { $out[substr($a,2)] = true; }
    }
    // Remover opções obsoletas relacionadas a seeders se presentes para evitar efeitos colaterais
    foreach (['seed','skip-seed','ignore-seed-errors'] as $legacy) { if (isset($out[$legacy])) unset($out[$legacy]); }
    return $out;
}

if (PHP_SAPI === 'cli') {
    global $CLI_OPTS; $CLI_OPTS = parseArgs($argv);
    if (isset($CLI_OPTS['help']) || isset($CLI_OPTS['h'])) { echo tr('_args_usage') . PHP_EOL; exit(0); }
    main();
}
