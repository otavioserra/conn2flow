<?php
/**
 * Conn2Flow Plugin Version Bumper
 *
 * This script updates the version in the manifest.json of the active plugin.
 *
 * How it works:
 * - By default, it reads the plugin list and the active plugin id from environment.json (in the parent directory).
 * - It locates the manifest.json of the active plugin and bumps the version (major, minor, or patch).
 * - You can override the plugin path, manifest path, or version type via command-line arguments for automation/CI.
 *
 * Usage:
 *   php version.php [type] [plugin_path] [manifest_path]
 *
 *   type         = 'major', 'minor', or 'patch' (default: patch)
 *   plugin_path  = path to the plugin directory (overrides environment.json)
 *   manifest_path = path to manifest.json (overrides everything)
 *
 * Examples:
 *   php version.php minor
 *   php version.php patch ../plugin
 *   php version.php major ../plugin ../plugin/manifest.json
 *
 * The script will always use the following priority for locating the manifest:
 *   1. manifest_path argument (if provided)
 *   2. plugin_path argument + '/manifest.json' (if provided)
 *   3. plugin path from environment.json + '/manifest.json'
 *
 * The active plugin is determined by 'activePlugin.id' in environment.json, which must match an entry in 'plugins'.
 *
 * The manifest must have a 'version' field in the format 'X.Y.Z'.
 */

function get_env_json($baseDir) {
    $envPath = $baseDir . '/environment.json';
    if (!file_exists($envPath)) {
        fwrite(STDERR, "environment.json not found: $envPath\n");
        exit(1);
    }
    $env = json_decode(file_get_contents($envPath), true);
    if (!$env) {
        fwrite(STDERR, "Failed to parse environment.json\n");
        exit(1);
    }
    return $env;
}

$baseDir = dirname(__DIR__, 1); // environment.json is one level up
$type = $argv[1] ?? 'patch';
$pluginPathArg = $argv[2] ?? null;
$manifestPathArg = $argv[3] ?? null;

if ($manifestPathArg) {
    $manifestPath = $manifestPathArg;
} else {
    if ($pluginPathArg) {
        $pluginPath = $pluginPathArg;
    } else {
        $env = get_env_json($baseDir);
        $activeId = $env['activePlugin']['id'] ?? null;
        if (!$activeId) {
            fwrite(STDERR, "activePlugin.id not set in environment.json\n");
            exit(1);
        }
        $plugin = null;
        foreach ($env['plugins'] as $p) {
            if ($p['id'] === $activeId) {
                $plugin = $p;
                break;
            }
        }
        if (!$plugin) {
            fwrite(STDERR, "Plugin with id '$activeId' not found in environment.json\n");
            exit(1);
        }
        $pluginPath = $baseDir . '/' . $plugin['path'];
    }
    $manifestPath = rtrim($pluginPath, '/\\') . '/manifest.json';
}

if (!file_exists($manifestPath)) {
    fwrite(STDERR, "Manifest not found: $manifestPath\n");
    exit(1);
}
$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    fwrite(STDERR, "Failed to parse manifest.json\n");
    exit(1);
}

$versionKey = isset($manifest['version']) ? 'version' : (isset($manifest['versao']) ? 'versao' : null);
if (!$versionKey) {
    fwrite(STDERR, "No 'version' field found in manifest.json\n");
    exit(1);
}
list($maj, $min, $pat) = array_map('intval', explode('.', $manifest[$versionKey]));
switch ($type) {
    case 'major': $maj++; $min = 0; $pat = 0; break;
    case 'minor': $min++; $pat = 0; break;
    default: $pat++; break;
}
$manifest[$versionKey] = $new = "$maj.$min.$pat";
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo $new;
