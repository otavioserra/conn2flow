<?php
/**
 * Generation of multiple Data.json files for PLUGIN (Layouts, Pages, Components, Variables)
 * ---------------------------------------------------------------------------------------
 * This script is intended to be used by developers and CI/CD automations as a base for
 * updating resource data for ONE plugin: global + modules.
 *
 * Output (in <plugin>/db/data):
 *   - LayoutsData.json
 *   - PaginasData.json
 *   - ComponentesData.json
 *   - VariaveisData.json
 *   - (orphans) db/orphans/<Type>Data.json
 *
 * Summary of rules (aligned with core v2):
 *   - Reuse 'versao' when checksum (md5 html/css/combined) does not change.
 *   - Increment 'versao' (n+1) when any checksum changes.
 *   - 'file_version' receives the value of the 'version' field from the source files (layouts.json etc).
 *   - Checksum stored as JSON string (compatibility).
 *   - Uniqueness: layouts/lang|id, components/lang|id, pages (lang|mod|id) and path lang|path, variables with group rules.
 *   - Modules: same hierarchy as core but starting at <plugin>/modules/<mod>.
 *
 * Plugin root and deployment root configuration:
 *   - By default, the script loads paths from environment/environment.json (devEnvironment section):
 *       - pluginRoot:   <repoRoot>/plugin
 *       - deployPluginRoot: <repoRoot>/<testsBuild>/plugin
 *       - You can configure 'testsBuild' and other paths in environment.json.
 *   - You can override these paths via CLI arguments:
 *       --plugin-root=/absolute/path/to/source/plugin
 *       --deploy-plugin-root=/absolute/path/to/deploy/plugin
 *   - For backward compatibility, --test-plugin is accepted as an alias for --deploy-plugin-root.
 *   - If a CLI argument is provided and the directory exists, it takes precedence over environment.json.
 *
 * Usage examples:
 *   php update-data-resources-plugin.php
 *   php update-data-resources-plugin.php --plugin-root=/my/source/plugin --deploy-plugin-root=/my/deploy/plugin
 *   php update-data-resources-plugin.php --test-plugin
 *
 * Targets:
 *   - Default: internal skeleton plugin (plugin-skeleton/plugin)
 *   - Test: use flag --test-plugin or set deployPluginRoot to gestor/tests/build/plugin (test branch)
 *   - Override: --plugin-root=/absolute/path and/or --deploy-plugin-root=/absolute/path (highest priority)
 */
declare(strict_types=1);




// ================= CONFIGURAÇÃO DE CAMINHOS (AMBIENTE DO PLUGIN) =================
// Esta seção configura os caminhos baseados no arquivo environment.json do plugin
// O script sempre busca o environment.json dois níveis acima do seu próprio diretório

// Busca o arquivo de configuração do plugin (sempre 2 níveis acima deste script)
$pluginEnvPath = dirname(dirname(__DIR__)) . '/environment.json';
if (!is_file($pluginEnvPath)) {
    fwrite(STDERR, "ERRO: Arquivo environment.json do plugin não encontrado em: $pluginEnvPath\n");
    exit(1);
}

// Carrega e valida o arquivo de configuração do plugin
$pluginEnvJson = json_decode(file_get_contents($pluginEnvPath), true);
if (!is_array($pluginEnvJson) || empty($pluginEnvJson['devEnvironment']) || empty($pluginEnvJson['activePlugin']['id']) || empty($pluginEnvJson['plugins'])) {
    fwrite(STDERR, "ERRO: Configuração inválida ou faltando devEnvironment/activePlugin/plugins no environment.json do plugin\n");
    exit(1);
}

// ================= FUNÇÃO PARA NORMALIZAR CAMINHOS =================
// Converte caminhos Unix para Windows quando necessário
// Isso garante compatibilidade entre sistemas operacionais

function normalizePath(string $path): string {
    // Converte caminhos Unix no Windows (/c/Users/...) para caminhos Windows (C:\Users\...)
    if (DIRECTORY_SEPARATOR === '\\' && preg_match('#^/([a-zA-Z])/(.+)$#', $path, $matches)) {
        return $matches[1] . ':\\' . str_replace('/', '\\', $matches[2]);
    }
    return $path;
}

// ================= EXTRAÇÃO E NORMALIZAÇÃO DOS CAMINHOS =================
// Extrai as configurações essenciais do environment e normaliza os caminhos

$devEnv = $pluginEnvJson['devEnvironment'];
$activePluginId = $pluginEnvJson['activePlugin']['id'];
$plugins = $pluginEnvJson['plugins'];

// Normaliza os caminhos do devEnvironment
$devEnv['source'] = normalizePath($devEnv['source']);
$devEnv['target'] = normalizePath($devEnv['target'] ?? '');
$devEnv['dockerPath'] = normalizePath($devEnv['dockerPath'] ?? '');
$devEnv['deploys'] = normalizePath($devEnv['deploys']);
$devEnv['tests'] = normalizePath($devEnv['tests'] ?? '');
$devEnv['testsBuild'] = normalizePath($devEnv['testsBuild'] ?? '');

// Determina o caminho base do plugin e encontra o plugin ativo
$pluginRootBase = $devEnv['source'];
$activePluginPath = null;
foreach ($plugins as $p) {
    if (isset($p['id']) && $p['id'] === $activePluginId) {
        $activePluginPath = $p['path'];
        break;
    }
}
if (!$activePluginPath) {
    fwrite(STDERR, "ERRO: Caminho do plugin ativo não encontrado para ID: $activePluginId\n");
    exit(1);
}

// Monta os caminhos completos do plugin
$pluginRoot = rtrim($pluginRootBase, '/\\') . '/' . ltrim($activePluginPath, '/\\');
$testsPath = $devEnv['tests'] ?? 'tests';
$testsBuildPath = $devEnv['testsBuild'] ?? 'tests/build';
$deploysPath = $devEnv['deploys'] ?? $pluginRootBase . '/deploys/';

// Define os caminhos padrão baseados no environment.json (agora dinâmico)
$defaultPluginRoot = $pluginRoot;
$defaultDeployPluginRoot = rtrim($deploysPath, '/\\') . '/' . ltrim($activePluginPath, '/\\');

// ================= CAPTURA DE ARGUMENTOS DA LINHA DE COMANDO =================
// Processa os argumentos passados via linha de comando para sobrescrever configurações

$CLI_ARGS = [];
if (PHP_SAPI === 'cli') {
    global $argv;
    foreach ($argv as $a) {
        // Processa argumentos no formato --chave=valor
        if (preg_match('/^--([^=]+)=(.+)$/', $a, $m)) {
            $CLI_ARGS[$m[1]] = $m[2];
        }
        // Processa argumentos booleanos no formato --chave
        elseif (substr($a, 0, 2) === '--') {
            $CLI_ARGS[substr($a, 2)] = true;
        }
    }
}

// ================= DEFINIÇÃO FINAL DOS CAMINHOS =================
// Usa argumentos CLI se fornecidos, senão usa valores do environment.json


// Caminho da fonte do plugin (onde estão os arquivos originais)
$pluginRoot = !empty($CLI_ARGS['plugin-root']) && is_dir($CLI_ARGS['plugin-root'])
    ? rtrim($CLI_ARGS['plugin-root'], '/')
    : $defaultPluginRoot;

// Caminho de destino do plugin (onde serão gerados os arquivos processados)
$deployPluginRoot = !empty($CLI_ARGS['deploy-plugin-root']) && is_dir($CLI_ARGS['deploy-plugin-root'])
    ? rtrim($CLI_ARGS['deploy-plugin-root'], '/')
    : null;

// Compatibilidade com versões anteriores: --test-plugin funciona como alias
if (!empty($CLI_ARGS['test-plugin']) && is_dir($defaultDeployPluginRoot)) {
    $deployPluginRoot = $defaultDeployPluginRoot;
}

// Valida se o diretório fonte existe
if (!is_dir($pluginRoot)) {
    fwrite(STDERR, "ERRO: Diretório fonte do plugin inválido: $pluginRoot\n");
    exit(1);
}

// ================= PREPARAÇÃO DO DIRETÓRIO DE DESTINO =================
// Só faz cópia se o argumento --deploy-plugin-root foi passado explicitamente
if ($deployPluginRoot && $deployPluginRoot !== $pluginRoot) {
    if (!is_dir($deployPluginRoot)) {
        if (!@mkdir($deployPluginRoot, 0775, true) && !is_dir($deployPluginRoot)) {
            fwrite(STDERR, "ERRO: Diretório de destino inválido e falha ao criar: $deployPluginRoot\n");
            exit(1);
        }
    }

    // ================= CÓPIA RECURSIVA DO PLUGIN ORIGINAL =================
    // Copia todo o conteúdo do plugin original para a pasta de destino
    function recursiveCopy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0775, true);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                // Ignora diretórios que não devem ser copiados
                if (in_array($file, ['.git', 'node_modules'])) continue;

                $srcPath = $src . DIRECTORY_SEPARATOR . $file;
                $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

                if (is_dir($srcPath)) {
                    // Cópia recursiva para diretórios
                    recursiveCopy($srcPath, $dstPath);
                } else {
                    // Cópia simples para arquivos
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    // Executa a cópia completa do plugin original para o destino
    recursiveCopy($pluginRoot, $deployPluginRoot);

    // ================= REDIRECIONAMENTO PARA PASTA DE DESTINO =================
    // A partir deste ponto, todas as operações são feitas na pasta de destino
    // Isso garante que o plugin original nunca seja modificado
    $pluginRoot = $deployPluginRoot;
}

$dataDir = $pluginRoot . '/db/data';
$orphDir = $pluginRoot . '/db/orphans';
@mkdir($dataDir, 0775, true);
@mkdir($orphDir, 0775, true);

// ================= FUNÇÕES UTILITÁRIAS =================
// Conjunto de funções auxiliares para manipulação de arquivos e dados

function jsonRead(string $p): ?array {
    if (!is_file($p)) return null;
    $d = json_decode(file_get_contents($p), true);
    return is_array($d) ? $d : null;
}

function jsonWrite(string $p, array $data): void {
    @mkdir(dirname($p), 0775, true);
    file_put_contents($p, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function readFileIfExists(string $p): ?string {
    return is_file($p) ? file_get_contents($p) : null;
}

function buildChecksum(?string $html, ?string $css): array {
    $h = ($html === null || $html === '') ? '' : md5($html);
    $c = ($css === null || $css === '') ? '' : md5($css);
    $combined = ($h === '' && $c === '') ? '' : md5(($html ?? '') . ($css ?? ''));
    return ['html' => $h, 'css' => $c, 'combined' => $combined];
}

function checksumsEqual(array $a, array $b): bool {
    return ($a['html'] ?? null) == ($b['html'] ?? null) &&
           ($a['css'] ?? null) == ($b['css'] ?? null) &&
           ($a['combined'] ?? null) == ($b['combined'] ?? null);
}

function incrementVersion(?string $v): string {
    if (!$v) return '1.0';
    $p = explode('.', $v);
    if (count($p) == 2 && ctype_digit($p[1])) {
        $p[1] = (string)((int)$p[1] + 1);
        return implode('.', $p);
    }
    return '1.0';
}

function resourcePaths(string $base, string $lang, string $tipo, string $id, bool $baseIsResources = false): array {
    $baseDir = $baseIsResources ? $base : $base . '/resources';
    $dir = $baseDir . '/' . $lang . '/' . $tipo . '/' . $id;
    return ['html' => $dir . '/' . $id . '.html', 'css' => $dir . '/' . $id . '.css'];
}

// ================= Load existing (version) =================
// Carrega os arquivos Data.json existentes para manter controle de versão
// Isso permite comparar checksums e decidir se deve incrementar a versão

$existing = ['layouts' => [], 'paginas' => [], 'componentes' => [], 'variaveis' => []];
foreach (['layouts' => 'LayoutsData.json', 'paginas' => 'PaginasData.json', 'componentes' => 'ComponentesData.json', 'variaveis' => 'VariaveisData.json'] as $tipo => $file) {
    $arr = jsonRead($dataDir . '/' . $file) ?? [];
    foreach ($arr as $r) {
        switch ($tipo) {
            case 'layouts':
            case 'componentes':
                if (isset($r['language'], $r['id'])) $existing[$tipo][$r['language'] . '|' . $r['id']] = $r;
                break;
            case 'paginas':
                if (isset($r['language'], $r['id'])) $existing[$tipo][$r['language'] . '|' . ($r['modulo'] ?? '') . '|' . $r['id']] = $r;
                break;
            case 'variaveis':
                if (isset($r['linguagem_codigo'], $r['id'])) $existing[$tipo][$r['linguagem_codigo'] . '|' . ($r['modulo'] ?? '') . '|' . $r['id'] . '|' . ($r['grupo'] ?? '')] = $r;
                break;
        }
    }
}

// ================= MAPEAMENTO DE IDIOMAS =================
// Carrega o arquivo resources.map.php que define os idiomas suportados
// e os arquivos de dados correspondentes para cada idioma

$resourcesDir = $pluginRoot . '/resources';
$mapFile = $resourcesDir . '/resources.map.php';
if (!is_file($mapFile)) {
    fwrite(STDERR, "ERRO: Arquivo resources.map.php não encontrado em: $resourcesDir\n");
    exit(1);
}
$map = include $mapFile;
if (!isset($map['languages'])) {
    fwrite(STDERR, "ERRO: Estrutura inválida no arquivo resources.map.php\n");
    exit(1);
}
$languages = array_keys($map['languages']);

// ================= ESTRUTURAS DE DADOS =================
// Inicializa arrays para armazenar os dados processados de cada tipo de recurso

$layoutsData = $pagesData = $componentsData = $variablesData = [];
$orphans = ['layouts' => [], 'paginas' => [], 'componentes' => [], 'variaveis' => []];
$idxLayouts = $idxComponentes = $idxPaginasId = $idxPaginasPath = $idxVariaveis = [];

// ================= FUNÇÃO DE CONTROLE DE VERSÃO =================
// Função que calcula a versão e checksum baseado no conteúdo HTML/CSS
// Mantém a versão se o conteúdo não mudou, incrementa se mudou

$versaoChecksum = function(string $tipo, string $key, ?string $html, ?string $css) use (&$existing): array {
    $cks = buildChecksum($html, $css);
    $versao = 1;
    if (isset($existing[$tipo][$key])) {
        $old = $existing[$tipo][$key];
        $oldChecksum = $old['checksum'] ?? null;
        if (is_string($oldChecksum)) {
            $dec = json_decode($oldChecksum, true);
            if (is_array($dec)) $oldChecksum = $dec;
        }
        if (is_array($oldChecksum) && checksumsEqual($oldChecksum, $cks)) {
            $versao = (int)($old['versao'] ?? 1);
        } else {
            $versao = (int)($old['versao'] ?? 1) + 1;
        }
    }
    return [$versao, $cks];
};

// ================= PROCESSAMENTO DOS RECURSOS GLOBAIS =================
// Processa todos os recursos globais do plugin (layouts, componentes, páginas, variáveis)
// para cada idioma suportado definido no resources.map.php

foreach ($languages as $lang) {
    $langInfo = $map['languages'][$lang] ?? null;
    if (!$langInfo || !isset($langInfo['data'])) continue;
    $dataFiles = $langInfo['data'];

    // ================= PROCESSAMENTO DE LAYOUTS =================
    if (!empty($dataFiles['layouts'])) {
        $lista = jsonRead($resourcesDir . '/' . $lang . '/' . $dataFiles['layouts']) ?? [];
        foreach ($lista as $l) {
            $id = $l['id'] ?? null;
            if (!$id) {
                $orphans['layouts'][] = $l + ['_motivo' => 'sem id', 'language' => $lang];
                continue;
            }
            $key = $lang . '|' . $id;
            if (isset($idxLayouts[$key])) {
                $orphans['layouts'][] = $l + ['_motivo' => 'duplicidade id', 'language' => $lang];
                continue;
            }
            $idxLayouts[$key] = true;
            $paths = resourcePaths($resourcesDir, $lang, 'layouts', $id, true);
            $html = readFileIfExists($paths['html']);
            $css = readFileIfExists($paths['css']);
            [$versao, $cks] = $versaoChecksum('layouts', $key, $html, $css);
            $layoutsData[] = [
                'nome' => $l['name'] ?? ($l['nome'] ?? $id),
                'id' => $id,
                'language' => $lang,
                'html' => $html,
                'css' => $css,
                'framework_css' => $l['framework_css'] ?? null,
                'status' => $l['status'] ?? 'A',
                'versao' => $versao,
                'file_version' => $l['version'] ?? null,
                'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    // ================= PROCESSAMENTO DE COMPONENTES =================
    if (!empty($dataFiles['components'])) {
        $lista = jsonRead($resourcesDir . '/' . $lang . '/' . $dataFiles['components']) ?? [];
        foreach ($lista as $c) {
            $id = $c['id'] ?? null;
            if (!$id) {
                $orphans['componentes'][] = $c + ['_motivo' => 'sem id', 'language' => $lang];
                continue;
            }
            $key = $lang . '|' . $id;
            if (isset($idxComponentes[$key])) {
                $orphans['componentes'][] = $c + ['_motivo' => 'duplicidade id', 'language' => $lang];
                continue;
            }
            $idxComponentes[$key] = true;
            $paths = resourcePaths($resourcesDir, $lang, 'components', $id, true);
            $html = readFileIfExists($paths['html']);
            $css = readFileIfExists($paths['css']);
            [$versao, $cks] = $versaoChecksum('componentes', $key, $html, $css);
            $componentsData[] = [
                'nome' => $c['name'] ?? ($c['nome'] ?? $id),
                'id' => $id,
                'language' => $lang,
                'modulo' => $c['module'] ?? ($c['modulo'] ?? null),
                'html' => $html,
                'css' => $css,
                'framework_css' => $c['framework_css'] ?? null,
                'status' => $c['status'] ?? 'A',
                'versao' => $versao,
                'file_version' => $c['version'] ?? null,
                'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    // ================= PROCESSAMENTO DE PÁGINAS =================
    if (!empty($dataFiles['pages'])) {
        $lista = jsonRead($resourcesDir . '/' . $lang . '/' . $dataFiles['pages']) ?? [];
        foreach ($lista as $p) {
            $id = $p['id'] ?? null;
            if (!$id) {
                $orphans['paginas'][] = $p + ['_motivo' => 'sem id', 'language' => $lang];
                continue;
            }
            $mod = $p['module'] ?? ($p['modulo'] ?? null);
            $path = $p['path'] ?? ($p['caminho'] ?? ($id . '/'));
            $kId = $lang . '|' . ($mod ?? '') . '|' . $id;
            if (isset($idxPaginasId[$kId])) {
                $orphans['paginas'][] = $p + ['_motivo' => 'duplicidade id', 'language' => $lang];
                continue;
            }
            $kPath = $lang . '|' . strtolower(trim($path, '/'));
            if (isset($idxPaginasPath[$kPath])) {
                $orphans['paginas'][] = $p + ['_motivo' => 'duplicidade caminho', 'language' => $lang];
                continue;
            }
            $idxPaginasId[$kId] = true;
            $idxPaginasPath[$kPath] = true;
            $paths = resourcePaths($resourcesDir, $lang, 'pages', $id, true);
            $html = readFileIfExists($paths['html']);
            $css = readFileIfExists($paths['css']);
            [$versao, $cks] = $versaoChecksum('paginas', $kId, $html, $css);
            $pagesData[] = [
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
                'framework_css' => $p['framework_css'] ?? null,
                'status' => $p['status'] ?? 'A',
                'versao' => $versao,
                'file_version' => $p['version'] ?? null,
                'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    // ================= PROCESSAMENTO DE VARIÁVEIS =================
    if (!empty($dataFiles['variables'])) {
        $lista = jsonRead($resourcesDir . '/' . $lang . '/' . $dataFiles['variables']) ?? [];
        foreach ($lista as $v) {
            $id = $v['id'] ?? null;
            if (!$id) {
                $orphans['variaveis'][] = $v + ['_motivo' => 'sem id', 'linguagem_codigo' => $lang];
                continue;
            }
            $mod = $v['module'] ?? ($v['modulo'] ?? '');
            $grp = $v['group'] ?? ($v['grupo'] ?? null);
            $base = $lang . '|' . $mod . '|' . $id;
            if (!isset($idxVariaveis[$base])) $idxVariaveis[$base] = [];
            $groups = $idxVariaveis[$base];
            if ($grp === null || $grp === '') {
                if (!empty($groups) || in_array('', $groups, true)) {
                    $orphans['variaveis'][] = $v + ['_motivo' => 'duplicidade sem group', 'linguagem_codigo' => $lang];
                    continue;
                }
            } else {
                if (in_array($grp, $groups, true)) {
                    $orphans['variaveis'][] = $v + ['_motivo' => 'duplicidade group repetido', 'linguagem_codigo' => $lang];
                    continue;
                }
            }
            $idxVariaveis[$base][] = ($grp ?? '');
            $variablesData[] = [
                'linguagem_codigo' => $lang,
                'modulo' => $mod !== '' ? $mod : null,
                'id' => $id,
                'valor' => $v['value'] ?? ($v['valor'] ?? null),
                'tipo' => $v['type'] ?? ($v['tipo'] ?? null),
                'grupo' => $grp,
                'descricao' => $v['description'] ?? ($v['descricao'] ?? null)
            ];
        }
    }
}

// ================= PROCESSAMENTO DE MÓDULOS =================
// Processa os módulos do plugin, se existirem
// Os módulos têm a mesma estrutura que os recursos globais, mas ficam em <plugin>/modules/<modulo>/

$modulesDir = $pluginRoot . '/modules';
if (is_dir($modulesDir)) {
    $mods = glob($modulesDir . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($mods as $modPath) {
        $modId = basename($modPath);
        $jsonFile = $modPath . '/' . $modId . '.json';
        $data = jsonRead($jsonFile);
        if (!$data || empty($data['resources'])) continue;

        foreach ($languages as $lang) {
            if (empty($data['resources'][$lang])) continue;
            $res = $data['resources'][$lang];

            // Processa layouts, componentes e páginas dos módulos (mesma lógica dos globais)
            foreach (['layouts', 'components', 'pages'] as $tipo) {
                $arr = $res[$tipo] ?? [];
                foreach ($arr as $item) {
                    $id = $item['id'] ?? null;
                    if (!$id) continue;
                    $paths = resourcePaths($modPath, $lang, $tipo, $id);
                    $html = readFileIfExists($paths['html']);
                    $css = readFileIfExists($paths['css']);

                    if ($tipo === 'layouts') {
                        $key = $lang . '|' . $id;
                        if (isset($idxLayouts[$key])) {
                            $orphans['layouts'][] = $item + ['_motivo' => 'duplicidade id', 'language' => $lang, 'modulo' => $modId];
                            continue;
                        }
                        $idxLayouts[$key] = true;
                        [$versao, $cks] = $versaoChecksum('layouts', $key, $html, $css);
                        $layoutsData[] = [
                            'nome' => $item['name'] ?? $id,
                            'id' => $id,
                            'language' => $lang,
                            'modulo' => $modId,
                            'html' => $html,
                            'css' => $css,
                            'framework_css' => $item['framework_css'] ?? null,
                            'status' => $item['status'] ?? 'A',
                            'versao' => $versao,
                            'file_version' => $item['version'] ?? null,
                            'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
                        ];
                    } elseif ($tipo === 'components') {
                        $key = $lang . '|' . $id;
                        if (isset($idxComponentes[$key])) {
                            $orphans['componentes'][] = $item + ['_motivo' => 'duplicidade id', 'language' => $lang, 'modulo' => $modId];
                            continue;
                        }
                        $idxComponentes[$key] = true;
                        [$versao, $cks] = $versaoChecksum('componentes', $key, $html, $css);
                        $componentsData[] = [
                            'nome' => $item['name'] ?? $id,
                            'id' => $id,
                            'language' => $lang,
                            'modulo' => $modId,
                            'html' => $html,
                            'css' => $css,
                            'framework_css' => $item['framework_css'] ?? null,
                            'status' => $item['status'] ?? 'A',
                            'versao' => $versao,
                            'file_version' => $item['version'] ?? null,
                            'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
                        ];
                    } else { // pages
                        $path = $item['path'] ?? ($id . '/');
                        $kId = $lang . '|' . $modId . '|' . $id;
                        if (isset($idxPaginasId[$kId])) {
                            $orphans['paginas'][] = $item + ['_motivo' => 'duplicidade id', 'language' => $lang, 'modulo' => $modId];
                            continue;
                        }
                        $kPath = $lang . '|' . strtolower(trim($path, '/'));
                        if (isset($idxPaginasPath[$kPath])) {
                            $orphans['paginas'][] = $item + ['_motivo' => 'duplicidade caminho', 'language' => $lang, 'modulo' => $modId];
                            continue;
                        }
                        $idxPaginasId[$kId] = true;
                        $idxPaginasPath[$kPath] = true;
                        [$versao, $cks] = $versaoChecksum('paginas', $kId, $html, $css);
                        $pagesData[] = [
                            'layout_id' => $item['layout'] ?? null,
                            'nome' => $item['name'] ?? $id,
                            'id' => $id,
                            'language' => $lang,
                            'caminho' => $path,
                            'tipo' => $item['type'] ?? null,
                            'modulo' => $modId,
                            'opcao' => $item['option'] ?? null,
                            'raiz' => $item['root'] ?? null,
                            'sem_permissao' => $item['without_permission'] ?? null,
                            'html' => $html,
                            'css' => $css,
                            'framework_css' => $item['framework_css'] ?? null,
                            'status' => $item['status'] ?? 'A',
                            'versao' => $versao,
                            'file_version' => $item['version'] ?? null,
                            'checksum' => json_encode($cks, JSON_UNESCAPED_UNICODE)
                        ];
                    }
                }

                // Processa variáveis dos módulos
                if (!empty($res['variables'])) {
                    foreach ($res['variables'] as $v) {
                        $id = $v['id'] ?? null;
                        if (!$id) continue;
                        $grp = $v['group'] ?? null;
                        $base = $lang . '|' . $modId . '|' . $id;
                        if (!isset($idxVariaveis[$base])) $idxVariaveis[$base] = [];
                        $groups = $idxVariaveis[$base];
                        if ($grp === null || $grp === '') {
                            if (!empty($groups) || in_array('', $groups, true)) {
                                $orphans['variaveis'][] = $v + ['_motivo' => 'duplicidade sem group', 'linguagem_codigo' => $lang, 'modulo' => $modId];
                                continue;
                            }
                        } else {
                            if (in_array($grp, $groups, true)) {
                                $orphans['variaveis'][] = $v + ['_motivo' => 'duplicidade group repetido', 'linguagem_codigo' => $lang, 'modulo' => $modId];
                                continue;
                            }
                        }
                        $idxVariaveis[$base][] = ($grp ?? '');
                        $variablesData[] = [
                            'linguagem_codigo' => $lang,
                            'modulo' => $modId,
                            'id' => $id,
                            'valor' => $v['value'] ?? null,
                            'tipo' => $v['type'] ?? null,
                            'grupo' => $grp,
                            'descricao' => $v['description'] ?? null
                        ];
                    }
                }
            }
        }
    }
}

// ================= SALVAMENTO DOS ARQUIVOS FINAIS =================
// Salva todos os dados processados nos arquivos JSON correspondentes
// Também salva os recursos órfãos (com problemas) para análise posterior

jsonWrite($dataDir . '/LayoutsData.json', $layoutsData);
jsonWrite($dataDir . '/PaginasData.json', $pagesData);
jsonWrite($dataDir . '/ComponentesData.json', $componentsData);
jsonWrite($dataDir . '/VariaveisData.json', $variablesData);

// Salva os recursos órfãos em arquivos separados para debug
foreach (['Layouts', 'Paginas', 'Componentes', 'Variaveis'] as $T) {
    jsonWrite($orphDir . '/' . $T . 'Data.json', $orphans[strtolower($T)] ?? []);
}

// Remove arquivo legado Data.json se existir (substituído pelos arquivos separados)
$legacy = $dataDir . '/Data.json';
if (is_file($legacy)) @unlink($legacy);

// ================= RELATÓRIO FINAL =================
// Exibe um resumo do processamento realizado

$summary = sprintf(
    "Target: %s\nLayouts=%d Pages=%d Components=%d Variables=%d Orphans=%d\n",
    $pluginRoot,
    count($layoutsData), count($pagesData), count($componentsData), count($variablesData),
    array_sum(array_map('count', $orphans))
);
echo $summary;
exit(0);
