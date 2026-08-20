<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AiPruneMemoriesCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'ai:prune-memories';
    }

    public function getDescription(): string
    {
        return 'Execute Memory Gardening on MEMORIA-ENGENHARIA-EXECUCAO.md (<5KB ceiling).';
    }

    public function getAliases(): array
    {
        return ['ai:gardening', 'sdd:prune'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:prune-memories\n\n" .
               "Validates that sdd/MEMORIA-ENGENHARIA-EXECUCAO.md is kept under 50 lines / 5KB.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — SDD Memory Gardening');
        $memFile = $this->rootPath . '/sdd/MEMORIA-ENGENHARIA-EXECUCAO.md';

        if (!file_exists($memFile)) {
            $output->warning("Memory file not found at: {$memFile}");
            return 0;
        }

        $size = filesize($memFile);
        $lines = count(file($memFile));

        $output->info("Current Memory File: {$size} bytes ({$lines} lines)");

        if ($size > 5120 || $lines > 50) {
            $output->warning("Memory file exceeds the 5KB / 50 lines ceiling ({$size}B, {$lines} lines). Please prune older completed tasks.");
            return 1;
        }

        $output->success("Memory file is healthy ({$size}B, {$lines} lines) and within the 5KB ceiling!");
        return 0;
    }
}
