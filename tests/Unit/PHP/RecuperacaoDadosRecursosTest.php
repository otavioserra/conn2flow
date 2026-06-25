<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Cobertura do descompilador de recursos (Pull System / req-058 / BATCH-058):
 * gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php.
 *
 * Exercita, sem banco e com fixtures em diretório temporário, a engenharia reversa de
 * <PascalCase>Data.json brutos de volta para arquivos físicos + metadados:
 *  - extração de campos field_types "file:<ext>" para arquivo (layout plano, BOM removido);
 *  - decodificação de campos field_types "json" (string -> array);
 *  - saneamento de colunas de build/banco (versao/checksum/user_modified/project/PK/idioma/status);
 *  - escrita de metadados externos (metadata_file) e inline (resources->idioma->tabela);
 *  - resolução de caminhos espelhando o compilador (global vs módulo, com/sem resources_dir).
 */
final class RecuperacaoDadosRecursosTest extends TestCase
{
    private string $tmpDir = '';

    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        $GLOBALS['RDR_SILENT'] = true;
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR
            . 'arquitetura' . DIRECTORY_SEPARATOR . 'recuperacao-dados-recursos.php';
    }

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_rdr_' . uniqid();
        @mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir && is_dir($this->tmpDir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
            @rmdir($this->tmpDir);
        }
    }

    /** Config sintética de uma tabela global com metadados externos. */
    private function cfgGlobalExterno(string $baseDir): array
    {
        return [
            'nome' => 'widgets_demo',
            'id' => 'id',
            'id_numerico' => 'id_widgets_demo',
            'strategy' => 'natural_key',
            'natural_key_columns' => ['language', 'id'],
            'sync_resources' => true,
            'resources_dir' => null,
            'metadata_file' => 'widgets_demo.json',
            'field_types' => ['html' => 'file:html', 'fields_schema' => 'json'],
            'scope' => 'global',
            'modulo' => null,
            'base_dir' => $baseDir,
            'source_file' => $baseDir . DIRECTORY_SEPARATOR . 'tables_config.json',
        ];
    }

    public function testDecompilaRegistroExtraiArquivoDecodificaJsonESaneiaColunas(): void
    {
        $base = $this->tmpDir . DIRECTORY_SEPARATOR . 'resources';
        $cfg = $this->cfgGlobalExterno($base);

        $rec = [
            'id_widgets_demo' => 7,
            'id' => 'hero',
            'name' => 'Hero',
            'language' => 'pt-br',
            'html' => "\xEF\xBB\xBF<div>Olá</div>",
            'fields_schema' => '{"fields":[{"id":"titulo","type":"text"}]}',
            'status' => 'A',
            'versao' => 4,
            'checksum' => 'deadbeef',
            'user_modified' => 1,
            'project' => 'transformamp',
        ];

        $res = rdr_descompilar_registro($rec, $cfg, 'pt-br');

        // Arquivo físico em subpasta por ID (<base>/<lang>/<tabela>/<id>/<id>.<ext>), sem BOM.
        $esperado = $base . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'widgets_demo' . DIRECTORY_SEPARATOR . 'hero' . DIRECTORY_SEPARATOR . 'hero.html';
        $this->assertArrayHasKey($esperado, $res['files']);
        $this->assertSame('<div>Olá</div>', $res['files'][$esperado]);

        $meta = $res['meta'];
        // Campo file removido do metadado.
        $this->assertArrayNotHasKey('html', $meta);
        // Campo json decodificado para array.
        $this->assertIsArray($meta['fields_schema']);
        $this->assertSame('titulo', $meta['fields_schema']['fields'][0]['id']);
        // Colunas de build/banco saneadas.
        foreach (['id_widgets_demo', 'language', 'versao', 'checksum', 'user_modified', 'project', 'status'] as $col) {
            $this->assertArrayNotHasKey($col, $meta, "coluna '$col' deveria ter sido removida");
        }
        // Campos de conteúdo preservados.
        $this->assertSame('hero', $meta['id']);
        $this->assertSame('Hero', $meta['name']);
    }

    public function testSaneamentoPreservaStatusNaoDefaultERemoveModuloDono(): void
    {
        $cfgModulo = [
            'nome' => 'menus',
            'id' => 'id',
            'id_numerico' => 'id_menus',
            'strategy' => 'natural_key',
            'natural_key_columns' => ['language', 'module', 'id'],
            'sync_resources' => true,
            'resources_dir' => null,
            'metadata_file' => 'menus.json',
            'field_types' => [],
            'scope' => 'module',
            'modulo' => 'menus',
            'base_dir' => $this->tmpDir,
            'source_file' => $this->tmpDir . DIRECTORY_SEPARATOR . 'menus.json',
        ];

        // status != 'A' deve ser preservado; module == módulo dono deve sair.
        $meta1 = rdr_sanear(['id' => 'main', 'language' => 'pt-br', 'module' => 'menus', 'status' => 'I'], $cfgModulo, 'pt-br');
        $this->assertArrayNotHasKey('module', $meta1);
        $this->assertSame('I', $meta1['status']);
        $this->assertArrayNotHasKey('language', $meta1);

        // module != módulo dono deve ser preservado.
        $meta2 = rdr_sanear(['id' => 'main', 'language' => 'pt-br', 'module' => 'outro'], $cfgModulo, 'pt-br');
        $this->assertSame('outro', $meta2['module']);
    }

    public function testProcessaEscreveMetadadosExternosEArquivoFisico(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);

        $tablesConfig = ['tabelas' => ['widgets_demo' => [
            'nome' => 'widgets_demo', 'id' => 'id', 'id_numerico' => 'id_widgets_demo',
            'config' => [
                'strategy' => 'natural_key', 'natural_key_columns' => ['language', 'id'],
                'sync_resources' => true, 'metadata_file' => 'widgets_demo.json',
                'field_types' => ['html' => 'file:html', 'fields_schema' => 'json'],
            ],
        ]]];
        file_put_contents($gestor . '/resources/tables_config.json', json_encode($tablesConfig));

        file_put_contents($src . '/WidgetsDemoData.json', json_encode([
            ['id_widgets_demo' => 5, 'id' => 'hero', 'name' => 'Hero', 'language' => 'pt-br',
             'html' => '<section>Conteúdo</section>', 'fields_schema' => '{"fields":[]}',
             'status' => 'A', 'versao' => 1, 'checksum' => 'x', 'user_modified' => 0],
        ]));

        $stats = rdr_processar($src, $gestor);

        $this->assertArrayHasKey('widgets_demo', $stats['tabelas']);
        $this->assertSame(1, $stats['tabelas']['widgets_demo']['registros']);

        // Metadado externo: global SEM resources_dir => <lang>/<tabela>.json (não dentro da subpasta).
        $metaPath = $gestor . '/resources/pt-br/widgets_demo.json';
        $this->assertFileExists($metaPath);
        $lista = json_decode(file_get_contents($metaPath), true);
        $this->assertCount(1, $lista);
        $this->assertSame('hero', $lista[0]['id']);
        $this->assertArrayNotHasKey('html', $lista[0]);
        $this->assertIsArray($lista[0]['fields_schema']);
        $this->assertArrayNotHasKey('versao', $lista[0]);

        // Arquivo físico: subpasta da tabela + subpasta do recurso.
        $this->assertFileExists($gestor . '/resources/pt-br/widgets_demo/hero/hero.html');
        $this->assertSame('<section>Conteúdo</section>', file_get_contents($gestor . '/resources/pt-br/widgets_demo/hero/hero.html'));
    }

    public function testProcessaEscreveMetadadosInlineNoJsonRaiz(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);

        // Sem metadata_file => inline em resources->lang->tabela do próprio tables_config.json.
        $tablesConfig = ['tabelas' => ['widgets_inline' => [
            'nome' => 'widgets_inline', 'id' => 'id', 'id_numerico' => 'id_widgets_inline',
            'config' => [
                'strategy' => 'natural_key', 'natural_key_columns' => ['language', 'id'],
                'sync_resources' => true, 'field_types' => ['css' => 'file:css'],
            ],
        ]]];
        file_put_contents($gestor . '/resources/tables_config.json', json_encode($tablesConfig));

        file_put_contents($src . '/WidgetsInlineData.json', json_encode([
            ['id_widgets_inline' => 2, 'id' => 'box', 'language' => 'pt-br', 'css' => '.box{color:red}',
             'status' => 'A', 'versao' => 1, 'checksum' => 'y', 'user_modified' => 0],
        ]));

        rdr_processar($src, $gestor);

        // O JSON raiz foi atualizado preservando 'tabelas' e adicionando 'resources'.
        $root = json_decode(file_get_contents($gestor . '/resources/tables_config.json'), true);
        $this->assertArrayHasKey('tabelas', $root, 'bloco tabelas deve ser preservado');
        $this->assertArrayHasKey('widgets_inline', $root['resources']['pt-br']);
        $inline = $root['resources']['pt-br']['widgets_inline'];
        $this->assertSame('box', $inline[0]['id']);
        $this->assertArrayNotHasKey('css', $inline[0]);
        $this->assertArrayNotHasKey('id_widgets_inline', $inline[0]);

        // Arquivo físico do css.
        $this->assertFileExists($gestor . '/resources/pt-br/widgets_inline/box/box.css');
        $this->assertSame('.box{color:red}', file_get_contents($gestor . '/resources/pt-br/widgets_inline/box/box.css'));
    }

    public function testResolucaoDeCaminhosEspelhaCompilador(): void
    {
        // Global sem resources_dir: metadado em <lang>/<metadata_file>; arquivos em <lang>/<tabela>/.
        $cfgGlobal = $this->cfgGlobalExterno('/base');
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'widgets_demo.json',
            rdr_metadata_path($cfgGlobal, 'pt-br'));
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'widgets_demo',
            rdr_files_dir($cfgGlobal, 'pt-br'));

        // Global COM resources_dir: metadado dentro da subpasta.
        $cfgGlobalRd = $cfgGlobal;
        $cfgGlobalRd['resources_dir'] = 'widgets';
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . 'widgets_demo.json',
            rdr_metadata_path($cfgGlobalRd, 'pt-br'));

        // Módulo: metadado sempre dentro da subpasta (resources_dir|tabela).
        $cfgMod = $cfgGlobal;
        $cfgMod['scope'] = 'module';
        $cfgMod['modulo'] = 'demo';
        $this->assertSame('/base' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'widgets_demo' . DIRECTORY_SEPARATOR . 'widgets_demo.json',
            rdr_metadata_path($cfgMod, 'en'));
    }

    public function testDataFileToTableEIgnoraTabelaSemConfig(): void
    {
        $this->assertSame('widgets_demo', rdr_data_file_to_table('WidgetsDemoData.json'));
        $this->assertSame('publisher_highlights', rdr_data_file_to_table('PublisherHighlightsData.json'));
        $this->assertSame('menus', rdr_data_file_to_table('MenusData.json'));

        // rdr_processar ignora Data.json sem configuração sync_resources registrada.
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);
        file_put_contents($gestor . '/resources/tables_config.json', json_encode(['tabelas' => []]));
        file_put_contents($src . '/PaginasData.json', json_encode([['id' => 'home', 'language' => 'pt-br']]));

        $stats = rdr_processar($src, $gestor);
        $this->assertContains('paginas', $stats['ignoradas']);
        $this->assertArrayNotHasKey('paginas', $stats['tabelas']);
    }

    public function testProcessaRegistroSemIdiomaUsaFallbackPtBr(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);

        $tablesConfig = ['tabelas' => ['arquivos' => [
            'nome' => 'arquivos',
            'id' => 'id',
            'id_numerico' => 'id_arquivos',
            'config' => [
                'strategy' => 'natural_key',
                'natural_key_columns' => ['id'],
                'sync_resources' => true,
                'metadata_file' => 'arquivos.json',
                'field_types' => [],
            ],
        ]]];
        file_put_contents($gestor . '/resources/project_tables_config.json', json_encode($tablesConfig));
        file_put_contents($src . '/ArquivosData.json', json_encode([
            ['id_arquivos' => 9, 'id' => 'logo', 'nome' => 'Logo', 'status' => 'A'],
        ]));

        $stats = rdr_processar($src, $gestor);

        $this->assertArrayHasKey('arquivos', $stats['tabelas']);
        $this->assertSame(['pt-br'], $stats['tabelas']['arquivos']['idiomas']);
        $metaPath = $gestor . '/resources/pt-br/arquivos.json';
        $this->assertFileExists($metaPath);
        $lista = json_decode(file_get_contents($metaPath), true);
        $this->assertSame('logo', $lista[0]['id']);
        $this->assertArrayNotHasKey('id_arquivos', $lista[0]);
    }

    public function testOverrideScopeModuloEmTablesConfigUsaResourcesDoModulo(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . '/resources', 0775, true);
        @mkdir($gestor . '/modulos/menus/resources', 0775, true);
        @mkdir($src, 0775, true);

        $tablesConfig = ['tabelas' => ['menus' => [
            'nome' => 'menus',
            'id' => 'id',
            'id_numerico' => 'id_menus',
            'config' => [
                'scope' => 'module',
                'modulo' => 'menus',
                'strategy' => 'natural_key',
                'natural_key_columns' => ['language', 'module', 'id'],
                'sync_resources' => true,
                'metadata_file' => 'menus.json',
                'field_types' => ['html' => 'file:html'],
            ],
        ]]];
        file_put_contents($gestor . '/resources/project_tables_config.json', json_encode($tablesConfig));
        file_put_contents($src . '/MenusData.json', json_encode([
            ['id_menus' => 1, 'id' => 'main', 'language' => 'pt-br', 'module' => 'menus', 'html' => '<nav>Main</nav>', 'status' => 'A'],
        ]));

        $cfgs = rdr_coletar_configs($gestor);
        $this->assertSame('module', $cfgs['menus']['scope']);
        $this->assertSame('menus', $cfgs['menus']['modulo']);
        $this->assertSame($gestor . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'menus' . DIRECTORY_SEPARATOR . 'resources', $cfgs['menus']['base_dir']);

        rdr_processar($src, $gestor);
        $this->assertFileExists($gestor . '/modulos/menus/resources/pt-br/menus/main/main.html');
        $this->assertSame('<nav>Main</nav>', file_get_contents($gestor . '/modulos/menus/resources/pt-br/menus/main/main.html'));
        $meta = json_decode(file_get_contents($gestor . '/modulos/menus/resources/pt-br/menus/menus.json'), true);
        $this->assertSame('main', $meta[0]['id']);
        $this->assertArrayNotHasKey('module', $meta[0]);
    }

    #[RunInSeparateProcess]
    public function testCompiladorNormalizaOverrideScopeModuloEmConfig(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR
            . 'arquitetura' . DIRECTORY_SEPARATOR . 'atualizacao-dados-recursos.php';

        $norm = normalizarConfigTabela([
            'nome' => 'menus',
            'id' => 'id',
            'id_numerico' => 'id_menus',
            'config' => [
                'scope' => 'module',
                'modulo' => 'menus',
                'strategy' => 'natural_key',
                'natural_key_columns' => ['language', 'module', 'id'],
                'sync_resources' => true,
            ],
        ]);

        $this->assertSame('module', $norm[0]['scope_override']);
        $this->assertSame('menus', $norm[0]['modulo_override']);
        $this->assertSame('menus', $norm[0]['nome']);
    }

    #[RunInSeparateProcess]
    public function testCompiladorGeraProjectSchemaMetadataNaRaizDoGestor(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        @mkdir($gestor . '/resources', 0775, true);
        @mkdir($gestor . '/db/data', 0775, true);
        @mkdir($gestor . '/logs/arquitetura', 0775, true);

        file_put_contents($gestor . '/resources/project_tables_config.json', json_encode(['tabelas' => ['arquivos' => [
            'nome' => 'arquivos',
            'id' => 'id',
            'id_numerico' => 'id_arquivos',
            'config' => [
                'strategy' => 'natural_key',
                'natural_key_columns' => ['id'],
                'sync_resources' => false,
            ],
        ]]]));

        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        $argv = ['atualizacao-dados-recursos.php', '--project-path=' . $gestor];
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR
            . 'arquitetura' . DIRECTORY_SEPARATOR . 'atualizacao-dados-recursos.php';

        gerarSchemaMetadata();

        $manifestPath = $gestor . '/project-schema-metadata.json';
        $this->assertFileExists($manifestPath);
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertArrayHasKey('tabelas', $manifest);
        $this->assertArrayHasKey('arquivos', $manifest['tabelas']);
        $this->assertArrayNotHasKey('variaveis', $manifest['tabelas']);
        $this->assertSame('ArquivosData.json', $manifest['tabelas']['arquivos']['data_file']);
        $this->assertArrayNotHasKey('generated_at', $manifest);
    }

    #[RunInSeparateProcess]
    public function testCompiladorLeArquivoFisicoEmSubpastaPorId(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR
            . 'arquitetura' . DIRECTORY_SEPARATOR . 'atualizacao-dados-recursos.php';

        $base = $this->tmpDir . DIRECTORY_SEPARATOR . 'resources';
        @mkdir($base . '/pt-br/widgets_demo/hero', 0775, true);
        file_put_contents($base . '/pt-br/widgets_demo/hero/hero.html', '<section>Hero</section>');

        $cfg = [
            'nome' => 'widgets_demo',
            'resources_dir' => null,
            'field_types' => ['html' => 'file:html'],
            'base_dir' => $base,
        ];

        $registro = processarRegistroDinamico(['id' => 'hero'], $cfg, 'pt-br');

        $this->assertSame('<section>Hero</section>', $registro['html']);
    }

    public function testCliServidorLeProjectSchemaMetadataSanitizandoTabelas(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'recuperacoes' . DIRECTORY_SEPARATOR
            . 'recuperacao-banco-de-dados.php';

        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        @mkdir($gestor, 0775, true);
        file_put_contents($gestor . '/project-schema-metadata.json', json_encode([
            'tabelas' => [
                'menus' => [],
                'Publisher_Highlights' => [],
                'bad-name!' => [],
            ],
        ]));

        $this->assertSame(
            ['menus', 'publisher_highlights', 'badname'],
            recuperacao_project_schema_metadata_tables($gestor)
        );
    }

    public function testContentsPulaQuandoMd5IgualSemAlterarTimestamp(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . '/contents/uploads', 0775, true);
        @mkdir($src . '/contents/uploads', 0775, true);
        file_put_contents($gestor . '/contents/uploads/logo.txt', 'same');
        file_put_contents($src . '/contents/uploads/logo.txt', 'same');
        touch($gestor . '/contents/uploads/logo.txt', 1000);
        touch($src . '/contents/uploads/logo.txt', 2000);

        $stats = rdr_sincronizar_contents($src, $gestor);

        $this->assertSame(0, $stats['copiados']);
        $this->assertSame(1, $stats['pulados']);
        $this->assertSame([], $stats['conflitos']);
        $this->assertSame(1000, filemtime($gestor . '/contents/uploads/logo.txt'));
    }

    public function testContentsSobrescreveQuandoRemotoMaisNovoEPreservaTimestamp(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . '/contents/uploads', 0775, true);
        @mkdir($src . '/contents/uploads', 0775, true);
        file_put_contents($gestor . '/contents/uploads/logo.txt', 'local');
        file_put_contents($src . '/contents/uploads/logo.txt', 'remote');
        touch($gestor . '/contents/uploads/logo.txt', 1000);
        touch($src . '/contents/uploads/logo.txt', 2000);

        $stats = rdr_sincronizar_contents($src, $gestor);

        $this->assertSame(1, $stats['copiados']);
        $this->assertSame('remote', file_get_contents($gestor . '/contents/uploads/logo.txt'));
        $this->assertSame(2000, filemtime($gestor . '/contents/uploads/logo.txt'));
    }

    public function testContentsConflitoQuandoLocalMaisNovoOuMesmoTimestamp(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . '/contents/uploads', 0775, true);
        @mkdir($src . '/contents/uploads', 0775, true);
        file_put_contents($gestor . '/contents/uploads/logo.txt', 'local');
        file_put_contents($src . '/contents/uploads/logo.txt', 'remote');
        touch($gestor . '/contents/uploads/logo.txt', 2000);
        touch($src . '/contents/uploads/logo.txt', 1000);

        $stats = rdr_sincronizar_contents($src, $gestor);

        $this->assertSame(0, $stats['copiados']);
        $this->assertSame(1, $stats['pulados']);
        $this->assertSame(['uploads/logo.txt'], $stats['conflitos']);
        $this->assertSame('local', file_get_contents($gestor . '/contents/uploads/logo.txt'));
    }

    /**
     * Campo file:<ext> nulo ou vazio (registro que usa o template padrão sem customização) não
     * deve gerar arquivo em branco e deve emitir o log explicativo RDR_DEBUG_FILE_EMPTY (req-063).
     */
    public function testCampoFileNuloOuVazioEmiteLogEnaoCriaArquivo(): void
    {
        $base = $this->tmpDir . DIRECTORY_SEPARATOR . 'resources';
        $cfg = $this->cfgGlobalExterno($base);
        $cfg['field_types'] = ['html' => 'file:html', 'css' => 'file:css'];

        // html como string vazia e css nulo: nenhum dos dois deve virar arquivo.
        $rec = ['id' => 'plain', 'name' => 'Plain', 'language' => 'pt-br', 'html' => '', 'css' => null];

        $anterior = $GLOBALS['RDR_SILENT'] ?? null;
        $GLOBALS['RDR_SILENT'] = false;
        ob_start();
        try {
            $res = rdr_descompilar_registro($rec, $cfg, 'pt-br');
        } finally {
            $saida = ob_get_clean();
            $GLOBALS['RDR_SILENT'] = $anterior;
        }

        // Nenhum arquivo físico acumulado (sem arquivo em branco).
        $this->assertSame([], $res['files']);
        // As chaves de campo file saem do metadado mesmo quando vazias.
        $this->assertArrayNotHasKey('html', $res['meta']);
        $this->assertArrayNotHasKey('css', $res['meta']);
        // Log explicativo emitido por campo omitido.
        $this->assertStringContainsString('RDR_DEBUG_FILE_EMPTY tabela=widgets_demo id=plain campo=html', $saida);
        $this->assertStringContainsString('RDR_DEBUG_FILE_EMPTY tabela=widgets_demo id=plain campo=css', $saida);
    }

    /**
     * Cenário misto realista (espelha o relatório do req-063): um registro customizado gera
     * arquivos físicos; um registro que usa o template padrão (html/css nulos) é contabilizado
     * mas não gera arquivos, emitindo apenas o log RDR_DEBUG_FILE_EMPTY.
     */
    public function testProcessaMisturaCustomizadoEPadraoSemArquivoEmBranco(): void
    {
        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);

        $tablesConfig = ['tabelas' => ['galleries' => [
            'nome' => 'galleries', 'id' => 'id', 'id_numerico' => 'id_galleries',
            'config' => [
                'strategy' => 'natural_key', 'natural_key_columns' => ['language', 'id'],
                'sync_resources' => true, 'metadata_file' => 'galleries.json',
                'field_types' => ['html' => 'file:html', 'css' => 'file:css', 'fields_schema' => 'json'],
            ],
        ]]];
        file_put_contents($gestor . '/resources/project_tables_config.json', json_encode($tablesConfig));

        file_put_contents($src . '/GalleriesData.json', json_encode([
            // Customizado: html e css preenchidos -> 2 arquivos.
            ['id_galleries' => 1, 'id' => 'galeria-teste', 'name' => 'Galeria Teste', 'language' => 'pt-br',
             'html' => '<section>custom</section>', 'css' => '.x{color:red}', 'fields_schema' => '{"template_id":"galleries-slider"}',
             'status' => 'A', 'versao' => 8, 'checksum' => 'a', 'user_modified' => 0],
            // Template padrão: html/css nulos -> nenhum arquivo, só log.
            ['id_galleries' => 2, 'id' => 'galeria-home', 'name' => 'Galeria Home', 'language' => 'pt-br',
             'html' => null, 'css' => null, 'fields_schema' => '{"template_id":"galeria-home"}',
             'status' => 'A', 'versao' => 8, 'checksum' => 'b', 'user_modified' => 0],
        ]));

        $anterior = $GLOBALS['RDR_SILENT'] ?? null;
        $GLOBALS['RDR_SILENT'] = false;
        ob_start();
        try {
            $stats = rdr_processar($src, $gestor);
        } finally {
            $saida = ob_get_clean();
            $GLOBALS['RDR_SILENT'] = $anterior;
        }

        // 2 registros processados, mas só 2 arquivos (do registro customizado).
        $this->assertArrayHasKey('galleries', $stats['tabelas']);
        $this->assertSame(2, $stats['tabelas']['galleries']['registros']);
        $this->assertSame(2, $stats['tabelas']['galleries']['arquivos']);
        $this->assertSame(2, $stats['arquivos']);

        // Registro customizado gera arquivos no layout por ID.
        $this->assertFileExists($gestor . '/resources/pt-br/galleries/galeria-teste/galeria-teste.html');
        $this->assertFileExists($gestor . '/resources/pt-br/galleries/galeria-teste/galeria-teste.css');
        $this->assertSame('<section>custom</section>', file_get_contents($gestor . '/resources/pt-br/galleries/galeria-teste/galeria-teste.html'));

        // Registro de template padrão não gera arquivo em branco.
        $this->assertFileDoesNotExist($gestor . '/resources/pt-br/galleries/galeria-home/galeria-home.html');
        $this->assertFileDoesNotExist($gestor . '/resources/pt-br/galleries/galeria-home/galeria-home.css');

        // Metadado externo escrito para ambos, com fields_schema decodificado.
        $lista = json_decode(file_get_contents($gestor . '/resources/pt-br/galleries.json'), true);
        $this->assertCount(2, $lista);
        $this->assertIsArray($lista[0]['fields_schema']);

        // Log emitido só para o registro de template padrão.
        $this->assertStringContainsString('RDR_DEBUG_FILE_EMPTY tabela=galleries id=galeria-home campo=html', $saida);
        $this->assertStringContainsString('RDR_DEBUG_FILE_EMPTY tabela=galleries id=galeria-home campo=css', $saida);
        $this->assertStringNotContainsString('id=galeria-teste', $saida);
    }

    /**
     * Tabela com coluna de ID customizada (ex.: publisher_pages -> "id": "page_id"): o descompilador
     * deve resolver o $id a partir da coluna configurada, gerar o arquivo na subpasta correta e
     * preservar a coluna lógica no metadado, removendo apenas a PK auto-increment (req-065).
     */
    public function testDescompilaResolveColunaIdCustomizada(): void
    {
        $base = $this->tmpDir . DIRECTORY_SEPARATOR . 'resources';
        $cfg = $this->cfgGlobalExterno($base);
        $cfg['nome'] = 'publisher_pages';
        $cfg['id'] = 'page_id';
        $cfg['id_numerico'] = 'id_publisher_pages';
        $cfg['field_types'] = ['html' => 'file:html'];

        // Dump bruto: a chave lógica é page_id (não existe coluna 'id').
        $rec = ['id_publisher_pages' => 1, 'page_id' => 'sobre', 'name' => 'Sobre',
                'language' => 'pt-br', 'html' => '<p>Sobre</p>', 'status' => 'A'];

        $res = rdr_descompilar_registro($rec, $cfg, 'pt-br');

        // Arquivo físico na subpasta baseada no ID customizado.
        $esperado = $base . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'publisher_pages'
            . DIRECTORY_SEPARATOR . 'sobre' . DIRECTORY_SEPARATOR . 'sobre.html';
        $this->assertArrayHasKey($esperado, $res['files']);
        $this->assertSame('<p>Sobre</p>', $res['files'][$esperado]);

        // page_id preservado; PK auto-increment e campo file removidos.
        $this->assertSame('sobre', $res['meta']['page_id']);
        $this->assertArrayNotHasKey('id_publisher_pages', $res['meta']);
        $this->assertArrayNotHasKey('html', $res['meta']);
    }

    /**
     * Round-trip com coluna de ID customizada: descompila um dump com page_id para arquivos físicos +
     * metadado e, em seguida, recompila lendo o arquivo físico de volta, sem perda de atributos (req-065).
     */
    #[RunInSeparateProcess]
    public function testRoundTripColunaIdCustomizada(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR
            . 'arquitetura' . DIRECTORY_SEPARATOR . 'atualizacao-dados-recursos.php';

        $gestor = $this->tmpDir . DIRECTORY_SEPARATOR . 'gestor';
        $src = $this->tmpDir . DIRECTORY_SEPARATOR . 'src';
        @mkdir($gestor . DIRECTORY_SEPARATOR . 'resources', 0775, true);
        @mkdir($src, 0775, true);

        // Tabela de projeto com coluna de ID customizada (page_id) e PK id_publisher_pages.
        $tablesConfig = ['tabelas' => ['publisher_pages' => [
            'nome' => 'publisher_pages',
            'id' => 'page_id',
            'id_numerico' => 'id_publisher_pages',
            'config' => [
                'strategy' => 'natural_key',
                'natural_key_columns' => ['language', 'page_id'],
                'sync_resources' => true,
                'metadata_file' => 'publisher_pages.json',
                'field_types' => ['html' => 'file:html', 'fields_values' => 'json'],
            ],
        ]]];
        file_put_contents($gestor . '/resources/project_tables_config.json', json_encode($tablesConfig));

        file_put_contents($src . '/PublisherPagesData.json', json_encode([
            ['id_publisher_pages' => 1, 'page_id' => 'home', 'language' => 'pt-br',
             'html' => '<div>Home</div>', 'fields_values' => '[{"id":"titulo","value":"Início"}]',
             'status' => 'A', 'versao' => 3, 'checksum' => 'k', 'user_modified' => 0],
        ]));

        // 1) DESCOMPILAÇÃO
        $stats = rdr_processar($src, $gestor);
        $this->assertArrayHasKey('publisher_pages', $stats['tabelas']);
        $this->assertSame(1, $stats['tabelas']['publisher_pages']['registros']);
        $this->assertSame(1, $stats['tabelas']['publisher_pages']['arquivos']);

        $htmlPath = $gestor . '/resources/pt-br/publisher_pages/home/home.html';
        $this->assertFileExists($htmlPath);
        $this->assertSame('<div>Home</div>', file_get_contents($htmlPath));

        $meta = json_decode(file_get_contents($gestor . '/resources/pt-br/publisher_pages.json'), true);
        $this->assertSame('home', $meta[0]['page_id']);
        $this->assertArrayNotHasKey('id_publisher_pages', $meta[0]);
        $this->assertArrayNotHasKey('html', $meta[0]);
        $this->assertIsArray($meta[0]['fields_values']);

        // 2) COMPILAÇÃO (round-trip): recompila lendo o arquivo físico gerado.
        $cfgComp = [
            'nome' => 'publisher_pages',
            'id' => 'page_id',
            'resources_dir' => null,
            'field_types' => ['html' => 'file:html'],
            'base_dir' => $gestor . DIRECTORY_SEPARATOR . 'resources',
            'natural_key_columns' => ['language', 'page_id'],
            'scope' => 'global',
        ];
        $registro = processarRegistroDinamico(['page_id' => 'home'], $cfgComp, 'pt-br');
        $this->assertSame('home', $registro['page_id']);
        $this->assertSame('<div>Home</div>', $registro['html']);
    }
}
