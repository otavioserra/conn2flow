<?php

/**
 * Rotina de Atualização de Banco de Dados
 *
 * Funções principais:
 * - Executa migrações Phinx
 * - Compara dados atuais das tabelas com arquivos JSON em gestor/db/data (inserindo/atualizando conforme necessário)
 * - Segrega e exporta registros órfãos conforme regras de unicidade
 * - Gera relatório final consolidado
 * - Multilíngue via __t        $matched = [];
 *
 * Argumentos de linha de comando suportados:
 *
 * --project         : Define o ID do projeto para atualizações feitas via deploy de projeto.
 * --debug           : Ativa modo detalhado de logs e exibe operações passo a passo.
 * --log-diff        : Exibe detalhes das diferenças encontradas entre banco e JSON.
 * --dry-run         : Simula operações sem alterar o banco (apenas exibe o que seria feito).
 * --force-all       : Força atualização de todas as tabelas, ignorando checksums anteriores.
 * --tables=lista    : Sincroniza apenas as tabelas especificadas (ex: --tables=variaveis,paginas).
 * --orphans-mode=op : Define tratamento de órfãos: export (default), log ou ignore.
 * --skip-migrate    : Pula execução das migrações Phinx (útil para ambiente já migrado).
 * --backup          : Realiza backup das tabelas antes de atualizar (em backups/atualizacoes/).
 * --reverse         : Exporta dados do banco para arquivos *Data.json (modo reverso).
 * --env-dir=nome    : Define ambiente de autenticação (pasta autenticacoes/<nome>).
 * --help, -h        : Exibe ajuda detalhada dos argumentos e encerra.
 *
 * Exemplos de uso:
 *   php atualizacoes-banco-de-dados.php --debug --log-diff
 *   php atualizacoes-banco-de-dados.php --tables=variaveis --force-all
 *   php atualizacoes-banco-de-dados.php --orphans-mode=log --dry-run
 *   php atualizacoes-banco-de-dados.php --reverse
 *
 * Estrutura principal: migracoes -> comparacaoDados -> relatorioFinal -> main
 */

declare(strict_types=1);

// =====================
// Configuração Global
// =====================
global $LOG_FILE_DB, $BASE_PATH_DB, $DB_DATA_DIR, $BACKUP_DIR_BASE, $GLOBALS;

$LOG_FILE_DB = 'atualizacoes-bd';
$BASE_PATH_DB = realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR;
$DB_DATA_DIR = $BASE_PATH_DB . 'db/data/';
$BACKUP_DIR_BASE = $BASE_PATH_DB . 'backups/atualizacoes/';

// Bibliotecas
require_once $BASE_PATH_DB . 'bibliotecas/lang.php';
require_once $BASE_PATH_DB . 'bibliotecas/log.php';
require_once $BASE_PATH_DB . 'bibliotecas/banco.php';
require_once $BASE_PATH_DB . 'bibliotecas/gestor.php';

// Gestor 
global $_GESTOR;
if (!isset($_GESTOR)) $_GESTOR = [];
$_GESTOR['logs-path'] = $BASE_PATH_DB . 'logs' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR;
if (!is_dir($_GESTOR['logs-path'])) @mkdir($_GESTOR['logs-path'], 0775, true);
set_lang('pt-br');
$localLangDir = $BASE_PATH_DB . 'controladores/atualizacoes/lang/';
if (is_dir($localLangDir)) {
    $localFile = $localLangDir . $GLOBALS['lang'] . '.json';
    if (file_exists($localFile)) {
        $localDict = json_decode(file_get_contents($localFile), true);
        if (is_array($localDict)) {
            $GLOBALS['dicionario'] = array_merge($GLOBALS['dicionario'], $localDict);
        }
    }
}

/** Helper simples substituição de placeholders :chave */
function tr(string $key, array $vars = []): string { return __t($key, $vars); }

/** Conexão PDO reutilizável */
/** Logger unificado para disco, stdout CLI e consumidores externos por referencia. */
function log_unificado(string $msg, ?string $logFilename = null): void {
    global $LOG_FILE_DB, $GLOBALS;

    if (isset($GLOBALS['EXTERNAL_LOGGER']) && is_array($GLOBALS['EXTERNAL_LOGGER'])) {
        $GLOBALS['EXTERNAL_LOGGER'][] = $msg;
    }

    $filename = $logFilename ?? $LOG_FILE_DB;
    log_disco($msg, $filename);

    if (PHP_SAPI === 'cli') {
        echo $msg . PHP_EOL;
    }
}

function db(): PDO {
    global $BASE_PATH_DB, $_BANCO, $CLI_OPTS, $_ENV;

    static $pdo = null; if ($pdo) return $pdo;

    if(isset($CLI_OPTS['installing']) && isset($CLI_OPTS['db']) && $CLI_OPTS['installing']) {
        $host = $CLI_OPTS['db']['host'] ?? '';
        $name = $CLI_OPTS['db']['name'] ?? '';
        $user = $CLI_OPTS['db']['user'] ?? '';
        $pass = $CLI_OPTS['db']['pass'] ?? '';

        // Validação básica para evitar erros durante instalação
        if (empty($host) || empty($name) || empty($user)) {
            throw new RuntimeException("Configurações de banco não definidas para instalação. Verifique as variáveis CLI_OPTS");
        }
    } else {
        $host = $_BANCO['host'] ?? 'localhost';
        $name = $_BANCO['nome'] ?? '';
        $user = $_BANCO['usuario'] ?? '';
        $pass = $_BANCO['senha'] ?? '';
    }

    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Força charset para todas operações
    $pdo->exec("SET NAMES utf8mb4");
    return $pdo;
}

/**
 * Executa migrações usando phinx.
 */
function migracoes(): array {
    global $BASE_PATH_DB, $LOG_FILE_DB;

    log_unificado(tr('_migrations_start'), $LOG_FILE_DB);

    // Caminho do autoload
    $autoload = $BASE_PATH_DB . 'vendor/autoload.php';
    if (!file_exists($autoload)) {
        $msg = 'Autoload do Composer não encontrado em ' . $autoload;
        log_unificado($msg, $LOG_FILE_DB);
        throw new RuntimeException($msg);
    }
    require_once $autoload;

    // Localiza phinx.php (config array)
    $phinxConfigFile = $BASE_PATH_DB . 'phinx.php';
    if (!file_exists($phinxConfigFile)) {
        $repoRoot = realpath($BASE_PATH_DB . '..') . DIRECTORY_SEPARATOR;
        if (file_exists($repoRoot . 'phinx.php')) {
            $phinxConfigFile = $repoRoot . 'phinx.php';
        } else {
            $msg = 'Arquivo de configuração phinx.php não encontrado: ' . $phinxConfigFile;
            log_unificado($msg, $LOG_FILE_DB);
            throw new RuntimeException($msg);
        }
    }

    $rawConfig = require $phinxConfigFile; // retorna array
    if (!is_array($rawConfig)) {
        $msg = 'Configuração Phinx inválida (esperado array).';
        log_unificado($msg, $LOG_FILE_DB);
        throw new RuntimeException($msg);
    }

    // Instancia objetos do Phinx
    try {
        $config = new \Phinx\Config\Config($rawConfig, $phinxConfigFile);
        // Inputs/Outputs do Symfony Console
        $input  = new \Symfony\Component\Console\Input\ArrayInput([]);
        $buffer = new \Symfony\Component\Console\Output\BufferedOutput();
        $manager = new \Phinx\Migration\Manager($config, $input, $buffer);

        // Ambiente alvo
        $env = $config->getDefaultEnvironment() ?: 'gestor';
        log_unificado('[DEBUG] MIGRATING ENV=' . $env, $LOG_FILE_DB);

        // Executa as migrações pendentes
        $manager->migrate($env);

        $out = $buffer->fetch();
        if ($out !== '') {
            foreach (explode("\n", trim($out)) as $line) {
                if ($line==='') continue;
                log_unificado('[PHINX] ' . $line, $LOG_FILE_DB);
            }
        }
        log_unificado(tr('_migrations_done'), $LOG_FILE_DB);
        if (PHP_SAPI === 'cli') echo "[OK] Migrações concluídas via API interna!\n";
        return ['output' => $out];
    } catch (\Throwable $e) {
        $msg = 'Falha ao executar migrações via API: ' . $e->getMessage();
        log_unificado($msg, $LOG_FILE_DB);
        if (PHP_SAPI === 'cli') echo "[ERRO] $msg\n";
        throw $e;
    }
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

/**
 * Carrega o contrato consolidado de sincronização (schema-metadata.json) com cache.
 * Fonte única e dinâmica das regras antes hardcoded ($tabelasChaveNatural, $tabelasInsertOnly,
 * $preserveMap e a chave natural por tabela). Gerado por atualizacao-dados-recursos.php.
 */
function schemaMetadata(): array {
    global $DB_DATA_DIR;
    static $meta = null;
    if ($meta !== null) return $meta;
    $meta = ['tables' => [], 'deletar' => [], 'forcar_atualizacao' => []];
    $file = $DB_DATA_DIR . 'schema-metadata.json';
    if (is_file($file)) {
        $d = json_decode((string)file_get_contents($file), true);
        if (is_array($d)) {
            $meta['tables'] = is_array($d['tables'] ?? null) ? $d['tables'] : [];
            $meta['deletar'] = is_array($d['deletar'] ?? null) ? $d['deletar'] : [];
            $meta['forcar_atualizacao'] = is_array($d['forcar_atualizacao'] ?? null) ? $d['forcar_atualizacao'] : [];
        }
    } else {
        log_unificado('SCHEMA_METADATA_AUSENTE ' . $file . ' (usando fallback heurístico)', $GLOBALS['LOG_FILE_DB'] ?? 'atualizacoes-bd');
    }
    return $meta;
}

/** Metadados de uma tabela no contrato (ou null). */
function tabelaMeta(string $tabela): ?array {
    $m = schemaMetadata();
    return $m['tables'][$tabela] ?? null;
}

/** Colunas da chave natural de uma tabela (vazio quando a estratégia não é natural_key). */
function naturalKeyColumns(string $tabela): array {
    $meta = tabelaMeta($tabela);
    if ($meta && ($meta['strategy'] ?? 'pk') === 'natural_key' && is_array($meta['natural_key_columns'] ?? null)) {
        return $meta['natural_key_columns'];
    }
    return [];
}

/**
 * Lista de registros sob "atualização forçada" para uma tabela (do contrato schema-metadata.json).
 * Cada item é { "pk": valor } ou { "natural_key": { coluna: valor, ... } }. Registros que casarem
 * com essas regras são sobrescritos pelo deploy ignorando as proteções de project e user_modified
 * (e com reset de user_modified=0). Retorna lista vazia quando não há regras.
 */
function forcarAtualizacaoLista(string $tabela): array {
    $m = schemaMetadata();
    $lista = $m['forcar_atualizacao'][$tabela] ?? [];
    return is_array($lista) ? $lista : [];
}

/**
 * Motor genérico de chave natural: monta a chave de comparação a partir das colunas
 * definidas no contrato. Colunas modulo/module/grupo são opcionais (default ''); as demais
 * (id, language, perfil, operacao, ...) são obrigatórias — ausência => chave inválida (null).
 * Aceita alias language/linguagem_codigo para compatibilidade com schema legado.
 */
function naturalKeyGenerica(string $tabela, array $row): ?string {
    $cols = naturalKeyColumns($tabela);
    if (!$cols) return null;
    static $opcionais = ['modulo' => true, 'module' => true, 'grupo' => true];
    $parts = [];
    foreach ($cols as $c) {
        $v = $row[$c] ?? null;
        if (($v === null || $v === '') && $c === 'language') { $v = $row['linguagem_codigo'] ?? null; }
        if ($v === null || $v === '') {
            if (isset($opcionais[$c])) { $parts[] = ''; continue; }
            return null; // coluna obrigatória ausente
        }
        $parts[] = strtolower((string)$v);
    }
    return implode('|', $parts);
}

/** Mapa explícito de PKs quando nome foge ao padrão id_<tabela> (fallback retrocompat). */
function pkPorTabela(string $tabela): ?string {
    $meta = tabelaMeta($tabela);
    if ($meta && !empty($meta['id_numerico'])) return $meta['id_numerico'];
    static $map = [
        'paginas' => 'id_paginas',
        'layouts' => 'id_layouts',
        'componentes' => 'id_componentes',
    'variaveis' => 'id_variaveis',
    // Permissões / relacionamentos
    'usuarios_perfis_modulos' => 'id_usuarios_perfis_modulos',
    'usuarios_perfis_modulos_operacoes' => 'id_usuarios_perfis_modulos_operacoes'
    ];
    return $map[$tabela] ?? null;
}

/**
 * Obtém o max_allowed_packet do MySQL dinamicamente, com fallback fixo de 16MB.
 * Usado pelo loteador (threshold-based batching) para limitar pacotes a 70% do máximo.
 */
function maxAllowedPacket(PDO $pdo): int {
    static $cache = null;
    if ($cache !== null) return $cache;
    $fallback = 16 * 1024 * 1024; // 16MB
    try {
        $row = $pdo->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch(PDO::FETCH_ASSOC);
        $val = isset($row['Value']) ? (int)$row['Value'] : 0;
        $cache = $val > 0 ? $val : $fallback;
    } catch (Throwable $e) {
        $cache = $fallback;
    }
    return $cache;
}

/**
 * Insere registros em lote (multi-row), dividindo em chunks que respeitam o $threshold
 * (70% do max_allowed_packet — threshold-based batching). Agrupa por assinatura de colunas
 * (multi-row exige colunas idênticas). Em erro de lote (ex.: chave duplicada), faz fallback
 * individual preservando o skip de duplicatas. Atualiza $cnt['inserted'] e $cnt['same'].
 */
function inserirEmLote(PDO $pdo, string $tabela, array $linhas, int $threshold, bool $simulate, array &$cnt): void {
    if (!$linhas) return;
    if ($threshold < 4096) $threshold = 4096; // piso de segurança
    // Agrupar por assinatura de colunas
    $grupos = [];
    foreach ($linhas as $row) {
        $cols = array_keys($row);
        $sig = implode('|', $cols);
        if (!isset($grupos[$sig])) $grupos[$sig] = ['cols' => $cols, 'rows' => []];
        $grupos[$sig]['rows'][] = $row;
    }
    foreach ($grupos as $g) {
        $cols = $g['cols'];
        $colSql = implode(',', array_map(fn($c) => "`$c`", $cols));
        $buffer = [];
        $bufSize = 0;
        $flush = function () use (&$buffer, &$bufSize, $pdo, $tabela, $colSql, $cols, &$cnt, $simulate) {
            if (!$buffer) return;
            if ($simulate) {
                log_unificado("SIMULATE_INSERT_BATCH $tabela qtd=" . count($buffer), $GLOBALS['LOG_FILE_DB']);
                $cnt['inserted'] += count($buffer);
                $buffer = []; $bufSize = 0; return;
            }
            $placeholders = []; $params = []; $n = 0;
            foreach ($buffer as $r) {
                $ph = [];
                foreach ($cols as $c) { $k = ':p' . $n; $ph[] = $k; $params[$k] = $r[$c]; $n++; }
                $placeholders[] = '(' . implode(',', $ph) . ')';
            }
            $sql = "INSERT INTO `$tabela` ($colSql) VALUES " . implode(',', $placeholders);
            try {
                $pdo->prepare($sql)->execute($params);
                $cnt['inserted'] += count($buffer);
            } catch (PDOException $e) {
                // Fallback individual (isola duplicatas e erros pontuais)
                foreach ($buffer as $r) {
                    $ph = []; $p = [];
                    foreach ($cols as $c) { $ph[] = ':' . $c; $p[':' . $c] = $r[$c]; }
                    $sql1 = "INSERT INTO `$tabela` ($colSql) VALUES (" . implode(',', $ph) . ")";
                    try { $pdo->prepare($sql1)->execute($p); $cnt['inserted']++; }
                    catch (PDOException $e2) {
                        $msgErr = $e2->getMessage();
                        if (stripos($msgErr, 'Duplicate entry') !== false || stripos($msgErr, 'UNIQUE constraint') !== false) {
                            log_unificado("DUP_SKIP_BATCH $tabela " . encLog($msgErr), $GLOBALS['LOG_FILE_DB']);
                            $cnt['same']++;
                        } else {
                            log_unificado("ERROR_INSERT_BATCH $tabela " . encLog($e2->getMessage()), $GLOBALS['LOG_FILE_DB']);
                            throw $e2;
                        }
                    }
                }
            }
            $buffer = []; $bufSize = 0;
        };
        foreach ($g['rows'] as $row) {
            $rowSize = 0; foreach ($row as $v) { $rowSize += strlen((string)$v) + 3; }
            if ($buffer && ($bufSize + $rowSize) > $threshold) { $flush(); }
            $buffer[] = $row; $bufSize += $rowSize;
        }
        $flush();
    }
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
    if (empty($registros)) return ['inserted'=>0,'updated'=>0,'same'=>0];
    $debug = !empty($GLOBALS['CLI_OPTS']['debug']);
    $project = $GLOBALS['CLI_OPTS']['project'] ?? null;

    // Descobrir colunas existentes na tabela para filtrar campos inexistentes vindos do JSON
    static $schemaCache = [];
    if (!isset($schemaCache[$tabela])) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC);
            $schemaCache[$tabela] = array_fill_keys(array_map(fn($c)=>$c['Field'], $cols), true);
        } catch (Throwable $e) {
            $schemaCache[$tabela] = null; // não conseguiu descobrir; não filtra
            if ($debug) log_unificado("WARN_SCHEMA tabela=$tabela msg=".encLog($e->getMessage()), $GLOBALS['LOG_FILE_DB']);
        }
    }
    $allowedCols = $schemaCache[$tabela];

    // Descobrir coluna real de linguagem no schema (se existir)
    $colLangReal = null;
    if (is_array($allowedCols)) {
        if (isset($allowedCols['language'])) $colLangReal='language';
        elseif (isset($allowedCols['linguagem_codigo'])) $colLangReal='linguagem_codigo';
    }
    // Normaliza linha JSON para ter somente a coluna real de linguagem (se identificada)
    $normalizeLangRow = function(array &$r) use ($colLangReal) {
        if (!$colLangReal) return; // nada a fazer
        $alt = $colLangReal === 'language' ? 'linguagem_codigo' : 'language';
        // Se veio só o alias alternativo, copiar para o real
        if (!isset($r[$colLangReal]) && isset($r[$alt])) {
            $r[$colLangReal] = $r[$alt];
        }
        // Se ambos existem, preferir o real e remover o alternativo para não gerar diffs em coluna inexistente
        if (isset($r[$colLangReal]) && isset($r[$alt])) {
            unset($r[$alt]);
        }
    };

    // Regras de sincronização carregadas dinamicamente do contrato schema-metadata.json
    // (substituem $tabelasChaveNatural, $tabelasInsertOnly e $preserveMap hardcoded).
    $meta = tabelaMeta($tabela);
    $strategy = $meta['strategy'] ?? 'pk';
    $preserveCampos = ($meta && is_array($meta['preserve_on_user_modified'] ?? null)) ? $meta['preserve_on_user_modified'] : [];
    $temPreserve = !empty($preserveCampos);

    $pkDeclarada = pkPorTabela($tabela) ?? descobrirPK($tabela, $registros[0]);
    $primeiroTemPk = array_key_exists($pkDeclarada, $registros[0]);
    $usarChaveNatural = ($strategy === 'natural_key'); // estratégia definida no contrato
    $insertOnly = !empty($meta['insert_only']); // tabela só aceita INSERT

    // Atualização forçada (BATCH-056): registros que sobrescrevem as proteções de project e
    // user_modified (com reset de user_modified=0). Identificados por PK ou por chave natural.
    $forcarLista = forcarAtualizacaoLista($tabela);
    $forcedPks = [];       // valor PK (string) => true
    $forcedNaturais = [];  // chave natural => true
    foreach ($forcarLista as $fReg) {
        if (!is_array($fReg)) continue;
        if (array_key_exists('pk', $fReg)) {
            $forcedPks[(string)$fReg['pk']] = true;
        } elseif (isset($fReg['natural_key']) && is_array($fReg['natural_key'])) {
            $nk = naturalKeyGenerica($tabela, $fReg['natural_key']);
            if ($nk !== null) $forcedNaturais[$nk] = true;
        }
    }
    $temForcar = ($forcedPks || $forcedNaturais);
    // Resolve se um registro existente do banco está sob atualização forçada (PK e/ou chave natural).
    $isForced = function(array $exist) use ($tabela, $pkDeclarada, $forcedPks, $forcedNaturais, $temForcar): bool {
        if (!$temForcar) return false;
        if ($forcedPks && isset($exist[$pkDeclarada]) && isset($forcedPks[(string)$exist[$pkDeclarada]])) return true;
        if ($forcedNaturais) { $nk = naturalKeyGenerica($tabela, $exist); if ($nk !== null && isset($forcedNaturais[$nk])) return true; }
        return false;
    };

    if ($debug) {
        log_unificado("SYNC_INI tabela=$tabela modo=".($usarChaveNatural?'natural':'pk')." qtdJson=".count($registros)." forcar=".count($forcarLista), $GLOBALS['LOG_FILE_DB']);
    }

    $inserted=$updated=$same=0;
    $insertThreshold = (int)(maxAllowedPacket($pdo) * 0.7); // loteador: 70% do max_allowed_packet (fallback 16MB)

    // Função para chave natural (motor genérico baseado no contrato schema-metadata.json)
    $naturalKeyFn = fn(string $tabela, array $row): ?string => naturalKeyGenerica($tabela, $row);

    // Carregar linhas existentes
    $dbRows = $pdo->query("SELECT * FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC);

    // Preparar modo órfãos
    $orphansMode = $GLOBALS['CLI_OPTS']['orphans-mode'] ?? 'export'; // export|log|ignore
    $orphans = [];

    if (!$usarChaveNatural) {
        // Modo anterior baseado em PK
        $jsonByPk = [];
        foreach ($registros as $r) {
            // Normalizar linguagem antes de indexar
            $normalizeLangRow($r);
            if (!array_key_exists($pkDeclarada, $r)) continue; // ignora sem pk
            $jsonByPk[(string)$r[$pkDeclarada]] = $r;
        }
        $matched = [];
        foreach ($dbRows as $exist) {
            $pkVal = (string)($exist[$pkDeclarada] ?? ''); if ($pkVal==='') continue;
            if (!isset($jsonByPk[$pkVal])) {
                if ($debug) log_unificado("ORPHAN_DB_ROW tabela=$tabela pk=$pkVal", $GLOBALS['LOG_FILE_DB']);
                if ($orphansMode !== 'ignore') { $orphans[] = $exist; }
                $same++;
                continue;
            }
            $row = $jsonByPk[$pkVal];
            $matched[$pkVal]=true; $diff=[]; $oldVals=[];
            $forced = $isForced($exist); // atualização forçada: ignora project/user_modified

            // Se tabela é insert-only, pular atualização
            if ($insertOnly) {
                if ($debug) log_unificado("SKIP_UPDATE_INSERT_ONLY tabela=$tabela pk=$pkVal", $GLOBALS['LOG_FILE_DB']);
                $same++;
                continue;
            }

            // Proteção de projeto para tabelas específicas (ignorada sob atualização forçada)
            if ($temPreserve && !$forced) {
                if (!$project && !empty($exist['project'])) {
                    if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
                        $same++;
                        continue;
                    }
                }
            }
            foreach ($row as $c=>$vNew) {
                if ($c === $pkDeclarada) continue;
                // Filtrar colunas inexistentes no schema (exceto map de linguagem)
                if (is_array($allowedCols) && !isset($allowedCols[$c])) {
                    // Mapear language->linguagem_codigo se necessário
                    if ($c==='language' && isset($allowedCols['linguagem_codigo']) && !isset($diff['linguagem_codigo'])) {
                        $vOldMap = $exist['linguagem_codigo'] ?? null;
                        if (normalizeValue($row['language'])!==normalizeValue($vOldMap)) { $diff['linguagem_codigo']=$row['language']; $oldVals['linguagem_codigo']=$vOldMap; }
                    }
                    continue;
                }
                $vOld = $exist[$c] ?? null;
                if ($c==='user_modified' && (int)$vOld===1 && (int)$vNew!==1 && !$forced) continue;
                if (normalizeValue($vNew)!==normalizeValue($vOld)) { $diff[$c]=$vNew; $oldVals[$c]=$vOld; }
            }
            // Sob atualização forçada NÃO preservamos campos do usuário (payload completo do JSON).
            if (isset($exist['user_modified']) && (int)$exist['user_modified']===1 && $temPreserve && !$forced) {
                $changedPreserved=false; foreach ($preserveCampos as $campo) {
                    if (array_key_exists($campo,$diff)) {
                        if ($tabela==='variaveis' && $campo==='valor') { if (isset($exist['value_updated'])||isset($row['value_updated'])) $diff['value_updated']=$diff[$campo]; }
                        else { $dest=$campo.'_updated'; if (isset($exist[$dest])||isset($row[$dest])) $diff[$dest]=$diff[$campo]; }
                        unset($diff[$campo]); $changedPreserved=true; }
                }
                if ($changedPreserved) { if (isset($exist['system_updated'])||isset($row['system_updated'])) $diff['system_updated']=1; if ($debug) log_unificado("USER_MODIFIED_PRESERVADO $tabela pk=$pkVal", $GLOBALS['LOG_FILE_DB']); }
            }
            // Reset de user_modified=0 quando forçado (alinha o registro à base de código do deploy).
            if ($forced && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (!is_array($allowedCols) || isset($allowedCols['user_modified']))) {
                $diff['user_modified'] = 0; $oldVals['user_modified'] = $exist['user_modified'];
            }
            if ($diff) {
                // Marcar projeto se deploy de projeto (preserva o project existente quando forçado).
                if ($temPreserve && $project && !$forced) {
                    $diff['project'] = $project;
                }
                if ($simulate) { log_unificado("SIMULATE_UPDATE $tabela pk=$pkVal campos=".implode(',',array_keys($diff)),$GLOBALS['LOG_FILE_DB']); }
                else { $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff))); $sql="UPDATE `$tabela` SET $sets WHERE `$pkDeclarada`=:pk"; $stmt=$pdo->prepare($sql); $params=$diff; $params['pk']=$pkVal; $stmt->execute($params);}            
                if ($logDiffs) { $pairs=[];$lim=0; foreach ($diff as $c=>$v){$pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(++$lim>=10){$pairs[]='...';break;}} log_unificado(tr('_diff_update_detail',['tabela'=>$tabela,'pk'=>$pkVal,'campos'=>implode(', ',$pairs)]),$GLOBALS['LOG_FILE_DB']); }
                $updated++; } else { $same++; }
        }
        $novosPk = [];
        foreach ($jsonByPk as $pkVal=>$row) {
            if (isset($matched[$pkVal])) continue;
            $normalizeLangRow($row);
            if (is_array($allowedCols)) { $row = array_intersect_key($row, $allowedCols); }
            if(!$row){ $same++; continue; }
            // Marcar projeto se deploy de projeto para tabelas específicas
            if ($temPreserve && $project) { $row['project'] = $project; }
            $novosPk[] = $row;
        }
        if ($novosPk) {
            $cntPk = ['inserted'=>0,'same'=>0];
            inserirEmLote($pdo, $tabela, $novosPk, $insertThreshold, $simulate, $cntPk);
            $inserted += $cntPk['inserted']; $same += $cntPk['same'];
        }
        // Exportar órfãos se houver
        if ($orphans && $orphansMode === 'export') {
            $dir = $GLOBALS['DB_ORPHANS_DIR'] ?? ($GLOBALS['DB_ORPHANS_DIR'] = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'orphans' . DIRECTORY_SEPARATOR . 'bd' . DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $file = $dir . $tabela . '-orphans-' . date('Ymd-His') . '.json';
            @file_put_contents($file, json_encode($orphans, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado("ORPHANS_EXPORTED tabela=$tabela qtd=".count($orphans)." arquivo=\"$file\"", $GLOBALS['LOG_FILE_DB']);
        } elseif ($orphans && $orphansMode === 'log') {
            log_unificado("ORPHANS_DETECTED tabela=$tabela qtd=".count($orphans), $GLOBALS['LOG_FILE_DB']);
        }
        if ($debug) log_unificado("SYNC_FIM tabela=$tabela +$inserted ~$updated =$same orphans=".count($orphans)." modo=pk", $GLOBALS['LOG_FILE_DB']);
        return ['inserted'=>$inserted,'updated'=>$updated,'same'=>$same];
    }

    // ===== MODO CHAVE NATURAL =====
    // Indexar DB por chave natural
    $dbIndex = []; // chaveNatural => row
    $dbPkIndex = []; // chaveNatural => pk numérico
    // Índices secundários (fallback) para registros que perderam a linguagem (bug histórico) a fim de evitar duplicações
    $fallbackIndex = [
        'layouts' => [],          // modulo|id
        'componentes' => [],      // modulo|id
        'paginas' => [],          // modulo|id
        'templates' => [],        // modulo|id
        'forms' => [],            // modulo|id
        'variaveis' => [],        // modulo|grupo|id
        'modulos' => [],          // id
        'modulos_grupos' => [],   // id
        'modulos_operacoes' => [],// id
        'usuarios_perfis' => [],  // id
        'prompts_ia' => [],       // id
        'alvos_ia' => [],         // id
        'modos_ia' => []          // id
    ];
    foreach ($dbRows as $exist) {
        $k = $naturalKeyFn($tabela, $exist); if ($k===null) continue; $dbIndex[$k] = $exist; // última ocorrência prevalece
        // Captura PK numérico se existir
        $pkNumeric = pkPorTabela($tabela) ?? null; if ($pkNumeric && isset($exist[$pkNumeric])) { $dbPkIndex[$k] = $exist[$pkNumeric]; }
        // Fallback: se não há language/linguagem_codigo válido, indexar por combinação sem linguagem
        $langVal = $exist['language'] ?? $exist['linguagem_codigo'] ?? null;
        if ($langVal===null || $langVal==='') {
            switch ($tabela) {
                case 'layouts':
                case 'componentes':
                case 'paginas':
                case 'templates':
                    $mod = $exist['modulo'] ?? ''; if (isset($exist['id'])) $fallbackIndex[$tabela][$mod.'|'.$exist['id']] = $exist; break;
                case 'forms':
                    $mod = $exist['module'] ?? ''; if (isset($exist['id'])) $fallbackIndex[$tabela][$mod.'|'.$exist['id']] = $exist; break;
                case 'variaveis':
                    $mod = $exist['modulo'] ?? ''; $grp = $exist['grupo'] ?? ''; if (isset($exist['id'])) $fallbackIndex[$tabela][$mod.'|'.$grp.'|'.$exist['id']] = $exist; break;
                case 'modulos':
                case 'modulos_grupos':
                case 'modulos_operacoes':
                case 'usuarios_perfis':
                case 'prompts_ia':
                case 'alvos_ia':
                case 'modos_ia':
                    if (isset($exist['id'])) $fallbackIndex[$tabela][$exist['id']] = $exist; break;
            }
        }
    }

    $novosNaturais = [];
    foreach ($registros as $row) {
        $k = $naturalKeyFn($tabela, $row);
        if ($k===null) { if ($debug) log_unificado("SKIP_INVALID_NATURAL_KEY tabela=$tabela row_sem_chave", $GLOBALS['LOG_FILE_DB']); continue; }
        // Normalizar linguagem (após gerar chave natural que aceita ambos os aliases)
        $normalizeLangRow($row);
        if (isset($dbIndex[$k])) {
            $exist = $dbIndex[$k];
            $diff=[]; $oldVals=[];
            $forced = ($temForcar && (isset($forcedNaturais[$k]) || $isForced($exist))); // atualização forçada

            // Proteção de projeto para tabelas específicas (ignorada sob atualização forçada)
            if ($temPreserve && !$forced) {
                if (!$project && !empty($exist['project'])) {
                    if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
                        $same++;
                        continue;
                    }
                }
            }
            foreach ($row as $c=>$vNew) {
                // Ignorar campos de controle que não fazem parte do JSON natural
                if ($c === 'user_modified' && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (int)$vNew!==1 && !$forced) continue;
                // Filtrar colunas inexistentes, com mapeamento de linguagem
                if (is_array($allowedCols) && !isset($allowedCols[$c])) {
                    if ($c==='language' && isset($allowedCols['linguagem_codigo']) && !isset($diff['linguagem_codigo'])) {
                        $vOldMap = $exist['linguagem_codigo'] ?? null;
                        if (normalizeValue($row['language'])!==normalizeValue($vOldMap)) { $diff['linguagem_codigo']=$row['language']; $oldVals['linguagem_codigo']=$vOldMap; }
                    }
                    continue;
                }
                $vOld = $exist[$c] ?? null;
                if (normalizeValue($vNew)!==normalizeValue($vOld)) { $diff[$c]=$vNew; $oldVals[$c]=$vOld; }
            }
            // Sob atualização forçada NÃO preservamos campos do usuário (payload completo do JSON).
            if (isset($exist['user_modified']) && (int)$exist['user_modified']===1 && $temPreserve && !$forced) {
                $changedPreserved=false; foreach ($preserveCampos as $campo){ if(array_key_exists($campo,$diff)){ if($tabela==='variaveis' && $campo==='valor'){ if(isset($exist['value_updated'])||isset($row['value_updated'])) $diff['value_updated']=$diff[$campo]; } else { $dest=$campo.'_updated'; if(isset($exist[$dest])||isset($row[$dest])) $diff[$dest]=$diff[$campo]; } unset($diff[$campo]); $changedPreserved=true; }} if($changedPreserved){ if(isset($exist['system_updated'])||isset($row['system_updated'])) $diff['system_updated']=1; if ($debug) log_unificado("USER_MODIFIED_PRESERVADO_NAT tabela=$tabela chave=$k", $GLOBALS['LOG_FILE_DB']); }}
            // Reset de user_modified=0 quando forçado (alinha o registro à base de código do deploy).
            if ($forced && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (!is_array($allowedCols) || isset($allowedCols['user_modified']))) {
                $diff['user_modified'] = 0; $oldVals['user_modified'] = $exist['user_modified'];
            }
            if ($diff) {
                // Marcar projeto se deploy de projeto (preserva o project existente quando forçado).
                if ($temPreserve && $project && !$forced) {
                    $diff['project'] = $project;
                }
                if ($simulate) { log_unificado("SIMULATE_UPDATE_NAT tabela=$tabela chave=$k campos=".implode(',',array_keys($diff)),$GLOBALS['LOG_FILE_DB']); }
                else {
                    // Atualiza via chave natural (WHERE pelos campos da chave) ou via PK se disponível
                    $whereSql=''; $params=$diff;
                    if (isset($dbPkIndex[$k])) { // usar PK numérico mais eficiente
                        $pkNumeric = pkPorTabela($tabela);
                        $whereSql = "WHERE `$pkNumeric` = :__pk"; $params['__pk']=$dbPkIndex[$k];
                    } else {
                        // WHERE genérico pelos componentes da chave natural (null-safe <=>), com alias de linguagem legado
                        $cols = naturalKeyColumns($tabela);
                        $conds = [];
                        foreach ($cols as $i => $c) {
                            $pn = '__nk' . $i;
                            $colReal = ($c === 'language' && !array_key_exists('language', $exist) && array_key_exists('linguagem_codigo', $exist)) ? 'linguagem_codigo' : $c;
                            $valor = $exist[$c] ?? (($c === 'language') ? ($exist['linguagem_codigo'] ?? null) : null);
                            $conds[] = "`$colReal` <=> :$pn";
                            $params[$pn] = $valor;
                        }
                        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : 'WHERE 1=0';
                    }
                    $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff)));
                    $sql="UPDATE `$tabela` SET $sets $whereSql"; $stmt=$pdo->prepare($sql); $stmt->execute($params);
                }
                if ($logDiffs) { $pairs=[];$lim=0; foreach ($diff as $c=>$v){$pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(++$lim>=10){$pairs[]='...';break;}} log_unificado(tr('_diff_update_detail',['tabela'=>$tabela,'pk'=>$k,'campos'=>implode(', ',$pairs)]),$GLOBALS['LOG_FILE_DB']); }
                $updated++;
            } else { $same++; }
        } else {
            // Tentar fallback de recuperação (linha existente sem linguagem)
            $fallbackKey = null; $existFallback = null;
            switch ($tabela) {
                case 'paginas': case 'layouts': case 'componentes': case 'templates': $fallbackKey = ($row['modulo'] ?? '').'|'.($row['id'] ?? ''); $existFallback = $fallbackIndex[$tabela][$fallbackKey] ?? null; break;
                case 'forms': $fallbackKey = ($row['module'] ?? '').'|'.($row['id'] ?? ''); $existFallback = $fallbackIndex[$tabela][$fallbackKey] ?? null; break;
                case 'variaveis': $fallbackKey = ($row['modulo'] ?? '').'|'.(($row['grupo'] ?? '')).'|'.($row['id'] ?? ''); $existFallback = $fallbackIndex[$tabela][$fallbackKey] ?? null; break;
                case 'modulos': case 'modulos_grupos': case 'modulos_operacoes': case 'usuarios_perfis': case 'prompts_ia': case 'alvos_ia': case 'modos_ia': $fallbackKey = $row['id'] ?? null; $existFallback = $fallbackKey!==null ? ($fallbackIndex[$tabela][$fallbackKey] ?? null) : null; break;
            }
            if ($existFallback) {
                // Atualiza registro existente preenchendo linguagem faltante (auto-correção de bug histórico)
                $exist = $existFallback; $diff=[]; $oldVals=[];
                $forced = $isForced($exist); // atualização forçada

                // Proteção de projeto para tabelas específicas (ignorada sob atualização forçada)
                if ($temPreserve && !$forced) {
                    if (!$project && !empty($exist['project'])) {
                        if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
                            $same++;
                            continue;
                        }
                    }
                }
                $normalizeLangRow($row);
                foreach ($row as $c=>$vNew) {
                    $vOld = $exist[$c] ?? null;
                    if ($c==='user_modified' && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (int)$vNew!==1 && !$forced) continue;
                    if (is_array($allowedCols) && !isset($allowedCols[$c])) {
                        if ($c==='language' && isset($allowedCols['linguagem_codigo']) && !isset($diff['linguagem_codigo'])) {
                            $vOldMap = $exist['linguagem_codigo'] ?? null;
                            if (normalizeValue($row['language'])!==normalizeValue($vOldMap)) { $diff['linguagem_codigo']=$row['language']; $oldVals['linguagem_codigo']=$vOldMap; }
                        }
                        continue;
                    }
                    if (normalizeValue($vNew)!==normalizeValue($vOld)) { $diff[$c]=$vNew; $oldVals[$c]=$vOld; }
                }
                // Se linguagem só existe como 'language' mas schema usa 'linguagem_codigo', ajustar
                if (isset($diff['language']) && is_array($allowedCols) && !isset($allowedCols['language']) && isset($allowedCols['linguagem_codigo']) && !isset($diff['linguagem_codigo'])) {
                    $diff['linguagem_codigo'] = $diff['language']; unset($diff['language']);
                }
                // Reset de user_modified=0 quando forçado (alinha o registro à base de código do deploy).
                if ($forced && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (!is_array($allowedCols) || isset($allowedCols['user_modified']))) {
                    $diff['user_modified'] = 0; $oldVals['user_modified'] = $exist['user_modified'];
                }
                if ($diff) {
                    // Marcar projeto se deploy de projeto (preserva o project existente quando forçado).
                    if ($temPreserve && $project && !$forced) {
                        $diff['project'] = $project;
                    }
                    if ($simulate) { log_unificado("SIMULATE_UPDATE_FALLBACK_NAT tabela=$tabela fallback=$fallbackKey campos=".implode(',',array_keys($diff)),$GLOBALS['LOG_FILE_DB']); }
                    else {
                        // WHERE por PK numérica se existir senão por combinação fallback + language IS NULL
                        $whereSql=''; $params=$diff;
                        if ($pkDeclarada && isset($exist[$pkDeclarada])) { $whereSql="WHERE `$pkDeclarada`=:__pk"; $params['__pk']=$exist[$pkDeclarada]; }
                        else {
                            // fallback genérico: casa por chave natural ignorando a linguagem ausente
                            $cols = naturalKeyColumns($tabela);
                            $conds = [];
                            foreach ($cols as $i => $c) {
                                if ($c === 'language') { $conds[] = "(`language` IS NULL OR `language` = '')"; continue; }
                                $pn = '__fk' . $i; $conds[] = "`$c` <=> :$pn"; $params[$pn] = $exist[$c] ?? null;
                            }
                            $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : 'WHERE 1=0';
                        }
                        $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff)));
                        $sql="UPDATE `$tabela` SET $sets $whereSql"; $stmt=$pdo->prepare($sql); $stmt->execute($params);
                    }
                    if ($logDiffs) { $pairs=[]; foreach ($diff as $c=>$v){ $pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(count($pairs)>=10){$pairs[]='...';break;} } log_unificado("FALLBACK_UPDATE_NAT tabela=$tabela chave=$fallbackKey campos=".implode(', ',$pairs), $GLOBALS['LOG_FILE_DB']); }
                    $updated++;
                } else { $same++; }
            } else {
                // Novo registro – acumular para inserção em lote (sem PK numérica)
                $normalizeLangRow($row);
                if (is_array($allowedCols)) { $row = array_intersect_key($row, $allowedCols); }
                $row = array_filter($row, fn($c)=>!preg_match('/^id_/', $c), ARRAY_FILTER_USE_KEY); // evitar enviar id_paginas etc
                // Marcar projeto se deploy de projeto para tabelas específicas
                if ($temPreserve && $project) { $row['project'] = $project; }
                $novosNaturais[] = $row;
            }
        }
    }

    // Flush dos novos registros acumulados em lote (threshold-based batching)
    if ($novosNaturais) {
        $cntNat = ['inserted'=>0,'same'=>0];
        inserirEmLote($pdo, $tabela, $novosNaturais, $insertThreshold, $simulate, $cntNat);
        $inserted += $cntNat['inserted']; $same += $cntNat['same'];
    }

    // Detectar órfãos naturais (dbIndex - registros JSON)
    // Construir conjunto JSON para detecção de órfãos (post-process) – mais eficiente fora do laço
    $jsonKeys = [];
    foreach ($registros as $row) { $nk=$naturalKeyFn($tabela,$row); if($nk!==null) $jsonKeys[$nk]=true; }
    foreach ($dbIndex as $nk=>$exist) {
        if (!isset($jsonKeys[$nk])) {
            if ($debug) log_unificado("ORPHAN_DB_ROW_NAT tabela=$tabela chave=$nk", $GLOBALS['LOG_FILE_DB']);
            if ($orphansMode !== 'ignore') { $orphans[] = $exist; }
        }
    }
    // Exportar órfãos
    if ($orphans && $orphansMode === 'export') {
        $dir = $GLOBALS['DB_ORPHANS_DIR'] ?? ($GLOBALS['DB_ORPHANS_DIR'] = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'orphans' . DIRECTORY_SEPARATOR . 'bd' . DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . $tabela . '-orphans-' . date('Ymd-His') . '.json';
        @file_put_contents($file, json_encode($orphans, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    log_unificado("ORPHANS_EXPORTED tabela=$tabela qtd=".count($orphans)." arquivo=\"$file\"", $GLOBALS['LOG_FILE_DB']);
    } elseif ($orphans && $orphansMode === 'log') {
        log_unificado("ORPHANS_DETECTED tabela=$tabela qtd=".count($orphans), $GLOBALS['LOG_FILE_DB']);
    }

    if ($debug) log_unificado("SYNC_FIM tabela=$tabela +$inserted ~$updated =$same orphans=".count($orphans)." modo=natural", $GLOBALS['LOG_FILE_DB']);
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

/**
 * Executa a deleção física (deleção imperativa) dos registros declarados no bloco "deletar"
 * do contrato schema-metadata.json. Cada registro é identificado por { "pk": valor } ou
 * { "natural_key": { coluna: valor, ... } }. Retorna o total removido por tabela.
 */
function executarDelecoes(PDO $pdo, bool $simulate = false): array {
    $meta = schemaMetadata();
    $deletar = $meta['deletar'] ?? [];
    $resumo = [];
    foreach ($deletar as $tabela => $registros) {
        if (!is_array($registros) || !$registros) continue;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$tabela)) { log_unificado("DELETE_SKIP_TABELA_INVALIDA tabela=$tabela", $GLOBALS['LOG_FILE_DB']); continue; }
        $pk = pkPorTabela($tabela);
        $removidos = 0;
        foreach ($registros as $reg) {
            if (!is_array($reg)) continue;
            $where = ''; $params = [];
            if (array_key_exists('pk', $reg) && $pk) {
                $where = "`$pk` = :__pk"; $params['__pk'] = $reg['pk'];
            } elseif (isset($reg['natural_key']) && is_array($reg['natural_key'])) {
                $conds = [];
                foreach ($reg['natural_key'] as $col => $val) {
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$col)) continue; // proteção contra injeção em identificadores
                    $pn = 'd_' . $col;
                    $conds[] = "`$col` <=> :$pn"; $params[$pn] = $val;
                }
                $where = $conds ? implode(' AND ', $conds) : '';
            }
            if ($where === '') { log_unificado("DELETE_SKIP_INVALIDO tabela=$tabela", $GLOBALS['LOG_FILE_DB']); continue; }
            $sql = "DELETE FROM `$tabela` WHERE $where";
            if ($simulate) { log_unificado("SIMULATE_DELETE tabela=$tabela where=$where", $GLOBALS['LOG_FILE_DB']); continue; }
            try {
                $stmt = $pdo->prepare($sql); $stmt->execute($params); $removidos += $stmt->rowCount();
            } catch (Throwable $e) {
                log_unificado("ERROR_DELETE tabela=$tabela ex=" . encLog($e->getMessage()), $GLOBALS['LOG_FILE_DB']);
            }
        }
        if ($removidos) log_unificado("DELETE_DONE tabela=$tabela removidos=$removidos", $GLOBALS['LOG_FILE_DB']);
        $resumo[$tabela] = $removidos;
    }
    return $resumo;
}

function comparacaoDados(): array {
    global $DB_DATA_DIR, $LOG_FILE_DB, $CLI_OPTS, $CHECKSUM_CHANGED_TABLES;
    log_unificado(tr('_compare_start'), $LOG_FILE_DB);
    $arquivos = glob($DB_DATA_DIR . '*Data.json');
    // Filtro --tables opcional
    if (!empty($CLI_OPTS['tables'])) {
        $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
        $arquivos = array_values(array_filter($arquivos, function($f) use ($filter){
            $t = tabelaFromDataFile($f); return in_array(strtolower($t), $filter, true);
        }));
        log_unificado(tr('_filter_tables',[ 'lista'=>implode(',', array_map(fn($f)=>tabelaFromDataFile($f), $arquivos))]), $LOG_FILE_DB);
    }
    $pdo = db();
    $resumo = [];
    foreach ($arquivos as $file) {
        $tabela = tabelaFromDataFile($file);
        if (is_array($CHECKSUM_CHANGED_TABLES) && !in_array($tabela, $CHECKSUM_CHANGED_TABLES, true)) {
            log_unificado("SKIP_NO_CHECKSUM_CHANGE tabela=$tabela", $LOG_FILE_DB);
            continue;
        }
        $registros = loadDataFile($file);
        // Ajustes específicos pré-sincronização
        if ($tabela === 'paginas' && $registros) {
            foreach ($registros as &$r) {
                if (isset($r['type']) && !isset($r['tipo'])) { $r['tipo']=$r['type']; unset($r['type']); }
                if (isset($r['tipo'])) { $map=['page'=>'pagina','system'=>'sistema']; $orig=strtolower((string)$r['tipo']); if(isset($map[$orig])) $r['tipo']=$map[$orig]; }
                // Campo 'plugin' não existe mais na estrutura da tabela paginas
                if (array_key_exists('plugin', $r)) { unset($r['plugin']); }
                $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null];
            } unset($r);
        } elseif (in_array($tabela,['layouts','componentes','templates'],true) && $registros) {
            foreach ($registros as &$r) {
                if (($tabela==='componentes' || $tabela==='templates') && array_key_exists('plugin',$r)) { unset($r['plugin']); }
                $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null];
            } unset($r);
        } elseif ($tabela==='variaveis' && $registros) {
            foreach ($registros as &$r) {
                // Garantir que a coluna real do banco (linguagem_codigo) permaneça presente.
                // Se existir apenas alias 'language' em alguma fonte futura, copiar para linguagem_codigo.
                if (!isset($r['linguagem_codigo']) && isset($r['language'])) {
                    $r['linguagem_codigo'] = $r['language'];
                }
                // Manter também um campo auxiliar 'language' (usado por chave natural) se só houver linguagem_codigo.
                if (isset($r['linguagem_codigo']) && !isset($r['language'])) {
                    $r['language'] = $r['linguagem_codigo'];
                }
                $r += ['user_modified'=>0,'system_updated'=>0,'value_updated'=>null];
            } unset($r);
        }
        if (!$registros) { log_unificado(tr('_compare_no_changes',['tabela'=>$tabela]), $LOG_FILE_DB); continue; }
        log_unificado(tr('_executing_table',['tabela'=>$tabela]), $LOG_FILE_DB);
        $resultado = sincronizarTabela($pdo, $tabela, $registros, !empty($CLI_OPTS['log-diff']), !empty($CLI_OPTS['dry-run']));
        log_unificado(tr('_compare_summary', ['tabela'=>$tabela,'ins'=>$resultado['inserted'],'upd'=>$resultado['updated'],'same'=>$resultado['same']]), $LOG_FILE_DB);
        $resumo[$tabela]=$resultado;
    }
    return $resumo;
}

/** Executa backup JSON de tabelas antes das alterações. */
function executarBackup(PDO $pdo, array $tabelas, string $dirBase): string {
    global $LOG_FILE_DB;
    $timestampDir = $dirBase . date('Ymd-His') . '/';
    if (!is_dir($timestampDir) && !@mkdir($timestampDir, 0775, true)) {
        log_unificado(tr('_backup_error',[ 'msg'=>'mkdir fail '.$timestampDir ]), $LOG_FILE_DB);
        return '';
    }
    log_unificado(tr('_backup_start',[ 'dir'=>$timestampDir ]), $LOG_FILE_DB);
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents($timestampDir . $t . '.json', json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado(tr('_backup_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]), $LOG_FILE_DB);
        } catch (Throwable $e) {
            log_unificado(tr('_backup_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]), $LOG_FILE_DB);
        }
    }
    log_unificado(tr('_backup_complete'), $LOG_FILE_DB);
    return $timestampDir;
}

/** Exporta dados do banco para arquivos *Data.json (modo reverso) */
function reverseExport(PDO $pdo, array $tabelas, string $dataDir): void {
    global $LOG_FILE_DB;
    log_unificado(tr('_reverse_start'), $LOG_FILE_DB);
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) { log_unificado(tr('_reverse_empty',[ 'tabela'=>$t ]), $LOG_FILE_DB); continue; }
            $fileName = dataFileNameFromTable($t);
            $dest = $dataDir . $fileName;
            // backup antigo se existir
            if (file_exists($dest)) {
                @rename($dest, $dest . '.bak.' . date('Ymd-His'));
            }
            file_put_contents($dest, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado(tr('_reverse_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]), $LOG_FILE_DB);
        } catch (Throwable $e) {
            log_unificado(tr('_reverse_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]), $LOG_FILE_DB);
        }
    }
    log_unificado(tr('_reverse_complete'), $LOG_FILE_DB);
}

/** Converte nome de tabela snake_case para PascalCase *Data.json */
function dataFileNameFromTable(string $tabela): string {
    $pascal = preg_replace_callback('/(^|_)([a-z])/', function($m){ return strtoupper($m[2]); }, strtolower($tabela));
    return $pascal . 'Data.json';
}

function relatorioFinal(array $resumo): void {
    global $LOG_FILE_DB;
    $totalIns=$totalUpd=$totalSame=0; foreach ($resumo as $r){$totalIns+=$r['inserted'];$totalUpd+=$r['updated'];$totalSame+=$r['same'];}
    $msg = "📝 " . tr('_final_report') . PHP_EOL
        . str_repeat('═',50) . PHP_EOL;
    foreach ($resumo as $tab=>$r) {
        $msg .= sprintf("📦 %s => +%d ~%d =%d" . PHP_EOL, $tab, $r['inserted'],$r['updated'],$r['same']);
    }
    $msg .= "Σ TOTAL => +$totalIns ~{$totalUpd} ={$totalSame}" . PHP_EOL;
    log_unificado($msg, $LOG_FILE_DB); if (PHP_SAPI === 'cli') echo $msg;
}

function main(): int {
    global $LOG_FILE_DB, $CLI_OPTS, $BACKUP_DIR_BASE, $DB_DATA_DIR, $CHECKSUM_CHANGED_TABLES;

    try {
        log_unificado(tr('_process_start'), $LOG_FILE_DB);
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
            log_unificado(tr('_process_end_success'), $LOG_FILE_DB);
            return 0;
        }
        if (!empty($CLI_OPTS['dry-run'])) log_unificado(tr('_dry_run_mode'), $LOG_FILE_DB);
        if (empty($CLI_OPTS['skip-migrate'])) { migracoes(); } else { log_unificado(tr('_skip_migrations'), $LOG_FILE_DB); }

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
            log_unificado('WARN lendo manager_updates: '.encLog($e->getMessage()), $LOG_FILE_DB);
        }

        if ($previousMap && !$forceAll) {
            $changed = [];
            foreach ($checksums as $file=>$sum) {
                if (!isset($previousMap[$file]) || $previousMap[$file] !== $sum) { $changed[] = tabelaFromDataFile($file); }
            }
            if ($changed) {
                $CHECKSUM_CHANGED_TABLES = $changed;
                log_unificado('CHECKSUM_CHANGED_TABLES='.implode(',', $changed), $LOG_FILE_DB);
            } else {
                $CHECKSUM_CHANGED_TABLES = []; // nenhuma mudou
                log_unificado('CHECKSUM_NENHUMA_TABELA_MUDOU', $LOG_FILE_DB);
            }
        } else {
            if ($forceAll) { log_unificado('FORCE_ALL_TABELAS', $LOG_FILE_DB); }
            elseif (!$previousMap) { log_unificado('CHECKSUM_PRIMEIRA_ATUALIZACAO', $LOG_FILE_DB); }
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
            log_unificado('SEM_MUDANCAS_DADOS -> pulando sincronizacao', $LOG_FILE_DB);
            $resumo = [];
            // Mesmo sem mudanças de dados, processa deleções imperativas declaradas no contrato.
            executarDelecoes($pdo, !empty($CLI_OPTS['dry-run']));
        } else {
            // Envolve toda a sincronização + deleção imperativa em transação PDO (commit/rollBack atômicos).
            $emTransacao = false;
            try {
                $pdo->beginTransaction(); $emTransacao = true;
                $resumo = comparacaoDados();
                executarDelecoes($pdo, !empty($CLI_OPTS['dry-run']));
                $pdo->commit(); $emTransacao = false;
                log_unificado('TRANSACAO_COMMIT', $LOG_FILE_DB);
            } catch (Throwable $e) {
                if ($emTransacao && $pdo->inTransaction()) { $pdo->rollBack(); }
                log_unificado('TRANSACAO_ROLLBACK ' . encLog($e->getMessage()), $LOG_FILE_DB);
                throw $e;
            }
        }
        // Registrar manager_updates
        try {
            $versao = $GLOBALS['_GESTOR']['versao'] ?? null;
            $ins = $pdo->prepare('INSERT INTO manager_updates (db_checksum, backup_path, version, date) VALUES (:c,:b,:v,NOW())');
            $ins->execute([':c'=>$checksumsJson, ':b'=>$backupPath, ':v'=>$versao]);
            log_unificado('MANAGER_UPDATES_REGISTRADO id='.$pdo->lastInsertId(), $LOG_FILE_DB);
        } catch (Throwable $e) {
            log_unificado('WARN registrar manager_updates: '.encLog($e->getMessage()), $LOG_FILE_DB);
        }
        relatorioFinal($resumo);
        gestor_sessao_del_all(); // limpa cache de sessão do gestor (se houver)
        log_unificado(tr('_process_end_success'), $LOG_FILE_DB);
        return 0;
    } catch (Throwable $e) {
        log_unificado(tr('_process_error',['msg'=>$e->getMessage()]), $LOG_FILE_DB);
        if (PHP_SAPI === 'cli') echo 'Erro: ' . $e->getMessage() . PHP_EOL;
        return 1;
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

// Permite execucao via require/include usando $GLOBALS['CLI_OPTS'] (opcional), inclusive em CLI.
// O guard SDD_NO_AUTORUN permite incluir o arquivo em testes sem disparar a execução.
$__included = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__;
if (!defined('SDD_NO_AUTORUN')) {
    if ($__included) {
        global $CLI_OPTS, $GLOBALS;
        if (isset($GLOBALS['CLI_OPTS']) && is_array($GLOBALS['CLI_OPTS'])) {
            $CLI_OPTS = $GLOBALS['CLI_OPTS'];
        } else {
            $CLI_OPTS = [];
        }
        return main();
    }

    global $CLI_OPTS;
    $CLI_OPTS = parseArgs($argv);
    if (isset($CLI_OPTS['help']) || isset($CLI_OPTS['h'])) { echo tr('_args_usage') . PHP_EOL; exit(0); }
    exit(main());
}
