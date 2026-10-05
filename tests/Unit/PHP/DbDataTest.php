<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DbDataTest extends TestCase {
    private string $dir;

    protected function setUp(): void {
        $this->dir = sys_get_temp_dir() . '/c2f-db-data-test-' . bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    protected function tearDown(): void {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        rmdir($this->dir);
    }

    private function rows(int $count = 6): array {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) $rows[] = ['id' => sprintf('row-%02d', $i), 'language' => 'pt-br', 'html' => '<p>ação 😀</p>', 'number' => 1.0];
        return $rows;
    }

    public function testRoundTripBoundsAndDeterministicPublication(): void {
        $rows = $this->rows();
        $result = db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 350]);
        self::assertTrue($result['partitioned']);
        self::assertSame($rows, db_data_read_table('paginas', $this->dir));
        $manifest = db_data_table_manifest('paginas', $this->dir);
        self::assertSame(6, $manifest['total_records']);
        self::assertGreaterThan(1, $manifest['total_parts']);
        self::assertFileDoesNotExist($this->dir . '/PaginasData.json');
        foreach ($manifest['parts'] as $part) {
            self::assertLessThanOrEqual(350, $part['size_bytes']);
            self::assertSame($part['sha256'], hash_file('sha256', $this->dir . '/' . $part['file']));
        }
        $before = file_get_contents($this->dir . '/PaginasData.manifest.json');
        $checksum = db_data_table_checksum('paginas', $this->dir);
        touch($this->dir . '/PaginasData.manifest.json', 1234567890);
        db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 350]);
        clearstatcache();
        self::assertSame(1234567890, filemtime($this->dir . '/PaginasData.manifest.json'));
        self::assertSame($before, file_get_contents($this->dir . '/PaginasData.manifest.json'));
        self::assertSame($checksum, db_data_table_checksum('paginas', $this->dir));
        self::assertSame([], glob($this->dir . '/*.tmp'));
    }

    public function testMonolithCompatibilityDiscoveryAndMissingTable(): void {
        $rows = $this->rows(1);
        db_data_write_table('usuarios_perfis', $rows, $this->dir);
        self::assertSame($rows, db_data_read_table('usuarios_perfis', $this->dir));
        self::assertNull(db_data_table_manifest('usuarios_perfis', $this->dir));
        file_put_contents($this->dir . '/OrphanData.part-001.json', '[]');
        file_put_contents($this->dir . '/legacy_tableData.json', json_encode($rows, JSON_PRESERVE_ZERO_FRACTION));
        self::assertSame($rows, db_data_read_table('legacy_table', $this->dir));
        self::assertSame(['legacy_table', 'usuarios_perfis'], db_data_list_tables($this->dir));
        self::assertSame([], db_data_read_table('missing', $this->dir));
    }

    public function testGrowReducePartCountThenShrinkAndEmpty(): void {
        $rows = $this->rows();
        db_data_write_table('paginas', $rows, $this->dir);
        db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 180]);
        self::assertCount(6, glob($this->dir . '/*.part-*.json'));
        db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 350]);
        self::assertCount(3, glob($this->dir . '/*.part-*.json'));
        db_data_write_table('paginas', array_slice($rows, 0, 1), $this->dir);
        self::assertSame(array_slice($rows, 0, 1), db_data_read_table('paginas', $this->dir));
        self::assertSame([], glob($this->dir . '/*.part-*.json'));
        self::assertFileDoesNotExist($this->dir . '/PaginasData.manifest.json');
        db_data_write_table('paginas', [], $this->dir);
        self::assertSame('[]', file_get_contents($this->dir . '/PaginasData.json'));
    }

    public static function corruptions(): array {
        return array_map(fn($case) => [$case], ['byte', 'missing', 'hash', 'path', 'index', 'parts', 'records', 'bytes', 'version', 'json', 'no-manifest']);
    }

    #[DataProvider('corruptions')]
    public function testCorruptionNeverFallsBackToStaleMonolith(string $case): void {
        db_data_write_table('paginas', $this->rows(), $this->dir, ['part_size_limit' => 350]);
        $path = $this->dir . '/PaginasData.manifest.json';
        $m = json_decode(file_get_contents($path), true);
        $part = $this->dir . '/' . $m['parts'][0]['file'];
        // A stale file must not mask any manifest failure.
        file_put_contents($this->dir . '/PaginasData.json', '[]');
        switch ($case) {
            case 'byte': $raw = file_get_contents($part); $raw[0] = ' '; file_put_contents($part, $raw); break;
            case 'missing': unlink($part); break;
            case 'hash': $m['parts'][0]['sha256'] = str_repeat('0', 64); break;
            case 'path': $m['parts'][0]['file'] = '../outside.json'; break;
            case 'index': $m['parts'][0]['index'] = 2; break;
            case 'parts': $m['total_parts']++; break;
            case 'records': $m['total_records']++; break;
            case 'bytes': $m['total_bytes']++; break;
            case 'version': $m['schema_version'] = 2; break;
            case 'json': file_put_contents($path, '{'); break;
            case 'no-manifest': unlink($path); unlink($this->dir . '/PaginasData.json'); break;
        }
        if (!in_array($case, ['json', 'no-manifest'], true)) file_put_contents($path, json_encode($m));
        $this->expectException(RuntimeException::class);
        db_data_read_table('paginas', $this->dir);
    }

    public function testOversizedRecordFailsBeforeReplacingExistingTable(): void {
        db_data_write_table('paginas', $this->rows(1), $this->dir);
        try {
            db_data_write_table('paginas', [['html' => str_repeat('a', 1000)]], $this->dir, ['part_size_limit' => 100]);
            self::fail('Oversized record accepted');
        } catch (RuntimeException $e) { self::assertStringContainsString('RECORD_TOO_LARGE', $e->getMessage()); }
        self::assertSame($this->rows(1), db_data_read_table('paginas', $this->dir));
    }

    public function testStagingFailureLeavesPreviouslyPublishedTableIntact(): void {
        db_data_write_table('paginas', $this->rows(1), $this->dir);
        mkdir($this->dir . '/PaginasData.part-002.json.tmp');
        try {
            db_data_write_table('paginas', $this->rows(), $this->dir, ['part_size_limit' => 350]);
            self::fail('Failed temporary write accepted');
        } catch (RuntimeException $e) { self::assertStringContainsString('WRITE_FAILED', $e->getMessage()); }
        self::assertSame($this->rows(1), db_data_read_table('paginas', $this->dir));
        self::assertSame([], glob($this->dir . '/*.part-*.json'));
        self::assertFileDoesNotExist($this->dir . '/PaginasData.part-001.json.tmp');
    }

    public function testExactLimitPartitionsAndMalformedMonolithFails(): void {
        db_data_write_table('paginas', $this->rows(1), $this->dir);
        $limit = filesize($this->dir . '/PaginasData.json');
        db_data_write_table('paginas', $this->rows(1), $this->dir, ['part_size_limit' => $limit]);
        self::assertSame(1, db_data_table_manifest('paginas', $this->dir)['total_parts']);
        file_put_contents($this->dir . '/UsuariosData.json', '{}');
        $this->expectException(RuntimeException::class);
        db_data_read_table('usuarios', $this->dir);
    }

    public function testChecksumDetectsChangesOutsideFirstPartAndSkipsTimestampChanges(): void {
        $rows = $this->rows();
        db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 350]);
        $before = db_data_table_checksum('paginas', $this->dir);
        $m = db_data_table_manifest('paginas', $this->dir);
        $m['generated_at'] = '2000-01-01T00:00:00Z';
        file_put_contents($this->dir . '/PaginasData.manifest.json', json_encode($m));
        self::assertSame($before, db_data_table_checksum('paginas', $this->dir));
        $rows[5]['html'] = '<p>changed</p>';
        db_data_write_table('paginas', $rows, $this->dir, ['part_size_limit' => 350]);
        self::assertNotSame($before, db_data_table_checksum('paginas', $this->dir));
        self::assertSame(['paginas'], db_data_list_tables($this->dir));
    }

    public function testActualDefaultEightyMiBLimit(): void {
        $payload = str_repeat('x', 1024 * 1024);
        $rows = [];
        for ($i = 0; $i < 82; $i++) $rows[] = ['id' => $i, 'html' => $payload];
        db_data_write_table('paginas', $rows, $this->dir);
        $m = db_data_table_manifest('paginas', $this->dir);
        self::assertSame(2, $m['total_parts']);
        foreach ($m['parts'] as $part) self::assertLessThanOrEqual(80 * 1024 * 1024, $part['size_bytes']);
        self::assertSame($rows, db_data_read_table('paginas', $this->dir));
    }
}
