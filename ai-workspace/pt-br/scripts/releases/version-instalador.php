<?php

/**
 * Incrementa a versão do gestor-instalador.
 *
 * Fonte canônica (instalador v2): `gestor-instalador/src/InstallerGuard.php`, em
 * `const VERSION = 'x.y.z';`. O front controller `index.php` apenas referencia
 * `InstallerGuard::VERSION`, então este script mantém o comentário descritivo `(x.y.z)`
 * sincronizado em vez de reescrever um literal inexistente.
 *
 * Retrocompatibilidade (instalador v1): quando `InstallerGuard.php` não existe ou não tem
 * `const VERSION`, o script cai no literal legado em
 * `$_GESTOR_INSTALADOR['versao'] = 'x.y.z';` dentro do `index.php`.
 */

$installerRoot = __DIR__ . '/../../../../gestor-instalador';
$guardPath = $installerRoot . '/src/InstallerGuard.php';
$indexPath = $installerRoot . '/index.php';

$updateType = $argv[1] ?? 'patch'; // 'patch', 'minor', 'major'
$dryRun = in_array('--dry-run', $argv, true);
if (!in_array($updateType, ['patch', 'minor', 'major'], true)) {
    fwrite(STDERR, "Erro: Tipo de atualização inválido '$updateType'. Use patch, minor ou major.\n");
    exit(1);
}

// Constante canônica do instalador v2 e literal legado do instalador v1.
$guardPattern = "/(const\s+VERSION\s*=\s*')(\d+\.\d+\.\d+)(')/";
$legacyPattern = "/(\\\$_GESTOR_INSTALADOR\['versao'\]\s*=\s*')(\d+\.\d+\.\d+)(')/";
// Comentário descritivo do `index.php` que espelha a constante canônica.
$commentPattern = "/(InstallerGuard::VERSION;[^\r\n]*\()(\d+\.\d+\.\d+)(\))/";

/**
 * Incrementa uma versão semântica conforme o tipo de atualização solicitado.
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
 * Reescreve a primeira linha que casa com `$pattern`, preservando o resto da formatação.
 * Devolve a versão atual capturada, ou string vazia quando nenhuma linha casou.
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
    fwrite(STDERR, "Erro: Não foi possível ler o InstallerGuard.php em $guardPath nem o index.php em $indexPath\n");
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
    fwrite(STDERR, "Erro: Padrão de versão não encontrado no InstallerGuard.php nem no index.php do instalador.\n");
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

    // Mantém o comentário descritivo do `index.php` alinhado à constante canônica.
    if ($indexLines !== false && replace_first_version($indexLines, $commentPattern, $newVersion) !== '') {
        file_put_contents($indexPath, implode('', $indexLines));
    }
} else {
    replace_first_version($indexLines, $legacyPattern, $newVersion);
    file_put_contents($indexPath, implode('', $indexLines));
}

// Imprime a nova versão para que o script de release possa capturá-la
echo $newVersion;
