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
function loadDataFile(string $file): array { 
    $d = json_decode(file_get_contents($file), true); 
    if (!is_array($d)) return [];
    // Limpar espaços desnecessários nos valores
    return array_map('cleanDataRow', $d);
}

/** Limpa espaços desnecessários em uma linha de dados */
function cleanDataRow(array $row): array {
    $cleaned = [];
    foreach ($row as $key => $value) {
        if (is_string($value) && $value !== null) {
            $trimmed = trim($value);
            // Se é numérico, usar o valor limpo
            if (is_numeric($trimmed)) {
                $cleaned[$key] = $trimmed;
            } else {
                $cleaned[$key] = $value; // preservar espaços em strings não-numéricas
            }
        } else {
            $cleaned[$key] = $value;
        }
    }
    return $cleaned;
}

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

/** Mapa explícito de PKs quando nome foge ao padrão id_<tabela> */
function pkPorTabela(string $tabela): ?string {
    static $map = [
        'paginas' => 'id_paginas',
        'layouts' => 'id_layouts',
        'componentes' => 'id_componentes',
        'variaveis' => 'id_variaveis'
    ];
    return $map[$tabela] ?? null;
}

/** Descoberta heurística de PK (fallback) */
function descobrirPK(string $tabela, array $row): string {
    if ($pk = pkPorTabela($tabela)) return $pk;
    // heurística: primeira chave que começa com 'id_' ou termina no nome da tabela
    foreach ($row as $k=>$v) {
        if (stripos($k,'id_') === 0) return $k;
    }
    // fallback final: se existe 'id'
    return array_key_exists('id',$row) ? 'id' : array_key_first($row);
}

/** Insere registros ausentes e atualiza divergentes */
/**
 * Sincroniza uma tabela com registros vindos de arquivos JSON.
 * v1.10.16: parâmetro $simulate para permitir dry-run exibindo diffs sem persistir.
 */
function sincronizarTabela(PDO $pdo, string $tabela, array $registros, bool $logDiffs = true, bool $simulate = false): array {
    // Estratégia DB-first: varre linhas existentes no banco, aplica diffs, depois insere JSON restantes.
    if (empty($registros)) return ['inserted'=>0,'updated'=>0,'same'=>0];
    $pk = pkPorTabela($tabela) ?? descobrirPK($tabela, $registros[0]);
    $debug = !empty($GLOBALS['CLI_OPTS']['debug']);
    if ($debug) log_disco("SYNC_DB_FIRST_ini tabela=$tabela pk=$pk qtdJson=".count($registros), $GLOBALS['LOG_FILE']);

    // Indexar registros JSON por PK
    $jsonByPk = [];
    foreach ($registros as $r) {
        if (!array_key_exists($pk, $r)) continue; // ignora sem pk
        $jsonByPk[(string)$r[$pk]] = $r;
    }
    $matched = [];

    // Fetch completo (pode otimizar: selecionar apenas colunas presentes)
    $dbRows = $pdo->query("SELECT * FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC);
    $inserted=$updated=$same=0;

    // Mapa de preservação para user_modified=1
    $preserveMap = [
        'paginas'      => ['html','css'],
        'layouts'      => ['html','css'],
        'componentes'  => ['html','css'],
        'variaveis'    => ['valor']
    ];

    foreach ($dbRows as $exist) {
        $pkVal = (string)($exist[$pk] ?? ''); if ($pkVal === '') continue; // sanidade
        if (!isset($jsonByPk[$pkVal])) {
            // Não existe no JSON -> manter (poderia futuramente marcar ORPHAN)
            if ($debug) log_disco("ORPHAN_DB_ROW tabela=$tabela pk=$pkVal", $GLOBALS['LOG_FILE']);
            $same++; // conta como não alterado
            continue;
        }
        $row = $jsonByPk[$pkVal];
        $matched[$pkVal] = true;
        $diff = []; $oldVals = [];
        foreach ($row as $c=>$vNew) {
            if ($c === $pk) continue; // não altera PK
            $vOld = $exist[$c] ?? null;
            // proteção user_modified
            if ($c === 'user_modified' && (int)$vOld === 1 && (int)$vNew !== 1) continue;
            $cmpOld = normalizeValue($vOld);
            $cmpNew = normalizeValue($vNew);
            if ($cmpNew !== $cmpOld) { $diff[$c] = $vNew; $oldVals[$c] = $vOld; }
        }
        if (isset($exist['user_modified']) && (int)$exist['user_modified'] === 1 && isset($preserveMap[$tabela])) {
            $changedPreserved = false;
            foreach ($preserveMap[$tabela] as $campo) {
                if (array_key_exists($campo, $diff)) {
                    if ($tabela === 'variaveis' && $campo === 'valor') {
                        if (array_key_exists('value_updated', $exist) || array_key_exists('value_updated', $row)) {
                            $diff['value_updated'] = $diff[$campo];
                        }
                    } else {
                        $dest = $campo . '_updated';
                        if (array_key_exists($dest, $exist) || array_key_exists($dest, $row)) {
                            $diff[$dest] = $diff[$campo];
                        }
                    }
                    unset($diff[$campo]);
                    $changedPreserved = true;
                }
            }
            if ($changedPreserved) {
                if (array_key_exists('system_updated', $exist) || array_key_exists('system_updated', $row)) {
                    $diff['system_updated'] = 1;
                }
                if ($debug) log_disco("USER_MODIFIED_PRESERVADO $tabela pk=$pkVal campos=".implode(',', $preserveMap[$tabela]), $GLOBALS['LOG_FILE']);
            }
        }
        if ($diff) {
            if ($simulate) {
                log_disco("SIMULATE_UPDATE $tabela pk=$pkVal campos=".implode(',', array_keys($diff)), $GLOBALS['LOG_FILE']);
            } else {
                $sets = implode(',', array_map(fn($c)=>"`$c`=:$c", array_keys($diff)));
                $sql = "UPDATE `$tabela` SET $sets WHERE `$pk` = :pk";
                $stmt = $pdo->prepare($sql);
                $params = $diff; $params['pk'] = $pkVal;
                $stmt->execute($params);
            }
            if ($logDiffs) {
                $pairs = [];$lim=0;
                foreach ($diff as $c=>$v) { $pairs[] = $c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(++$lim>=10){$pairs[]='...';break;} }
                log_disco(tr('_diff_update_detail',[ 'tabela'=>$tabela, 'pk'=>$pkVal, 'campos'=>implode(', ',$pairs)]), $GLOBALS['LOG_FILE']);
            }
            $updated++;
        } else {
            $same++;
        }
    }

    // Inserir JSON restantes não encontrados no banco
    foreach ($jsonByPk as $pkVal=>$row) {
        if (isset($matched[$pkVal])) continue; // já tratado
        // Preparar colunas
        $cols = array_keys($row);
        $placeholders = ':' . implode(',:', $cols);
        $sql = "INSERT INTO `$tabela` (".implode(',', array_map(fn($c)=>"`$c`", $cols)).") VALUES ($placeholders)";
        if ($simulate) {
            log_disco("SIMULATE_INSERT $tabela pk=$pkVal", $GLOBALS['LOG_FILE']);
            $inserted++;
            continue;
        }
        $stmt = $pdo->prepare($sql);
        try {
            $params = [];
            foreach ($row as $c=>$v) { $params[':'.$c] = $v; }
            $stmt->execute($params);
            $inserted++;
        } catch (PDOException $e) {
            if (stripos($e->getMessage(),'Duplicate entry') !== false) {
                log_disco("DUP_SKIP $tabela pk=$pkVal msg=".encLog($e->getMessage()), $GLOBALS['LOG_FILE']);
                $same++;
            } else {
                log_disco("ERROR_INSERT $tabela pk=$pkVal ex=".encLog($e->getMessage()), $GLOBALS['LOG_FILE']);
                throw $e;
            }
        }
    }

    if ($debug) log_disco("SYNC_DB_FIRST_fim tabela=$tabela +$inserted ~$updated =$same", $GLOBALS['LOG_FILE']);
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

/** Normaliza valores para comparação precisa evitando atualizações desnecessárias */
function normalizeValue($value): string {
    if ($value === null) return 'NULL';
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_scalar($value)) {
        $s = (string)$value;
        $trim = trim($s);
        if ($trim !== $s && is_numeric($trim)) return $trim; // normaliza números com espaços
        return $s;
    }
    if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    return (string)$value;
}

function comparacaoDados(): array {
    global $DB_DATA_DIR, $LOG_FILE, $CLI_OPTS, $CHECKSUM_CHANGED_TABLES;
    log_disco(tr('_compare_start'), $LOG_FILE);
    $arquivos = glob($DB_DATA_DIR . '*Data.json');
    // Filtro --tables opcional
    if (!empty($CLI_OPTS['tables'])) {
        $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
        $arquivos = array_values(array_filter($arquivos, function($f) use ($filter){
            $t = tabelaFromDataFile($f); return in_array(strtolower($t), $filter, true);
        }));
        log_disco(tr('_filter_tables',[ 'lista'=>implode(',', array_map(fn($f)=>tabelaFromDataFile($f), $arquivos))]), $LOG_FILE);
    }
    $pdo = db();
    $resumo = [];
    foreach ($arquivos as $file) {
        $tabela = tabelaFromDataFile($file);
        if (is_array($CHECKSUM_CHANGED_TABLES) && !in_array($tabela, $CHECKSUM_CHANGED_TABLES, true)) {
            log_disco("SKIP_NO_CHECKSUM_CHANGE tabela=$tabela", $LOG_FILE);
            continue;
        }
        $registros = loadDataFile($file);
        // Ajustes específicos pré-sincronização
        if ($tabela === 'paginas' && $registros) {
            foreach ($registros as &$r) {
                if (isset($r['type']) && !isset($r['tipo'])) { $r['tipo']=$r['type']; unset($r['type']); }
                if (isset($r['tipo'])) { $map=['page'=>'pagina','system'=>'sistema']; $orig=strtolower((string)$r['tipo']); if(isset($map[$orig])) $r['tipo']=$map[$orig]; }
                $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null];
            } unset($r);
        } elseif (in_array($tabela,['layouts','componentes'],true) && $registros) {
            foreach ($registros as &$r) { $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null]; } unset($r);
        } elseif ($tabela==='variaveis' && $registros) {
            foreach ($registros as &$r) { $r += ['user_modified'=>0,'system_updated'=>0,'value_updated'=>null]; } unset($r);
        }
        if (!$registros) { log_disco(tr('_compare_no_changes',['tabela'=>$tabela]), $LOG_FILE); continue; }
        log_disco(tr('_executing_table',['tabela'=>$tabela]), $LOG_FILE);
        $resultado = sincronizarTabela($pdo, $tabela, $registros, !empty($CLI_OPTS['log-diff']), !empty($CLI_OPTS['dry-run']));
        log_disco(tr('_compare_summary', ['tabela'=>$tabela,'ins'=>$resultado['inserted'],'upd'=>$resultado['updated'],'same'=>$resultado['same']]), $LOG_FILE);
        $resumo[$tabela]=$resultado;
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
    global $LOG_FILE, $CLI_OPTS, $BACKUP_DIR_BASE, $DB_DATA_DIR, $CHECKSUM_CHANGED_TABLES;
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

        // Cálculo de checksums (garante que migration criou manager_updates)
        $pdo = db();
        $checksums = [];
        foreach (glob($DB_DATA_DIR . '*Data.json') as $f) {
            $checksums[basename($f)] = md5_file($f) ?: '';
        }
        $checksumsJson = json_encode($checksums, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $previousMap = [];
        $CHECKSUM_CHANGED_TABLES = null; // null => todas
        $forceAll = !empty($CLI_OPTS['force-all']);
        try {
            $stmtPrev = $pdo->query('SELECT db_checksum FROM manager_updates ORDER BY date DESC, id_manager_updates DESC LIMIT 1');
            $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            if ($prevRow) { $previousMap = json_decode($prevRow['db_checksum'] ?? '[]', true) ?: []; }
        } catch (Throwable $e) {
            log_disco('WARN lendo manager_updates: '.encLog($e->getMessage()), $LOG_FILE);
        }

        if ($previousMap && !$forceAll) {
            $changed = [];
            foreach ($checksums as $file=>$sum) {
                if (!isset($previousMap[$file]) || $previousMap[$file] !== $sum) { $changed[] = tabelaFromDataFile($file); }
            }
            if ($changed) {
                $CHECKSUM_CHANGED_TABLES = $changed;
                log_disco('CHECKSUM_CHANGED_TABLES='.implode(',', $changed), $LOG_FILE);
            } else {
                $CHECKSUM_CHANGED_TABLES = []; // nenhuma mudou
                log_disco('CHECKSUM_NENHUMA_TABELA_MUDOU', $LOG_FILE);
            }
        } else {
            if ($forceAll) { log_disco('FORCE_ALL_TABELAS', $LOG_FILE); }
            elseif (!$previousMap) { log_disco('CHECKSUM_PRIMEIRA_ATUALIZACAO', $LOG_FILE); }
        }
        // Backup opcional
        if (!empty($CLI_OPTS['backup'])) {
            $arquivos = glob($DB_DATA_DIR . '*Data.json');
            $tabelas = [];
            foreach ($arquivos as $f) { $tabelas[] = tabelaFromDataFile($f); }
            if (!empty($CLI_OPTS['tables'])) {
                $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
                $tabelas = array_values(array_filter($tabelas, fn($t)=>in_array(strtolower($t), $filter, true)));
            }
            $backupPath = executarBackup($pdo, $tabelas, $BACKUP_DIR_BASE);
        } else { $backupPath = null; }
        if (is_array($CHECKSUM_CHANGED_TABLES) && count($CHECKSUM_CHANGED_TABLES) === 0) {
            log_disco('SEM_MUDANCAS_DADOS -> pulando sincronizacao', $LOG_FILE);
            $resumo = [];
        } else {
            $resumo = comparacaoDados();
        }
        // Registrar manager_updates
        try {
            $versao = $GLOBALS['_GESTOR']['versao'] ?? null;
            $ins = $pdo->prepare('INSERT INTO manager_updates (db_checksum, backup_path, version, date) VALUES (:c,:b,:v,NOW())');
            $ins->execute([':c'=>$checksumsJson, ':b'=>$backupPath, ':v'=>$versao]);
            log_disco('MANAGER_UPDATES_REGISTRADO id='.$pdo->lastInsertId(), $LOG_FILE);
        } catch (Throwable $e) {
            log_disco('WARN registrar manager_updates: '.encLog($e->getMessage()), $LOG_FILE);
        }
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
