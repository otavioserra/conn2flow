---
title: "publisher-index module"
description: "Publication indexes with search, sorting, manual curation and AJAX pagination."
section: reference
module: publisher-index
sources:
  - gestor/modulos/publisher-index/publisher-index.php
  - gestor/modulos/publisher-index/publisher-index.js
  - gestor/modulos/publisher-index/publisher-index.widget.php
  - gestor/modulos/publisher-index/publisher-index.widget.js
  - gestor/modulos/publisher-index/publisher-index.json
  - gestor/modulos/publisher-index/resources
  - gestor/db/migrations/20260611120000_create_publisher_index_table.php
  - gestor/db/migrations/20260707100000_add_project_to_widget_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: dd893291
---

# `publisher-index` module

Creates public indexes for a publisher, with the first page rendered on the server and search, sorting and incremental loading handled by the browser. Each record stores its own HTML/CSS and selection settings; the selected template provides a starting point.

## How to use

1. Open `publisher-index/` and `publisher-index/adicionar/`. Enter a name and publisher, choose a template with target `publisher-index` and adjust the HTML in the editor.
2. Choose automatic (`latest`) or manual (`manual`) selection. In manual mode, search publications and drag their tags to reorder. Changing the publisher clears manual selection.
3. Map `item` variables to publication fields; configure items per page, search, sorting, load-more button and metrics.
4. Save and check `publisher-index/editar/?id=<slug>`. Cloning uses `publisher-index/clonar/?id=<slug>`. These paths are identical in pt-br/en JSON, relative to the language root. The listing also offers status and deletion.
5. Insert into a page or layout:

```html
<!-- widgets#publisher-index->render({"grupo_slug":"news"}) < -->
<div>Index mockup</div>
<!-- widgets#publisher-index->render({"grupo_slug":"news"}) > -->
```

The widget looks up an active slug in the current language. A missing record or empty HTML produces an empty string, without falling back to the mockup. CSS, compiled CSS and extra head HTML enter the resource pipeline. The template option ending in `-modificado` preserves saved HTML/CSS; the suffix is removed before persisting `template_id` in the schema.

## Technical reference

### Operations and AJAX

The controller configures `listar` and dispatches `adicionar`, `editar`, `clonar`; `status` and `excluir` are shared-interface actions. Name and publisher are required. Changes increment the version, set `user_modified` and record history and content backups.

| Administrative AJAX case | Purpose |
|---|---|
| `template-load` | Reads an active template in the language and correct target; returns HTML, CSS, framework and `item` variables |
| `publisher-load` | Returns standard `titulo`, `url`, `data` fields and publisher fields; if `template_map` has links, filters custom fields to linked ones |
| `publisher-pages-search` | Searches name/id, up to 50 results; also returns diagnostics including SQL |
| `publisher-pages-fetch` | Hydrates names for selected slugs |
| `widget-preview` | Renders supplied HTML/CSS/schema using the inline renderer |

Public AJAX is `publisher_index_render_ajax()`, dispatched by the widget router. JS sends `ajax=sim`, `ajaxOpcao=publisher-index-load`, `ajaxRegistroId`, `ajaxWidgets` and `params[busca|pagina|ordenacao]` to the current page URL. The response contains `status`, `html`, `tem_mais`, `total`. Pages below 1 become 1; sorting outside the allowlist becomes `date_desc`.

The JSON declares no `hooks`/`hooks.api`, and `ModulosOperacoesData.json` seeds no module-specific operation. CRUD access goes through the administrative interface; the widget has its own public flow.

### Data

`publisher_index` has numeric key `id_publisher_index`; `id` and `publisher_id` are strings up to 100 characters (the latter is a slug, not a numeric key); `name` up to 255; JSON `fields_schema`; MEDIUMTEXT `html`, `css_compiled`, `html_extra_head` and TEXT `css`. Other columns are `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. The unique index is `(id, language)`; publisher, plugin and language are indexed. `project` comes from the later shared migration.

```json
{
  "template_id":"publisher-index-lista",
  "rule":"latest",
  "selected_items":[],
  "order_by":"date_desc",
  "items_per_page":10,
  "show_search_input":true,
  "show_sorting_select":true,
  "show_load_more_btn":true,
  "show_metrics":true,
  "variable_mapping":{"summary":"description"}
}
```

Missing, empty or negative `items_per_page` defaults to 10; **zero skips publication queries**. `count` remains in CRUD with default 4, but is vestigial: it does not limit this renderer. The four visual controls default to true. In PHP, accepted true strings are `true`, `1`, `yes`, `on` (case-insensitive).

Automatic selection joins `paginas` and `publisher_pages` by id and language, requiring an active page and matching `paginas.publisher_id`. Sorting: `date_desc`, `date_asc` by modification time; `title_asc`, `title_desc` by name. Search checks the name and valid `fields_values` JSON, including legacy Unicode-escape variants.

Manual selection restricts results to `selected_items` slugs, restores their order in PHP and paginates with `array_slice`; it ignores the selected sorting. Search uses `mb_stripos` on title/fields, excluding `page_id`, `url`, `data`. The two modes may differ in accent comparison and SQL search wildcards.

### Template contract

The first `<!-- item < -->` … `<!-- item > -->` block repeats per publication; `no-item` is the empty state. `search-input`, `sort-select`, `load-more`, `metrics` are conditional blocks. Global variables are `grupo_slug`, `publisher_id`, `items_per_page`, `ordenacao`, the four `show_*` flags, `page_count` and `page_total`, using either `[[...]]` or `@[[...]]@`.

`[[item#field]]` resolves through `variable_mapping`, or directly by name without a mapping; missing fields become empty. Each item provides `page_id`, `titulo`, `url`, `data` plus `fields_values` fields (`id`/`value` pairs). Custom fields can override the four standard names. Relative images receive the site root; HTTP(S), `//` and `data:` URLs are preserved.

JS requires `.conn2flow-publisher-index` with `data-grupo-slug` and `data-ordenacao`, plus `.publisher-index-items`, `.publisher-index-search`, `.publisher-index-sort`, `.publisher-index-load-more`. Metrics use `[data-page-count]` and `[data-page-total]`; the empty state needs `.publisher-index-empty`. Search is debounced by 300 ms. Each item must be a child of the list for visual counting to work.

Bundled templates: `publisher-index-lista`, `publisher-index-grid`, `publisher-index-timeline`, `publisher-index-agenda`, `publisher-index-grid-imagem`, `publisher-index-lista-imagem`.

## Confirmed limitations

> [!CAUTION]
> Public queries filter status and language but do not check individual page permissions or scheduling. Do not use widget inclusion/exclusion as access control. Field values enter HTML without contextual escaping: templates and content must be trusted.

> [!WARNING]
> In manual selection without search, `total` is `count(selected_items)`, even when some slugs are missing or inactive. Metrics can exceed the rendered item count.

> [!WARNING]
> The client drops requests while another is loading, without queuing the latest search. It also increments the page before the response and does not restore it on error. The load-more block is removed from initial HTML when there is no next page; JS does not recreate the button later.

## See also

- [Publication definitions](publisher.md)
- [Menus](menus.md)
- [Database library](../libraries/banco.md)
