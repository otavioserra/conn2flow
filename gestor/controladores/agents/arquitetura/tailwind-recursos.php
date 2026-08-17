<?php

declare(strict_types=1);

/**
 * Builder incremental Tailwind por recurso (req-114).
 *
 * Este arquivo só é acionado pelo compilador CLI de recursos. O runtime web nunca executa
 * processos nem depende de Node/Tailwind.
 */

const TAILWIND_RECURSOS_MANIFEST_VERSION = 1;

function tailwind_recursos_normalizar_path(string $path): string
{
    return str_replace('\\', '/', $path);
}

function tailwind_recursos_path_dentro(string $path, string $root): bool
{
    $path = rtrim(tailwind_recursos_normalizar_path($path), '/');
    $root = rtrim(tailwind_recursos_normalizar_path($root), '/');
    if (DIRECTORY_SEPARATOR === '\\') {
        $path = strtolower($path);
        $root = strtolower($root);
    }
    return $path === $root || str_starts_with($path, $root . '/');
}

function tailwind_recursos_relativo(string $fromDir, string $target): string
{
    $from = explode('/', trim(tailwind_recursos_normalizar_path($fromDir), '/'));
    $to = explode('/', trim(tailwind_recursos_normalizar_path($target), '/'));

    if (isset($from[0], $to[0]) && strcasecmp($from[0], $to[0]) !== 0) {
        return tailwind_recursos_normalizar_path($target);
    }

    while ($from && $to && strcasecmp((string)$from[0], (string)$to[0]) === 0) {
        array_shift($from);
        array_shift($to);
    }

    $relative = str_repeat('../', count($from)) . implode('/', $to);
    return $relative === '' ? './' : $relative;
}

function tailwind_recursos_css_string(string $value): string
{
    return str_replace(['\\', '"'], ['/', '\\"'], $value);
}

/** @return list<string> */
function tailwind_recursos_parse_command(string $command): array
{
    preg_match_all('/"((?:\\\\.|[^"])*)"|\'((?:\\\\.|[^\'])*)\'|([^\s]+)/', $command, $matches, PREG_SET_ORDER);
    $tokens = [];
    foreach ($matches as $match) {
        $token = $match[1] !== '' ? stripcslashes($match[1]) : ($match[2] !== '' ? stripcslashes($match[2]) : $match[3]);
        if ($token !== '') $tokens[] = $token;
    }
    return $tokens;
}

/** Remove input/output antigos de uma configuração legada e devolve somente o comando-base. */
function tailwind_recursos_command_base(array $tokens): array
{
    $base = [];
    for ($i = 0, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        if (in_array($token, ['-i', '--input', '-o', '--output'], true)) {
            $i++;
            continue;
        }
        if (str_starts_with($token, '--input=') || str_starts_with($token, '--output=')) continue;
        if ($token === '--minify') continue;
        $base[] = $token;
    }
    return $base;
}

/**
 * Substitui runners de pacote por um binário Tailwind local já localizado.
 *
 * No Windows, `npx` normalmente é um arquivo .cmd. O proc_open com comando em
 * array não resolve esse shim pelo nome simples, embora consiga iniciar o
 * caminho absoluto gerado em node_modules/.bin.
 *
 * @param list<string> $tokens
 * @param list<string> $localCandidates
 * @return list<string>
 */
function tailwind_recursos_normalizar_runner(array $tokens, array $localCandidates): array
{
    if ($tokens === [] || $localCandidates === []) return $tokens;

    $executable = strtolower(basename(str_replace('\\', '/', $tokens[0])));
    $candidate = $localCandidates[0];

    if (
        !str_contains(str_replace('\\', '/', $tokens[0]), '/')
        && in_array($executable, ['tailwindcss', 'tailwindcss.cmd', 'tailwindcss.exe'], true)
    ) {
        return array_merge([$candidate], array_slice($tokens, 1));
    }

    if (
        in_array($executable, ['npx', 'npx.cmd', 'npx.exe'], true)
        && isset($tokens[1])
        && in_array(strtolower($tokens[1]), ['@tailwindcss/cli', 'tailwindcss'], true)
    ) {
        return array_merge([$candidate], array_slice($tokens, 2));
    }

    return $tokens;
}

/** @return list<string> */
function tailwind_recursos_local_candidates(): array
{
    global $SYSTEM_PATH, $GESTOR_DIR;

    $binary = PHP_OS_FAMILY === 'Windows' ? 'tailwindcss.cmd' : 'tailwindcss';
    $candidates = [
        $SYSTEM_PATH . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . $binary,
        $GESTOR_DIR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . $binary,
    ];

    return array_values(array_unique(array_filter($candidates, 'is_file')));
}

/** @return list<string>|null */
function tailwind_recursos_resolver_command(): ?array
{
    $args = $GLOBALS['CLI_ARGS'] ?? [];
    $localCandidates = tailwind_recursos_local_candidates();
    $json = $args['tailwind-command-json'] ?? getenv('TAILWINDCSS_COMMAND_JSON') ?: null;
    if (is_string($json) && $json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && $decoded !== [] && count(array_filter($decoded, 'is_string')) === count($decoded)) {
            return tailwind_recursos_normalizar_runner(
                tailwind_recursos_command_base(array_values($decoded)),
                $localCandidates
            );
        }
        throw new RuntimeException('TAILWINDCSS_COMMAND_JSON deve ser um array JSON de argumentos.');
    }

    $configured = $args['tailwind-command'] ?? getenv('TAILWINDCSS_COMMAND') ?: null;
    if (is_string($configured) && trim($configured) !== '') {
        $tokens = tailwind_recursos_command_base(tailwind_recursos_parse_command($configured));
        if ($tokens !== []) return tailwind_recursos_normalizar_runner($tokens, $localCandidates);
    }

    if ($localCandidates !== []) return [$localCandidates[0]];

    return null;
}

/** @return array{code:int,stdout:string,stderr:string} */
function tailwind_recursos_exec(array $command, ?string $cwd = null): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = @proc_open($command, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Não foi possível iniciar o Tailwind CLI: ' . ($command[0] ?? '(comando vazio)'));
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return ['code' => $code, 'stdout' => $stdout, 'stderr' => $stderr];
}

function tailwind_recursos_cli_version(array $command): string
{
    $result = tailwind_recursos_exec(array_merge($command, ['--help']));
    $text = $result['stdout'] . "\n" . $result['stderr'];
    if (preg_match('/tailwindcss\s+v?([0-9]+(?:\.[0-9]+){1,3})/i', $text, $match)) return $match[1];
    return 'unknown';
}

function tailwind_recursos_input_central(): ?string
{
    global $GESTOR_DIR, $isProjectMode;
    $args = $GLOBALS['CLI_ARGS'] ?? [];
    if (isset($args['tailwind-input']) && is_string($args['tailwind-input'])) {
        $candidate = realpath($args['tailwind-input']);
        if ($candidate === false || !is_file($candidate)) throw new RuntimeException('Input Tailwind informado não existe.');
        return $candidate;
    }

    $candidate = $isProjectMode
        ? $GESTOR_DIR . 'contents' . DIRECTORY_SEPARATOR . 'tailwindcss' . DIRECTORY_SEPARATOR . 'input.css'
        : $GESTOR_DIR . 'assets' . DIRECTORY_SEPARATOR . 'tailwindcss' . DIRECTORY_SEPARATOR . 'system-input.css';

    return is_file($candidate) ? realpath($candidate) ?: $candidate : null;
}

/** @return list<string> */
function tailwind_recursos_sources(array $metadata, string $resourceDir): array
{
    global $GESTOR_DIR;
    $sources = [];
    $declaredSources = (array)($metadata['tailwind_sources'] ?? []);
    if ($declaredSources !== []) {
        $reason = $metadata['tailwind_sources_reason'] ?? null;
        if (!is_string($reason) || trim($reason) === '') {
            $id = is_string($metadata['id'] ?? null) ? $metadata['id'] : '(sem id)';
            throw new RuntimeException("Recurso {$id} usa tailwind_sources sem tailwind_sources_reason.");
        }
    }
    foreach ($declaredSources as $source) {
        if (!is_string($source) || trim($source) === '') continue;
        $candidate = realpath($resourceDir . DIRECTORY_SEPARATOR . $source);
        if ($candidate === false || !is_file($candidate)) {
            throw new RuntimeException("Fonte Tailwind adicional não encontrada: {$source}");
        }
        if (!tailwind_recursos_path_dentro($candidate, $GESTOR_DIR)) {
            throw new RuntimeException("Fonte Tailwind fora da raiz permitida: {$source}");
        }
        $sources[] = $candidate;
    }
    sort($sources, SORT_STRING);
    return array_values(array_unique($sources));
}

/**
 * Resolve dependências semânticas de recursos somente durante o build.
 *
 * O metadado referencia IDs do Gestor, nunca caminhos publicados. Os arquivos
 * físicos existem no repositório de desenvolvimento e são removidos do pacote
 * depois que o css_precompiled já foi persistido nos Data.json.
 *
 * @return list<string>
 */
function tailwind_recursos_dependencies(array $metadata, string $scope, ?string $module, string $language, string $type): array
{
    global $GESTOR_DIR;

    $dependencies = (array)($metadata['tailwind_dependencies'] ?? []);
    if ($dependencies !== []) {
        $reason = $metadata['tailwind_dependencies_reason'] ?? null;
        if (!is_string($reason) || trim($reason) === '') {
            $id = is_string($metadata['id'] ?? null) ? $metadata['id'] : '(sem id)';
            throw new RuntimeException("Recurso {$id} usa tailwind_dependencies sem tailwind_dependencies_reason.");
        }
    }

    // Um bundle de página sempre inclui o layout já declarado no contrato da
    // própria página. Primeiro procura no módulo e depois nos recursos globais.
    if (($metadata['tailwind_bundle'] ?? false) === true && $type === 'pages') {
        $pageId = is_string($metadata['id'] ?? null) ? $metadata['id'] : '(sem id)';
        $layoutId = $metadata['layout'] ?? null;
        if (!is_string($layoutId) || trim($layoutId) === '') {
            throw new RuntimeException("Página {$pageId} usa tailwind_bundle sem layout.");
        }
        $layoutLanguage = $metadata['tailwind_layout_language'] ?? $language;
        if (!is_string($layoutLanguage) || $layoutLanguage === '') {
            throw new RuntimeException("Página {$pageId} possui tailwind_layout_language inválido.");
        }
        $layoutDependency = ['type' => 'layouts', 'id' => $layoutId, 'language' => $layoutLanguage];
        $moduleCandidate = $module !== null
            ? tailwind_recursos_dependency_path($layoutDependency + ['module' => $module])
            : null;
        if ($moduleCandidate !== null && is_file($moduleCandidate)) {
            $dependencies[] = $layoutDependency + ['module' => $module];
        } else {
            $dependencies[] = $layoutDependency + ['scope' => 'global'];
        }
    }

    $resolved = [];
    foreach ($dependencies as $dependency) {
        if (!is_array($dependency)) {
            throw new RuntimeException('Cada tailwind_dependencies deve ser um objeto com type e id.');
        }
        $dependency += ['language' => $language];
        if (!array_key_exists('module', $dependency) && ($dependency['scope'] ?? null) !== 'global') {
            $dependency['module'] = $module;
        }
        $candidate = tailwind_recursos_dependency_path($dependency);
        if ($candidate === null || !is_file($candidate)) {
            $id = (string)($dependency['id'] ?? '(sem id)');
            throw new RuntimeException("Dependência Tailwind do Gestor não encontrada: {$id}");
        }
        if (!tailwind_recursos_path_dentro($candidate, $GESTOR_DIR)) {
            throw new RuntimeException("Dependência Tailwind fora da raiz permitida: {$candidate}");
        }
        $resolved[] = realpath($candidate) ?: $candidate;
    }

    sort($resolved, SORT_STRING);
    return array_values(array_unique($resolved));
}

function tailwind_recursos_dependency_path(array $dependency): ?string
{
    global $GESTOR_DIR;

    $allowedTypes = ['layouts', 'components', 'pages', 'templates'];
    $type = $dependency['type'] ?? null;
    $id = $dependency['id'] ?? null;
    $language = $dependency['language'] ?? null;
    if (!is_string($type) || !in_array($type, $allowedTypes, true)
        || !is_string($id) || $id === '' || str_contains($id, '/') || str_contains($id, '\\')
        || !is_string($language) || $language === '') {
        return null;
    }

    $module = $dependency['module'] ?? null;
    if (($dependency['scope'] ?? null) === 'global') $module = null;
    if ($module !== null && (!is_string($module) || $module === '' || str_contains($module, '/') || str_contains($module, '\\'))) {
        return null;
    }

    $base = $module === null
        ? $GESTOR_DIR . DIRECTORY_SEPARATOR . 'resources'
        : $GESTOR_DIR . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'resources';

    return $base . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $type
        . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.html';
}

/**
 * Registra um aviso de build (findings F1 e F4c do review de 2026-08-15).
 *
 * Avisa por padrão e só interrompe com `--tailwind-strict`: os defeitos cobertos são silenciosos e
 * antigos, então transformá-los em erro de imediato pararia builds que hoje passam. O flag existe
 * para o CI apertar quando o inventário estiver limpo.
 */
function tailwind_recursos_aviso(string $mensagem): void
{
    if (!isset($GLOBALS['TAILWIND_RECURSOS_AVISOS'])) $GLOBALS['TAILWIND_RECURSOS_AVISOS'] = [];
    $GLOBALS['TAILWIND_RECURSOS_AVISOS'][] = $mensagem;
    cliProgress('       [aviso] ' . $mensagem, false);
}

function tailwind_recursos_descriptor(array $metadata, string $scope, ?string $module, string $language, string $type, string $base, bool $baseIsResourcesDir): ?array
{
    $id = $metadata['id'] ?? null;
    if (!is_string($id) || $id === '' ) return null;

    // F4c(a): recurso com utilities no HTML mas sem `framework_css` é IGNORADO pelo compilador e
    // nunca ganha `.precompiled.css` — em silêncio. Foi assim que o `form-ui`, que serve overlay e
    // mensagens de erro a todos os formulários, ficou sem estilo por lotes inteiros.
    if (getFrameworkCss($metadata) !== 'tailwindcss') {
        $candidato = resourcePaths($base, $language, $type, $id, $baseIsResourcesDir);
        if (is_file($candidato['html']) && tailwind_recursos_html_usa_tailwind((string)file_get_contents($candidato['html']))) {
            tailwind_recursos_aviso(
                ($module ? $module . '/' : '') . $type . '/' . $id . ' (' . $language . '): ' .
                'o HTML usa utilities Tailwind mas o recurso não declara framework_css=tailwindcss — ele não será compilado.'
            );
        }
        return null;
    }

    $paths = resourcePaths($base, $language, $type, $id, $baseIsResourcesDir);
    if (!is_file($paths['html'])) return null;
    $sources = array_merge(
        tailwind_recursos_sources($metadata, $paths['dir']),
        tailwind_recursos_dependencies($metadata, $scope, $module, $language, $type)
    );
    sort($sources, SORT_STRING);
    $sources = array_values(array_unique($sources));
    $bundle = ($metadata['tailwind_bundle'] ?? false) === true;
    if ($bundle && $type !== 'pages') {
        throw new RuntimeException("tailwind_bundle só pode ser usado em páginas: {$id}");
    }
    if ($bundle && $sources === []) {
        throw new RuntimeException("Página {$id} usa tailwind_bundle sem dependências ou fontes resolvidas.");
    }
    $safelist = array_values(array_filter((array)($metadata['tailwind_safelist'] ?? []), fn($v) => is_string($v) && trim($v) !== ''));
    sort($safelist, SORT_STRING);
    return [
        'key' => implode('|', [$scope, $module ?? '', $language, $type, $id]),
        'scope' => $scope,
        'module' => $module,
        'language' => $language,
        'type' => $type,
        'id' => $id,
        'layout' => $type === 'layouts',
        'layout_id' => (string)($metadata['layout'] ?? ''), // F3: liga a página ao layout que a serve.
        'bundle' => $bundle,
        'html' => $paths['html'],
        'css' => $paths['css'], // F1: CSS autoral do recurso, conferido contra os tokens da saída.
        'output' => $paths['css_precompiled'],
        'dir' => $paths['dir'],
        'sources' => $sources,
        'safelist' => $safelist,
    ];
}

/** @return list<array<string,mixed>> */
function tailwind_recursos_descobrir(array $map): array
{
    global $RESOURCES_DIR, $MODULES_DIR;
    $found = [];
    $languages = array_keys($map['languages'] ?? []);

    foreach ($languages as $language) {
        $dataFiles = $map['languages'][$language]['data'] ?? [];
        foreach (['layouts' => 'layouts', 'components' => 'components', 'pages' => 'pages', 'templates' => 'templates'] as $dataKey => $type) {
            if (empty($dataFiles[$dataKey])) continue;
            $items = jsonRead($RESOURCES_DIR . $language . DIRECTORY_SEPARATOR . $dataFiles[$dataKey]) ?? [];
            foreach ($items as $metadata) {
                if (!is_array($metadata)) continue;
                $descriptor = tailwind_recursos_descriptor($metadata, 'global', null, $language, $type, $RESOURCES_DIR, true);
                if ($descriptor !== null) $found[$descriptor['key']] = $descriptor;
            }
        }
    }

    if (is_dir($MODULES_DIR)) {
        foreach (glob($MODULES_DIR . '*', GLOB_ONLYDIR) ?: [] as $modulePath) {
            $module = basename($modulePath);
            $moduleData = jsonRead($modulePath . DIRECTORY_SEPARATOR . $module . '.json');
            if (!$moduleData) continue;
            foreach ($languages as $language) {
                $resources = $moduleData['resources'][$language] ?? [];
                foreach (['layouts', 'components', 'pages', 'templates'] as $type) {
                    foreach ((array)($resources[$type] ?? []) as $metadata) {
                        if (!is_array($metadata)) continue;
                        $descriptor = tailwind_recursos_descriptor($metadata, 'module', $module, $language, $type, $modulePath, false);
                        if ($descriptor !== null) $found[$descriptor['key']] = $descriptor;
                    }
                }
            }
        }
    }

    ksort($found, SORT_STRING);
    return array_values($found);
}

/** @return array{resources_with_sources:int,additional_sources:int} */
function tailwind_recursos_estatisticas_fontes(array $resources): array
{
    $resourcesWithSources = 0;
    $additionalSources = 0;
    foreach ($resources as $resource) {
        $count = count((array)($resource['sources'] ?? []));
        if ($count === 0) continue;
        $resourcesWithSources++;
        $additionalSources += $count;
    }
    return [
        'resources_with_sources' => $resourcesWithSources,
        'additional_sources' => $additionalSources,
    ];
}

function tailwind_recursos_browser_contract(string $centralInput): array
{
    $content = is_file($centralInput) ? (string)file_get_contents($centralInput) : '';
    if (preg_match('/@(plugin|config)\b/i', $content, $match)) {
        throw new RuntimeException("Diretiva @{$match[1]} não é suportada pelo contrato Tailwind do navegador.");
    }

    $content = preg_replace('/@import\s+["\']tailwindcss(?:\/[^"\']*)?["\'][^;]*;/i', '', $content) ?? $content;
    $content = preg_replace('/@source\s+[^;]+;/i', '', $content) ?? $content;
    $content = trim($content) . "\n";

    $path = dirname($centralInput) . DIRECTORY_SEPARATOR . 'browser-contract.css';
    $hash = hash('sha256', $content);
    if (!is_file($path) || hash('sha256', (string)file_get_contents($path)) !== $hash) {
        tailwind_recursos_atomic_write($path, $content);
    }
    return ['path' => $path, 'hash' => $hash, 'content' => $content];
}

function tailwind_recursos_atomic_write(string $path, string $content): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException("Falha ao criar diretório: {$directory}");
    }
    $new = $path . '.new-' . getmypid() . '-' . bin2hex(random_bytes(3));
    if (file_put_contents($new, $content, LOCK_EX) === false) throw new RuntimeException("Falha ao gravar temporário: {$new}");

    $backup = null;
    if (is_file($path)) {
        $backup = $path . '.bak-' . getmypid() . '-' . bin2hex(random_bytes(3));
        if (!rename($path, $backup)) {
            @unlink($new);
            throw new RuntimeException("Falha ao preparar substituição atômica: {$path}");
        }
    }

    if (!rename($new, $path)) {
        if ($backup !== null) @rename($backup, $path);
        @unlink($new);
        throw new RuntimeException("Falha ao publicar arquivo: {$path}");
    }
    if ($backup !== null) @unlink($backup);
}

function tailwind_recursos_input_temporario(array $resource, string $centralInput, string $tempDir): string
{
    $central = tailwind_recursos_css_string(tailwind_recursos_relativo($tempDir, $centralInput));
    // Layouts e bundles canônicos precisam carregar theme/base/preflight. Recursos
    // isolados importam apenas utilities porque recebem essas camadas do layout.
    $lines = ($resource['layout'] || !empty($resource['bundle']))
        ? ['@import "' . $central . '";']
        : ['@reference "' . $central . '";', '@import "tailwindcss/utilities.css" layer(utilities) source(none);'];

    foreach (array_merge([$resource['html']], $resource['sources']) as $source) {
        $relative = tailwind_recursos_css_string(tailwind_recursos_relativo($tempDir, $source));
        $lines[] = '@source "' . $relative . '";';
    }
    foreach ($resource['safelist'] as $token) {
        $lines[] = '@source inline("' . tailwind_recursos_css_string($token) . '");';
    }
    return implode("\n", $lines) . "\n";
}

function tailwind_recursos_fingerprint(array $resource, string $centralHash, string $version): string
{
    $sourceHashes = [];
    foreach (array_merge([$resource['html']], $resource['sources']) as $source) {
        $sourceHashes[tailwind_recursos_normalizar_path($source)] = hash_file('sha256', $source) ?: '';
    }
    return hash('sha256', json_encode([
        'manifest_version' => TAILWIND_RECURSOS_MANIFEST_VERSION,
        'central' => $centralHash,
        'tailwind' => $version,
        'layout' => $resource['layout'],
        'bundle' => !empty($resource['bundle']),
        'sources' => $sourceHashes,
        'safelist' => $resource['safelist'],
        'minify' => true,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function tailwind_recursos_output_valido(string $path): bool
{
    return is_file($path) && trim((string)file_get_contents($path)) !== '';
}

// ===================================================================================
// Guardas de build — findings F1 e F4c do review de 2026-08-15
// ===================================================================================
//
// As três funções abaixo são PURAS (string → array), justamente porque o defeito que elas cobrem é
// mudo: nenhuma delas quebra hoje, e sem teste voltariam a passar despercebidas.

/**
 * F1 — tokens de `@theme` consumidos pelo CSS autoral e AUSENTES na saída compilada.
 *
 * O Tailwind v4 só emite a variável de tema que ele VÊ sendo usada, e ele vê apenas as utilities do
 * HTML escaneado. O CSS autoral (`<recurso>.css`) é concatenado no runtime e nunca passa pelo
 * compilador: se ele consome `var(--color-…)` de um token podado, a declaração inteira é invalidada
 * no cômputo e a propriedade cai para o valor inicial — `border: 2px solid var(--color-photon-accent)`
 * vira "sem borda", sem erro no build, no console ou no PHP. Medido no lumix: 23 tokens
 * referenciados, 9 presentes.
 *
 * Só considera tokens de tema (`--color-`, `--font-`, `--spacing-`, `--text-`…): variáveis que o
 * próprio CSS autoral declara ficam de fora, e é por isso que as declaradas no arquivo são
 * descontadas antes da comparação.
 *
 * @return list<string> nomes de token ausentes, em ordem estável.
 */
function tailwind_recursos_tokens_ausentes(string $cssAutoral, string $cssCompilado): array
{
    if (trim($cssAutoral) === '') return [];

    preg_match_all('/var\(\s*(--[A-Za-z0-9_-]+)/', $cssAutoral, $usados);
    if (empty($usados[1])) return [];

    // Tokens que o próprio arquivo define não dependem do `@theme`.
    preg_match_all('/(^|[;{\s])(--[A-Za-z0-9_-]+)\s*:/m', $cssAutoral, $declarados);
    $proprios = array_flip($declarados[2] ?? []);

    $ausentes = [];
    foreach (array_unique($usados[1]) as $token) {
        if (isset($proprios[$token])) continue;
        // Só cobra o que tem cara de token de tema — `--tw-*` e variáveis locais de componente não.
        if (!preg_match('/^--(color|font|text|spacing|radius|shadow|breakpoint|container|leading|tracking|ease|animate|blur|perspective|aspect)-/', $token)) continue;
        if (strpos($cssCompilado, $token . ':') === false) $ausentes[] = $token;
    }

    sort($ausentes, SORT_STRING);
    return $ausentes;
}

/**
 * F4c(b) — utilities do Tailwind v3 removidas na v4, ainda presentes no HTML.
 *
 * Elas somem sem erro: o compilador não gera regra e o markup fica com uma classe inerte. Foi o caso
 * do `bg-opacity-50` no dimmer do componente `form-ui` — o overlay era injetado no DOM e ficava
 * invisível.
 *
 * @return list<string> classes encontradas, em ordem estável.
 */
function tailwind_recursos_utilities_removidas(string $html): array
{
    if (trim($html) === '') return [];

    $padroes = [
        '/\b(?:bg|text|border|placeholder|divide|ring)-opacity-\d+\b/', // viraram modificador `/50`
        '/\bflex-(?:shrink|grow)-\d+\b/',                              // viraram `shrink-*`/`grow-*`
        '/\boverflow-(?:x-|y-)?clip\b(?!-)/',                          // renomeadas
        '/\bdecoration-(?:slice|clone)\b/',                            // viraram `box-decoration-*`
    ];

    $encontradas = [];
    foreach ($padroes as $padrao) {
        if (preg_match_all($padrao, $html, $m)) {
            foreach ($m[0] as $classe) $encontradas[$classe] = true;
        }
    }

    $lista = array_keys($encontradas);
    sort($lista, SORT_STRING);
    return $lista;
}

/**
 * F3 — o CSS de um layout inverte desktop/mobile quando concatenado fora de ordem?
 *
 * O sintoma medido na Busca Clínica: `.hidden` de um sidecar posterior anulava o `lg:flex` do
 * layout e o menu aparecia invertido — sidebar sumindo no desktop, hambúrguer aparecendo nele. É o
 * conflito que o bundle canônico existe para evitar, e o build não avisava: quem via o defeito
 * suspeitava do markup, que estava correto.
 *
 * O compilador tem informação suficiente para alertar: ele conhece o CSS que gerou para cada layout.
 * Layout que emite `display` sob variante responsiva E um `display` incondicional concorrente é
 * candidato ao conflito; página sob ele que não declara `tailwind_bundle` é candidata ao aviso.
 *
 * Função PURA sobre o CSS compilado.
 */
function tailwind_recursos_layout_display_sensivel(string $css): bool
{
    if (trim($css) === '') return false;

    // `display` dentro de bloco `@media` (variante responsiva: `lg:flex`, `md:grid`, …).
    $temResponsivo = false;
    if (preg_match_all('/@media[^{]*\{(.*?)\}\s*\}/s', $css, $blocos)) {
        foreach ($blocos[1] as $bloco) {
            if (strpos($bloco, 'display:') !== false || strpos($bloco, 'display :') !== false) {
                $temResponsivo = true;
                break;
            }
        }
    }
    if (!$temResponsivo) return false;

    // …e um `display` incondicional que possa vencê-lo se a ordem se perder.
    return (bool)preg_match('/\.(hidden|block|flex|grid|inline-flex)\s*\{\s*display\s*:/', $css);
}

/**
 * F4c(a) — indício de que o HTML usa utilities Tailwind.
 *
 * Serve para avisar quando um recurso com cara de Tailwind NÃO declara `framework_css`: o compilador
 * simplesmente o ignora e ele nunca ganha `.precompiled.css`, sem que nada reclame. Foi o caso do
 * componente `form-ui`, que fornece overlay e mensagens de erro a TODOS os formulários.
 *
 * Heurística deliberadamente estreita, calibrada contra o inventário real do core: as utilities sem
 * hífen (`flex`, `grid`, `hidden`) ficaram DE FORA porque colidem com o Fomantic UI (`ui grid`,
 * `ui items`) e faziam o aviso disparar em 176 recursos administrativos — um aviso que soa sempre é
 * um aviso que ninguém lê. Exigem-se DUAS utilities distintas com valor, padrão que o Fomantic não
 * produz.
 */
function tailwind_recursos_html_usa_tailwind(string $html): bool
{
    if (trim($html) === '') return false;

    $prefixos = 'p|m|px|py|mx|my|pt|pb|pl|pr|mt|mb|ml|mr|w|h|min-w|max-w|gap|space-x|space-y|text|bg|border|rounded|shadow|ring|opacity|z|inset|top|left|right|bottom|grid-cols|col-span|flex-col|flex-row|items|justify|leading|tracking|font';

    // Classes do Bootstrap que casariam os prefixos acima sem nunca serem utilities Tailwind.
    // `mb-4`/`px-2` existem nos dois e não dão para separar — por isso o limiar de duas classes.
    $bootstrap = '/^(?:justify-content|align-items|align-self|text-(?:primary|secondary|success|danger|warning|info|muted|body|white-50))-/';

    if (!preg_match_all('/class\s*=\s*["\']([^"\']*)["\']/', $html, $atributos)) return false;

    $encontradas = [];
    foreach ($atributos[1] as $valor) {
        if (preg_match_all('/(?:^|\s)((?:[a-z]{2}:|hover:|focus:)?(?:' . $prefixos . ')-[a-z0-9\[\]\/.%#-]+)(?=\s|$)/', $valor, $m)) {
            foreach ($m[1] as $classe) {
                if (preg_match($bootstrap, $classe)) continue;
                $encontradas[$classe] = true;
            }
        }
    }

    return count($encontradas) >= 2;
}

/**
 * @return array{enabled:bool,discovered:int,compiled:int,cached:int,removed:int,failed:int,resources_with_sources:int,additional_sources:int,version:string,browser_contract:?string,warnings:int}
 */
function tailwind_recursos_compilar(array $map): array
{
    global $GESTOR_DIR, $RESOURCES_DIR, $LOG_FILE;
    $stats = [
        'enabled' => false,
        'discovered' => 0,
        'compiled' => 0,
        'cached' => 0,
        'removed' => 0,
        'failed' => 0,
        'resources_with_sources' => 0,
        'additional_sources' => 0,
        'version' => '',
        'browser_contract' => null,
        'warnings' => 0, // findings F1/F4c do review de 2026-08-15.
    ];
    $args = $GLOBALS['CLI_ARGS'] ?? [];

    $centralInput = tailwind_recursos_input_central();
    if ($centralInput === null || isset($args['no-tailwind'])) {
        cliProgress('       Tailwind por recurso: ignorado (sem input ou --no-tailwind).', false);
        return $stats;
    }

    $resources = tailwind_recursos_descobrir($map);
    $stats['enabled'] = true;
    $stats['discovered'] = count($resources);
    $sourceStats = tailwind_recursos_estatisticas_fontes($resources);
    $stats['resources_with_sources'] = $sourceStats['resources_with_sources'];
    $stats['additional_sources'] = $sourceStats['additional_sources'];
    $centralHash = hash_file('sha256', $centralInput) ?: '';
    $contract = tailwind_recursos_browser_contract($centralInput);
    $stats['browser_contract'] = $contract['path'];

    $manifestPath = $RESOURCES_DIR . '.tailwind-build-manifest.json';
    $oldManifest = jsonRead($manifestPath) ?? [];
    $oldEntries = is_array($oldManifest['resources'] ?? null) ? $oldManifest['resources'] : [];
    $newEntries = [];

    $command = null;
    $version = 'import-only';
    if (!isset($args['tailwind-import-only'])) {
        $command = tailwind_recursos_resolver_command();
        if ($command === null) {
            throw new RuntimeException('Tailwind CLI não encontrado. Use --tailwind-command, TAILWINDCSS_COMMAND ou --tailwind-import-only.');
        }
        $version = tailwind_recursos_cli_version($command);
    }
    $stats['version'] = $version;

    $tempDir = $GESTOR_DIR . '.tailwind-build' . DIRECTORY_SEPARATOR . 'inputs';
    ensureDir($tempDir, $LOG_FILE);
    $force = isset($args['tailwind-force']);
    $toCompile = [];

    foreach ($resources as $resource) {
        $fingerprint = tailwind_recursos_fingerprint($resource, $centralHash, $version);
        $old = $oldEntries[$resource['key']] ?? null;
        if (!$force && is_array($old) && ($old['fingerprint'] ?? '') === $fingerprint && tailwind_recursos_output_valido($resource['output'])) {
            $outputHash = hash_file('sha256', $resource['output']) ?: '';
            if (($old['output_hash'] ?? '') === $outputHash) {
                $stats['cached']++;
                $newEntries[$resource['key']] = $old;
                continue;
            }
        }
        $resource['fingerprint'] = $fingerprint;
        $toCompile[] = $resource;
    }

    cliProgress(
        '       Tailwind: ' . count($resources) . ' recursos, ' . count($toCompile) .
        ' para compilar, ' . $stats['cached'] . ' em cache; ' .
        $stats['resources_with_sources'] . ' recursos usam ' . $stats['additional_sources'] . ' fontes adicionais.'
    );

    foreach ($toCompile as $index => $resource) {
        $label = ($resource['module'] ? $resource['module'] . '/' : '') . $resource['type'] . '/' . $resource['id'] . ' (' . $resource['language'] . ')';
        cliProgress('       [' . ($index + 1) . '/' . count($toCompile) . '] ' . $label);
        $GLOBALS['CLI_PROGRESS_LAST_CONTEXT'] = 'Tailwind ' . $label;

        if ($command === null) {
            if (!tailwind_recursos_output_valido($resource['output'])) {
                throw new RuntimeException("Sidecar ausente ou vazio no modo import-only: {$label}");
            }
        } else {
            $inputPath = $tempDir . DIRECTORY_SEPARATOR . hash('sha256', $resource['key']) . '.css';
            $tempOutput = $resource['output'] . '.tmp-' . getmypid() . '-' . bin2hex(random_bytes(3));
            tailwind_recursos_atomic_write($inputPath, tailwind_recursos_input_temporario($resource, $centralInput, $tempDir));
            $result = tailwind_recursos_exec(array_merge($command, ['-i', $inputPath, '-o', $tempOutput, '--minify']), $GESTOR_DIR);
            @unlink($inputPath);
            if ($result['code'] !== 0 || !is_file($tempOutput)) {
                $stats['failed']++;
                @unlink($tempOutput);
                $details = trim($result['stderr'] . "\n" . $result['stdout']);
                throw new RuntimeException("Falha ao compilar {$label}: {$details}");
            }
            $compiled = (string)file_get_contents($tempOutput);
            @unlink($tempOutput);
            if (trim($compiled) === '') throw new RuntimeException("Tailwind gerou saída vazia para {$label}");
            tailwind_recursos_atomic_write($resource['output'], $compiled);
            $stats['compiled']++;

            // F1: o CSS autoral consome tokens que o `@theme` pode ter podado. Aqui é o único ponto
            // do sistema que enxerga as duas pontas (o autoral e a saída) ao mesmo tempo.
            if (isset($resource['css']) && is_file($resource['css'])) {
                $ausentes = tailwind_recursos_tokens_ausentes((string)file_get_contents($resource['css']), $compiled);
                if ($ausentes) {
                    tailwind_recursos_aviso(
                        $label . ': o CSS autoral usa ' . count($ausentes) . ' token(s) de tema ausentes na saída (' .
                        implode(', ', array_slice($ausentes, 0, 6)) . (count($ausentes) > 6 ? ', …' : '') .
                        '). Use `@theme static` no input do projeto — var() indefinida invalida a declaração em silêncio.'
                    );
                }
            }

            // F4c(b): utility da v3 removida na v4 não gera regra e nem aviso — some do markup.
            $removidas = tailwind_recursos_utilities_removidas((string)file_get_contents($resource['html']));
            if ($removidas) {
                tailwind_recursos_aviso(
                    $label . ': utilities removidas no Tailwind v4 presentes no HTML (' . implode(', ', $removidas) . ').'
                );
            }
        }

        $newEntries[$resource['key']] = [
            'fingerprint' => $resource['fingerprint'],
            'output_hash' => hash_file('sha256', $resource['output']) ?: '',
            'output' => tailwind_recursos_normalizar_path(tailwind_recursos_relativo($GESTOR_DIR, $resource['output'])),
            'tailwind_version' => $version,
            'sources' => array_map(fn($v) => tailwind_recursos_normalizar_path(tailwind_recursos_relativo($GESTOR_DIR, $v)), array_merge([$resource['html']], $resource['sources'])),
            'safelist' => $resource['safelist'],
            'built_at' => gmdate(DATE_ATOM),
        ];
    }

    foreach ($oldEntries as $key => $entry) {
        if (isset($newEntries[$key]) || !is_array($entry)) continue;
        $relative = $entry['output'] ?? null;
        if (is_string($relative) && $relative !== '') {
            $candidate = $GESTOR_DIR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (tailwind_recursos_path_dentro($candidate, $GESTOR_DIR) && str_ends_with($candidate, '.precompiled.css') && is_file($candidate)) {
                if (@unlink($candidate)) $stats['removed']++;
            }
        }
    }

    ksort($newEntries, SORT_STRING);
    tailwind_recursos_atomic_write($manifestPath, json_encode([
        'version' => TAILWIND_RECURSOS_MANIFEST_VERSION,
        'tailwind_version' => $version,
        'central_input' => tailwind_recursos_normalizar_path(tailwind_recursos_relativo($GESTOR_DIR, $centralInput)),
        'central_hash' => $centralHash,
        'browser_contract' => tailwind_recursos_normalizar_path(tailwind_recursos_relativo($GESTOR_DIR, $contract['path'])),
        'browser_contract_hash' => $contract['hash'],
        'resources' => $newEntries,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

    // F3: página sob layout com variante responsiva de `display` e SEM bundle canônico volta à
    // concatenação de sidecars, onde a ordem global das utilities se perde. O compilador conhece as
    // duas pontas — o CSS que gerou para o layout e a declaração da página —, então é aqui que o
    // aviso cabe. Roda depois de tudo compilado, para ler o sidecar final de cada layout.
    $layoutsSensiveis = [];
    foreach ($resources as $recurso) {
        if (!$recurso['layout'] || !tailwind_recursos_output_valido($recurso['output'])) continue;
        if (tailwind_recursos_layout_display_sensivel((string)file_get_contents($recurso['output']))) {
            $layoutsSensiveis[$recurso['language'].'|'.$recurso['id']] = true;
        }
    }

    if ($layoutsSensiveis) {
        foreach ($resources as $recurso) {
            if ($recurso['type'] !== 'pages' || !empty($recurso['bundle'])) continue;
            $layoutId = (string)($recurso['layout_id'] ?? '');
            if ($layoutId === '') continue;
            if (!isset($layoutsSensiveis[$recurso['language'].'|'.$layoutId])) continue;

            tailwind_recursos_aviso(
                ($recurso['module'] ? $recurso['module'].'/' : '').'pages/'.$recurso['id'].' ('.$recurso['language'].'): '
                .'o layout `'.$layoutId.'` emite `display` sob variante responsiva e a página não declara '
                .'tailwind_bundle — a concatenação de sidecars pode inverter desktop/mobile.'
            );
        }
    }

    // Findings F1/F3/F4c: o resumo aparece sempre; `--tailwind-strict` promove os avisos a erro de
    // build, para o CI travar assim que o inventário estiver limpo.
    $avisos = $GLOBALS['TAILWIND_RECURSOS_AVISOS'] ?? [];
    $stats['warnings'] = count($avisos);

    cliProgress('       Tailwind concluído: ' . $stats['compiled'] . ' compilados, ' . $stats['cached'] . ' em cache, ' . $stats['removed'] . ' removidos' . ($avisos ? ', ' . count($avisos) . ' aviso(s)' : '') . '.');

    if ($avisos && isset($args['tailwind-strict'])) {
        throw new RuntimeException('Tailwind: ' . count($avisos) . ' aviso(s) com --tailwind-strict:' . "\n - " . implode("\n - ", $avisos));
    }

    return $stats;
}
