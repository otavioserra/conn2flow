<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-206: compilando um PROJETO, as tabelas que o contrato global do core semeia (req-202) são
 * semeadas pelo projeto. Ler as sementes do core gravava os dados do core por cima do `db/data` do
 * projeto, e o deploy retirava os módulos e as permissões dele.
 *
 * O compilador e o sincronizador de banco declaram funções de mesmo nome (`main`,
 * `dataFileNameFromTable`), então o compilador é carregado só no processo filho de cada teste.
 */
#[PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class Req206ProjectSeedsTest extends TestCase
{
    private const TABELAS_DO_CORE = [
        'modulos', 'modulos_grupos', 'modulos_operacoes', 'usuarios', 'usuarios_perfis',
        'usuarios_perfis_modulos', 'usuarios_perfis_modulos_operacoes', 'categorias',
    ];

    private string $root = '';
    private array $anterior = [];

    protected function setUp(): void
    {
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_ROOT . '/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php';

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req206-' . bin2hex(random_bytes(6));
        foreach (['resources/pt-br', 'resources/en', 'modulos', 'db/data'] as $pasta) {
            mkdir($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pasta), 0777, true);
        }

        global $SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE;
        $this->anterior = [$SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE, $GLOBALS['CLI_ARGS'] ?? []];
        $SYSTEM_PATH = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR;
        $RESOURCES_DIR = $this->root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $MODULES_DIR = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR;
        $DB_DATA_DIR = $this->root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        $GESTOR_DIR = $this->root . DIRECTORY_SEPARATOR;
        $LOG_FILE = 'req206-test';
        $GLOBALS['CLI_ARGS'] = ['project-path' => $this->root];
    }

    protected function tearDown(): void
    {
        global $SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE;
        [$SYSTEM_PATH, $RESOURCES_DIR, $MODULES_DIR, $DB_DATA_DIR, $GESTOR_DIR, $LOG_FILE, $GLOBALS['CLI_ARGS']] = $this->anterior;

        $itens = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($itens as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    private function coletar(): array
    {
        return coletarRecursos([], ['languages' => ['pt-br' => ['data' => []], 'en' => ['data' => []]]]);
    }

    public function testProjetoSemSementeNaoRecebeOsDadosDoCore(): void
    {
        $coletado = $this->coletar()['dynamicTablesData'];

        foreach (self::TABELAS_DO_CORE as $tabela) {
            self::assertArrayNotHasKey($tabela, $coletado, $tabela . ' veio das sementes do core');
        }
    }

    public function testProjetoComSementeCompilaSoAsProprias(): void
    {
        global $RESOURCES_DIR;
        file_put_contents($RESOURCES_DIR . 'user_profiles_modules.json', json_encode([
            ['perfil' => 'cloud-nano', 'modulo' => 'host-user-manager'],
            ['perfil' => 'cloud-pro', 'modulo' => 'host-user-manager'],
        ]));
        file_put_contents($RESOURCES_DIR . 'pt-br' . DIRECTORY_SEPARATOR . 'modules.json', json_encode([
            ['id' => 'host-user-manager', 'nome' => 'Hospedagem'],
        ]));

        $coletado = $this->coletar()['dynamicTablesData'];

        self::assertSame(
            ['cloud-nano', 'cloud-pro'],
            array_column($coletado['usuarios_perfis_modulos'] ?? [], 'perfil')
        );
        self::assertSame(['host-user-manager'], array_column($coletado['modulos'] ?? [], 'id'));
        self::assertSame(['pt-br'], array_column($coletado['modulos'], 'language'));
        // Tabela sem semente no projeto continua de fora, mesmo com outras semeadas.
        self::assertArrayNotHasKey('usuarios', $coletado);
        self::assertArrayNotHasKey('usuarios_perfis', $coletado);
    }

    public function testNoCoreAsOitoTabelasSeguemSendoCompiladas(): void
    {
        global $RESOURCES_DIR, $MODULES_DIR;
        $GLOBALS['CLI_ARGS'] = [];
        $RESOURCES_DIR = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $MODULES_DIR = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR;

        $coletado = $this->coletar()['dynamicTablesData'];

        foreach (self::TABELAS_DO_CORE as $tabela) {
            self::assertNotEmpty($coletado[$tabela] ?? [], $tabela);
        }
    }

    public function testEscritaDeJsonIgualNaoMexeNaDataDoArquivo(): void
    {
        $arquivo = $this->root . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'XData.json';
        self::assertTrue(jsonWrite($arquivo, [['id' => 'a']]));
        touch($arquivo, time() - 3600);
        clearstatcache();
        $antes = filemtime($arquivo);

        self::assertTrue(jsonWrite($arquivo, [['id' => 'a']]));
        clearstatcache();
        self::assertSame($antes, filemtime($arquivo));

        self::assertTrue(jsonWrite($arquivo, [['id' => 'b']]));
        clearstatcache();
        self::assertGreaterThan($antes, filemtime($arquivo));
    }

    public function testScriptsCopiamOsDadosPorConteudo(): void
    {
        $projeto = (string)file_get_contents(CONN2FLOW_ROOT . '/ai-workspace/en/scripts/projects/synchronize-project.sh');
        $core = (string)file_get_contents(CONN2FLOW_ROOT . '/ai-workspace/en/scripts/projects/sync-core-to-project.sh');

        self::assertMatchesRegularExpression('/rsync -avc --relative "\$\{PT_RSYNC_OPTS\[@\]\}" "\$ORIGEM\/\.\/db\/data\/" "\$DESTINO\/"/', $projeto);
        self::assertStringContainsString('"$CORE_SOURCE/./db/data/"', $core);
        // A cópia por conteúdo vem depois da cópia geral, nos dois scripts.
        self::assertGreaterThan(strpos($projeto, 'run_project_rsync "$ORIGEM" "$DESTINO"'), strpos($projeto, 'PROJECT_DATA_CMD=('));
        self::assertGreaterThan(strpos($core, 'RUNTIME_CONTRACT_CMD=('), strpos($core, 'CORE_DATA_CMD=('));
    }
}
