<?php

/**
 * Script de Upsert/Delete de Recursos (CLI)
 * 
 * Gerencia a criação, atualização e remoção de recursos (páginas, layouts, componentes, etc.)
 * atuando como a "Fonte da Verdade" (Source of Truth) do sistema.
 * 
 * Este script NÃO gerencia versionamento ou cálculo de checksums. Essa responsabilidade
 * pertence ao script `atualizacao-dados-recursos.php` que prepara os dados para o banco.
 * 
 * Parâmetros da CLI:
 * (Sem parâmetros) : Ativa automaticamente o modo interativo.
 * --interactive : (ou -i) Força o modo interativo (menu CLI).
 * --target      : Alvo da operação. Opções: 'gestor' (default), 'plugin', 'project'.
 * --plugin-type : Tipo do plugin (se target=plugin). Opções: 'public', 'private'.
 * --scope       : Escopo do recurso. Opções: 'global' (default), 'module'.
 * --module-id   : ID do módulo (obrigatório se scope=module).
 * --lang        : Linguagem do recurso. Default: 'pt-br'.
 * --type        : Tipo do recurso. Ex: 'page', 'layout', 'component', 'variable', 'prompt_ia', etc.
 * --action      : Ação a executar. Opções: 'upsert' (default), 'delete', 'copy'.
 * --open        : Flag opcional. Se presente, abre os arquivos físicos e metadados no VS Code.
 * --id          : ID do recurso (origem no caso de copy) ou lista separada por vírgulas.
 * --new-id      : (Opcional) Novo ID para o recurso de destino na ação 'copy'.
 * --data        : JSON string contendo os dados do recurso.
 * 
 * Parâmetros Específicos para 'copy' (Origem):
 * --source-target      : Alvo de origem.
 * --source-plugin-type : Tipo de plugin de origem.
 * --source-scope       : Escopo de origem.
 * --source-module-id   : ID do módulo de origem.
 * --source-lang        : Linguagem de origem.
 * 
 * Uso Exemplo:
 * php upsert-resources.php --interactive
 * php upsert-resources.php --target=gestor --type=page --id=minha-pag --open
 * php upsert-resources.php --action=copy --type=page --id=home --new-id=home-copy --source-target=gestor --target=project --open
 */

declare(strict_types=1);

// ===============================================================================================
// 1. Configuração e Helpers
// ===============================================================================================

// Definir caminhos base
$WORKSPACE_ROOT = realpath(__DIR__ . '/../../../'); // ai-workspace/scripts/resources/ -> raiz
$ENV_FILE = $WORKSPACE_ROOT . '/dev-environment/data/environment.json';

const TYPE_MAP = [
    'page' => 'pages',
    'layout' => 'layouts',
    'component' => 'components',
    'template' => 'templates',
    'variable' => 'variables',
    'prompt_ia' => 'ai_prompts',
    'modo_ia' => 'ai_modes',
    'alvo_ia' => 'ai_prompts_targets',
];

/**
 * Lê um arquivo JSON e retorna o array associativo.
 */
function readJson(string $path): array {
    if (!file_exists($path)) {
        throw new RuntimeException("Arquivo JSON não encontrado: $path");
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Erro ao decodificar JSON ($path): " . json_last_error_msg());
    }
    return $data;
}

/**
 * Salva um array como JSON formatado.
 */
function saveJson(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($path, $json) === false) {
        throw new RuntimeException("Erro ao salvar JSON em: $path");
    }
}

/**
 * Parseia argumentos da CLI.
 */
function parseArgs(array $argv): array {
    $args = [];
    foreach ($argv as $arg) {
        if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
            $args[$matches[1]] = $matches[2];
        } elseif (preg_match('/^--([^=]+)$/', $arg, $matches)) {
            $args[$matches[1]] = true;
        }
    }
    return $args;
}

// ===============================================================================================
// 2. Lógica de Resolução de Caminhos
// ===============================================================================================

/**
 * Resolve o caminho raiz ({root}) baseado no target.
 */
function resolveRootPath(array $args, string $envFile, string $workspaceRoot): string {
    $target = $args['target'] ?? 'gestor';

    if ($target === 'gestor') {
        return $workspaceRoot . '/gestor/';
    }

    $envData = readJson($envFile);

    if ($target === 'project') {
        $projectId = $envData['devEnvironment']['projectTarget'] ?? null;
        if (!$projectId) {
            throw new RuntimeException("Nenhum projeto ativo definido em devEnvironment.projectTarget");
        }
        $projectPath = $envData['devProjects'][$projectId]['path'] ?? null;
        if (!$projectPath) {
            throw new RuntimeException("Caminho não encontrado para o projeto: $projectId");
        }
        
        if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^\/([a-zA-Z])\/(.*)$/', $projectPath, $m)) {
            $projectPath = strtoupper($m[1]) . ':/' . $m[2];
        }
        
        return rtrim($projectPath, '/\\') . '/';
    }

    if ($target === 'plugin') {
        $pluginType = $args['plugin-type'] ?? null;
        if (!$pluginType || !in_array($pluginType, ['public', 'private'])) {
            throw new RuntimeException("Argumento --plugin-type (public|private) é obrigatório para target=plugin");
        }

        $pluginEnvPath = $envData['devPluginEnvironmentConfig'][$pluginType]['path'] ?? null;
        if (!$pluginEnvPath) {
            throw new RuntimeException("Caminho do ambiente de plugin ($pluginType) não configurado.");
        }

        if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^\/([a-zA-Z])\/(.*)$/', $pluginEnvPath, $m)) {
            $pluginEnvPath = strtoupper($m[1]) . ':/' . $m[2];
        }

        $pluginEnvData = readJson($pluginEnvPath);
        $activePluginId = $pluginEnvData['activePlugin']['id'] ?? null;
        if (!$activePluginId) {
            throw new RuntimeException("Nenhum plugin ativo definido no ambiente de plugins ($pluginType).");
        }

        $sourceBase = $pluginEnvData['devEnvironment']['source'] ?? '';
        
        if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^\/([a-zA-Z])\/(.*)$/', $sourceBase, $m)) {
            $sourceBase = strtoupper($m[1]) . ':/' . $m[2];
        }

        $pluginPath = null;
        foreach ($pluginEnvData['plugins'] as $p) {
            if (($p['id'] ?? '') === $activePluginId) {
                $pluginPath = $p['path'] ?? null;
                break;
            }
        }

        if (!$pluginPath) {
            throw new RuntimeException("Caminho do plugin '$activePluginId' não encontrado na configuração.");
        }
        
        return rtrim($sourceBase, '/\\') . '/' . trim($pluginPath, '/\\') . '/';
    }

    throw new RuntimeException("Target desconhecido: $target");
}

// ===============================================================================================
// 3. Manipulação de Metadados e Arquivos
// ===============================================================================================

/**
 * Obtém o caminho do arquivo de metadados JSON.
 */
function getMetadataFilePath(string $rootPath, string $scope, string $lang, string $type, ?string $moduleId): string {
    $typeKey = TYPE_MAP[$type] ?? null;
    if (!$typeKey) {
        throw new RuntimeException("Tipo de recurso desconhecido: $type");
    }

    if ($scope === 'global') {
        $mapFile = $rootPath . 'resources/resources.map.php';
        if (!file_exists($mapFile)) {
            throw new RuntimeException("Arquivo de mapeamento global não encontrado: $mapFile");
        }
        $map = include $mapFile;
        $jsonFile = $map['languages'][$lang]['data'][$typeKey] ?? null;
        
        if (!$jsonFile) {
            // Fallback ou erro? Se não estiver no mapa, não podemos salvar.
            throw new RuntimeException("Tipo '$typeKey' não mapeado para linguagem '$lang' em resources.map.php");
        }
        
        return $rootPath . "resources/$lang/$jsonFile";
    } elseif ($scope === 'module') {
        if (!$moduleId) {
            throw new RuntimeException("Module ID obrigatório para escopo module");
        }
        return $rootPath . "modulos/$moduleId/$moduleId.json";
    }

    throw new RuntimeException("Escopo desconhecido: $scope");
}

/**
 * Manipula arquivos físicos (criação/atualização/remoção).
 * Retorna array com caminhos dos arquivos manipulados (para upsert).
 */
function handlePhysicalFiles(string $rootPath, string $scope, string $lang, string $type, string $id, ?string $moduleId, array $data, string $action): array {
    $typeKey = TYPE_MAP[$type];
    $touchedFiles = [];
    
    // Variáveis não têm arquivos físicos
    if ($type === 'variable') return [];

    // Determinar diretório base
    if ($scope === 'global') {
        $baseDir = $rootPath . "resources/$lang/$typeKey/$id/";
    } else {
        $baseDir = $rootPath . "modulos/$moduleId/resources/$lang/$typeKey/$id/";
    }

    if ($action === 'delete') {
        // Remover diretório recursivamente se existir
        if (is_dir($baseDir)) {
            // Simples remoção de arquivos conhecidos para evitar acidentes
            @unlink($baseDir . "$id.html");
            @unlink($baseDir . "$id.css");
            @unlink($baseDir . "$id.md");
            @rmdir($baseDir); // Só remove se estiver vazio
        }
        return [];
    }

    // Upsert
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    if (in_array($type, ['prompt_ia', 'modo_ia', 'alvo_ia'])) {
        // Markdown
        if (isset($data['md'])) {
            $path = $baseDir . "$id.md";
            file_put_contents($path, $data['md']);
            $touchedFiles[] = $path;
        }
    } else {
        // HTML/CSS
        if (isset($data['html'])) {
            $path = $baseDir . "$id.html";
            file_put_contents($path, $data['html']);
            $touchedFiles[] = $path;
        }
        if (isset($data['css'])) {
            $path = $baseDir . "$id.css";
            file_put_contents($path, $data['css']);
            $touchedFiles[] = $path;
        }
    }
    
    return $touchedFiles;
}

/**
 * Processa o Upsert.
 */
function processUpsert(string $rootPath, array $args, array $data): void {
    $scope = $args['scope'] ?? 'global';
    $lang = $args['lang'] ?? 'pt-br';
    $type = $args['type'];
    $moduleId = $args['module-id'] ?? null;
    $typeKey = TYPE_MAP[$type];

    $id = $data['id'] ?? null;
    if (!$id) throw new RuntimeException("ID do recurso é obrigatório.");

    // 1. Carregar Metadados
    $metaPath = getMetadataFilePath($rootPath, $scope, $lang, $type, $moduleId);
    
    $metaData = [];
    if ($scope === 'global') {
        if (file_exists($metaPath)) {
            $metaData = readJson($metaPath);
        }
    } else {
        // Módulo: JSON contém estrutura completa
        if (file_exists($metaPath)) {
            $fullModuleData = readJson($metaPath);
            $metaData = $fullModuleData['resources'][$lang][$typeKey] ?? [];
        } else {
            throw new RuntimeException("Arquivo de módulo não encontrado: $metaPath");
        }
    }

    // 2. Verificar existência e preparar dados
    $existingIndex = -1;
    foreach ($metaData as $idx => $item) {
        if (($item['id'] ?? '') === $id) {
            $existingIndex = $idx;
            break;
        }
    }

    $resourceData = $existingIndex >= 0 ? $metaData[$existingIndex] : [];
    
    // Mesclar dados novos (exceto conteúdo físico que será tratado separadamente)
    // Preservar campos que não vieram no input se for update
    $resourceData = array_merge($resourceData, $data);

    // Default geral: Se 'name' não definido, usa o ID
    if (empty($resourceData['name'])) {
        $resourceData['name'] = $id;
    }

    // Aplicar defaults específicos para 'page'
    if ($type === 'page') {
        if (empty($resourceData['layout'])) {
            $resourceData['layout'] = 'layout-pagina-sem-permissao';
        }
        if (empty($resourceData['path'])) {
            $resourceData['path'] = $id . '/';
        }
        if (empty($resourceData['type'])) {
            $resourceData['type'] = 'page';
        }
    }

    // Aplicar defaults específicos para 'variable'
    if ($type === 'variable') {
        if (!isset($resourceData['value'])) {
            $resourceData['value'] = '';
        }
        if (empty($resourceData['type'])) {
            $resourceData['type'] = 'string';
        }
    }

    // 3. Manipular Arquivos Físicos
    $html = $data['html'] ?? null;
    $css = $data['css'] ?? null;
    $md = $data['md'] ?? null;

    // Remover campos de conteúdo do objeto de metadados
    unset($resourceData['html'], $resourceData['css'], $resourceData['md']);

    // 4. Atualizar Array de Metadados
    if ($existingIndex >= 0) {
        $metaData[$existingIndex] = $resourceData;
    } else {
        $metaData[] = $resourceData;
    }

    // 5. Salvar Arquivos Físicos
    // Passamos apenas o que veio no input para evitar re-escrever o que não mudou (embora não faça mal)
    // Mas handlePhysicalFiles espera o conteúdo em $data.
    // Vamos passar o conteúdo final (mesclado) para garantir consistência.
    $filesData = [];
    if ($html !== null) $filesData['html'] = $html;
    if ($css !== null) $filesData['css'] = $css;
    if ($md !== null) $filesData['md'] = $md;
    
    $touchedFiles = handlePhysicalFiles($rootPath, $scope, $lang, $type, $id, $moduleId, $filesData, 'upsert');

    // 6. Salvar Metadados
    if ($scope === 'global') {
        saveJson($metaPath, $metaData);
    } else {
        $fullModuleData['resources'][$lang][$typeKey] = $metaData;
        saveJson($metaPath, $fullModuleData);
    }
    
    echo colorize("Recurso '$id' ($type) atualizado com sucesso em $scope.\n", 'green');

    // 7. Abrir arquivos no editor se solicitado
    if (!empty($args['open'])) {
        $filesToOpen = $touchedFiles;
        $filesToOpen[] = $metaPath; // Inclui o arquivo de metadados
        
        // Adicionar arquivos físicos esperados (para navegação ou criação)
        if ($scope === 'global') {
            $baseDir = $rootPath . "resources/$lang/$typeKey/$id/";
        } else {
            $baseDir = $rootPath . "modulos/$moduleId/resources/$lang/$typeKey/$id/";
        }
        
        if (in_array($type, ['prompt_ia', 'modo_ia', 'alvo_ia'])) {
            $filesToOpen[] = $baseDir . "$id.md";
        } elseif ($type !== 'variable') {
            $filesToOpen[] = $baseDir . "$id.html";
            $filesToOpen[] = $baseDir . "$id.css";
        }

        $filesToOpen = array_unique($filesToOpen);

        foreach ($filesToOpen as $file) {
            // Se o arquivo existe, abrimos.
            // Se não existe, mas o diretório pai existe (recurso válido), abrimos para permitir criação.
            if (!file_exists($file)) {
                $dir = dirname($file);
                if (!is_dir($dir)) continue;
            }

            // Tenta abrir com o comando 'code' (VS Code)
            // No Windows/Git Bash, 'code' deve estar no PATH.
            $cmd = "code " . escapeshellarg($file);
            // Executa em background no Windows para não travar o script?
            // O comando 'code' já retorna imediatamente por padrão (é um launcher).
            exec($cmd);
            echo colorize("Abrindo arquivo: $file\n", 'cyan');
        }
    }
}

/**
 * Processa o Delete.
 */
function processDelete(string $rootPath, array $args, array $data): void {
    $scope = $args['scope'] ?? 'global';
    $lang = $args['lang'] ?? 'pt-br';
    $type = $args['type'];
    $moduleId = $args['module-id'] ?? null;
    $typeKey = TYPE_MAP[$type];

    $id = $data['id'] ?? null;
    if (!$id) throw new RuntimeException("ID do recurso é obrigatório.");

    // 1. Carregar Metadados
    $metaPath = getMetadataFilePath($rootPath, $scope, $lang, $type, $moduleId);
    
    $metaData = [];
    if ($scope === 'global') {
        if (file_exists($metaPath)) {
            $metaData = readJson($metaPath);
        }
    } else {
        if (file_exists($metaPath)) {
            $fullModuleData = readJson($metaPath);
            $metaData = $fullModuleData['resources'][$lang][$typeKey] ?? [];
        } else {
            throw new RuntimeException("Arquivo de módulo não encontrado: $metaPath");
        }
    }

    // 2. Remover do Array
    $found = false;
    $newMetaData = [];
    foreach ($metaData as $item) {
        if (($item['id'] ?? '') === $id) {
            $found = true;
            continue; // Pula (remove)
        }
        $newMetaData[] = $item;
    }

    if (!$found) {
        echo colorize("Recurso '$id' não encontrado. Nada a deletar.\n", 'yellow');
        return;
    }

    // 3. Remover Arquivos Físicos
    handlePhysicalFiles($rootPath, $scope, $lang, $type, $id, $moduleId, [], 'delete');

    // 4. Salvar Metadados
    if ($scope === 'global') {
        saveJson($metaPath, $newMetaData);
    } else {
        $fullModuleData['resources'][$lang][$typeKey] = $newMetaData;
        saveJson($metaPath, $fullModuleData);
    }

    echo colorize("Recurso '$id' ($type) removido com sucesso.\n", 'green');
}

/**
 * Processa o Copy.
 */
function processCopy(string $workspaceRoot, string $envFile, array $args, array $data): void {
    $id = $data['id'] ?? null;
    if (!$id) throw new RuntimeException("ID do recurso é obrigatório.");

    // 1. Preparar Argumentos de Origem
    $srcArgs = [
        'target' => $args['source-target'] ?? null,
        'plugin-type' => $args['source-plugin-type'] ?? null,
        'scope' => $args['source-scope'] ?? null,
        'module-id' => $args['source-module-id'] ?? null,
        'lang' => $args['source-lang'] ?? null,
        'type' => $args['type'] // Mesmo tipo
    ];

    // 2. Resolver Caminhos de Origem
    $srcRoot = resolveRootPath($srcArgs, $envFile, $workspaceRoot);
    $srcMetaPath = getMetadataFilePath($srcRoot, $srcArgs['scope'], $srcArgs['lang'], $srcArgs['type'], $srcArgs['module-id']);

    // 3. Ler Metadados de Origem
    $srcMetaDataList = [];
    if ($srcArgs['scope'] === 'global') {
        if (file_exists($srcMetaPath)) {
            $srcMetaDataList = readJson($srcMetaPath);
        }
    } else {
        if (file_exists($srcMetaPath)) {
            $fullModuleData = readJson($srcMetaPath);
            $typeKey = TYPE_MAP[$srcArgs['type']];
            $srcMetaDataList = $fullModuleData['resources'][$srcArgs['lang']][$typeKey] ?? [];
        }
    }

    // 4. Encontrar o Recurso na Origem
    $resourceData = null;
    foreach ($srcMetaDataList as $item) {
        if (($item['id'] ?? '') === $id) {
            $resourceData = $item;
            break;
        }
    }

    if (!$resourceData) {
        throw new RuntimeException("Recurso '$id' não encontrado na origem.");
    }

    // 5. Ler Arquivos Físicos de Origem
    $typeKey = TYPE_MAP[$srcArgs['type']];
    if ($srcArgs['scope'] === 'global') {
        $srcBaseDir = $srcRoot . "resources/{$srcArgs['lang']}/$typeKey/$id/";
    } else {
        $srcBaseDir = $srcRoot . "modulos/{$srcArgs['module-id']}/resources/{$srcArgs['lang']}/$typeKey/$id/";
    }

    if (file_exists($srcBaseDir . "$id.html")) {
        $resourceData['html'] = file_get_contents($srcBaseDir . "$id.html");
    }
    if (file_exists($srcBaseDir . "$id.css")) {
        $resourceData['css'] = file_get_contents($srcBaseDir . "$id.css");
    }
    if (file_exists($srcBaseDir . "$id.md")) {
        $resourceData['md'] = file_get_contents($srcBaseDir . "$id.md");
    }

    // 6. Definir Novo ID (se fornecido)
    $destId = $args['new-id'] ?? $data['new_id'] ?? $id;
    $resourceData['id'] = $destId;

    // 7. Executar Upsert no Destino
    // O destino usa os argumentos padrão ($args['target'], etc.)
    // O resolveRootPath será chamado dentro de processUpsert com os args padrão.
    // Precisamos passar o rootPath do destino para processUpsert, então resolvemos aqui.
    $destRoot = resolveRootPath($args, $envFile, $workspaceRoot);
    
    echo colorize("Copiando recurso '$id' para '$destId'...\n", 'cyan');
    processUpsert($destRoot, $args, $resourceData);
}

// ===============================================================================================
// 3.5. Modo Interativo
// ===============================================================================================
/**
 * Helper para colorir saída no terminal (ANSI codes).
 */
function colorize(string $text, string $color): string {
    // Se não for terminal interativo ou Windows antigo sem suporte, talvez devesse desativar.
    // Mas assumindo Git Bash/VS Code Terminal que suportam.
    $colors = [
        'green' => "\033[0;32m",
        'cyan' => "\033[0;36m",
        'yellow' => "\033[1;33m",
        'red' => "\033[0;31m",
        'reset' => "\033[0m",
        'bold' => "\033[1m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

/**
 * Prompt interativo para o usuário.
 */
function prompt(string $question, array $options = [], ?string $default = null): string {
    echo "\n$question";
    if (!empty($options)) {
        echo "\n";
        foreach ($options as $key => $label) {
            echo "  " . colorize("[$key]", 'cyan') . " $label\n";
        }
    }
    
    $defText = $default !== null ? " [$default]" : "";
    echo " >" . colorize($defText, 'green') . " ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    $input = trim($line);
    
    if ($input === '' && $default !== null) {
        return $default;
    }
    
    if (!empty($options)) {
        if (!array_key_exists($input, $options)) {
            echo "Opção inválida. Tente novamente.\n";
            return prompt($question, $options, $default);
        }
        return $input;
    }
    
    return $input;
}

/**
 * Helper para perguntar contexto (Target, Scope, etc).
 */
function askContextParameters(array $args, string $prefix = '', string $title = ''): array {
    if ($title) {
        echo "\n" . colorize("=== Configuração de Contexto: $title ===", 'yellow') . "\n";
    }

    // 1. Target
    if (empty($args[$prefix . 'target'])) {
        $opt = prompt("Selecione o Alvo ($title):", [
            '1' => 'Gestor',
            '2' => 'Plugin',
            '3' => 'Projeto'
        ], '1');
        $map = ['1' => 'gestor', '2' => 'plugin', '3' => 'project'];
        $args[$prefix . 'target'] = $map[$opt];
    }

    // 2. Plugin Type
    if ($args[$prefix . 'target'] === 'plugin' && empty($args[$prefix . 'plugin-type'])) {
        $opt = prompt("Tipo do Plugin ($title):", [
            '1' => 'Público (Public)',
            '2' => 'Privado (Private)'
        ], '2');
        $map = ['1' => 'public', '2' => 'private'];
        $args[$prefix . 'plugin-type'] = $map[$opt];
    }

    // 3. Scope
    if (empty($args[$prefix . 'scope'])) {
        $opt = prompt("Escopo ($title):", [
            '1' => 'Global',
            '2' => 'Módulo'
        ], '1');
        $map = ['1' => 'global', '2' => 'module'];
        $args[$prefix . 'scope'] = $map[$opt];
    }

    // 4. Module ID
    if ($args[$prefix . 'scope'] === 'module' && empty($args[$prefix . 'module-id'])) {
        while (empty($args[$prefix . 'module-id'])) {
            $args[$prefix . 'module-id'] = prompt("ID do Módulo ($title):");
        }
    }

    // 5. Lang
    if (empty($args[$prefix . 'lang'])) {
        $args[$prefix . 'lang'] = prompt("Linguagem ($title):", [], 'pt-br');
    }

    return $args;
}

/**
 * Executa o modo interativo para preencher argumentos faltantes.
 */
function runInteractiveMode(array $args): array {
    echo "\n=== Modo Interativo de Recursos ===\n";

    // 1. Action (Agora é o primeiro)
    if (empty($args['action'])) {
        $opt = prompt("Ação:", [
            '1' => 'Upsert (Criar/Atualizar)',
            '2' => 'Delete (Remover)',
            '3' => 'Copy (Copiar)'
        ], '1');
        $map = ['1' => 'upsert', '2' => 'delete', '3' => 'copy'];
        $args['action'] = $map[$opt];
    }

    // 2. Type
    if (empty($args['type'])) {
        $typeOptions = [];
        $i = 1;
        $typeMapKeys = array_keys(TYPE_MAP);
        $selectionMap = [];
        
        foreach ($typeMapKeys as $t) {
            $typeOptions[$i] = ucfirst($t);
            $selectionMap[$i] = $t;
            $i++;
        }
        
        // Default para 'page' se estiver na lista, senão o primeiro
        $defaultIdx = array_search('page', $typeMapKeys);
        $defaultKey = $defaultIdx !== false ? (string)($defaultIdx + 1) : '1';

        $opt = prompt("Tipo de Recurso:", $typeOptions, $defaultKey);
        $args['type'] = $selectionMap[$opt];
    }

    // 3. Input Data (ID ou JSON)
    if (empty($args['id']) && empty($args['data'])) {
        $inputType = prompt("Entrada de Dados:", [
            '1' => 'Lista de IDs',
            '2' => 'JSON Completo'
        ], '1');

        if ($inputType === '1') {
            while (empty($args['id'])) {
                $args['id'] = prompt("ID(s) do Recurso (separados por vírgula):");
            }
        } else {
            echo "\n" . colorize("Cole o JSON de dados (em uma única linha) e pressione ENTER:", 'cyan') . "\n > ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            $args['data'] = trim($line);
        }
    }

    // 4. Open
    if (!isset($args['open'])) {
        $opt = prompt("Abrir arquivos no editor?", [
            '1' => 'Sim',
            '0' => 'Não'
        ], '1');
        if ($opt == '1') $args['open'] = true;
    }

    // 5. Contextos (Target, Scope, etc)
    if ($args['action'] === 'copy') {
        // Origem (Source)
        $args = askContextParameters($args, 'source-', 'ORIGEM');
        // Destino (Target padrão)
        $args = askContextParameters($args, '', 'DESTINO');

        // Novo ID (Opcional)
        if (empty($args['new-id'])) {
            $args['new-id'] = prompt("Novo ID no destino (opcional, deixe vazio para usar o mesmo):");
            if ($args['new-id'] === '') {
                unset($args['new-id']);
            }
        }
    } else {
        // Contexto Único
        $args = askContextParameters($args, '', '');
    }

    echo "\n" . colorize("--------------------------------------------------", 'green') . "\n";
    return $args;
}

// ===============================================================================================
// 4. Execução Principal
// ===============================================================================================

try {
    $args = parseArgs($argv);
    
    // Verificar modo interativo (Flag explícita ou sem argumentos)
    if (empty($args) || isset($args['interactive']) || isset($args['i'])) {
        $args = runInteractiveMode($args);
    }
    
    if (empty($args['type'])) {
        throw new RuntimeException("Argumento --type é obrigatório.");
    }

    $itemsToProcess = [];

    if (!empty($args['id'])) {
        // Modo Lista de IDs (Navegação/Criação Rápida)
        $idList = explode(',', $args['id']);
        foreach ($idList as $id) {
            $id = trim($id);
            if (!$id) continue;
            $itemsToProcess[] = ['id' => $id];
        }
    } elseif (!empty($args['data'])) {
        // Modo Dados Completos (JSON)
        $singleData = json_decode($args['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Erro ao decodificar JSON de dados: " . json_last_error_msg());
        }
        $itemsToProcess[] = $singleData;
    } else {
        throw new RuntimeException("É obrigatório informar --data OU --id.");
    }

    $rootPath = resolveRootPath($args, $ENV_FILE, $WORKSPACE_ROOT);
    $action = $args['action'] ?? 'upsert';

    foreach ($itemsToProcess as $data) {
        if ($action === 'upsert') {
            processUpsert($rootPath, $args, $data);
        } elseif ($action === 'delete') {
            processDelete($rootPath, $args, $data);
        } elseif ($action === 'copy') {
            processCopy($WORKSPACE_ROOT, $ENV_FILE, $args, $data);
        } else {
            throw new RuntimeException("Ação desconhecida: $action");
        }
    }

} catch (Throwable $e) {
    echo colorize("Erro: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
