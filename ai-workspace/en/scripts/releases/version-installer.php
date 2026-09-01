<?php

/**
 * Bumps the gestor-instalador version.
 *
 * Canonical source (installer v2): `gestor-instalador/src/InstallerGuard.php`, in
 * `const VERSION = 'x.y.z';`. The `index.php` front controller only references
 * `InstallerGuard::VERSION`, so this script keeps its descriptive comment `(x.y.z)`
 * synchronized instead of rewriting an inexistent literal.
 *
 * Backward compatibility (installer v1): when `InstallerGuard.php` is absent or has no
 * `const VERSION`, the script falls back to the legacy literal in
 * `$_GESTOR_INSTALADOR['versao'] = 'x.y.z';` inside `index.php`.
 */

$installerRoot = __DIR__ . '/../../../../gestor-instalador';
$guardPath = $installerRoot . '/src/InstallerGuard.php';
$indexPath = $installerRoot . '/index.php';

$updateType = $argv[1] ?? 'patch'; // 'patch', 'minor', 'major'
$dryRun = in_array('--dry-run', $argv, true);
if (!in_array($updateType, ['patch', 'minor', 'major'], true)) {
    fwrite(STDERR, "Error: Invalid update type '$updateType'. Use patch, minor or major.\n");
    exit(1);
}

// Canonical constant of the installer v2 and legacy literal of the installer v1.
$guardPattern = "/(const\s+VERSION\s*=\s*')(\d+\.\d+\.\d+)(')/";
$legacyPattern = "/(\\\$_GESTOR_INSTALADOR\['versao'\]\s*=\s*')(\d+\.\d+\.\d+)(')/";
// Descriptive comment of `index.php` that mirrors the canonical constant.
$commentPattern = "/(InstallerGuard::VERSION;[^\r\n]*\()(\d+\.\d+\.\d+)(\))/";

/**
 * Bumps a semantic version according to the requested update type.
 */
function bump_semver($currentVersion, $updateType)
{
    list($major, $minor, $patch) = array_map('intval', explode('.', $currentVersion));

    switch ($updateType) {
        case 'major':
            $major++;
            $minor = 0;
            $patch = 0;
            break;
        case 'minor':
            $minor++;
            $patch = 0;
            break;
        case 'patch':
        default:
            $patch++;
            break;
    }

    return "$major.$minor.$patch";
}

/**
 * Rewrites the first line matching `$pattern`, preserving the rest of the file formatting.
 * Returns the captured current version, or an empty string when no line matched.
 */
function replace_first_version(array &$lines, $pattern, $newVersion)
{
    foreach ($lines as $i => $line) {
        if (!preg_match($pattern, $line, $matches)) {
            continue;
        }

        if ($newVersion !== null) {
            $lines[$i] = preg_replace($pattern, '${1}' . $newVersion . '${3}', $line, 1);
        }

        return $matches[2];
    }

    return '';
}

$guardLines = is_file($guardPath) ? file($guardPath) : false;
$indexLines = is_file($indexPath) ? file($indexPath) : false;

if ($guardLines === false && $indexLines === false) {
    fwrite(STDERR, "Error: Could not read InstallerGuard.php at $guardPath nor index.php at $indexPath\n");
    exit(1);
}

$source = '';
$currentVersion = '';

if ($guardLines !== false) {
    $currentVersion = replace_first_version($guardLines, $guardPattern, null);
    if ($currentVersion !== '') {
        $source = 'guard';
    }
}

if ($currentVersion === '' && $indexLines !== false) {
    $currentVersion = replace_first_version($indexLines, $legacyPattern, null);
    if ($currentVersion !== '') {
        $source = 'legacy';
    }
}

if ($currentVersion === '') {
    fwrite(STDERR, "Error: Version pattern not found in InstallerGuard.php nor in installer index.php.\n");
    exit(1);
}

$newVersion = bump_semver($currentVersion, $updateType);

if ($dryRun) {
    echo $newVersion;
    exit(0);
}

if ($source === 'guard') {
    replace_first_version($guardLines, $guardPattern, $newVersion);
    file_put_contents($guardPath, implode('', $guardLines));

    // Keeps the descriptive comment of `index.php` aligned with the canonical constant.
    if ($indexLines !== false && replace_first_version($indexLines, $commentPattern, $newVersion) !== '') {
        file_put_contents($indexPath, implode('', $indexLines));
    }
} else {
    replace_first_version($indexLines, $legacyPattern, $newVersion);
    file_put_contents($indexPath, implode('', $indexLines));
}

// Prints the new version so the release script can capture it
echo $newVersion;
