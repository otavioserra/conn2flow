<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\AiArchiveSddCommand;
use Conn2Flow\Cli\Commands\AiPruneMemoriesCommand;
use Conn2Flow\Cli\Commands\AiSyncCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Contracts\OutputInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php',
    'Console/Input.php', 'Commands/BaseProcessCommand.php', 'Commands/AiArchiveSddCommand.php',
    'Commands/AiPruneMemoriesCommand.php', 'Commands/AiSyncCommand.php'] as $file) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $file;
}

final class MemoryDualReq266Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-req266-' . bin2hex(random_bytes(6));
        mkdir($this->root);
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

    public static function trees(): array
    {
        return ['memory' => ['memory', false], 'legacy' => ['sdd', false], 'both' => ['memory', true]];
    }

    #[DataProvider('trees')]
    public function testArchiveDryRunMoveAndLinkRepair(string $tree, bool $both): void
    {
        foreach ($both ? ['memory', 'sdd'] : [$tree] as $folder) {
            mkdir($this->root . '/' . $folder . '/human-requests', 0777, true);
            mkdir($this->root . '/' . $folder . '/implementation', 0777, true);
            file_put_contents($this->root . '/' . $folder . '/human-requests/req-001.md', "# Old\n");
            file_put_contents($this->root . '/' . $folder . '/human-requests/req-002.md', "# New\n");
            file_put_contents($this->root . '/' . $folder . '/implementation/BATCH-001.md', "# Old\n");
            file_put_contents($this->root . '/' . $folder . '/implementation/BATCH-002.md', "# New\n");
            file_put_contents($this->root . '/' . $folder . '/README.md',
                "[Request](human-requests/req-001.md)\n[Batch](implementation/BATCH-001.md)\n");
        }
        // --repo must determine the selected tree even when the command root has none.
        $command = new AiArchiveSddCommand($this->root . '/unused');
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::never())->method('error');
        $output->expects(self::never())->method('warning');
        $args = ['c2f', 'ai:archive-sdd', '--repo=' . $this->root, '--keep=1'];
        self::assertSame(0, $command->execute(new Input([...$args, '--dry-run']), $output));
        self::assertFileExists($this->root . '/' . $tree . '/human-requests/req-001.md');
        self::assertDirectoryDoesNotExist($this->root . '/' . $tree . '/human-requests/archive');

        self::assertSame(0, $command->execute(new Input($args), $output));
        foreach (['human-requests/req', 'implementation/BATCH'] as $prefix) {
            [$folder, $name] = explode('/', $prefix);
            self::assertFileDoesNotExist($this->root . '/' . $tree . '/' . $prefix . '-001.md');
            self::assertFileExists($this->root . '/' . $tree . '/' . $folder . '/archive/' . $name . '-001.md');
            self::assertFileExists($this->root . '/' . $tree . '/' . $prefix . '-002.md');
        }
        self::assertSame("[Request](human-requests/archive/req-001.md)\n[Batch](implementation/archive/BATCH-001.md)\n",
            file_get_contents($this->root . '/' . $tree . '/README.md'));
        if ($both) {
            self::assertFileExists($this->root . '/sdd/human-requests/req-001.md');
            self::assertFileExists($this->root . '/sdd/implementation/BATCH-001.md');
        }
    }

    public function testPrunePrefersMemoryFileAndFallsBackToLegacyFile(): void
    {
        mkdir($this->root . '/memory');
        mkdir($this->root . '/sdd');
        $legacy = $this->root . '/sdd/MEMORIA-ENGENHARIA-EXECUCAO.md';
        $memory = $this->root . '/memory/MEMORIA-ENGENHARIA-EXECUCAO.md';
        file_put_contents($legacy, str_repeat("old\n", 300));
        $command = new AiPruneMemoriesCommand($this->root);
        $input = new Input(['c2f', 'ai:prune-memories']);
        $critical = $this->createMock(OutputInterface::class);
        $critical->expects(self::once())->method('warning')->with(self::stringContains('mandatory ceiling'));
        self::assertSame(1, $command->execute($input, $critical));

        file_put_contents($memory, "# Healthy\n");
        $healthy = $this->createMock(OutputInterface::class);
        $healthy->expects(self::never())->method('warning');
        $healthy->expects(self::once())->method('success')->with(self::stringContains('healthy'));
        self::assertSame(0, $command->execute($input, $healthy));
        self::assertSame("# Healthy\n", file_get_contents($memory));
        self::assertSame(str_repeat("old\n", 300), file_get_contents($legacy));
    }

    public function testSyncRequiresAll44SkillsAcrossTheFiveKits(): void
    {
        $names = array_map('basename', glob(CONN2FLOW_ROOT . '/.gemini/skills/*', GLOB_ONLYDIR) ?: []);
        self::assertCount(44, $names);
        foreach (['.claude', '.cursor', '.gemini', '.github', '.codex'] as $kit) {
            foreach ($names as $name) {
                $folder = $this->root . '/' . $kit . '/skills/' . $name;
                mkdir($folder, 0777, true);
                file_put_contents($folder . '/SKILL.md', "# ⚡ Gatilho Obrigatório\n- **TRIGGER**: Fixture.\n");
            }
        }
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::never())->method('warning');
        $output->expects(self::once())->method('table')->willReturnCallback(static function (array $headers, array $rows): void {
            self::assertCount(5, $rows);
            foreach ($rows as $row) {
                self::assertSame(['44', '44/44', '44', '✔ Complete'], array_slice($row, 1));
            }
        });
        $command = new AiSyncCommand($this->root);
        $input = new Input(['c2f', 'ai:sync']);
        self::assertSame(0, $command->execute($input, $output));

        unlink($this->root . '/.codex/skills/c2f-ai-features/SKILL.md');
        $missing = $this->createMock(OutputInterface::class);
        $missing->expects(self::once())->method('warning')->with(self::stringContains('.codex/skills/c2f-ai-features'));
        self::assertSame(1, $command->execute($input, $missing));
    }
}
