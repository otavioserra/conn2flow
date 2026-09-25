---
title: "publisher-pages module"
description: "Publications with structured fields, materialized HTML, inherited CSS and movement between publishers."
section: reference
module: publisher-pages
sources:
  - gestor/modulos/publisher-pages/publisher-pages.php
  - gestor/modulos/publisher-pages/publisher-pages.js
  - gestor/modulos/publisher-pages/publisher-pages.json
  - gestor/modulos/publisher-pages/resources
  - gestor/bibliotecas/banco.php
  - gestor/gestor.php
  - gestor/db/data/ModulosOperacoesData.json
  - gestor/db/migrations/20250723165530_create_paginas_table.php
  - gestor/db/migrations/20250814130000_alter_paginas_add_updated_flags.php
  - gestor/db/migrations/20250827110010_alter_paginas_add_framework_css.php
  - gestor/db/migrations/20250903160000_alter_recursos_add_plugin_id.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20250918130000_add_html_extra_head_to_tables.php
  - gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php
  - gestor/db/migrations/20260127163000_add_publisher_id_to_paginas_table.php
  - gestor/db/migrations/20260712100000_add_publish_window_to_paginas.php
  - gestor/db/migrations/20260813120000_add_seo_metadata_to_paginas.php
  - gestor/db/migrations/20260814100000_add_meta_seo_to_paginas.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
  - gestor/db/migrations/20250723165531_create_paginas_301_table.php
  - gestor/db/migrations/20260127162100_create_publisher_pages_table.php
  - gestor/db/migrations/20260713130000_expand_publisher_pages_identifiers.php
verified_at: 837c383f
---

# `publisher-pages` module

Creates publications from `publisher` definitions. Each publication is a page in `paginas`, accompanied by structured values and template HTML in `publisher_pages`. The populated HTML is saved on the page for runtime use.

## How to use

1. Open `publisher-pages/` and filter by publisher. The initial filter is `tipo=pagina`; system/both type, module and `publisher_id` filters are also available.
2. Use `publisher-pages/adicionar/?publisher_id=<slug>` to select the definition. Fill in name, path, layout and publisher fields. The interface supports text, textarea, HTML through Quill and images through the file picker.
3. Review the template and preview. The browser builds populated HTML; textarea line breaks become `<br>`, images receive the root and HTML uses Quill content.
4. Configure SEO, featured image and dates; changing `sem_permissao` requires operation `permissao-pagina`.
5. Save. Edit: `publisher-pages/editar/?id=<slug>`; clone: `publisher-pages/clonar/?id=<slug>`. Routes stay identical in pt-br/en JSON, relative to the language root.
6. To change publication type, use the **Move Publication** modal, select the destination and confirm. Review fields and design afterward: there is no automatic conversion to the destination template.

JS combines the user prefix, publisher prefix and normalized path, removing prefixes already present to avoid duplication. Selecting a publisher may reload the screen; save your work before switching context.

## Technical reference

### Operations, permissions and hooks

The controller configures `listar` and dispatches `adicionar`, `editar`, `clonar`. Status/deletion are shared-interface operations with sitemap callbacks. The listing requires `publisher_id IS NOT NULL`, not a mandatory JOIN with `publisher_pages`.

Module AJAX:

| Case | Input/result |
|---|---|
| `editor-html-switch` | `editor_checked=sim` enables the module boolean variable; other values disable it; returns `status=Ok` |
| `mover-publicador` | `page_id` (fallback `ajaxRegistroId`) and `new_publisher_id`; returns `Ok` with message/redirect or `Error` with message |

The operation seed declares `modificar-permissao-do-publicador-da-pagina`, operation `permissao-pagina`. The backend checks it before writing `sem_permissao`. The move handler has no additional local check for a dedicated operation: it depends on authorization by the calling interface.

Filter `hook_apply_filters('publisher-pages', 'listar.publisher-select', $publisher)` allows changing the publisher list. This JSON has no `hooks.api` or `hooks` subscriptions, and there is no public widget of its own. Publication templates belong to `publisher`, target `publisher`; this module's resources supply screens and field fragments.

### Tables and value format

The main CRUD table is `paginas`: id, name, path, layout, content, CSS, permission, status, dates and SEO follow the [admin-paginas](admin-paginas.md) contract. `paginas.publisher_id` is a string up to 255.

| `publisher_pages` column | Type/contract |
|---|---|
| `id_publisher_pages` | Numeric key |
| `page_id`, `publisher_id` | Required strings up to 255, expanded by the July 2026 migration |
| `language` | String up to 10, default pt-br |
| `fields_values` | Nullable JSON |
| `html_template` | Nullable MEDIUMTEXT |

There is a unique `(page_id, language)` index and publisher/language indexes. This table has no status, version or dates of its own; those belong to the page.

```json
[
  {"id":"title","value":"My publication"},
  {"id":"content","value":"<p>Article text.</p>"},
  {"id":"featured_image","value":"uploads/cover.webp"}
]
```

Ids come from `publisher.fields_schema.fields`. On submission, `publisher_fields_schema` describes the fields; PHP reads `field_<id>` or `field_<id>-caminho` for images. JSON is encoded with unescaped Unicode and slashes, then escaped for SQL.

Submitted `html` preserves template markers in `publisher_pages.html_template`; `htmlWithValues` becomes `paginas.html`. On creation/cloning, nonempty editor HTML is preserved. When empty, helpers try to recover HTML from an active template of an active publisher. This fallback only supplies structure: it does not itself substitute custom values on the server.

### CSS, editing and dates

Creation/cloning can inherit `templates.css_precompiled`. `publisher_pages_css_derivado()` returns this CSS and its signature, calculated from final HTML/CSS, template CSS, layout CSS and compiler version. Without precompiled template CSS, both are empty. The editor also submits `css_compiled` and `html_extra_head`.

Editing updates the page and structured data separately; renaming may generate a new slug and update `page_id`. It records history/backups and marks version/modification origin. Path changes register a 301 and update the sitemap; sitemap exceptions do not abort saving. SEO includes social title/description, meta description/keywords and image path.

Dates use the shared converter and router publication window described in [admin-paginas](admin-paginas.md). There is no draft/review/approval workflow in this controller. Creation saves status `A`.

### Moving publishers

The handler requires an existing, nondeleted page and destination in the language, rejects the same publisher and checks collisions when the path changes. Inactive destinations are accepted because the query uses `status!='D'`.

It only replaces the prefix when the old prefix is nonempty and matches the path start, case-insensitively. Otherwise it preserves the URL. It updates `paginas.publisher_id` and `publisher_pages.publisher_id`, the page's version/date/`user_modified`, history and, when the path changes, 301/sitemap. It does not remap `fields_values` or replace `html_template`, published HTML or CSS.

## Defects and legacy discrepancies

> [!CAUTION]
> On insertion, submitted `publisher` is added as `publisher_id` without `banco_escape_field()`; `banco_insert_name()` does not escape values. Older queries also concatenate publisher ids directly. Visual selection is not SQL validation for requests constructed outside the UI.

> [!WARNING]
> Collection uses `if($_REQUEST[$post_nome])`: string `"0"` and empty fields are omitted. When all values become empty during editing, the code can attempt to write an empty string to JSON `fields_values` instead of `[]`. The schema also comes from the client, and these persistence loops do not validate the `mandatory` flag.

> [!WARNING]
> Writes to `paginas` and `publisher_pages`, including moves, are not wrapped in a transaction by this controller and do not check each UPDATE/INSERT result before continuing. There is no local guarantee of atomicity or that a success message confirms both tables.

> [!WARNING]
> The editing screen does not hydrate window dates or expose the clearing parameters expected by the backend. Date changes are appended only when the page UPDATE accumulator already exists. See the limitations in admin-paginas.

Old Portuguese documentation used nonexistent names such as `publicador_paginas` and a taxonomy table. The actual model is `paginas` + `publisher_pages`; no tag/category CRUD or editorial approval is implemented here.

## See also

- [Publication definitions](publisher.md)
- [Page administration](admin-paginas.md)
- [Publication index](publisher-index.md)
- [Highlights](publisher-highlights.md)
