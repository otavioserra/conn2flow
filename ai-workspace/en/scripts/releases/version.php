<?php

// The path now goes up one level ('..') to find config.php in the manager root.
$configPath = __DIR__ . '/../../../gestor/config.php';
$lines = file($configPath); // Reads the file as an array of lines, preserving formatting

if ($lines === false) {
    fwrite(STDERR, "Error: Could not read config.php file at: $configPath\n");
    exit(1);
}

$updateType = $argv[1] ?? 'patch'; // 'patch', 'minor', 'major'

$versionUpdated = false;
$newVersion = '';

foreach ($lines as $i => $line) {
    // Uses strpos for a flexible search of the line containing the version definition
    if (strpos($line, "\$_GESTOR['versao']") !== false) {
        // Once the line is found, uses a simple regex to extract the version number
        $pattern = "/(')(\d+\.\d+\.\d+)(')/";
        
        if (preg_match($pattern, $line, $matches)) {
            $currentVersion = $matches[2];
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
         
            $newVersion = "$major.$minor.$patch";
         
            // Replaces only the version number, preserving the rest of the line
            $lines[$i] = preg_replace($pattern, '${1}' . $newVersion . '${3}', $line, 1);
            $versionUpdated = true;
            
            // Stops the loop as the version has already been found and updated
            break;
        }
    }
}
 
if ($versionUpdated) {
    file_put_contents($configPath, implode('', $lines));
    // Prints the new version so the release script can capture it
    echo $newVersion;
} else {
    fwrite(STDERR, "Error: Version pattern not found in config.php.\n");
    exit(1);
}
