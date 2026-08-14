<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regressão do mecanismo "forcar_atualizacao" (req-056 / BATCH-056) em sincronizarTabela().
 *
 * Cobre, com PDO SQLite em memória e um contrato schema-metadata.json temporário:
 *  - Registro forçado por chave natural: payload completo sobrescreve, user_modified reseta para 0,
 *    project é preservado.
 *  - Registro forçado por PK: bypass da proteção de project.
 *  - Registro NÃO forçado com user_modified=1: campo preservado.
 *  - Registro NÃO forçado protegido por project: permanece intacto.
 *
 * Observação: o updater usa "SHOW COLUMNS"/"SHOW VARIABLES" (MySQL); no SQLite essas queries
 * lançam exceção tratada (allowedCols=null => sem filtragem de colunas; max_allowed_packet usa
 * fallback). As colunas do JSON casam exatamente com a tabela, então o caminho é exercido sem ruído.
 */
final class ForcarAtualizacaoTest extends TestCase
{
    private static string $tmpDir = '';

    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR
            . 'atualizacoes-banco-de-dados.php';

        self::$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_forcar_' . uniqid();
        @mkdir(self::$tmpDir, 0775, true);

        // Aponta o contrato (schema-metadata.json) para o diretório temporário ANTES da 1ª
        // chamada a schemaMetadata() — que faz cache estático do conteúdo lido.
        $GLOBALS['DB_DATA_DIR'] = self::$tmpDir . DIRECTORY_SEPARATOR;
        $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tmpDir && is_dir(self::$tmpDir)) {
            foreach (glob(self::$tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) { @unlink($f); }
            @rmdir(self::$tmpDir);
        }
    }

    public function testForcarAtualizacaoBypassaProtecoesEResetaUserModified(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE widgets_demo (
            id_widgets_demo INTEGER PRIMARY KEY AUTOINCREMENT,
            id TEXT, language TEXT, html TEXT, css_precompiled TEXT, status TEXT,
            user_modified INTEGER DEFAULT 0, project TEXT
        )');
        $pdo->exec("INSERT INTO widgets_demo (id,language,html,css_precompiled,status,user_modified,project) VALUES
            ('hero','pt-br','USER EDITED','OLD PRE A','A',1,NULL),
            ('banner','pt-br','USER EDITED B','OLD PRE B','A',1,NULL),
            ('card','pt-br','OLD','OLD PRE C','A',0,'proj1'),
            ('menu','pt-br','OLD D','OLD PRE D','A',0,'proj1')");
        $pkMenu = (int) $pdo->query("SELECT id_widgets_demo FROM widgets_demo WHERE id='menu'")->fetchColumn();

        file_put_contents(self::$tmpDir . DIRECTORY_SEPARATOR . 'schema-metadata.json', (string) json_encode([
            'generated_at' => date('c'),
            'tables' => ['widgets_demo' => [
                'nome' => 'widgets_demo', 'id' => 'id', 'id_numerico' => 'id_widgets_demo',
                'data_file' => 'WidgetsDemoData.json', 'strategy' => 'natural_key',
                'natural_key_columns' => ['language', 'id'],
                // Inclui css_precompiled de propósito para simular contrato antigo em produção.
                'preserve_on_user_modified' => ['html', 'css_precompiled'], 'insert_only' => false, 'source' => 'test',
            ]],
            'deletar' => [],
            'forcar_atualizacao' => ['widgets_demo' => [
                ['natural_key' => ['language' => 'pt-br', 'id' => 'hero']],
                ['pk' => $pkMenu],
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $registros = [
            ['id' => 'hero', 'language' => 'pt-br', 'html' => 'CODE HTML', 'css_precompiled' => 'NEW PRE A', 'status' => 'A'],
            ['id' => 'banner', 'language' => 'pt-br', 'html' => 'CODE HTML B', 'css_precompiled' => 'NEW PRE B', 'status' => 'A'],
            ['id' => 'card', 'language' => 'pt-br', 'html' => 'CODE HTML C', 'css_precompiled' => 'NEW PRE C', 'status' => 'A'],
            ['id' => 'menu', 'language' => 'pt-br', 'html' => 'CODE HTML D', 'css_precompiled' => 'NEW PRE D', 'status' => 'A'],
        ];

        ob_start();
        $res = sincronizarTabela($pdo, 'widgets_demo', $registros, false, false);
        ob_end_clean();

        $rows = [];
        foreach ($pdo->query('SELECT id,html,css_precompiled,user_modified,project FROM widgets_demo')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[$r['id']] = $r;
        }

        // Forçado por chave natural: sobrescreve payload + reseta user_modified, preserva project.
        self::assertSame('CODE HTML', $rows['hero']['html']);
        self::assertSame(0, (int) $rows['hero']['user_modified']);
        self::assertNull($rows['hero']['project']);

        // Não forçado, user_modified=1: html preservado.
        self::assertSame('USER EDITED B', $rows['banner']['html']);
        self::assertSame('NEW PRE B', $rows['banner']['css_precompiled']);
        self::assertSame(1, (int) $rows['banner']['user_modified']);

        // Não forçado, protegido por project (deploy de núcleo): intacto.
        self::assertSame('OLD', $rows['card']['html']);
        self::assertSame('OLD PRE C', $rows['card']['css_precompiled']);
        self::assertSame('proj1', $rows['card']['project']);

        // Forçado por PK: bypassa a proteção de project e atualiza, preservando o project.
        self::assertSame('CODE HTML D', $rows['menu']['html']);
        self::assertSame('proj1', $rows['menu']['project']);

        self::assertSame(3, $res['updated']);
        self::assertSame(1, $res['same']);
    }

    public function testDeployProjetoAtualizaPrecompiledSemSobrescreverHtmlUsuario(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE widgets_demo (
            id_widgets_demo INTEGER PRIMARY KEY AUTOINCREMENT,
            id TEXT, language TEXT, html TEXT, css_precompiled TEXT, status TEXT,
            user_modified INTEGER DEFAULT 0, project TEXT
        )');
        $pdo->exec("INSERT INTO widgets_demo (id,language,html,css_precompiled,status,user_modified,project)
            VALUES ('landing','pt-br','HTML DO USUARIO','PRE ANTIGO','A',1,'proj1')");

        $registros = [[
            'id' => 'landing',
            'language' => 'pt-br',
            'html' => 'HTML DO REPOSITORIO',
            'css_precompiled' => 'PRE NOVO',
            'status' => 'A',
        ]];

        $projectAnterior = $GLOBALS['CLI_OPTS']['project'] ?? null;
        $GLOBALS['CLI_OPTS']['project'] = 'proj1';
        ob_start();
        try {
            $res = sincronizarTabela($pdo, 'widgets_demo', $registros, false, false);
        } finally {
            ob_end_clean();
            if ($projectAnterior === null) unset($GLOBALS['CLI_OPTS']['project']);
            else $GLOBALS['CLI_OPTS']['project'] = $projectAnterior;
        }

        $row = $pdo->query("SELECT html,css_precompiled,user_modified,project FROM widgets_demo WHERE id='landing'")
            ->fetch(PDO::FETCH_ASSOC);

        self::assertSame('HTML DO USUARIO', $row['html']);
        self::assertSame('PRE NOVO', $row['css_precompiled']);
        self::assertSame(1, (int) $row['user_modified']);
        self::assertSame('proj1', $row['project']);
        self::assertSame(1, $res['updated']);
    }

    public function testMudancaDaPoliticaInvalidaSomenteTabelasDeRecursos(): void
    {
        $atuais = [
            'PaginasData.json' => 'mesmo-hash-paginas',
            'LayoutsData.json' => 'mesmo-hash-layouts',
            'ComponentesData.json' => 'mesmo-hash-componentes',
            'TemplatesData.json' => 'mesmo-hash-templates',
            'VariaveisData.json' => 'mesmo-hash-variaveis',
            '__sync_policy' => DB_SYNC_POLICY_VERSION,
        ];
        $anteriores = $atuais;
        unset($anteriores['__sync_policy']);

        self::assertSame(
            ['paginas', 'layouts', 'componentes', 'templates'],
            tabelasAlteradasPorChecksum($atuais, $anteriores)
        );
    }
}
