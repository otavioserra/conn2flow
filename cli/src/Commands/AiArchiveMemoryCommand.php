<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

/**
 * Enforces the "Rule of 10 Active Files" on the MDD memory root folders.
 *
 * Keeps only the N most recent sequenced files inside memory/human-requests/,
 * memory/implementation/ and memory/reports/ (legacy fallback: sdd/), moving the
 * older ones into their respective archive/ subfolder and deterministically
 * rewriting only the markdown links affected by those moves, so no index
 * reference is left orphaned and unrelated links keep their exact text.
 *
 * MDD (Memory Driven Development) is the evolution of SDD: `ai:archive-memory`
 * is the canonical name and `ai:archive-sdd` stays as a backward-compatible alias.
 */
final class AiArchiveMemoryCommand implements CommandInterface
{
    private const DEFAULT_KEEP = 10;

    /**
     * Matches a fenced block or an inline code span BEFORE a markdown link, so
     * documentation examples such as `[file.md](archive/file.md)` are never
     * rewritten nor reported as orphaned.
     */
    private const LINK_SCAN_PATTERN = '/(```.*?```|`[^`\n]*`)|\]\(([^)\s]+)\)/s';

    /** How many ancestor folders the link repair walks up while re-anchoring. */
    private const REPAIR_MAX_DEPTH = 4;

    /**
     * Folders governed by the Rule of 10, with the files that are never moved.
     *
     * @var array<int, array{dir: string, protected: array<int, string>, by_date?: bool}>
     */
    private const TARGET_FOLDERS = [
        [
            'dir' => 'human-requests',
            'protected' => ['CURRENT.md', 'README.md', 'INDEX.md'],
        ],
        [
            'dir' => 'implementation',
            'protected' => ['BATCH-INDEX.md', 'README.md', 'INDEX.md', 'CURRENT.md'],
        ],
        [
            // REQ-090: executor reports (REP-XXX, BL-XXX) follow the same window,
            // ordered by frontmatter date because their prefixes mix.
            'dir' => 'reports',
            'protected' => ['README.md', 'INDEX.md'],
            'by_date' => true,
        ],
    ];

    /**
     * Matches <prefix>-<sequence>[-<slug>].md (req-048.md, BATCH-106-stripe.md,
     * local-batch-002-engineering-memories.md). Files without a sequence number
     * are treated as structural and never archived.
     */
    private const SEQUENCED_FILE_PATTERN = '/^(?P<prefix>[A-Za-z][A-Za-z0-9]*(?:-[A-Za-z][A-Za-z0-9]*)*)-(?P<seq>\d+)(?P<rest>-.*)?\.md$/';

    /**
     * Only the active-pointer lines of CURRENT.md pin a file to the root.
     * CURRENT.md also carries long historic backlogs in some repositories, and
     * those references must stay archivable (their links are rewritten).
     *
     * @var array<int, string>
     */
    private const ACTIVE_POINTER_MARKERS = [
        'ponteiro ativo',
        'requisição ativa',
        'requisicao ativa',
        'active request',
        'active pointer',
        'lote relacionado',
        'lote atual',
        'lote anterior',
    ];

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'ai:archive-memory';
    }

    public function getDescription(): string
    {
        return 'Apply the Rule of 10 to MDD memory folders, archiving old requests/batches/reports and rewriting affected markdown links.';
    }

    public function getAliases(): array
    {
        // ai:archive-sdd and sdd:archive are kept for backward compatibility (SDD → MDD).
        return ['ai:archive-sdd', 'memory:archive', 'ai:archive', 'sdd:archive'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:archive-memory [options]   (alias: ai:archive-sdd)\n\n" .
               "Keeps only the most recent sequenced files in memory/human-requests/, memory/implementation/\n" .
               "and memory/reports/ (legacy fallback: sdd/). Older files are moved to the matching archive/\n" .
               "subfolder. Only the markdown links affected by the moves are rewritten (links that point to a\n" .
               "moved file, and the relative links inside each moved file); every other link keeps its exact text.\n\n" .
               "Options:\n" .
               "  --repo=PATH       Repository root to process (default: the current Core repository).\n" .
               "  --keep=N          How many sequenced files stay in each folder root (default: 10).\n" .
               "  --protect=A,B     Extra file names that must never be archived.\n" .
               "  --folders=A,B     Governed folders to process (default: " . implode(',', self::folderNames()) . ").\n" .
               "  --status=A,B      Only files whose status (frontmatter `status:` or a `**Status**:` line) is in the\n" .
               "                    list may be archived, e.g. --status=HOMOLOGATED,REVIEWED. Other files are held in\n" .
               "                    the root and occupy the active window, so pending reviews are never selected by\n" .
               "                    numeric order alone.\n" .
               "  --repair-links    Also re-anchor links already broken by earlier manual archiving.\n" .
               "  --dry-run         Show the plan without moving files or rewriting links.\n" .
               "  --verbose         List every archived file and every rewritten link.";
    }

    /**
     * @return array<int, string>
     */
    private static function folderNames(): array
    {
        return array_map(static fn (array $target): string => $target['dir'], self::TARGET_FOLDERS);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — MDD Memory Archiving (Rule of 10)');

        $repoPath = $this->resolveRepoPath($input);
        if ($repoPath === null) {
            $output->error('Repository path not found. Check the --repo option.');
            return 1;
        }

        $memoryDir = is_dir($repoPath . '/memory') ? 'memory' : 'sdd';
        $memoryPath = $repoPath . '/' . $memoryDir;
        if (!is_dir($memoryPath)) {
            $output->error("No memory/ or sdd/ folder found at: {$repoPath}");
            return 1;
        }

        $keep = $this->resolveKeep($input);
        if ($keep < 1) {
            $output->error('The --keep option must be a positive integer.');
            return 1;
        }

        $dryRun = $input->hasOption('dry-run');
        $verbose = $input->hasOption('verbose');

        $output->info('Repository: ' . $this->displayPath($repoPath));
        $output->info("Active window: {$keep} file(s) per folder root" . ($dryRun ? '  [DRY-RUN]' : ''));

        $extraProtected = $this->resolveExtraProtected($input);

        $folders = $this->resolveFolders($input);
        if ($folders === null) {
            $output->error('Unknown --folders value. Allowed: ' . implode(', ', self::folderNames()) . '.');
            return 1;
        }

        $statuses = $this->resolveStatuses($input);
        if ($statuses !== []) {
            $output->info('Archivable statuses: ' . implode(', ', $statuses));
        }

        // 1. Build the move plan for every governed folder.
        $moves = [];
        $planRows = [];

        foreach (self::TARGET_FOLDERS as $target) {
            if (!in_array($target['dir'], $folders, true)) {
                continue;
            }

            $target['dir'] = $memoryDir . '/' . $target['dir'];
            $folder = $repoPath . '/' . $target['dir'];
            if (!is_dir($folder)) {
                $planRows[] = [$target['dir'], '-', '-', '-', 'not present'];
                continue;
            }

            $protected = $this->collectProtectedNames($folder, $target['protected'], $extraProtected);
            $candidates = $this->collectSequencedFiles($folder, $protected, (bool) ($target['by_date'] ?? false));

            // Pinned requests/batches occupy slots of the active window, so the
            // folder root never exceeds --keep sequenced files in total.
            $pinned = $this->countPinnedSequencedFiles($folder, $protected);
            $budget = max(0, $keep - $pinned);
            $total = count($candidates) + $pinned;

            // --status: files outside the archivable statuses are held in the root
            // and consume the window first; only the oldest eligible files move.
            $held = 0;
            if ($statuses !== []) {
                $eligible = [];
                foreach ($candidates as $candidate) {
                    $candidate['status'] = $this->readStatus($folder . '/' . $candidate['name']);
                    if (in_array($candidate['status'], $statuses, true)) {
                        $eligible[] = $candidate;
                    } else {
                        $held++;
                    }
                }
                $candidates = $eligible;
                $budget = max(0, $budget - $held);
            }

            $toArchive = count($candidates) > $budget ? array_slice($candidates, $budget) : [];

            foreach ($toArchive as $candidate) {
                $moves[] = [
                    'folder' => $target['dir'],
                    'name' => $candidate['name'],
                    'status' => $candidate['status'] ?? $this->readStatus($folder . '/' . $candidate['name']),
                    'old' => $this->normalizePath($folder . '/' . $candidate['name']),
                    'new' => $this->normalizePath($folder . '/archive/' . $candidate['name']),
                ];
            }

            $keptInRoot = $total - count($toArchive);
            $planRows[] = [
                $target['dir'],
                (string) $total,
                (string) $keptInRoot,
                (string) count($toArchive),
                count($toArchive) > 0
                    ? 'over the limit'
                    : ($keptInRoot > $keep ? "held by status ({$held})" : 'compliant'),
            ];
        }

        $output->section('Rule of 10 Analysis');
        $output->table(
            ['Memory Folder', 'Sequenced', 'Kept in root', 'To archive', 'Status'],
            $planRows
        );

        $repairLinks = $input->hasOption('repair-links');

        if (empty($moves)) {
            $output->success('Every governed folder already respects the active window. Nothing to archive.');

            if ($repairLinks && !$dryRun) {
                $this->runRepairPass($memoryPath, $output, $verbose);
            }

            $orphans = $this->findBrokenLinks($memoryPath);
            $this->reportBrokenLinks($output, $orphans);
            return empty($orphans) ? 0 : 1;
        }

        if ($verbose || $dryRun) {
            $output->section('Files selected for archiving');
            $moveRows = [];
            foreach ($moves as $move) {
                $moveRows[] = [$move['folder'], $move['name'], $move['status'] ?: '-', $move['folder'] . '/archive/' . $move['name']];
            }
            $output->table(['Folder', 'File', 'Status', 'Destination'], $moveRows);
        }

        if ($dryRun) {
            $output->info(sprintf('Dry run: %d file(s) would be archived and their links rewritten.', count($moves)));
            return 0;
        }

        // 2. Snapshot every markdown file under memory/ (or sdd/) before moving anything.
        $markdownFiles = $this->collectMarkdownFiles($memoryPath);

        // 3. Physically move the files.
        $moveMap = [];
        $archived = 0;

        foreach ($moves as $move) {
            $archiveDir = dirname($move['new']);
            if (!is_dir($archiveDir) && !mkdir($archiveDir, 0775, true) && !is_dir($archiveDir)) {
                $output->error("Could not create archive folder: {$archiveDir}");
                return 1;
            }

            if (file_exists($move['new'])) {
                $output->warning("Skipped (already archived): {$move['folder']}/archive/{$move['name']}");
                continue;
            }

            if (!@rename($move['old'], $move['new'])) {
                $output->error("Could not move: {$move['old']}");
                return 1;
            }

            $moveMap[$this->mapKey($move['old'])] = $move['new'];
            $archived++;
        }

        // 4. Rewrite every markdown link that referenced a moved file, and
        //    re-anchor the relative links inside the moved files themselves.
        $rewrittenFiles = 0;
        $rewrittenLinks = 0;
        $rewriteRows = [];

        foreach ($markdownFiles as $oldFilePath) {
            $newFilePath = $moveMap[$this->mapKey($oldFilePath)] ?? $oldFilePath;
            if (!is_file($newFilePath)) {
                continue;
            }

            $content = file_get_contents($newFilePath);
            if ($content === false) {
                continue;
            }

            $changes = 0;
            $updated = $this->rewriteLinks($content, $oldFilePath, $newFilePath, $moveMap, $changes, $rewriteRows);

            if ($changes > 0 && $updated !== $content) {
                file_put_contents($newFilePath, $updated);
                $rewrittenFiles++;
                $rewrittenLinks += $changes;
            }
        }

        $output->section('Execution Summary');
        $output->table(
            ['Metric', 'Value'],
            [
                ['Files archived', (string) $archived],
                ['Markdown files rewritten', (string) $rewrittenFiles],
                ['Links rewritten', (string) $rewrittenLinks],
            ]
        );

        if ($verbose && !empty($rewriteRows)) {
            $output->section('Rewritten links');
            $output->table(['Markdown file', 'Old target', 'New target'], $rewriteRows);
        }

        // 5. Optional repair of links already broken by earlier manual archiving.
        if ($repairLinks) {
            $this->runRepairPass($memoryPath, $output, $verbose);
        }

        // 6. Final integrity gate: no relative markdown link may stay orphaned.
        $orphans = $this->findBrokenLinks($memoryPath);
        $this->reportBrokenLinks($output, $orphans);

        if (!empty($orphans)) {
            return 1;
        }

        $output->success(sprintf(
            '%d file(s) archived and %d link(s) rewritten with zero broken references.',
            $archived,
            $rewrittenLinks
        ));

        return 0;
    }

    // ------------------------------------------------------------------
    // Option resolution
    // ------------------------------------------------------------------

    private function resolveRepoPath(InputInterface $input): ?string
    {
        $repo = $input->getOption('repo');
        if (!is_string($repo) || $repo === '') {
            return $this->normalizePath($this->rootPath);
        }

        $resolved = realpath($repo);
        if ($resolved === false) {
            return null;
        }

        return $this->normalizePath($resolved);
    }

    private function resolveKeep(InputInterface $input): int
    {
        $keep = $input->getOption('keep', self::DEFAULT_KEEP);
        if (is_string($keep) && ctype_digit($keep)) {
            return (int) $keep;
        }

        if (is_int($keep)) {
            return $keep;
        }

        return self::DEFAULT_KEEP;
    }

    /**
     * @return array<int, string>
     */
    private function resolveExtraProtected(InputInterface $input): array
    {
        $raw = $input->getOption('protect');
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $names = array_map('trim', explode(',', $raw));

        return array_values(array_filter($names, static fn (string $name): bool => $name !== ''));
    }

    /**
     * @return array<int, string>|null Null when an unknown folder is requested.
     */
    private function resolveFolders(InputInterface $input): ?array
    {
        $raw = $input->getOption('folders');
        if (!is_string($raw) || trim($raw) === '') {
            return self::folderNames();
        }

        $requested = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $name): bool => $name !== ''));
        foreach ($requested as $name) {
            if (!in_array($name, self::folderNames(), true)) {
                return null;
            }
        }

        return $requested;
    }

    /**
     * @return array<int, string> Upper-case statuses; empty means "no status filter".
     */
    private function resolveStatuses(InputInterface $input): array
    {
        $raw = $input->getOption('status');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $statuses = array_map(static fn (string $value): string => strtoupper(trim($value, " \t\"'`")), explode(',', $raw));

        return array_values(array_unique(array_filter($statuses, static fn (string $value): bool => $value !== '')));
    }

    /**
     * Reads a document status: YAML frontmatter `status:` first, then the first
     * `**Status**:` bullet (legacy records). Returns '' when none is declared.
     */
    private function readStatus(string $file): string
    {
        $status = $this->readFrontmatterField($file, 'status');
        if ($status !== '') {
            return strtoupper($status);
        }

        $content = @file_get_contents($file, false, null, 0, 16384);
        if ($content !== false && preg_match('/^\s*[*-]\s*\*\*Status\*\*\s*:\s*`?([A-Za-z0-9_-]+)/mi', $content, $match) === 1) {
            return strtoupper($match[1]);
        }

        return '';
    }

    // ------------------------------------------------------------------
    // Planning
    // ------------------------------------------------------------------

    /**
     * Structural files plus every file the active pointer (CURRENT.md) links to.
     *
     * @param array<int, string> $baseProtected
     * @param array<int, string> $extraProtected
     * @return array<string, true>
     */
    private function collectProtectedNames(string $folder, array $baseProtected, array $extraProtected): array
    {
        $protected = [];

        foreach (array_merge($baseProtected, $extraProtected) as $name) {
            $protected[strtolower($name)] = true;
        }

        $currentFile = $folder . '/CURRENT.md';
        if (!is_file($currentFile)) {
            $currentFile = dirname($folder) . '/human-requests/CURRENT.md';
        }

        if (is_file($currentFile)) {
            foreach ((array) file($currentFile) as $line) {
                if (!$this->isActivePointerLine((string) $line)) {
                    continue;
                }

                if (preg_match_all('/[A-Za-z0-9_.-]+\.md/', (string) $line, $matches)) {
                    foreach ($matches[0] as $name) {
                        $protected[strtolower($name)] = true;
                    }
                }
            }
        }

        return $protected;
    }

    private function isActivePointerLine(string $line): bool
    {
        $normalized = mb_strtolower($line);

        foreach (self::ACTIVE_POINTER_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sequenced files in the folder root, most recent first.
     *
     * Folders that mix prefixes (reports/: REP-002 next to BL-028) are ordered by
     * the frontmatter `date:` first, because their sequence numbers are not
     * comparable; files without a date sort as the oldest. Other folders keep the
     * numeric order.
     *
     * @param array<string, true> $protected
     * @return array<int, array{name: string, seq: int, date: string}>
     */
    private function collectSequencedFiles(string $folder, array $protected, bool $byDate = false): array
    {
        $entries = scandir($folder);
        if ($entries === false) {
            return [];
        }

        $files = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (!is_file($folder . '/' . $entry)) {
                continue;
            }

            if (isset($protected[strtolower($entry)])) {
                continue;
            }

            if (!preg_match(self::SEQUENCED_FILE_PATTERN, $entry, $matches)) {
                continue;
            }

            $files[] = [
                'name' => $entry,
                'seq' => (int) $matches['seq'],
                'date' => $byDate ? $this->readFrontmatterField($folder . '/' . $entry, 'date') : '',
            ];
        }

        usort($files, static function (array $a, array $b): int {
            return strcmp($b['date'], $a['date'])
                ?: ($b['seq'] <=> $a['seq'] ?: strcmp(strtolower($b['name']), strtolower($a['name'])));
        });

        return $files;
    }

    private function readFrontmatterField(string $file, string $field): string
    {
        $content = @file_get_contents($file, false, null, 0, 16384);
        if ($content === false) {
            return '';
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        if (preg_match('/\A---\r?\n(.*?)\r?\n---\r?\n/s', $content, $frontmatter) !== 1) {
            return '';
        }

        if (preg_match('/^' . preg_quote($field, '/') . ':\s*(.+?)\s*$/mi', $frontmatter[1], $match) !== 1) {
            return '';
        }

        return trim($match[1], " \t\"'`");
    }

    /**
     * Sequenced files kept in the root by an explicit protection rule.
     *
     * @param array<string, true> $protected
     */
    private function countPinnedSequencedFiles(string $folder, array $protected): int
    {
        $entries = scandir($folder);
        if ($entries === false) {
            return 0;
        }

        $pinned = 0;

        foreach ($entries as $entry) {
            if (!is_file($folder . '/' . $entry)) {
                continue;
            }

            if (!isset($protected[strtolower($entry)])) {
                continue;
            }

            if (preg_match(self::SEQUENCED_FILE_PATTERN, $entry) === 1) {
                $pinned++;
            }
        }

        return $pinned;
    }

    /**
     * @return array<int, string>
     */
    private function collectMarkdownFiles(string $memoryPath): array
    {
        $files = [];
        $stack = [$memoryPath];

        while ($stack !== []) {
            $dir = array_pop($stack);
            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $dir . '/' . $entry;
                if (is_dir($path)) {
                    $stack[] = $path;
                    continue;
                }

                if (str_ends_with(strtolower($entry), '.md')) {
                    $files[] = $this->normalizePath($path);
                }
            }
        }

        sort($files);

        return $files;
    }

    // ------------------------------------------------------------------
    // Link rewriting
    // ------------------------------------------------------------------

    /**
     * @param array<string, string> $moveMap
     * @param array<int, array<int, string>> $rewriteRows
     */
    private function rewriteLinks(
        string $content,
        string $oldFilePath,
        string $newFilePath,
        array $moveMap,
        int &$changes,
        array &$rewriteRows
    ): string {
        $oldDir = dirname($oldFilePath);
        $newDir = dirname($newFilePath);
        $label = basename($newFilePath);

        return (string) preg_replace_callback(
            self::LINK_SCAN_PATTERN,
            function (array $match) use ($oldDir, $newDir, $moveMap, &$changes, &$rewriteRows, $label): string {
                if (($match[1] ?? '') !== '') {
                    return $match[0];
                }

                $target = $match[2] ?? '';
                // `[x](<target>)` destinations (generated index.md tables) keep their brackets.
                [$inner, $wrapped] = $this->unwrapAngleTarget($target);
                $rewritten = $this->rewriteTarget($inner, $oldDir, $newDir, $moveMap);

                if ($rewritten === null || $rewritten === $inner) {
                    return $match[0];
                }

                if ($wrapped) {
                    $rewritten = '<' . $rewritten . '>';
                }

                $changes++;
                $rewriteRows[] = [$label, $target, $rewritten];

                return '](' . $rewritten . ')';
            },
            $content
        );
    }

    /**
     * @param array<string, string> $moveMap
     */
    private function rewriteTarget(string $target, string $oldDir, string $newDir, array $moveMap): ?string
    {
        [$path, $anchor] = $this->splitAnchor($target);

        if ($path === '') {
            return null;
        }

        // file:///c:/... absolute references used by the Architect toolkits.
        if (stripos($path, 'file:///') === 0) {
            $fsPath = $this->normalizePath(rawurldecode(substr($path, strlen('file:///'))));
            $moved = $moveMap[$this->mapKey($fsPath)] ?? null;
            if ($moved === null) {
                return null;
            }

            return 'file:///' . $moved . $anchor;
        }

        // Anything that is not a plain relative POSIX path is left untouched.
        if (!$this->isRewritableRelativeTarget($target)) {
            return null;
        }

        $resolvedOld = $this->normalizePath($oldDir . '/' . rawurldecode($path));
        $movedTarget = $moveMap[$this->mapKey($resolvedOld)] ?? null;
        $documentMoved = $this->mapKey($oldDir) !== $this->mapKey($newDir);

        // REQ-090 / REP-011: only links affected by a move change. A document that
        // stayed in place keeps the exact text of every link to a file that did not
        // move (no normalization of `./`, `..`, case or trailing slashes).
        if ($movedTarget === null && !$documentMoved) {
            return null;
        }

        $resolvedNew = $movedTarget ?? $resolvedOld;

        // Moved documents also re-anchor links to folders (e.g. `../scripts/`).
        if (!file_exists($resolvedNew)) {
            return null;
        }

        $relative = $this->relativePath($newDir, $resolvedNew);
        if ($relative === null) {
            return null;
        }

        if (str_ends_with($path, '/') && !str_ends_with($relative, '/')) {
            $relative .= '/';
        }

        return $relative . $anchor;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitAnchor(string $target): array
    {
        $pos = strpos($target, '#');
        if ($pos === false) {
            return [$target, ''];
        }

        return [substr($target, 0, $pos), substr($target, $pos)];
    }

    // ------------------------------------------------------------------
    // Integrity gate
    // ------------------------------------------------------------------

    /**
     * @return array<int, array<int, string>>
     */
    private function findBrokenLinks(string $memoryPath): array
    {
        $broken = [];

        foreach ($this->collectMarkdownFiles($memoryPath) as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $dir = dirname($file);

            foreach ($this->extractLinkTargets($content) as $target) {
                if (!$this->isRewritableRelativeTarget($target)) {
                    continue;
                }

                [$path] = $this->splitAnchor($target);
                $resolved = $this->normalizePath($dir . '/' . rawurldecode($path));

                if (!file_exists($resolved)) {
                    $broken[] = [$this->displayPath($file), $target];
                }
            }
        }

        return $broken;
    }

    /**
     * Link targets outside fenced blocks and inline code spans.
     *
     * @return array<int, string>
     */
    private function extractLinkTargets(string $content): array
    {
        if (!preg_match_all(self::LINK_SCAN_PATTERN, $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $targets = [];

        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                continue;
            }

            [$target] = $this->unwrapAngleTarget($match[2] ?? '');
            if ($target !== '') {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /**
     * Splits a CommonMark `<destination>` into its inner target.
     *
     * @return array{0: string, 1: bool} Inner target and whether it was wrapped.
     */
    private function unwrapAngleTarget(string $target): array
    {
        if (strlen($target) > 2 && $target[0] === '<' && str_ends_with($target, '>')) {
            return [substr($target, 1, -1), true];
        }

        return [$target, false];
    }

    /**
     * True only for plain relative POSIX targets: URLs, absolute paths, Windows
     * paths and template placeholders are outside the tool's contract.
     */
    private function isRewritableRelativeTarget(string $target): bool
    {
        [$path] = $this->splitAnchor($target);

        if ($path === '' || str_contains($path, '\\')) {
            return false;
        }

        if (preg_match('/[{}<>$*|"]/', $path) === 1) {
            return false;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path) === 1) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            return false;
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Historic link repair
    // ------------------------------------------------------------------

    /**
     * Re-anchors relative links that were already broken by earlier manual
     * archiving, when the intended target can be located unambiguously.
     *
     * @param array<int, array<int, string>> $repairRows
     */
    private function repairBrokenLinks(string $memoryPath, array &$repairRows): int
    {
        $repaired = 0;

        foreach ($this->collectMarkdownFiles($memoryPath) as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $dir = dirname($file);
            $label = basename($file);
            $changes = 0;

            $updated = (string) preg_replace_callback(
                self::LINK_SCAN_PATTERN,
                function (array $match) use ($dir, $memoryPath, $label, &$changes, &$repairRows): string {
                    if (($match[1] ?? '') !== '') {
                        return $match[0];
                    }

                    $target = $match[2] ?? '';
                    if (!$this->isRewritableRelativeTarget($target)) {
                        return $match[0];
                    }

                    [$path, $anchor] = $this->splitAnchor($target);
                    $resolved = $this->normalizePath($dir . '/' . rawurldecode($path));

                    if (file_exists($resolved)) {
                        return $match[0];
                    }

                    $fixed = $this->findRepairCandidate($path, $dir, $memoryPath);
                    if ($fixed === null) {
                        return $match[0];
                    }

                    $relative = $this->relativePath($dir, $fixed);
                    if ($relative === null || $relative === $path) {
                        return $match[0];
                    }

                    $changes++;
                    $repairRows[] = [$label, $target, $relative . $anchor];

                    return '](' . $relative . $anchor . ')';
                },
                $content
            );

            if ($changes > 0 && $updated !== $content) {
                file_put_contents($file, $updated);
                $repaired += $changes;
            }
        }

        return $repaired;
    }

    /**
     * Deterministic candidate lookup: archive sibling, ancestor re-anchoring,
     * then a unique basename match under the selected memory/ (or sdd/) tree.
     */
    private function findRepairCandidate(string $path, string $dir, string $memoryPath): ?string
    {
        $decoded = rawurldecode($path);
        $resolved = $this->normalizePath($dir . '/' . $decoded);

        $archiveSibling = $this->normalizePath(dirname($resolved) . '/archive/' . basename($resolved));
        if (is_file($archiveSibling)) {
            return $archiveSibling;
        }

        // The file itself moved deeper: re-anchor from an ancestor folder.
        $ancestor = $dir;
        for ($depth = 0; $depth < self::REPAIR_MAX_DEPTH; $depth++) {
            $ancestor = dirname($ancestor);
            if ($ancestor === '' || $ancestor === dirname($ancestor)) {
                break;
            }

            $candidate = $this->normalizePath($ancestor . '/' . $decoded);
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // The link climbs too far up: drop one leading '../' at a time.
        $trimmed = $decoded;
        for ($depth = 0; $depth < self::REPAIR_MAX_DEPTH && str_starts_with($trimmed, '../'); $depth++) {
            $trimmed = substr($trimmed, 3);
            $candidate = $this->normalizePath($dir . '/' . $trimmed);
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $this->findUniqueByBasename($memoryPath, basename($decoded));
    }

    private function findUniqueByBasename(string $memoryPath, string $basename): ?string
    {
        if (!str_ends_with(strtolower($basename), '.md')) {
            return null;
        }

        $matches = [];

        foreach ($this->collectMarkdownFiles($memoryPath) as $file) {
            if (strcasecmp(basename($file), $basename) === 0) {
                $matches[] = $file;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function runRepairPass(string $memoryPath, OutputInterface $output, bool $verbose): void
    {
        $repairRows = [];
        $repaired = $this->repairBrokenLinks($memoryPath, $repairRows);

        if ($repaired === 0) {
            $output->info('Link repair: no previously broken relative link could be re-anchored.');
            return;
        }

        $output->success("Link repair: {$repaired} previously broken relative link(s) re-anchored.");

        if ($verbose) {
            $output->section('Repaired links');
            $output->table(['Markdown file', 'Old target', 'New target'], array_slice($repairRows, 0, 60));
        }
    }

    /**
     * @param array<int, array<int, string>> $orphans
     */
    private function reportBrokenLinks(OutputInterface $output, array $orphans): void
    {
        if (empty($orphans)) {
            $output->success('Link integrity gate: no orphaned relative markdown links under the selected memory/ or sdd/ tree.');
            return;
        }

        $output->section('Broken relative links detected');
        $output->table(['Markdown file', 'Target'], array_slice($orphans, 0, 40));
        $output->error(sprintf('%d broken relative link(s) found under the selected memory/ or sdd/ tree.', count($orphans)));
    }

    // ------------------------------------------------------------------
    // Path helpers
    // ------------------------------------------------------------------

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $isUnc = str_starts_with($path, '//');
        $prefix = '';

        if (preg_match('#^([A-Za-z]:)#', $path, $matches) === 1) {
            $prefix = $matches[1];
            $path = substr($path, strlen($prefix));
        }

        $isAbsolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($segments !== [] && end($segments) !== '..') {
                    array_pop($segments);
                } elseif (!$isAbsolute) {
                    $segments[] = '..';
                }
                continue;
            }

            $segments[] = $segment;
        }

        $result = implode('/', $segments);

        if ($isUnc) {
            return '//' . $result;
        }

        if ($isAbsolute) {
            return $prefix . '/' . $result;
        }

        return $prefix . $result;
    }

    private function relativePath(string $fromDir, string $toPath): ?string
    {
        $from = explode('/', $this->normalizePath($fromDir));
        $to = explode('/', $this->normalizePath($toPath));

        if (strcasecmp($from[0], $to[0]) !== 0) {
            return null;
        }

        while ($from !== [] && $to !== [] && strcasecmp($from[0], $to[0]) === 0) {
            array_shift($from);
            array_shift($to);
        }

        if ($to === []) {
            return null;
        }

        $relative = array_merge(array_fill(0, count($from), '..'), $to);

        return implode('/', $relative);
    }

    private function mapKey(string $path): string
    {
        return strtolower($this->normalizePath($path));
    }

    private function displayPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
