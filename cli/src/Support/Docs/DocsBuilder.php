<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Planeja os recursos de sistema que publicam as docs num projeto (req-178).
 *
 * Entrada: a árvore Markdown do Core (DocsTree) e o `docs.config.json` do projeto.
 * Saída: um plano puro — arquivos a gravar e pastas a remover — que o comando aplica ou
 * apenas lista (`--dry-run`). Nada é escrito aqui, o que torna o builder testável.
 *
 * Recursos gerados por idioma, todos com id `docs` ou prefixo `docs-` (a fronteira do que o
 * build gerencia — recursos de outros ids nunca são tocados):
 *   - `pages/<id>/<id>.html` + entradas de `pages.json` (publicações levam `publisher_id`);
 *   - `publisher_pages/<id>/<id>.html` + `publisher-pages.json` (valores dos campos);
 *   - `menus/<menu>/<menu>.html` + `menus.json` (árvore da barra lateral);
 *   - `assets/docs/llms*.txt` (índice e conteúdo integral para agentes de IA).
 */
final class DocsBuilder
{
    private const SECTION_ORDER = ['guides', 'concepts', 'reference', 'whats-new'];
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    private DocsTree $tree;
    private string $gestorPath;

    /** @var array<string, mixed> */
    private array $config;

    /** @var array<string, array<string, string>> */
    private array $moduleNames;

    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    /**
     * @param array<string, mixed> $config conteúdo de docs.config.json
     */
    /**
     * @param array<string, array<string, string>> $moduleNames Nome de cada módulo por idioma (`[idioma][id] => nome`),
     *        da tabela `modulos`: é o rótulo do menu das docs que declaram `module:` no frontmatter.
     */
    public function __construct(DocsTree $tree, string $gestorPath, array $config, array $moduleNames = [])
    {
        $this->tree = $tree;
        $this->gestorPath = rtrim(str_replace('\\', '/', $gestorPath), '/');
        $this->config = $config;
        $this->moduleNames = $moduleNames;
    }

    /**
     * Lê `[idioma][id] => nome` de um `ModulosData.json`. Arquivo ausente ou inválido devolve `[]`.
     *
     * @return array<string, array<string, string>>
     */
    public static function loadModuleNames(string $modulosDataJson): array
    {
        $dados = is_file($modulosDataJson) ? json_decode((string)file_get_contents($modulosDataJson), true) : null;
        $nomes = [];
        foreach (is_array($dados) ? $dados : [] as $m) {
            if (is_array($m) && isset($m['language'], $m['id'], $m['nome']) && trim((string)$m['nome']) !== '') {
                $nomes[(string)$m['language']][(string)$m['id']] = trim((string)$m['nome']);
            }
        }

        return $nomes;
    }

    /**
     * Rótulo de uma doc no menu: o nome do módulo (tabela `modulos`) quando ela declara `module:`,
     * senão o `label:` do frontmatter, senão o título.
     */
    private function menuLabel(string $lang, array $doc): string
    {
        $modulo = (string)($doc['meta']['module'] ?? '');
        if ($modulo !== '' && isset($this->moduleNames[$lang][$modulo])) {
            return $this->moduleNames[$lang][$modulo];
        }
        $label = trim((string)($doc['meta']['label'] ?? ''));

        return $label !== '' ? $label : (string)$doc['meta']['title'];
    }

    /**
     * Caminho do código documentado, a partir da raiz do Gestor: `bibliotecas/<lib>.php` para a
     * referência de bibliotecas e `modulos/<id>/` para a de módulos. Vazio para as demais docs.
     */
    public static function codePath(string $rel, array $meta): string
    {
        if (preg_match('#^reference/libraries/([a-z0-9_-]+)\.md$#', $rel, $m) && $m[1] !== 'index') {
            return 'bibliotecas/' . $m[1] . '.php';
        }
        $modulo = (string)($meta['module'] ?? '');
        if (str_starts_with($rel, 'reference/modules/') && preg_match('/^[a-z0-9_-]+$/', $modulo)) {
            return 'modulos/' . $modulo . '/';
        }

        return '';
    }

    /**
     * Selo com o caminho do código documentado, no topo do conteúdo (bibliotecas e módulos).
     *
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $labels
     */
    private function codePathHtml(string $rel, array $meta, array $labels): string
    {
        $caminho = self::codePath($rel, $meta);
        if ($caminho === '') {
            return '';
        }

        return '<p class="' . DocsTheme::CODE_PATH . '"><span class="' . DocsTheme::CODE_PATH_LABEL . '">'
            . self::text((string)($labels['code_path'] ?? 'Path')) . '</span> <code class="' . DocsTheme::ELEMENTS['code'] . '">'
            . self::text($caminho) . '</code></p>' . "
";
    }

    /** Comparação de rótulos sem acento, para a ordem alfabética do menu. */
    private static function semAcento(string $s): string
    {
        $t = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) : false;

        return strtolower($t !== false ? $t : $s);
    }

    /**
     * @return array{write: array<string, string>, delete: list<string>, errors: list<string>, warnings: list<string>, stats: array<string, int>}
     */
    public function plan(): array
    {
        $this->errors = [];
        $this->warnings = [];
        $write = [];
        $delete = [];
        $stats = ['pages' => 0, 'publications' => 0, 'landings' => 0];
        $llms = [];

        foreach ($this->languages() as $lang) {
            $docs = $this->collect($lang);
            $nav = $this->navigation($docs);
            $resDir = $this->gestorPath . '/resources/' . $lang;

            $articleTpl = $this->readTemplate($lang, (string)$this->config['article_template']);
            $menuTpl = $this->readTemplate($lang, (string)($this->config['menu']['template'] ?? ''));
            if ($articleTpl === null || $menuTpl === null) {
                continue;
            }

            $pages = [];
            $publications = [];

            foreach ($docs as $rel => $doc) {
                $fields = $this->fieldsFor($lang, $rel, $doc, $docs, $nav);
                $pageId = self::pageId($rel);
                $publisher = $this->publisherFor($rel, $doc['meta']['section'] ?? '');
                $pages[$pageId] = [
                    'name' => (string)$doc['meta']['title'],
                    'path' => $this->urlPath($rel),
                    'publisher_id' => $publisher,
                    'html' => self::fill($articleTpl, $fields),
                ];
                if ($publisher !== null) {
                    $publications[$pageId] = ['publisher_id' => $publisher, 'fields' => $fields, 'template' => $articleTpl];
                }
            }

            // Página de entrada de cada publisher (índice com busca), quando a pasta não tem index.md.
            foreach ((array)($this->config['publishers'] ?? []) as $publisherId => $pub) {
                $folder = trim((string)($pub['index_path'] ?? ''), '/');
                $temPublicacoes = in_array((string)$publisherId, array_column($publications, 'publisher_id'), true);
                if ($folder === '' || !$temPublicacoes || isset($docs[$folder . '/index.md'])) {
                    continue;
                }
                $widget = (string)($pub['index_widget'] ?? '');
                $labels = $this->labels($lang);
                $landingTitle = (string)($labels['landings'][$publisherId]['title'] ?? $publisherId);
                $fields = [
                    'titulo' => self::text($landingTitle),
                    'descricao' => self::text((string)($labels['landings'][$publisherId]['description'] ?? '')),
                    'secao' => self::text((string)($labels['sections'][explode('/', $folder)[0]] ?? '')),
                    'conteudo' => $widget === '' ? '' : '<!-- widgets#publisher-index->render({"grupo_slug": "' . $widget . '"}) < -->'
                        . '<!-- widgets#publisher-index->render({"grupo_slug": "' . $widget . '"}) > -->',
                    'sumario' => '',
                    'navegacao' => '',
                    'verificacao' => '',
                    'markdown' => '',
                ];
                $id = self::pageId($folder . '/index.md');
                $pages[$id] = [
                    'name' => $landingTitle,
                    'path' => $this->urlPath($folder . '/index.md'),
                    'publisher_id' => null,
                    'html' => self::fill($articleTpl, $fields),
                ];
                $stats['landings']++;
            }

            // ===== pages.json + pages/<id>/<id>.html
            [$json, $removed] = $this->merge($resDir . '/pages.json', 'id', array_map(function (array $p, string $id): array {
                $entry = [
                    'name' => $p['name'],
                    'id' => $id,
                    'layout' => (string)$this->config['layout'],
                    'path' => $p['path'],
                    'type' => 'page',
                    'framework_css' => (string)($this->config['framework_css'] ?? 'tailwindcss'),
                    'without_permission' => true,
                ];
                if ($p['publisher_id'] !== null) {
                    $entry['publisher_id'] = $p['publisher_id'];
                }

                return $entry;
            }, $pages, array_keys($pages)));
            $write[$resDir . '/pages.json'] = $json;
            foreach ($pages as $id => $p) {
                $write[$resDir . '/pages/' . $id . '/' . $id . '.html'] = $p['html'];
            }
            foreach ($removed as $id) {
                $delete[] = $resDir . '/pages/' . $id;
            }

            // ===== publisher-pages.json + publisher_pages/<id>/<id>.html
            [$json, $removed] = $this->merge($resDir . '/publisher-pages.json', 'page_id', array_map(static function (array $pub, string $id): array {
                $values = [];
                foreach ($pub['fields'] as $field => $value) {
                    $values[] = ['id' => $field, 'value' => $value];
                }

                return ['page_id' => $id, 'publisher_id' => $pub['publisher_id'], 'fields_values' => $values];
            }, $publications, array_keys($publications)));
            $write[$resDir . '/publisher-pages.json'] = $json;
            foreach ($publications as $id => $pub) {
                $write[$resDir . '/publisher_pages/' . $id . '/' . $id . '.html'] = $pub['template'];
            }
            foreach ($removed as $id) {
                $delete[] = $resDir . '/publisher_pages/' . $id;
            }

            // ===== menus.json + menus/<menu>/<menu>.html
            $menuId = (string)$this->config['menu']['id'];
            $tree = $this->menuTree($lang, $docs, $pages);
            [$json] = $this->merge($resDir . '/menus.json', 'id', [[
                'id_usuarios' => 1,
                'name' => (string)($this->labels($lang)['menu_name'] ?? $menuId),
                'id' => $menuId,
                'fields_schema' => [
                    'template_id' => (string)$this->config['menu']['template'],
                    'availability' => 'todos',
                    'conditions' => [],
                    'menus' => ['visible_to_all' => $tree],
                    'selected_items' => $tree,
                ],
                'css_compiled' => '',
                'html_extra_head' => null,
            ]], [$menuId]);
            $write[$resDir . '/menus.json'] = $json;
            $write[$resDir . '/menus/' . $menuId . '/' . $menuId . '.html'] = $menuTpl;

            $stats['pages'] += count($pages);
            $stats['publications'] += count($publications);
            $llms[$lang] = [$docs, $nav];
        }

        foreach ($this->llmsFiles($llms) as $path => $content) {
            $write[$path] = $content;
        }

        return ['write' => $write, 'delete' => $delete, 'errors' => $this->errors, 'warnings' => $this->warnings, 'stats' => $stats];
    }

    // ========================================================================= coleta e navegação

    /** @return list<string> */
    private function languages(): array
    {
        $langs = $this->config['languages'] ?? DocsTree::LANGS;

        return array_values(array_filter(array_map('strval', (array)$langs)));
    }

    /**
     * @return array<string, array{meta: array<string, mixed>, body: string}>
     */
    private function collect(string $lang): array
    {
        $docs = [];
        foreach ($this->tree->newDocs($lang) as $rel) {
            $fm = Frontmatter::parse($this->tree->read($lang, $rel));
            if (!$fm['has'] || empty($fm['meta']['title'])) {
                $this->errors[] = "{$lang}:{$rel}: frontmatter ausente ou sem title (rode docs:audit).";
                continue;
            }
            if (($fm['meta']['visibility'] ?? 'public') === 'restricted') {
                $this->warnings[] = "{$lang}:{$rel}: visibility restricted — não publicado.";
                continue;
            }
            $docs[$rel] = ['meta' => $fm['meta'], 'body' => $fm['body']];
        }

        return $docs;
    }

    /**
     * Ordem de leitura: índice raiz, depois seção a seção (subpasta, `order`, título).
     *
     * @param array<string, array{meta: array<string, mixed>, body: string}> $docs
     * @return list<string>
     */
    private function navigation(array $docs): array
    {
        $keys = array_keys($docs);
        usort($keys, static function (string $a, string $b) use ($docs): int {
            return self::sortKey($a, $docs[$a]['meta']) <=> self::sortKey($b, $docs[$b]['meta']);
        });

        return $keys;
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, int|string>
     */
    private static function sortKey(string $rel, array $meta): array
    {
        if ($rel === 'index.md') {
            return [-1, '', 0, ''];
        }
        $parts = explode('/', $rel);
        $section = array_search($parts[0], self::SECTION_ORDER, true);
        $sub = count($parts) > 2 ? $parts[1] : '';
        $isIndex = basename($rel) === 'index.md' ? 0 : 1;

        return [$section === false ? 99 : (int)$section, $sub, $isIndex, (int)($meta['order'] ?? 100), (string)$meta['title']];
    }

    // ========================================================================= campos de uma doc

    /**
     * @param array{meta: array<string, mixed>, body: string} $doc
     * @param array<string, array{meta: array<string, mixed>, body: string}> $docs
     * @param list<string> $nav
     * @return array<string, string>
     */
    private function fieldsFor(string $lang, string $rel, array $doc, array $docs, array $nav): array
    {
        $labels = $this->labels($lang);
        $body = preg_replace('/\A\s*#\s+[^\n]*\n/', '', $doc['body'], 1) ?? $doc['body'];
        // Os marcadores do bloco gerado são para o docs:extract; no site o safe mode os exibiria como texto.
        $body = str_replace([LibraryReference::START, LibraryReference::END], '', $body);

        $renderer = new MarkdownRenderer((array)($labels['callouts'] ?? []), function (string $href) use ($lang, $rel, $docs): ?string {
            return $this->resolveLink($lang, $rel, $href, $docs);
        });
        $rendered = $renderer->render($body);

        $idx = array_search($rel, $nav, true);
        $prev = $idx !== false && $idx > 0 ? $nav[$idx - 1] : null;
        $next = $idx !== false && $idx < count($nav) - 1 ? $nav[$idx + 1] : null;

        $section = (string)($doc['meta']['section'] ?? '');
        $sectionLabel = (string)($labels['sections'][$section] ?? '');
        $parts = explode('/', $rel);
        if (count($parts) > 2 && isset($labels['subsections'][$parts[1]])) {
            $sectionLabel .= ' / ' . $labels['subsections'][$parts[1]];
        }

        return [
            'titulo' => self::text((string)$doc['meta']['title']),
            'descricao' => self::text((string)($doc['meta']['description'] ?? '')),
            'secao' => self::text($sectionLabel),
            'conteudo' => $this->codePathHtml($rel, $doc['meta'], $labels) . $rendered['html'],
            'sumario' => $this->tocHtml($rendered['toc'], $labels),
            'navegacao' => $this->navHtml($prev, $next, $docs, $labels),
            'verificacao' => $this->verificationHtml($lang, $rel, $doc['meta'], $labels),
            'markdown' => MarkdownRenderer::protectGestorMarkers(htmlspecialchars('# ' . $doc['meta']['title'] . "\n" . $body, ENT_QUOTES, 'UTF-8')),
        ];
    }

    /**
     * @param array<string, array{meta: array<string, mixed>, body: string}> $docs
     */
    private function resolveLink(string $lang, string $rel, string $href, array $docs): ?string
    {
        if ($href === '' || $href[0] === '#' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $href)) {
            return $href;
        }
        $frag = '';
        if (($p = strpos($href, '#')) !== false) {
            $frag = substr($href, $p);
            $href = substr($href, 0, $p);
        }
        if (str_ends_with($href, '.md')) {
            $target = DocsAuditor::resolve(dirname($rel), $href);
            if ($target === null || !isset($docs[$target])) {
                $this->errors[] = "{$lang}:{$rel}: link quebrado para {$href}.";

                return null;
            }

            return MarkdownRenderer::URL_ROOT_TOKEN . $this->urlPath($target) . $frag;
        }
        $repoTarget = DocsAuditor::resolve(dirname($this->tree->repoRelative($lang, $rel)), $href);
        if ($repoTarget === null) {
            $this->errors[] = "{$lang}:{$rel}: link {$href} sai do repositório.";

            return null;
        }

        return $this->blobUrl($repoTarget) . $frag;
    }

    /**
     * @param list<array{level: int, id: string, text: string}> $toc
     * @param array<string, mixed> $labels
     */
    private function tocHtml(array $toc, array $labels): string
    {
        if (count($toc) < 2) {
            return '';
        }
        $html = '<p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">' . self::text((string)($labels['toc'] ?? 'On this page')) . '</p><ul class="space-y-0.5 text-sm">';
        foreach ($toc as $item) {
            $class = $item['level'] === 3 ? DocsTheme::TOC_LINK_L3 : DocsTheme::TOC_LINK;
            $html .= '<li><a href="#' . $item['id'] . '" class="' . $class . '" data-docs-toc>' . self::text($item['text']) . '</a></li>';
        }

        return $html . '</ul>';
    }

    /**
     * @param array<string, array{meta: array<string, mixed>, body: string}> $docs
     * @param array<string, mixed> $labels
     */
    private function navHtml(?string $prev, ?string $next, array $docs, array $labels): string
    {
        if ($prev === null && $next === null) {
            return '';
        }
        $card = function (?string $rel, string $label, string $align) use ($docs): string {
            if ($rel === null) {
                return '<div class="flex-1"></div>';
            }

            return '<a href="' . MarkdownRenderer::URL_ROOT_TOKEN . $this->urlPath($rel) . '" class="' . DocsTheme::NAV_CARD . ' ' . $align . '">'
                . '<p class="' . DocsTheme::NAV_LABEL . '">' . self::text($label) . '</p>'
                . '<p class="' . DocsTheme::NAV_TITLE . '">' . self::text((string)$docs[$rel]['meta']['title']) . '</p></a>';
        };

        return MarkdownRenderer::protectGestorMarkers('<nav class="mt-16 flex flex-col gap-4 sm:flex-row">'
            . $card($prev, (string)($labels['previous'] ?? 'Previous'), 'text-left')
            . $card($next, (string)($labels['next'] ?? 'Next'), 'text-right')
            . '</nav>');
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $labels
     */
    private function verificationHtml(string $lang, string $rel, array $meta, array $labels): string
    {
        $commit = (string)($meta['verified_at'] ?? '');
        $repo = rtrim((string)($this->config['repository'] ?? ''), '/');
        $html = '<div class="mt-12 border-t border-white/10 pt-6 space-y-3">';
        if ($commit !== '') {
            $commitHtml = self::text($commit);
            if ($repo !== '') {
                $commitHtml = '<a href="' . $repo . '/commit/' . rawurlencode($commit) . '" target="_blank" rel="noopener" class="underline underline-offset-2">' . $commitHtml . '</a>';
            }
            $html .= '<p class="' . DocsTheme::BADGE . '"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>'
                . self::text((string)($labels['verified'] ?? 'Verified against the code at')) . ' ' . $commitHtml . '</p>';
        }
        $sources = is_array($meta['sources'] ?? null) ? $meta['sources'] : [];
        if ($sources !== [] && $repo !== '') {
            $html .= '<p class="text-xs text-gray-500">' . self::text((string)($labels['sources'] ?? 'Sources')) . ':</p><ul class="flex flex-wrap gap-x-4 gap-y-1">';
            foreach ($sources as $src) {
                $html .= '<li><a href="' . $this->blobUrl((string)$src) . '" target="_blank" rel="noopener" class="' . DocsTheme::SOURCE_LINK . '">' . self::text((string)$src) . '</a></li>';
            }
            $html .= '</ul>';
        }
        if ($repo !== '') {
            $html .= '<p><a href="' . $this->blobUrl($this->tree->repoRelative($lang, $rel)) . '" target="_blank" rel="noopener" class="' . DocsTheme::SOURCE_LINK . '">'
                . self::text((string)($labels['edit'] ?? 'Edit this page')) . ' →</a></p>';
        }

        return MarkdownRenderer::protectGestorMarkers($html . '</div>');
    }

    // ========================================================================= menu

    /**
     * @param array<string, array{meta: array<string, mixed>, body: string}> $docs
     * @param array<string, array{name: string, path: string, publisher_id: ?string, html: string}> $pages
     * @return list<array<string, mixed>>
     */
    private function menuTree(string $lang, array $docs, array $pages): array
    {
        $labels = $this->labels($lang);
        $item = static fn (string $pageId, string $label, array $children = []): array => [
            'id' => 'item_' . substr(md5($pageId . '|' . $label), 0, 12),
            'type' => 'pagina',
            'page_id' => $pageId,
            'label' => $label,
            'url' => '',
            'css_classes' => '',
            'children' => $children,
        ];
        $header = static fn (string $key, string $label, array $children): array => [
            'id' => 'item_' . substr(md5('h|' . $key), 0, 12),
            'type' => 'cabecalho',
            'label' => $label,
            'url' => '',
            'css_classes' => '',
            'children' => $children,
        ];

        $tree = [];
        if (isset($pages['docs'])) {
            $tree[] = $item('docs', (string)($labels['home'] ?? 'Overview'));
        }

        $grouped = [];
        foreach ($this->navigation($docs) as $rel) {
            if ($rel === 'index.md') {
                continue;
            }
            $parts = explode('/', $rel);
            $sub = count($parts) > 2 ? $parts[1] : '';
            $grouped[$parts[0]][$sub][] = $rel;
        }

        foreach (self::SECTION_ORDER as $section) {
            if (!isset($grouped[$section])) {
                continue;
            }
            $children = [];
            foreach ($grouped[$section] as $sub => $rels) {
                $leaves = [];
                foreach ($rels as $rel) {
                    if (basename($rel) === 'index.md') {
                        continue;
                    }
                    $leaves[] = $item(self::pageId($rel), $this->menuLabel($lang, $docs[$rel]));
                }
                // Com o nome do módulo como rótulo, a ordem do título antigo deixa de fazer sentido.
                if ($sub === 'modules' || $sub === 'libraries') {
                    usort($leaves, static fn (array $a, array $b): int => strcasecmp(
                        self::semAcento($a['label']),
                        self::semAcento($b['label'])
                    ));
                }
                if ($sub === '') {
                    array_push($children, ...$leaves);
                } else {
                    $children[] = $header($section . '/' . $sub, (string)($labels['subsections'][$sub] ?? ucfirst($sub)), $leaves);
                }
            }
            $landing = self::pageId($section . '/index.md');
            $label = (string)($labels['sections'][$section] ?? ucfirst($section));
            $tree[] = isset($pages[$landing]) ? $item($landing, $label, $children) : $header($section, $label, $children);
        }

        return $tree;
    }

    // ========================================================================= llms.txt

    /**
     * @param array<string, array{0: array<string, array{meta: array<string, mixed>, body: string}>, 1: list<string>}> $byLang
     * @return array<string, string>
     */
    private function llmsFiles(array $byLang): array
    {
        $out = [];
        $site = rtrim((string)($this->config['site_url'] ?? ''), '/') . '/';
        foreach ($byLang as $lang => [$docs, $nav]) {
            $suffix = $lang === ($this->config['llms_default_language'] ?? 'en') ? '' : '-' . $lang;
            $labels = $this->labels($lang);
            $index = '# ' . ($labels['llms_title'] ?? 'Conn2Flow') . "\n\n> " . ($labels['llms_summary'] ?? '') . "\n\n";
            $full = $index;
            $current = null;
            foreach ($nav as $rel) {
                $section = (string)($docs[$rel]['meta']['section'] ?? '');
                if ($section !== $current && $section !== 'home') {
                    $index .= "\n## " . ($labels['sections'][$section] ?? $section) . "\n\n";
                    $current = $section;
                }
                $url = $site . $this->urlPath($rel);
                $index .= '- [' . $docs[$rel]['meta']['title'] . '](' . $url . '): ' . ($docs[$rel]['meta']['description'] ?? '') . "\n";
                $full .= "\n\n---\n\nSource: " . $url . "\n\n" . trim($docs[$rel]['body']) . "\n";
            }
            $out[$this->gestorPath . '/assets/docs/llms' . $suffix . '.txt'] = $index;
            $out[$this->gestorPath . '/assets/docs/llms-full' . $suffix . '.txt'] = $full;
        }

        return $out;
    }

    // ========================================================================= utilidades

    /** `reference/libraries/modelo.md` → `docs-reference-libraries-modelo`; `index.md` → `docs`. */
    public static function pageId(string $rel): string
    {
        $slug = self::relSlug($rel);

        return $slug === '' ? 'docs' : 'docs-' . str_replace('/', '-', $slug);
    }

    private static function relSlug(string $rel): string
    {
        $noExt = substr($rel, 0, -3);
        if (basename($noExt) === 'index') {
            $noExt = dirname($noExt);
        }

        return $noExt === '.' ? '' : $noExt;
    }

    private function urlPath(string $rel): string
    {
        $base = trim((string)($this->config['base_path'] ?? 'docs/'), '/');
        $slug = self::relSlug($rel);

        return $base . '/' . ($slug === '' ? '' : $slug . '/');
    }

    private function blobUrl(string $repoPath): string
    {
        $repo = rtrim((string)($this->config['repository'] ?? ''), '/');
        $branch = (string)($this->config['branch'] ?? 'main');

        return $repo . '/blob/' . $branch . '/' . ltrim($repoPath, '/');
    }

    private function publisherFor(string $rel, string $section): ?string
    {
        if (basename($rel) === 'index.md') {
            return null;
        }
        foreach ((array)($this->config['publishers'] ?? []) as $id => $pub) {
            if (in_array($section, (array)($pub['sections'] ?? []), true)) {
                return (string)$id;
            }
        }
        $this->warnings[] = "{$rel}: seção '{$section}' sem publisher em docs.config.json — publicada como página comum.";

        return null;
    }

    /** @return array<string, mixed> */
    private function labels(string $lang): array
    {
        return (array)($this->config['labels'][$lang] ?? []);
    }

    private function readTemplate(string $lang, string $id): ?string
    {
        $path = $this->gestorPath . '/resources/' . $lang . '/templates/' . $id . '/' . $id . '.html';
        if ($id === '' || !is_file($path)) {
            $this->errors[] = "{$lang}: template '{$id}' não encontrado em {$path}.";

            return null;
        }

        return (string)file_get_contents($path);
    }

    /** Troca `@[[publisher#<tipo>#<campo>]]@` pelo valor do campo (vazio se não houver). */
    public static function fill(string $template, array $fields): string
    {
        $html = preg_replace_callback('/@\[\[publisher#[a-z]+#([a-z0-9_-]+)\]\]@/i', static function (array $m) use ($fields): string {
            return $fields[strtolower($m[1])] ?? '';
        }, $template) ?? $template;

        // Campo vazio deixa só a indentação do template na linha; não publicar espaço à direita.
        return preg_replace('/[ \t]+$/m', '', $html) ?? $html;
    }

    private static function text(string $s): string
    {
        return MarkdownRenderer::protectGestorMarkers(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Mescla entradas geradas no JSON existente pela chave, preservando entradas de outros ids
     * e as chaves que o pipeline mantém (version, checksum). Entradas `docs*` que não foram
     * geradas agora são removidas (doc apagada ou renomeada).
     *
     * @param list<array<string, mixed>> $generated
     * @param list<string>|null $managed ids gerenciados (padrão: prefixo docs)
     * @return array{0: string, 1: list<string>}
     */
    private function merge(string $file, string $key, array $generated, ?array $managed = null): array
    {
        $existing = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
        $existing = is_array($existing) ? $existing : [];
        $byId = [];
        foreach ($generated as $g) {
            $byId[(string)$g[$key]] = $g;
        }
        $isManaged = static fn (string $id): bool => $managed !== null ? in_array($id, $managed, true) : ($id === 'docs' || str_starts_with($id, 'docs-'));

        $out = [];
        $removed = [];
        $seen = [];
        foreach ($existing as $entry) {
            $id = (string)($entry[$key] ?? '');
            if (isset($byId[$id])) {
                $merged = $byId[$id];
                foreach ($entry as $k => $v) {
                    if (!array_key_exists($k, $merged) && in_array($k, ['version', 'checksum'], true)) {
                        $merged[$k] = $v;
                    }
                }
                $out[] = $merged;
                $seen[$id] = true;
            } elseif ($isManaged($id)) {
                $removed[] = $id;
            } else {
                $out[] = $entry;
            }
        }
        foreach ($byId as $id => $g) {
            if (!isset($seen[$id])) {
                $out[] = $g;
            }
        }

        return [json_encode($out, self::JSON_FLAGS) . "\n", $removed];
    }
}
