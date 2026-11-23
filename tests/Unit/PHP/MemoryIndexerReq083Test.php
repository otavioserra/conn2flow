<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Memory\Indexer;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/Memory/Indexer.php';

final class MemoryIndexerReq083Test extends TestCase
{
    private string $root;
    private Indexer $indexer;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/c2f-memory-req083-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/memory/reports', 0777, true);
        $this->indexer = new Indexer($this->root);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    private function document(string $name, string $id, string $status): void
    {
        file_put_contents($this->root . '/memory/' . $name, "---\nid: '$id'\nstatus: '$status'\n---\n# Body\n");
    }

    public function testReadsYamlIdAndLegacySlugCaseInsensitively(): void
    {
        $this->document('reports/renamed.md', 'REP-004', 'READY_FOR_ARCHITECT');
        file_put_contents($this->root . '/memory/reports/req-083-legacy.md', "# Legacy\nStatus: APPROVED\n");
        self::assertSame('READY_FOR_ARCHITECT', $this->indexer->get('rep-004', 'status'));
        self::assertSame('APPROVED', $this->indexer->get('REQ-083', 'status'));
    }

    public function testExactNameAndStemTakePrecedenceOverYamlAndSlug(): void
    {
        $this->document('reports/another-name.md', 'REP-004', 'YAML');
        $this->document('reports/REP-004-slug.md', 'OTHER', 'PREFIX');
        $this->document('reports/REP-004.md', 'EXACT', 'EXACT');
        self::assertSame('EXACT', $this->indexer->get('rep-004', 'status'));
        self::assertSame('EXACT', $this->indexer->get('rep-004.MD', 'status'));
        self::assertSame('YAML', $this->indexer->get('memory/reports/another-name.md', 'status'));
    }

    public function testSetResolvesYamlIdAndSlugAndUpdatesLocalIndex(): void
    {
        $this->document('reports/renamed.md', 'REP-004', 'BEFORE');
        $this->document('reports/req-083-legacy.md', 'OTHER', 'BEFORE');
        $this->indexer->set('rep-004', ['status' => 'AFTER_YAML']);
        $this->indexer->set('REQ-083', ['status' => 'AFTER_SLUG']);
        self::assertSame('AFTER_YAML', $this->indexer->get('memory/reports/renamed.md', 'status'));
        self::assertSame('AFTER_SLUG', $this->indexer->get('memory/reports/req-083-legacy.md', 'status'));
        $index = file_get_contents($this->root . '/memory/reports/index.md');
        self::assertStringContainsString('AFTER_YAML', $index);
        self::assertStringContainsString('AFTER_SLUG', $index);
    }

    public function testAmbiguousIdListsSortedPathsAndDoesNotMutate(): void
    {
        $this->document('reports/aa.md', 'REP-004', 'UNCHANGED');
        $this->document('REP-004-slug.md', 'REP-004', 'UNCHANGED');
        try {
            $this->indexer->set('REP-004', ['status' => 'CHANGED']);
            self::fail('Expected ambiguous target to be refused');
        } catch (RuntimeException $error) {
            self::assertSame(
                'Target must be unique: REP-004. Use a path under memory/: memory/REP-004-slug.md, memory/reports/aa.md',
                $error->getMessage()
            );
        }
        self::assertSame('UNCHANGED', $this->indexer->get('memory/reports/aa.md', 'status'));
        self::assertSame('UNCHANGED', $this->indexer->get('memory/REP-004-slug.md', 'status'));
        self::assertFileDoesNotExist($this->root . '/memory/.mdd.lock');
    }

    public function testAmbiguousExactStemRemainsAmbiguous(): void
    {
        $this->document('note.md', 'ROOT', 'ROOT');
        $this->document('reports/note.md', 'NESTED', 'NESTED');
        $this->expectExceptionMessage('memory/note.md, memory/reports/note.md');
        $this->indexer->get('note', 'status');
    }

    public function testDiscoveryIgnoresInfrastructureDuplicateIdsAndInvalidUtf8(): void
    {
        $this->document('README.md', 'REP-004', 'INFRA');
        file_put_contents($this->root . '/memory/duplicate.md', "---\nid: REP-004\nid: REP-004\n---\n");
        file_put_contents($this->root . '/memory/invalid.md', "---\nid: REP-004\n---\n\xff\n");
        $this->expectExceptionMessage('Target not found: REP-004');
        $this->indexer->get('REP-004', 'status');
    }
}
