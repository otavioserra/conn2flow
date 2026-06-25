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

        // Arquivo físico em layout PLANO (<base>/<lang>/<tabela>/<id>.<ext>), sem BOM.
        $esperado = $base . DIRECTORY_SEPARATOR . 'pt-br' . DIRECTORY_SEPARATOR . 'widgets_demo' . DIRECTORY_SEPARATOR . 'hero.html';
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

        // Arquivo físico: subpasta = nome da tabela.
        $this->assertFileExists($gestor . '/resources/pt-br/widgets_demo/hero.html');
        $this->assertSame('<section>Conteúdo</section>', file_get_contents($gestor . '/resources/pt-br/widgets_demo/hero.html'));
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
        $this->assertFileExists($gestor . '/resources/pt-br/widgets_inline/box.css');
        $this->assertSame('.box{color:red}', file_get_contents($gestor . '/resources/pt-br/widgets_inline/box.css'));
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
        file_put_contents($gestor . '/resources/tables_config.json', json_encode($tablesConfig));
        file_put_contents($src . '/MenusData.json', json_encode([
            ['id_menus' => 1, 'id' => 'main', 'language' => 'pt-br', 'module' => 'menus', 'html' => '<nav>Main</nav>', 'status' => 'A'],
        ]));

        $cfgs = rdr_coletar_configs($gestor);
        $this->assertSame('module', $cfgs['menus']['scope']);
        $this->assertSame('menus', $cfgs['menus']['modulo']);
        $this->assertSame($gestor . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'menus' . DIRECTORY_SEPARATOR . 'resources', $cfgs['menus']['base_dir']);

        rdr_processar($src, $gestor);
        $this->assertFileExists($gestor . '/modulos/menus/resources/pt-br/menus/main.html');
        $this->assertSame('<nav>Main</nav>', file_get_contents($gestor . '/modulos/menus/resources/pt-br/menus/main.html'));
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

        file_put_contents($gestor . '/resources/tables_config.json', json_encode(['tabelas' => ['arquivos' => [
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
        $this->assertSame('ArquivosData.json', $manifest['tabelas']['arquivos']['data_file']);
        $this->assertArrayNotHasKey('generated_at', $manifest);
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
}
