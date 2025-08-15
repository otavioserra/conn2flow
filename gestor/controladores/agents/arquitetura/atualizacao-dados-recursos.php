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
 *  - garantirSeeders (gera seeders se faltarem)
 *  - reporteFinal
 *  - main
 *
 * @version 2.0.0
 * @date 2025-08-15
 */

declare(strict_types=1);

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
$PLUGINS_DIR     = $BASE_PATH . 'gestor-plugins' . DIRECTORY_SEPARATOR;
$DB_DATA_DIR     = $GESTOR_DIR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
$SEEDS_DIR       = $GESTOR_DIR . 'db' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR;
$LOG_DIR         = $GESTOR_DIR . 'logs' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR;
$LOG_FILE        = 'atualizacao-dados-recursos';

if (!is_dir($LOG_DIR)) @mkdir($LOG_DIR, 0775, true);
if (!is_dir($DB_DATA_DIR)) @mkdir($DB_DATA_DIR, 0775, true);
if (!is_dir($SEEDS_DIR)) @mkdir($SEEDS_DIR, 0775, true);

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
 * Compara checksums.
 */
function checksumsEqual(array $a, array $b): bool {
    return ($a['html'] ?? null) === ($b['html'] ?? null)
        && ($a['css'] ?? null) === ($b['css'] ?? null)
        && ($a['combined'] ?? null) === ($b['combined'] ?? null);
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
    log_disco(_('_map_file_not_found', ['file' => $mapFile]), $LOG_FILE);
        throw new RuntimeException('resources.map.php não encontrado');
    }
    $map = include $mapFile; // retorna $resources
    if (!isset($map['languages']) || !is_array($map['languages'])) {
    log_disco(_('_map_invalid_structure'), $LOG_FILE);
        throw new RuntimeException('Estrutura inválida em resources.map.php');
    }
    log_disco(_('_map_loaded', ['langs' => implode(',', array_keys($map['languages']))]), $LOG_FILE);
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
        'layouts'     => $DB_DATA_DIR . 'LayoutsData.json',
        'paginas'     => $DB_DATA_DIR . 'PaginasData.json',
        'componentes' => $DB_DATA_DIR . 'ComponentesData.json',
        'variaveis'   => $DB_DATA_DIR . 'VariaveisData.json',
    ];
    $exist = [];
    foreach ($arquivos as $tipo => $file) {
        $lista = jsonRead($file) ?? [];
        $exist[$tipo] = [];
        foreach ($lista as $r) {
            switch ($tipo) {
                case 'layouts':
                case 'componentes':
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
    global $RESOURCES_DIR, $MODULES_DIR, $PLUGINS_DIR, $LOG_FILE;
    $languages = array_keys($map['languages']);

    $layouts = $paginas = $componentes = $variaveis = [];
    $orphans = [ 'layouts'=>[], 'paginas'=>[], 'componentes'=>[], 'variaveis'=>[] ];

    // Índices de unicidade
    $idxLayouts = [];              // lang|id
    $idxComponentes = [];          // lang|id
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
                foreach (['layouts','components','pages'] as $tipo) {
                    $arr = $res[$tipo] ?? [];
                    foreach ($arr as $item) {
                        $id = $item['id'] ?? null; if(!$id) continue;
                        $paths = resourcePaths($modPath,$lang,$tipo,$id);
                        $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                        if ($tipo==='layouts') {
                            $key = $lang.'|'.$id; if(isset($idxLayouts[$key])) { $orphans['layouts'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxLayouts[$key]=true; [$versao,$cks]=$versaoChecksum('layouts',$key,$html,$css);
                            $layouts[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        } elseif ($tipo==='components') {
                            $key = $lang.'|'.$id; if(isset($idxComponentes[$key])) { $orphans['componentes'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxComponentes[$key]=true; [$versao,$cks]=$versaoChecksum('componentes',$key,$html,$css);
                            $componentes[] = [ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
                        } else { // pages
                            $path = $item['path'] ?? ($id.'/');
                            $kId = $lang.'|'.$modId.'|'.$id; if(isset($idxPaginasId[$kId])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId]; continue; }
                            $kPath = $lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade caminho','language'=>$lang,'modulo'=>$modId]; continue; }
                            $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true; [$versao,$cks]=$versaoChecksum('paginas',$kId,$html,$css);
                            $paginas[] = [ 'layout_id'=>$item['layout'] ?? null,'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'caminho'=>$path,'tipo'=>$item['type'] ?? null,'modulo'=>$modId,'opcao'=>$item['option'] ?? null,'raiz'=>$item['root'] ?? null,'sem_permissao'=>$item['without_permission'] ?? null,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE) ];
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

    // ---------- Plugins (mesma lógica de módulos, caminho diferente) ----------
    if (is_dir($PLUGINS_DIR)) {
        $plugins = glob($PLUGINS_DIR.'*',GLOB_ONLYDIR) ?: [];
        foreach ($plugins as $plugPath) {
            $plugId = basename($plugPath);
            $modsBase = $plugPath.DIRECTORY_SEPARATOR.'local'.DIRECTORY_SEPARATOR.'modulos'.DIRECTORY_SEPARATOR;
            if(!is_dir($modsBase)) continue;
            $mods = glob($modsBase.'*',GLOB_ONLYDIR) ?: [];
            foreach ($mods as $modPath) {
                $modId = basename($modPath);
                $jsonFile = $modPath.DIRECTORY_SEPARATOR.$modId.'.json';
                $data = jsonRead($jsonFile); if(!$data) continue;
                foreach ($languages as $lang) {
                    if(empty($data['resources'][$lang])) continue;
                    $res = $data['resources'][$lang];
                    foreach (['layouts','components','pages'] as $tipo) {
                        $arr = $res[$tipo] ?? [];
                        foreach ($arr as $item) {
                            $id = $item['id'] ?? null; if(!$id) continue;
                            $paths = resourcePaths($modPath,$lang,$tipo,$id);
                            $html = readFileIfExists($paths['html']); $css = readFileIfExists($paths['css']);
                            if ($tipo==='layouts') {
                                $key=$lang.'|'.$id; if(isset($idxLayouts[$key])) { $orphans['layouts'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; }
                                $idxLayouts[$key]=true; [$versao,$cks]=$versaoChecksum('layouts',$key,$html,$css);
                                $layouts[]=[ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE),'plugin'=>$plugId ];
                            } elseif ($tipo==='components') {
                                $key=$lang.'|'.$id; if(isset($idxComponentes[$key])) { $orphans['componentes'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; }
                                $idxComponentes[$key]=true; [$versao,$cks]=$versaoChecksum('componentes',$key,$html,$css);
                                $componentes[]=[ 'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'modulo'=>$modId,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE),'plugin'=>$plugId ];
                            } else { // pages
                                $path = $item['path'] ?? ($id.'/');
                                $kId=$lang.'|'.$modId.'|'.$id; if(isset($idxPaginasId[$kId])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade id','language'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; }
                                $kPath=$lang.'|'.strtolower(trim($path,'/')); if(isset($idxPaginasPath[$kPath])) { $orphans['paginas'][]=$item+['_motivo'=>'duplicidade caminho','language'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; }
                                $idxPaginasId[$kId]=true; $idxPaginasPath[$kPath]=true; [$versao,$cks]=$versaoChecksum('paginas',$kId,$html,$css);
                                $paginas[]=[ 'layout_id'=>$item['layout'] ?? null,'nome'=>$item['name'] ?? $id,'id'=>$id,'language'=>$lang,'caminho'=>$path,'tipo'=>$item['type'] ?? null,'modulo'=>$modId,'opcao'=>$item['option'] ?? null,'raiz'=>$item['root'] ?? null,'sem_permissao'=>$item['without_permission'] ?? null,'html'=>$html,'css'=>$css,'status'=>$item['status'] ?? 'A','versao'=>$versao,'file_version'=>$item['version'] ?? null,'checksum'=>json_encode($cks,JSON_UNESCAPED_UNICODE),'plugin'=>$plugId ];
                            }
                        }
                    }
                    if (!empty($res['variables'])) {
                        foreach ($res['variables'] as $v) {
                            $id = $v['id'] ?? null; if(!$id) continue; $grp = $v['group'] ?? null; $base=$lang.'|'.$modId.'|'.$id;
                            if(!isset($idxVariaveis[$base])) $idxVariaveis[$base]=[]; $groups=$idxVariaveis[$base];
                            if($grp===null || $grp===''){ if(!empty($groups)||in_array('', $groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade sem group','linguagem_codigo'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; } }
                            else { if(in_array($grp,$groups,true)){ $orphans['variaveis'][]=$v+['_motivo'=>'duplicidade group repetido','linguagem_codigo'=>$lang,'modulo'=>$modId,'plugin'=>$plugId]; continue; } }
                            $idxVariaveis[$base][] = ($grp ?? '');
                            $variaveis[]=[ 'linguagem_codigo'=>$lang,'modulo'=>$modId,'id'=>$id,'valor'=>$v['value'] ?? null,'tipo'=>$v['type'] ?? null,'grupo'=>$grp,'descricao'=>$v['description'] ?? null,'plugin'=>$plugId ];
                        }
                    }
                }
            }
        }
    }

    log_disco(_('_collected_summary', [
        'layouts'=>count($layouts), 'pages'=>count($paginas), 'components'=>count($componentes), 'variables'=>count($variaveis)
    ]), $LOG_FILE);

    return [
        'layoutsData'=>$layouts,
        'pagesData'=>$paginas,
        'componentsData'=>$componentes,
        'variablesData'=>$variaveis,
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
    $orphDir = $GESTOR_DIR.'db'.DIRECTORY_SEPARATOR.'orphans'.DIRECTORY_SEPARATOR;
    if(!is_dir($orphDir)) @mkdir($orphDir,0775,true);
    foreach (['Layouts','Paginas','Componentes','Variaveis'] as $T) {
        $k = strtolower($T);
        jsonWrite($orphDir.$T.'Data.json', $recursos['orphans'][$k] ?? []);
    }
    log_disco('Dados persistidos + órfãos.', $LOG_FILE);
}

// ========================= 5) SEEDERS (Garantir) =========================

/**
 * Garante a existência dos seeders padrão sem sobrescrever existentes.
 */
function garantirSeeders(): void {
    global $SEEDS_DIR, $LOG_FILE;
    $defs = [
        ['LayoutsSeeder','layouts','LayoutsData.json'],
        ['PaginasSeeder','paginas','PaginasData.json'],
        ['ComponentesSeeder','componentes','ComponentesData.json'],
        ['VariaveisSeeder','variaveis','VariaveisData.json'],
    ];
    foreach ($defs as [$class,$table,$file]) {
        $path = $SEEDS_DIR.$class.'.php';
        if (file_exists($path)) continue;
        $code = "<?php\n\ndeclare(strict_types=1);\nuse Phinx\\Seed\\AbstractSeed;\nfinal class $class extends AbstractSeed { public function run(): void { $data = json_decode(file_get_contents(__DIR__.'/../data/$file'), true); if(!empty($data)){ $t=$this->table('$table'); if(method_exists($t,'truncate')){ $t->truncate(); } $t->insert($data)->saveData(); } } }\n";
        file_put_contents($path,$code);
        log_disco("Seeder criado: $class", $LOG_FILE);
    }
}

// ========================= 6) REPORTE FINAL =========================

function validarDuplicidades(array $recursos): array {
    $erros = [];
    foreach (['layouts','paginas','componentes','variaveis'] as $t) {
        $q = count($recursos['orphans'][$t] ?? []); if($q>0) $erros[] = "$t: $q órfãos";
    }
    return $erros;
}

/**
 * Aplica os erros diretamente nos arquivos de origem (globais, módulos, plugins).
 * @param array $dupsMeta Lista de duplicados com metadados e mensagem.
 */
function aplicarErrosOrigem(array $dupsMeta, array $originsIndex): void { /* V2: não marca origem, usa órfãos */ }

function reporteFinal(array $recursos, array $erros): void {
    global $LOG_FILE;
    $total = count($recursos['layoutsData']) + count($recursos['pagesData']) + count($recursos['componentsData']) + count($recursos['variablesData']);
    $totalOrphans = 0; foreach ($recursos['orphans'] as $lst) { $totalOrphans += count($lst); }
    $msg = "📝 Relatório Final".PHP_EOL.
           "📦 Layouts: ".count($recursos['layoutsData']).PHP_EOL.
           "📄 Páginas: ".count($recursos['pagesData']).PHP_EOL.
           "🧩 Componentes: ".count($recursos['componentsData']).PHP_EOL.
           "🔧 Variáveis: ".count($recursos['variablesData']).PHP_EOL.
           "Σ TOTAL: $total".PHP_EOL.
           "🗃️ Órfãos: $totalOrphans".PHP_EOL;
    if (!empty($erros)) { $msg .= "⚠️ Problemas: ".implode('; ',$erros).PHP_EOL; }
    else { $msg .= "✅ Nenhum problema de unicidade adicional.".PHP_EOL; }
    log_disco($msg,$LOG_FILE); echo $msg;
}

// ========================= 7) MAIN =========================

function main(): void {
    global $LOG_FILE;
    try {
        log_disco('Início processo V2', $LOG_FILE);
        $map = carregarMapeamentoGlobal();
        $exist = carregarDadosExistentes();
        $recursos = coletarRecursos($exist,$map);
        atualizarDados($exist,$recursos);
        garantirSeeders();
        $erros = validarDuplicidades($recursos);
        reporteFinal($recursos,$erros);
        log_disco('Fim processo V2 OK', $LOG_FILE);
    } catch (Throwable $e) {
        $err = 'Erro fatal: '.$e->getMessage();
        log_disco($err,$LOG_FILE); echo "❌ $err".PHP_EOL;
    }
}

// Executa
main();

?>
