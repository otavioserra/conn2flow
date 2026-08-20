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
        return 'Execute Memory Gardening on MEMORIA-ENGENHARIA-EXECUCAO.md (35KB alert, 50KB max ceiling).';
    }

    public function getAliases(): array
    {
        return ['ai:gardening', 'sdd:prune'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:prune-memories\n\n" .
               "Validates that sdd/MEMORIA-ENGENHARIA-EXECUCAO.md is kept under 50KB / 150 lines (alert at 35KB / 100 lines).";
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

        if ($size > 51200 || $lines > 150) {
            $output->warning("Memory file exceeds the 50KB / 150 lines ceiling ({$size}B, {$lines} lines). Please prune older completed tasks to ~15KB (12-15 tasks).");
            return 1;
        }

        if ($size > 35840 || $lines > 100) {
            $output->info("Memory file is approaching limit ({$size}B, {$lines} lines). Consider scheduling memory pruning soon.");
            return 0;
        }

        $output->success("Memory file is healthy ({$size}B, {$lines} lines) and within the 35KB-50KB ceiling!");
        return 0;
    }
}
