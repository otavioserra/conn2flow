<?php

/**
 * Script to Manage Resources (Upsert, Delete, Copy)
 * 
 * Usage:
 * php upsert-resources.php --action=[upsert|delete|copy] --type=[page|component|...] --id=[id] ...
 * 
 * Or interactive mode:
 * php upsert-resources.php
 */

// ===============================================================================================
// 1. Configuration and Constants
// ===============================================================================================

// Adjusts to find the root and the .env file
$WORKSPACE_ROOT = __DIR__ . '/../../../../..'; // Assuming scripts/resources/upsert-resources.php
$ENV_FILE = $WORKSPACE_ROOT . '/.env';

// Map of Resource Types -> Subfolders / File Structures
const TYPE_MAP = [
    'page' => [
        'folder' => 'paginas',
        'files' => ['html', 'css', 'js', 'json']
    ],
    'component' => [
        'folder' => 'componentes',
        'files' => ['html', 'css', 'js', 'json']
    ],
    'layout' => [
        'folder' => 'layouts',
        'files' => ['html', 'css', 'js', 'json']
    ],
    'router' => [
        'folder' => 'rotas',
        'files' => ['json'] // Routers are usually just JSON or PHP, adjusting to JSON for standard
    ],
    'style' => [
        'folder' => 'estilos',
        'files' => ['css']
    ],
    'script' => [
        'folder' => 'scripts',
        'files' => ['js']
    ]
];

// ===============================================================================================
// 2. Helper Functions
// ===============================================================================================

/**
 * Reads a JSON file and returns the array.
 */
function readJson(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $json = json_decode($content, true);
    return is_array($json) ? $json : [];
}

/**
 * Saves an array as JSON formatted.
 */
function saveJson(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/**
 * Resolves the root path based on the target (Manager, Plugin, Project).
 */
function resolveRootPath(array $args, string $envFile, string $workspaceRoot): string {
    $target = $args['target'] ?? 'gestor'; // Default to Manager
    $scope = $args['scope'] ?? 'global';
    $moduleId = $args['module-id'] ?? null;
    $pluginType = $args['plugin-type'] ?? 'private'; // public or private

    // Base path logic
    $basePath = '';

    if ($target === 'gestor') {
        $basePath = $workspaceRoot . '/gestor';
    } elseif ($target === 'plugin') {
        // Plugins are in dev-plugins/plugins/[public|private]
        $basePath = $workspaceRoot . '/dev-plugins/plugins/' . $pluginType;
    } elseif ($target === 'project') {
        // Projects depend on the ID defined in .env or argument
        // For simplicity, assuming a standard structure or reading from .env if necessary
        // Here we will use a generic placeholder or logic if implemented
        throw new RuntimeException("Target 'project' not fully implemented in this script version.");
    } else {
        throw new RuntimeException("Unknown target: $target");
    }

    // Adjust for Scope (Module vs Global)
    // In the Manager/Plugin structure:
    // Global -> [basePath]/resources/[lang]/...
    // Module -> [basePath]/modulos/[moduleId]/resources/[lang]/...
    
    $lang = $args['lang'] ?? 'pt-br';
    
    if ($scope === 'module') {
        if (!$moduleId) {
            throw new RuntimeException("Module ID is required for 'module' scope.");
        }
        return "$basePath/modulos/$moduleId/resources/$lang";
    } else {
        return "$basePath/resources/$lang";
    }
}

/**
 * Returns the path to the metadata file (resources.json) for the type.
 */
function getMetadataFilePath(string $rootPath, string $type): string {
    // Example: .../resources/pt-br/paginas/resources.json
    if (!isset(TYPE_MAP[$type])) {
        throw new RuntimeException("Unknown resource type: $type");
    }
    $folder = TYPE_MAP[$type]['folder'];
    return "$rootPath/$folder/resources.json";
}

/**
 * Creates or updates physical files (.html, .css, .js, etc).
 */
function handlePhysicalFiles(string $rootPath, string $type, string $id, bool $openInEditor): void {
    if (!isset(TYPE_MAP[$type])) {
        return;
    }

    $config = TYPE_MAP[$type];
    $folderName = $config['folder'];
    $extensions = $config['files'];

    // Base directory for the resource files
    // Example: .../resources/pt-br/paginas/[id]/[id].[ext]
    $resourceDir = "$rootPath/$folderName/$id";

    if (!is_dir($resourceDir)) {
        mkdir($resourceDir, 0777, true);
        echo "Created directory: $resourceDir\n";
    }

    foreach ($extensions as $ext) {
        $filePath = "$resourceDir/$id.$ext";
        if (!file_exists($filePath)) {
            // Create empty file or with basic template
            $content = "";
            if ($ext === 'json') $content = "{}";
            if ($ext === 'html') $content = "<!-- $id -->";
            
            file_put_contents($filePath, $content);
            echo "Created file: $filePath\n";
        }

        // Open in VS Code if requested
        if ($openInEditor) {
            // Using 'code' command (requires code in PATH)
            // Using shell_exec in background to not block
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B code \"$filePath\"", "r"));
            } else {
                exec("code \"$filePath\" > /dev/null 2>&1 &");
            }
        }
    }
}

/**
 * Deletes physical files.
 */
function deletePhysicalFiles(string $rootPath, string $type, string $id): void {
    if (!isset(TYPE_MAP[$type])) {
        return;
    }

    $config = TYPE_MAP[$type];
    $folderName = $config['folder'];
    
    $resourceDir = "$rootPath/$folderName/$id";

    if (is_dir($resourceDir)) {
        // Simple recursive delete
        $files = array_diff(scandir($resourceDir), ['.', '..']);
        foreach ($files as $file) {
            unlink("$resourceDir/$file");
        }
        rmdir($resourceDir);
        echo "Removed directory: $resourceDir\n";
    }
}

/**
 * Copies physical files from one location to another.
 */
function copyPhysicalFiles(string $srcRoot, string $destRoot, string $type, string $srcId, string $destId): void {
    if (!isset(TYPE_MAP[$type])) {
        return;
    }

    $config = TYPE_MAP[$type];
    $folderName = $config['folder'];
    
    $srcDir = "$srcRoot/$folderName/$srcId";
    $destDir = "$destRoot/$folderName/$destId";

    if (!is_dir($srcDir)) {
        echo "Source directory not found: $srcDir\n";
        return;
    }

    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }

    $files = array_diff(scandir($srcDir), ['.', '..']);
    foreach ($files as $file) {
        $srcFile = "$srcDir/$file";
        
        // Rename file if it contains the ID
        $destFile = str_replace($srcId, $destId, $file);
        $destPath = "$destDir/$destFile";

        copy($srcFile, $destPath);
        echo "Copied: $file -> $destFile\n";
    }
}

/**
 * Colors text for CLI output.
 */
function colorize(string $text, string $color): string {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'cyan' => "\033[36m",
        'reset' => "\033[0m"
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

/**
 * Parses command line arguments like --key=value.
 */
function parseArgs(array $argv): array {
    $args = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            $args[$key] = $value;
        }
    }
    return $args;
}

// ===============================================================================================
// 3. Logic Actions (Upsert, Delete, Copy)
// ===============================================================================================

function processUpsert(string $rootPath, array $args, array $data): void {
    $type = $args['type'];
    $id = $data['id'] ?? $args['id'] ?? null;

    if (!$id) {
        throw new RuntimeException("ID is required for Upsert.");
    }

    echo colorize(">>> Processing Upsert: [$type] $id\n", 'cyan');

    // 1. Update Metadata (resources.json)
    $metaPath = getMetadataFilePath($rootPath, $type);
    $resources = readJson($metaPath);
    
    // Merge data (preserve existing, overwrite with new)
    $existing = $resources[$id] ?? [];
    $merged = array_merge($existing, $data);
    $merged['id'] = $id; // Ensure ID is set
    $merged['updated_at'] = date('Y-m-d H:i:s');
    if (!isset($merged['created_at'])) {
        $merged['created_at'] = date('Y-m-d H:i:s');
    }

    $resources[$id] = $merged;
    saveJson($metaPath, $resources);
    echo "Metadata updated in: $metaPath\n";

    // 2. Handle Physical Files
    handlePhysicalFiles($rootPath, $type, $id, $args['open'] ?? false);
}

function processDelete(string $rootPath, array $args, array $data): void {
    $type = $args['type'];
    $id = $data['id'] ?? $args['id'] ?? null;

    if (!$id) {
        throw new RuntimeException("ID is required for Delete.");
    }

    echo colorize(">>> Processing Delete: [$type] $id\n", 'red');

    // 1. Update Metadata
    $metaPath = getMetadataFilePath($rootPath, $type);
    $resources = readJson($metaPath);

    if (isset($resources[$id])) {
        unset($resources[$id]);
        saveJson($metaPath, $resources);
        echo "Removed from metadata: $metaPath\n";
    } else {
        echo "ID not found in metadata.\n";
    }

    // 2. Remove Files
    deletePhysicalFiles($rootPath, $type, $id);
}

function processCopy(string $workspaceRoot, string $envFile, array $args, array $data): void {
    // Separate Source and Target Contexts
    // Source: source-target, source-scope, source-module-id, source-lang
    // Target: target, scope, module-id, lang (standard)

    $srcArgs = [
        'target' => $args['source-target'] ?? $args['target'],
        'scope' => $args['source-scope'] ?? $args['scope'],
        'module-id' => $args['source-module-id'] ?? $args['module-id'] ?? null,
        'lang' => $args['source-lang'] ?? $args['lang'],
        'plugin-type' => $args['source-plugin-type'] ?? $args['plugin-type'] ?? 'private'
    ];

    $destArgs = $args; // Uses standard args for destination

    $srcRoot = resolveRootPath($srcArgs, $envFile, $workspaceRoot);
    $destRoot = resolveRootPath($destArgs, $envFile, $workspaceRoot);

    $type = $args['type'];
    $srcId = $data['id'] ?? $args['id'] ?? null;
    $destId = $args['new-id'] ?? $srcId;

    if (!$srcId) {
        throw new RuntimeException("Source ID is required for Copy.");
    }

    echo colorize(">>> Processing Copy: [$type] $srcId -> $destId\n", 'yellow');
    echo "Source: $srcRoot\n";
    echo "Target: $destRoot\n";

    // 1. Copy Metadata
    $srcMetaPath = getMetadataFilePath($srcRoot, $type);
    $destMetaPath = getMetadataFilePath($destRoot, $type);

    $srcResources = readJson($srcMetaPath);
    if (!isset($srcResources[$srcId])) {
        throw new RuntimeException("Source ID '$srcId' not found in $srcMetaPath");
    }

    $resourceData = $srcResources[$srcId];
    $resourceData['id'] = $destId; // Update ID
    $resourceData['copied_from'] = $srcId;
    $resourceData['updated_at'] = date('Y-m-d H:i:s');
    $resourceData['created_at'] = date('Y-m-d H:i:s');

    $destResources = readJson($destMetaPath);
    $destResources[$destId] = $resourceData;
    saveJson($destMetaPath, $destResources);
    echo "Metadata copied to: $destMetaPath\n";

    // 2. Copy Files
    copyPhysicalFiles($srcRoot, $destRoot, $type, $srcId, $destId);

    // 3. Open new files if requested
    if ($args['open'] ?? false) {
        // Re-using handlePhysicalFiles logic just to open (files already exist/copied)
        // Or just construct path and open
        $config = TYPE_MAP[$type];
        $folderName = $config['folder'];
        $extensions = $config['files'];
        $resourceDir = "$destRoot/$folderName/$destId";
        
        foreach ($extensions as $ext) {
            $filePath = "$resourceDir/$destId.$ext";
            if (file_exists($filePath)) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("start /B code \"$filePath\"", "r"));
                } else {
                    exec("code \"$filePath\" > /dev/null 2>&1 &");
                }
            }
        }
    }
}

/**
 * Interactive Prompt.
 */
function prompt(string $question, array $options = [], ?string $default = null): string {
    echo colorize($question, 'cyan') . "\n";
    
    if (!empty($options)) {
        foreach ($options as $key => $val) {
            echo " [$key] $val\n";
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
            echo "Invalid option. Try again.\n";
            return prompt($question, $options, $default);
        }
        return $input;
    }
    
    return $input;
}

/**
 * Helper to ask context parameters (Target, Scope, etc).
 */
function askContextParameters(array $args, string $prefix = '', string $title = ''): array {
    if ($title) {
        echo "\n" . colorize("=== Context Configuration: $title ===", 'yellow') . "\n";
    }

    // 1. Target
    if (empty($args[$prefix . 'target'])) {
        $opt = prompt("Select Target ($title):", [
            '1' => 'Manager',
            '2' => 'Plugin',
            '3' => 'Project'
        ], '1');
        $map = ['1' => 'gestor', '2' => 'plugin', '3' => 'project'];
        $args[$prefix . 'target'] = $map[$opt];
    }

    // 2. Plugin Type
    if ($args[$prefix . 'target'] === 'plugin' && empty($args[$prefix . 'plugin-type'])) {
        $opt = prompt("Plugin Type ($title):", [
            '1' => 'Public',
            '2' => 'Private'
        ], '2');
        $map = ['1' => 'public', '2' => 'private'];
        $args[$prefix . 'plugin-type'] = $map[$opt];
    }

    // 3. Scope
    if (empty($args[$prefix . 'scope'])) {
        $opt = prompt("Scope ($title):", [
            '1' => 'Global',
            '2' => 'Module'
        ], '1');
        $map = ['1' => 'global', '2' => 'module'];
        $args[$prefix . 'scope'] = $map[$opt];
    }

    // 4. Module ID
    if ($args[$prefix . 'scope'] === 'module' && empty($args[$prefix . 'module-id'])) {
        while (empty($args[$prefix . 'module-id'])) {
            $args[$prefix . 'module-id'] = prompt("Module ID ($title):");
        }
    }

    // 5. Lang
    if (empty($args[$prefix . 'lang'])) {
        $args[$prefix . 'lang'] = prompt("Language ($title):", [], 'pt-br');
    }

    return $args;
}

/**
 * Runs interactive mode to fill missing arguments.
 */
function runInteractiveMode(array $args): array {
    echo "\n=== Resource Interactive Mode ===\n";

    // 1. Action (Now first)
    if (empty($args['action'])) {
        $opt = prompt("Action:", [
            '1' => 'Upsert (Create/Update)',
            '2' => 'Delete (Remove)',
            '3' => 'Copy'
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
        
        // Default to 'page' if in list, else first
        $defaultIdx = array_search('page', $typeMapKeys);
        $defaultKey = $defaultIdx !== false ? (string)($defaultIdx + 1) : '1';

        $opt = prompt("Resource Type:", $typeOptions, $defaultKey);
        $args['type'] = $selectionMap[$opt];
    }

    // 3. Input Data (ID or JSON)
    if (empty($args['id']) && empty($args['data'])) {
        $inputType = prompt("Data Input:", [
            '1' => 'ID List',
            '2' => 'Full JSON'
        ], '1');

        if ($inputType === '1') {
            while (empty($args['id'])) {
                $args['id'] = prompt("Resource ID(s) (comma separated):");
            }
        } else {
            echo "\n" . colorize("Paste JSON data (in a single line) and press ENTER:", 'cyan') . "\n > ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            $args['data'] = trim($line);
        }
    }

    // 4. Open
    if (!isset($args['open'])) {
        $opt = prompt("Open files in editor?", [
            '1' => 'Yes',
            '0' => 'No'
        ], '1');
        if ($opt == '1') $args['open'] = true;
    }

    // 5. Contexts (Target, Scope, etc)
    if ($args['action'] === 'copy') {
        // Source
        $args = askContextParameters($args, 'source-', 'SOURCE');
        // Target (Default)
        $args = askContextParameters($args, '', 'TARGET');

        // New ID (Optional)
        if (empty($args['new-id'])) {
            $args['new-id'] = prompt("New ID in target (optional, leave empty to use same):");
            if ($args['new-id'] === '') {
                unset($args['new-id']);
            }
        }
    } else {
        // Single Context
        $args = askContextParameters($args, '', '');
    }

    echo "\n" . colorize("--------------------------------------------------", 'green') . "\n";
    return $args;
}

// ===============================================================================================
// 4. Main Execution
// ===============================================================================================

try {
    $args = parseArgs($argv);
    
    // Check interactive mode (Explicit flag or no arguments)
    if (empty($args) || isset($args['interactive']) || isset($args['i'])) {
        $args = runInteractiveMode($args);
    }
    
    if (empty($args['type'])) {
        throw new RuntimeException("Argument --type is required.");
    }

    $itemsToProcess = [];

    if (!empty($args['id'])) {
        // ID List Mode (Navigation/Quick Creation)
        $idList = explode(',', $args['id']);
        foreach ($idList as $id) {
            $id = trim($id);
            if (!$id) continue;
            $itemsToProcess[] = ['id' => $id];
        }
    } elseif (!empty($args['data'])) {
        // Full Data Mode (JSON)
        $singleData = json_decode($args['data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Error decoding data JSON: " . json_last_error_msg());
        }
        $itemsToProcess[] = $singleData;
    } else {
        throw new RuntimeException("--data OR --id is required.");
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
            throw new RuntimeException("Unknown action: $action");
        }
    }

} catch (Throwable $e) {
    echo colorize("Error: " . $e->getMessage() . "\n", 'red');
    exit(1);
}
