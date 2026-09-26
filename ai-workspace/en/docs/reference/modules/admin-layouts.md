---
title: "Admin layouts module"
description: "Editing complete page layouts."
section: reference
module: admin-layouts
sources:
  - gestor/modulos/admin-layouts/admin-layouts.php
  - gestor/modulos/admin-layouts/admin-layouts.js
  - gestor/modulos/admin-layouts/admin-layouts.json
  - gestor/modulos/admin-layouts/resources
  - gestor/bibliotecas/gestor.php
  - gestor/gestor.php
  - gestor/db/migrations/20250723165526_create_layouts_table.php
  - gestor/db/migrations/20250813170010_alter_layouts_index_id_language.php
  - gestor/db/migrations/20250827110000_alter_layouts_add_framework_css.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
verified_at: 45812d2e
---

# `admin-layouts` module

Edits the complete HTML frame receiving page content, including `<head>`, `<body>`, CSS, and framework choice. The core selects the layout by id and language and merges the page body into `@[[pagina#corpo]]@`.

## How to use

Open `admin-layouts/`, create at `admin-layouts/adicionar/`, or edit at `admin-layouts/editar/?id=<slug>`. Enter a name, choose the CSS framework, and edit HTML/CSS in the `html-editor` component. Then associate the layout with the pages that need it. Routes are identical in both languages.

## Technical reference

The controller handles `listar`, `adicionar`, and `editar`; status and deletion use the shared interface. The AJAX switch has no active case. On save, textual global-variable markers are converted to internal form and converted back when editing. Editing backs up changed content, records history, increments version, marks `user_modified`, and calculates `css_source_hash` from HTML/CSS. Module JS delegates editing to `html-editor`. JSON defines no widget, template, hooks, or `hooks.api`.

`layouts` has numeric `id_layouts`, `nome`, `id`, `language`, `modulo`, LONGTEXT `html`, MEDIUMTEXT `css`, `status`, `versao`, timestamps, `user_modified`, `file_version`, and `checksum`; migrations add `framework_css`, `css_compiled`, `css_precompiled`, project, and provenance hash. `(id, language)` is unique. `gestor_layout()` reads HTML/CSS from the database for the language, includes precompiled CSS with the `layout-precompiled` role before page resources, and returns HTML; in development it may also read corresponding physical files. `gestor_pagina_layout()` replaces `pagina#corpo` with page content and sets the title.

## Confirmed limitations

> [!CAUTION]
> Renaming the layout slug does not update pages storing the old id. A missing layout falls back to minimal HTML in the helper; that does not preserve the intended layout's structure, resources, or customizations.

> [!WARNING]
> The CRUD stores supplied `css_compiled` and a provenance hash but does not compile CSS itself. For Tailwind, layout precompiled CSS has its own cascade role; changing HTML/CSS without rebuilding resources may leave the page with stale styling.

## See also

- [Components](admin-componentes.md)
- [Pages](admin-paginas.md)
