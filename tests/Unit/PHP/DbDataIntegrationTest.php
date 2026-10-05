<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DbDataIntegrationTest extends TestCase {
    private string $dir;
    private array $rows;

    protected function setUp(): void {
        define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/recuperacao-dados-recursos.php';
        $this->dir = sys_get_temp_dir() . '/c2f-data-integration-' . bin2hex(random_bytes(8));
        mkdir($this->dir);
        $GLOBALS['DB_DATA_DIR'] = $this->dir . '/';
        $GLOBALS['CLI_OPTS'] = ['no-resource-removal' => true, 'orphans-mode' => 'ignore'];
        $GLOBALS['CHECKSUM_CHANGED_TABLES'] = null;
        $GLOBALS['RDR_SILENT'] = true;
        file_put_contents($this->dir . '/schema-metadata.json', json_encode(['tables' => ['widgets_demo' => [
            'nome' => 'widgets_demo', 'id' => 'id', 'id_numerico' => 'id_widgets_demo',
            'strategy' => 'natural_key', 'natural_key_columns' => ['id', 'language'],
        ]]]));
        schemaMetadata(true);
        $this->rows = [];
        for ($i = 1; $i <= 6; $i++) $this->rows[] = ['id' => 'row' . $i, 'language' => 'pt-br', 'html' => '<p>record ' . $i . '</p>', 'status' => 'A'];
    }

    protected function tearDown(): void {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        rmdir($this->dir);
    }

    private function pdo(): PDO {
        $pdo = new DbDataSqlitePdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE TABLE widgets_demo (id_widgets_demo INTEGER PRIMARY KEY AUTOINCREMENT, id TEXT, language TEXT, html TEXT, status TEXT, project TEXT, user_modified INTEGER DEFAULT 0)");
        $stmt = $pdo->prepare('INSERT INTO widgets_demo (id,language,html,status) VALUES (:id,:language,:html,:status)');
        foreach (array_reverse($this->rows) as $row) $stmt->execute($row);
        return $pdo;
    }

    public function testComparisonSeesAllPartsAndWithdrawalKeepsEveryRow(): void {
        $pdo = $this->pdo();
        db_data_write_table('widgets_demo', $this->rows, $this->dir, ['part_size_limit' => 300]);
        self::assertGreaterThan(1, db_data_table_manifest('widgets_demo', $this->dir)['total_parts']);
        recursos_retirada_tabela($pdo, $this->dir, 'widgets_demo', $this->rows, ['id', 'language'], null, false);
        $before = $pdo->query('SELECT * FROM widgets_demo ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        ob_start();
        try { $summary = comparacaoDados($pdo); } finally { ob_end_clean(); }
        self::assertSame(6, $summary['widgets_demo']['same']);
        self::assertSame(0, $summary['widgets_demo']['orphans']);
        $withdrawal = recursos_retirada_tabela($pdo, $this->dir, 'widgets_demo', db_data_read_table('widgets_demo', $this->dir), ['id', 'language'], null, false);
        self::assertSame(0, $withdrawal['saidos']);
        self::assertSame($before, $pdo->query('SELECT * FROM widgets_demo ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        // Negative control: feeding just the last part would withdraw valid earlier rows.
        $m = db_data_table_manifest('widgets_demo', $this->dir);
        $last = json_decode(file_get_contents($this->dir . '/' . $m['parts'][count($m['parts']) - 1]['file']), true);
        self::assertNotEmpty(recursos_retirada_planejar(recursos_retirada_chaves($this->rows, ['id', 'language']), recursos_retirada_chaves($last, ['id', 'language'])));
    }

    public function testCorruptedPartAbortsComparisonBeforeSqlChanges(): void {
        $pdo = $this->pdo();
        $rows = $this->rows; $rows[0]['html'] = 'changed';
        db_data_write_table('widgets_demo', $rows, $this->dir, ['part_size_limit' => 300]);
        unlink($this->dir . '/WidgetsDemoData.part-002.json');
        $before = $pdo->query('SELECT * FROM widgets_demo')->fetchAll(PDO::FETCH_ASSOC);
        ob_start();
        try { comparacaoDados($pdo); self::fail('Incomplete table accepted'); }
        catch (RuntimeException $e) { self::assertStringContainsString('PART_MISSING', $e->getMessage()); }
        finally { ob_end_clean(); }
        self::assertSame($before, $pdo->query('SELECT * FROM widgets_demo')->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testReverseExportZipExtractionAndResourceRecovery(): void {
        reverseExport($this->pdo(), ['widgets_demo'], $this->dir, ['part_size_limit' => 500]);
        self::assertNotNull(db_data_table_manifest('widgets_demo', $this->dir));
        self::assertSame(array_column($this->rows, 'id'), array_column(db_data_read_table('widgets_demo', $this->dir), 'id'));
        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->dir . '/recover.zip', ZipArchive::CREATE));
        db_data_zip_add_tables($zip, $this->dir);
        self::assertTrue($zip->close());
        self::assertTrue($zip->open($this->dir . '/recover.zip'));
        self::assertSame(count(db_data_table_files('widgets_demo', $this->dir)), $zip->numFiles);
        mkdir($this->dir . '/restored');
        self::assertTrue($zip->extractTo($this->dir . '/restored'));
        $zip->close();
        self::assertSame(db_data_read_table('widgets_demo', $this->dir), db_data_read_table('widgets_demo', $this->dir . '/restored'));
        $gestor = $this->dir . '/gestor'; mkdir($gestor . '/resources', 0775, true);
        file_put_contents($gestor . '/resources/tables_config.json', json_encode(['tabelas' => ['widgets_demo' => [
            'nome' => 'widgets_demo', 'id' => 'id', 'id_numerico' => 'id_widgets_demo',
            'config' => ['strategy' => 'natural_key', 'natural_key_columns' => ['id', 'language'],
                'sync_resources' => true, 'metadata_file' => 'widgets_demo.json', 'field_types' => ['html' => 'file:html']],
        ]]]));
        $stats = rdr_processar($this->dir . '/restored', $gestor);
        self::assertSame(6, $stats['registros']);
        foreach ($this->rows as $row) self::assertSame($row['html'], file_get_contents($gestor . '/resources/pt-br/widgets_demo/' . $row['id'] . '/' . $row['id'] . '.html'));
        self::assertCount(6, json_decode(file_get_contents($gestor . '/resources/pt-br/widgets_demo.json'), true));
    }
}

/** Adapt only MySQL column discovery; all synchronization/withdrawal SQL runs in SQLite. */
final class DbDataSqlitePdo extends PDO {
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        if (preg_match('/^SHOW COLUMNS FROM `([a-z_]+)`$/', $query, $m)) {
            $query = "SELECT name AS Field FROM pragma_table_info('" . $m[1] . "')";
        }
        return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}
