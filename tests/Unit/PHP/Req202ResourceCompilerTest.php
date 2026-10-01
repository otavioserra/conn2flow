<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// O compilador e o sincronizador de banco declaram funções de mesmo nome (`main`,
// `dataFileNameFromTable`); carregado no processo da suíte, o compilador derruba os testes do
// sincronizador. Por isso é carregado só no processo filho de cada teste.
#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class Req202ResourceCompilerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_ROOT . '/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php';
    }

    public function testConfiguracaoEfetivaDeUsuariosPreservaCamposSensíveis(): void
    {
        $module = json_decode(
            (string) file_get_contents(CONN2FLOW_ROOT . '/gestor/modulos/usuarios/usuarios.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $config = $module['tabela']['config'];

        self::assertSame('natural_key', $config['strategy']);
        self::assertSame(['id'], $config['natural_key_columns']);
        self::assertSame(['senha', 'email', 'nome', 'usuario', 'status'], $config['preserve_on_user_modified']);
        self::assertTrue($config['insert_only']);
    }

    public function testOnlyRejeitaAlvoDesconhecido(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('--only contém alvo desconhecido');

        validarAlvosOnly(['alvo_inexistente']);
    }

    public function testOnlyAtualizaODataJsonSelecionado(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req202-data-' . bin2hex(random_bytes(6));
        $dataDirectory = $root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        $gestorDirectory = $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR;
        mkdir($dataDirectory, 0777, true);
        file_put_contents($dataDirectory . 'VariaveisData.json', '[{"preservar":true}]');

        global $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE;
        $previous = [
            'db_data_dir' => $DB_DATA_DIR,
            'gestor_dir' => $GESTOR_DIR,
            'log_file' => $LOG_FILE,
            'cli_args' => $GLOBALS['CLI_ARGS'] ?? [],
        ];
        $DB_DATA_DIR = $dataDirectory;
        $GESTOR_DIR = $gestorDirectory;
        $LOG_FILE = 'req202-test';
        $GLOBALS['CLI_ARGS'] = ['only' => 'modulos'];

        try {
            atualizarDados([], [
                'layoutsData' => [],
                'pagesData' => [],
                'componentsData' => [],
                'templatesData' => [],
                'variablesData' => [],
                'promptsData' => [],
                'targetsData' => [],
                'formsData' => [],
                'modesData' => [],
                'widgetsData' => [],
                'cronTarefasData' => [],
                'dynamicTablesData' => ['modulos' => [['id' => 'admin', 'language' => 'pt-br']]],
                'orphans' => [],
            ]);

            self::assertSame(
                [['id' => 'admin', 'language' => 'pt-br']],
                json_decode((string) file_get_contents($dataDirectory . 'ModulosData.json'), true, 512, JSON_THROW_ON_ERROR)
            );
            self::assertSame('[{"preservar":true}]', file_get_contents($dataDirectory . 'VariaveisData.json'));
        } finally {
            $this->removeDirectory($root);
            $DB_DATA_DIR = $previous['db_data_dir'];
            $GESTOR_DIR = $previous['gestor_dir'];
            $LOG_FILE = $previous['log_file'];
            $GLOBALS['CLI_ARGS'] = $previous['cli_args'];
        }
    }

    public function testColetorAceitaTabelaDeclarativaComChaveNaturalSemId(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req202-natural-' . bin2hex(random_bytes(6));
        $resourcesDirectory = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $modulesDirectory = $root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR;
        $dataDirectory = $root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        mkdir($resourcesDirectory . 'pt-br', 0777, true);
        mkdir($modulesDirectory, 0777, true);
        mkdir($dataDirectory, 0777, true);
        file_put_contents($resourcesDirectory . 'tables_config.json', json_encode([
            'tabelas' => [
                'usuarios_perfis_modulos' => [
                    'nome' => 'usuarios_perfis_modulos',
                    'id' => 'id',
                    'config' => [
                        'strategy' => 'natural_key',
                        'natural_key_columns' => ['perfil', 'modulo'],
                        'sync_resources' => true,
                        'metadata_file' => 'user_profiles_modules.json',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        file_put_contents($resourcesDirectory . 'pt-br/user_profiles_modules.json', json_encode([
            ['perfil' => 'administradores', 'modulo' => 'zeta'],
            ['perfil' => 'administradores', 'modulo' => 'alpha'],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        global $SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $LOG_FILE;
        $previous = [
            'system_path' => $SYSTEM_PATH,
            'resources_dir' => $RESOURCES_DIR,
            'modules_dir' => $MODULES_DIR,
            'db_data_dir' => $DB_DATA_DIR,
            'log_file' => $LOG_FILE,
            'cli_args' => $GLOBALS['CLI_ARGS'] ?? [],
        ];
        $SYSTEM_PATH = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR;
        $RESOURCES_DIR = $resourcesDirectory;
        $MODULES_DIR = $modulesDirectory;
        $DB_DATA_DIR = $dataDirectory;
        $LOG_FILE = 'req202-test';
        $GLOBALS['CLI_ARGS'] = [];

        try {
            $resources = coletarRecursos([], ['languages' => ['pt-br' => ['data' => []]]]);

            self::assertSame(
                [
                    ['perfil' => 'administradores', 'modulo' => 'alpha', 'language' => 'pt-br', 'status' => 'A', 'versao' => 1],
                    ['perfil' => 'administradores', 'modulo' => 'zeta', 'language' => 'pt-br', 'status' => 'A', 'versao' => 1],
                ],
                array_map(static function (array $record): array {
                    unset($record['checksum'], $record['user_modified']);
                    return $record;
                }, $resources['dynamicTablesData']['usuarios_perfis_modulos'] ?? [])
            );
        } finally {
            $this->removeDirectory($root);
            $SYSTEM_PATH = $previous['system_path'];
            $RESOURCES_DIR = $previous['resources_dir'];
            $MODULES_DIR = $previous['modules_dir'];
            $DB_DATA_DIR = $previous['db_data_dir'];
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
