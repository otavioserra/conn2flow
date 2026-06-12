<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhinxMigrationsTest extends TestCase
{
    public function testPhinxConfigExisteEApontaParaMigrationsDoGestor(): void
    {
        $configPath = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'phinx.php';
        $migrationsPath = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'migrations';

        self::assertFileExists($configPath);
        self::assertDirectoryExists($migrationsPath);
        self::assertNotEmpty(glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: []);
    }

    public function testFluxoDeMigracoesPodeSerExecutadoEmBancoDeTesteConfigurado(): void
    {
        if (getenv('CONN2FLOW_RUN_DB_TESTS') !== '1') {
            self::markTestSkipped('Defina CONN2FLOW_RUN_DB_TESTS=1 para executar migrations contra o banco de testes.');
        }

        $command = sprintf(
            '%s %s migrate -c %s -e gestor',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phinx'),
            escapeshellarg(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'phinx.php')
        );

        exec($command . ' 2>&1', $output, $exitCode);

        self::assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }
}
