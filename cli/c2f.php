<?php

declare(strict_types=1);

/**
 * Conn2Flow Core CLI Entry Point
 * ----------------------------------------------------
 * Bootstrap file for the modern OOP CLI subsystem.
 */

namespace Conn2Flow\Cli;

use Conn2Flow\Cli\Console\Application;

// Ensure CLI execution only
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Error: The c2f tool must be executed from the command line." . PHP_EOL);
    exit(1);
}

// Basic Autoloader for Conn2Flow\Cli namespace
spl_autoload_register(static function (string $class): void {
    $prefix = 'Conn2Flow\\Cli\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Root path of the repository
$rootPath = dirname(__DIR__);

// Run Application
$app = new Application($rootPath);
$exitCode = $app->run($argv);

exit($exitCode);
