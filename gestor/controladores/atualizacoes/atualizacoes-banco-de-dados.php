<?php
/**
 * Rotina de Atualização de Banco de Dados
 * - Executa migrações Phinx
 * - Executa seeders
 * - Compara dados atuais das tabelas com arquivos JSON em gestor/db/data (inserindo/atualizando conforme necessário)
 * - Gera relatório final
 *
 * Estrutura conforme prompt: migracoes -> seeders -> comparacaoDados -> relatorioFinal -> main
 * Multilíngue via _() e logs via log_disco().
 */

declare(strict_types=1);

$BASE_PATH = realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR; // raiz repo corrigido

require_once $BASE_PATH . 'gestor/bibliotecas/lang.php';
@require_once $BASE_PATH . 'gestor/bibliotecas/log.php';

// Config
$LOG_FILE = 'atualizacoes-bd';
$DB_DATA_DIR = $BASE_PATH . 'gestor/db/data/';
$GESTOR_DIR = $BASE_PATH . 'gestor/';
$PHINX_BIN = $BASE_PATH . 'vendor/bin/phinx'; // pode ser ajustado se path diferente

// Ajuste ambiente log
global $_GESTOR; if (!isset($_GESTOR)) $_GESTOR = []; if (!isset($_GESTOR['logs-path'])) $_GESTOR['logs-path'] = $BASE_PATH . 'gestor/logs/atualizacoes/'; if (!is_dir($_GESTOR['logs-path'])) @mkdir($_GESTOR['logs-path'], 0775, true);
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
    [$code, $out] = runCmd($cmd);
    log_disco($out, $LOG_FILE);
    if ($code !== 0) {
        log_disco('Erro migrações exitCode=' . $code, $LOG_FILE);
        throw new RuntimeException('Falha migrações');
    }
    log_disco(tr('_migrations_done'), $LOG_FILE);
    return ['output' => $out];
}

/**
 * Executa seeders (todos) usando phinx.
 */
function seeders(): array {
    global $PHINX_BIN, $GESTOR_DIR, $LOG_FILE;
    log_disco(tr('_seeds_start'), $LOG_FILE);
    $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($PHINX_BIN) . ' seed:run -c ' . escapeshellarg($GESTOR_DIR . 'phinx.php') . ' -e gestor';
    [$code, $out] = runCmd($cmd);
    log_disco($out, $LOG_FILE);
    if ($code !== 0) {
        log_disco('Erro seeders exitCode=' . $code, $LOG_FILE);
        throw new RuntimeException('Falha seeders');
    }
    log_disco(tr('_seeds_done'), $LOG_FILE);
    return ['output' => $out];
}

/** Carrega JSON Data file */
function loadDataFile(string $file): array { $d = json_decode(file_get_contents($file), true); return is_array($d) ? $d : []; }

/** Obtém nome tabela a partir de Data file (LayoutsData.json => layouts) */
function tabelaFromDataFile(string $file): string { return strtolower(preg_replace('/Data\.json$/', '', basename($file))); }

/** Insere registros ausentes e atualiza divergentes */
function sincronizarTabela(PDO $pdo, string $tabela, array $registros): array {
    if (empty($registros)) return ['inserted'=>0,'updated'=>0,'same'=>0];
    $pk = descobrirPK($tabela, $registros[0]);
    $columns = array_keys($registros[0]);
    // Preparar selects individuais (poderia otimizar com IN se PK numérica)
    $sel = $pdo->prepare("SELECT * FROM `$tabela` WHERE `$pk` = :pk LIMIT 1");
    $inserted = $updated = $same = 0;
    foreach ($registros as $row) {
        $pkVal = $row[$pk] ?? null; if ($pkVal === null) continue; // ignora sem PK
        $sel->execute([':pk' => $pkVal]);
        $exist = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$exist) {
            // INSERT
            $cols = array_keys($row);
            $place = ':' . implode(',:', $cols);
            $sql = "INSERT INTO `$tabela` (" . implode(',', array_map(fn($c)=>"`$c`", $cols)) . ") VALUES ($place)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_combine(array_map(fn($c)=>":".$c, $cols), array_values($row)));
            $inserted++;
        } else {
            // Comparar diferenças
            $diff = [];
            foreach ($columns as $c) {
                if (!array_key_exists($c, $row)) continue;
                $vNew = $row[$c]; $vOld = $exist[$c] ?? null;
                if ($vNew !== $vOld) $diff[$c] = $vNew;
            }
            if ($diff) {
                $sets = implode(',', array_map(fn($c)=>"`$c` = :$c", array_keys($diff)));
                $diff[":pk"] = $pkVal;
                $sql = "UPDATE `$tabela` SET $sets WHERE `$pk` = :pk";
                $stmt = $pdo->prepare($sql);
                $params = [];
                foreach ($diff as $k=>$v) { $params[ $k[0]===':'?$k:':'.$k ] = $v; }
                $stmt->execute($params);
                $updated++;
            } else {
                $same++;
            }
        }
    }
    return ['inserted'=>$inserted,'updated'=>$updated,'same'=>$same];
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
    // Filtrar por --tables se fornecido
    if (!empty($CLI_OPTS['tables'])) {
        $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
        $arquivos = array_filter($arquivos, function($f) use ($filter){
            $t = strtolower(preg_replace('/Data\.json$/','', basename($f)));
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
        $resultado = !empty($CLI_OPTS['dry-run']) ? ['inserted'=>0,'updated'=>0,'same'=>count($registros)] : sincronizarTabela($pdo, $tabela, $registros);
        log_disco(tr('_compare_summary', ['tabela'=>$tabela,'ins'=>$resultado['inserted'],'upd'=>$resultado['updated'],'same'=>$resultado['same']]), $LOG_FILE);
        $resumo[$tabela] = $resultado;
    }
    return $resumo;
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
    global $LOG_FILE;
    try {
        global $CLI_OPTS;
        log_disco(tr('_process_start'), $LOG_FILE);
        // Verificar .env para ambiente de testes
        $configPath = __DIR__ . '/../../config.php';
        $envDir = __DIR__ . '/../../autenticacoes/localhost/';
        if (!file_exists($envDir . '.env')) {
            log_disco(tr('_env_missing'), $LOG_FILE);
            log_disco(tr('_hint_sync'), $LOG_FILE);
            throw new RuntimeException('Ambiente não sincronizado (.env ausente).');
        }
        if (!empty($CLI_OPTS['dry-run'])) log_disco(tr('_dry_run_mode'), $LOG_FILE);
        if (empty($CLI_OPTS['skip-migrate'])) { migracoes(); } else { log_disco(tr('_skip_migrations'), $LOG_FILE); }
        if (empty($CLI_OPTS['skip-seed'])) { seeders(); } else { log_disco(tr('_skip_seeders'), $LOG_FILE); }
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
    return $out;
}

if (PHP_SAPI === 'cli') {
    global $CLI_OPTS; $CLI_OPTS = parseArgs($argv);
    if (isset($CLI_OPTS['help']) || isset($CLI_OPTS['h'])) { echo tr('_args_usage') . PHP_EOL; exit(0); }
    main();
}
