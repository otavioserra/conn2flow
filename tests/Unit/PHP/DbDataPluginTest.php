<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DbDataPluginTest extends TestCase {
    public function testPluginComparisonReceivesCompletePartitionedTable(): void {
        define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/plugins/atualizacao-plugin-banco-de-dados.php';
        $dir = sys_get_temp_dir() . '/c2f-data-plugin-' . bin2hex(random_bytes(8));
        mkdir($dir);
        $GLOBALS['DB_DATA_DIR'] = $dir . '/';
        $GLOBALS['CHECKSUM_CHANGED_TABLES'] = null;
        $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
        $pdo = new DbDataPluginSqlitePdo('sqlite::memory:');
        $pdo->exec('CREATE TABLE widgets_demo (id_widgets_demo INTEGER PRIMARY KEY, html TEXT)');
        $rows = [];
        $stmt = $pdo->prepare('INSERT INTO widgets_demo VALUES (?,?)');
        for ($i = 1; $i <= 6; $i++) {
            $rows[] = ['id_widgets_demo' => $i, 'html' => str_repeat('x', 50)];
            $stmt->execute([$i, str_repeat('x', 50)]);
        }
        try {
            db_data_write_table('widgets_demo', $rows, $dir, ['part_size_limit' => 250]);
            ob_start();
            try { $result = comparacaoDados($pdo); } finally { ob_end_clean(); }
            self::assertSame(6, $result['widgets_demo']['same']);
            self::assertSame(0, $result['widgets_demo']['inserted']);
            self::assertSame(0, $result['widgets_demo']['updated']);
            self::assertSame($rows, $pdo->query('SELECT * FROM widgets_demo ORDER BY id_widgets_demo')->fetchAll(PDO::FETCH_ASSOC));
        } finally {
            foreach (glob($dir . '/*') as $file) unlink($file);
            rmdir($dir);
        }
    }
}

final class DbDataPluginSqlitePdo extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if ($query === 'SHOW TABLES LIKE :t') $query = "SELECT name FROM sqlite_master WHERE type='table' AND name=:t";
        if (preg_match('/^SHOW COLUMNS FROM `([a-z_]+)` LIKE \'plugin\'$/', $query, $m)) {
            $query = "SELECT name AS Field FROM pragma_table_info('" . $m[1] . "') WHERE name='plugin'";
        }
        return parent::prepare($query, $options);
    }
}
