<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\AiArchiveMemoryCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Contracts\OutputInterface;
use PHPUnit\Framework\TestCase;

foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php',
    'Console/Input.php', 'Commands/AiArchiveMemoryCommand.php'] as $file) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $file;
}

/**
 * REQ-090 / BATCH-092 (ARCH-018 Phase 3): MDD naming, memory/reports/ support,
 * --status selection and rewriting of only the links affected by a move.
 */
final class MemoryArchiveReq090Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-req090-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/memory/human-requests', 0777, true);
        mkdir($this->root . '/memory/implementation', 0777, true);
        mkdir($this->root . '/memory/reports', 0777, true);
        mkdir($this->root . '/scripts', 0777, true);
        file_put_contents($this->root . '/scripts/tool.cjs', "//\n");
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    private function write(string $relative, string $content): void
    {
        file_put_contents($this->root . '/' . $relative, $content);
    }

    private function read(string $relative): string
    {
        return (string) file_get_contents($this->root . '/' . $relative);
    }

    private function archive(array $options, ?OutputInterface $output = null): int
    {
        $output ??= $this->quietOutput();
        $command = new AiArchiveMemoryCommand($this->root . '/unused');

        return $command->execute(new Input(['c2f', 'ai:archive-memory', '--repo=' . $this->root, ...$options]), $output);
    }

    private function quietOutput(): OutputInterface
    {
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::never())->method('error');

        return $output;
    }

    private function doc(string $status, string $body = "# Doc\n"): string
    {
        return "---\nid: \"X\"\nstatus: \"{$status}\"\n---\n\n{$body}";
    }

    public function testCanonicalNameKeepsLegacyAliases(): void
    {
        $command = new AiArchiveMemoryCommand($this->root);
        self::assertSame('ai:archive-memory', $command->getName());
        self::assertContains('ai:archive-sdd', $command->getAliases());
        self::assertContains('sdd:archive', $command->getAliases());
        self::assertStringNotContainsString('SDD folders', $command->getDescription());
        self::assertStringContainsString('--status=A,B', $command->getHelp());
        self::assertStringContainsString('reports', $command->getHelp());
    }

    public function testReportsFolderFollowsTheRuleOfTen(): void
    {
        $this->write('memory/reports/README.md', "# Reports\n");
        // BL-028 has the highest number but the oldest date: reports order by date.
        $this->write('memory/reports/BL-028-old.md', "---\nid: \"BL-028\"\ndate: \"2026-08-01\"\n---\n# BL\n");
        $this->write('memory/reports/REP-001-old.md', "---\nid: \"REP-001\"\ndate: \"2026-10-01\"\n---\n# Old\n");
        $this->write('memory/reports/REP-002-new.md', "---\nid: \"REP-002\"\ndate: \"2026-11-24\"\n---\n# New\n");
        $this->write('memory/reports/index.md', "[a](REP-001-old.md) [b](REP-002-new.md) [c](BL-028-old.md)\n");

        self::assertSame(0, $this->archive(['--keep=1']));

        self::assertFileExists($this->root . '/memory/reports/REP-002-new.md');
        self::assertFileExists($this->root . '/memory/reports/archive/REP-001-old.md');
        self::assertFileExists($this->root . '/memory/reports/archive/BL-028-old.md');
        self::assertFileExists($this->root . '/memory/reports/README.md');
        self::assertSame("[a](archive/REP-001-old.md) [b](REP-002-new.md) [c](archive/BL-028-old.md)\n",
            $this->read('memory/reports/index.md'));
    }

    public function testFoldersOptionRestrictsTheGovernedAreas(): void
    {
        $this->write('memory/reports/REP-001-old.md', "# Old\n");
        $this->write('memory/reports/REP-002-new.md', "# New\n");
        $this->write('memory/implementation/batch-001.md', "# Old\n");
        $this->write('memory/implementation/batch-002.md', "# New\n");

        self::assertSame(0, $this->archive(['--keep=1', '--folders=implementation']));
        self::assertFileExists($this->root . '/memory/implementation/archive/batch-001.md');
        self::assertFileExists($this->root . '/memory/reports/REP-001-old.md');

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())->method('error')->with(self::stringContains('Unknown --folders'));
        self::assertSame(1, $this->archive(['--folders=decisions'], $output));
    }

    public function testStatusFilterNeverSelectsPendingFilesByNumericOrder(): void
    {
        // Numeric order alone would archive batch-001..003 (keeping 004, 005).
        $this->write('memory/implementation/batch-001.md', $this->doc('ready-for-review'));
        $this->write('memory/implementation/batch-002.md', $this->doc('HOMOLOGATED'));
        $this->write('memory/implementation/batch-003.md', "# Legacy\n\n* **Status**: `REVIEWED` — ok\n");
        $this->write('memory/implementation/batch-004.md', $this->doc('homologated'));
        $this->write('memory/implementation/batch-005.md', $this->doc('IN_PROGRESS'));

        self::assertSame(0, $this->archive(['--keep=3', '--status=HOMOLOGATED,REVIEWED', '--folders=implementation']));

        // Pending 001 and 005 are held and consume 2 of the 3 slots; the newest
        // eligible (004) keeps the last slot; eligible 002 and 003 move.
        foreach (['001', '004', '005'] as $kept) {
            self::assertFileExists($this->root . "/memory/implementation/batch-{$kept}.md");
        }
        foreach (['002', '003'] as $moved) {
            self::assertFileExists($this->root . "/memory/implementation/archive/batch-{$moved}.md");
        }
    }

    public function testStatusFilterReportsHeldFilesWhenPendingExceedTheWindow(): void
    {
        $this->write('memory/implementation/batch-001.md', $this->doc('ready-for-review'));
        $this->write('memory/implementation/batch-002.md', $this->doc('ready-for-review'));
        $this->write('memory/implementation/batch-003.md', $this->doc('HOMOLOGATED'));

        $rows = [];
        $output = $this->quietOutput();
        $output->method('table')->willReturnCallback(static function (array $headers, array $table) use (&$rows): void {
            if ($headers[0] === 'Memory Folder') {
                $rows = $table;
            }
        });

        self::assertSame(0, $this->archive(['--keep=1', '--status=HOMOLOGATED', '--folders=implementation'], $output));
        self::assertFileExists($this->root . '/memory/implementation/archive/batch-003.md');
        self::assertFileExists($this->root . '/memory/implementation/batch-001.md');
        self::assertFileExists($this->root . '/memory/implementation/batch-002.md');
        self::assertSame(['memory/implementation', '3', '2', '1', 'over the limit'], $rows[0]);

        self::assertSame(0, $this->archive(['--keep=1', '--status=HOMOLOGATED', '--folders=implementation'], $output));
        self::assertSame(['memory/implementation', '2', '2', '0', 'held by status (2)'], $rows[0]);
    }

    public function testOnlyLinksAffectedByTheMoveAreRewritten(): void
    {
        $this->write('memory/implementation/batch-001.md',
            "# Old\n[up](../00-baseline.md) [scripts](../../scripts/) [tool](../../scripts/tool.cjs) [index](BATCH-INDEX.md#top)\n");
        $this->write('memory/implementation/batch-002.md', "# New\n");
        $this->write('memory/implementation/BATCH-INDEX.md', "# Index\n");
        $this->write('memory/00-baseline.md', "# Base\n");
        $unrelated = "[moved](implementation/batch-001.md)\n"
            . "[dot](./00-baseline.md) [up](../scripts/) [dotdot](implementation/../00-baseline.md) "
            . "[CASE](Implementation/BATCH-INDEX.md) [anchor](00-baseline.md#x) [web](https://example.com/a.md)\n"
            . "`[code](implementation/batch-001.md)`\n";
        $this->write('memory/README.md', $unrelated);
        // [CASE] must stay untouched and resolvable on case-sensitive filesystems (Linux CI) too;
        // on Windows/macOS the lowercase folder already answers for it.
        if (!is_file($this->root . '/memory/Implementation/BATCH-INDEX.md')) {
            mkdir($this->root . '/memory/Implementation', 0777, true);
            $this->write('memory/Implementation/BATCH-INDEX.md', "# Case\n");
        }

        self::assertSame(0, $this->archive(['--keep=1', '--folders=implementation']));

        // The document that stayed in place changes only the link to the moved file.
        self::assertSame(str_replace('](implementation/batch-001.md)' . "\n" . '[dot]', '](implementation/archive/batch-001.md)' . "\n" . '[dot]', $unrelated),
            $this->read('memory/README.md'));
        // The moved document re-anchors every relative link, folders included (trailing slash kept).
        self::assertSame("# Old\n[up](../../00-baseline.md) [scripts](../../../scripts/) [tool](../../../scripts/tool.cjs) [index](../BATCH-INDEX.md#top)\n",
            $this->read('memory/implementation/archive/batch-001.md'));
    }

    public function testAngleBracketDestinationsOfGeneratedIndexesAreRewritten(): void
    {
        $this->write('memory/implementation/batch-001.md', "# Old\n");
        $this->write('memory/implementation/batch-002.md', "# New\n");
        $this->write('memory/implementation/index.md',
            "| BATCH-001 | [source](<batch-001.md>) |\n| BATCH-002 | [source](<batch-002.md>) |\n[broken](<missing.md>)\n");

        $orphans = [];
        $output = $this->createMock(OutputInterface::class);
        $output->method('table')->willReturnCallback(static function (array $headers, array $rows) use (&$orphans): void {
            if ($headers === ['Markdown file', 'Target']) {
                $orphans = array_column($rows, 1);
            }
        });

        // The integrity gate now sees the broken `<missing.md>` and fails.
        self::assertSame(1, $this->archive(['--keep=1', '--folders=implementation'], $output));
        self::assertSame("| BATCH-001 | [source](<archive/batch-001.md>) |\n| BATCH-002 | [source](<batch-002.md>) |\n[broken](<missing.md>)\n",
            $this->read('memory/implementation/index.md'));
        self::assertSame(['missing.md'], $orphans);
    }

    public function testLegacySddTreeStillWorksThroughTheAlias(): void
    {
        $legacy = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-req090-legacy-' . bin2hex(random_bytes(6));
        mkdir($legacy . '/sdd/human-requests', 0777, true);
        file_put_contents($legacy . '/sdd/human-requests/req-001.md', "# Old\n");
        file_put_contents($legacy . '/sdd/human-requests/req-002.md', "# New\n");
        try {
            $command = new AiArchiveMemoryCommand($legacy);
            self::assertSame(0, $command->execute(new Input(['c2f', 'ai:archive-sdd', '--keep=1']), $this->quietOutput()));
            self::assertFileExists($legacy . '/sdd/human-requests/archive/req-001.md');
        } finally {
            unlink($legacy . '/sdd/human-requests/archive/req-001.md');
            unlink($legacy . '/sdd/human-requests/req-002.md');
            rmdir($legacy . '/sdd/human-requests/archive');
            rmdir($legacy . '/sdd/human-requests');
            rmdir($legacy . '/sdd');
            rmdir($legacy);
        }
    }
}
