<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'publisher-index' . DIRECTORY_SEPARATOR . 'publisher-index.widget.php';

final class PublisherIndexWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        global $_GESTOR;

        $_GESTOR['linguagem-codigo'] = 'pt-br';
        $_GESTOR['url-raiz'] = '/';
        $_GESTOR['bibliotecas-dados'] = $_GESTOR['bibliotecas-dados'] ?? [];
        $_GESTOR['bibliotecas-dados']['formato'] = ['formato.php'];
        $_GESTOR['bibliotecas-inseridas'] = $_GESTOR['bibliotecas-inseridas'] ?? [];
        $_GESTOR['recursos-incluidos-hashes'] = [];
        $_GESTOR['html-extra-head'] = [];
        $_GESTOR['css'] = [];
        $_GESTOR['css-compiled'] = [];
    }

    public function testRenderItensSubstituiCamposMapeados(): void
    {
        $template = '<article><h2>[[item#titulo]]</h2><a href="@[[item#link]]@">Abrir</a></article>';
        $publicacoes = [
            ['nome' => 'Ignorado', 'headline' => 'Primeiro', 'url' => '/primeiro'],
            ['headline' => 'Segundo', 'url' => '/segundo'],
        ];

        $html = publisher_index_widget_render_itens($template, $publicacoes, [
            'titulo' => 'headline',
            'link' => 'url',
        ]);

        self::assertStringContainsString('<h2>Primeiro</h2>', $html);
        self::assertStringContainsString('href="/segundo"', $html);
        self::assertStringNotContainsString('Ignorado', $html);
    }

    public function testMontarSaidaDeduplicaRecursosDoWidget(): void
    {
        global $_GESTOR;

        $html = publisher_index_widget_montar_saida('<section>Indice</section>', '.index{display:grid}', '.compiled{}', '<meta name="widget" content="index">');
        publisher_index_widget_montar_saida('<section>Indice</section>', '.index{display:grid}', '.compiled{}', '<meta name="widget" content="index">');

        self::assertSame('<section>Indice</section>', $html);
        self::assertCount(3, $_GESTOR['recursos-incluidos-hashes']);
        self::assertCount(1, $_GESTOR['html-extra-head']);
        self::assertCount(3, $_GESTOR['css']);
        self::assertCount(3, $_GESTOR['css-compiled']);
    }

    public function testUnicodeEscapeGeraVariantesComESemBarra(): void
    {
        self::assertSame('Tu00edtulo', publisher_index_widget_unicode_escape('Título'));
        self::assertSame('T\\u00edtulo', publisher_index_widget_unicode_escape('Título', true));
        self::assertSame('Acentuau00e7u00e3o', publisher_index_widget_unicode_escape('Acentuação'));
        self::assertSame('Acentua\\u00e7\\u00e3o', publisher_index_widget_unicode_escape('Acentuação', true));
    }

    public function testCorrigirUnicodePreservaStringsNormaisEDecodificaCorrompidas(): void
    {
        self::assertSame('Título normal', publisher_index_widget_corrigir_unicode('Título normal'));
        self::assertSame('Título', publisher_index_widget_corrigir_unicode('Tu00edtulo'));
        self::assertSame('Título', publisher_index_widget_corrigir_unicode('T\\u00edtulo'));
        self::assertSame('Acentuação', publisher_index_widget_corrigir_unicode('Acentuau00e7u00e3o'));
    }

    public function testMetricasGlobaisERemocaoCondicionalDoBloco(): void
    {
        $template = '<section data-metrics="@[[show_metrics]]@"><!-- metrics < --><div class="publisher-index-metrics">Exibindo [[page_count]] de @[[page_total]]@</div><!-- metrics > --></section>';

        $resolved = publisher_index_widget_resolver_globais($template, [
            'show_metrics' => 'true',
            'page_count' => '2',
            'page_total' => '5',
        ]);

        $visible = publisher_index_widget_bloco_condicional($resolved, 'metrics', true);
        self::assertStringContainsString('data-metrics="true"', $visible);
        self::assertStringContainsString('Exibindo 2 de 5', $visible);
        self::assertStringNotContainsString('<!-- metrics', $visible);

        $hidden = publisher_index_widget_bloco_condicional($resolved, 'metrics', false);
        self::assertStringNotContainsString('publisher-index-metrics', $hidden);
        self::assertStringContainsString('data-metrics="true"', $hidden);
    }

    public function testBuscaComMysqlTemFiltroDisjuntivoEInnerJoin(): void
    {
        if (!filter_var(getenv('CONN2FLOW_RUN_DB_TESTS'), FILTER_VALIDATE_BOOLEAN)) {
            self::markTestSkipped('Defina CONN2FLOW_RUN_DB_TESTS=1 para executar o teste integrado com MySQL.');
        }

        if (!extension_loaded('mysqli')) {
            self::markTestSkipped('Extensão mysqli indisponível no PHP CLI.');
        }

        global $_BANCO, $_GESTOR;

        $database = getenv('CONN2FLOW_DB_DATABASE') ?: ($_BANCO['nome'] ?? '');
        if ($database !== 'conn2flow_test') {
            self::markTestSkipped('Teste integrado bloqueado: CONN2FLOW_DB_DATABASE deve ser conn2flow_test.');
        }

        $host = getenv('CONN2FLOW_DB_HOST') ?: ($_BANCO['host'] ?? '127.0.0.1');
        $user = getenv('CONN2FLOW_DB_USERNAME') ?: ($_BANCO['usuario'] ?? 'root');
        $pass = getenv('CONN2FLOW_DB_PASSWORD') ?: ($_BANCO['senha'] ?? '');

        $mysqli = $this->connectMysqlForTest($host, $user, $pass, $database);
        if (!$mysqli) {
            self::markTestSkipped('MySQL de teste indisponível ou sem permissão para preparar conn2flow_test.');
        }

        $_BANCO['tipo'] = 'mysqli';
        $_BANCO['host'] = $host;
        $_BANCO['nome'] = $database;
        $_BANCO['usuario'] = $user;
        $_BANCO['senha'] = $pass;
        unset($_BANCO['conexao']);
        $_GESTOR['linguagem-codigo'] = 'pt-br';

        try {
            $this->resetPublisherIndexTables($mysqli);
            $this->seedPublisherIndexRows($mysqli);

            $itens = publisher_index_widget_buscar_publicacoes([
                'publisher_id' => 'noticias',
                'busca' => 'Título',
                'offset' => 0,
                'limit' => 10,
                'order_by' => 'title_asc',
            ]);

            $ids = array_column($itens, 'page_id');
            sort($ids);

            self::assertSame(['pagina-barra', 'pagina-corrompida', 'pagina-normal'], $ids);
            self::assertNotContains('pagina-sem-publisher-page', $ids);
            self::assertNotContains('pagina-outro-publicador', $ids);
            self::assertContains('Título com barra', array_column($itens, 'titulo'));
            self::assertContains('Título corrompido', array_column($itens, 'titulo'));
            self::assertContains('Título normal', array_column($itens, 'titulo'));
            self::assertContains('Acentuação normal', array_column($itens, 'resumo'));
            self::assertSame(3, publisher_index_widget_contar_publicacoes([
                'publisher_id' => 'noticias',
                'busca' => 'Título',
            ]));
        } finally {
            $this->resetPublisherIndexTables($mysqli);
            $mysqli->close();
            unset($_BANCO['conexao']);
        }
    }

    private function connectMysqlForTest(string $host, string $user, string $pass, string $database): ?mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            return new mysqli($host, $user, $pass, $database);
        } catch (mysqli_sql_exception $firstError) {
            try {
                $admin = new mysqli($host, $user, $pass);
                $admin->query('CREATE DATABASE IF NOT EXISTS `'.$admin->real_escape_string($database).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $admin->select_db($database);
                return $admin;
            } catch (mysqli_sql_exception $secondError) {
                return null;
            }
        }
    }

    private function resetPublisherIndexTables(mysqli $mysqli): void
    {
        $mysqli->query('DROP TABLE IF EXISTS publisher_pages');
        $mysqli->query('DROP TABLE IF EXISTS paginas');
        $mysqli->query(
            'CREATE TABLE paginas (
                id VARCHAR(190) NOT NULL PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                caminho VARCHAR(255) NOT NULL,
                data_modificacao DATETIME NOT NULL,
                publisher_id VARCHAR(190) NOT NULL,
                status CHAR(1) NOT NULL,
                language VARCHAR(10) NOT NULL,
                KEY idx_pub_lang_status (publisher_id, language, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $mysqli->query(
            'CREATE TABLE publisher_pages (
                page_id VARCHAR(190) NOT NULL,
                language VARCHAR(10) NOT NULL,
                fields_values JSON NULL,
                PRIMARY KEY (page_id, language)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function seedPublisherIndexRows(mysqli $mysqli): void
    {
        $pages = [
            ['pagina-normal', 'Título normal', 'normal', '2026-06-15 10:00:00', 'noticias', 'A', 'pt-br'],
            ['pagina-corrompida', 'Tu00edtulo corrompido', 'corrompida', '2026-06-15 11:00:00', 'noticias', 'A', 'pt-br'],
            ['pagina-barra', 'T\\u00edtulo com barra', 'barra', '2026-06-15 12:00:00', 'noticias', 'A', 'pt-br'],
            ['pagina-sem-publisher-page', 'Título sem join', 'sem-join', '2026-06-15 13:00:00', 'noticias', 'A', 'pt-br'],
            ['pagina-outro-publicador', 'Título outro publicador', 'outro', '2026-06-15 14:00:00', 'blog', 'A', 'pt-br'],
            ['pagina-inativa', 'Título inativo', 'inativa', '2026-06-15 15:00:00', 'noticias', 'I', 'pt-br'],
        ];

        $stmt = $mysqli->prepare('INSERT INTO paginas (id, nome, caminho, data_modificacao, publisher_id, status, language) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($pages as $page) {
            $stmt->bind_param('sssssss', $page[0], $page[1], $page[2], $page[3], $page[4], $page[5], $page[6]);
            $stmt->execute();
        }
        $stmt->close();

        $publisherRows = [
            ['pagina-normal', 'pt-br', '[{"id":"resumo","value":"Acentuação normal"}]'],
            ['pagina-corrompida', 'pt-br', '[{"id":"resumo","value":"Acentuau00e7u00e3o corrompida"}]'],
            ['pagina-barra', 'pt-br', '[{"id":"resumo","value":"Acentua\\\\u00e7\\\\u00e3o com barra"}]'],
            ['pagina-outro-publicador', 'pt-br', '[{"id":"resumo","value":"Outro publicador"}]'],
            ['pagina-inativa', 'pt-br', '[{"id":"resumo","value":"Inativo"}]'],
        ];

        $stmt = $mysqli->prepare('INSERT INTO publisher_pages (page_id, language, fields_values) VALUES (?, ?, ?)');
        foreach ($publisherRows as $row) {
            $stmt->bind_param('sss', $row[0], $row[1], $row[2]);
            $stmt->execute();
        }
        $stmt->close();
    }
}
