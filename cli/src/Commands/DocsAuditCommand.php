<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\Docs\DocsAuditor;
use Conn2Flow\Cli\Support\Docs\DocsTree;

/**
 * `c2f docs:audit` — ranking de defasagem da documentação (req-177).
 */
final class DocsAuditCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'docs:audit';
    }

    public function getDescription(): string
    {
        return 'Rank documentation drift against the code (frontmatter, sources, language pairs, coverage).';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docs:audit [--limit=N] [--json] [--strict] [--source=<dir>]\n\n"
            . "Audits ai-workspace/<lang>/docs/ (guides, concepts, reference, whats-new) and ranks items by\n"
            . "score (error = 10, warning = 3). Also lists libraries and modules without documentation and\n"
            . "counts legacy docs still waiting for migration.\n\n"
            . "  --limit=N   show only the N highest-scoring items (default 30; 0 = all)\n"
            . "  --json      print the full report as JSON\n"
            . "  --strict    exit 1 when any error-level issue exists\n"
            . "  --source    directory containing <lang>/docs (default: <core>/ai-workspace)";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getOption('source');
        $tree = new DocsTree($this->rootPath, is_string($source) ? $source : null);
        $report = (new DocsAuditor($tree))->audit();

        if ($input->hasOption('json')) {
            $output->writeln((string)json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($input, $report);
        }

        $output->title('Conn2Flow — Documentation Audit');
        $limit = (int)($input->getOption('limit', 30));
        $items = array_values(array_filter($report['items'], static fn (array $i): bool => $i['score'] > 0));
        $shown = $limit > 0 ? array_slice($items, 0, $limit) : $items;

        $rows = [];
        foreach ($shown as $it) {
            $rows[] = [
                (string)$it['score'],
                $it['item'],
                implode(' | ', array_map(static fn (array $i): string => strtoupper($i['severity'][0]) . ': ' . $i['message'], $it['issues'])),
            ];
        }
        if ($rows !== []) {
            $output->table(['Score', 'Item', 'Issues'], $rows);
        }

        $clean = count($report['items']) - count($items);
        $output->info(sprintf(
            '%d item(s) with issues (%d shown), %d clean doc(s). Errors: %d, warnings: %d.',
            count($items),
            count($shown),
            $clean,
            $report['totals']['error'],
            $report['totals']['warning']
        ));
        $output->info(sprintf('Legacy docs awaiting migration: pt-br %d, en %d.', $report['legacy']['pt-br'] ?? 0, $report['legacy']['en'] ?? 0));

        return $this->exitCode($input, $report);
    }

    /** @param array{totals: array{error: int}} $report */
    private function exitCode(InputInterface $input, array $report): int
    {
        return $input->hasOption('strict') && $report['totals']['error'] > 0 ? 1 : 0;
    }
}
