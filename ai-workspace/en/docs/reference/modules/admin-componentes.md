---
title: "Admin components module"
description: "Editing reusable HTML/CSS components."
section: reference
module: admin-componentes
sources:
  - gestor/modulos/admin-componentes/admin-componentes.php
  - gestor/modulos/admin-componentes/admin-componentes.js
  - gestor/modulos/admin-componentes/admin-componentes.json
  - gestor/modulos/admin-componentes/resources
  - gestor/bibliotecas/gestor.php
  - gestor/db/migrations/20250723165440_create_componentes_table.php
  - gestor/db/migrations/20250813153000_alter_componentes_index_id_language.php
  - gestor/db/migrations/20250827110020_alter_componentes_add_framework_css.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20250918130000_add_html_extra_head_to_tables.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
verified_at: 45812d2e
---

# `admin-componentes` module

Edits reusable HTML/CSS fragments by language and optionally module. The core inserts them with `gestor_componente()` and adds their resources to the page.

## How to use

Open `admin-componentes/`, create at `admin-componentes/adicionar/`, or edit at `admin-componentes/editar/?id=<slug>`. Enter a name, CSS framework, and module where relevant; write HTML, CSS, and extra `<head>` HTML in the editor. The code editor comes from `html-editor`; this module's JS does not implement a separate editor. Routes are the same in pt-br and en.

## Technical reference

The controller handles `listar`, `adicionar`, and `editar`; status and deletion use the shared interface. The AJAX switch has no active case. Saved HTML/CSS use internal global-variable markers; the controller converts the editor's textual form to storage form and reverses it when loading. Editing backs up changed content, records history, increments version, and marks `user_modified=1`. It stores `css_source_hash` calculated from HTML/CSS to track CSS provenance. The module declares no widget, template, hooks, or `hooks.api`.

`componentes` has numeric `id_componentes`, `id`, `nome`, `language`, `modulo`, MEDIUMTEXT `html`/`css`, `status`, `versao`, timestamps, `user_modified`, `file_version`, and `checksum`; migrations add `framework_css`, `css_compiled`, `css_precompiled`, `html_extra_head`, project, and provenance hash. `(id, language)` is unique. `gestor_componente(['id'=>'...'])` queries by id and language, accepts an optional module filter, and returns HTML; `return_css` returns HTML and resources separately. The normal path adds CSS, precompiled and compiled CSS, and head HTML to the page. In development mode, the helper can also read physical module/root resources when the record points to them.

## Confirmed limitations

> [!CAUTION]
> Changing a component slug in the editor does not update `gestor_componente()` calls or markers referring to the old id. With `return_css`, the caller must include the returned resources in the proper context.

> [!WARNING]
> Saving compiled CSS is not compilation triggered by this CRUD: the controller stores supplied content and a provenance hash. The presence of `css_source_hash` alone does not establish that compiled CSS matches current HTML/CSS without pipeline verification.

## See also

- [Layouts](admin-layouts.md)
- [Pages](admin-paginas.md)
