<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CoreSeedReplacementReq238Test extends TestCase
{
    public function testCoreSyncRemovesStaleProjectPartitionsWithoutTouchingRuntimeContent(): void
    {
        if (PHP_OS_FAMILY === 'Windows') self::markTestSkipped('Runs with the Lab bash/rsync toolchain.');
        $root = sys_get_temp_dir() . '/req238-seeds-' . bin2hex(random_bytes(6));
        mkdir($root . '/core/db/data', 0777, true);
        mkdir($root . '/target/db/data', 0777, true);
        mkdir($root . '/target/contents', 0777, true);
        file_put_contents($root . '/core/db/data/PaginasData.json', '[{"id":"dashboard"}]');
        foreach (['PaginasData.manifest.json', 'PaginasData.part-001.json', 'CookieConsentData.json'] as $file) {
            file_put_contents($root . '/target/db/data/' . $file, '[]');
        }
        file_put_contents($root . '/target/contents/upload.txt', 'preserve');
        $source = file_get_contents(getenv('C2F_CORE_SYNC_SOURCE') ?: CONN2FLOW_ROOT . '/ai-workspace/en/scripts/projects/sync-core-to-project.sh');
        preg_match('/CORE_DATA_CMD=\([\s\S]*?\n\)/', $source, $match);
        $script = 'CORE_SOURCE=' . escapeshellarg($root . '/core') . "\n"
            . 'TARGET_PATH=' . escapeshellarg($root . '/target') . "\nPT_RSYNC_OPTS=()\n"
            . $match[0] . "\n\"\${CORE_DATA_CMD[@]}\"\n";
        file_put_contents($root . '/sync.sh', $script);
        try {
            exec('bash ' . escapeshellarg($root . '/sync.sh') . ' 2>&1', $output, $exit);
            self::assertSame(0, $exit, implode("\n", $output));
            self::assertSame('[{"id":"dashboard"}]', file_get_contents($root . '/target/db/data/PaginasData.json'));
            foreach (['PaginasData.manifest.json', 'PaginasData.part-001.json', 'CookieConsentData.json'] as $file) {
                self::assertFileDoesNotExist($root . '/target/db/data/' . $file);
            }
            self::assertSame('preserve', file_get_contents($root . '/target/contents/upload.txt'));
        } finally {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            rmdir($root);
        }
    }
}
