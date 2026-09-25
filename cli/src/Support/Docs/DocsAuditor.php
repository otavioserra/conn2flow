<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Ranking de defasagem da documentação (req-177 §3.1, rotina do FEAT-014 §4.4).
 *
 * Cada item acumula diagnósticos com severidade; a pontuação (erro = 10, aviso = 3) ordena
 * o que a próxima passagem de agente deve atacar primeiro.
 */
final class DocsAuditor
{
    private const SCORE = ['error' => 10, 'warning' => 3, 'info' => 0];
    private const REQUIRED = ['title', 'description', 'section', 'verified_at'];

    private DocsTree $tree;

    /** @var callable(list<string>): array{code: int, stdout: string} */
    private $git;

    /** @param callable(list<string>): array{code: int, stdout: string}|null $git */
    public function __construct(DocsTree $tree, ?callable $git = null)
    {
        $this->tree = $tree;
        $this->git = $git ?? self::defaultGit($tree->rootPath());
    }

    /**
     * @return array{items: list<array{item: string, score: int, issues: list<array{severity: string, message: string}>}>, legacy: array<string, int>, totals: array{error: int, warning: int, info: int}}
     */
    public function audit(): array
    {
        $items = [];
        foreach (DocsTree::LANGS as $lang) {
            foreach ($this->tree->newDocs($lang) as $rel) {
                $issues = $this->auditDoc($lang, $rel);
                $items[] = $this->item($lang . ':' . $rel, $issues);
            }
        }
        foreach ($this->coverageGaps() as $gap) {
            $items[] = $this->item('missing:' . $gap, [['severity' => 'warning', 'message' => 'Documentação inexistente (pt-br e en).']]);
        }

        usort($items, static fn (array $a, array $b): int => [$b['score'], $a['item']] <=> [$a['score'], $b['item']]);

        $legacy = [];
        foreach (DocsTree::LANGS as $lang) {
            $legacy[$lang] = count($this->tree->files($lang)) - count($this->tree->newDocs($lang));
        }

        $totals = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($items as $it) {
            foreach ($it['issues'] as $issue) {
                $totals[$issue['severity']]++;
            }
        }

        return ['items' => $items, 'legacy' => $legacy, 'totals' => $totals];
    }

    /** @return list<array{severity: string, message: string}> */
    public function auditDoc(string $lang, string $rel): array
    {
        $issues = [];
        $content = $this->tree->read($lang, $rel);
        $fm = Frontmatter::parse($content);

        if (!$fm['has']) {
            return [['severity' => 'error', 'message' => 'Sem frontmatter.']];
        }
        foreach ($fm['errors'] as $e) {
            $issues[] = ['severity' => 'error', 'message' => 'Frontmatter: ' . $e];
        }
        $meta = $fm['meta'];

        foreach (self::REQUIRED as $key) {
            if (!isset($meta[$key]) || $meta[$key] === '' || $meta[$key] === []) {
                $issues[] = ['severity' => 'error', 'message' => "Chave obrigatória ausente: {$key}."];
            }
        }
        $expected = DocsTree::expectedSection($rel);
        if (isset($meta['section']) && $meta['section'] !== $expected) {
            $issues[] = ['severity' => 'error', 'message' => "section '{$meta['section']}' diverge da pasta ('{$expected}')."];
        }
        $visibility = $meta['visibility'] ?? 'public';
        if (!in_array($visibility, ['public', 'restricted'], true)) {
            $issues[] = ['severity' => 'error', 'message' => "visibility inválida: '{$visibility}'."];
        }

        $other = $lang === 'pt-br' ? 'en' : 'pt-br';
        if (!$this->tree->exists($other, $rel)) {
            $issues[] = ['severity' => 'error', 'message' => "Sem par em {$other}."];
        }

        $sources = is_array($meta['sources'] ?? null) ? array_map('strval', $meta['sources']) : [];
        if ($expected === 'reference' && $sources === []) {
            $issues[] = ['severity' => 'error', 'message' => 'Doc de referência sem sources.'];
        }
        $existing = [];
        foreach ($sources as $src) {
            if (file_exists($this->tree->rootPath() . '/' . $src)) {
                $existing[] = $src;
            } else {
                $issues[] = ['severity' => 'error', 'message' => "Fonte inexistente: {$src}."];
            }
        }

        $verified = is_string($meta['verified_at'] ?? null) ? $meta['verified_at'] : '';
        if ($verified !== '') {
            if (($this->git)(['rev-parse', '--verify', '--quiet', $verified . '^{commit}'])['code'] !== 0) {
                $issues[] = ['severity' => 'error', 'message' => "verified_at '{$verified}' não é um commit conhecido."];
            } elseif ($existing !== []) {
                $log = ($this->git)(array_merge(['log', '--format=%h', $verified . '..HEAD', '--'], $existing));
                $n = count(array_filter(explode("\n", trim($log['stdout']))));
                if ($n > 0) {
                    $issues[] = ['severity' => 'warning', 'message' => "Fontes mudaram em {$n} commit(s) desde verified_at."];
                }
            }
        }

        foreach ($this->brokenLinks($lang, $rel, $fm['body']) as $link) {
            $issues[] = ['severity' => 'error', 'message' => "Link quebrado: {$link}."];
        }

        if (str_starts_with($rel, 'reference/libraries/') && basename($rel) !== 'index.md') {
            $issues = array_merge($issues, $this->auditLibrary($lang, $rel, $content));
        }

        return $issues;
    }

    /** @return list<array{severity: string, message: string}> */
    private function auditLibrary(string $lang, string $rel, string $content): array
    {
        $lib = basename($rel, '.md');
        $sourceRel = 'gestor/bibliotecas/' . $lib . '.php';
        $sourceAbs = $this->tree->rootPath() . '/' . $sourceRel;
        if (!is_file($sourceAbs)) {
            return [['severity' => 'error', 'message' => "Biblioteca {$sourceRel} não existe."]];
        }
        $functions = PhpFunctionExtractor::extract((string)file_get_contents($sourceAbs));
        $current = LibraryReference::current($content);
        if ($current === null) {
            return [['severity' => 'warning', 'message' => 'Sem bloco c2f:extract (rode docs:extract).']];
        }

        $issues = [];
        $expected = LibraryReference::render($functions, $sourceRel, $this->tree->repoRelative($lang, $rel), $lang);
        if ($current !== $expected) {
            $issues[] = ['severity' => 'warning', 'message' => 'Bloco c2f:extract desatualizado (rode docs:extract).'];
        }

        $prose = LibraryReference::withoutBlock($content);
        $unexplained = [];
        foreach ($functions as $fn) {
            if (!preg_match('/\b' . preg_quote($fn['name'], '/') . '\b/', $prose)) {
                $unexplained[] = $fn['name'];
            }
        }
        if ($unexplained !== []) {
            $issues[] = [
                'severity' => 'warning',
                'message' => count($unexplained) . ' função(ões) sem explicação no texto: ' . implode(', ', array_slice($unexplained, 0, 5)) . (count($unexplained) > 5 ? ', …' : '') . '.',
            ];
        }

        return $issues;
    }

    /**
     * Links Markdown relativos para `.md` que não existem.
     *
     * @return list<string>
     */
    private function brokenLinks(string $lang, string $rel, string $body): array
    {
        $body = preg_replace('/```.*?```/s', '', $body) ?? $body;
        $broken = [];
        if (!preg_match_all('/\]\(([^)\s]+\.md)(?:#[^)]*)?\)/', $body, $m)) {
            return [];
        }
        foreach (array_unique($m[1]) as $target) {
            if (preg_match('#^[a-z]+://#i', $target)) {
                continue;
            }
            $resolved = self::resolve(dirname($rel), $target);
            if ($resolved === null || !$this->tree->exists($lang, $resolved)) {
                $broken[] = $target;
            }
        }

        return $broken;
    }

    /** Resolve `a/b` + `../c.md` → `a/c.md`; null se sair de docs/. */
    public static function resolve(string $baseDir, string $target): ?string
    {
        $parts = $baseDir === '.' || $baseDir === '' ? [] : explode('/', $baseDir);
        foreach (explode('/', $target) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if ($parts === []) {
                    return null;
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $seg;
        }

        return implode('/', $parts);
    }

    /** @return list<string> caminhos relativos a docs/ que deveriam existir */
    public function coverageGaps(): array
    {
        $gaps = [];
        $root = $this->tree->rootPath();
        foreach (glob($root . '/gestor/bibliotecas/*.php') ?: [] as $lib) {
            $rel = 'reference/libraries/' . basename($lib, '.php') . '.md';
            if (!$this->tree->exists('pt-br', $rel) && !$this->tree->exists('en', $rel)) {
                $gaps[] = $rel;
            }
        }
        foreach (glob($root . '/gestor/modulos/*', GLOB_ONLYDIR) ?: [] as $mod) {
            $rel = 'reference/modules/' . basename($mod) . '.md';
            if (!$this->tree->exists('pt-br', $rel) && !$this->tree->exists('en', $rel)) {
                $gaps[] = $rel;
            }
        }
        sort($gaps, SORT_STRING);

        return $gaps;
    }

    /**
     * @param list<array{severity: string, message: string}> $issues
     * @return array{item: string, score: int, issues: list<array{severity: string, message: string}>}
     */
    private function item(string $name, array $issues): array
    {
        $score = 0;
        foreach ($issues as $i) {
            $score += self::SCORE[$i['severity']];
        }

        return ['item' => $name, 'score' => $score, 'issues' => $issues];
    }

    /** @return callable(list<string>): array{code: int, stdout: string} */
    private static function defaultGit(string $cwd): callable
    {
        return static function (array $args) use ($cwd): array {
            $proc = proc_open(array_merge(['git'], $args), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
            if (!is_resource($proc)) {
                return ['code' => 1, 'stdout' => ''];
            }
            $out = (string)stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return ['code' => proc_close($proc), 'stdout' => $out];
        };
    }
}
