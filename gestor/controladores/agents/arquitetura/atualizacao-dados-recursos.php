<?php
/**
 * Atualização de Dados de Recursos (Layouts, Páginas, Componentes e Variáveis) - Versão 2.0
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
 * @version 2.0.0
 * @date 2025-08-15
 */

declare(strict_types=1);

// Framework CSS default (fase 1 – somente propagação de dados)
const DEFAULT_FRAMEWORK_CSS = 'fomantic-ui';

/**
 * Retorna framework_css do item ou fallback padrão.
 */
function getFrameworkCss(?array $src): string {
    if (!$src) return DEFAULT_FRAMEWORK_CSS;
    $v = $src['framework_css'] ?? null;
    if (is_string($v) && $v !== '') return $v; // aceitamos valor informado
    return DEFAULT_FRAMEWORK_CSS;
}

// ========================= CONFIGURAÇÃO BÁSICA =========================

$BASE_PATH = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR; // raiz do repositório

require_once $BASE_PATH . 'gestor/bibliotecas/lang.php';
// Biblioteca de logs (pode ser adaptada futuramente). Se não existir função, definimos fallback simples.
// Carregar biblioteca de log original
@require_once $BASE_PATH . 'gestor/bibliotecas/log.php';
// Usaremos diretamente log_disco() da biblioteca

// Diretórios centrais
$GESTOR_DIR      = $BASE_PATH . 'gestor' . DIRECTORY_SEPARATOR;
$RESOURCES_DIR   = $GESTOR_DIR . 'resources' . DIRECTORY_SEPARATOR;
$MODULES_DIR     = $GESTOR_DIR . 'modulos' . DIRECTORY_SEPARATOR;
$DB_DATA_DIR     = $GESTOR_DIR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
$LOG_DIR         = $GESTOR_DIR . 'logs' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR;
$LOG_FILE        = 'atualizacao-dados-recursos';

if (!is_dir($LOG_DIR)) @mkdir($LOG_DIR, 0775, true);
if (!is_dir($DB_DATA_DIR)) @mkdir($DB_DATA_DIR, 0775, true);

// Ajustar defaults para biblioteca de log tradicional se necessária
global $_GESTOR;
if (!isset($_GESTOR)) { $_GESTOR = []; }
if (!array_key_exists('debug', $_GESTOR)) { $_GESTOR['debug'] = false; }
if (!array_key_exists('logs-path', $_GESTOR)) { $_GESTOR['logs-path'] = $LOG_DIR; }

// Definir linguagem padrão (pode ser parametrizado futuramente)
set_lang('pt-br');

// ========================= UTILIDADES GENÉRICAS =========================

/**
 * Lê JSON retornando array associativo ou null.
 */
function jsonRead(string $path): ?array {
    if (!file_exists($path)) return null;
    $c = file_get_contents($path);
    $d = json_decode($c, true);
    return is_array($d) ? $d : null;
}

/**
 * Escreve JSON formatado.
 */
function jsonWrite(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Retorna conteúdo do arquivo se existir.
 */
function readFileIfExists(string $path): ?string {
    return file_exists($path) ? file_get_contents($path) : null;
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
        foreach ([ 'layouts'=>'layouts', 'components'=>'components', 'pages'=>'pages' ] as $tipoKey=>$dirName) {
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
                    log_disco("ORIGIN_UPDATE $tipoKey id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
                }
            }
            unset($item);
            if ($changed) {
                jsonWrite($jsonPath,$lista);
                log_disco("ORIGIN_FILE_SAVED $jsonPath", $LOG_FILE);
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
                foreach (['layouts','components','pages'] as $tipo) {
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
                            log_disco("ORIGIN_UPDATE_MODULE modulo=$modId tipo=$tipo id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
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
                            log_disco("ORIGIN_UPDATE_MODULE modulo=$modId tipo=$tipo id=$id lang=$lang version {$oldVersion}=>{$item['version']}", $LOG_FILE);
                        }
                    }
                    unset($item);
                }
            }
            if ($changedModule) {
                jsonWrite($jsonFile,$data);
                log_disco("ORIGIN_FILE_SAVED_MODULE $jsonFile", $LOG_FILE);
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
    log_disco(__t('_map_file_not_found', ['file' => $mapFile]), $LOG_FILE);
        throw new RuntimeException('resources.map.php não encontrado');
    }
    $map = include $mapFile; // retorna $resources
    if (!isset($map['languages']) || !is_array($map['languages'])) {
    log_disco(__t('_map_invalid_structure'), $LOG_FILE);
        throw new RuntimeException('Estrutura inválida em resources.map.php');
    }
    log_disco(__t('_map_loaded', ['langs' => implode(',', array_keys($map['languages']))]), $LOG_FILE);
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
        'layouts'             => $DB_DATA_DIR . 'LayoutsData.json',
        'paginas'             => $DB_DATA_DIR . 'PaginasData.json',
        'componentes'         => $DB_DATA_DIR . 'ComponentesData.json',
        'variaveis'           => $DB_DATA_DIR . 'VariaveisData.json',
        'prompts_ia'          => $DB_DATA_DIR . 'PromptsIaData.json',
        'modos_ia'            => $DB_DATA_DIR . 'ModosIaData.json',
        'alvos_ia'            => $DB_DATA_DIR . 'AlvosIaData.json',
    ];
    $exist = [];
    foreach ($arquivos as $tipo => $file) {
        $lista = jsonRead($file) ?? [];
        $exist[$tipo] = [];
        foreach ($lista as $r) {
            switch ($tipo) {
                case 'layouts':
                case 'componentes':
                case 'prompts_ia':
                case 'modos_ia':
                case 'alvos_ia':
                    if (!isset($r['language'],$r['id'])) continue 2;
                    $k = $r['language'].'|'.$r['id'];
                    break;
                case 'paginas':
                    if (!isset($r['language'],$r['id'])) continue 2;
                    $k = $r['language'].'|'.($r['modulo'] ?? '').'|'.$r['id'];
                    break;
                case 'variaveis':
                    if (!isset($r['linguagem_codigo'],$r['id'])) continue 2;
                    $k = $r['linguagem_codigo'].'|'.($r['modulo'] ?? '').'|'.$r['id'].'|'.($r['grupo'] ?? '');
                    break;
                default: continue 2;
            }
            $exist[$tipo][$k] = $r;
        }
        log_disco("Existentes ($tipo): ".count($exist[$tipo]), $LOG_FILE);
    }
    return $exist;
}

// ========================= 3) COLETAR RECURSOS =========================

/**
 * Coleta recursos aplicando regras de unicidade e separando órfãos.
 */
function coletarRecursos(array $existentes, array $map): array {
    global $RESOURCES_DIR, $MODULES_DIR, $LOG_FILE;
    $languages = array_keys($map['languages']);

    $layouts = $paginas = $componentes = $variaveis = $prompts_ia = $alvos_ia = $modos_ia = [];
    $orphans = [ 'layouts'=>[], 'paginas'=>[], 'componentes'=>[], 'variaveis'=>[], 'prompts_ia'=>[], 'alvos_ia'=>[], 'modos_ia'=>[] ];

    // Índices de unicidade
    $idxLayouts = [];              // lang|id
    $idxComponentes = [];          // lang|id
    $idxPromptsIa = [];            // lang|id
    $idxPromptsAlvosIa = [];       // lang|id
    $idxModosIa = [];              // lang|id
    $idxPaginasId = [];            // lang|mod|id
    $idxPaginasPath = [];          // lang|caminho
    $idxVariaveis = [];            // lang|mod|id => groups[]

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
                $id = $v['id'] ?? null; if(!$id){$orphans['variaveis'][]=$v+['_motivo'=>'sem id','linguagem_codigo'=>$lang];continue;}
                $mod = $v['module'] ?? ($v['modulo'] ?? '');
                $grp = $v['group'] ?? ($v['grupo'] ?? null);
                $base = $lang.'|'.$mod.'|'.$id;
                if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[];
                $groups = $idxVariaveis[$base];
                if($grp===null || $grp==='') { // sem group permitido somente se nenhum group já existe
                    if(!empty($groups) || in_array('', $groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','linguagem_codigo'=>$lang]; continue; }
                } else { // com group
                    if(in_array($grp,$groups,true)) { $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','linguagem_codigo'=>$lang]; continue; }
                }
                $idxVariaveis[$base][] = ($grp ?? '');
                $variaveis[] = [
                    'linguagem_codigo' => $lang,
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
                foreach (['layouts','components','pages','ai_prompts','ai_prompts_targets','ai_modes'] as $tipo) {
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
                            $paginas[] = [ 'layout_id'=>$item['layout'] ?? null,'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'caminho'=>$path,'tipo'=>$item['type'] ?? null,'modulo'=>$modId,'opcao'=>$item['option'] ?? null,'raiz'=>$item['root'] ?? null,'sem_permissao'=>$item['without_permission'] ?? null,'html'=>$html,'css'=>$css,'framework_css'=>getFrameworkCss($item),'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        }
                    }
                }
                if (!empty($res['variables'])) {
                    foreach ($res['variables'] as $v) {
                        $id = $v['id'] ?? null; if(!$id) continue;
                        $grp = $v['group'] ?? null; $base = $lang.'|'.$modId.'|'.$id;
                        if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[];
                        $groups = $idxVariaveis[$base];
                        if($grp===null || $grp==='') { if(!empty($groups) || in_array('', $groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','linguagem_codigo'=>$lang,'modulo'=>$modId]; continue; } }
                        else { if(in_array($grp,$groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','linguagem_codigo'=>$lang,'modulo'=>$modId]; continue; } }
                        $idxVariaveis[$base][] = ($grp ?? '');
                        $variaveis[] = [ 'linguagem_codigo'=>$lang,'modulo'=>$modId,'id'=>$id,'valor'=>$v['value'] ?? null,'tipo'=>$v['type'] ?? null,'grupo'=>$grp,'descricao'=>$v['description'] ?? null ];
                    }
                }
            }
        }
    }

    log_disco(__t('_collected_summary', [
        'layouts'=>count($layouts), 'pages'=>count($paginas), 'components'=>count($componentes), 'variables'=>count($variaveis), 'prompts_ia'=>count($prompts_ia), 'alvos_ia'=>count($alvos_ia), 'modos_ia'=>count($modos_ia)
    ]), $LOG_FILE);

    return [
        'layoutsData'=>$layouts,
        'pagesData'=>$paginas,
        'componentsData'=>$componentes,
        'variablesData'=>$variaveis,
        'promptsData'=>$prompts_ia,
        'modesData'=>$modos_ia,
        'targetsData'=>$alvos_ia,
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
    jsonWrite($DB_DATA_DIR.'VariaveisData.json', $recursos['variablesData']);
    jsonWrite($DB_DATA_DIR.'PromptsIaData.json', $recursos['promptsData']);
    jsonWrite($DB_DATA_DIR.'AlvosIaData.json', $recursos['targetsData']);
    jsonWrite($DB_DATA_DIR.'ModosIaData.json', $recursos['modesData']);
    $orphDir = $GESTOR_DIR.'db'.DIRECTORY_SEPARATOR.'orphans'.DIRECTORY_SEPARATOR;
    if(!is_dir($orphDir)) @mkdir($orphDir,0775,true);
    foreach (['Layouts','Paginas','Componentes','Variaveis','PromptsIa','AlvosIa','ModosIa'] as $T) {
        $k = strtolower($T);
        jsonWrite($orphDir.$T.'Data.json', $recursos['orphans'][$k] ?? []);
    }
    log_disco('Dados persistidos + órfãos.', $LOG_FILE);
}

// ========================= 5) REPORTE FINAL =========================

function validarDuplicidades(array $recursos): array {
    $erros = [];
    foreach (['layouts','paginas','componentes','variaveis','prompts_ia','alvos_ia','modos_ia'] as $t) {
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
    $total = count($recursos['layoutsData']) + count($recursos['pagesData']) + count($recursos['componentsData']) + count($recursos['variablesData']) + count($recursos['promptsData']) + count($recursos['modesData']) + count($recursos['targetsData']);
    $totalOrphans = 0; foreach ($recursos['orphans'] as $lst) { $totalOrphans += count($lst); }
    $msg = "📝 Relatório Final".PHP_EOL.
           "📦 Layouts: ".count($recursos['layoutsData']).PHP_EOL.
           "📄 Páginas: ".count($recursos['pagesData']).PHP_EOL.
           "🧩 Componentes: ".count($recursos['componentsData']).PHP_EOL.
           "🔧 Variáveis: ".count($recursos['variablesData']).PHP_EOL.
           "🤖 Modos IA: ".count($recursos['modesData']).PHP_EOL.
           "💬 Prompts IA: ".count($recursos['promptsData']).PHP_EOL.
           "🎯 Alvos IA: ".count($recursos['targetsData']).PHP_EOL.
           "Σ TOTAL: $total".PHP_EOL.
           "🗃️ Órfãos: $totalOrphans".PHP_EOL;
    if (!empty($erros)) { $msg .= "⚠️ Problemas: ".implode('; ',$erros).PHP_EOL; }
    else { $msg .= "✅ Nenhum problema de unicidade adicional.".PHP_EOL; }
    log_disco($msg,$LOG_FILE); echo $msg;
}

// ========================= 6) MAIN =========================

function main(): void {
    global $LOG_FILE;
    try {
        log_disco('Início processo V2', $LOG_FILE);
        $map = carregarMapeamentoGlobal();
        if (empty($GLOBALS['CLI_ARGS']['no-origin-update'])) {
            atualizarArquivosOrigem($map);
        } else {
            log_disco('PULANDO atualização de arquivos de origem (--no-origin-update)', $LOG_FILE);
        }
        $exist = carregarDadosExistentes();
        $recursos = coletarRecursos($exist,$map);
        atualizarDados($exist,$recursos);
        $erros = validarDuplicidades($recursos);
        reporteFinal($recursos,$erros);
        log_disco('Fim processo V2 OK', $LOG_FILE);
    } catch (Throwable $e) {
        $err = 'Erro fatal: '.$e->getMessage();
        log_disco($err,$LOG_FILE); echo "❌ $err".PHP_EOL;
    }
}

// Executa
// Captura argumentos simples (--chave ou --chave=valor)
$GLOBALS['CLI_ARGS'] = [];
if (PHP_SAPI === 'cli') {
    foreach ($argv as $a) {
        if (preg_match('/^--([^=]+)=(.+)$/',$a,$m)) { $GLOBALS['CLI_ARGS'][$m[1]] = $m[2]; }
        elseif (substr($a,0,2)==='--') { $GLOBALS['CLI_ARGS'][substr($a,2)] = true; }
    }
}
main();

?>
