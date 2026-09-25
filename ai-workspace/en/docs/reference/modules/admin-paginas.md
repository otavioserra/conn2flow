---
title: "admin-paginas module"
description: "Authoring site pages and system screens, routes, permissions, SEO, scheduling and history."
section: reference
module: admin-paginas
sources:
  - gestor/modulos/admin-paginas/admin-paginas.php
  - gestor/modulos/admin-paginas/admin-paginas.js
  - gestor/modulos/admin-paginas/admin-paginas.json
  - gestor/modulos/admin-paginas/resources
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
verified_at: 837c383f
---

# `admin-paginas` module

Manages site pages and system-screen records: content, layout, path, module/option binding, permissions, dates and SEO metadata. Published content is stored in `paginas`; the screen embeds the shared HTML editor.

## How to use

1. Open `admin-paginas/`. The initial filter is `tipo=pagina`; choose system or both to see administrative screens. `module_id` filters modules when the type is not page.
2. At `admin-paginas/adicionar/`, enter a name, slash-terminated path, layout, CSS framework and type. For `sistema`, enter module, option and module root when applicable.
3. Edit content, configure SEO/sharing and optionally publication, creation and modification dates. The featured-image picker stores the file's relative physical path.
4. Permission `permissao-pagina` allows changing `sem_permissao` (page without a permission requirement). Without it, the control is removed and the backend does not save that change.
5. Save and check `admin-paginas/editar/?id=<slug>`. Cloning uses `admin-paginas/clonar/?id=<slug>`. Declared routes are identical in pt-br/en, relative to the language root. The listing offers status, deletion and editing.

JS formats paths without accents, in lowercase, with hyphens and a trailing slash. Changing name/path can reformat the address; review it before saving. Use `publisher-pages` for publications with structured fields.

## Technical reference

### Dispatch, permissions and hooks

The controller configures `listar` and dispatches `adicionar`, `editar`, `clonar`; the shared interface handles `status` and `excluir`. Its only AJAX case is `editor-html-switch`: `editor_checked=sim` saves the module boolean variable as true; any other value saves false, returning `status=Ok`.

`ModulosOperacoesData.json` seeds `modificar-permissao-da-pagina`, operation `permissao-pagina`, in both languages. There is no public widget or content template declared in this JSON; it supplies administrative pages and the `lista-pagina-ou-sistema` component.

PHP emits actions under `admin-paginas`: `adicionar.banco`, `editar.banco`, `clonar.banco`, after writing, passing id and data. Editing includes `alteracoes`; creation/cloning include name, path, type and module. The JSON declares neither `hooks.api` nor `hooks` subscriptions: emitting an action does not imply a registered consumer.

### Persistence and old routes

Creation/cloning generate a language-scoped unique slug, status `A`, version 1 and current author. Path duplication is checked within the language. Editing compares fields, backs up nonempty previous content, records history, sets `user_modified=1` and increments the version. Renaming may change `id` unless `_gestor-nao-alterar-id` is present.

HTML/CSS/head markers change from `[[...]]` to `@[[...]]@` when saved. The controller calculates `css_source_hash` from HTML, CSS and layout through the provenance helper; this is not itself a new CSS compilation.

When `caminho` changes, it resolves the numeric id and calls `gestor_pagina_301_registrar(id_paginas, oldPath)`. Sitemap synchronization runs after creation/cloning/editing and through status/deletion callbacks; synchronization exceptions are logged without interrupting CRUD.

### Tables and columns

| Table/group | Migration columns and types |
|---|---|
| `paginas`: identity | Numeric `id_paginas`; nullable integer `id_usuarios`, default 1; strings `nome`, `id`, `layout_id` up to 255; `language` up to 10, default pt-br |
| Routing | TEXT `caminho`; strings `tipo`, `modulo`, `opcao` up to 255; nullable small integers `raiz`, `sem_permissao`; `publisher_id` up to 255 |
| Content | MEDIUMTEXT `html`, `css`, `css_precompiled`, `css_compiled`, `html_extra_head`, `html_updated`, `css_updated`; `framework_css` up to 50; `css_source_hash` up to 64 |
| SEO | `imagem_destaque` up to 500, `og_titulo` up to 255, TEXT `og_descricao` and `meta_descricao`, `meta_keywords` up to 500 |
| State/origin | Character `status` (default A), integer `versao` (default 1), `user_modified`, `system_updated`, `file_version`, `checksum`, `plugin`, `project` |
| Dates | `data_criacao`, `data_modificacao`; nullable `data_publicacao_inicio` and `data_publicacao_fim` |
| `paginas_301` | `id_paginas_301`, `id_paginas`, TEXT `caminho`, `data_criacao` |

Page uniqueness is `(id, language)`, not just `id`. Migrations do not define the ENUMs and foreign keys shown in the old doc. `tipo` and `framework_css` are strings; `caminho` is not VARCHAR(500).

### Dates and SEO

Dates pass through `formato_data_hora_br_para_datetime()`. On creation, unparsed creation/modification dates use `NOW()`; an empty publication window remains null. When editing, clearing a window requires `data_publicacao_inicio_limpar` or `data_publicacao_fim_limpar`; an empty field alone does not remove the previous date.

The router requires a null start or `<= NOW()` and a null end or `>= NOW()`, as well as active status. Outside the window, the normal query does not find the page. This controller has no separate “scheduled” status.

SEO includes `og_titulo`, `og_descricao`, `meta_descricao`, `meta_keywords` and featured image. Keywords are normalized by the helper; submitted empty fields can clear old values on edit.

## Limitations and legacy

> [!WARNING]
> Scheduling inputs on the editing screen are not populated with existing dates, and the controller SELECT omits window columns. The screen also lacks the explicit clearing parameters expected by the backend. An empty input does not mean there is no saved schedule.

> [!WARNING]
> Date updates sit inside `if(isset($editar['dados']))`: dates alone do not initialize that accumulator. A nonempty CSS signature normally initializes it, but that depends on the helper result. There is no local validation that the start precedes the end.

> [!CAUTION]
> HTML, CSS and head are unrestricted authored content. This CRUD is not an HTML sanitizer for untrusted authors. Server validation covers name/path; several interface requirements (layout, type and framework) are not repeated in the local backend required-field list.

Legacy documentation described `raiz` as the site root and translated the module id to `admin-pages`. The form places this option in the module configuration block; the real id and routes remain `admin-paginas` in English.

## See also

- [Publication definitions](publisher.md)
- [Page search](pages-index.md)
- [Database library](../libraries/banco.md)
