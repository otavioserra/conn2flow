<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AiPruneMemoriesCommand extends BaseProcessCommand
{
    private const WARNING_BYTES = 50 * 1024;
    private const WARNING_LINES = 200;
    private const CRITICAL_BYTES = 75 * 1024;
    private const CRITICAL_LINES = 300;

    public function getName(): string
    {
        return 'ai:prune-memories';
    }

    public function getDescription(): string
    {
        return 'Validate Memory Gardening thresholds (50KB warning, 75KB mandatory ceiling).';
    }

    public function getAliases(): array
    {
        return ['ai:gardening', 'sdd:prune'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:prune-memories\n\n" .
               "Validates sdd/MEMORIA-ENGENHARIA-EXECUCAO.md with a 50KB / 200-line warning, a 75KB / 300-line mandatory pruning ceiling, and a ~25KB post-pruning target.";
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

        if ($size >= self::CRITICAL_BYTES || $lines >= self::CRITICAL_LINES) {
            $output->warning("Memory file reached the 75KB / 300-line mandatory ceiling ({$size}B, {$lines} lines). Prune to ~25KB while preserving 20-25 recent tasks and learnings.");
            return 1;
        }

        if ($size >= self::WARNING_BYTES || $lines >= self::WARNING_LINES) {
            $output->info("Memory file reached the 50KB / 200-line preventive warning ({$size}B, {$lines} lines). Schedule gardening; do not prune automatically before the mandatory ceiling.");
            return 0;
        }

        $output->success("Memory file is healthy ({$size}B, {$lines} lines). Pruning below 50KB / 200 lines is prohibited.");
        return 0;
    }
}
