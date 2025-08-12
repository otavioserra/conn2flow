<?php
/**
 * Script: corrigir-dados-corrompidos.php
 * Implementação: Dados Corrompidos
 * Objetivo: Auditar e corrigir divergências específicas (principalmente campo 'option' de páginas)
 *            entre os dados originais do banco legado (conn2flow_old) e os recursos atuais em JSON
 *            (globais e de módulos), conforme especificação em
 *            ai-workspace/prompts/arquitetura/corrigir-dados-corrompidos.md
 *
 * Funcionalidades principais:
 *  - Modo dry-run (--dry-run)
 *  - Limitação de processamento (--limit N)
 *  - Seleção de recursos (--include pages,layouts,components)
 *  - Geração de relatório JSON (--report-json=path)
 *  - Backups automáticos antes de escrita real
 *  - Correções atômicas (arquivo temporário + rename)
 *  - Internacionalização de logs (pt-br/en)
 *
 * Regras de correção (resumido):
 *  - Campos alvo em pages: name, id, layout (via id_layouts), path, type, option, root
 *  - 'type': sistema=>system, pagina=>page, demais lower-case
 *  - 'option': criar/atualizar se houver valor no banco; se banco vazio e JSON possuir valor -> manter
 *  - 'root': criar se 1 => true; remover se presente e banco == 0 (configurável futuramente)
 *  - 'layout': mapear via id_layouts numérico -> layout.id textual
 *  - 'path': garantir sufixo '/'
 *  - Não modificar checksums/versions
 */

declare(strict_types=1);

// ---------------------------------------------------------
// Bootstrap básico
// ---------------------------------------------------------
// --- DETECÇÃO DE CONTEXTO (HOST vs CONTAINER) ---------------------------------------------
// Caminho base "gestor" (onde ficam controladores/ resources/ autenticacoes/) é sempre 3 níveis acima:
//   .../gestor/controladores/agents/arquitetura  -> subir 3 => .../gestor
//   .../conn2flow-gestor/controladores/agents/arquitetura (container) -> subir 3 => .../conn2flow-gestor
$GESTOR_CANDIDATO = realpath(dirname(__DIR__, 3));
if (!$GESTOR_CANDIDATO) { $GESTOR_CANDIDATO = getcwd(); }

// Se nesse candidato existem pastas chaves, assumimos que ele é a raiz gestor/ (host ou container)
if (is_dir($GESTOR_CANDIDATO . DIRECTORY_SEPARATOR . 'controladores') &&
    is_dir($GESTOR_CANDIDATO . DIRECTORY_SEPARATOR . 'resources')) {
    $GESTOR = $GESTOR_CANDIDATO;
} else {
    // fallback antigo (pouco provável necessário)
    $GESTOR = realpath(__DIR__ . '/../../../gestor') ?: $GESTOR_CANDIDATO;
}

// Raiz do repositório no host é um nível acima se existir diretório 'docker'
if (is_dir($GESTOR . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'docker')) {
    $ROOT = realpath($GESTOR . DIRECTORY_SEPARATOR . '..'); // host
} else {
    $ROOT = $GESTOR; // container: gestor coincide com raiz lógica
}
$RESOURCES_GLOBAL = $GESTOR . DIRECTORY_SEPARATOR . 'resources';
$MODULES_DIR = $GESTOR . DIRECTORY_SEPARATOR . 'modulos';
$LANG_DEFAULT = 'pt-br';
$BACKUP_DIR = $ROOT . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'arquitetura';
$LOGS_DIR = $GESTOR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'arquitetura';
$LANG_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'lang';
$SEEDS_DATA_DIR = $GESTOR . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data';

if (!is_dir($BACKUP_DIR)) { @mkdir($BACKUP_DIR, 0755, true); }
if (!is_dir($LOGS_DIR)) { @mkdir($LOGS_DIR, 0755, true); }

// ---------------------------------------------------------
// Helpers genéricos
// ---------------------------------------------------------
function cliArg(string $name, ?string $default = null): ?string {
    foreach ($GLOBALS['argv'] as $arg) {
        if (strpos($arg, "--$name=") === 0) {
            return substr($arg, strlen($name) + 3);
        }
        if ($arg === "--$name") return '1';
    }
    return $default;
}

function hasFlag(string $name): bool {
    foreach ($GLOBALS['argv'] as $arg) {
        if ($arg === "--$name") return true;
    }
    return false;
}

function readJson(string $path) {
    if (!file_exists($path)) return null;
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function writeJsonAtomic(string $path, $data): bool {
    $tmp = $path . '.tmp';
    $ok = file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    if (!$ok) return false;
    return rename($tmp, $path);
}

function backupFile(string $path, string $backupDir): ?string {
    if (!file_exists($path)) return null;
    $ts = date('Ymd_His');
    $base = basename($path);
    $dest = rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $base . '.' . $ts . '.bak';
    if (@copy($path, $dest)) return $dest;
    return null;
}

function normalizeType(?string $t): ?string {
    if ($t === null) return null;
    $t = trim(mb_strtolower($t));
    return match($t) {
        'sistema' => 'system',
        'pagina' => 'page',
        default => $t,
    };
}

function normalizePath(?string $p): ?string {
    if ($p === null) return null;
    $p = trim($p);
    $p = preg_replace('#/{2,}#', '/', $p);
    if ($p === '') return $p;
    return str_ends_with($p, '/') ? $p : ($p . '/');
}

function loadEnv(string $path): array {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        $env[$k] = trim($v, "'\"");
    }
    return $env;
}

function logMsg(string $msg): void {
    echo $msg . "\n";
}

// ---------------------------------------------------------
// Carregar linguagem (simplificado)
// ---------------------------------------------------------
function loadLang(string $lang, string $langDir): array {
    $file = $langDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'corrigir-dados-corrompidos.json';
    $data = readJson($file);
    return $data ?? [];
}

$LANG = cliArg('lang', $LANG_DEFAULT);
$LANG_MAP = loadLang($LANG, $LANG_DIR);

function __(string $key): string {
    return $GLOBALS['LANG_MAP'][$key] ?? "[$key]";
}

// ---------------------------------------------------------
// Conexão banco legado
// ---------------------------------------------------------
function connectLegacy(array $env): PDO {
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('Extensão pdo_mysql não carregada. Instale/ habilite no ambiente PHP.');
    }
    $host = $env['DB_HOST'] ?? 'localhost';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USERNAME'] ?? $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASSWORD'] ?? '';
    $legacyDb = 'conn2flow_old';
    $dsn = "mysql:host=$host;port=$port;dbname=$legacyDb;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

// ---------------------------------------------------------
// Fetch dados antigos
// ---------------------------------------------------------
function fetchLegacyData(PDO $pdo, ?int $limit = null, array $include = ['pages','layouts','components']): array {
    $result = ['layouts'=>[], 'pages'=>[], 'components'=>[]];
    if (in_array('layouts', $include, true)) {
        $sql = 'SELECT id_layouts, id, nome FROM layouts';
        if ($limit) $sql .= ' LIMIT ' . (int)$limit;
        $result['layouts'] = $pdo->query($sql)->fetchAll();
    }
    if (in_array('pages', $include, true)) {
        $sql = 'SELECT id_paginas, id_layouts, id, nome, caminho, tipo, opcao, raiz FROM paginas';
        if ($limit) $sql .= ' LIMIT ' . (int)$limit;
        $result['pages'] = $pdo->query($sql)->fetchAll();
    }
    if (in_array('components', $include, true)) {
        $sql = 'SELECT id_componentes, id, nome FROM componentes';
        if ($limit) $sql .= ' LIMIT ' . (int)$limit;
        $result['components'] = $pdo->query($sql)->fetchAll();
    }
    return $result;
}

// ---------------------------------------------------------
// Carregar JSON atuais (globais + módulos)
// ---------------------------------------------------------
function loadCurrentResources(string $resourcesGlobalDir, string $modulesDir, string $lang, array $include): array {
    $out = [
        'layouts_global' => [],
        'pages_global' => [],
        'components_global' => [],
        'modules' => [] // cada módulo: resources[layouts|pages|components]
    ];
    // Globais: arquivos de índice listados em resources.map.php
    $mapFile = $resourcesGlobalDir . DIRECTORY_SEPARATOR . 'resources.map.php';
    if (!file_exists($mapFile)) {
        throw new RuntimeException('resources.map.php não encontrado');
    }
    $map = include $mapFile; // $resources
    if (!isset($map['languages'][$lang]['data'])) {
        throw new RuntimeException('Idioma não definido em resources.map.php');
    }
    $dataMap = $map['languages'][$lang]['data'];

    // Carregar listas globais
    foreach (['layouts'=>'layouts_global','pages'=>'pages_global','components'=>'components_global'] as $typeEn=>$bucket) {
        if (!in_array($typeEn, $include, true)) continue;
        if (!isset($dataMap[$typeEn])) continue;
        $listFile = $resourcesGlobalDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $dataMap[$typeEn];
        $out[$bucket] = readJson($listFile) ?? [];
    }

    // Módulos
    if (is_dir($modulesDir)) {
        $mods = glob($modulesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($mods as $modPath) {
            $modId = basename($modPath);
            $jsonFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
            if (!file_exists($jsonFile)) continue;
            $json = readJson($jsonFile);
            if (!$json || !isset($json['resources'][$lang])) continue;
            $out['modules'][$modId] = [
                'file' => $jsonFile,
                'data' => $json['resources'][$lang]
            ];
        }
    }

    return $out;
}

// ---------------------------------------------------------
// Indexação utilitária
// ---------------------------------------------------------
function indexLegacyLayouts(array $legacyLayouts): array {
    $byIdLayouts = [];
    foreach ($legacyLayouts as $row) {
        $byIdLayouts[(int)$row['id_layouts']] = $row; // id_layouts numérico
    }
    return $byIdLayouts;
}

function mapLayoutIdNumToText(int $idNum, array $legacyLayouts): ?string {
    return $legacyLayouts[$idNum]['id'] ?? null;
}

// ---------------------------------------------------------
// Diferenças
// ---------------------------------------------------------
function computeDiffs(array $legacy, array $current, string $lang, array $include): array {
    $diff = [
        'changes' => [],
        'missing' => ['pages'=>[], 'layouts'=>[], 'components'=>[]],
        'layout_inconsistencies' => []
    ];

    // Index legacy layouts numérico->row
    $legacyLayoutIndex = indexLegacyLayouts($legacy['layouts']);

    // Index globais atuais por id
    $currentLayoutsById = [];
    foreach ($current['layouts_global'] as $l) { $currentLayoutsById[$l['id']] = $l; }

    $currentPagesById = [];
    foreach ($current['pages_global'] as $p) { $currentPagesById[$p['id']] = $p; }

    $currentCompsById = [];
    foreach ($current['components_global'] as $c) { $currentCompsById[$c['id']] = $c; }

    // Layouts: apenas detectar ids faltantes
    if (in_array('layouts',$include,true)) {
        foreach ($legacy['layouts'] as $lay) {
            if (!isset($currentLayoutsById[$lay['id']])) {
                $diff['missing']['layouts'][] = $lay['id'];
            }
        }
    }

    // Pages: comparar campos
    if (in_array('pages',$include,true)) {
        foreach ($legacy['pages'] as $pageRow) {
            $pid = $pageRow['id'];
            $currentBucket = null; // global ou módulo
            $currentRef = null;
            $scope = 'global';

            // procurar primeiro global
            if (isset($currentPagesById[$pid])) {
                $currentRef =& $currentPagesById[$pid];
            } else {
                // procurar em módulos
                foreach ($current['modules'] as $modId=>$mdata) {
                    if (!empty($mdata['data']['pages'])) {
                        foreach ($mdata['data']['pages'] as $idx=>$pr) {
                            if (($pr['id'] ?? null) === $pid) {
                                $currentRef =& $current['modules'][$modId]['data']['pages'][$idx];
                                $scope = 'module:' . $modId;
                                break 2;
                            }
                        }
                    }
                }
            }

            if (!$currentRef) {
                $diff['missing']['pages'][] = $pid;
                continue;
            }

            $changes = [];
            // name
            if (($currentRef['name'] ?? null) !== $pageRow['nome']) {
                $changes[] = ['campo'=>'name','antes'=>$currentRef['name']??null,'depois'=>$pageRow['nome']];
            }
            // path
            $normalizedPath = normalizePath($pageRow['caminho']);
            if (isset($currentRef['path'])) {
                if (normalizePath($currentRef['path']) !== $normalizedPath) {
                    $changes[] = ['campo'=>'path','antes'=>$currentRef['path'],'depois'=>$normalizedPath];
                }
            } else {
                $changes[] = ['campo'=>'path','antes'=>null,'depois'=>$normalizedPath];
            }
            // type
            $normLegacyType = normalizeType($pageRow['tipo']);
            $normCurrentType = normalizeType($currentRef['type'] ?? null);
            if ($normLegacyType !== $normCurrentType) {
                $changes[] = ['campo'=>'type','antes'=>$currentRef['type']??null,'depois'=>$normLegacyType];
            }
            // option
            $legacyOption = trim((string)($pageRow['opcao'] ?? ''));
            if ($legacyOption !== '') {
                if (($currentRef['option'] ?? null) !== $legacyOption) {
                    $changes[] = ['campo'=>'option','antes'=>$currentRef['option']??null,'depois'=>$legacyOption];
                }
            } else {
                // banco vazio; se JSON não tem, nada a fazer; se tem, mantemos (apenas relatar?) -> aviso, mas não mudança
            }
            // root
            $legacyRoot = (int)$pageRow['raiz'] === 1;
            if ($legacyRoot) {
                if (!isset($currentRef['root']) || $currentRef['root'] !== true) {
                    $changes[] = ['campo'=>'root','antes'=>$currentRef['root']??null,'depois'=>true];
                }
            } else {
                if (isset($currentRef['root']) && $currentRef['root'] === true) {
                    $changes[] = ['campo'=>'root','antes'=>true,'depois'=>null]; // remover
                }
            }
            // layout
            $idLayoutNum = (int)$pageRow['id_layouts'];
            $layoutText = mapLayoutIdNumToText($idLayoutNum, $legacyLayoutIndex);
            if ($layoutText === null) {
                $diff['layout_inconsistencies'][] = ['page_id'=>$pid,'id_layouts_num'=>$idLayoutNum,'nao_encontrado'=>true];
            } else {
                if (($currentRef['layout'] ?? null) !== $layoutText) {
                    $changes[] = ['campo'=>'layout','antes'=>$currentRef['layout']??null,'depois'=>$layoutText];
                }
            }

            if ($changes) {
                foreach ($changes as &$ch) { $ch['id'] = $pid; $ch['escopo'] = $scope; }
                $diff['changes'] = array_merge($diff['changes'], $changes);
            }
        }
    }

    // Components: apenas detectar ids faltantes (não há campos extras a corrigir agora)
    if (in_array('components',$include,true)) {
        foreach ($legacy['components'] as $comp) {
            if (!isset($currentCompsById[$comp['id']])) {
                $diff['missing']['components'][] = $comp['id'];
            }
        }
    }

    return $diff;
}

// ---------------------------------------------------------
// Diferenças contra arquivos de seeds (LayoutsData.json, PaginasData.json, ComponentesData.json)
// Estrutura dos arquivos segue o formato "legacy" (campos id_layouts, nome, caminho, tipo, opcao, raiz, etc.)
// ---------------------------------------------------------
function loadSeedsData(string $seedsDir, ?int $limit = null, array $include = ['pages','layouts','components']): array {
    $out = ['layouts'=>[], 'pages'=>[], 'components'=>[]];
    // Layouts
    if (in_array('layouts',$include,true)) {
        $file = $seedsDir . DIRECTORY_SEPARATOR . 'LayoutsData.json';
        $data = readJson($file) ?: [];
        if ($limit) $data = array_slice($data,0,$limit);
        // Normalizar chaves como no legacy fetch
        foreach ($data as $row) {
            $out['layouts'][] = [
                'id_layouts' => $row['id_layouts'] ?? null,
                'id' => $row['id'] ?? null,
                'nome' => $row['nome'] ?? null,
            ];
        }
    }
    if (in_array('pages',$include,true)) {
        $file = $seedsDir . DIRECTORY_SEPARATOR . 'PaginasData.json';
        $data = readJson($file) ?: [];
        if ($limit) $data = array_slice($data,0,$limit);
        foreach ($data as $row) {
            $out['pages'][] = [
                'id_paginas' => $row['id_paginas'] ?? null,
                'id_layouts' => $row['id_layouts'] ?? null,
                'id' => $row['id'] ?? null,
                'nome' => $row['nome'] ?? null,
                'caminho' => $row['caminho'] ?? null,
                'tipo' => $row['tipo'] ?? null,
                'opcao' => $row['opcao'] ?? null,
                'raiz' => $row['raiz'] ?? null,
            ];
        }
    }
    if (in_array('components',$include,true)) {
        $file = $seedsDir . DIRECTORY_SEPARATOR . 'ComponentesData.json';
        $data = readJson($file) ?: [];
        if ($limit) $data = array_slice($data,0,$limit);
        foreach ($data as $row) {
            $out['components'][] = [
                'id_componentes' => $row['id_componentes'] ?? null,
                'id' => $row['id'] ?? null,
                'nome' => $row['nome'] ?? null,
            ];
        }
    }
    return $out;
}

function computeSeedsDiff(array $seeds, array $current, array $include): array {
    $diff = [
        'changes' => [],
        'missing' => ['pages'=>[], 'layouts'=>[], 'components'=>[]],
        'layout_inconsistencies' => []
    ];

    // Index layouts por id_layouts (numérico)
    $layoutIndexByNum = [];
    foreach ($seeds['layouts'] as $l) {
        if (isset($l['id_layouts'])) $layoutIndexByNum[(int)$l['id_layouts']] = $l;
    }

    // Index current resources
    $currentLayoutsById = [];
    foreach ($current['layouts_global'] as $l) { $currentLayoutsById[$l['id']] = $l; }
    $currentPagesById = [];
    foreach ($current['pages_global'] as $p) { $currentPagesById[$p['id']] = $p; }
    $currentCompsById = [];
    foreach ($current['components_global'] as $c) { $currentCompsById[$c['id']] = $c; }

    if (in_array('layouts',$include,true)) {
        foreach ($seeds['layouts'] as $lay) {
            if (!isset($lay['id'])) continue;
            if (!isset($currentLayoutsById[$lay['id']])) {
                $diff['missing']['layouts'][] = $lay['id'];
            } else {
                // Compare nome
                $cur = $currentLayoutsById[$lay['id']];
                if (($cur['name'] ?? null) !== ($lay['nome'] ?? null)) {
                    $diff['changes'][] = [ 'recurso'=>'layout','id'=>$lay['id'],'campo'=>'name','antes'=>$cur['name']??null,'depois'=>$lay['nome']??null,'escopo'=>'global' ];
                }
            }
        }
    }

    if (in_array('pages',$include,true)) {
        foreach ($seeds['pages'] as $pageRow) {
            $pid = $pageRow['id'] ?? null;
            if (!$pid) continue;
            $scope = 'global';
            $ref = null;
            if (isset($currentPagesById[$pid])) {
                $ref =& $currentPagesById[$pid];
            } else {
                // procurar em módulos
                foreach ($current['modules'] as $modId=>$mdata) {
                    if (!empty($mdata['data']['pages'])) {
                        foreach ($mdata['data']['pages'] as $idx=>$pr) {
                            if (($pr['id'] ?? null) === $pid) { $ref =& $current['modules'][$modId]['data']['pages'][$idx]; $scope='module:'.$modId; break 2; }
                        }
                    }
                }
            }
            if (!$ref) { $diff['missing']['pages'][] = $pid; continue; }
            $changes = [];
            if (($ref['name']??null) !== ($pageRow['nome']??null)) {
                $changes[] = ['campo'=>'name','antes'=>$ref['name']??null,'depois'=>$pageRow['nome']??null];
            }
            $normSeedPath = normalizePath($pageRow['caminho'] ?? null);
            if (isset($ref['path'])) {
                if (normalizePath($ref['path']) !== $normSeedPath) {
                    $changes[] = ['campo'=>'path','antes'=>$ref['path'],'depois'=>$normSeedPath];
                }
            } elseif ($normSeedPath !== null) {
                $changes[] = ['campo'=>'path','antes'=>null,'depois'=>$normSeedPath];
            }
            $normSeedType = normalizeType($pageRow['tipo'] ?? null);
            $normCurType = normalizeType($ref['type'] ?? null);
            if ($normSeedType !== $normCurType) {
                $changes[] = ['campo'=>'type','antes'=>$ref['type']??null,'depois'=>$normSeedType];
            }
            $seedOption = trim((string)($pageRow['opcao'] ?? ''));
            if ($seedOption !== '') {
                if (($ref['option'] ?? null) !== $seedOption) {
                    $changes[] = ['campo'=>'option','antes'=>$ref['option']??null,'depois'=>$seedOption];
                }
            }
            $seedRoot = (int)($pageRow['raiz'] ?? 0) === 1;
            if ($seedRoot) {
                if (!isset($ref['root']) || $ref['root'] !== true) {
                    $changes[] = ['campo'=>'root','antes'=>$ref['root']??null,'depois'=>true];
                }
            } else {
                if (isset($ref['root']) && $ref['root'] === true) {
                    $changes[] = ['campo'=>'root','antes'=>true,'depois'=>null];
                }
            }
            $idLayoutNum = (int)($pageRow['id_layouts'] ?? 0);
            if ($idLayoutNum) {
                $layoutText = $layoutIndexByNum[$idLayoutNum]['id'] ?? null;
                if ($layoutText === null) {
                    $diff['layout_inconsistencies'][] = ['page_id'=>$pid,'id_layouts_num'=>$idLayoutNum,'nao_encontrado'=>true];
                } else if (($ref['layout'] ?? null) !== $layoutText) {
                    $changes[] = ['campo'=>'layout','antes'=>$ref['layout']??null,'depois'=>$layoutText];
                }
            }
            if ($changes) {
                foreach ($changes as &$c) { $c['id']=$pid; $c['escopo']=$scope; $c['recurso']='page'; }
                $diff['changes'] = array_merge($diff['changes'],$changes);
            }
        }
    }

    if (in_array('components',$include,true)) {
        foreach ($seeds['components'] as $compRow) {
            $cid = $compRow['id'] ?? null;
            if (!$cid) continue;
            if (!isset($currentCompsById[$cid])) {
                $diff['missing']['components'][] = $cid;
            } else {
                $cur = $currentCompsById[$cid];
                if (($cur['name'] ?? null) !== ($compRow['nome'] ?? null)) {
                    $diff['changes'][] = [ 'recurso'=>'component','id'=>$cid,'campo'=>'name','antes'=>$cur['name']??null,'depois'=>$compRow['nome']??null,'escopo'=>'global'];
                }
            }
        }
    }

    return $diff;
}

// ---------------------------------------------------------
// Aplicar correções em memória
// ---------------------------------------------------------
function applyCorrections(array $diff, array $current, string $lang): array {
    if (empty($diff['changes'])) return $current; // nada a fazer

    // Reaplicar alterações buscando referência (global/módulo)
    foreach ($diff['changes'] as $change) {
        $id = $change['id'];
        $campo = $change['campo'];
        $escopo = $change['escopo'];
        $novo = $change['depois'];
        if ($campo === 'id') continue; // nunca alteramos id

        if ($escopo === 'global') {
            // localizar referência em pages_global
            foreach ($current['pages_global'] as &$p) {
                if (($p['id'] ?? null) === $id) {
                    if ($novo === null) {
                        unset($p[$campo]);
                    } else {
                        $p[$campo] = $novo;
                    }
                    break;
                }
            }
        } elseif (str_starts_with($escopo, 'module:')) {
            $modId = substr($escopo, 7);
            if (isset($current['modules'][$modId]['data']['pages'])) {
                foreach ($current['modules'][$modId]['data']['pages'] as &$p) {
                    if (($p['id'] ?? null) === $id) {
                        if ($novo === null) {
                            unset($p[$campo]);
                        } else {
                            $p[$campo] = $novo;
                        }
                        break;
                    }
                }
            }
        }
    }

    return $current;
}

// ---------------------------------------------------------
// Persistir alterações (globais + módulos)
// ---------------------------------------------------------
function persistResources(array $current, string $resourcesGlobalDir, string $modulesDir, string $lang, string $backupDir, bool $dryRun): void {
    // Globais: precisamos do resources.map.php para obter filenames
    $mapFile = $resourcesGlobalDir . DIRECTORY_SEPARATOR . 'resources.map.php';
    $map = include $mapFile;
    $dataMap = $map['languages'][$lang]['data'];

    // Layouts, pages, components
    $globalFiles = [
        'layouts_global' => $resourcesGlobalDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $dataMap['layouts'],
        'pages_global' => $resourcesGlobalDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $dataMap['pages'],
        'components_global' => $resourcesGlobalDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $dataMap['components'],
    ];

    foreach ($globalFiles as $bucket=>$file) {
        if ($dryRun) continue; // não escreve
        if (!is_dir(dirname($file))) @mkdir(dirname($file),0755,true);
        backupFile($file, $backupDir);
        writeJsonAtomic($file, $current[$bucket]);
    }

    // Módulos
    foreach ($current['modules'] as $modId=>$mdata) {
        $file = $mdata['file'];
        if ($dryRun) continue;
        $jsonFull = readJson($file);
        if (!$jsonFull) continue; // pular
        $jsonFull['resources'][$lang] = $mdata['data'];
        backupFile($file, $backupDir);
        writeJsonAtomic($file, $jsonFull);
    }
}

// ---------------------------------------------------------
// Relatório
// ---------------------------------------------------------
function generateReport(array $legacyDiff, array $seedsDiff, array $options, ?string $pathJson): void {
    $summary = [
        'timestamp' => date('c'),
        'dry_run' => $options['dry_run'],
        'legacy_counts' => [
            'changes' => count($legacyDiff['changes']),
            'missing_pages' => count($legacyDiff['missing']['pages']),
            'missing_layouts' => count($legacyDiff['missing']['layouts']),
            'missing_components' => count($legacyDiff['missing']['components']),
            'layout_inconsistencies' => count($legacyDiff['layout_inconsistencies'])
        ],
        'seeds_counts' => [
            'changes' => count($seedsDiff['changes']),
            'missing_pages' => count($seedsDiff['missing']['pages']),
            'missing_layouts' => count($seedsDiff['missing']['layouts']),
            'missing_components' => count($seedsDiff['missing']['components']),
            'layout_inconsistencies' => count($seedsDiff['layout_inconsistencies'])
        ],
        'legacy_sample_changes' => array_slice($legacyDiff['changes'],0,20),
        'seeds_sample_changes' => array_slice($seedsDiff['changes'],0,20),
    ];
    echo "===== RELATORIO CORRECAO =====\n";
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    if ($pathJson) {
        $payload = [ 'legacy_diff' => $legacyDiff, 'seeds_diff' => $seedsDiff, 'summary' => $summary ];
        file_put_contents($pathJson, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Relatório JSON salvo em: $pathJson\n";
    }
}

// ---------------------------------------------------------
// MAIN
// ---------------------------------------------------------
function main(): void {
    global $ROOT, $RESOURCES_GLOBAL, $MODULES_DIR, $BACKUP_DIR, $LANG_DEFAULT, $SEEDS_DATA_DIR;

    $dryRun = hasFlag('dry-run');
    $limit = cliArg('limit');
    $limitInt = $limit !== null ? max(1, (int)$limit) : null;
    $includeArg = cliArg('include');
    $include = $includeArg ? array_map('trim', explode(',', strtolower($includeArg))) : ['pages','layouts','components'];
    $reportJson = cliArg('report-json');
    $lang = cliArg('lang', $LANG_DEFAULT);

    // Caminhos candidatos para o .env (host vs container)
    $candidateEnvPaths = [];
    // Host (repositório): gestor raiz está em $GESTOR e repositório em $ROOT
    $candidateEnvPaths[] = $ROOT . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'dados' . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'localhost' . DIRECTORY_SEPARATOR . 'conn2flow-gestor' . DIRECTORY_SEPARATOR . 'autenticacoes' . DIRECTORY_SEPARATOR . 'localhost' . DIRECTORY_SEPARATOR . '.env';
    // Caminho direto relativo ao diretório gestor (funciona host e container)
    $candidateEnvPaths[] = $GESTOR . DIRECTORY_SEPARATOR . 'autenticacoes' . DIRECTORY_SEPARATOR . 'localhost' . DIRECTORY_SEPARATOR . '.env';
    // Caminho relativo ao diretório de execução (container pode acessar via path absoluto já resolvido)
    $candidateEnvPaths[] = $ROOT . DIRECTORY_SEPARATOR . 'autenticacoes' . DIRECTORY_SEPARATOR . 'localhost' . DIRECTORY_SEPARATOR . '.env';
    $env = [];
    $envPathUsed = null;
    foreach ($candidateEnvPaths as $p) {
        if (is_file($p)) { $env = loadEnv($p); $envPathUsed = $p; break; }
    }
    if (!$env) {
        fwrite(STDERR, "Arquivo .env não encontrado nos caminhos candidatos. Verifique sincronização.\n" . implode("\n", $candidateEnvPaths) . "\n");
    } else {
        echo "Usando .env: $envPathUsed\n";
    }

    try {
        $pdo = connectLegacy($env);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Erro conexão legado: ' . $e->getMessage() . "\n");
        exit(1);
    }

    $legacy = fetchLegacyData($pdo, $limitInt, $include); // dados banco legado conn2flow_old
    $current = loadCurrentResources($RESOURCES_GLOBAL, $MODULES_DIR, $lang, $include); // recursos atuais JSON
    $seeds = loadSeedsData($SEEDS_DATA_DIR, $limitInt, $include); // arquivos *Data.json

    $legacyDiff = computeDiffs($legacy, $current, $lang, $include);
    $seedsDiff = computeSeedsDiff($seeds, $current, $include);

    if (!empty($legacyDiff['changes'])) {
        echo "Alterações (legacy) detectadas: " . count($legacyDiff['changes']) . "\n";
    } else {
        echo "Nenhuma alteração de campos necessária (legacy).\n";
    }
    if (!empty($seedsDiff['changes'])) {
        echo "Diferenças em relação aos seeds detectadas: " . count($seedsDiff['changes']) . "\n";
    } else {
        echo "Nenhuma diferença entre recursos e seeds.\n";
    }

    if ($dryRun) {
        echo "(dry-run) Nenhum arquivo será modificado.\n";
        generateReport($legacyDiff, $seedsDiff, ['dry_run'=>true], $reportJson);
        $exit = (
            count($legacyDiff['changes'])>0 || count($legacyDiff['missing']['pages'])>0 ||
            count($seedsDiff['changes'])>0 || count($seedsDiff['missing']['pages'])>0
        ) ? 2 : 0;
        exit($exit);
    }

    // Aplicar apenas correções vindas do diff legacy (seedsDiff é somente auditoria)
    $currentCorrigido = applyCorrections($legacyDiff, $current, $lang);
    persistResources($currentCorrigido, $RESOURCES_GLOBAL, $MODULES_DIR, $lang, $BACKUP_DIR, false);

    generateReport($legacyDiff, $seedsDiff, ['dry_run'=>false], $reportJson);
}

main();

?>
