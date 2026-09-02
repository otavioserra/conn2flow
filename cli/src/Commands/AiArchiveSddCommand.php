<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

/**
 * Enforces the "Rule of 10 Active Files" on the SDD root folders.
 *
 * Keeps only the N most recent sequenced files inside sdd/human-requests/ and
 * sdd/implementation/, moving the older ones into their respective archive/
 * subfolder and deterministically rewriting every markdown link that pointed to
 * the old location, so no index reference is left orphaned.
 */
final class AiArchiveSddCommand implements CommandInterface
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
     * @var array<int, array{dir: string, protected: array<int, string>}>
     */
    private const TARGET_FOLDERS = [
        [
            'dir' => 'sdd/human-requests',
            'protected' => ['CURRENT.md', 'README.md', 'INDEX.md'],
        ],
        [
            'dir' => 'sdd/implementation',
            'protected' => ['BATCH-INDEX.md', 'README.md', 'INDEX.md', 'CURRENT.md'],
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
        return 'ai:archive-sdd';
    }

    public function getDescription(): string
    {
        return 'Apply the Rule of 10 to SDD folders, archiving old requests/batches and rewriting markdown links.';
    }

    public function getAliases(): array
    {
        return ['sdd:archive', 'ai:archive'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:archive-sdd [options]\n\n" .
               "Keeps only the most recent sequenced files in sdd/human-requests/ and sdd/implementation/.\n" .
               "Older files are moved to the matching archive/ subfolder and every markdown link that\n" .
               "referenced them (in BATCH-INDEX.md, VALIDATION-CHECKLIST.md, DECISION-LOG.md, CURRENT.md\n" .
               "and any other .md under sdd/) is rewritten to the new path.\n\n" .
               "Options:\n" .
               "  --repo=PATH       Repository root to process (default: the current Core repository).\n" .
               "  --keep=N          How many sequenced files stay in each folder root (default: 10).\n" .
               "  --protect=A,B     Extra file names that must never be archived.\n" .
               "  --repair-links    Also re-anchor links already broken by earlier manual archiving.\n" .
               "  --dry-run         Show the plan without moving files or rewriting links.\n" .
               "  --verbose         List every archived file and every rewritten link.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — SDD Archiving (Rule of 10)');

        $repoPath = $this->resolveRepoPath($input);
        if ($repoPath === null) {
            $output->error('Repository path not found. Check the --repo option.');
            return 1;
        }

        $sddPath = $repoPath . '/sdd';
        if (!is_dir($sddPath)) {
            $output->error("No sdd/ folder found at: {$repoPath}");
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

        // 1. Build the move plan for every governed folder.
        $moves = [];
        $planRows = [];

        foreach (self::TARGET_FOLDERS as $target) {
            $folder = $repoPath . '/' . $target['dir'];
            if (!is_dir($folder)) {
                $planRows[] = [$target['dir'], '-', '-', '-', 'not present'];
                continue;
            }

            $protected = $this->collectProtectedNames($folder, $target['protected'], $extraProtected);
            $candidates = $this->collectSequencedFiles($folder, $protected);

            // Pinned requests/batches occupy slots of the active window, so the
            // folder root never exceeds --keep sequenced files in total.
            $pinned = $this->countPinnedSequencedFiles($folder, $protected);
            $budget = max(0, $keep - $pinned);

            $total = count($candidates) + $pinned;
            $toArchive = count($candidates) > $budget ? array_slice($candidates, $budget) : [];

            foreach ($toArchive as $candidate) {
                $moves[] = [
                    'folder' => $target['dir'],
                    'name' => $candidate['name'],
                    'old' => $this->normalizePath($folder . '/' . $candidate['name']),
                    'new' => $this->normalizePath($folder . '/archive/' . $candidate['name']),
                ];
            }

            $planRows[] = [
                $target['dir'],
                (string) $total,
                (string) min($total, $keep),
                (string) count($toArchive),
                count($toArchive) > 0 ? 'over the limit' : 'compliant',
            ];
        }

        $output->section('Rule of 10 Analysis');
        $output->table(
            ['SDD Folder', 'Sequenced', 'Kept in root', 'To archive', 'Status'],
            $planRows
        );

        $repairLinks = $input->hasOption('repair-links');

        if (empty($moves)) {
            $output->success('Every governed folder already respects the active window. Nothing to archive.');

            if ($repairLinks && !$dryRun) {
                $this->runRepairPass($sddPath, $output, $verbose);
            }

            $orphans = $this->findBrokenLinks($sddPath);
            $this->reportBrokenLinks($output, $orphans);
            return empty($orphans) ? 0 : 1;
        }

        if ($verbose || $dryRun) {
            $output->section('Files selected for archiving');
            $moveRows = [];
            foreach ($moves as $move) {
                $moveRows[] = [$move['folder'], $move['name'], $move['folder'] . '/archive/' . $move['name']];
            }
            $output->table(['Folder', 'File', 'Destination'], $moveRows);
        }

        if ($dryRun) {
            $output->info(sprintf('Dry run: %d file(s) would be archived and their links rewritten.', count($moves)));
            return 0;
        }

        // 2. Snapshot every markdown file under sdd/ before moving anything.
        $markdownFiles = $this->collectMarkdownFiles($sddPath);

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
            $this->runRepairPass($sddPath, $output, $verbose);
        }

        // 6. Final integrity gate: no relative markdown link may stay orphaned.
        $orphans = $this->findBrokenLinks($sddPath);
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
     * @param array<string, true> $protected
     * @return array<int, array{name: string, seq: int}>
     */
    private function collectSequencedFiles(string $folder, array $protected): array
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

            $files[] = ['name' => $entry, 'seq' => (int) $matches['seq']];
        }

        usort($files, static function (array $a, array $b): int {
            return $b['seq'] <=> $a['seq'] ?: strcmp(strtolower($b['name']), strtolower($a['name']));
        });

        return $files;
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
    private function collectMarkdownFiles(string $sddPath): array
    {
        $files = [];
        $stack = [$sddPath];

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
                $rewritten = $this->rewriteTarget($target, $oldDir, $newDir, $moveMap);

                if ($rewritten === null || $rewritten === $target) {
                    return $match[0];
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
        $resolvedNew = $moveMap[$this->mapKey($resolvedOld)] ?? $resolvedOld;

        if (!is_file($resolvedNew)) {
            return null;
        }

        $relative = $this->relativePath($newDir, $resolvedNew);
        if ($relative === null) {
            return null;
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
    private function findBrokenLinks(string $sddPath): array
    {
        $broken = [];

        foreach ($this->collectMarkdownFiles($sddPath) as $file) {
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

            $target = $match[2] ?? '';
            if ($target !== '') {
                $targets[] = $target;
            }
        }

        return $targets;
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
    private function repairBrokenLinks(string $sddPath, array &$repairRows): int
    {
        $repaired = 0;

        foreach ($this->collectMarkdownFiles($sddPath) as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $dir = dirname($file);
            $label = basename($file);
            $changes = 0;

            $updated = (string) preg_replace_callback(
                self::LINK_SCAN_PATTERN,
                function (array $match) use ($dir, $sddPath, $label, &$changes, &$repairRows): string {
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

                    $fixed = $this->findRepairCandidate($path, $dir, $sddPath);
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
     * then a unique basename match under sdd/.
     */
    private function findRepairCandidate(string $path, string $dir, string $sddPath): ?string
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

        return $this->findUniqueByBasename($sddPath, basename($decoded));
    }

    private function findUniqueByBasename(string $sddPath, string $basename): ?string
    {
        if (!str_ends_with(strtolower($basename), '.md')) {
            return null;
        }

        $matches = [];

        foreach ($this->collectMarkdownFiles($sddPath) as $file) {
            if (strcasecmp(basename($file), $basename) === 0) {
                $matches[] = $file;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function runRepairPass(string $sddPath, OutputInterface $output, bool $verbose): void
    {
        $repairRows = [];
        $repaired = $this->repairBrokenLinks($sddPath, $repairRows);

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
            $output->success('Link integrity gate: no orphaned relative markdown links under sdd/.');
            return;
        }

        $output->section('Broken relative links detected');
        $output->table(['Markdown file', 'Target'], array_slice($orphans, 0, 40));
        $output->error(sprintf('%d broken relative link(s) found under sdd/.', count($orphans)));
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
