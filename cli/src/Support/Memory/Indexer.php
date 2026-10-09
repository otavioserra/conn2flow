<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Memory;

use RuntimeException;
use Throwable;

/** Scalar YAML dialect and byte-identical indexes to mdd_client.core.indexer. */
final class Indexer
{
    private const HEADER = '/\A(?:\xEF\xBB\xBF)?---\r?\n(.*?)\r?\n---(?:\r?\n|\z)/s';
    private const INFRA = ['index.md', 'readme.md', 'current.md', 'batch-index.md', 'decision-log.md', 'validation-checklist.md'];
    private string $root;
    private string $memory;

    public function __construct(string $root)
    {
        $this->root = realpath($root) ?: throw new RuntimeException('Repository not found.');
        $this->memory = $this->root . DIRECTORY_SEPARATOR . 'memory';
        $this->safe($this->memory);
        if (!is_dir($this->memory)) {
            throw new RuntimeException('Memory folder not found.');
        }
    }

    /** Refuse traversal, symlinks and Windows junctions via realpath comparison. */
    private function safe(string $path): string
    {
        $absolute = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $prefix = $this->memory . DIRECTORY_SEPARATOR;
        if ($absolute !== $this->memory && !str_starts_with($absolute, $prefix)) {
            throw new RuntimeException('Target must be inside memory/.');
        }
        foreach (explode(DIRECTORY_SEPARATOR, substr($absolute, strlen($this->root) + 1)) as $part) {
            if ($part === '..' || $part === '.') {
                throw new RuntimeException('Relative traversal is not writable.');
            }
        }
        for ($node = $absolute; $node !== $this->root; $node = dirname($node)) {
            if (is_link($node)) {
                throw new RuntimeException('Linked path is not writable.');
            }
            $real = realpath($node);
            if ($real !== false && strcasecmp($real, $node) !== 0) {
                throw new RuntimeException('Linked path is not writable.');
            }
        }
        return $absolute;
    }

    private function read(string $path): string
    {
        $this->safe($path);
        $data = file_get_contents($path);
        if ($data === false || !preg_match('//u', $data)) {
            throw new RuntimeException('Unreadable UTF-8 document: ' . $path);
        }
        return $data;
    }

    /** @return array{meta: array<string,string>, body: string, has: bool, errors: list<string>, header: string} */
    public static function parse(string $content): array
    {
        if (!preg_match(self::HEADER, $content, $match)) {
            $body = preg_replace('/\A\xEF\xBB\xBF/', '', $content);
            return ['meta' => [], 'body' => $body, 'has' => false, 'errors' => preg_match('/\A---\r?\n/', $body) ? ['Unclosed frontmatter'] : [], 'header' => ''];
        }
        $lines = explode("\n", str_replace("\r\n", "\n", $match[1]));
        $meta = [];
        $errors = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }
            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_-]*):[ \t]*(.*)$/', $line, $entry)) {
                $errors[] = 'Unsupported YAML mapping';
                continue;
            }
            [$unused, $key, $raw] = $entry;
            if (array_key_exists($key, $meta)) {
                $errors[] = 'Duplicate field: ' . $key;
            }
            try {
                if (preg_match('/^[|>][-+]?(?:[ \t]+#.*)?$/', $raw)) {
                    $block = [];
                    while ($i + 1 < count($lines) && (trim($lines[$i + 1]) === '' || preg_match('/^[ \t]/', $lines[$i + 1]))) {
                        $block[] = $lines[++$i];
                    }
                    $meta[$key] = self::blockValue($raw, $block);
                } else {
                    $meta[$key] = self::scalar($raw);
                }
            } catch (Throwable $e) {
                $errors[] = 'Unsupported value: ' . $key;
            }
        }
        return ['meta' => $meta, 'body' => substr($content, strlen($match[0])), 'has' => true, 'errors' => $errors, 'header' => $match[1]];
    }

    private static function scalar(string $raw): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, '#')) {
            return '';
        }
        if (str_starts_with($raw, '"')) {
            if (!preg_match('/^("(?:[^"\\\\]|\\\\.)*")[ \t]*(?:#.*)?$/s', $raw, $match)) {
                throw new RuntimeException('Invalid quoted scalar');
            }
            $value = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            if (!is_string($value)) {
                throw new RuntimeException('Invalid quoted scalar');
            }
            return $value;
        }
        if (str_starts_with($raw, "'")) {
            if (!preg_match("/^'((?:[^']|'')*)'[ \t]*(?:#.*)?$/", $raw, $match)) {
                throw new RuntimeException('Invalid quoted scalar');
            }
            return str_replace("''", "'", $match[1]);
        }
        if (preg_match('/^[\[\{&*!|>@`,\]\}]/', $raw) || preg_match('/^[-?:](?:\s|$)/', $raw)) {
            throw new RuntimeException('Unsupported YAML scalar');
        }
        $value = rtrim(preg_split('/[ \t]+#/', $raw, 2)[0]);
        if (preg_match('/:(?:\s|$)|[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value)) {
            throw new RuntimeException('Invalid plain YAML scalar');
        }
        return $value;
    }

    private static function blockValue(string $raw, array $block): string
    {
        $nonempty = array_values(array_filter($block, static fn($s) => trim($s) !== ''));
        $indent = $nonempty ? strlen($nonempty[0]) - strlen(ltrim($nonempty[0], ' ')) : 0;
        foreach ($nonempty as $line) {
            if (str_starts_with($line, "\t") || strlen($line) - strlen(ltrim($line, ' ')) < $indent) {
                throw new RuntimeException('Invalid block indentation');
            }
        }
        $parts = array_map(static fn($s) => trim($s) === '' ? '' : substr($s, $indent), $block);
        if (!$parts) {
            return '';
        }
        $value = '';
        foreach ($parts as $n => $part) {
            $value .= $part;
            $sep = "\n";
            if ($raw[0] === '>' && $n + 1 < count($parts) && $part !== '' && !preg_match('/^[ \t]/', $part)) {
                $next = $parts[$n + 1];
                if ($next !== '' && !preg_match('/^[ \t]/', $next)) {
                    $sep = ' ';
                } elseif ($next === '') {
                    $following = array_values(array_filter(array_slice($parts, $n + 2), static fn($s) => $s !== ''))[0] ?? '';
                    if ($following !== '' && !preg_match('/^[ \t]/', $following)) {
                        $sep = '';
                    }
                }
            }
            $value .= $sep;
        }
        $chomp = $raw[1] ?? '';
        return $chomp === '-' ? rtrim($value, "\n") : ($chomp === '+' ? $value : (trim($value, "\n") !== '' ? rtrim($value, "\n") . "\n" : ''));
    }

    /** @return array<string,string> */
    public function metadata(string $path, ?string $content = null): array
    {
        $parsed = self::parse($content ?? $this->read($path));
        $body = $parsed['body'];
        $stem = pathinfo($path, PATHINFO_FILENAME);
        $title = preg_match('/^# +(.+)$/m', $body, $h) ? trim($h[1]) : $stem;
        $id = preg_match('/^([A-Za-z][A-Za-z0-9_-]*-\d+)\b/', $title, $m) ? strtoupper($m[1]) : strtoupper($stem);
        $archived = preg_match('~(?:^|[/\\\\])archive(?:-[^/\\\\]*)?(?:[/\\\\]|$)~', $path);
        $status = preg_match('/^\s*(?:[*-]\s*)?(?:\*\*)?Status(?:\*\*)?\s*:\s*(.+)$/mi', $body, $s) ? trim($s[1], " `*.\r\n") : ($archived ? 'archived' : 'indexed');
        $paragraph = [];
        $inCode = false;
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '```') || str_starts_with($line, '~~~')) {
                $inCode = !$inCode;
                continue;
            }
            if (!$inCode && $line !== '' && !preg_match('/^(?:[#|>*+-]|\d+[.)]\s|<!--|---)/', $line)) {
                $paragraph[] = $line;
            } elseif ($paragraph) {
                break;
            }
        }
        $summary = implode(' ', $paragraph);
        preg_match_all('/./us', $summary, $characters);
        if (count($characters[0]) > 120) {
            $summary = implode('', array_slice($characters[0], 0, 117)) . '...';
        }
        return array_replace(['id' => $id, 'title' => $title, 'status' => $status, 'date' => '', 'author' => '', 'target_repo' => basename($this->root), 'summary_short' => $summary, 'summary_medium' => $summary], $parsed['meta']);
    }

    /** @return list<string> */
    private function files(string $directory): array
    {
        $this->safe($directory);
        $result = [];
        foreach (scandir($directory) ?: [] as $name) {
            if (str_starts_with($name, '.')) {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) {
                // Linked directories are skipped during discovery, refused when explicit.
                if (is_link($path) || strcasecmp(realpath($path) ?: '', $path) !== 0) {
                    continue;
                }
                $result = array_merge($result, $this->files($path));
            } elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'md') {
                $result[] = $this->safe($path);
            }
        }
        return $result;
    }

    public function resolve(string $target, bool $directory = false): string
    {
        $absolute = preg_match('~^(?:[A-Za-z]:[/\\\\]|[/\\\\])~', $target);
        $candidates = $absolute ? [$target] : [$this->root . '/' . $target, $this->memory . '/' . $target];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $path = $this->safe($candidate);
                if (is_dir($path) !== $directory || (!$directory && strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'md')) {
                    throw new RuntimeException('Invalid target type.');
                }
                return $path;
            }
        }
        if ($directory || str_contains($target, '/') || str_contains($target, '\\')) {
            throw new RuntimeException('Target not found: ' . $target);
        }
        $matches = array_values(array_filter($this->files($this->memory), static fn($p) => strcasecmp(basename($p), $target) === 0 || strcasecmp(pathinfo($p, PATHINFO_FILENAME), $target) === 0));
        if (count($matches) !== 1) {
            throw new RuntimeException('Target must be unique: ' . $target . ' (' . count($matches) . ' matches)');
        }
        return $matches[0];
    }

    private static function cell(string $value): string
    {
        return strtr(trim(preg_replace('/\s+/u', ' ', $value)), ['&' => '&amp;', '|' => '&#124;', '<' => '&lt;', '>' => '&gt;', '[' => '&#91;', ']' => '&#93;']);
    }

    public function render(string $directory, array $overrides = []): string
    {
        $this->safe($directory);
        $rows = ['# Index — ' . basename($directory), '', '| ID | Title | Executive summary | Relative link | Status |', '| --- | --- | --- | --- | --- |'];
        $children = [];
        $names = scandir($directory) ?: throw new RuntimeException('Cannot read directory.');
        sort($names, SORT_STRING);
        foreach ($names as $name) {
            if (str_starts_with($name, '.')) {
                continue;
            }
            $path = $this->safe($directory . DIRECTORY_SEPARATOR . $name);
            if (is_dir($path)) {
                if (is_file($path . '/index.md')) {
                    $this->safe($path . '/index.md');
                    $children[] = '- [' . self::cell($name) . '](<' . rawurlencode($name) . '/index.md>)';
                }
            } elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'md' && !in_array(strtolower($name), self::INFRA, true)) {
                $meta = $this->metadata($path, $overrides[$path] ?? null);
                $rows[] = '| ' . self::cell($meta['id']) . ' | ' . self::cell($meta['title']) . ' | ' . self::cell($meta['summary_short']) . ' | [source](<' . rawurlencode($name) . '>) | ' . self::cell($meta['status']) . ' |';
            }
        }
        if ($children) {
            $rows = array_merge($rows, ['', '## Directories', ''], $children);
        }
        return implode("\n", $rows) . "\n";
    }

    private function write(string $path, string $content): void
    {
        $this->safe($path);
        $temp = tempnam(dirname($path), '.mdd-');
        if ($temp === false) {
            throw new RuntimeException('Cannot stage atomic write.');
        }
        try {
            if (file_put_contents($temp, $content) !== strlen($content)) {
                throw new RuntimeException('Incomplete atomic write.');
            }
            if (file_exists($path)) {
                chmod($temp, fileperms($path) & 0777);
            }
            if (!rename($temp, $path)) {
                throw new RuntimeException('Cannot replace document: ' . $path);
            }
        } finally {
            if (file_exists($temp)) {
                unlink($temp);
            }
        }
    }

    private function locked(callable $operation): mixed
    {
        $path = $this->safe($this->memory . '/.mdd.lock');
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('Another MDD mutation owns memory/.mdd.lock (or lock cannot be created).');
        }
        try {
            fwrite($handle, (string)getmypid());
            fclose($handle);
            return $operation();
        } finally {
            unlink($path);
        }
    }

    /** @return list<string> */
    public function index(?string $target = null): array
    {
        return $this->locked(function () use ($target) {
            $directories = $target !== null ? [$this->resolve($target, true)] : array_unique(array_map('dirname', array_filter($this->files($this->memory), static fn($p) => basename($p) === 'index.md')));
            sort($directories, SORT_STRING);
            $result = [];
            foreach ($directories as $directory) {
                $path = $directory . DIRECTORY_SEPARATOR . 'index.md';
                $this->write($path, $this->render($directory));
                $result[] = $path;
            }
            return $result;
        });
    }

    public function get(string $target, ?string $field = null): array|string
    {
        $meta = $this->metadata($this->resolve($target));
        if ($field !== null && !array_key_exists($field, $meta)) {
            throw new RuntimeException('Unknown field: ' . $field);
        }
        return $field === null ? $meta : $meta[$field];
    }

    public function set(string $target, array $updates): string
    {
        if (!$updates) {
            throw new RuntimeException('Supply --field=value.');
        }
        foreach ($updates as $key => $value) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $key) || !is_string($value) || !preg_match('//u', $value)) {
                throw new RuntimeException('Supply valid string metadata fields.');
            }
        }
        return $this->locked(function () use ($target, $updates) {
            $path = $this->resolve($target);
            $before = $this->read($path);
            $parsed = self::parse($before);
            if ($parsed['errors']) {
                throw new RuntimeException('Cannot safely mutate frontmatter: ' . implode('; ', $parsed['errors']));
            }
            $newline = str_contains($before, "\r\n") ? "\r\n" : "\n";
            $encode = static fn($s) => json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if ($parsed['has']) {
                $header = str_replace("\r\n", "\n", $parsed['header']);
                foreach ($updates as $key => $value) {
                    $entry = $key . ': ' . $encode($value);
                    $lines = explode("\n", $header);
                    $found = false;
                    foreach ($lines as $start => $line) {
                        if (preg_match('/^' . preg_quote($key, '/') . ':[ \t]*(.*)$/', $line, $match)) {
                            $end = $start + 1;
                            if (preg_match('/^[|>][-+]?(?:[ \t]+#.*)?$/', $match[1])) {
                                while ($end < count($lines) && (trim($lines[$end]) === '' || preg_match('/^[ \t]/', $lines[$end]))) {
                                    $end++;
                                }
                            }
                            array_splice($lines, $start, $end - $start, [$entry]);
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $lines[] = $entry;
                    }
                    $header = implode("\n", $lines);
                }
            } else {
                $meta = array_replace($this->metadata($path, $before), $updates);
                $header = implode("\n", array_map(static fn($key) => $key . ': ' . $encode($meta[$key]), array_keys($meta)));
            }
            $bom = str_starts_with($before, "\xEF\xBB\xBF") ? "\xEF\xBB\xBF" : '';
            $after = $bom . '---' . $newline . str_replace("\n", $newline, $header) . $newline . '---' . $newline . $parsed['body'];
            $directory = dirname($path);
            $rendered = $this->render($directory, [$path => $after]);
            $this->write($path, $after);
            try {
                $this->write($directory . '/index.md', $rendered);
            } catch (Throwable $e) {
                $this->write($path, $before);
                throw $e;
            }
            return $path;
        });
    }
}
