<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// O compilador e o sincronizador de banco declaram funções de mesmo nome (`main`,
// `dataFileNameFromTable`); carregado no processo da suíte, o compilador derruba os testes do
// sincronizador. Por isso é carregado só no processo filho de cada teste.
#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class Req203LanguageAgnosticResourcesTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_ROOT . '/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php';
    }

    public function testContratosReaisMantemRecursosGlobaisForaDosMapasPorIdioma(): void
    {
        $resourcesDirectory = CONN2FLOW_ROOT . '/gestor/resources';
        $config = json_decode(
            file_get_contents($resourcesDirectory . '/tables_config.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $map = require $resourcesDirectory . '/resources.map.php';
        $tables = [
            'usuarios' => ['file' => 'users.json', 'map_key' => 'users', 'keys' => ['id'], 'count' => 1],
            'usuarios_perfis_modulos' => ['file' => 'user_profiles_modules.json', 'map_key' => 'user_profiles_modules', 'keys' => ['perfil', 'modulo'], 'count' => 39],
            'usuarios_perfis_modulos_operacoes' => ['file' => 'user_profiles_modules_operations.json', 'map_key' => 'user_profiles_modules_operations', 'keys' => ['perfil', 'operacao'], 'count' => 3],
            'categorias' => ['file' => 'categories.json', 'map_key' => 'categories', 'keys' => ['id'], 'count' => 1],
        ];

        foreach ($tables as $table => $definition) {
            $tableConfig = $config['tabelas'][$table]['config'] ?? [];
            self::assertTrue($tableConfig['language_agnostic'] ?? false, $table);
            self::assertSame($definition['file'], $tableConfig['metadata_file'], $table);
            self::assertSame($definition['keys'], $tableConfig['natural_key_columns'], $table);
            self::assertFileExists($resourcesDirectory . '/' . $definition['file']);
            self::assertCount(
                $definition['count'],
                json_decode(
                    file_get_contents($resourcesDirectory . '/' . $definition['file']),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
                $table
            );

            foreach (['pt-br', 'en'] as $language) {
                self::assertArrayNotHasKey($definition['map_key'], $map['languages'][$language]['data']);
                self::assertFileDoesNotExist($resourcesDirectory . '/' . $language . '/' . $definition['file']);
            }
        }
    }

    public function testColetorLeTabelasAgnosticasDaRaizUmaUnicaVez(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req203-global-' . bin2hex(random_bytes(6));
        $resourcesDirectory = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $modulesDirectory = $root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR;
        $dataDirectory = $root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        mkdir($resourcesDirectory . 'pt-br', 0777, true);
        mkdir($resourcesDirectory . 'en', 0777, true);
        mkdir($modulesDirectory, 0777, true);
        mkdir($dataDirectory, 0777, true);

        $sourceResourcesDirectory = CONN2FLOW_ROOT . '/gestor/resources';
        $sourceConfig = json_decode(
            file_get_contents($sourceResourcesDirectory . '/tables_config.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $tableFiles = [
            'usuarios' => 'users.json',
            'usuarios_perfis_modulos' => 'user_profiles_modules.json',
            'usuarios_perfis_modulos_operacoes' => 'user_profiles_modules_operations.json',
            'categorias' => 'categories.json',
        ];
        $tables = [];
        $config = ['tabelas' => []];
        foreach ($tableFiles as $table => $file) {
            $definition = [
                'metadata_file' => $file,
                'records' => json_decode(
                    file_get_contents($sourceResourcesDirectory . '/' . $file),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            ];
            $tables[$table] = $definition;
            $config['tabelas'][$table] = $sourceConfig['tabelas'][$table];
            file_put_contents(
                $resourcesDirectory . $file,
                json_encode($definition['records'], JSON_THROW_ON_ERROR)
            );
            file_put_contents(
                $resourcesDirectory . 'pt-br' . DIRECTORY_SEPARATOR . $file,
                '[]'
            );
            file_put_contents(
                $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . $file,
                '[]'
            );
        }
        file_put_contents(
            $resourcesDirectory . 'tables_config.json',
            json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $resourcesDirectory . 'resources.map.php',
            file_get_contents(CONN2FLOW_ROOT . '/gestor/resources/resources.map.php')
        );

        global $SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE;
        $previous = [
            'system_path' => $SYSTEM_PATH,
            'resources_dir' => $RESOURCES_DIR,
            'modules_dir' => $MODULES_DIR,
            'db_data_dir' => $DB_DATA_DIR,
            'gestor_dir' => $GESTOR_DIR,
            'log_file' => $LOG_FILE,
            'cli_args' => $GLOBALS['CLI_ARGS'] ?? [],
        ];
        $SYSTEM_PATH = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR;
        $RESOURCES_DIR = $resourcesDirectory;
        $MODULES_DIR = $modulesDirectory . DIRECTORY_SEPARATOR;
        $DB_DATA_DIR = $dataDirectory;
        $GESTOR_DIR = $root . DIRECTORY_SEPARATOR;
        $LOG_FILE = 'req203-test';
        $GLOBALS['CLI_ARGS'] = [];

        try {
            $resources = coletarRecursos([], [
                'languages' => [
                    'pt-br' => ['data' => []],
                    'en' => ['data' => []],
                ],
            ]);

            foreach ($tables as $table => $definition) {
                $records = $resources['dynamicTablesData'][$table] ?? [];
                self::assertCount(count($definition['records']), $records, $table);
                self::assertArrayNotHasKey('language', $records[0], $table);
                $record = $records[0];
                unset($record['versao'], $record['checksum'], $record['user_modified']);
                if (!array_key_exists('status', $definition['records'][0])) unset($record['status']);
                self::assertSame($definition['records'][0], $record, $table);
            }

            $GLOBALS['CLI_ARGS'] = [
                'only' => implode(',', array_keys($tables)),
                'skip-css' => true,
                'no-origin-update' => true,
                'no-assets' => true,
            ];
            self::assertSame(0, main());

            foreach ($tables as $table => $definition) {
                $dataFile = $dataDirectory . dataFileNameFromTable($table);
                $records = json_decode(file_get_contents($dataFile), true, 512, JSON_THROW_ON_ERROR);
                self::assertCount(count($definition['records']), $records, $table);
                self::assertArrayNotHasKey('language', $records[0], $table);
            }
        } finally {
            $this->removeDirectory($root);
            $SYSTEM_PATH = $previous['system_path'];
            $RESOURCES_DIR = $previous['resources_dir'];
            $MODULES_DIR = $previous['modules_dir'];
            $DB_DATA_DIR = $previous['db_data_dir'];
            $GESTOR_DIR = $previous['gestor_dir'];
            $LOG_FILE = $previous['log_file'];
            $GLOBALS['CLI_ARGS'] = $previous['cli_args'];
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
