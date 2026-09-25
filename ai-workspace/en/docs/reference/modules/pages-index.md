---
title: "pages-index module"
description: "Search and paginated listing of public pages, with summaries, term highlighting and keyboard navigation."
section: reference
module: pages-index
sources:
  - gestor/modulos/pages-index/pages-index.php
  - gestor/modulos/pages-index/pages-index.js
  - gestor/modulos/pages-index/pages-index.widget.php
  - gestor/modulos/pages-index/pages-index.widget.js
  - gestor/modulos/pages-index/pages-index.json
  - gestor/modulos/pages-index/resources
  - gestor/db/migrations/20260715120000_create_forms_search_and_pages_index_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: 837c383f
---

# `pages-index` module

Creates searchable indexes of public site pages. The widget reads `paginas` directly and provides title, summary, URL and date, without depending on publishers or custom fields.

## How to use

1. Open `pages-index/` and `pages-index/adicionar/`; enter a name, select a template targeting `pages-index` and adjust HTML/CSS.
2. Configure items per page, sorting and visibility of search, sorting selector, load more and metrics. Map template variables to `title`, `summary`, `url`, `date`.
3. Save. Editing and cloning use `pages-index/editar/?id=<slug>` and `pages-index/clonar/?id=<slug>`. Routes are identical in both languages, relative to the language root.
4. Insert the widget or use the supplied `pages-index-search/?search=term` page, whose HTML calls group `padrao`.

```html
<!-- widgets#pages-index->render({"grupo_slug":"padrao"}) < -->
<div>Mockup</div>
<!-- widgets#pages-index->render({"grupo_slug":"padrao"}) > -->
```

The record must be active in the current language and contain HTML; otherwise it returns empty without using the mockup. The `-modificado` template option preserves saved HTML/CSS; its suffix is removed before saving.

## Technical reference

### Controller and data

The controller configures `listar` and dispatches `adicionar`, `editar`, `clonar`; status and deletion belong to the shared interface. The only required field checked during creation is `name`. Editing records history, content backups, version and `user_modified`.

Administrative AJAX: `template-load` reads an active template in the language/target and returns HTML/CSS/framework/variables; `campos-load` returns the four fixed fields; `widget-preview` renders supplied data. The JSON declares no `hooks`/`hooks.api`, and `ModulosOperacoesData.json` seeds no special operation.

The `pages_index` table contains `id_pages_index` (numeric key), `id` (up to 100), `name` (up to 255), JSON `fields_schema`, MEDIUMTEXT `html`/`css_compiled`/`html_extra_head`, TEXT `css`; plus `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. It has a unique `(id, language)` index and plugin/language indexes. There is no `publisher_id`.

```json
{
  "template_id":"pages-index-lista",
  "items_per_page":10,
  "order_by":"date_desc",
  "show_search_input":true,
  "show_sorting_select":true,
  "show_load_more_btn":true,
  "show_metrics":true,
  "variable_mapping":{"heading":"title","excerpt":"summary"}
}
```

The four controls default to true. Missing, empty or negative `items_per_page` becomes 10; zero skips page queries. `rule`, `count` and `selected_items` remain in inherited administrative JS but do not control this renderer.

### Query and rendering

Filter: `status='A'`, current language, `tipo='pagina'`, `sem_permissao=1`. Search uses `LIKE` on `nome` or `html`; sorting uses name (`title_asc|title_desc`) or modification time (`date_asc|date_desc`, descending by default). The filter does not exclude the index page itself.

Summary: strips HTML tags, normalizes whitespace, decodes entities and truncates to 200 characters plus an ellipsis (or bytes without mbstring). Widgets are not rendered before summarizing. Item fields are `title`, `summary`, `url`, `date`; absent mappings use the variable name, missing values become empty.

The first `item` block repeats and `no-item` handles empty results. Conditional blocks: `search-input`, `sort-select`, `load-more`, `metrics`, delimited like `<!-- item < -->` / `<!-- item > -->`. Global variables: `grupo_slug`, `items_per_page`, `ordenacao`, `search`, the four `show_*` flags, `page_count`, `page_total`. Markers accept `[[...]]` and `@[[...]]@`. `search` is HTML-escaped; item fields are not.

Templates: `pages-index-lista`, `pages-index-grid`, `pages-index-timeline`, `pages-index-agenda`, `pages-index-grid-imagem`, `pages-index-lista-imagem`. Image model names do not add an image field to the query: the contract still has four fields.

### Browser and public AJAX

`pages_index_render_ajax()` receives `ajaxRegistroId`, `params[busca]`, `params[pagina]`, `params[ordenacao]` through the widget router; it returns `status=Ok`, `html`, `tem_mais`, `total`. Pages below 1 become 1 and unknown sorting becomes `date_desc`. JS sends `ajaxOpcao=pages-index-load` and `ajaxWidgets` to the current URL.

The container needs `.conn2flow-pages-index`, `data-grupo-slug`, `data-ordenacao`; controls use `.pages-index-items|search|sort|load-more`. Empty state: `.pages-index-empty`. Metrics: `[data-page-count]`, `[data-page-total]`. Each result must be a child of the list.

Search has a 300 ms debounce, AbortController cancellation and a token rejecting old responses. The in-memory cache keys on group, lowercase term, page and sorting. `history.replaceState` synchronizes `search`; highlighting uses text nodes and `mark`, preserving tags. Arrow keys select results and Enter opens the link. A URL with `search` triggers another query after initial rendering.

Nonempty searches are forwarded to `forms_search_registrar_busca`, when available: on initial load and AJAX page 1. Later pagination does not log; browser cache also avoids another call.

## Confirmed limitations

> [!CAUTION]
> The public filter does not check `data_publicacao_inicio/fim`. An active public scheduled page can enter the index before its window. The summary decodes entities after stripping tags and is inserted without further escaping: this is not an HTML sanitizer.

> [!WARNING]
> `%/_` in the term remain LIKE wildcards. Searching HTML can match tag or attribute names absent from the summary. Initial loading with `search` and its AJAX confirmation can call the search logger twice.

> [!WARNING]
> The load-more button is removed from initial HTML without a next page and JS does not recreate it. On network errors, the client neither displays a message nor restores the incremented page; the next click can skip results.

## See also

- [Publication index](publisher-index.md)
- [Menus](menus.md)
- [Documentation contract](../../guides/documentation.md)
