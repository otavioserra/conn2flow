<?php
/**
 * Atualização de Dados de Recursos (Layouts, Páginas, Componentes e Variáveis)
 *
 * Integra a lógica anteriormente distribuída entre:
 *  - gestor/resources/generate.multilingual.seeders.php (orquestrador)
 *  - ai-workspace/scripts/arquitetura/generate_seeds_data.php (geração layouts/páginas/componentes)
 *  - Recurso de migração de variáveis (recurso-variaveis-criar-origem.php)
 *
 * Agora centraliza em um único script de agente seguindo a estrutura definida no prompt:
 *  carregarMapeamentoGlobal -> carregarDadosExistentes -> coletarRecursos -> atualizarDados -> reporteFinal -> main
 *
 * Regras / Objetivos:
 *  - Manter IDs estáveis dos recursos existentes (id_layouts, id_paginas, id_componentes, id_variaveis)
 *  - Atualizar version/checksum em arquivos de origem (layouts/pages/components) quando HTML/CSS mudarem
 *  - Integrar novo recurso "variaveis" gerando VariaveisData.json a partir de globais/módulos/plugins
 *  - Suporte multilíngue via função _() e logs escritos em disco
 *  - Código modular e bem comentado (DocBlocks)
 *  - Não sobrescrever seeders já existentes (LayoutsSeeder, PaginasSeeder, ComponentesSeeder, VariaveisSeeder)
 *
 * @version 1.0.0
 * @author IA Agent
 * @date 2025-08-12
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

function nowStr(): string { return date('Y-m-d H:i:s'); }

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
 * Carrega dados existentes para manter IDs estáveis.
 * @return array Estrutura padronizada.
 */
function carregarDadosExistentes(): array {
    global $DB_DATA_DIR, $LOG_FILE;
    $paths = [
        'layouts'      => $DB_DATA_DIR . 'LayoutsData.json',
        'paginas'      => $DB_DATA_DIR . 'PaginasData.json',
        'componentes'  => $DB_DATA_DIR . 'ComponentesData.json',
        'variaveis'    => $DB_DATA_DIR . 'VariaveisData.json',
    ];
    $dados = [];
    foreach ($paths as $k => $p) {
        $dados[$k] = jsonRead($p) ?? [];
    log_disco(_('_loaded_existing', ['tipo' => $k, 'qtd' => count($dados[$k])]), $LOG_FILE);
    }
    return $dados;
}

// ========================= 3) COLETAR RECURSOS =========================

/**
 * Coleta, consolida e prepara recursos de layouts/páginas/componentes/variáveis.
 * Retorna estrutura consolidada incluindo informações necessárias para geração dos Data.json.
 */
function coletarRecursos(array $dadosExistentes, array $dadosMapeamentoGlobal): array {
    global $RESOURCES_DIR, $MODULES_DIR, $PLUGINS_DIR, $LOG_FILE;

    $languages = array_keys($dadosMapeamentoGlobal['languages']);

    // Mapas de IDs existentes
    $existingLayouts     = $dadosExistentes['layouts'];
    $existingPages       = $dadosExistentes['paginas'];
    $existingComponents  = $dadosExistentes['componentes'];
    $existingVars        = $dadosExistentes['variaveis'];

    $layoutIdMap = [];
    $nextLayoutId = 1;
    foreach ($existingLayouts as $row) {
        if (isset($row['id'], $row['id_layouts'])) {
            $layoutIdMap[$row['id']] = (int)$row['id_layouts'];
            $nextLayoutId = max($nextLayoutId, (int)$row['id_layouts'] + 1);
        }
    }

    $pageIdMap = [];
    $nextPageId = 1;
    foreach ($existingPages as $row) {
        if (isset($row['id'], $row['language'], $row['id_paginas'])) {
            $key = $row['language'] . '|' . ($row['modulo'] ?? '') . '|' . $row['id'];
            $pageIdMap[$key] = (int)$row['id_paginas'];
            $nextPageId = max($nextPageId, (int)$row['id_paginas'] + 1);
        }
    }

    $compIdMap = [];
    $nextCompId = 1;
    foreach ($existingComponents as $row) {
        if (isset($row['id'], $row['language'], $row['id_componentes'])) {
            $key = $row['language'] . '|' . ($row['modulo'] ?? '') . '|' . $row['id'];
            $compIdMap[$key] = (int)$row['id_componentes'];
            $nextCompId = max($nextCompId, (int)$row['id_componentes'] + 1);
        }
    }

    $varIdMap = [];
    $nextVarId = 1;
    foreach ($existingVars as $row) {
        if (isset($row['id'], $row['linguagem_codigo'], $row['id_variaveis'])) {
            $key = $row['linguagem_codigo'] . '|' . ($row['modulo'] ?? '') . '|' . $row['id'];
            $varIdMap[$key] = (int)$row['id_variaveis'];
            $nextVarId = max($nextVarId, (int)$row['id_variaveis'] + 1);
        }
    }

    $layoutsData = [];
    $pagesData = [];
    $componentsData = [];
    $variablesData = [];

    // Índices de origem para posterior marcação de erros diretamente nos arquivos fonte
    // Cada item conterá metadados suficientes para reabrir o arquivo e adicionar error/error_msg
    $originsIndex = [
        'paginas' => [], // [ ['file'=>..., 'scope'=>global|module|plugin, 'module'=>?, 'plugin'=>?, 'lang'=>..., 'id'=>..., 'path'=>...] ]
        'variaveis' => [], // [ ['file'=>..., 'scope'=>global|module|plugin, 'module'=>?, 'plugin'=>?, 'lang'=>..., 'id'=>..., 'group'=>...] ]
    ];

    $seenLayouts = [];
    $originUpdates = [];

    // ---- Globais (layouts/pages/components) + variáveis globais
    foreach ($languages as $lang) {
        $langInfo = $dadosMapeamentoGlobal['languages'][$lang];
        if (!isset($langInfo['data'])) continue;
        $dataFiles = $langInfo['data'];
        foreach (['layouts' => 'layouts', 'pages' => 'pages', 'components' => 'components'] as $tKey => $fileKey) {
            if (!isset($dataFiles[$fileKey])) continue;
            $listFile = $RESOURCES_DIR . $lang . DIRECTORY_SEPARATOR . $dataFiles[$fileKey];
            $list = jsonRead($listFile) ?? [];
            $changed = false;
            foreach ($list as $idx => $item) {
                $id = $item['id'] ?? null;
                if (!$id) continue;
                $paths = resourcePaths($RESOURCES_DIR, $lang, $fileKey, $id, true);
                $html = readFileIfExists($paths['html']);
                $css  = readFileIfExists($paths['css']);
                $cks = buildChecksum($html, $css);
                applyChecksumAndVersionUpdate($list[$idx], $cks, $originUpdates, "global:$lang:$fileKey");
                if ($fileKey === 'layouts') {
                    if (!isset($layoutIdMap[$id])) $layoutIdMap[$id] = $nextLayoutId++;
                    if (!isset($seenLayouts[$id])) {
                        $layoutsData[] = [
                            'id_layouts' => $layoutIdMap[$id],
                            'id_usuarios' => 1,
                            'nome' => $item['name'] ?? $id,
                            'id' => $id,
                            'language' => $lang,
                            'modulo' => null,
                            'html' => $html,
                            'css' => $css,
                            'status' => 'A',
                            'versao' => 1,
                            'data_criacao' => nowStr(),
                            'data_modificacao' => nowStr(),
                            'user_modified' => 0,
                            'file_version' => $list[$idx]['version'] ?? '1.0',
                            'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                        ];
                        $seenLayouts[$id] = true;
                    }
                } elseif ($fileKey === 'pages') {
                    $k = $lang . '||' . $id; // modulo vazio
                    $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                    $pagesData[] = [
                        'id_paginas' => $pid,
                        'id_usuarios' => 1,
                        'id_layouts' => null,
                        'nome' => $item['name'] ?? $id,
                        'id' => $id,
                        'language' => $lang,
                        'caminho' => $item['path'] ?? ($id . '/'),
                        'tipo' => $item['type'] ?? 'sistema',
                        'modulo' => $item['module'] ?? null,
                        'opcao' => $item['option'] ?? null,
                        'raiz' => isset($item['root']) ? (int)!!$item['root'] : null,
                        'sem_permissao' => isset($item['without_permission']) ? (int)!!$item['without_permission'] : null,
                        'html' => $html,
                        'css' => $css,
                        'status' => 'A',
                        'versao' => 1,
                        'data_criacao' => nowStr(),
                        'data_modificacao' => nowStr(),
                        'user_modified' => 0,
                        'file_version' => $list[$idx]['version'] ?? '1.0',
                        'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                        '_layout_id_str' => $item['layout'] ?? null,
                    ];
                    // Registrar origem página global
                    $originsIndex['paginas'][] = [
                        'file' => $listFile,
                        'scope' => 'global',
                        'module' => null,
                        'plugin' => null,
                        'lang' => $lang,
                        'id' => $id,
                        'path' => $item['path'] ?? ($id . '/'),
                    ];
                } else { // components
                    $k = $lang . '||' . $id;
                    $cid = $compIdMap[$k] ?? ($compIdMap[$k] = $nextCompId++);
                    $componentsData[] = [
                        'id_componentes' => $cid,
                        'id_usuarios' => 1,
                        'nome' => $item['name'] ?? $id,
                        'id' => $id,
                        'language' => $lang,
                        'modulo' => $item['module'] ?? null,
                        'html' => $html,
                        'css' => $css,
                        'status' => 'A',
                        'versao' => 1,
                        'data_criacao' => nowStr(),
                        'data_modificacao' => nowStr(),
                        'user_modified' => 0,
                        'file_version' => $list[$idx]['version'] ?? '1.0',
                        'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
            // Persistir origem se mudou
            jsonWrite($listFile, $list);
        }

        // Variáveis globais (variables.json)
        if (isset($dataFiles['variables'])) {
            $varsFile = $RESOURCES_DIR . $lang . DIRECTORY_SEPARATOR . $dataFiles['variables'];
            $vars = jsonRead($varsFile) ?? [];
            foreach ($vars as $v) {
                $id = $v['id'] ?? null; if (!$id) continue;
                $mod = $v['modulo'] ?? null; // em globais pode existir modulo campo
                $k = $lang . '|' . ($mod ?? '') . '|' . $id;
                $vid = $varIdMap[$k] ?? ($varIdMap[$k] = $nextVarId++);
                $variablesData[] = [
                    'id_variaveis' => (string)$vid,
                    'linguagem_codigo' => $lang,
                    'modulo' => $mod,
                    'id' => $id,
                    'valor' => $v['value'] ?? null,
                    'tipo' => $v['type'] ?? null,
                    'grupo' => $v['group'] ?? null,
                    'descricao' => $v['description'] ?? null,
                ];
                $originsIndex['variaveis'][] = [
                    'file' => $varsFile,
                    'scope' => 'global',
                    'module' => $mod,
                    'plugin' => null,
                    'lang' => $lang,
                    'id' => $id,
                    'group' => $v['group'] ?? null,
                ];
            }
        }
    }

    // ---- Módulos
    if (is_dir($MODULES_DIR)) {
        $mods = glob($MODULES_DIR . '*', GLOB_ONLYDIR) ?: [];
        foreach ($mods as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
            $modData = jsonRead($jsonFile);
            if (!$modData) continue;
            foreach ($languages as $lang) {
                if (!isset($modData['resources'][$lang])) continue;
                // Layouts/Pages/Components
                foreach (['layouts', 'pages', 'components'] as $type) {
                    $arr = $modData['resources'][$lang][$type] ?? [];
                    foreach ($arr as $idx => $item) {
                        $id = $item['id'] ?? null; if (!$id) continue;
                        $paths = resourcePaths($modPath, $lang, $type, $id);
                        $html = readFileIfExists($paths['html']);
                        $css  = readFileIfExists($paths['css']);
                        $cks = buildChecksum($html, $css);
                        applyChecksumAndVersionUpdate($modData['resources'][$lang][$type][$idx], $cks, $originUpdates, "module:$modId:$lang:$type");
                        if ($type === 'layouts') {
                            if (!isset($layoutIdMap[$id])) $layoutIdMap[$id] = $nextLayoutId++;
                            if (!isset($seenLayouts[$id])) {
                                $layoutsData[] = [
                                    'id_layouts' => $layoutIdMap[$id],
                                    'id_usuarios' => 1,
                                    'nome' => $item['name'] ?? $id,
                                    'id' => $id,
                                    'language' => $lang,
                                    'modulo' => $modId,
                                    'html' => $html,
                                    'css' => $css,
                                    'status' => 'A',
                                    'versao' => 1,
                                    'data_criacao' => nowStr(),
                                    'data_modificacao' => nowStr(),
                                    'user_modified' => 0,
                                    'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                    'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                                ];
                                $seenLayouts[$id] = true;
                            }
                        } elseif ($type === 'pages') {
                            $k = $lang . '|' . $modId . '|' . $id;
                            $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                            $pagesData[] = [
                                'id_paginas' => $pid,
                                'id_usuarios' => 1,
                                'id_layouts' => null,
                                'nome' => $item['name'] ?? $id,
                                'id' => $id,
                                'language' => $lang,
                                'caminho' => $item['path'] ?? ($id . '/'),
                                'tipo' => $item['type'] ?? 'sistema',
                                'modulo' => $modId,
                                'opcao' => $item['option'] ?? null,
                                'raiz' => isset($item['root']) ? (int)!!$item['root'] : null,
                                'sem_permissao' => isset($item['without_permission']) ? (int)!!$item['without_permission'] : null,
                                'html' => $html,
                                'css' => $css,
                                'status' => 'A',
                                'versao' => 1,
                                'data_criacao' => nowStr(),
                                'data_modificacao' => nowStr(),
                                'user_modified' => 0,
                                'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                                '_layout_id_str' => $item['layout'] ?? null,
                            ];
                            $originsIndex['paginas'][] = [
                                'file' => $jsonFile,
                                'scope' => 'module',
                                'module' => $modId,
                                'plugin' => null,
                                'lang' => $lang,
                                'id' => $id,
                                'path' => $item['path'] ?? ($id . '/'),
                            ];
                        } else { // components
                            $k = $lang . '|' . $modId . '|' . $id;
                            $cid = $compIdMap[$k] ?? ($compIdMap[$k] = $nextCompId++);
                            $componentsData[] = [
                                'id_componentes' => $cid,
                                'id_usuarios' => 1,
                                'nome' => $item['name'] ?? $id,
                                'id' => $id,
                                'language' => $lang,
                                'modulo' => $modId,
                                'html' => $html,
                                'css' => $css,
                                'status' => 'A',
                                'versao' => 1,
                                'data_criacao' => nowStr(),
                                'data_modificacao' => nowStr(),
                                'user_modified' => 0,
                                'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                            ];
                        }
                    }
                }
                // Variáveis de módulo
                if (isset($modData['resources'][$lang]['variables'])) {
                    foreach ($modData['resources'][$lang]['variables'] as $v) {
                        $id = $v['id'] ?? null; if (!$id) continue;
                        $k = $lang . '|' . $modId . '|' . $id;
                        $vid = $varIdMap[$k] ?? ($varIdMap[$k] = $nextVarId++);
                        $variablesData[] = [
                            'id_variaveis' => (string)$vid,
                            'linguagem_codigo' => $lang,
                            'modulo' => $modId,
                            'id' => $id,
                            'valor' => $v['value'] ?? null,
                            'tipo' => $v['type'] ?? null,
                            'grupo' => $v['group'] ?? null,
                            'descricao' => $v['description'] ?? null,
                        ];
                        $originsIndex['variaveis'][] = [
                            'file' => $jsonFile,
                            'scope' => 'module',
                            'module' => $modId,
                            'plugin' => null,
                            'lang' => $lang,
                            'id' => $id,
                            'group' => $v['group'] ?? null,
                        ];
                    }
                }
            }
            // Persistir se versões/cks alterados
            jsonWrite($jsonFile, $modData);
        }
    }

    // ---- Plugins
    if (is_dir($PLUGINS_DIR)) {
        $plugins = glob($PLUGINS_DIR . '*', GLOB_ONLYDIR) ?: [];
        foreach ($plugins as $plugPath) {
            $plugId = basename($plugPath);
            $modsBase = $plugPath . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR;
            if (!is_dir($modsBase)) continue;
            $plugMods = glob($modsBase . '*', GLOB_ONLYDIR) ?: [];
            foreach ($plugMods as $modPath) {
                $modId = basename($modPath);
                $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
                $modData = jsonRead($jsonFile);
                if (!$modData) continue;
                foreach ($languages as $lang) {
                    if (!isset($modData['resources'][$lang])) continue;
                    foreach (['layouts', 'pages', 'components'] as $type) {
                        $arr = $modData['resources'][$lang][$type] ?? [];
                        foreach ($arr as $idx => $item) {
                            $id = $item['id'] ?? null; if (!$id) continue;
                            $paths = resourcePaths($modPath, $lang, $type, $id);
                            $html = readFileIfExists($paths['html']);
                            $css  = readFileIfExists($paths['css']);
                            $cks = buildChecksum($html, $css);
                            applyChecksumAndVersionUpdate($modData['resources'][$lang][$type][$idx], $cks, $originUpdates, "plugin:$plugId:$modId:$lang:$type");
                            if ($type === 'layouts') {
                                if (!isset($layoutIdMap[$id])) $layoutIdMap[$id] = $nextLayoutId++;
                                if (!isset($seenLayouts[$id])) {
                                    $layoutsData[] = [
                                        'id_layouts' => $layoutIdMap[$id],
                                        'id_usuarios' => 1,
                                        'nome' => $item['name'] ?? $id,
                                        'id' => $id,
                                        'language' => $lang,
                                        'modulo' => $modId,
                                        'html' => $html,
                                        'css' => $css,
                                        'status' => 'A',
                                        'versao' => 1,
                                        'data_criacao' => nowStr(),
                                        'data_modificacao' => nowStr(),
                                        'user_modified' => 0,
                                        'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                        'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                                    ];
                                    $seenLayouts[$id] = true;
                                }
                            } elseif ($type === 'pages') {
                                $k = $lang . '|' . $modId . '|' . $id;
                                $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                                $pagesData[] = [
                                    'id_paginas' => $pid,
                                    'id_usuarios' => 1,
                                    'id_layouts' => null,
                                    'nome' => $item['name'] ?? $id,
                                    'id' => $id,
                                    'language' => $lang,
                                    'caminho' => $item['path'] ?? ($id . '/'),
                                    'tipo' => $item['type'] ?? 'sistema',
                                    'modulo' => $modId,
                                    'opcao' => $item['option'] ?? null,
                                    'raiz' => isset($item['root']) ? (int)!!$item['root'] : null,
                                    'sem_permissao' => isset($item['without_permission']) ? (int)!!$item['without_permission'] : null,
                                    'html' => $html,
                                    'css' => $css,
                                    'status' => 'A',
                                    'versao' => 1,
                                    'data_criacao' => nowStr(),
                                    'data_modificacao' => nowStr(),
                                    'user_modified' => 0,
                                    'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                    'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                                    '_layout_id_str' => $item['layout'] ?? null,
                                ];
                                $originsIndex['paginas'][] = [
                                    'file' => $jsonFile,
                                    'scope' => 'plugin',
                                    'module' => $modId,
                                    'plugin' => $plugId,
                                    'lang' => $lang,
                                    'id' => $id,
                                    'path' => $item['path'] ?? ($id . '/'),
                                ];
                            } else { // components
                                $k = $lang . '|' . $modId . '|' . $id;
                                $cid = $compIdMap[$k] ?? ($compIdMap[$k] = $nextCompId++);
                                $componentsData[] = [
                                    'id_componentes' => $cid,
                                    'id_usuarios' => 1,
                                    'nome' => $item['name'] ?? $id,
                                    'id' => $id,
                                    'language' => $lang,
                                    'modulo' => $modId,
                                    'html' => $html,
                                    'css' => $css,
                                    'status' => 'A',
                                    'versao' => 1,
                                    'data_criacao' => nowStr(),
                                    'data_modificacao' => nowStr(),
                                    'user_modified' => 0,
                                    'file_version' => $modData['resources'][$lang][$type][$idx]['version'] ?? '1.0',
                                    'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE),
                                ];
                            }
                        }
                    }
                    // Variáveis plugin
                    if (isset($modData['resources'][$lang]['variables'])) {
                        foreach ($modData['resources'][$lang]['variables'] as $v) {
                            $id = $v['id'] ?? null; if (!$id) continue;
                            $k = $lang . '|' . $modId . '|' . $id;
                            $vid = $varIdMap[$k] ?? ($varIdMap[$k] = $nextVarId++);
                            $variablesData[] = [
                                'id_variaveis' => (string)$vid,
                                'linguagem_codigo' => $lang,
                                'modulo' => $modId,
                                'id' => $id,
                                'valor' => $v['value'] ?? null,
                                'tipo' => $v['type'] ?? null,
                                'grupo' => $v['group'] ?? null,
                                'descricao' => $v['description'] ?? null,
                            ];
                            $originsIndex['variaveis'][] = [
                                'file' => $jsonFile,
                                'scope' => 'plugin',
                                'module' => $modId,
                                'plugin' => $plugId,
                                'lang' => $lang,
                                'id' => $id,
                                'group' => $v['group'] ?? null,
                            ];
                        }
                    }
                }
                jsonWrite($jsonFile, $modData);
            }
        }
    }

    // Resolver id_layouts nas páginas
    foreach ($pagesData as &$p) {
        if (!empty($p['_layout_id_str'])) {
            $lid = $p['_layout_id_str'];
            $p['id_layouts'] = $layoutIdMap[$lid] ?? null;
        }
        unset($p['_layout_id_str']);
    }
    unset($p);

    log_disco(_('_collected_summary', [
        'layouts' => count($layoutsData),
        'pages' => count($pagesData),
        'components' => count($componentsData),
        'variables' => count($variablesData),
    ]), $LOG_FILE);

    return [
        'layoutsData' => $layoutsData,
        'pagesData' => $pagesData,
        'componentsData' => $componentsData,
        'variablesData' => $variablesData,
        'originsIndex' => $originsIndex,
    ];
}

// ========================= 4) ATUALIZAR DADOS (Persistir) =========================

/**
 * Persiste os Data.json finais.
 */
function atualizarDados(array $dadosExistentes, array $recursos): void {
    global $DB_DATA_DIR, $LOG_FILE;
    jsonWrite($DB_DATA_DIR . 'LayoutsData.json', $recursos['layoutsData']);
    jsonWrite($DB_DATA_DIR . 'PaginasData.json', $recursos['pagesData']);
    jsonWrite($DB_DATA_DIR . 'ComponentesData.json', $recursos['componentsData']);
    jsonWrite($DB_DATA_DIR . 'VariaveisData.json', $recursos['variablesData']);
    log_disco(_('_data_written'), $LOG_FILE);
}

// ========================= 5) SEEDERS (Garantir) =========================

/**
 * Garante a existência dos seeders padrão sem sobrescrever existentes.
 */
function garantirSeeders(): void {
    global $SEEDS_DIR, $LOG_FILE;
    $defs = [
        ['LayoutsSeeder', 'layouts', 'LayoutsData.json', 'layouts'],
        ['PaginasSeeder', 'paginas', 'PaginasData.json', 'paginas'],
        ['ComponentesSeeder', 'componentes', 'ComponentesData.json', 'componentes'],
        ['VariaveisSeeder', 'variaveis', 'VariaveisData.json', 'variaveis'],
    ];
    foreach ($defs as [$class, $table, $dataFile, $tag]) {
        $path = $SEEDS_DIR . $class . '.php';
        if (file_exists($path)) {
            log_disco(_('_seeder_exists', ['seeder' => $class]), $LOG_FILE);
            continue;
        }
        $code = "<?php\n\ndeclare(strict_types=1);\n\nuse Phinx\\Seed\\AbstractSeed;\n\nfinal class $class extends AbstractSeed\n{\n    public function run(): void\n    {\n        $data = json_decode(file_get_contents(__DIR__ . '/../data/$dataFile'), true);\n        if (count($data) > 0) {\n            $table = $this->table('$table');\n            if (method_exists($table, 'truncate')) {\n                $table->truncate();\n            }\n            $table->insert($data)->saveData();\n        }\n    }\n}\n";
        file_put_contents($path, $code);
    log_disco(_('_seeder_created', ['seeder' => $class]), $LOG_FILE);
    }
}

// ========================= 6) REPORTE FINAL =========================

/**
 * Valida duplicidades sem alterar Data.json; retorna relatório e metadados para marcar origem.
 * Regras:
 *  - Páginas: duplicidade de id (mesmo lang+modulo) ou caminho (mesmo lang independente de módulo) => erro.
 *  - Variáveis: duplicidade de id (mesmo lang+modulo) É PERMITIDA se TODOS os registros desse conjunto tiverem 'group' definido E existirem múltiplos valores distintos de group.
 */
function validarDuplicidades(array $recursos): array {
    $report = [];
    $dupsMeta = [];

    // Páginas (por id dentro de lang+mod) & caminho por lang
    $byPageId = [];
    $byPath = [];
    foreach ($recursos['pagesData'] as $p) {
        $lang = $p['language']; $mod = $p['modulo'] ?? ''; $id = $p['id']; $path = $p['caminho'];
        $kId = $lang . '|' . $mod . '|' . $id;
        $byPageId[$kId][] = $p;
        $kPath = $lang . '|' . strtolower(trim($path, '/'));
        $byPath[$kPath][] = $p;
    }
    foreach ($byPageId as $k => $list) {
        if (count($list) > 1) {
            $id = $list[0]['id'];
            $report['paginas']['id'][] = $id;
            foreach ($list as $item) {
                $dupsMeta[] = [
                    'tipo' => 'paginas',
                    'campo' => 'id',
                    'id' => $item['id'],
                    'path' => $item['caminho'],
                    'lang' => $item['language'],
                    'module' => $item['modulo'] ?? null,
                    'msg' => _("dup_pages_id", ['id' => $item['id']]),
                ];
            }
        }
    }
    foreach ($byPath as $k => $list) {
        if (count($list) > 1) {
            $path = $list[0]['caminho'];
            $report['paginas']['caminho'][] = $path;
            foreach ($list as $item) {
                $dupsMeta[] = [
                    'tipo' => 'paginas',
                    'campo' => 'caminho',
                    'id' => $item['id'],
                    'path' => $item['caminho'],
                    'lang' => $item['language'],
                    'module' => $item['modulo'] ?? null,
                    'msg' => _("dup_pages_path", ['path' => $item['caminho']]),
                ];
            }
        }
    }

    // Variáveis (agrupadas por lang+module+id)
    $byVar = [];
    foreach ($recursos['variablesData'] as $v) {
        $k = $v['linguagem_codigo'] . '|' . ($v['modulo'] ?? '') . '|' . $v['id'];
        $byVar[$k][] = $v;
    }
    foreach ($byVar as $k => $list) {
        if (count($list) > 1) {
            $groups = [];
            $allHaveGroup = true;
            foreach ($list as $item) {
                $g = $item['grupo'] ?? null;
                if ($g === null || $g === '') { $allHaveGroup = false; }
                $groups[$g ?? ''] = true;
            }
            $distinctGroups = count($groups);
            // Permitido se todos têm group e groups > 1
            if (!($allHaveGroup && $distinctGroups > 1)) {
                $id = $list[0]['id'];
                $report['variaveis']['id'][] = $id;
                foreach ($list as $item) {
                    $dupsMeta[] = [
                        'tipo' => 'variaveis',
                        'campo' => 'id',
                        'id' => $item['id'],
                        'group' => $item['grupo'] ?? null,
                        'lang' => $item['linguagem_codigo'],
                        'module' => $item['modulo'] ?? null,
                        'msg' => _("dup_vars_id", ['id' => $item['id']]),
                    ];
                }
            }
        }
    }

    return ['report' => $report, 'meta' => $dupsMeta];
}

/**
 * Aplica os erros diretamente nos arquivos de origem (globais, módulos, plugins).
 * @param array $dupsMeta Lista de duplicados com metadados e mensagem.
 */
function aplicarErrosOrigem(array $dupsMeta, array $originsIndex): void {
    // Indexar por (lang,module,id,path,group)
    $indexPag = [];
    foreach ($originsIndex['paginas'] as $o) {
        $key = 'paginas|' . $o['lang'] . '|' . ($o['module'] ?? '') . '|' . $o['id'] . '|' . strtolower(trim($o['path'], '/'));
        $indexPag[$key][] = $o;
    }
    $indexVar = [];
    foreach ($originsIndex['variaveis'] as $o) {
        $key = 'variaveis|' . $o['lang'] . '|' . ($o['module'] ?? '') . '|' . $o['id'] . '|' . ($o['group'] ?? '');
        $indexVar[$key][] = $o;
    }
    // Agrupar duplicados por arquivo para minimizar IO
    $filesToUpdate = [];
    foreach ($dupsMeta as $d) {
        // Determinar candidatos pelo tipo
        if ($d['tipo'] === 'paginas') {
            // Procurar todas as origens possíveis com mesmo lang+module+id OR mesmo path
            foreach ($originsIndex['paginas'] as $o) {
                $match = ($o['lang'] === $d['lang'] && $o['id'] === $d['id']);
                if ($d['campo'] === 'caminho') {
                    $match = $match || ($o['lang'] === $d['lang'] && strtolower(trim($o['path'],'/')) === strtolower(trim($d['path'],'/')));
                }
                if ($match) {
                    $filesToUpdate[$o['file']][] = ['tipo'=>'paginas','lang'=>$o['lang'],'id'=>$o['id'],'path'=>$o['path'],'msg'=>$d['msg']];
                }
            }
        } elseif ($d['tipo'] === 'variaveis') {
            foreach ($originsIndex['variaveis'] as $o) {
                if ($o['lang'] === $d['lang'] && $o['id'] === $d['id'] && (($o['module'] ?? null) === ($d['module'] ?? null))) {
                    $filesToUpdate[$o['file']][] = ['tipo'=>'variaveis','lang'=>$o['lang'],'id'=>$o['id'],'group'=>$o['group'],'msg'=>$d['msg']];
                }
            }
        }
    }
    foreach ($filesToUpdate as $file => $items) {
        $json = jsonRead($file);
        if (!$json) continue;
        $modified = false;
        // Detectar se é arquivo de módulo/plugin (tem 'resources') ou lista global (array simples)
        if (isset($json['resources'])) {
            // Módulo/Plugin
            foreach ($json['resources'] as $lang => &$res) {
                foreach (['pages','variables'] as $t) {
                    if (!isset($res[$t]) || !is_array($res[$t])) continue;
                    foreach ($res[$t] as &$entry) {
                        foreach ($items as $it) {
                            if ($t === 'pages' && $it['tipo'] === 'paginas' && $lang === $it['lang'] && $entry['id'] === $it['id']) {
                                if (empty($entry['error'])) { $entry['error'] = true; $entry['error_msg'] = $it['msg']; $modified = true; }
                            } elseif ($t === 'variables' && $it['tipo'] === 'variaveis' && $lang === $it['lang'] && $entry['id'] === $it['id']) {
                                if (empty($entry['error'])) { $entry['error'] = true; $entry['error_msg'] = $it['msg']; $modified = true; }
                            }
                        }
                    }
                    unset($entry);
                }
                unset($res);
            }
            if ($modified) jsonWrite($file, $json);
        } elseif (is_array($json)) {
            // Lista global (pages.json ou variables.json)
            foreach ($json as &$entry) {
                if (!is_array($entry) || !isset($entry['id'])) continue;
                foreach ($items as $it) {
                    if ($it['tipo'] === 'paginas' && isset($entry['path'])) {
                        if ($entry['id'] === $it['id'] || strtolower(trim($entry['path'],'/')) === strtolower(trim($it['path'],'/'))) {
                            if (empty($entry['error'])) { $entry['error'] = true; $entry['error_msg'] = $it['msg']; $modified = true; }
                        }
                    } elseif ($it['tipo'] === 'variaveis' && isset($entry['value'])) {
                        if ($entry['id'] === $it['id']) {
                            if (empty($entry['error'])) { $entry['error'] = true; $entry['error_msg'] = $it['msg']; $modified = true; }
                        }
                    }
                }
            }
            unset($entry);
            if ($modified) jsonWrite($file, $json);
        }
    }
}

function reporteFinal(array $recursos, array $erros, array $alteracoesOrigem = []): void {
    global $LOG_FILE;
    $total = count($recursos['layoutsData']) + count($recursos['pagesData']) + count($recursos['componentsData']) + count($recursos['variablesData']);
    $msg = "📝 " . _('_final_report') . PHP_EOL
        . str_repeat('═', 50) . PHP_EOL
        . "📦 Layouts: " . count($recursos['layoutsData']) . PHP_EOL
        . "📄 Páginas: " . count($recursos['pagesData']) . PHP_EOL
        . "🧩 Componentes: " . count($recursos['componentsData']) . PHP_EOL
        . "🔧 Variáveis: " . count($recursos['variablesData']) . PHP_EOL
        . "Σ TOTAL: $total" . PHP_EOL;
    if (!empty($alteracoesOrigem)) {
        $msg .= PHP_EOL . "✅ Arquivos origem atualizados:" . PHP_EOL;
        foreach ($alteracoesOrigem as $k => $q) { $msg .= "  - $k => $q" . PHP_EOL; }
    }
    if (!empty($erros)) {
        $msg .= PHP_EOL . _("dup_section_header") . PHP_EOL;
        foreach ($erros as $tipo => $grupos) {
            foreach ($grupos as $campo => $lista) {
                $msg .= '  ' . _("dup_section_item", [
                    'tipo' => $tipo,
                    'campo' => $campo,
                    'lista' => implode(', ', array_unique($lista))
                ]) . PHP_EOL;
            }
        }
    } else {
        $msg .= PHP_EOL . _("dup_section_none") . PHP_EOL;
    }
    log_disco($msg, $LOG_FILE);
    echo $msg;
}

// ========================= 7) MAIN =========================

function main(): void {
    try {
        log_disco(_('_process_start'), 'atualizacao-dados-recursos');
        $map = carregarMapeamentoGlobal();
        $existentes = carregarDadosExistentes();
        $recursos = coletarRecursos($existentes, $map); // inclui originsIndex
        $resultadoDup = validarDuplicidades($recursos);
        aplicarErrosOrigem($resultadoDup['meta'], $recursos['originsIndex']);
        // Remover estrutura de origem antes de persistir
        unset($recursos['originsIndex']);
        atualizarDados($existentes, $recursos); // grava Data.json SEM error/error_msg
        garantirSeeders();
        reporteFinal($recursos, $resultadoDup['report']);
        log_disco(_('_process_end_success'), 'atualizacao-dados-recursos');
    } catch (Throwable $e) {
        log_disco(_('_process_error', ['msg' => $e->getMessage()]), 'atualizacao-dados-recursos');
        echo 'Erro: ' . $e->getMessage();
    }
}

// Executa
main();

?>
