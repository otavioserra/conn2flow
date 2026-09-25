<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Frontmatter das docs (req-177 §2.2): subconjunto de YAML suficiente para o contrato —
 * `chave: valor` escalar e listas `- item` sob uma chave vazia. Sem dependência externa:
 * o CLI não usa Composer e o contrato não precisa de mais que isso.
 */
final class Frontmatter
{
    /**
     * @return array{has: bool, meta: array<string, mixed>, body: string, bodyLine: int, errors: list<string>}
     */
    public static function parse(string $content): array
    {
        $content = str_replace("\r\n", "\n", $content);
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }

        if (!preg_match('/\A---\n(.*?)\n---(?:\n|\z)/s', $content, $m)) {
            return ['has' => false, 'meta' => [], 'body' => $content, 'bodyLine' => 1, 'errors' => []];
        }

        $meta = [];
        $errors = [];
        $currentList = null;
        foreach (explode("\n", $m[1]) as $n => $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }
            if (preg_match('/^\s+-\s*(.*)$/', $line, $item)) {
                if ($currentList === null) {
                    $errors[] = 'Linha ' . ($n + 2) . ': item de lista sem chave.';
                    continue;
                }
                $meta[$currentList][] = self::scalar($item[1]);
                continue;
            }
            if (!preg_match('/^([a-z_]+):\s*(.*)$/', $line, $kv)) {
                $errors[] = 'Linha ' . ($n + 2) . ": não reconhecida ('" . trim($line) . "').";
                continue;
            }
            if ($kv[2] === '') {
                $meta[$kv[1]] = [];
                $currentList = $kv[1];
            } else {
                $meta[$kv[1]] = self::scalar($kv[2]);
                $currentList = null;
            }
        }

        $body = substr($content, strlen($m[0]));

        return [
            'has' => true,
            'meta' => $meta,
            'body' => $body,
            'bodyLine' => substr_count($m[0], "\n") + 1,
            'errors' => $errors,
        ];
    }

    private static function scalar(string $raw): string|int
    {
        $raw = trim($raw);
        if (preg_match('/^"(.*)"$/', $raw, $q) || preg_match("/^'(.*)'$/", $raw, $q)) {
            return $q[1];
        }
        if (preg_match('/^-?\d+$/', $raw)) {
            return (int)$raw;
        }

        return $raw;
    }
}
