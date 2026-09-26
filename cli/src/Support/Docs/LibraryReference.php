<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Bloco `<!-- c2f:extract:start -->…<!-- c2f:extract:end -->` das docs de biblioteca (req-177 §2.3).
 *
 * Lista, não tabela: assinaturas com union types (`string|array`) quebrariam a coluna de uma
 * tabela Markdown. O link de linha é relativo à raiz do repositório, para funcionar no GitHub e
 * no editor; o `docs:build` o reescreve para a URL do repositório no site.
 */
final class LibraryReference
{
    public const START = '<!-- c2f:extract:start -->';
    public const END = '<!-- c2f:extract:end -->';

    private const LABELS = [
        'pt-br' => ['intro' => 'Referência gerada a partir de `%s` por `c2f docs:extract` — %d funções. Não edite dentro deste bloco.', 'line' => 'linha', 'params' => 'Parâmetros', 'return' => 'Retorno'],
        'en' => ['intro' => 'Reference generated from `%s` by `c2f docs:extract` — %d functions. Do not edit inside this block.', 'line' => 'line', 'params' => 'Parameters', 'return' => 'Returns'],
    ];

    /**
     * @param list<array{name: string, line: int, params: list<array<string, mixed>>, return: string}> $functions
     * @param string $sourceRel caminho da biblioteca relativo à raiz do repositório
     * @param string $docRel    caminho da doc relativo à raiz do repositório
     */
    public static function render(array $functions, string $sourceRel, string $docRel, string $lang): string
    {
        $labels = self::LABELS[$lang] ?? self::LABELS['en'];
        $toRoot = str_repeat('../', substr_count(str_replace('\\', '/', $docRel), '/'));
        $lines = [self::START, '', sprintf($labels['intro'], $sourceRel, count($functions)), ''];
        foreach ($functions as $fn) {
            $lines[] = '- `' . PhpFunctionExtractor::signatureText($fn) . '` — ['
                . $labels['line'] . ' ' . $fn['line'] . '](' . $toRoot . $sourceRel . '#L' . $fn['line'] . ')';
            if (($fn['description'] ?? '') !== '') {
                $lines[] = '  ' . $fn['description'];
            }
            if (($fn['paramDescriptions'] ?? []) !== []) {
                $lines[] = '  ' . $labels['params'] . ':';
                foreach ($fn['paramDescriptions'] as $name => $description) {
                    $lines[] = '  - `' . $name . '`: ' . $description;
                }
            }
            if (($fn['returnDescription'] ?? '') !== '') {
                $lines[] = '  ' . $labels['return'] . ': ' . $fn['returnDescription'];
            }
        }
        $lines[] = '';
        $lines[] = self::END;

        return implode("\n", $lines);
    }

    /** Conteúdo atual do bloco, ou null quando a doc não o tem. */
    public static function current(string $content): ?string
    {
        $content = str_replace("\r\n", "\n", $content);
        $a = strpos($content, self::START);
        $b = strpos($content, self::END);
        if ($a === false || $b === false || $b < $a) {
            return null;
        }

        return substr($content, $a, $b + strlen(self::END) - $a);
    }

    /** Substitui o bloco; se não houver, anexa ao fim. */
    public static function replace(string $content, string $block): string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $normalized = str_replace("\r\n", "\n", $content);
        $current = self::current($normalized);
        $normalized = $current === null
            ? rtrim($normalized, "\n") . "\n\n" . $block . "\n"
            : str_replace($current, $block, $normalized);

        return $eol === "\n" ? $normalized : str_replace("\n", $eol, $normalized);
    }

    /** Texto da doc sem o bloco gerado (onde as funções precisam ser explicadas). */
    public static function withoutBlock(string $content): string
    {
        $current = self::current($content);

        return $current === null ? $content : str_replace($current, '', str_replace("\r\n", "\n", $content));
    }
}
