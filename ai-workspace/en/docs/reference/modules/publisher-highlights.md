---
title: "publisher-highlights module"
description: "Publication highlight blocks with automatic or manual curation, templates and field mapping."
section: reference
module: publisher-highlights
sources:
  - gestor/modulos/publisher-highlights/publisher-highlights.php
  - gestor/modulos/publisher-highlights/publisher-highlights.js
  - gestor/modulos/publisher-highlights/publisher-highlights.widget.php
  - gestor/modulos/publisher-highlights/publisher-highlights.json
  - gestor/modulos/publisher-highlights/resources
  - gestor/db/migrations/20260701100000_create_publisher_highlights_table.php
  - gestor/db/migrations/20260611110000_add_css_compiled_and_html_extra_head_to_widgets_tables.php
  - gestor/db/migrations/20260707100000_add_project_to_widget_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: dd893291
---

# `publisher-highlights` module

Builds publisher highlight blocks limited by count, using automatic selection or an operator-curated publication list. Each record stores the HTML/CSS rendered by the public widget.

## How to use

1. Open `publisher-highlights/`, then `publisher-highlights/adicionar/`. Enter a name and publisher; choose a template targeting `publisher-highlights`.
2. Configure automatic selection (`latest`), count and ordering, or manual selection (`manual`), with publication search and draggable tags. Changing the publisher clears the selected items.
3. Link `[[item#...]]` variables to publisher fields, adjust HTML/CSS and check the preview using real data.
4. Save. Editing and cloning use `publisher-highlights/editar/?id=<slug>` and `publisher-highlights/clonar/?id=<slug>`. Paths are identical in both language resources, relative to the language root. The listing offers activation/deactivation and deletion.
5. Insert the block into a page or layout:

```html
<!-- widgets#publisher-highlights->render({"grupo_slug":"highlights"}) < -->
<div>Highlights mockup</div>
<!-- widgets#publisher-highlights->render({"grupo_slug":"highlights"}) > -->
```

The record must be active, in the current language and have nonempty HTML. Otherwise it returns empty; the mockup is not a fallback. When editing, the `-modificado` template option preserves saved code without reloading the original; the suffix is not persisted in the schema.

## Technical reference

### Controller, permissions and AJAX

Normal flow configures `listar` and dispatches `adicionar`, `editar`, `clonar`, wrapped by the shared interface. `status` and `excluir` are configured interface actions. Name and publisher are required. Editing records history and backups for changed content fields, increments the version and sets `user_modified=1`.

| AJAX case | Contract |
|---|---|
| `template-load` | Receives `params.template_id`; reads an active template in the language and target, returning `modelo`, HTML/CSS, framework and `item` variables |
| `publisher-load` | Receives `params.publisher_id`; returns standard and custom fields, filtered by `template_map` links when present |
| `publisher-pages-search` | Receives publisher and `q`; searches name/id, up to 50 results, including SQL diagnostics |
| `publisher-pages-fetch` | Receives publisher and `ids`; returns names of selected active records |
| `widget-preview` | Renders supplied HTML/CSS and schema without requiring a saved record |

The JSON declares no `hooks`/`hooks.api`; `ModulosOperacoesData.json` seeds no special operation for this module. CRUD uses the administrative interface. This module has neither a public `render_ajax` function nor a `.widget.js` file: selection is rendered on the server.

### Table and schema

`publisher_highlights` has key `id_publisher_highlights`, `id` and `publisher_id` up to 100 characters, `name` up to 255, JSON `fields_schema`, MEDIUMTEXT `html`/`css_compiled`/`html_extra_head` and TEXT `css`. Other columns are `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. The unique index is `(id, language)`; publisher, plugin and language are indexed. `publisher_id` is a textual slug. The compatibility migration adds compiled CSS/head when missing; `project` comes from a later shared migration.

```json
{
  "template_id":"publisher-highlights-noticias-grid-cards",
  "rule":"latest",
  "count":4,
  "order_by":"date_desc",
  "selected_items":[],
  "variable_mapping":{"imagem":"featured_image","resumo":"description"}
}
```

Renderer defaults: `rule=latest`, `count=4`, `order_by=date_desc`; counts below 1 become 1. `template_id` lives in JSON, without a dedicated column. Outside manual mode, JS saves a copy with `selected_items=[]`.

The query uses `paginas LEFT JOIN publisher_pages` by id and language, filtering `paginas.publisher_id`, active page and current language. It can therefore include a page without a matching `publisher_pages` record. Automatic selection sorts by name (`title_asc`, `title_desc`) or modification date (`date_asc`, `date_desc`) and limits in SQL. Manual selection queries the slugs, restores `selected_items` order and limits to `count` in PHP; it ignores `order_by`.

### Template and variables

The first `<!-- item < -->` … `<!-- item > -->` block repeats. With no results, the first `no-item` remains and `item` is removed; without `no-item`, the entire widget returns empty. With results but no `item` block, it returns the template structure without repetition.

`[[item#name]]` uses `variable_mapping[name]`, or `name` directly; a missing field becomes empty. Standard fields: `page_id`, `titulo`, `url`, `data` (formatted modification time). The `id`/`value` pairs from `publisher_pages.fields_values` are merged and can override these names. Fields declared as `image` receive the site root when relative; HTTP(S), `//`, `data:` and the explicit root marker are handled by the URL helper.

Bundled templates: `publisher-highlights-noticias-lista-simples`, `publisher-highlights-noticias-grid-cards`, `publisher-highlights-artigos-editorial`, `publisher-highlights-lives-video-destaque`, `publisher-highlights-notas-mosaico`, `publisher-highlights-principal-carousel`. The bundled carousel uses horizontal scrolling and CSS scroll-snap; this module includes no slide-controller JS.

## Observed defects and limitations

> [!WARNING]
> In the branch with publications and an `item` block, the final `publisher_highlights_widget_montar_saida()` call passes only HTML and CSS. `css_compiled` and `html_extra_head` are lost in that branch, although forwarded in the empty-state and no-`item` branches.

> [!WARNING]
> `item` substitution uses `preg_replace` with content as the replacement: sequences such as `$1` can be interpreted as capture references. The field regex consumes `[[item#...]]` but not surrounding at-signs; HTML still containing `@[[item#...]]@` may produce residual at-signs. CRUD template loading removes the enclosure before editing.

> [!CAUTION]
> The widget checks neither individual page permissions nor scheduling, and inserts values without contextual HTML escaping. Use trusted content and enforce access on the page itself; curation and block status do not protect publication data.

## See also

- [Publication definitions](publisher.md)
- [Paginated index](publisher-index.md)
- [Menus](menus.md)
