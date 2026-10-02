<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * HTML da referência de funções de uma biblioteca no site (req-207).
 *
 * O bloco gerado por `docs:extract` é uma lista Markdown, boa para o GitHub e para o editor. No
 * site ela virava uma lista corrida, sem como achar uma função nem distinguir parâmetro de retorno.
 * Aqui o mesmo bloco é lido de volta e vira: filtro, índice de funções e um cartão por função, com
 * âncora própria, assinatura copiável, parâmetros com tipo e link para a linha no repositório.
 *
 * As classes ficam explícitas no HTML, como no resto das docs, para o compilador de CSS do projeto
 * enxergá-las. O filtro é comportamento do template da página (`data-docs-fn-*`); sem JavaScript o
 * índice e os cartões continuam funcionando.
 */
final class FunctionReferenceHtml
{
    private const LABELS = [
        'pt-br' => [
            'source' => 'Gerado a partir de', 'count' => '%d funções', 'filter' => 'Filtrar funções…', 'index' => 'Índice das funções',
            'showing' => '%d de %d', 'code' => 'ver no código', 'line' => 'linha', 'params' => 'Parâmetros', 'return' => 'Retorno',
            'empty' => 'Nenhuma função com esse nome.', 'copy' => 'Copiar assinatura', 'anchor' => 'Link para',
        ],
        'en' => [
            'source' => 'Generated from', 'count' => '%d functions', 'filter' => 'Filter functions…', 'index' => 'Function index',
            'showing' => '%d of %d', 'code' => 'view source', 'line' => 'line', 'params' => 'Parameters', 'return' => 'Returns',
            'empty' => 'No function with that name.', 'copy' => 'Copy signature', 'anchor' => 'Link to',
        ],
    ];

    private const FILTER = 'w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-gray-500 focus:border-[rgb(29,170,198)]/60 focus:outline-none';
    private const CHIP = 'rounded-full border border-white/10 bg-white/5 px-3 py-1 font-mono text-xs text-gray-300 hover:border-[rgb(29,170,198)]/60 hover:text-white transition-colors';
    private const CARD = 'scroll-mt-24 rounded-2xl border border-white/10 bg-[rgb(15,25,45)] p-5 sm:p-6';
    private const SOURCE = 'inline-flex shrink-0 items-center gap-1.5 rounded-full border border-white/10 px-3 py-1 text-xs text-gray-400 hover:border-[rgb(29,170,198)]/60 hover:text-white transition-colors';
    private const TYPE = 'rounded-md border border-violet-400/30 bg-violet-400/10 px-1.5 py-0.5 font-mono text-xs text-violet-200';
    private const LABEL = 'text-xs font-bold uppercase tracking-wider text-[rgb(29,170,198)]';

    /**
     * Lê o bloco de `LibraryReference::render()` de volta para dados.
     *
     * @return array{source: string, functions: list<array{name: string, signature: string, params: list<array{name: string, type: string, description: string}>, returnType: string, line: int, href: string, description: string, returnDescription: string}>}
     */
    public static function parse(string $block): array
    {
        $block = str_replace([LibraryReference::START, LibraryReference::END, "\r\n"], ['', '', "\n"], $block);
        $source = preg_match('/`([^`]+\.php)`/', $block, $m) ? $m[1] : '';
        $functions = [];
        $current = null;
        $inParams = false;
        foreach (explode("\n", $block) as $line) {
            if (preg_match('/^- `(.+)` — \[[^\]]*?(\d+)\]\(([^)]+)\)\s*$/u', $line, $m)) {
                if ($current !== null) {
                    $functions[] = $current;
                }
                $current = self::signature($m[1]) + ['line' => (int)$m[2], 'href' => $m[3], 'description' => '', 'returnDescription' => ''];
                $inParams = false;
                continue;
            }
            if ($current === null) {
                continue;
            }
            if (preg_match('/^  - `(\$[^`]+)`:\s*(.*)$/u', $line, $m)) {
                foreach ($current['params'] as $i => $param) {
                    if ($param['name'] === $m[1]) {
                        $current['params'][$i]['description'] = $m[2];
                    }
                }
                continue;
            }
            if (preg_match('/^  (Parâmetros|Parameters):\s*$/u', $line)) {
                $inParams = true;
                continue;
            }
            if (preg_match('/^  (Retorno|Returns):\s*(.*)$/u', $line, $m)) {
                $current['returnDescription'] = $m[2];
                $inParams = false;
                continue;
            }
            if (!$inParams && str_starts_with($line, '  ') && trim($line) !== '') {
                $current['description'] = trim($current['description'] . ' ' . trim($line));
            }
        }
        if ($current !== null) {
            $functions[] = $current;
        }

        return ['source' => $source, 'functions' => $functions];
    }

    /**
     * `nome(tipo $a, tipo $b = padrão): retorno` em partes.
     *
     * @return array{name: string, signature: string, params: list<array{name: string, type: string, description: string}>, returnType: string}
     */
    private static function signature(string $signature): array
    {
        $name = $signature;
        $inside = '';
        $returnType = '';
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\((.*)\)(?::\s*(.+))?$/s', $signature, $m)) {
            [$name, $inside, $returnType] = [$m[1], $m[2], trim($m[3] ?? '')];
        }
        $params = [];
        $depth = 0;
        $part = '';
        // Vírgula dentro de um padrão (`array('a', 'b')`, `[1, 2]`) não separa parâmetros.
        foreach (str_split($inside . ',') as $char) {
            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth--;
            }
            if ($char === ',' && $depth === 0) {
                if (preg_match('/^\s*(.*?)\s*((?:&|\.\.\.)*\$[A-Za-z_][A-Za-z0-9_]*)/s', $part, $m)) {
                    $params[] = ['name' => ltrim($m[2], '&.'), 'type' => trim($m[1]), 'description' => ''];
                }
                $part = '';
                continue;
            }
            $part .= $char;
        }

        return ['name' => $name, 'signature' => $signature, 'params' => $params, 'returnType' => $returnType];
    }

    /**
     * @param callable(string): string $resolveHref link do bloco (relativo ao repositório) → URL final
     */
    public static function render(string $block, string $lang, callable $resolveHref): string
    {
        $labels = self::LABELS[$lang] ?? self::LABELS['en'];
        $data = self::parse($block);
        $functions = $data['functions'];
        if ($functions === []) {
            return '';
        }
        $total = count($functions);

        $chips = '';
        $cards = '';
        foreach ($functions as $fn) {
            $id = 'fn-' . MarkdownRenderer::slug(str_replace('_', '-', $fn['name']));
            $name = self::e($fn['name']);
            $chips .= '<a href="#' . $id . '" data-docs-fn-chip="' . $name . '" class="' . self::CHIP . '">' . $name . '</a>';

            $rows = '';
            foreach ($fn['params'] as $param) {
                $rows .= '<div class="flex flex-col gap-1 border-t border-white/5 py-3 sm:flex-row sm:gap-6">'
                    . '<dt class="flex shrink-0 flex-wrap items-center gap-2 sm:w-56"><code class="font-mono text-sm text-[rgb(125,220,240)]">' . self::e($param['name']) . '</code>'
                    . ($param['type'] !== '' ? '<span class="' . self::TYPE . '">' . self::e($param['type']) . '</span>' : '') . '</dt>'
                    . '<dd class="min-w-0 text-sm leading-6 text-gray-300">' . self::inline($param['description']) . '</dd></div>';
            }
            $params = $rows === '' ? '' : '<div class="mt-5"><p class="' . self::LABEL . '">' . $labels['params'] . '</p><dl class="mt-2">' . $rows . '</dl></div>';

            $return = '';
            if ($fn['returnType'] !== '' || $fn['returnDescription'] !== '') {
                $return = '<div class="mt-5"><p class="' . self::LABEL . '">' . $labels['return'] . '</p>'
                    . '<div class="mt-2 flex flex-col gap-1 border-t border-white/5 py-3 sm:flex-row sm:gap-6">'
                    . '<div class="shrink-0 sm:w-56">' . ($fn['returnType'] !== '' ? '<span class="' . self::TYPE . '">' . self::e($fn['returnType']) . '</span>' : '') . '</div>'
                    . '<div class="min-w-0 text-sm leading-6 text-gray-300">' . self::inline($fn['returnDescription']) . '</div></div></div>';
            }

            $cards .= '<article id="' . $id . '" data-docs-fn="' . $name . '" class="' . self::CARD . '">'
                . '<header class="flex flex-wrap items-center justify-between gap-3">'
                . '<h3 class="min-w-0 break-all font-mono text-lg font-semibold text-white"><a href="#' . $id . '" class="hover:text-[rgb(29,170,198)] transition-colors" aria-label="' . $labels['anchor'] . ' ' . $name . '">' . $name . '</a></h3>'
                . '<a href="' . self::e($resolveHref($fn['href'])) . '" target="_blank" rel="noopener" class="' . self::SOURCE . '">' . $labels['code'] . ' · ' . $labels['line'] . ' ' . $fn['line'] . ' ↗</a>'
                . '</header>'
                . '<div class="' . DocsTheme::CODE_FRAME . ' mt-4 mb-0"><pre class="' . DocsTheme::ELEMENTS['pre'] . ' whitespace-pre-wrap break-words"><code class="' . DocsTheme::ELEMENTS['pre code'] . '">' . self::e($fn['signature']) . '</code></pre>'
                . '<button type="button" class="' . DocsTheme::COPY_BUTTON . '" data-docs-copy aria-label="' . $labels['copy'] . '">' . DocsTheme::COPY_ICON . '</button></div>'
                . ($fn['description'] !== '' ? '<p class="mt-4 leading-7 text-gray-300">' . self::inline($fn['description']) . '</p>' : '')
                . $params . $return
                . '</article>';
        }

        $html = '<div class="not-prose my-6" data-docs-fn-root>'
            . '<p class="text-sm text-gray-400">' . $labels['source'] . ' <code class="' . DocsTheme::ELEMENTS['code'] . '">' . self::e($data['source']) . '</code> · ' . sprintf($labels['count'], $total) . '</p>'
            . '<div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">'
            . '<input type="search" data-docs-fn-filter placeholder="' . $labels['filter'] . '" aria-label="' . $labels['filter'] . '" class="' . self::FILTER . '">'
            . '<span class="shrink-0 text-xs text-gray-500" data-docs-fn-count data-docs-fn-count-template="' . $labels['showing'] . '">' . sprintf($labels['showing'], $total, $total) . '</span>'
            . '</div>'
            . '<nav class="mt-4 flex flex-wrap gap-2" aria-label="' . $labels['index'] . '">' . $chips . '</nav>'
            . '<p class="mt-6 hidden text-sm text-gray-500" data-docs-fn-empty>' . $labels['empty'] . '</p>'
            . '<div class="mt-8 space-y-5">' . $cards . '</div>'
            . '</div>';

        return MarkdownRenderer::protectGestorMarkers($html);
    }

    /** Texto com `código` em linha; o restante escapado. */
    private static function inline(string $text): string
    {
        $out = '';
        foreach (preg_split('/(`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            $out .= (strlen($part) > 2 && $part[0] === '`' && substr($part, -1) === '`')
                ? '<code class="' . DocsTheme::ELEMENTS['code'] . '">' . self::e(substr($part, 1, -1)) . '</code>'
                : self::e($part);
        }

        return $out;
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
