<?php
/**
 * Atualização de Dados de Recursos (Layouts, Páginas, Componentes e Variáveis) - Versão 2.1
 * ------------------------------------------------------------------------------------------
 * Esta versão remove COMPLETAMENTE o controle manual de identificadores numéricos (id_layouts,
 * id_paginas, id_componentes, id_variaveis). O banco (auto increment) será o único responsável
 * pela geração das PKs. Os Data.json agora contêm apenas dados sem esses IDs numéricos.
 *
 * Regras de unicidade (planejamento v2):
 *  - Layouts / Componentes: id único por language.
 *  - Páginas:
 *      * id único por (language, modulo) — pode repetir entre módulos diferentes.
 *      * caminho (path/caminho) único por (language) independente de módulo.
 *  - Variáveis:
 *      * id único por (language, modulo) salvo quando existir mais de um registro com groups
 *        distintos e TODOS tiverem group definido — nesse caso todos são válidos.
 *      * Qualquer duplicidade que viole regra vira órfão.
 *
 * Órfãos: Recursos inválidos ou duplicados são gravados em gestor/db/orphans/<Tipo>Data.json
 * para futura análise. Os válidos vão para gestor/db/data.
 *
 * Versionamento: Mantemos campo 'versao' incremental SOMENTE quando checksum (html/css) mudar.
 * Campo 'file_version' é a versão de origem (sources). Checksum armazenado como string JSON
 * (compatibilidade histórica).
 *
 * layout_id: Em páginas vem diretamente do campo 'layout' da origem (sem tradução numérica).
 *
 * Estrutura Funções:
 *  - carregarMapeamentoGlobal
 *  - carregarDadosExistentes (para reusar versao quando checksum igual)
 *  - coletarRecursos (aplica unicidade, gera órfãos, calcula versao)
 *  - atualizarDados (grava data + órfãos)
 *  - reporteFinal
 *  - main
 *
 * @version 2.1.0
 * @date 2025-11-05
 */

// ========================= CONFIGURAÇÃO BÁSICA =========================

declare(strict_types=1);

// Definir globais
global $SYSTEM_PATH, $BASE_PATH, $GESTOR_DIR, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $LOG_DIR, $LOG_FILE, $LOG_DISCO;

$SYSTEM_PATH = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR; // raiz do repositório

require_once $SYSTEM_PATH . 'gestor/bibliotecas/lang.php';
// Biblioteca de logs (pode ser adaptada futuramente). Se não existir função, definimos fallback simples.
// Carregar biblioteca de log original
$LOG_LIB_PATH = $SYSTEM_PATH . 'gestor/bibliotecas/log.php';
if (is_file($LOG_LIB_PATH)) {
    require_once $LOG_LIB_PATH;
} else {
    fwrite(STDERR, "AVISO: biblioteca de log nao encontrada em {$LOG_LIB_PATH}\n");
}
// Usaremos diretamente log_disco_local() da biblioteca

// Parsing de argumentos CLI
$GLOBALS['CLI_ARGS'] = [];
if (PHP_SAPI === 'cli') {
    foreach (($argv ?? []) as $a) {
        if (preg_match('/^--([^=]+)=(.+)$/',$a,$m)) { $GLOBALS['CLI_ARGS'][$m[1]] = $m[2]; }
        elseif (substr($a,0,2)==='--') { $GLOBALS['CLI_ARGS'][substr($a,2)] = true; }
    }
}

// Verificar se é execução para projeto específico
$projectPath = $GLOBALS['CLI_ARGS']['project-path'] ?? null;
$isProjectMode = false;
if ($projectPath) {
    $BASE_PATH = realpath($projectPath) . DIRECTORY_SEPARATOR;
    // Para projetos: diretórios diretamente na raiz do projeto
    $GESTOR_DIR      = $BASE_PATH;
    $RESOURCES_DIR   = $BASE_PATH . 'resources' . DIRECTORY_SEPARATOR;
    $MODULES_DIR     = $BASE_PATH . 'modulos' . DIRECTORY_SEPARATOR;
    $DB_DATA_DIR     = $BASE_PATH . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    $LOG_DIR         = $BASE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR;
    $isProjectMode = true;
} else {
    $BASE_PATH = $SYSTEM_PATH;
    // Para sistema: diretórios dentro de gestor/
    $GESTOR_DIR      = $BASE_PATH . 'gestor' . DIRECTORY_SEPARATOR;
    $RESOURCES_DIR   = $GESTOR_DIR . 'resources' . DIRECTORY_SEPARATOR;
    $MODULES_DIR     = $GESTOR_DIR . 'modulos' . DIRECTORY_SEPARATOR;
    $DB_DATA_DIR     = $GESTOR_DIR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    $LOG_DIR         = $GESTOR_DIR . 'logs' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR;
}
$LOG_FILE        = 'atualizacao-dados-recursos';

// Framework CSS default
const DEFAULT_FRAMEWORK_CSS = 'fomantic-ui';

// Garantir existência dos diretórios
ensureDir($LOG_DIR, $LOG_FILE);
ensureDir($DB_DATA_DIR, $LOG_FILE);

// Ajustar defaults para biblioteca de log tradicional se necessária
global $_GESTOR;
if (!isset($_GESTOR)) { $_GESTOR = []; }
if (!array_key_exists('debug', $_GESTOR)) { $_GESTOR['debug'] = false; }
if (!array_key_exists('logs-path', $_GESTOR)) { $_GESTOR['logs-path'] = $LOG_DIR; }

// Definir linguagem padrão (pode ser parametrizado futuramente)
set_lang('pt-br');

// Função de log local que respeita configuração de log-disco
$LOG_DISCO = false;
$LOG_DISCO = isset($GLOBALS['CLI_ARGS']['log-disco']) ? true : $LOG_DISCO;

function log_disco_local(string $msg, string $file) {
    global $LOG_DISCO;

    if ($LOG_DISCO) {
        log_disco($msg, $file);
    }
}

/**
 * Garante a existência de um diretório, validando o resultado e registrando falhas.
 * Substitui o uso de @mkdir com silenciamento cego.
 */
function ensureDir(string $dir, string $logFile): bool {
    if (is_dir($dir)) return true;
    if (mkdir($dir, 0775, true) || is_dir($dir)) return true;
    log_disco_local('MKDIR_FALHA ' . $dir, $logFile);
    return false;
}

// Log do modo de execução
if ($isProjectMode) {
    log_disco_local("MODO PROJETO: Processando projeto em $BASE_PATH", $LOG_FILE);
} else {
    log_disco_local("MODO SISTEMA: Processando sistema em $BASE_PATH", $LOG_FILE);
}

// ========================= UTILIDADES GENÉRICAS =========================

/**
 * Retorna framework_css do item ou fallback padrão.
 */
function getFrameworkCss(?array $src): string {
    if (!$src) return DEFAULT_FRAMEWORK_CSS;
    $v = $src['framework_css'] ?? null;
    if (is_string($v) && $v !== '') return $v; // aceitamos valor informado
    return DEFAULT_FRAMEWORK_CSS;
}

/**
 * Remove BOM UTF-8 do início do conteúdo, quando presente.
 */
function stripUtf8Bom(?string $content): ?string {
    if ($content === null || $content === '') return $content;
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        return substr($content, 3);
    }
    return $content;
}

/**
 * Lê JSON retornando array associativo ou null.
 */
function jsonRead(string $path): ?array {
    if (!file_exists($path)) return null;
    $c = stripUtf8Bom(file_get_contents($path));
    $d = json_decode($c, true);
    return is_array($d) ? $d : null;
}

/**
 * Escreve JSON formatado.
 */
function jsonWrite(string $path, array $data): bool {
    global $LOG_FILE;
    $dir = dirname($path);
    ensureDir($dir, $LOG_FILE);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Retorna conteúdo do arquivo se existir.
 */
function readFileIfExists(string $path): ?string {
    return file_exists($path) ? stripUtf8Bom(file_get_contents($path)) : null;
}

/**
 * Calcula checksums individuais e combinado para HTML/CSS.
 */
function buildChecksum(?string $html, ?string $css): array {
    $h = ($html === null || $html === '') ? '' : md5($html);
    $c = ($css === null || $css === '') ? '' : md5($css);
    $combined = ($h === '' && $c === '') ? '' : md5(($html ?? '') . ($css ?? ''));
    return ['html' => $h, 'css' => $c, 'combined' => $combined];
}

/**
 * Calcula checksums individual do Markdown.
 */
function buildChecksumMD(?string $md): array {
    $m = ($md === null || $md === '') ? '' : md5($md);
    return ['md' => $m];
}

/**
 * Atualiza arquivos de origem (layouts.json, pages.json, components.json) incrementando
 * version e checksum quando HTML/CSS associados mudaram. Isso garante que:
 *  - O campo 'version' de origem reflita mudanças de conteúdo.
 *  - Os checksums em origem sirvam como histórico incremental.
 * Regras:
 *  - Se nenhum HTML/CSS existir (ambos vazios ou ausentes) mantém checksum vazio.
 *  - Incremento segue incrementVersionStr.
 */
function atualizarArquivosOrigem(array $map): void {
    global $RESOURCES_DIR, $LOG_FILE;
    $languages = array_keys($map['languages']);
    foreach ($languages as $lang) {
        $langInfo = $map['languages'][$lang] ?? null; if(!$langInfo||!isset($langInfo['data'])) continue;
        $dataFiles = $langInfo['data'];
        foreach ([ 'layouts'=>'layouts', 'components'=>'components', 'pages'=>'pages', 'templates'=>'templates' ] as $tipoKey=>$dirName) {
            if (empty($dataFiles[$tipoKey])) continue; // sem arquivo
            $jsonPath = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles[$tipoKey];
            $lista = jsonRead($jsonPath); if(!is_array($lista)) continue;
            $changed=false;
            foreach ($lista as &$item) {
                $id = $item['id'] ?? null; if(!$id) continue;
                // Caminhos de origem (para pages/components/layouts utilizamos mesmo padrão do resources)
                $paths = resourcePaths($RESOURCES_DIR,$lang,$tipoKey==='pages'?'pages':$tipoKey,$id,true);
                $html = readFileIfExists($paths['html']);
                $css  = readFileIfExists($paths['css']);
                $newChecksum = buildChecksum($html,$css);
                $oldChecksum = $item['checksum'] ?? ['html'=>'','css'=>'','combined'=>''];
                if (!is_array($oldChecksum)) { $dec=json_decode((string)$oldChecksum,true); if(is_array($dec)) $oldChecksum=$dec; }
                if (!checksumsEqual($oldChecksum,$newChecksum)) {
                    $oldVersion = $item['version'] ?? null;
                    $item['version'] = incrementVersionStr($oldVersion);
                    $item['checksum'] = $newChecksum; // mantém formato objeto
                    $changed=true;
                    log_disco_local("ORIGIN_UPDATE $tipoKey id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
                }
            }
            unset($item);
            if ($changed) {
                jsonWrite($jsonPath,$lista);
                log_disco_local("ORIGIN_FILE_SAVED $jsonPath", $LOG_FILE);
            }
        }
    }

    // ====== Atualização para MÓDULOS ======
    global $MODULES_DIR;
    if (is_dir($MODULES_DIR)) {
        $mods = glob($MODULES_DIR.'*', GLOB_ONLYDIR) ?: [];
        foreach ($mods as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath.DIRECTORY_SEPARATOR.$modId.'.json';
            $data = jsonRead($jsonFile); if(!$data || empty($data['resources'])) continue;
            $changedModule = false;
            foreach ($languages as $lang) {
                if (empty($data['resources'][$lang])) continue; // idioma não presente
                foreach (['layouts','components','pages','templates'] as $tipo) {
                    if (empty($data['resources'][$lang][$tipo]) || !is_array($data['resources'][$lang][$tipo])) continue;
                    foreach ($data['resources'][$lang][$tipo] as &$item) {
                        $id = $item['id'] ?? null; if(!$id) continue;
                        $paths = resourcePaths($modPath,$lang,$tipo,$id); // base modulo
                        $html = readFileIfExists($paths['html']);
                        $css  = readFileIfExists($paths['css']);
                        $newChecksum = buildChecksum($html,$css);
                        $oldChecksum = $item['checksum'] ?? ['html'=>'','css'=>'','combined'=>''];
                        if (!is_array($oldChecksum)) { $dec=json_decode((string)$oldChecksum,true); if(is_array($dec)) $oldChecksum=$dec; }
                        if (!checksumsEqual($oldChecksum,$newChecksum)) {
                            $oldVersion = $item['version'] ?? null;
                            $item['version'] = incrementVersionStr($oldVersion);
                            $item['checksum'] = $newChecksum;
                            $changedModule = true;
                            log_disco_local("ORIGIN_UPDATE_MODULE modulo=$modId tipo=$tipo id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
                        }
                    }
                    unset($item);
                }
                foreach (['prompts'] as $tipo) {
                    if (empty($data['resources'][$lang][$tipo]) || !is_array($data['resources'][$lang][$tipo])) continue;
                    foreach ($data['resources'][$lang][$tipo] as &$item) {
                        $id = $item['id'] ?? null; if(!$id) continue;
                        $paths = resourcePaths($modPath,$lang,$tipo,$id); // base modulo
                        $md = readFileIfExists($paths['md']);
                        $newChecksum = buildChecksumMD($md);
                        $oldChecksum = $item['checksum'] ?? ['md'=>''];
                        if (!is_array($oldChecksum)) { $dec=json_decode((string)$oldChecksum,true); if(is_array($dec)) $oldChecksum=$dec; }
                        if (!checksumsEqualMD($oldChecksum,$newChecksum)) {
                            $oldVersion = $item['version'] ?? null;
                            $item['version'] = incrementVersionStr($oldVersion);
                            $item['checksum'] = $newChecksum;
                            $changedModule = true;
                            log_disco_local("ORIGIN_UPDATE_MODULE modulo=$modId tipo=$tipo id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
                        }
                    }
                    unset($item);
                }
            }
            if ($changedModule) {
                jsonWrite($jsonFile,$data);
                log_disco_local("ORIGIN_FILE_SAVED_MODULE $jsonFile", $LOG_FILE);
            }
        }
    }
}

/**
 * Compara checksums.
 */
function checksumsEqual(array $a, array $b): bool {
    return ($a['html'] ?? null) === ($b['html'] ?? null)
        && ($a['css'] ?? null) === ($b['css'] ?? null)
        && ($a['combined'] ?? null) === ($b['combined'] ?? null);
}

/**
 * Compara checksums MD.
 */
function checksumsEqualMD(array $a, array $b): bool {
    return ($a['md'] ?? null) === ($b['md'] ?? null);
}

/**
 * Incrementa versão com formato X.Y assumindo Y inteiro.
 */
function incrementVersionStr(?string $v): string {
    if (!$v) return '1.0';
    $parts = explode('.', $v);
    if (count($parts) === 2 && ctype_digit($parts[1])) {
        $parts[1] = (string)((int)$parts[1] + 1);
        return implode('.', $parts);
    }
    return '1.0';
}

/**
 * Marca atualização de checksum/versão no item de origem.
 */
function applyChecksumAndVersionUpdate(array &$item, array $newChecksum, array &$updates, string $originKey): void {
    $oldChecksum = $item['checksum'] ?? ['html' => '', 'css' => '', 'combined' => ''];
    if (!is_array($oldChecksum)) {
        // Caso antigo: pode ter vindo como string JSON (não esperado aqui, mas previnimos)
        $decoded = json_decode((string)$oldChecksum, true);
        if (is_array($decoded)) $oldChecksum = $decoded; else $oldChecksum = ['html' => '', 'css' => '', 'combined' => ''];
    }
    $oldVersion  = $item['version'] ?? '1.0';
    if (!checksumsEqual($oldChecksum, $newChecksum)) {
        $item['checksum'] = $newChecksum;
        $item['version']  = incrementVersionStr($oldVersion);
        $updates[$originKey] = ($updates[$originKey] ?? 0) + 1;
    }
}

/**
 * Caminhos de layout/page/component por idioma e id.
 */
function resourcePaths(string $base, string $language, string $typeKey, string $resId, bool $baseIsResourcesDir = false): array {
    $baseDir = $baseIsResourcesDir ? $base : ($base . DIRECTORY_SEPARATOR . 'resources');
    $dir = $baseDir . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $typeKey . DIRECTORY_SEPARATOR . $resId;
    return [
        'dir'  => $dir,
        'html' => $dir . DIRECTORY_SEPARATOR . $resId . '.html',
        'css'  => $dir . DIRECTORY_SEPARATOR . $resId . '.css',
        'md'   => $dir . DIRECTORY_SEPARATOR . $resId . '.md',
    ];
}

// ========================= 1) CARREGAR MAPEAMENTO GLOBAL =========================

/**
 * Carrega o mapeamento global de idiomas e arquivos data (resources.map.php)
 * @return array [$dadosMapeamentoGlobal]
 */
function carregarMapeamentoGlobal(): array {
    global $RESOURCES_DIR, $LOG_FILE;
    $mapFile = $RESOURCES_DIR . 'resources.map.php';
    if (!file_exists($mapFile)) {
    log_disco_local(__t('_map_file_not_found', ['file' => $mapFile]), $LOG_FILE);
        throw new RuntimeException('resources.map.php não encontrado');
    }
    $map = include $mapFile; // retorna $resources
    if (!isset($map['languages']) || !is_array($map['languages'])) {
    log_disco_local(__t('_map_invalid_structure'), $LOG_FILE);
        throw new RuntimeException('Estrutura inválida em resources.map.php');
    }
    log_disco_local(__t('_map_loaded', ['langs' => implode(',', array_keys($map['languages']))]), $LOG_FILE);
    return $map;
}

// ========================= 2) CARREGAR DADOS EXISTENTES =========================

/**
 * Carrega dados existentes para reutilizar versão caso checksum permaneça igual.
 * Retorna mapa indexado por chave de unicidade (incluindo language e outros componentes).
 */
function carregarDadosExistentes(): array {
    global $DB_DATA_DIR, $LOG_FILE;
    $arquivos = [
        'layouts'               => $DB_DATA_DIR . 'LayoutsData.json',
        'paginas'               => $DB_DATA_DIR . 'PaginasData.json',
        'componentes'           => $DB_DATA_DIR . 'ComponentesData.json',
        'templates'             => $DB_DATA_DIR . 'TemplatesData.json',
        'variaveis'             => $DB_DATA_DIR . 'VariaveisData.json',
        'prompts_ia'            => $DB_DATA_DIR . 'PromptsIaData.json',
        'modos_ia'              => $DB_DATA_DIR . 'ModosIaData.json',
        'alvos_ia'              => $DB_DATA_DIR . 'AlvosIaData.json',
        'forms'                 => $DB_DATA_DIR . 'FormsData.json',
    ];
    $exist = [];
    foreach ($arquivos as $tipo => $file) {
        $lista = jsonRead($file) ?? [];
        $exist[$tipo] = [];
        foreach ($lista as $r) {
            switch ($tipo) {
                case 'prompts_ia':
                case 'modos_ia':
                case 'alvos_ia':
                    if (!isset($r['language'],$r['id'])) continue 2;
                    $k = $r['language'].'|'.$r['id'];
                    break;
                case 'layouts':
                case 'componentes':
                case 'paginas':
                case 'templates':
                case 'forms':
                    if (!isset($r['language'],$r['id'])) continue 2;
                    $k = $r['language'].'|'.($r['modulo'] ?? '').'|'.$r['id'];
                    break;
                case 'variaveis':
                    if (!isset($r['language'],$r['id'])) continue 2;
                    $k = $r['language'].'|'.($r['modulo'] ?? '').'|'.$r['id'].'|'.($r['grupo'] ?? '');
                    break;
                default: continue 2;
            }
            $exist[$tipo][$k] = $r;
        }
        log_disco_local("Existentes ($tipo): ".count($exist[$tipo]), $LOG_FILE);
    }
    return $exist;
}

// ========================= 3) COLETAR RECURSOS =========================

/**
 * Coleta recursos aplicando regras de unicidade e separando órfãos.
 */
function coletarRecursos(array $existentes, array $map): array {
    global $RESOURCES_DIR, $MODULES_DIR, $LOG_FILE, $DB_DATA_DIR;
    $languages = array_keys($map['languages']);

    $layouts = $paginas = $componentes = $variaveis = $prompts_ia = $alvos_ia = $modos_ia = $templates = $forms = [];
    $orphans = [ 'layouts'=>[], 'paginas'=>[], 'componentes'=>[], 'variaveis'=>[], 'prompts_ia'=>[], 'alvos_ia'=>[], 'modos_ia'=>[], 'templates'=>[], 'forms'=>[] ];

    // Índices de unicidade
    $idxLayouts = [];              // lang|id
    $idxComponentes = [];          // lang|id
    $idxTemplates = [];            // lang|id
    $idxPromptsIa = [];            // lang|id
    $idxPromptsAlvosIa = [];       // lang|id
    $idxModosIa = [];              // lang|id
    $idxPaginasId = [];            // lang|mod|id
    $idxPaginasPath = [];          // lang|caminho
    $idxVariaveis = [];            // lang|mod|id => groups[]
    $idxForms = [];               // lang|mod|id

    // Helper versão + checksum reutilizando existente
    $versaoChecksum = function(string $tipo, string $chave, ?string $html, ?string $css) use (&$existentes) : array {
        $cks = buildChecksum($html,$css);
        $versao = 1;
        if (isset($existentes[$tipo][$chave])) {
            $old = $existentes[$tipo][$chave];
            $oldChecksum = $old['checksum'] ?? null;
            if (is_string($oldChecksum)) { $dec = json_decode($oldChecksum,true); if ($dec) $oldChecksum=$dec; }
            if (is_array($oldChecksum) && checksumsEqual($oldChecksum,$cks)) {
                $versao = (int)($old['versao'] ?? 1);
            } else {
                $versao = (int)($old['versao'] ?? 1)+1;
            }
        }
        return [$versao,$cks];
    };

    $versaoChecksumPrompt = function(string $tipo, string $chave, ?string $md) use (&$existentes) : array {
        $cks = buildChecksumMD($md);
        $versao = 1;
        if (isset($existentes[$tipo][$chave])) {
            $old = $existentes[$tipo][$chave];
            $oldChecksum = $old['checksum'] ?? null;
            if (is_string($oldChecksum)) { $dec = json_decode($oldChecksum,true); if ($dec) $oldChecksum=$dec; }
            if (is_array($oldChecksum) && checksumsEqual($oldChecksum,$cks)) {
                $versao = (int)($old['versao'] ?? 1);
            } else {
                $versao = (int)($old['versao'] ?? 1)+1;
            }
        }
        return [$versao,$cks];
    };

    // ---------- Globais ----------
    foreach ($languages as $lang) {
        $langInfo = $map['languages'][$lang] ?? null; if (!$langInfo || !isset($langInfo['data'])) continue;
        $dataFiles = $langInfo['data'];

        // Layouts
        if (!empty($dataFiles['layouts'])) {
            $file = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles['layouts'];
            $lista = jsonRead($file) ?? [];
            foreach ($lista as $l) {
                $id = $l['id'] ?? null; if(!$id){$orphans['layouts'][]=$l+['_motivo'=>'sem id','language'=>$lang];continue;}
                $key = $lang.'|'.$id;
                if(isset($idxLayouts[$key])){ $orphans['layouts'][]=$l+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
                $idxLayouts[$key]=true;
                $paths = resourcePaths($RESOURCES_DIR,$lang,'layouts',$id,true);
                $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                [$versao,$cks] = $versaoChecksum('layouts',$key,$html,$css);
                $layouts[] = [
                    'nome' => $l['name'] ?? ($l['nome'] ?? $id),
                    'id' => $id,
                    'language' => $lang,
                    'html' => $html,
                    'css' => $css,
                    'framework_css' => getFrameworkCss($l),
                    'status' => $l['status'] ?? 'A',
                    'versao' => $versao,
                    'file_version' => $l['version'] ?? null,
                    'checksum' => json_encode($cks,JSON_UNESCAPED_UNICODE)
                ];
            }
        }

        // Componentes
        if (!empty($dataFiles['components'])) {
            $file = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles['components'];
            $lista = jsonRead($file) ?? [];
            foreach ($lista as $c) {
                $id = $c['id'] ?? null; if(!$id){$orphans['componentes'][]=$c+['_motivo'=>'sem id','language'=>$lang];continue;}
                $key = $lang.'|'.$id; if(isset($idxComponentes[$key])){$orphans['componentes'][]=$c+['_motivo'=>'duplicidade id','language'=>$lang];continue;}
                $idxComponentes[$key]=true;
                $paths = resourcePaths($RESOURCES_DIR,$lang,'components',$id,true);
                $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                [$versao,$cks] = $versaoChecksum('componentes',$key,$html,$css);
                $componentes[] = [
                    'nome' => $c['name'] ?? ($c['nome'] ?? $id),
                    'id' => $id,
                    'language' => $lang,
                    'modulo' => $c['module'] ?? ($c['modulo'] ?? null),
                    'html' => $html,
                    'css' => $css,
                    'framework_css' => getFrameworkCss($c),
                    'status' => $c['status'] ?? 'A',
                    'versao' => $versao,
                    'file_version' => $c['version'] ?? null,
                    'checksum' => json_encode($cks,JSON_UNESCAPED_UNICODE)
                ];
            }
        }

        // Páginas
        if (!empty($dataFiles['pages'])) {
            $file = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles['pages'];
            $lista = jsonRead($file) ?? [];
            foreach ($lista as $p) {
                $id = $p['id'] ?? null; if(!$id){$orphans['paginas'][]=$p+['_motivo'=>'sem id','language'=>$lang];continue;}
                $mod = $p['module'] ?? ($p['modulo'] ?? null);
                $path = $p['path'] ?? ($p['caminho'] ?? ($id.'/'));
                $kId = $lang.'|'.($mod??'').'|'.$id; if(isset($idxPaginasId[$kId])){$orphans['paginas'][]=$p+['_motivo'=>'duplicidade id','language'=>$lang];continue;}
                $kPath = $lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])){$orphans['paginas'][]=$p+['_motivo'=>'duplicidade caminho','language'=>$lang];continue;}
                $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true;
                $paths = resourcePaths($RESOURCES_DIR,$lang,'pages',$id,true);
                $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                [$versao,$cks] = $versaoChecksum('paginas',$kId,$html,$css);
                $paginas[] = [
                    'layout_id' => $p['layout'] ?? null,
                    'nome' => $p['name'] ?? ($p['nome'] ?? $id),
                    'id' => $id,
                    'language' => $lang,
                    'caminho' => $path,
                    'tipo' => $p['type'] ?? ($p['tipo'] ?? null),
                    'modulo' => $mod,
                    'opcao' => $p['option'] ?? ($p['opcao'] ?? null),
                    'raiz' => $p['root'] ?? ($p['raiz'] ?? null),
                    'sem_permissao' => $p['without_permission'] ?? ($p['sem_permissao'] ?? null),
                    'html' => $html,
                    'css' => $css,
                    'html_extra_head' => $p['html_extra_head'] ?? null,
                    'framework_css' => getFrameworkCss($p),
                    'status' => $p['status'] ?? 'A',
                    'versao' => $versao,
                    'file_version' => $p['version'] ?? null,
                    'checksum' => json_encode($cks,JSON_UNESCAPED_UNICODE)
                ];
            }
        }

        // Templates
        if (!empty($dataFiles['templates'])) {
            $file = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles['templates'];
            $lista = jsonRead($file) ?? [];
            foreach ($lista as $p) {
                $id = $p['id'] ?? null; if(!$id){$orphans['templates'][]=$p+['_motivo'=>'sem id','language'=>$lang];continue;}
                $target = $p['target'] ?? ($p['target'] ?? null);
                $kId = $lang.'|'.($target??'').'|'.$id; if(isset($idxTemplates[$kId])){$orphans['templates'][]=$p+['_motivo'=>'duplicidade id','language'=>$lang];continue;}
                $idxTemplates[$kId]=true;
                $paths = resourcePaths($RESOURCES_DIR,$lang,'templates',$id,true);
                $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                [$versao,$cks] = $versaoChecksum('templates',$kId,$html,$css);
                $templates[] = [
                    'nome' => $p['name'] ?? ($p['nome'] ?? $id),
                    'id' => $id,
                    'target' => $target,
                    'thumbnail' => $p['thumbnail'] ?? null,
                    'language' => $lang,
                    'html' => $html,
                    'css' => $css,
                    'framework_css' => getFrameworkCss($p),
                    'status' => $p['status'] ?? 'A',
                    'versao' => $versao,
                    'file_version' => $p['version'] ?? null,
                    'checksum' => json_encode($cks,JSON_UNESCAPED_UNICODE)
                ];
            }
        }

        // Variáveis globais
        if (!empty($dataFiles['variables'])) {
            $file = $RESOURCES_DIR.$lang.DIRECTORY_SEPARATOR.$dataFiles['variables'];
            $lista = jsonRead($file) ?? [];
            foreach ($lista as $v) {
                $id = $v['id'] ?? null; if(!$id){$orphans['variaveis'][]=$v+['_motivo'=>'sem id','language'=>$lang];continue;}
                $mod = $v['module'] ?? ($v['modulo'] ?? '');
                $grp = $v['group'] ?? ($v['grupo'] ?? null);
                $base = $lang.'|'.$mod.'|'.$id;
                if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[];
                $groups = $idxVariaveis[$base];
                if($grp===null || $grp==='') { // sem group permitido somente se nenhum group já existe
                    if(!empty($groups) || in_array('', $groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','language'=>$lang]; continue; }
                } else { // com group
                    if(in_array($grp,$groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','language'=>$lang]; continue; }
                }
                $idxVariaveis[$base][] = ($grp ?? '');
                $variaveis[] = [
                    'language' => $lang,
                    'modulo' => $mod!=='' ? $mod : null,
                    'id' => $id,
                    'valor' => $v['value'] ?? ($v['valor'] ?? null),
                    'tipo' => $v['type'] ?? ($v['tipo'] ?? null),
                    'grupo' => $grp,
                    'descricao' => $v['description'] ?? ($v['descricao'] ?? null),
                ];
            }
        }
    }

    // ---------- Módulos ----------
    if (is_dir($MODULES_DIR)) {
        $mods = glob($MODULES_DIR.'*',GLOB_ONLYDIR) ?: [];
        foreach ($mods as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath.DIRECTORY_SEPARATOR.$modId.'.json';
            $data = jsonRead($jsonFile); if(!$data) continue;
            foreach ($languages as $lang) {
                if(empty($data['resources'][$lang])) continue;
                $res = $data['resources'][$lang];
                foreach (['layouts','components','templates','pages','ai_prompts','ai_prompts_targets','ai_modes'] as $tipo) {
                    $arr = $res[$tipo] ?? [];
                    foreach ($arr as $item) {
                        $id = $item['id'] ?? null; if(!$id) continue;
                        $paths = resourcePaths($modPath,$lang,$tipo,$id);
                        $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                        if ($tipo==='layouts') {
                            $key = $lang.'|'.$modId.'|'.$id; if(isset($idxLayouts[$key])) { $orphans['layouts'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxLayouts[$key]=true; [$versao,$cks]=$versaoChecksum('layouts',$key,$html,$css);
                            $layouts[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'framework_css'=>getFrameworkCss($item),'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        } elseif ($tipo==='components') {
                            $key = $lang.'|'.$modId.'|'.$id; if(isset($idxComponentes[$key])) { $orphans['componentes'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxComponentes[$key]=true; [$versao,$cks]=$versaoChecksum('componentes',$key,$html,$css);
                            $componentes[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'framework_css'=>getFrameworkCss($item),'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        } elseif ($tipo==='templates') {
                            $target = $item['target'] ?? ($item['target'] ?? null);
                            $key = $lang.'|'.$target.'|'.$id; if(isset($idxTemplates[$key])) { $orphans['templates'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'target'=>$target]; continue; }
                            $idxTemplates[$key]=true; [$versao,$cks]=$versaoChecksum('templates',$key,$html,$css);
                            $templates[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'target'=>$target,'thumbnail'=>$item['thumbnail'] ?? null,'language'=>$lang,'html'=>$html,'css'=>$css,'framework_css'=>getFrameworkCss($item),'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        } elseif ($tipo==='ai_prompts') {
                            $md = readFileIfExists($paths['md']);
                            $key = $lang.'|'.$id; if(isset($idxPromptsIa[$key])) { $orphans['ai_prompts'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
                            $idxPromptsIa[$key]=true; [$versao,$cks]=$versaoChecksumPrompt('ai_prompts',$key,$md);
                            $prompts_ia_aux = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'alvo'=>$item['target'] ?? null,'prompt'=>$md,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                            if(isset($item['default']) && $item['default']) $prompts_ia_aux['padrao']=1;
                            $prompts_ia[] = $prompts_ia_aux;
                        } elseif ($tipo==='ai_modes') {
                            $md = readFileIfExists($paths['md']);
                            $key = $lang.'|'.$id; if(isset($idxModosIa[$key])) { $orphans['ai_modes'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
                            $idxModosIa[$key]=true; [$versao,$cks]=$versaoChecksumPrompt('ai_modes',$key,$md);
                            $modos_ia_aux = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'alvo'=>$item['target'] ?? null,'prompt'=>$md,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                            if(isset($item['default']) && $item['default']) $modos_ia_aux['padrao']=1;
                            $modos_ia[] = $modos_ia_aux;
                        } elseif ($tipo==='ai_prompts_targets') {
                            $key = $lang.'|'.$id; if(isset($idxPromptsAlvosIa[$key])) { $orphans['alvos_ia'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang]; continue; }
                            $idxPromptsAlvosIa[$key]=true;
                            $alvos_ia[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'status'=>$item['status'] ?? 'A','versao'=>$versao ];
                        } else { // pages
                            $path = $item['path'] ?? ($id.'/');
                            $kId = $lang.'|'.$modId.'|'.$id; if(isset($idxPaginasId[$kId])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $kPath = $lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade caminho','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true; [$versao,$cks]=$versaoChecksum('paginas',$kId,$html,$css);
                            $paginas[] = [ 'layout_id'=>$item['layout'] ?? null,'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'caminho'=>$path,'tipo'=>$item['type'] ?? null,'modulo'=>$modId,'opcao'=>$item['option'] ?? null,'raiz'=>$item['root'] ?? null,'sem_permissao'=>$item['without_permission'] ?? null,'html'=>$html, 'html_extra_head' => $item['html_extra_head'] ?? null,'css'=>$css,'framework_css'=>getFrameworkCss($item),'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        }
                    }
                }
                foreach (['variables','forms'] as $tipo) {
                    $arr = $res[$tipo] ?? [];
                    foreach ($arr as $item) {
                        $id = $item['id'] ?? null; if(!$id) continue;

                        if ($tipo==='variables') {
                            $grp = $item['group'] ?? null; $base = $lang.'|'.$modId.'|'.$id;
                            if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[];
                            $groups = $idxVariaveis[$base];
                            if($grp===null || $grp==='') { if(!empty($groups) || in_array('', $groups,true)){ $orphans['variaveis'][]=$item+['_motivo'=>'duplicidade sem group','language'=>$lang,'modulo'=>$modId]; continue; } }
                            else { if(in_array($grp,$groups,true)){ $orphans['variaveis'][]=$item+['_motivo'=>'duplicidade group repetido','language'=>$lang,'modulo'=>$modId]; continue; } }
                            $idxVariaveis[$base][] = ($grp ?? '');
                            $variaveis[] = [ 'language'=>$lang,'modulo'=>$modId,'id'=>$id,'valor'=>$item['value'] ?? null,'tipo'=>$item['type'] ?? null,'grupo'=>$grp,'descricao'=>$item['description'] ?? null ];
                        } elseif ($tipo==='forms') {
                            $kId = $lang.'|'.$modId.'|'.$id; if(isset($idxForms[$kId])) { $orphans['forms'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxForms[$kId]=true;
                            if(isset($item['fields_schema'])){
                                $item['fields_schema'] = json_encode($item['fields_schema'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
                            }
                            $forms[] = [ 'name'=>$item['name'] ?? null,'id'=>$id,'description'=>$item['description'] ?? null,'template_id'=>$item['template_id'] ?? null,'fields_schema'=>$item['fields_schema'] ?? null,'module'=>$modId,'language'=>$lang ];
                        }
                    }
                }
            }
        }
    }

    // ---------- Tabelas dinâmicas (sync_resources) ----------
    // Varredura genérica declarada em tables_config.json (global) e nos blocos "tabela.config"
    // dos módulos. Cada tabela com sync_resources=true tem seus registros lidos (inline/arquivo),
    // convertidos via field_types (json/file:ext) e acumulados em dynamicTablesData[tabela][].
    $dynamicTablesData = [];
    // Não sobrescrever os Data.json gerados pelo pipeline fixo acima.
    $reservadas = [
        'layouts'=>true,'paginas'=>true,'componentes'=>true,'templates'=>true,'variaveis'=>true,
        'prompts_ia'=>true,'modos_ia'=>true,'alvos_ia'=>true,'forms'=>true,
    ];
    $existDinamicoCache = [];
    foreach (coletarConfigsTabelas() as [$cfg, $src]) {
        if (empty($cfg['sync_resources'])) continue;
        $tabelaNome = $cfg['nome'];
        if (isset($reservadas[$tabelaNome])) {
            log_disco_local("DYNAMIC_SKIP_RESERVADA tabela=$tabelaNome src=$src", $LOG_FILE);
            continue;
        }
        $fieldTypes = (isset($cfg['field_types']) && is_array($cfg['field_types'])) ? $cfg['field_types'] : [];
        // Coluna identificadora lógica: tabelas customizadas podem usar outra coluna (ex.: page_id).
        $idCol = (isset($cfg['id']) && is_string($cfg['id']) && $cfg['id'] !== '') ? $cfg['id'] : 'id';

        // Índice dos dados existentes (para reuso de versao quando o checksum não muda).
        if (!array_key_exists($tabelaNome, $existDinamicoCache)) {
            $listaExist = jsonRead($DB_DATA_DIR . dataFileNameFromTable($tabelaNome)) ?? [];
            $idx = [];
            foreach ($listaExist as $er) {
                if (!is_array($er) || !isset($er[$idCol])) continue;
                $mod = $er['module'] ?? ($er['modulo'] ?? '');
                $lng = $er['language'] ?? ($er['linguagem_codigo'] ?? '');
                $idx[$lng.'|'.$mod.'|'.$er[$idCol]] = $er;
            }
            $existDinamicoCache[$tabelaNome] = $idx;
        }
        $existIdx = $existDinamicoCache[$tabelaNome];

        foreach ($languages as $lang) {
            foreach (lerMetadadosDinamicos($cfg, $lang) as $rec) {
                if (!is_array($rec)) continue;
                $id = $rec[$idCol] ?? null;
                if ($id === null || $id === '') continue;
                $registro = processarRegistroDinamico($rec, $cfg, $lang);
                $checksum = checksumRegistroDinamico($registro, $fieldTypes);
                $mod = $registro['module'] ?? ($registro['modulo'] ?? '');
                $kExist = $lang.'|'.$mod.'|'.$id;
                $versao = 1;
                if (isset($existIdx[$kExist])) {
                    $old = $existIdx[$kExist];
                    $oldVer = (int)($old['versao'] ?? 1);
                    $versao = (($old['checksum'] ?? null) === $checksum) ? $oldVer : $oldVer + 1;
                }
                $registro['versao'] = $versao;
                $registro['checksum'] = $checksum;
                if (!array_key_exists('user_modified', $registro)) $registro['user_modified'] = 0;
                $dynamicTablesData[$tabelaNome][] = $registro;
            }
        }
        log_disco_local("DYNAMIC_COLLECTED tabela=$tabelaNome qtd=".count($dynamicTablesData[$tabelaNome] ?? [])." src=$src", $LOG_FILE);
    }

    log_disco_local(__t('_collected_summary', [
        'layouts'=>count($layouts), 'pages'=>count($paginas), 'components'=>count($componentes), 'templates'=>count($templates), 'variables'=>count($variaveis), 'prompts_ia'=>count($prompts_ia), 'alvos_ia'=>count($alvos_ia), 'modos_ia'=>count($modos_ia), 'forms'=>count($forms)
    ]), $LOG_FILE);

    return [
        'layoutsData'=>$layouts,
        'pagesData'=>$paginas,
        'componentsData'=>$componentes,
        'templatesData'=>$templates,
        'variablesData'=>$variaveis,
        'promptsData'=>$prompts_ia,
        'modesData'=>$modos_ia,
        'targetsData'=>$alvos_ia,
        'formsData'=>$forms,
        'dynamicTablesData'=>$dynamicTablesData,
        'orphans'=>$orphans,
    ];
}

// ========================= 4) ATUALIZAR DADOS (Persistir) =========================

/**
 * Persiste os Data.json finais.
 */
function atualizarDados(array $dadosExistentes, array $recursos): void {
    global $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE;
    jsonWrite($DB_DATA_DIR.'LayoutsData.json', $recursos['layoutsData']);
    jsonWrite($DB_DATA_DIR.'PaginasData.json', $recursos['pagesData']);
    jsonWrite($DB_DATA_DIR.'ComponentesData.json', $recursos['componentsData']);
    jsonWrite($DB_DATA_DIR.'TemplatesData.json', $recursos['templatesData']);
    jsonWrite($DB_DATA_DIR.'VariaveisData.json', $recursos['variablesData']);
    jsonWrite($DB_DATA_DIR.'PromptsIaData.json', $recursos['promptsData']);
    jsonWrite($DB_DATA_DIR.'AlvosIaData.json', $recursos['targetsData']);
    jsonWrite($DB_DATA_DIR.'FormsData.json', $recursos['formsData']);
    jsonWrite($DB_DATA_DIR.'ModosIaData.json', $recursos['modesData']);
    // Tabelas dinâmicas (sync_resources): gera [PascalCase]Data.json para cada tabela coletada.
    foreach (($recursos['dynamicTablesData'] ?? []) as $tabela => $linhas) {
        if (!is_string($tabela) || !preg_match('/^[a-z0-9_]+$/', $tabela)) {
            log_disco_local("DYNAMIC_DATA_SKIP_NOME_INVALIDO tabela=".$tabela, $LOG_FILE);
            continue;
        }
        $fileName = dataFileNameFromTable($tabela);
        jsonWrite($DB_DATA_DIR.$fileName, array_values((array)$linhas));
        log_disco_local("DYNAMIC_DATA_SAVED $fileName qtd=".count((array)$linhas), $LOG_FILE);
    }
    $orphDir = $GESTOR_DIR.'db'.DIRECTORY_SEPARATOR.'orphans'.DIRECTORY_SEPARATOR;
    ensureDir($orphDir, $LOG_FILE);
    foreach (['Layouts','Paginas','Componentes','Templates','Variaveis','PromptsIa','AlvosIa','ModosIa','Forms'] as $T) {
        $k = strtolower($T);
        jsonWrite($orphDir.$T.'Data.json', $recursos['orphans'][$k] ?? []);
    }
    log_disco_local('Dados persistidos + órfãos.', $LOG_FILE);
}

// ========================= 5) REPORTE FINAL =========================

function validarDuplicidades(array $recursos): array {
    $erros = [];
    foreach (['layouts','paginas','componentes','templates','variaveis','prompts_ia','alvos_ia','modos_ia','forms'] as $t) {
        $q = count($recursos['orphans'][$t] ?? []); if($q>0) $erros[] = "$t: $q órfãos";
    }
    return $erros;
}

/**
 * Aplica os erros diretamente nos arquivos de origem (globais, módulos).
 * @param array $dupsMeta Lista de duplicados com metadados e mensagem.
 */
function aplicarErrosOrigem(array $dupsMeta, array $originsIndex): void { /* V2: não marca origem, usa órfãos */ }

function reporteFinal(array $recursos, array $erros): void {
    global $LOG_FILE;
    $total = count($recursos['layoutsData']) + count($recursos['pagesData']) + count($recursos['componentsData']) + count($recursos['templatesData']) + count($recursos['variablesData']) + count($recursos['promptsData']) + count($recursos['modesData']) + count($recursos['targetsData']) + count($recursos['formsData']);
    $totalOrphans = 0; foreach ($recursos['orphans'] as $lst) { $totalOrphans += count($lst); }

    $msg = "♻️  Relatório Final:".PHP_EOL.
           "➡️  Layouts: ".count($recursos['layoutsData']).PHP_EOL.
           "➡️  Páginas: ".count($recursos['pagesData']).PHP_EOL.
           "➡️  Componentes: ".count($recursos['componentsData']).PHP_EOL.
           "➡️  Templates: ".count($recursos['templatesData']).PHP_EOL.
           "➡️  Variáveis: ".count($recursos['variablesData']).PHP_EOL.
           "➡️  Modos IA: ".count($recursos['modesData']).PHP_EOL.
           "➡️  Prompts IA: ".count($recursos['promptsData']).PHP_EOL.
           "➡️  Alvos IA: ".count($recursos['targetsData']).PHP_EOL.
           "➡️  Formulários: ".count($recursos['formsData']).PHP_EOL.
           "Σ TOTAL: $total".PHP_EOL;

    if($totalOrphans > 0) $msg .=
           "⚠️  ⚠️  Órfãos Detectados: $totalOrphans ⚠️  ⚠️".PHP_EOL.
           "⚙️  Detalhes dos órfãos foram salvos na pasta 'gestor/db/orphans/' para análise.".PHP_EOL;
    if (!empty($erros)) { $msg .= "❌ ERROS: ".implode('; ',$erros).PHP_EOL; }

    if($totalOrphans > 0 || !empty($erros)) {
        $msg .= "❌ Problemas detectados necessitando análise.".PHP_EOL;
    } else {
        $msg .= "✅ Nenhum problema detectado.".PHP_EOL;
    }

    log_disco_local($msg,$LOG_FILE); echo $msg;
}

// ========================= 5.1) CONTRATO DE SINCRONIZAÇÃO (REGISTRY) =========================

/**
 * Converte nome de tabela snake_case para o nome do arquivo *Data.json.
 * Ex.: paginas => PaginasData.json ; usuarios_perfis_modulos => UsuariosPerfisModulosData.json
 */
function dataFileNameFromTable(string $tabela): string {
    $pascal = preg_replace_callback('/(^|_)([a-z])/', function ($m) { return strtoupper($m[2]); }, strtolower($tabela));
    return $pascal . 'Data.json';
}

/**
 * Lê a lista de registros (metadados) de uma tabela dinâmica (sync_resources) para um idioma.
 * Resolve a origem conforme a configuração:
 *  - metadata_file definido:
 *      * módulo: <base_dir>/<lang>/<resources_dir|tabela>/<metadata_file>
 *      * global: <base_dir>/<lang>/<metadata_file> (ou .../<resources_dir>/<metadata_file> se resources_dir for explícito)
 *  - metadata_file ausente: registros inline em <inline>.resources.<lang>.<tabela_nome>
 * Retorna sempre uma lista (array de registros) — vazia quando não há nada a coletar.
 */
function lerMetadadosDinamicos(array $cfg, string $lang): array {
    $tabela = $cfg['nome'];
    $metaFile = $cfg['metadata_file'] ?? null;
    $baseDir = $cfg['base_dir'] ?? null;
    if ($metaFile && is_string($baseDir) && $baseDir !== '') {
        $resDirExplicit = $cfg['resources_dir'] ?? null;
        if (($cfg['scope'] ?? null) === 'module') {
            $resDir = $resDirExplicit ?? $tabela;
            $path = $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDir . DIRECTORY_SEPARATOR . $metaFile;
        } else { // global
            $path = $resDirExplicit
                ? $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDirExplicit . DIRECTORY_SEPARATOR . $metaFile
                : $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $metaFile;
        }
        $lista = jsonRead($path);
        return is_array($lista) ? array_values($lista) : [];
    }
    // Inline: resources -> lang -> tabela_nome
    $inline = $cfg['inline'] ?? null;
    if (is_array($inline) && isset($inline['resources'][$lang][$tabela]) && is_array($inline['resources'][$lang][$tabela])) {
        return array_values($inline['resources'][$lang][$tabela]);
    }
    return [];
}

/**
 * Processa um registro de tabela dinâmica aplicando as conversões declaradas em field_types:
 *  - "json": codifica arrays/objetos com json_encode (pretty + unescaped unicode).
 *  - "file:<ext>": injeta o conteúdo (sem BOM) do arquivo físico <id>.<ext> na pasta do recurso
 *    (<base_dir>/<lang>/<resources_dir|tabela>/<id>/<id>.<ext>).
 * Preenche colunas padronizadas: language (idioma da varredura), status ('A' default),
 * user_modified (0 default). versao/checksum são resolvidos pelo chamador.
 */
function processarRegistroDinamico(array $rec, array $cfg, string $lang): array {
    $tabela = $cfg['nome'];
    $fieldTypes = (isset($cfg['field_types']) && is_array($cfg['field_types'])) ? $cfg['field_types'] : [];
    $resDirName = $cfg['resources_dir'] ?? $tabela;
    $baseDir = $cfg['base_dir'] ?? '';
    $filesDir = $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDirName;
    // Coluna identificadora lógica: tabelas customizadas podem usar outra coluna (ex.: page_id).
    $idCol = (isset($cfg['id']) && is_string($cfg['id']) && $cfg['id'] !== '') ? $cfg['id'] : 'id';
    $id = isset($rec[$idCol]) ? (string)$rec[$idCol] : '';

    $registro = [];
    foreach ($rec as $campo => $valor) {
        $tipo = $fieldTypes[$campo] ?? null;
        if ($tipo === 'json' && (is_array($valor) || is_object($valor))) {
            $registro[$campo] = json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $registro[$campo] = $valor;
        }
    }

    // Campos file:<ext> — injeta conteúdo físico (mesmo quando ausente no metadado).
    foreach ($fieldTypes as $campo => $tipo) {
        if (is_string($tipo) && strncmp($tipo, 'file:', 5) === 0 && $id !== '') {
            $ext = substr($tipo, 5);
            if ($ext === '') continue;
            $fpath = $filesDir . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.' . $ext;
            $conteudo = readFileIfExists($fpath);
            if ($conteudo !== null) {
                $registro[$campo] = $conteudo;
            } elseif (!array_key_exists($campo, $registro)) {
                $registro[$campo] = null;
            }
        }
    }

    // Colunas padronizadas (a coluna real de idioma/módulo é filtrada pelo atualizador via SHOW COLUMNS).
    if (!array_key_exists('language', $registro)) $registro['language'] = $lang;
    $cols = is_array($cfg['natural_key_columns'] ?? null) ? $cfg['natural_key_columns'] : [];
    if (($cfg['scope'] ?? null) === 'module' && !empty($cfg['modulo'])) {
        if (in_array('module', $cols, true) && !array_key_exists('module', $registro)) $registro['module'] = $cfg['modulo'];
        if (in_array('modulo', $cols, true) && !array_key_exists('modulo', $registro)) $registro['modulo'] = $cfg['modulo'];
    }
    if (!array_key_exists('status', $registro)) $registro['status'] = 'A';

    return $registro;
}

/**
 * Calcula o checksum MD5 de um registro dinâmico. Quando há campos file:<ext>, baseia-se
 * exclusivamente no conteúdo desses arquivos; caso contrário, no payload do registro
 * (excluindo colunas de controle versao/checksum/user_modified).
 */
function checksumRegistroDinamico(array $registro, array $fieldTypes): string {
    $fileParts = [];
    foreach ($fieldTypes as $campo => $tipo) {
        if (is_string($tipo) && strncmp($tipo, 'file:', 5) === 0) {
            $fileParts[] = $campo . '=' . (string)($registro[$campo] ?? '');
        }
    }
    if ($fileParts) {
        return md5(implode("\x1f", $fileParts));
    }
    $payload = $registro;
    unset($payload['versao'], $payload['checksum'], $payload['user_modified']);
    return md5((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
}

/**
 * Normaliza um bloco "tabela" (de módulo ou do global) para a forma canônica do contrato.
 * A sub-chave "config" pode ser um OBJETO único (1 tabela) ou um ARRAY de objetos (N tabelas);
 * objetos únicos são convertidos internamente em um array de 1 elemento.
 *
 * Retorna SEMPRE uma lista (array de 0+ entradas normalizadas), uma por configuração válida.
 * Cada entrada carrega os campos do contrato (estratégia/chave natural/preserve/insert_only) e
 * também as diretivas de sincronização declarativa de recursos (sync_resources/resources_dir/
 * metadata_file/field_types) e as listas agregadas por tabela (deletar/forcar_atualizacao).
 *
 * O nome da tabela de cada entrada vem de config.tabela_nome (quando presente) ou do nome do
 * bloco ("tabela"."nome"). As listas deletar/forcar_atualizacao podem ser declaradas dentro de
 * cada elemento de config OU no nível do bloco (retrocompat); o nível do bloco só é herdado
 * quando o nome da tabela coincide com o nome do bloco.
 */
function normalizarConfigTabela(array $meta): array {
    $nomeBloco = (isset($meta['nome']) && is_string($meta['nome'])) ? $meta['nome'] : null;
    $configRaw = $meta['config'] ?? null;
    if (!is_array($configRaw)) return [];
    // Objeto associativo único => array de 1 elemento. Array de objetos => mantém.
    $configs = array_is_list($configRaw) ? $configRaw : [$configRaw];

    $out = [];
    foreach ($configs as $config) {
        if (!is_array($config)) continue;
        $nome = (isset($config['tabela_nome']) && is_string($config['tabela_nome']) && $config['tabela_nome'] !== '')
            ? $config['tabela_nome'] : $nomeBloco;
        if (!is_string($nome) || $nome === '') continue;
        $mesmoBloco = ($nome === $nomeBloco);
        $strategy = ($config['strategy'] ?? 'pk') === 'natural_key' ? 'natural_key' : 'pk';

        // deletar/forcar_atualizacao: do elemento de config, com fallback ao nível do bloco
        // somente quando a tabela coincide com o nome do bloco (retrocompat com BATCH-029).
        $deletar = array_values((array)($config['deletar'] ?? []));
        if (!$deletar && $mesmoBloco) $deletar = array_values((array)($meta['deletar'] ?? []));
        $forcar = array_values((array)($config['forcar_atualizacao'] ?? []));
        if (!$forcar && $mesmoBloco) $forcar = array_values((array)($meta['forcar_atualizacao'] ?? []));

        $resourcesDir = (isset($config['resources_dir']) && is_string($config['resources_dir']) && $config['resources_dir'] !== '')
            ? $config['resources_dir'] : null;
        $metadataFile = (isset($config['metadata_file']) && is_string($config['metadata_file']) && $config['metadata_file'] !== '')
            ? $config['metadata_file'] : null;
        $fieldTypes = (isset($config['field_types']) && is_array($config['field_types'])) ? $config['field_types'] : [];
        $scopeOverride = (isset($config['scope']) && is_string($config['scope'])) ? $config['scope'] : null;
        $moduloOverride = (isset($config['modulo']) && is_string($config['modulo']) && $config['modulo'] !== '')
            ? $config['modulo'] : null;

        $out[] = [
            'nome' => $nome,
            'id' => $config['id'] ?? ($mesmoBloco ? ($meta['id'] ?? 'id') : 'id'),
            'id_numerico' => $config['id_numerico'] ?? ($mesmoBloco ? ($meta['id_numerico'] ?? null) : null),
            'data_file' => dataFileNameFromTable($nome),
            'strategy' => $strategy,
            'natural_key_columns' => array_values(array_filter((array)($config['natural_key_columns'] ?? []), 'is_string')),
            'preserve_on_user_modified' => array_values(array_filter((array)($config['preserve_on_user_modified'] ?? []), 'is_string')),
            'insert_only' => !empty($config['insert_only']),
            // Diretivas de sincronização declarativa de recursos (build-time; não vão ao contrato).
            'sync_resources' => !empty($config['sync_resources']),
            'resources_dir' => $resourcesDir,
            'metadata_file' => $metadataFile,
            'field_types' => $fieldTypes,
            'scope_override' => $scopeOverride,
            'modulo_override' => $moduloOverride,
            // Listas agregadas por tabela.
            'deletar' => $deletar,
            'forcar_atualizacao' => $forcar,
        ];
    }
    return $out;
}

/**
 * Coleta TODAS as configurações de tabela normalizadas (globais + módulos), em ordem
 * determinística (núcleo herdado em modo projeto → global/projeto → módulos do núcleo →
 * módulos locais). Cada item retornado é [$norm, $source], onde $norm é a saída de
 * normalizarConfigTabela() acrescida do contexto de varredura de recursos:
 *  - 'scope'    : 'global' ou 'module'
 *  - 'modulo'   : id do módulo (apenas scope=module)
 *  - 'base_dir' : pasta-base de recursos (gestor/resources ou <modulo>/resources)
 *  - 'inline'   : referência ao JSON de origem (para metadados inline em resources->lang->tabela)
 *
 * Reaproveitada por gerarSchemaMetadata() (contrato) e por coletarRecursos() (varredura dinâmica).
 * Registros posteriores (módulos) sobrescrevem anteriores (global) ao consolidar por nome de tabela.
 */
function coletarConfigsTabelas(): array {
    global $RESOURCES_DIR, $MODULES_DIR, $SYSTEM_PATH, $LOG_FILE;
    $out = [];

    // 1) Tabelas globais/projeto: core usa tables_config.json; projeto usa project_tables_config.json.
    $arquivosGlobais = [];
    if (!empty($GLOBALS['CLI_ARGS']['project-path'])) {
        $coreGlobal = $SYSTEM_PATH . 'gestor' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'tables_config.json';
        if (is_file($coreGlobal)) {
            $arquivosGlobais[] = [$coreGlobal, 'core:tables_config.json', dirname($coreGlobal)];
        }
        $projectGlobal = $RESOURCES_DIR . 'project_tables_config.json';
        if (is_file($projectGlobal)) {
            $arquivosGlobais[] = [$projectGlobal, 'project:project_tables_config.json', rtrim($RESOURCES_DIR, DIRECTORY_SEPARATOR)];
        }
    } else {
        $arquivosGlobais[] = [$RESOURCES_DIR . 'tables_config.json', 'global:tables_config.json', rtrim($RESOURCES_DIR, DIRECTORY_SEPARATOR)];
    }

    foreach ($arquivosGlobais as [$globalFile, $srcLabel, $baseDir]) {
        if (!is_file($globalFile)) {
            log_disco_local('SCHEMA_META_INFO ' . basename($globalFile) . ' ausente em ' . $globalFile, $LOG_FILE);
            continue;
        }
        $g = jsonRead($globalFile);
        if (!is_array($g) || !isset($g['tabelas']) || !is_array($g['tabelas'])) {
            log_disco_local('SCHEMA_META_WARN ' . basename($globalFile) . ' invalido ou sem chave "tabelas"', $LOG_FILE);
            continue;
        }
        foreach ($g['tabelas'] as $meta) {
            if (!is_array($meta)) continue;
            foreach (normalizarConfigTabela($meta) as $norm) {
                $scope = ($norm['scope_override'] === 'module' && !empty($norm['modulo_override'])) ? 'module' : 'global';
                $modulo = $scope === 'module' ? $norm['modulo_override'] : null;
                $norm['scope'] = $scope;
                $norm['modulo'] = $modulo;
                $norm['base_dir'] = $scope === 'module'
                    ? dirname($baseDir) . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $modulo . DIRECTORY_SEPARATOR . 'resources'
                    : $baseDir;
                $norm['inline'] = $g;
                $out[] = [$norm, $srcLabel];
            }
        }
    }

    // 2) Blocos locais "tabela.config" de cada módulo
    $pastasModulos = [];
    if (!empty($GLOBALS['CLI_ARGS']['project-path'])) {
        $coreModDir = $SYSTEM_PATH . 'gestor' . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR;
        if (is_dir($coreModDir)) {
            $pastasModulos[] = [$coreModDir, 'core:'];
        }
    }
    $pastasModulos[] = [$MODULES_DIR, 'module:'];

    foreach ($pastasModulos as [$mDir, $srcPrefix]) {
        if (!is_dir($mDir)) continue;
        foreach (glob($mDir . '*', GLOB_ONLYDIR) ?: [] as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
            $data = jsonRead($jsonFile);
            if (!is_array($data)) continue;
            $tabela = $data['tabela'] ?? null;
            if (!is_array($tabela) || empty($tabela['config'])) continue;
            foreach (normalizarConfigTabela($tabela) as $norm) {
                $scope = ($norm['scope_override'] === 'global') ? 'global' : 'module';
                $modulo = ($scope === 'module' && !empty($norm['modulo_override'])) ? $norm['modulo_override'] : ($scope === 'module' ? $modId : null);
                $norm['scope'] = $scope;
                $norm['modulo'] = $modulo;
                $norm['base_dir'] = $scope === 'module'
                    ? dirname($mDir) . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $modulo . DIRECTORY_SEPARATOR . 'resources'
                    : dirname($mDir) . DIRECTORY_SEPARATOR . 'resources';
                $norm['inline'] = $data;
                $out[] = [$norm, $srcPrefix . $modId];
            }
        }
    }

    return $out;
}

/**
 * Motor de varredura genérico (Registry Pattern): consolida as regras de sincronização
 * de tabelas a partir do arquivo global tables_config.json e dos blocos "tabela.config"
 * de cada módulo, produzindo o contrato único db/data/schema-metadata.json consumido pelo
 * atualizador (atualizacoes-banco-de-dados.php). Blocos locais de módulo sobrescrevem o global.
 * As listas "deletar" e "forcar_atualizacao" são consolidadas (agregadas) por tabela.
 */
function gerarSchemaMetadata(): void {
    global $DB_DATA_DIR, $LOG_FILE, $RESOURCES_DIR, $GESTOR_DIR;
    $tables = [];
    $deletar = [];
    $forcar = [];

    foreach (coletarConfigsTabelas() as [$norm, $source]) {
        $nome = $norm['nome'];
        $del = $norm['deletar'] ?? [];
        $forc = $norm['forcar_atualizacao'] ?? [];
        // Mantém no contrato apenas os campos consumidos pelo atualizador (descarta contexto
        // de varredura e diretivas de recurso, que são build-time).
        $tables[$nome] = [
            'nome' => $norm['nome'],
            'id' => $norm['id'],
            'id_numerico' => $norm['id_numerico'],
            'data_file' => $norm['data_file'],
            'strategy' => $norm['strategy'],
            'natural_key_columns' => $norm['natural_key_columns'],
            'preserve_on_user_modified' => $norm['preserve_on_user_modified'],
            'insert_only' => $norm['insert_only'],
            'source' => $source,
        ];
        if ($del) { $deletar[$nome] = array_merge($deletar[$nome] ?? [], $del); }
        if ($forc) { $forcar[$nome] = array_merge($forcar[$nome] ?? [], $forc); }
    }

    ksort($tables);
    $contrato = [
        'generated_at' => date('c'),
        'tables' => $tables,
        'deletar' => $deletar,
        'forcar_atualizacao' => $forcar,
    ];
    $dest = $DB_DATA_DIR . 'schema-metadata.json';
    if (jsonWrite($dest, $contrato)) {
        log_disco_local('SCHEMA_METADATA_SAVED ' . $dest . ' tabelas=' . count($tables) . ' deletar=' . count($deletar) . ' forcar=' . count($forcar), $LOG_FILE);
    } else {
        log_disco_local('SCHEMA_METADATA_ERRO ao gravar ' . $dest, $LOG_FILE);
    }

    $projectTablesConfig = rtrim($RESOURCES_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'project_tables_config.json';
    if (!empty($GLOBALS['CLI_ARGS']['project-path']) && is_file($projectTablesConfig)) {
        $projectConfig = jsonRead($projectTablesConfig);
        $projectKeys = (is_array($projectConfig) && isset($projectConfig['tabelas']) && is_array($projectConfig['tabelas']))
            ? array_keys($projectConfig['tabelas'])
            : [];
        $projectTables = array_intersect_key($tables, array_flip($projectKeys));
        $projectDest = rtrim($GESTOR_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'project-schema-metadata.json';
        $projectSchema = ['tabelas' => $projectTables];
        if (jsonWrite($projectDest, $projectSchema)) {
            log_disco_local('PROJECT_SCHEMA_METADATA_SAVED ' . $projectDest . ' tabelas=' . count($projectTables), $LOG_FILE);
        } else {
            log_disco_local('PROJECT_SCHEMA_METADATA_ERRO ao gravar ' . $projectDest, $LOG_FILE);
        }
    }
}

// ========================= 5.2) DATA HOOKS (PÓS-GERAÇÃO) =========================

/**
 * Carrega e executa, em sequência determinística, scripts data-hooks.php globais e locais
 * para tratamento de dados customizados após a geração dos Data.json e do contrato.
 * Hooks globais: resources/data-hooks.php e db/data-hooks.php.
 * Hooks locais: <modulo>/data-hooks.php.
 * Cada hook pode retornar um callable, que é invocado com o contexto de geração.
 */
function executarDataHooks(array $contexto): void {
    global $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $LOG_FILE;
    $globais = [];
    $locais = [];

    foreach ([$RESOURCES_DIR . 'data-hooks.php', rtrim($DB_DATA_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data-hooks.php'] as $g) {
        if (is_file($g)) $globais[] = $g;
    }
    if (is_dir($MODULES_DIR)) {
        foreach (glob($MODULES_DIR . '*', GLOB_ONLYDIR) ?: [] as $modPath) {
            $h = $modPath . DIRECTORY_SEPARATOR . 'data-hooks.php';
            if (is_file($h)) $locais[] = $h;
        }
    }
    sort($globais);
    sort($locais);
    $hooks = array_merge($globais, $locais);
    if (!$hooks) {
        log_disco_local('DATA_HOOKS_NENHUM', $LOG_FILE);
        return;
    }
    foreach ($hooks as $hook) {
        try {
            $fn = include $hook;
            if (is_callable($fn)) { $fn($contexto); }
            log_disco_local('DATA_HOOK_OK ' . $hook, $LOG_FILE);
        } catch (\Throwable $e) {
            log_disco_local('DATA_HOOK_ERRO ' . $hook . ' :: ' . $e->getMessage(), $LOG_FILE);
        }
    }
}

// ========================= 6) MAIN =========================

function main(): void {
    global $LOG_FILE;
    try {
        log_disco_local('Início processo V2', $LOG_FILE);
        $map = carregarMapeamentoGlobal();
        if (empty($GLOBALS['CLI_ARGS']['no-origin-update'])) {
            atualizarArquivosOrigem($map);
        } else {
            log_disco_local('PULANDO atualização de arquivos de origem (--no-origin-update)', $LOG_FILE);
        }
        $exist = carregarDadosExistentes();
        $recursos = coletarRecursos($exist,$map);
        atualizarDados($exist,$recursos);
        // Contrato de sincronização consolidado (Registry Pattern) e hooks pós-geração.
        gerarSchemaMetadata();
        executarDataHooks(['db_data_dir' => $GLOBALS['DB_DATA_DIR'], 'recursos' => $recursos]);
        $erros = validarDuplicidades($recursos);
        reporteFinal($recursos,$erros);
        log_disco_local('Fim processo V2 OK', $LOG_FILE);
    } catch (Throwable $e) {
        $err = 'Erro fatal: '.$e->getMessage();
        log_disco_local($err,$LOG_FILE); echo "❌ $err".PHP_EOL;
    }
}

// Executa (guard permite incluir este arquivo em testes sem disparar a geração completa)
if (!defined('SDD_NO_AUTORUN')) {
    main();
}

?>
