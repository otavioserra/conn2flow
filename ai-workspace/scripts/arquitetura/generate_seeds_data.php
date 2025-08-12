<?php
/**
 * Gera os arquivos JSON de dados dos seeders (LayoutsData.json, PaginasData.json, ComponentesData.json)
 * a partir da nova estrutura de recursos (globais, módulos e módulos de plugins),
 * recalculando checksums e atualizando versão/checksum nos JSONs de origem quando houver mudança.
 *
 * Fontes:
 * - Globais: gestor/resources/resources.map.php -> languages[*].data {layouts,pages,components}
 * - Módulos: gestor/modulos/{module}/{module}.json -> resources.{lang}.{layouts|pages|components}
 * - Plugins: gestor-plugins/{plugin}/local/modulos/{module}/{module}.json -> resources.{lang}.{...}
 *
 * Saídas:
 * - gestor/db/data/LayoutsData.json
 * - gestor/db/data/PaginasData.json
 * - gestor/db/data/ComponentesData.json
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "=== GERADOR DE DADOS PARA SEEDERS (Nova Estrutura) ===\n\n";

$root = realpath(__DIR__ . '/../../../'); // .../conn2flow
$gestor = $root . DIRECTORY_SEPARATOR . 'gestor';
$resourcesDir = $gestor . DIRECTORY_SEPARATOR . 'resources';
$modulesDir = $gestor . DIRECTORY_SEPARATOR . 'modulos';
$pluginsDir = $root . DIRECTORY_SEPARATOR . 'gestor-plugins';
$dbDataDir = $gestor . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data';

if (!is_dir($dbDataDir)) {
    mkdir($dbDataDir, 0755, true);
}

// Utilidades
function readFileIfExists($path) {
    return file_exists($path) ? file_get_contents($path) : null;
}

function jsonRead($path) {
    if (!file_exists($path)) return null;
    $s = file_get_contents($path);
    $d = json_decode($s, true);
    return is_array($d) ? $d : null;
}

function jsonWrite($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function checksumForContent($content) {
    // Se não houver conteúdo, por padrão manter vazio para não forçar bump indevido
    if ($content === null || $content === '') return '';
    return md5($content);
}

function buildChecksum($html, $css) {
    $h = checksumForContent($html);
    $c = checksumForContent($css);
    $combined = ($h === '' && $c === '') ? '' : md5(($html ?? '') . ($css ?? ''));
    return ['html' => $h, 'css' => $c, 'combined' => $combined];
}

function incrementVersionStr($v) {
    if (!$v) return '1.0';
    $parts = explode('.', (string)$v);
    if (count($parts) === 2 && ctype_digit((string)$parts[1])) {
        $parts[1] = (string)((int)$parts[1] + 1);
        return implode('.', $parts);
    }
    return '1.0';
}

function checksumsEqual($a, $b) {
    return isset($a['html'], $a['css'], $a['combined'], $b['html'], $b['css'], $b['combined'])
        && $a['html'] === $b['html'] && $a['css'] === $b['css'] && $a['combined'] === $b['combined'];
}

function translateTypePT($type) {
    if (!$type) return 'sistema';
    $map = ['system' => 'sistema', 'page' => 'paginas'];
    return $map[$type] ?? $type;
}

function nowStr() {
    return date('Y-m-d H:i:s');
}

// Carregar mapeamento principal de idiomas e data files
$resourcesMapFile = $resourcesDir . DIRECTORY_SEPARATOR . 'resources.map.php';
if (!file_exists($resourcesMapFile)) {
    echo "ERRO: Não encontrei resources.map.php em $resourcesMapFile\n";
    exit(1);
}
$resourcesMap = include $resourcesMapFile; // retorna $resources
if (!isset($resourcesMap['languages']) || !is_array($resourcesMap['languages'])) {
    echo "ERRO: Estrutura de languages ausente em resources.map.php\n";
    exit(1);
}

$languages = array_keys($resourcesMap['languages']);
echo "Idiomas: " . implode(', ', $languages) . "\n\n";

// Carregar dados existentes (para manter IDs estáveis)
$layoutsPath = $dbDataDir . DIRECTORY_SEPARATOR . 'LayoutsData.json';
$pagesPath   = $dbDataDir . DIRECTORY_SEPARATOR . 'PaginasData.json';
$compsPath   = $dbDataDir . DIRECTORY_SEPARATOR . 'ComponentesData.json';

$existingLayouts = jsonRead($layoutsPath) ?? [];
$existingPages   = jsonRead($pagesPath) ?? [];
$existingComps   = jsonRead($compsPath) ?? [];

$layoutsData = [];
$pagesData = [];
$componentsData = [];

// Para resolver id_layouts, manter um mapa id string -> id numérico
$layoutIdMap = [];
// Pré-popular mapa de layouts com dados existentes (id string -> id_layouts)
foreach ($existingLayouts as $row) {
    if (!empty($row['id']) && isset($row['id_layouts'])) {
        $layoutIdMap[$row['id']] = (int)$row['id_layouts'];
    }
}
// Próximo ID disponível para layouts
$nextLayoutId = empty($existingLayouts) ? 1 : (max(array_map(fn($r)=> (int)$r['id_layouts'], $existingLayouts)) + 1);

// Páginas e Componentes: mapas compostos por (language|modulo|id)
function pageKey($lang, $mod, $id) { return $lang . '|' . ($mod ?? '') . '|' . $id; }
function compKey($lang, $mod, $id) { return $lang . '|' . ($mod ?? '') . '|' . $id; }

$pageIdMap = [];
foreach ($existingPages as $row) {
    if (!empty($row['id']) && !empty($row['language']) && isset($row['id_paginas'])) {
        $k = pageKey($row['language'], $row['modulo'] ?? null, $row['id']);
        $pageIdMap[$k] = (int)$row['id_paginas'];
    }
}
$nextPageId = empty($existingPages) ? 1 : (max(array_map(fn($r)=> (int)$r['id_paginas'], $existingPages)) + 1);

$compIdMap = [];
foreach ($existingComps as $row) {
    if (!empty($row['id']) && !empty($row['language']) && isset($row['id_componentes'])) {
        $k = compKey($row['language'], $row['modulo'] ?? null, $row['id']);
        $compIdMap[$k] = (int)$row['id_componentes'];
    }
}
$nextCompId = empty($existingComps) ? 1 : (max(array_map(fn($r)=> (int)$r['id_componentes'], $existingComps)) + 1);

// Conjunto para evitar duplicar layouts por 'id' apenas dentro desta execução
$seenLayouts = [];

// Também rastrear mudanças aplicadas nos JSONs de origem
$originUpdates = [];

// Função para atualizar versão/checksum dentro de um item e sinalizar se houve mudança
function applyChecksumAndVersionUpdate(array &$item, array $newChecksum, array &$updates, $originKey) {
    $oldChecksum = $item['checksum'] ?? ['html' => '', 'css' => '', 'combined' => ''];
    $oldVersion = $item['version'] ?? '1.0';
    if (!checksumsEqual($oldChecksum, $newChecksum)) {
        $item['checksum'] = $newChecksum;
        $item['version'] = incrementVersionStr($oldVersion);
        $updates[$originKey] = ($updates[$originKey] ?? 0) + 1;
        return true;
    }
    return false;
}

// Helpers de caminho de recurso
function resourcePaths($base, $language, $typeKey, $resId, $baseIsResourcesDir = false) {
    // Base global já é .../gestor/resources; módulos/plugins usam .../{mod}/resources
    $baseDir = $baseIsResourcesDir ? $base : ($base . DIRECTORY_SEPARATOR . 'resources');
    $dir = $baseDir . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $typeKey . DIRECTORY_SEPARATOR . $resId;
    return [
        'dir' => $dir,
        'html' => $dir . DIRECTORY_SEPARATOR . $resId . '.html',
        'css'  => $dir . DIRECTORY_SEPARATOR . $resId . '.css',
    ];
}

// Persistir JSON de origem com segurança
function persistOriginJson($path, $data) {
    // manter pretty/Unicode
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// Coletar recursos globais por idioma
foreach ($languages as $lang) {
    echo "Processando idioma: $lang\n";
    $langInfo = $resourcesMap['languages'][$lang];
    if (!isset($langInfo['data'])) {
        echo "  - Sem 'data' definido para $lang, pulando globais.\n";
    } else {
        foreach (['layouts' => 'layouts', 'pages' => 'pages', 'components' => 'components'] as $typePt => $typeEn) {
            if (!isset($langInfo['data'][$typeEn])) continue;
            $listFile = $resourcesDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $langInfo['data'][$typeEn];
            $list = jsonRead($listFile) ?? [];
            $changed = false;

            foreach ($list as $idx => $item) {
                $id = $item['id'] ?? null;
                if (!$id) continue;
                $paths = resourcePaths($resourcesDir, $lang, $typeEn, $id, true);
                $html = readFileIfExists($paths['html']);
                $css  = readFileIfExists($paths['css']);
                $newCks = buildChecksum($html, $css);
                $originKey = "global:$lang:$typeEn";
                if (applyChecksumAndVersionUpdate($list[$idx], $newCks, $originUpdates, $originKey)) {
                    $changed = true;
                }

                // Montar linhas para Data.json
                if ($typeEn === 'layouts') {
                    if (!isset($layoutIdMap[$id])) {
                        $layoutIdMap[$id] = $nextLayoutId++;
                    }
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
                            'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                        ];
                        $seenLayouts[$id] = true;
                    }
                } elseif ($typeEn === 'pages') {
                    $k = pageKey($lang, $item['module'] ?? null, $id);
                    $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                    $pagesData[] = [
                        'id_paginas' => $pid,
                        'id_usuarios' => 1,
                        'id_layouts' => null, // resolver depois, quando mapa de layouts estiver completo
                        'nome' => $item['name'] ?? $id,
                        'id' => $id,
                        'language' => $lang,
                        'caminho' => $item['path'] ?? ($id . '/'),
                        'tipo' => translateTypePT($item['type'] ?? 'sistema'),
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
                        'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                        '_layout_id_str' => $item['layout'] ?? null,
                    ];
                } else { // components
                    $k = compKey($lang, $item['module'] ?? null, $id);
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
                        'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }

            if ($changed) {
                persistOriginJson($listFile, $list);
                echo "  - Atualizado $typeEn.json ($lang) com novas versões/checksums\n";
            }
        }
    }

    // Módulos
    if (is_dir($modulesDir)) {
        $mods = glob($modulesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($mods as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
            $modData = jsonRead($jsonFile);
            if (!$modData || !isset($modData['resources'][$lang])) continue;
            $changed = false;
            foreach (['layouts', 'pages', 'components'] as $typeEn) {
                $arr = $modData['resources'][$lang][$typeEn] ?? [];
                foreach ($arr as $idx => $item) {
                    $id = $item['id'] ?? null;
                    if (!$id) continue;
                    $paths = resourcePaths($modPath, $lang, $typeEn, $id);
                    $html = readFileIfExists($paths['html']);
                    $css  = readFileIfExists($paths['css']);
                    $newCks = buildChecksum($html, $css);
                    $originKey = "module:$modId:$lang:$typeEn";
                    if (applyChecksumAndVersionUpdate($modData['resources'][$lang][$typeEn][$idx], $newCks, $originUpdates, $originKey)) {
                        $changed = true;
                    }

                    if ($typeEn === 'layouts') {
                        if (!isset($layoutIdMap[$id])) {
                            $layoutIdMap[$id] = $nextLayoutId++;
                        }
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
                                'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                                'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                            ];
                            $seenLayouts[$id] = true;
                        }
                    } elseif ($typeEn === 'pages') {
                        $k = pageKey($lang, $modId, $id);
                        $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                        $pagesData[] = [
                            'id_paginas' => $pid,
                            'id_usuarios' => 1,
                            'id_layouts' => null,
                            'nome' => $item['name'] ?? $id,
                            'id' => $id,
                            'language' => $lang,
                            'caminho' => $item['path'] ?? ($id . '/'),
                            'tipo' => translateTypePT($item['type'] ?? 'sistema'),
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
                            'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                            'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                            '_layout_id_str' => $item['layout'] ?? null,
                        ];
                    } else {
                        $k = compKey($lang, $modId, $id);
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
                            'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                            'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }
            }
            if ($changed) {
                persistOriginJson($jsonFile, $modData);
                echo "  - Atualizado módulo $modId ($lang) com novas versões/checksums\n";
            }
        }
    }

    // Plugins
    global $pluginsDir;
    if (is_dir($pluginsDir)) {
        $plugins = glob($pluginsDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($plugins as $plugPath) {
            $plugId = basename($plugPath);
            $plugModsBase = $plugPath . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'modulos';
            if (!is_dir($plugModsBase)) continue;
            $plugMods = glob($plugModsBase . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            foreach ($plugMods as $modPath) {
                $modId = basename($modPath);
                $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
                $modData = jsonRead($jsonFile);
                if (!$modData || !isset($modData['resources'][$lang])) continue;
                $changed = false;
                foreach (['layouts', 'pages', 'components'] as $typeEn) {
                    $arr = $modData['resources'][$lang][$typeEn] ?? [];
                    foreach ($arr as $idx => $item) {
                        $id = $item['id'] ?? null;
                        if (!$id) continue;
                        $paths = resourcePaths($modPath, $lang, $typeEn, $id);
                        $html = readFileIfExists($paths['html']);
                        $css  = readFileIfExists($paths['css']);
                        $newCks = buildChecksum($html, $css);
                        $originKey = "plugin:$plugId:$modId:$lang:$typeEn";
                        if (applyChecksumAndVersionUpdate($modData['resources'][$lang][$typeEn][$idx], $newCks, $originUpdates, $originKey)) {
                            $changed = true;
                        }

                        if ($typeEn === 'layouts') {
                            if (!isset($layoutIdMap[$id])) {
                                $layoutIdMap[$id] = $nextLayoutId++;
                            }
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
                                    'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                                    'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                                ];
                                $seenLayouts[$id] = true;
                            }
                        } elseif ($typeEn === 'pages') {
                            $k = pageKey($lang, $modId, $id);
                            $pid = $pageIdMap[$k] ?? ($pageIdMap[$k] = $nextPageId++);
                            $pagesData[] = [
                                'id_paginas' => $pid,
                                'id_usuarios' => 1,
                                'id_layouts' => null,
                                'nome' => $item['name'] ?? $id,
                                'id' => $id,
                                'language' => $lang,
                                'caminho' => $item['path'] ?? ($id . '/'),
                                'tipo' => translateTypePT($item['type'] ?? 'sistema'),
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
                                'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                                'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                                '_layout_id_str' => $item['layout'] ?? null,
                            ];
                        } else {
                            $k = compKey($lang, $modId, $id);
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
                                'file_version' => $modData['resources'][$lang][$typeEn][$idx]['version'] ?? '1.0',
                                'checksum' => json_encode($newCks, JSON_UNESCAPED_UNICODE),
                            ];
                        }
                    }
                }
                if ($changed) {
                    persistOriginJson($jsonFile, $modData);
                    echo "  - Atualizado plugin $plugId/$modId ($lang) com novas versões/checksums\n";
                }
            }
        }
    }
}

// Resolver id_layouts nas páginas com base no mapa layoutIdMap
if (!empty($pagesData)) {
    foreach ($pagesData as &$p) {
        if (!empty($p['_layout_id_str'])) {
            $lid = $p['_layout_id_str'];
            $p['id_layouts'] = $layoutIdMap[$lid] ?? null;
        }
        unset($p['_layout_id_str']);
    }
}

// Persistir JSONs de dados
jsonWrite($layoutsPath, $layoutsData);
jsonWrite($pagesPath, $pagesData);
jsonWrite($compsPath, $componentsData);

echo "\n=== RESUMO ===\n";
echo "Layouts: " . count($layoutsData) . " -> $layoutsPath\n";
echo "Páginas: " . count($pagesData) . " -> $pagesPath\n";
echo "Componentes: " . count($componentsData) . " -> $compsPath\n";

if (!empty($originUpdates)) {
    echo "\nJSONs de origem atualizados (versão/checksum):\n";
    foreach ($originUpdates as $k => $n) {
        echo "- $k: $n item(ns)\n";
    }
}

echo "\nConcluído com sucesso.\n";
?>
