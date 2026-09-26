<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

require_once dirname(__DIR__, 3) . '/lib/parsedown/Parsedown.php';

/**
 * Markdown → HTML com classes Tailwind explícitas (req-178).
 *
 * Estende o Parsedown (cli/lib/parsedown, MIT) só no ponto `element()`, por onde passa todo
 * elemento: é ali que entram as classes do tema, as âncoras dos títulos, os callouts
 * `> [!NOTE]` e a reescrita de links. Classes explícitas (e não `@tailwindcss/typography`)
 * garantem que o compilador de CSS do projeto enxergue cada utilitário no HTML do recurso.
 *
 * Os links são resolvidos por um callback do builder: ele conhece a árvore de docs, a URL do
 * site e o repositório — o renderer não.
 */
final class MarkdownRenderer extends \Parsedown
{
    /** Marcador trocado por `@[[pagina#url-raiz]]@` depois do escape dos marcadores do Gestor. */
    public const URL_ROOT_TOKEN = "\x01C2F_URL_RAIZ\x01";

    private const CALLOUTS = ['NOTE', 'TIP', 'IMPORTANT', 'WARNING', 'CAUTION'];

    /** @var array<string, string> */
    private array $theme;

    /** @var array<string, array{box: string, title: string}> */
    private array $calloutTheme;

    /** @var array<string, string> */
    private array $calloutLabels;

    /** @var callable(string): ?string */
    private $linkResolver;

    /** @var list<array{level: int, id: string, text: string}> */
    private array $toc = [];

    /** @var array<string, int> */
    private array $usedIds = [];

    private bool $inPre = false;

    /**
     * @param array<string, string> $calloutLabels rótulos por tipo (NOTE → "Nota")
     * @param callable(string): ?string $linkResolver href original → href final (null = link quebrado)
     */
    public function __construct(array $calloutLabels, callable $linkResolver)
    {
        $this->theme = DocsTheme::ELEMENTS;
        $this->calloutTheme = DocsTheme::CALLOUTS;
        $this->calloutLabels = $calloutLabels;
        $this->linkResolver = $linkResolver;
        $this->setSafeMode(true);
    }

    /**
     * @return array{html: string, toc: list<array{level: int, id: string, text: string}>}
     */
    public function render(string $markdown): array
    {
        $this->toc = [];
        $this->usedIds = [];
        $html = $this->text($markdown);

        return ['html' => self::protectGestorMarkers($html), 'toc' => $this->toc];
    }

    /**
     * O runtime do Gestor interpreta `@[[grupo#var]]@` em qualquer HTML de página. Docs que
     * MOSTRAM esses marcadores (em código ou texto) teriam o exemplo trocado ou apagado; a
     * arroba vira entidade — o navegador exibe `@`, o Gestor não reconhece o marcador.
     */
    public static function protectGestorMarkers(string $html): string
    {
        $html = str_replace(['@[[', ']]@'], ['&#64;[[', ']]&#64;'], $html);

        return str_replace(self::URL_ROOT_TOKEN, '@[[pagina#url-raiz]]@', $html);
    }

    /**
     * Uma linha em branco encerra a citação. O Parsedown juntava dois blocos `>` separados por
     * linha em branco num só, e um `> [!NOTE]` logo depois de um `> [!WARNING]` sumia dentro dele.
     *
     * @param array<string, mixed> $Line
     * @param array<string, mixed> $Block
     */
    protected function blockQuoteContinue($Line, array $Block)
    {
        if (isset($Block['interrupted'])) {
            return null;
        }

        return parent::blockQuoteContinue($Line, $Block);
    }

    /** @param array<string, mixed> $Element */
    protected function element(array $Element)
    {
        $name = $Element['name'] ?? '';

        if ($name === 'blockquote') {
            $Element = $this->callout($Element);
            $name = $Element['name'];
        }

        if (in_array($name, ['h2', 'h3'], true) && isset($Element['text']) && is_string($Element['text'])) {
            $plain = self::plain($Element['text']);
            $id = $this->uniqueId(self::slug($plain));
            $Element['attributes']['id'] = $id;
            $this->toc[] = ['level' => (int)$name[1], 'id' => $id, 'text' => $plain];
        }

        if ($name === 'a' && isset($Element['attributes']['href'])) {
            $href = (string)$Element['attributes']['href'];
            $resolved = ($this->linkResolver)($href);
            $Element['attributes']['href'] = $resolved ?? $href;
            if (preg_match('#^https?://#i', $Element['attributes']['href'])) {
                $Element['attributes']['target'] = '_blank';
                $Element['attributes']['rel'] = 'noopener';
            }
        }

        if ($name === 'pre') {
            $this->inPre = true;
            if (isset($Element['text']['name']) && $Element['text']['name'] === 'code') {
                $lang = '';
                if (isset($Element['text']['attributes']['class']) && preg_match('/language-(\S+)/', (string)$Element['text']['attributes']['class'], $m)) {
                    $lang = $m[1];
                }
                $Element['text']['attributes']['class'] = $this->theme['pre code'];
                if ($lang !== '') {
                    $Element['attributes']['data-lang'] = $lang;
                }
            }
        }

        if (isset($this->theme[$name]) && !($name === 'code' && $this->inPre)) {
            $current = $Element['attributes']['class'] ?? '';
            $Element['attributes']['class'] = trim($this->theme[$name] . ' ' . $current);
        }

        $markup = parent::element($Element);

        if ($name === 'pre') {
            $this->inPre = false;
            // Moldura com botão de copiar; o comportamento vive no template da página.
            $markup = '<div class="' . DocsTheme::CODE_FRAME . '">' . $markup
                . '<button type="button" class="' . DocsTheme::COPY_BUTTON . '" data-docs-copy aria-label="Copy">'
                . DocsTheme::COPY_ICON . '</button></div>';
        }
        if ($name === 'table') {
            $markup = '<div class="' . DocsTheme::TABLE_FRAME . '">' . $markup . '</div>';
        }

        return $markup;
    }

    /**
     * `> [!NOTE]` na primeira linha vira caixa colorida com título; o resto do bloco segue
     * como Markdown normal.
     *
     * @param array<string, mixed> $Element
     * @return array<string, mixed>
     */
    private function callout(array $Element): array
    {
        $lines = $Element['text'] ?? null;
        if (!is_array($lines) || $lines === [] || !preg_match('/^\s*\[!([A-Z]+)\]\s*$/', (string)$lines[0], $m) || !in_array($m[1], self::CALLOUTS, true)) {
            return $Element;
        }
        $type = $m[1];
        array_shift($lines);
        $theme = $this->calloutTheme[$type];
        $label = $this->calloutLabels[$type] ?? ucfirst(strtolower($type));

        return [
            'name' => 'aside',
            'attributes' => ['class' => $theme['box'], 'data-callout' => strtolower($type)],
            'handler' => 'elements',
            'text' => [
                ['name' => 'div', 'attributes' => ['class' => $theme['title']], 'text' => $label],
                ['name' => 'div', 'attributes' => ['class' => DocsTheme::CALLOUT_BODY], 'handler' => 'lines', 'text' => $lines],
            ],
        ];
    }

    private function uniqueId(string $base): string
    {
        $base = $base === '' ? 'section' : $base;
        $n = $this->usedIds[$base] ?? 0;
        $this->usedIds[$base] = $n + 1;

        return $n === 0 ? $base : $base . '-' . ($n + 1);
    }

    /** Texto de título sem a marcação inline (`código`, **negrito**, [link](url)). */
    public static function plain(string $inline): string
    {
        $s = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $inline) ?? $inline;

        return trim(str_replace(['`', '**', '__', '*'], '', $s));
    }

    public static function slug(string $text): string
    {
        $t = strtolower($text);
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'í' => 'i', 'ì' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ç' => 'c', 'Ã' => 'a', 'Õ' => 'o', 'Â' => 'a', 'Ê' => 'e', 'Ô' => 'o',
        ]);
        $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';

        return trim($t, '-');
    }
}
