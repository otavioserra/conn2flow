<?php

/**
 * Rotina de Atualização de Banco de Dados de Plugins
 *
 * Funções principais:
 * - Executa migrações Phinx
 * - Compara dados atuais das tabelas com arquivos JSON em gestor/db/data (inserindo/atualizando conforme necessário)
 * - Segrega e exporta registros órfãos conforme regras de unicidade
 * - Gera relatório final consolidado
 * - Multilíngue via __t() e logs via log_disco()
 *
 * Argumentos de linha de comando suportados:
 *
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
global $LOG_FILE_DB, $BASE_PATH_DB, $DB_DATA_DIR, $BACKUP_DIR_BASE, $GLOBALS, $PLUGIN_SLUG, $PLUGIN_BASE_DIR, $PLUGIN_MIGRATIONS_DIR;

// Valores base (serão ajustados em main() após ler argumentos)
$LOG_FILE_DB = 'atualizacoes-plugin-bd';
$BASE_PATH_DB = realpath(dirname(__FILE__) . '/../../') . DIRECTORY_SEPARATOR; // raiz do gestor
$DB_DATA_DIR = null; // será definido após validar --plugin
$BACKUP_DIR_BASE = $BASE_PATH_DB . 'backups/atualizacoes/'; // mantemos mesmo diretório de backups
$PLUGIN_SLUG = null;
$PLUGIN_BASE_DIR = null;
$PLUGIN_MIGRATIONS_DIR = null;

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

/** Logger unificado - usa logger externo se disponível, senão log_disco */
function log_unificado(string $msg, ?string $logFilename = null): void {
    global $LOG_FILE_DB, $GLOBALS;
    
    // Se há logger externo (passado pelo plugins-installer), usar ele
    if (isset($GLOBALS['EXTERNAL_LOGGER']) && is_array($GLOBALS['EXTERNAL_LOGGER'])) {
        // Prefixo para identificar logs internos da atualização de banco de dados
        $GLOBALS['EXTERNAL_LOGGER'][] = '[db-internal] ' . $msg;
        return;
    }
    
    // Fallback: usar log_disco normal
    $filename = $logFilename ?? $LOG_FILE_DB;
    log_disco($msg, $filename);
}

/** Conexão PDO reutilizável */
function db(): PDO {
    global $BASE_PATH_DB, $_BANCO, $CLI_OPTS, $_ENV, $GLOBALS;

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

    log_unificado('DEBUG_DB_CONNECT host=' . $host . ' db=' . $name . ' user=' . $user);
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Força charset para todas operações
    $pdo->exec("SET NAMES utf8mb4");
    log_unificado('DEBUG_DB_CONNECTED');
    return $pdo;
}

/**
 * Executa migrações usando phinx.
 */
function migracoes(): array {
    global $BASE_PATH_DB, $LOG_FILE_DB, $PLUGIN_MIGRATIONS_DIR;

    log_unificado(tr('_migrations_start'));

    // Caminho do autoload
    $autoload = $BASE_PATH_DB . 'vendor/autoload.php';
    if (!file_exists($autoload)) {
        $msg = 'Autoload do Composer não encontrado em ' . $autoload;
        log_unificado($msg);
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
            log_unificado($msg);
            throw new RuntimeException($msg);
        }
    }

    $rawConfig = require $phinxConfigFile; // retorna array
    if (!is_array($rawConfig)) {
        $msg = 'Configuração Phinx inválida (esperado array).';
        log_unificado($msg);
        throw new RuntimeException($msg);
    }

    // Ajusta path de migrações para incluir diretório do plugin (mínima adaptação)
    if ($PLUGIN_MIGRATIONS_DIR && is_dir($PLUGIN_MIGRATIONS_DIR)) {
        if (!isset($rawConfig['paths']['migrations'])) {
            $rawConfig['paths']['migrations'] = $PLUGIN_MIGRATIONS_DIR;
        } else {
            // Suporta string ou array; converte para array e adiciona diretório do plugin ao final
            if (is_string($rawConfig['paths']['migrations'])) {
                $rawConfig['paths']['migrations'] = [$rawConfig['paths']['migrations']];
            }
            if (is_array($rawConfig['paths']['migrations']) && !in_array($PLUGIN_MIGRATIONS_DIR, $rawConfig['paths']['migrations'], true)) {
                $rawConfig['paths']['migrations'][] = $PLUGIN_MIGRATIONS_DIR;
            }
        }
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
        log_unificado('[DEBUG] MIGRATING ENV=' . $env);

        // Executa as migrações pendentes
        $manager->migrate($env);

        $out = $buffer->fetch();
        if ($out !== '') {
            foreach (explode("\n", trim($out)) as $line) {
                if ($line==='') continue;
                log_unificado('[PHINX] ' . $line);
            }
        }
        log_unificado(tr('_migrations_done'));
        if (PHP_SAPI === 'cli') echo "[OK] Migrações concluídas via API interna!\n";
        return ['output' => $out];
    } catch (\Throwable $e) {
        $msg = 'Falha ao executar migrações via API: ' . $e->getMessage();
        log_unificado($msg);
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
function tableFromDataFile(string $file): string {
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
        'variaveis' => 'id_variaveis',
        // Permissões
        'usuarios_perfis_modulos' => 'id_usuarios_perfis_modulos',
        'usuarios_perfis_modulos_operacoes' => 'id_usuarios_perfis_modulos_operacoes',
        // Operações de módulos
        'modulos' => 'id_modulos',
        'modulos_operacoes' => 'id_modulos_operacoes',
        'modulos_grupos' => 'id_modulos_grupos',
        'usuarios_perfis' => 'id_usuarios_perfis',
        'prompts_ia' => 'id_prompts_ia',
        'alvos_ia' => 'id_alvos_ia',
        'modos_ia' => 'id_modos_ia',
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
    global $CLI_OPTS, $GLOBALS;
    if (empty($registros)) return ['inserted'=>0,'updated'=>0,'same'=>0];
    $debug = !empty($GLOBALS['CLI_OPTS']['debug']);

    // Descobrir colunas existentes na tabela para filtrar campos inexistentes vindos do JSON
    static $schemaCache = [];
    if (!isset($schemaCache[$tabela])) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC);
            $schemaCache[$tabela] = array_fill_keys(array_map(fn($c)=>$c['Field'], $cols), true);
        } catch (Throwable $e) {
            $schemaCache[$tabela] = null; // não conseguiu descobrir; não filtra
            if ($debug) log_unificado("WARN_SCHEMA tabela=$tabela msg=".encLog($e->getMessage()));
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

    // Tabelas que usam chaves naturais nos Data.json (sem PK explícita nos arquivos)
    $tabelasChaveNatural = [
        'paginas','layouts','componentes','variaveis',
        // Permissões de perfis
        'usuarios_perfis_modulos','usuarios_perfis_modulos_operacoes',
        // Novas tabelas com rastreamento de plugin
        'modulos','modulos_grupos','modulos_operacoes','usuarios_perfis','prompts_ia','alvos_ia','modos_ia'
    ];
    
    // Tabelas que devem apenas inserir registros, nunca atualizar (proteção de dados sensíveis)
    $tabelasInsertOnly = [
        'usuarios', // Protege dados de usuários (senhas, emails, etc.)
    ];
    
    $insertOnly = in_array($tabela, $tabelasInsertOnly, true);
    $pkDeclarada = pkPorTabela($tabela) ?? descobrirPK($tabela, $registros[0]);
    $primeiroTemPk = array_key_exists($pkDeclarada, $registros[0]);
    $usarChaveNatural = in_array($tabela, $tabelasChaveNatural, true) && !$primeiroTemPk;

    // Para tabelas não listadas, tentar detectar se deve usar chave natural baseada em campos únicos
    if (!$usarChaveNatural && !$primeiroTemPk) {
        // Verificar se tem campos que podem formar uma chave natural (id, name, etc.)
        $camposCandidatos = ['id', 'name', 'nome', 'codigo', 'slug'];
        $temCamposUnicos = false;
        foreach ($camposCandidatos as $campo) {
            if (array_key_exists($campo, $registros[0])) {
                $temCamposUnicos = true;
                break;
            }
        }
        if ($temCamposUnicos) {
            $usarChaveNatural = true;
            if ($debug) log_unificado("DETECTADO_MODO_NATURAL_AUTO tabela=$tabela (campos únicos detectados)");
        }
    }

    if ($debug) {
        log_unificado("SYNC_INI tabela=$tabela modo=".($usarChaveNatural?'natural':'pk')." qtdJson=".count($registros));
    }

    // Mapa de preservação para user_modified=1
    $preserveMap = [
        'paginas'      => ['html','css'],
        'layouts'      => ['html','css'],
        'componentes'  => ['html','css'],
        'variaveis'    => ['valor']
    ];

    $inserted=$updated=$same=0;

    // Função para chave natural (inclui plugin se coluna existir no schema)
    $hasPluginColumn = is_array($allowedCols) && isset($allowedCols['plugin']);
    $pluginSlugGlobal = $GLOBALS['PLUGIN_SLUG'] ?? '';
    $naturalKeyFn = function(string $tabela, array $row) use ($hasPluginColumn, $pluginSlugGlobal): ?string {
        $pluginPart = $hasPluginColumn ? ($row['plugin'] ?? $pluginSlugGlobal ?? '') : '';
        switch ($tabela) {
            case 'layouts':
            case 'componentes':
                $lang = $row['language'] ?? $row['linguagem_codigo'] ?? null; if (!isset($lang,$row['id'])) return null; return $pluginPart.'|'.strtolower($lang).'|'.$row['id'];
            case 'paginas':
                $lang = $row['language'] ?? $row['linguagem_codigo'] ?? null; if (!isset($lang,$row['id'])) return null; $mod = $row['modulo'] ?? ''; return $pluginPart.'|'.strtolower($lang).'|'.$mod.'|'.$row['id'];
            case 'variaveis':
                $lang = $row['language'] ?? $row['linguagem_codigo'] ?? null; if (!isset($lang,$row['id'])) return null; $mod = $row['modulo'] ?? ''; $grp = $row['grupo'] ?? ''; return $pluginPart.'|'.strtolower($lang).'|'.$mod.'|'.$grp.'|'.$row['id'];
            case 'usuarios_perfis_modulos':
                if (!isset($row['perfil'],$row['modulo'])) return null; return strtolower($row['perfil']).'|'.strtolower($row['modulo']);
            case 'usuarios_perfis_modulos_operacoes':
                if (!isset($row['perfil'],$row['operacao'])) return null; return strtolower($row['perfil']).'|'.strtolower($row['operacao']);
            case 'modulos':
            case 'modulos_grupos':
            case 'modulos_operacoes':
            case 'usuarios_perfis':
            case 'prompts_ia':
            case 'alvos_ia':
            case 'modos_ia':
                // id + language para tabelas de módulos multilíngue
                $lang = $row['language'] ?? $row['linguagem_codigo'] ?? null; if (!isset($lang,$row['id'])) return null; return $pluginPart.'|'.strtolower($lang).'|'.$row['id'];
            default:
                // Caso padrão: usar campo 'id' se existir
                if (isset($row['id'])) return $pluginPart.'|'.$row['id'];
                return null;
        }
    };

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
                if ($debug) log_unificado("ORPHAN_DB_ROW tabela=$tabela pk=$pkVal");
                if ($orphansMode !== 'ignore') { $orphans[] = $exist; }
                $same++; 
                continue; 
            }
            $row = $jsonByPk[$pkVal];
            $matched[$pkVal]=true; $diff=[]; $oldVals=[];

            // Se tabela é insert-only, pular atualização
            if ($insertOnly) {
                if ($debug) log_unificado("SKIP_UPDATE_INSERT_ONLY tabela=$tabela pk=$pkVal");
                $same++;
                continue;
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
                if ($c==='user_modified' && (int)$vOld===1 && (int)$vNew!==1) continue;
                if (normalizeValue($vNew)!==normalizeValue($vOld)) { $diff[$c]=$vNew; $oldVals[$c]=$vOld; }
            }
            if (isset($exist['user_modified']) && (int)$exist['user_modified']===1 && isset($preserveMap[$tabela])) {
                $changedPreserved=false; foreach ($preserveMap[$tabela] as $campo) {
                    if (array_key_exists($campo,$diff)) {
                        if ($tabela==='variaveis' && $campo==='valor') { if (isset($exist['value_updated'])||isset($row['value_updated'])) $diff['value_updated']=$diff[$campo]; }
                        else { $dest=$campo.'_updated'; if (isset($exist[$dest])||isset($row[$dest])) $diff[$dest]=$diff[$campo]; }
                        unset($diff[$campo]); $changedPreserved=true; }
                }
                if ($changedPreserved) { if (isset($exist['system_updated'])||isset($row['system_updated'])) $diff['system_updated']=1; if ($debug) log_unificado("USER_MODIFIED_PRESERVADO $tabela pk=$pkVal"); }
            }
            if ($diff) {
                if ($simulate) { log_unificado("SIMULATE_UPDATE $tabela pk=$pkVal campos=".implode(',',array_keys($diff))); }
                else { $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff))); $sql="UPDATE `$tabela` SET $sets WHERE `$pkDeclarada`=:pk"; $stmt=$pdo->prepare($sql); $params=$diff; $params['pk']=$pkVal; $stmt->execute($params);}            
                if ($logDiffs) { $pairs=[];$lim=0; foreach ($diff as $c=>$v){$pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(++$lim>=10){$pairs[]='...';break;}} log_unificado(tr('_diff_update_detail',['tabela'=>$tabela,'pk'=>$pkVal,'campos'=>implode(', ',$pairs)])); }
                $updated++; } else { $same++; }
        }
        foreach ($jsonByPk as $pkVal=>$row) {
            if (isset($matched[$pkVal])) continue;
            $normalizeLangRow($row);
            if (is_array($allowedCols)) { $row = array_intersect_key($row, $allowedCols); }
            $cols=array_keys($row); if(!$cols){ $same++; continue; }
            $placeholders=':'.implode(',:',$cols); $sql="INSERT INTO `$tabela`(".implode(',',array_map(fn($c)=>"`$c`",$cols)).") VALUES ($placeholders)";
            if ($simulate) { log_unificado("SIMULATE_INSERT $tabela pk=$pkVal"); $inserted++; continue; }
            $stmt=$pdo->prepare($sql); $params=[]; foreach ($row as $c=>$v){$params[':'.$c]=$v;} try { $stmt->execute($params); $inserted++; } catch (PDOException $e){ if (stripos($e->getMessage(),'Duplicate entry')!==false){ log_unificado("DUP_SKIP $tabela pk=$pkVal msg=".encLog($e->getMessage())); $same++; } else { log_unificado("ERROR_INSERT $tabela pk=$pkVal ex=".encLog($e->getMessage())); throw $e; } }
        }
        // Exportar órfãos se houver
        if ($orphans && $orphansMode === 'export') {
            $dir = $GLOBALS['DB_ORPHANS_DIR'] ?? ($GLOBALS['DB_ORPHANS_DIR'] = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'orphans' . DIRECTORY_SEPARATOR . 'bd' . DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $file = $dir . $tabela . '-orphans-' . date('Ymd-His') . '.json';
            @file_put_contents($file, json_encode($orphans, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado("ORPHANS_EXPORTED tabela=$tabela qtd=".count($orphans)." arquivo=\"$file\"");
        } elseif ($orphans && $orphansMode === 'log') {
            log_unificado("ORPHANS_DETECTED tabela=$tabela qtd=".count($orphans));
        }
        if ($debug) log_unificado("SYNC_FIM tabela=$tabela +$inserted ~$updated =$same orphans=".count($orphans)." modo=pk");
        return ['inserted'=>$inserted,'updated'=>$updated,'same'=>$same];
    }

    // ===== MODO CHAVE NATURAL =====
    // Indexar DB por chave natural
    $dbIndex = []; // chaveNatural => row
    $dbPkIndex = []; // chaveNatural => pk numérico
    // Índices secundários (fallback) para registros que perderam a linguagem (bug histórico) a fim de evitar duplicações
    $fallbackIndex = [
        'layouts' => [],          // id
        'componentes' => [],      // id
        'paginas' => [],          // modulo|id
        'variaveis' => []         // modulo|grupo|id
    ];
    foreach ($dbRows as $exist) {
        // Garante consistência do campo plugin nos registros em memória
        if ($hasPluginColumn && !isset($exist['plugin'])) { $exist['plugin'] = null; }
        $k = $naturalKeyFn($tabela, $exist); if ($k===null) continue; $dbIndex[$k] = $exist; // última ocorrência prevalece
        // Captura PK numérico se existir
        $pkNumeric = pkPorTabela($tabela) ?? null; if ($pkNumeric && isset($exist[$pkNumeric])) { $dbPkIndex[$k] = $exist[$pkNumeric]; }
        // Fallback: se não há language/linguagem_codigo válido, indexar por combinação sem linguagem
        $langVal = $exist['language'] ?? $exist['linguagem_codigo'] ?? null;
        if ($langVal===null || $langVal==='') {
            switch ($tabela) {
                case 'layouts':
                case 'componentes':
                    if (isset($exist['id'])) $fallbackIndex[$tabela][$exist['id']] = $exist; break;
                case 'paginas':
                    $mod = $exist['modulo'] ?? ''; if (isset($exist['id'])) $fallbackIndex[$tabela][$mod.'|'.$exist['id']] = $exist; break;
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

    foreach ($registros as $row) {
        $k = $naturalKeyFn($tabela, $row);
        if ($k===null) { if ($debug) log_unificado("SKIP_INVALID_NATURAL_KEY tabela=$tabela row_sem_chave"); continue; }
        // Normalizar linguagem (após gerar chave natural que aceita ambos os aliases)
        $normalizeLangRow($row);
        // Migração suave: se não encontrar chave com plugin definido, tentar localizar versão sem plugin (registros antigos pré-coluna) e atualizar
        if (!isset($dbIndex[$k]) && $hasPluginColumn) {
            // chave sem plugin começa com delimitador '|'
            $pos = strpos($k,'|');
            if ($pos !== false) {
                $kSemPlugin = substr($k, $pos); // remove segmento plugin
                if (isset($dbIndex[$kSemPlugin])) {
                    $dbIndex[$k] = $dbIndex[$kSemPlugin];
                    unset($dbIndex[$kSemPlugin]);
                }
            }
        }
        if (isset($dbIndex[$k])) {
            $exist = $dbIndex[$k];
            $diff=[]; $oldVals=[];

            // Se tabela é insert-only, pular atualização
            if ($insertOnly) {
                if ($debug) log_unificado("SKIP_UPDATE_INSERT_ONLY tabela=$tabela chave=$k");
                $same++;
                continue;
            }
            foreach ($row as $c=>$vNew) {
                // Ignorar campos de controle que não fazem parte do JSON natural
                if ($c === 'user_modified' && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (int)$vNew!==1) continue;
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
            if (isset($exist['user_modified']) && (int)$exist['user_modified']===1 && isset($preserveMap[$tabela])) {
                $changedPreserved=false; foreach ($preserveMap[$tabela] as $campo){ if(array_key_exists($campo,$diff)){ if($tabela==='variaveis' && $campo==='valor'){ if(isset($exist['value_updated'])||isset($row['value_updated'])) $diff['value_updated']=$diff[$campo]; } else { $dest=$campo.'_updated'; if(isset($exist[$dest])||isset($row[$dest])) $diff[$dest]=$diff[$campo]; } unset($diff[$campo]); $changedPreserved=true; }} if($changedPreserved){ if(isset($exist['system_updated'])||isset($row['system_updated'])) $diff['system_updated']=1; if ($debug) log_unificado("USER_MODIFIED_PRESERVADO_NAT tabela=$tabela chave=$k"); }}
            if ($diff) {
                if ($simulate) { log_unificado("SIMULATE_UPDATE_NAT tabela=$tabela chave=$k campos=".implode(',',array_keys($diff))); }
                else {
                    // Atualiza via chave natural (WHERE pelos campos da chave) ou via PK se disponível
                    $whereSql=''; $params=$diff;
                    if (isset($dbPkIndex[$k])) { // usar PK numérico mais eficiente
                        $pkNumeric = pkPorTabela($tabela);
                        $whereSql = "WHERE `$pkNumeric` = :__pk"; $params['__pk']=$dbPkIndex[$k];
                    } else {
                        // construir cláusula where pelos componentes da chave natural
                        // Detecta nome real da coluna de linguagem na tabela (language ou linguagem_codigo)
                        $colLang = array_key_exists('language', $exist) ? 'language'
                                  : (array_key_exists('linguagem_codigo', $exist) ? 'linguagem_codigo' : 'language');
                        $langVal = $exist[$colLang] ?? ($exist['language'] ?? ($exist['linguagem_codigo'] ?? null));
                        switch ($tabela) {
                            case 'layouts':
                            case 'componentes':
                                $whereSql = "WHERE `$colLang` = :__lang AND id = :__id";
                                $params['__lang']=$langVal; $params['__id']=$exist['id'];
                                break;
                            case 'paginas':
                                $whereSql = "WHERE `$colLang` = :__lang AND id = :__id AND modulo = :__mod";
                                $params['__lang']=$langVal; $params['__id']=$exist['id']; $params['__mod']=$exist['modulo']??'';
                                break;
                            case 'variaveis':
                                $whereSql = "WHERE `$colLang` = :__lang AND id = :__id AND modulo = :__mod AND (grupo <=> :__grp)";
                                $params['__lang']=$langVal; $params['__id']=$exist['id']; $params['__mod']=$exist['modulo']??''; $params['__grp']=$exist['grupo']??null;
                                break;
                            case 'modulos':
                            case 'modulos_grupos':
                            case 'modulos_operacoes':
                            case 'usuarios_perfis':
                            case 'prompts_ia':
                            case 'alvos_ia':
                            case 'modos_ia':
                                $whereSql = "WHERE `$colLang` = :__lang AND id = :__id";
                                $params['__lang']=$langVal; $params['__id']=$exist['id'];
                                break;
                        }
                    }
                    $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff)));
                    $sql="UPDATE `$tabela` SET $sets $whereSql"; $stmt=$pdo->prepare($sql); $stmt->execute($params);
                }
                if ($logDiffs) { $pairs=[];$lim=0; foreach ($diff as $c=>$v){$pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(++$lim>=10){$pairs[]='...';break;}} log_unificado(tr('_diff_update_detail',['tabela'=>$tabela,'pk'=>$k,'campos'=>implode(', ',$pairs)])); }
                $updated++;
            } else { $same++; }
        } else {
            // Tentar fallback de recuperação (linha existente sem linguagem)
            $fallbackKey = null; $existFallback = null;
            switch ($tabela) {
                case 'layouts': case 'componentes': $fallbackKey = $row['id'] ?? null; $existFallback = $fallbackKey!==null ? ($fallbackIndex[$tabela][$fallbackKey] ?? null) : null; break;
                case 'paginas': $fallbackKey = ($row['modulo'] ?? '').'|'.($row['id'] ?? ''); $existFallback = $fallbackIndex[$tabela][$fallbackKey] ?? null; break;
                case 'variaveis': $fallbackKey = ($row['modulo'] ?? '').'|'.(($row['grupo'] ?? '')).'|'.($row['id'] ?? ''); $existFallback = $fallbackIndex[$tabela][$fallbackKey] ?? null; break;
            }
            if ($existFallback) {
                // Atualiza registro existente preenchendo linguagem faltante (auto-correção de bug histórico)
                $exist = $existFallback; $diff=[]; $oldVals=[];

                // Se tabela é insert-only, pular atualização
                if ($insertOnly) {
                    if ($debug) log_unificado("SKIP_UPDATE_INSERT_ONLY_FALLBACK tabela=$tabela fallback=$fallbackKey");
                    $same++;
                    continue;
                }
                $normalizeLangRow($row);
                foreach ($row as $c=>$vNew) {
                    $vOld = $exist[$c] ?? null;
                    if ($c==='user_modified' && isset($exist['user_modified']) && (int)$exist['user_modified']===1 && (int)$vNew!==1) continue;
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
                if ($diff) {
                    if ($simulate) { log_unificado("SIMULATE_UPDATE_FALLBACK_NAT tabela=$tabela fallback=$fallbackKey campos=".implode(',',array_keys($diff))); }
                    else {
                        // WHERE por PK numérica se existir senão por combinação fallback + language IS NULL
                        $whereSql=''; $params=$diff;
                        if ($pkDeclarada && isset($exist[$pkDeclarada])) { $whereSql="WHERE `$pkDeclarada`=:__pk"; $params['__pk']=$exist[$pkDeclarada]; }
                        else {
                            switch ($tabela) {
                                case 'layouts': case 'componentes': $whereSql="WHERE id = :__id AND (`language` IS NULL OR `linguagem_codigo` IS NULL)"; $params['__id']=$exist['id']; break;
                                case 'paginas': $whereSql="WHERE id = :__id AND modulo = :__mod AND (`language` IS NULL OR `linguagem_codigo` IS NULL)"; $params['__id']=$exist['id']; $params['__mod']=$exist['modulo']??''; break;
                                case 'variaveis': $whereSql="WHERE id = :__id AND modulo = :__mod AND (grupo <=> :__grp) AND (`language` IS NULL OR `linguagem_codigo` IS NULL)"; $params['__id']=$exist['id']; $params['__mod']=$exist['modulo']??''; $params['__grp']=$exist['grupo']??null; break;
                            }
                        }
                        $sets=implode(',',array_map(fn($c)=>"`$c`=:$c",array_keys($diff)));
                        $sql="UPDATE `$tabela` SET $sets $whereSql"; $stmt=$pdo->prepare($sql); $stmt->execute($params);
                    }
                    if ($logDiffs) { $pairs=[]; foreach ($diff as $c=>$v){ $pairs[]=$c.' ['.encLog($oldVals[$c]??null).' => '.encLog($v).']'; if(count($pairs)>=10){$pairs[]='...';break;} } log_unificado("FALLBACK_UPDATE_NAT tabela=$tabela chave=$fallbackKey campos=".implode(', ',$pairs)); }
                    $updated++;
                } else { $same++; }
            } else {
                // Novo registro – inserir (sem PK numérica)
                $normalizeLangRow($row);
                if (is_array($allowedCols)) { $row = array_intersect_key($row, $allowedCols); }
                if ($hasPluginColumn && !isset($row['plugin'])) { $row['plugin'] = $pluginSlugGlobal; }
                $cols = array_keys($row);
                $colsFiltradas = array_filter($cols, fn($c)=>!preg_match('/^id_/', $c)); // evitar enviar id_paginas etc caso apareça
                $placeholders = ':'.implode(',:',$colsFiltradas);
                $sql = "INSERT INTO `$tabela` (".implode(',',array_map(fn($c)=>"`$c`",$colsFiltradas)).") VALUES ($placeholders)";
                if ($simulate) { log_unificado("SIMULATE_INSERT_NAT tabela=$tabela chave=$k"); $inserted++; }
                else {
                    $stmt=$pdo->prepare($sql); $params=[]; foreach ($colsFiltradas as $c){ $params[':'.$c]=$row[$c]; } try { $stmt->execute($params); $inserted++; }
                    catch (PDOException $e){ if (stripos($e->getMessage(),'Duplicate entry')!==false){ log_unificado("DUP_SKIP_NAT tabela=$tabela chave=$k msg=".encLog($e->getMessage())); $same++; } else { log_unificado("ERROR_INSERT_NAT tabela=$tabela chave=$k ex=".encLog($e->getMessage())); throw $e; } }
                }
            }
        }
    }

    // Detectar órfãos naturais (dbIndex - registros JSON)
    // Construir conjunto JSON para detecção de órfãos (post-process) – mais eficiente fora do laço
    $jsonKeys = [];
    foreach ($registros as $row) { $nk=$naturalKeyFn($tabela,$row); if($nk!==null) $jsonKeys[$nk]=true; }
    foreach ($dbIndex as $nk=>$exist) {
        if (!isset($jsonKeys[$nk])) {
            if ($debug) log_unificado("ORPHAN_DB_ROW_NAT tabela=$tabela chave=$nk");
            if ($orphansMode !== 'ignore') { $orphans[] = $exist; }
        }
    }
    // Exportar órfãos
    if ($orphans && $orphansMode === 'export') {
        $dir = $GLOBALS['DB_ORPHANS_DIR'] ?? ($GLOBALS['DB_ORPHANS_DIR'] = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'orphans' . DIRECTORY_SEPARATOR . 'bd' . DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . $tabela . '-orphans-' . date('Ymd-His') . '.json';
        @file_put_contents($file, json_encode($orphans, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    log_unificado("ORPHANS_EXPORTED tabela=$tabela qtd=".count($orphans)." arquivo=\"$file\"");
    } elseif ($orphans && $orphansMode === 'log') {
        log_unificado("ORPHANS_DETECTED tabela=$tabela qtd=".count($orphans));
    }

    if ($debug) log_unificado("SYNC_FIM tabela=$tabela +$inserted ~$updated =$same orphans=".count($orphans)." modo=natural");
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
    global $DB_DATA_DIR, $LOG_FILE_DB, $CLI_OPTS, $CHECKSUM_CHANGED_TABLES;
    log_unificado(tr('_compare_start'));
    $arquivos = glob($DB_DATA_DIR . '*Data.json');
    log_unificado('DEBUG_COMPARACAO_DADOS arquivos_count=' . count($arquivos) . ' dataDir=' . $DB_DATA_DIR);
    foreach ($arquivos as $f) {
        log_unificado('DEBUG_ARQUIVO ' . basename($f));
    }
    // Filtro --tables opcional
    if (!empty($CLI_OPTS['tables'])) {
        $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
        $arquivos = array_values(array_filter($arquivos, function($f) use ($filter){
            $t = tableFromDataFile($f); return in_array(strtolower($t), $filter, true);
        }));
        log_unificado(tr('_filter_tables',[ 'lista'=>implode(',', array_map(fn($f)=>tableFromDataFile($f), $arquivos))]));
    }
    $pdo = db();
    $resumo = [];
    foreach ($arquivos as $file) {
        $tabela = tableFromDataFile($file);
        if (is_array($CHECKSUM_CHANGED_TABLES) && !in_array($tabela, $CHECKSUM_CHANGED_TABLES, true)) {
            log_unificado("SKIP_NO_CHECKSUM_CHANGE tabela=$tabela");
            continue;
        }
        $registros = loadDataFile($file);
        // Verificar existência da tabela no banco; se não existir (caso de JSON extra do plugin), pular com log.
        try {
            $stmtExists = $pdo->prepare("SHOW TABLES LIKE :t");
            $stmtExists->execute([':t'=>$tabela]);
            if (!$stmtExists->fetch(PDO::FETCH_NUM)) {
                log_unificado("SKIP_TABLE_NOT_FOUND tabela=$tabela");
                continue;
            }
        } catch (Throwable $e) {
            log_unificado("SKIP_TABLE_CHECK_ERROR tabela=$tabela msg=".encLog($e->getMessage()));
            continue;
        }
        // Ajustes específicos pré-sincronização + injeção de plugin
        $pluginSlug = $GLOBALS['PLUGIN_SLUG'] ?? '';
        if ($tabela === 'paginas' && $registros) {
            foreach ($registros as &$r) {
                if (isset($r['type']) && !isset($r['tipo'])) { $r['tipo']=$r['type']; unset($r['type']); }
                if (isset($r['tipo'])) { $map=['page'=>'pagina','system'=>'sistema']; $orig=strtolower((string)$r['tipo']); if(isset($map[$orig])) $r['tipo']=$map[$orig]; }
                $r['plugin'] = $pluginSlug; // força inclusão
                $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null];
            } unset($r);
        } elseif (in_array($tabela,['layouts','componentes'],true) && $registros) {
            foreach ($registros as &$r) {
                $r['plugin'] = $pluginSlug;
                $r += ['system_updated'=>0,'html_updated'=>null,'css_updated'=>null];
            } unset($r);
        } elseif ($tabela==='variaveis' && $registros) {
            foreach ($registros as &$r) {
                if (!isset($r['linguagem_codigo']) && isset($r['language'])) {
                    $r['linguagem_codigo'] = $r['language'];
                }
                if (isset($r['linguagem_codigo']) && !isset($r['language'])) {
                    $r['language'] = $r['linguagem_codigo'];
                }
                $r['plugin'] = $pluginSlug;
                $r += ['user_modified'=>0,'system_updated'=>0,'value_updated'=>null];
            } unset($r);
        } elseif (in_array($tabela, ['modulos','modulos_grupos','modulos_operacoes','usuarios_perfis','prompts_ia','alvos_ia','modos_ia'], true) && $registros) {
            foreach ($registros as &$r) {
                $r['plugin'] = $pluginSlug;
            } unset($r);
        } else {
            // Injeção genérica: se a tabela possuir coluna plugin e registros não possuem explicitamente, preencher.
            if ($registros) {
                try {
                    $pdoTmp = $pdo ?? db();
                    $hasPluginCol = false;
                    $stmtCol = $pdoTmp->prepare("SHOW COLUMNS FROM `{$tabela}` LIKE 'plugin'");
                    if ($stmtCol->execute() && $stmtCol->fetch(PDO::FETCH_ASSOC)) { $hasPluginCol = true; }
                    if ($hasPluginCol) {
                        foreach ($registros as &$r) {
                            if (!array_key_exists('plugin', $r) || $r['plugin'] === null || $r['plugin'] === '') {
                                $r['plugin'] = $pluginSlug;
                            }
                        } unset($r);
                    }
                } catch (Throwable $e) {
                    if (!empty($CLI_OPTS['debug'])) log_unificado('PLUGIN_INJECT_GENERIC_FAIL tabela='.$tabela.' msg='.encLog($e->getMessage()));
                }
            }
        }
        if (!$registros) { log_unificado(tr('_compare_no_changes',['tabela'=>$tabela])); continue; }
        log_unificado(tr('_executing_table',['tabela'=>$tabela]));
        $resultado = sincronizarTabela($pdo, $tabela, $registros, !empty($CLI_OPTS['log-diff']), !empty($CLI_OPTS['dry-run']));
        log_unificado(tr('_compare_summary', ['tabela'=>$tabela,'ins'=>$resultado['inserted'],'upd'=>$resultado['updated'],'same'=>$resultado['same']]));
        $resumo[$tabela]=$resultado;
    }
    return $resumo;
}

/** Executa backup JSON de tabelas antes das alterações. */
function executarBackup(PDO $pdo, array $tabelas, string $dirBase): string {
    global $LOG_FILE_DB;
    $timestampDir = $dirBase . date('Ymd-His') . '/';
    if (!is_dir($timestampDir) && !@mkdir($timestampDir, 0775, true)) {
        log_unificado(tr('_backup_error',[ 'msg'=>'mkdir fail '.$timestampDir ]));
        return '';
    }
    log_unificado(tr('_backup_start',[ 'dir'=>$timestampDir ]));
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents($timestampDir . $t . '.json', json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado(tr('_backup_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]));
        } catch (Throwable $e) {
            log_unificado(tr('_backup_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]));
        }
    }
    log_unificado(tr('_backup_complete'));
    return $timestampDir;
}

/** Exporta dados do banco para arquivos *Data.json (modo reverso) */
function reverseExport(PDO $pdo, array $tabelas, string $dataDir): void {
    global $LOG_FILE_DB;
    log_unificado(tr('_reverse_start'));
    foreach ($tabelas as $t) {
        try {
            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) { log_unificado(tr('_reverse_empty',[ 'tabela'=>$t ])); continue; }
            $fileName = dataFileNameFromTable($t);
            $dest = $dataDir . $fileName;
            // backup antigo se existir
            if (file_exists($dest)) {
                @rename($dest, $dest . '.bak.' . date('Ymd-His'));
            }
            file_put_contents($dest, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            log_unificado(tr('_reverse_table_done',[ 'tabela'=>$t, 'qtd'=>count($rows) ]));
        } catch (Throwable $e) {
            log_unificado(tr('_reverse_table_error',[ 'tabela'=>$t, 'msg'=>$e->getMessage() ]));
        }
    }
    log_unificado(tr('_reverse_complete'));
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
    $msg .= "Σ TOTAL => +$totalIns ~${totalUpd} =${totalSame}" . PHP_EOL;
    log_unificado($msg);
}

function main() {
    global $LOG_FILE_DB, $CLI_OPTS, $BACKUP_DIR_BASE, $DB_DATA_DIR, $CHECKSUM_CHANGED_TABLES, $PLUGIN_SLUG, $PLUGIN_BASE_DIR, $PLUGIN_MIGRATIONS_DIR, $BASE_PATH_DB, $GLOBALS;

    // Recalcula BASE_PATH_DB de forma segura (absoluto) independente de escopo global anterior
    $calcBase = realpath(__DIR__ . '/../../');
    if ($calcBase && is_dir($calcBase)) {
        $BASE_PATH_DB = rtrim($calcBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    log_unificado('DEBUG_PLUGIN_MAIN_INI slug=' . ($PLUGIN_SLUG ?? 'null') . ' baseDir=' . ($PLUGIN_BASE_DIR ?? 'null') . ' dataDir=' . ($DB_DATA_DIR ?? 'null'));

    try {
        log_unificado(tr('_process_start'));
        if (!empty($CLI_OPTS['reverse'])) {
            // Modo reverso exporta dados e encerra
            $pdo = db();
            $arquivos = glob($DB_DATA_DIR . '*Data.json');
            $tabelas = [];
            foreach ($arquivos as $f) { $tabelas[] = tableFromDataFile($f); }
            if (!empty($CLI_OPTS['tables'])) {
                $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
                $tabelas = array_values(array_filter($tabelas, fn($t)=>in_array(strtolower($t), $filter, true)));
            }
            reverseExport($pdo, $tabelas, $DB_DATA_DIR);
            log_unificado(tr('_process_end_success'));
            return 0; // Sucesso no modo reverso
        }
        // ========= Adaptação Plugin =========
        $PLUGIN_SLUG = $CLI_OPTS['plugin'] ?? null;
        if (!$PLUGIN_SLUG) {
            log_unificado('ERRO_PLUGIN_ARG: use --plugin=<slug_do_plugin>');
            throw new RuntimeException('Parâmetro obrigatório --plugin ausente.');
        }
        if (strpos($PLUGIN_SLUG, '/') !== false || strpos($PLUGIN_SLUG, '\\') !== false) {
            log_unificado('ERRO_PLUGIN_SLUG_INVALIDO slug='.$PLUGIN_SLUG);
            throw new RuntimeException('Slug inválido: não use barras.');
        }
        // Diretório padrão esperado: gestor/plugins/<slug>/
    $candidate = $BASE_PATH_DB . 'plugins' . DIRECTORY_SEPARATOR . $PLUGIN_SLUG . DIRECTORY_SEPARATOR;
    log_unificado('DEBUG_PLUGIN_CANDIDATE slug='.$PLUGIN_SLUG.' base='.$BASE_PATH_DB.' cand='.$candidate);
        if (!is_dir($candidate)) {
            // fallback: tentar raiz do repositório (ex: plugin-skeleton fora de gestor)
            $repoRootAlt = realpath($BASE_PATH_DB . '..') . DIRECTORY_SEPARATOR . $PLUGIN_SLUG . DIRECTORY_SEPARATOR;
            if (is_dir($repoRootAlt)) {
                $candidate = $repoRootAlt;
            }
        }
        if (!is_dir($candidate)) {
            log_unificado('ERRO_PLUGIN_DIR_NAO_ENCONTRADO slug='.$PLUGIN_SLUG.' dirTentado='.$candidate);
            throw new RuntimeException('Diretório do plugin não encontrado: '.$PLUGIN_SLUG);
        }
        $PLUGIN_BASE_DIR = $candidate;
        // Data dir do plugin (db/data)
        $dataDirCandidate = $PLUGIN_BASE_DIR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        if (!is_dir($dataDirCandidate)) {
            log_unificado('ERRO_PLUGIN_DATA_DIR_NAO_ENCONTRADO slug='.$PLUGIN_SLUG.' dataDir='.$dataDirCandidate);
            throw new RuntimeException('Diretório de dados do plugin não encontrado (db/data).');
        }
        $DB_DATA_DIR = $dataDirCandidate;
        // Orphans destino plugin (isolado) - configurar variável global usada em sincronizarTabela
        $GLOBALS['DB_ORPHANS_DIR'] = $PLUGIN_BASE_DIR . 'db' . DIRECTORY_SEPARATOR . 'orphans' . DIRECTORY_SEPARATOR . 'bd' . DIRECTORY_SEPARATOR;
        if (!is_dir($GLOBALS['DB_ORPHANS_DIR'])) { @mkdir($GLOBALS['DB_ORPHANS_DIR'], 0775, true); }
        // Diretório de migrações do plugin (opcional)
        $migDirCandidate = $PLUGIN_BASE_DIR . 'db' . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($migDirCandidate)) { $PLUGIN_MIGRATIONS_DIR = $migDirCandidate; }
        // Ajustar nome de arquivo de log para incluir slug (facilita rastreio)
        $LOG_FILE_DB = 'atualizacoes-plugin-bd-' . $PLUGIN_SLUG;
        if (!empty($CLI_OPTS['dry-run'])) log_unificado(tr('_dry_run_mode'));
        if (empty($CLI_OPTS['skip-migrate'])) { migracoes(); } else { log_unificado(tr('_skip_migrations')); }

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
            log_unificado('WARN lendo manager_updates: '.encLog($e->getMessage()));
        }

        if ($previousMap && !$forceAll) {
            $changed = [];
            foreach ($checksums as $file=>$sum) {
                if (!isset($previousMap[$file]) || $previousMap[$file] !== $sum) { $changed[] = tableFromDataFile($file); }
            }
            if ($changed) {
                $CHECKSUM_CHANGED_TABLES = $changed;
                log_unificado('CHECKSUM_CHANGED_TABLES='.implode(',', $changed));
            } else {
                $CHECKSUM_CHANGED_TABLES = []; // nenhuma mudou
                log_unificado('CHECKSUM_NENHUMA_TABELA_MUDOU');
            }
        } else {
            if ($forceAll) { log_unificado('FORCE_ALL_TABELAS'); }
            elseif (!$previousMap) { log_unificado('CHECKSUM_PRIMEIRA_ATUALIZACAO'); }
        }
        // Backup opcional
        if (!empty($CLI_OPTS['backup'])) {
            $arquivos = glob($DB_DATA_DIR . '*Data.json');
            $tabelas = [];
            foreach ($arquivos as $f) { $tabelas[] = tableFromDataFile($f); }
            if (!empty($CLI_OPTS['tables'])) {
                $filter = array_map('strtolower', array_map('trim', explode(',', $CLI_OPTS['tables'])));
                $tabelas = array_values(array_filter($tabelas, fn($t)=>in_array(strtolower($t), $filter, true)));
            }
            $backupPath = executarBackup($pdo, $tabelas, $BACKUP_DIR_BASE);
        } else { $backupPath = null; }
        if (is_array($CHECKSUM_CHANGED_TABLES) && count($CHECKSUM_CHANGED_TABLES) === 0) {
            log_unificado('SEM_MUDANCAS_DADOS -> pulando sincronizacao');
            $resumo = [];
        } else {
            log_unificado('INICIANDO_SINCRONIZACAO_DADOS');
            $resumo = comparacaoDados();
        }
        // Registrar manager_updates
        try {
            $versao = $GLOBALS['_GESTOR']['versao'] ?? null;
            $ins = $pdo->prepare('INSERT INTO manager_updates (db_checksum, backup_path, version, date) VALUES (:c,:b,:v,NOW())');
            $ins->execute([':c'=>$checksumsJson, ':b'=>$backupPath, ':v'=>$versao]);
            log_unificado('MANAGER_UPDATES_REGISTRADO id='.$pdo->lastInsertId());
        } catch (Throwable $e) {
            log_unificado('WARN registrar manager_updates: '.encLog($e->getMessage()));
        }
        relatorioFinal($resumo);
        gestor_sessao_del_all(); // limpa cache de sessão do gestor (se houver)
        log_unificado(tr('_process_end_success'));
        return 0; // Sucesso
    } catch (Throwable $e) {
        log_unificado(tr('_process_error',['msg'=>$e->getMessage()]));
        if (PHP_SAPI === 'cli') echo 'Erro: ' . $e->getMessage() . PHP_EOL;
        return 1; // Erro
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

// Permite execução via require/include (web) usando $GLOBALS['CLI_OPTS'] (opcional), ou via CLI.
// Verifica se este arquivo é o ponto de entrada principal (executado diretamente)
$isMainScript = (realpath($_SERVER['SCRIPT_FILENAME'] ?? __FILE__) === __FILE__);

if (!$isMainScript) {
    // Executado via require/include - usar $GLOBALS['CLI_OPTS']
    global $CLI_OPTS, $GLOBALS;
    if (isset($GLOBALS['CLI_OPTS']) && is_array($GLOBALS['CLI_OPTS'])) {
        $CLI_OPTS = $GLOBALS['CLI_OPTS'];
    } else {
        $CLI_OPTS = [];
    }
    // Quando incluído, retorna o código de saída da função main()
    return main();
} else {
    // Executado diretamente via CLI
    global $CLI_OPTS; $CLI_OPTS = parseArgs($argv);
    if (isset($CLI_OPTS['help']) || isset($CLI_OPTS['h'])) { echo tr('_args_usage') . PHP_EOL; exit(0); }
    exit(main());
}