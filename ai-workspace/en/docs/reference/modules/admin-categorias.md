---
title: "Admin categories module"
description: "Hierarchical categories associated with modules and files."
section: reference
module: admin-categorias
sources:
  - gestor/modulos/admin-categorias/admin-categorias.php
  - gestor/modulos/admin-categorias/admin-categorias.js
  - gestor/modulos/admin-categorias/admin-categorias.json
  - gestor/modulos/admin-categorias/resources
  - gestor/db/migrations/20250723165439_create_categorias_table.php
  - gestor/db/migrations/20260717120000_create_arquivos_disco_categorias_table.php
verified_at: 45812d2e
---

# `admin-categorias` module

Organizes categories in a tree, associated with a module and, in the current file manager, physical file paths.

## How to use

Open `admin-categorias/`, create a root category at `admin-categorias/adicionar/` by choosing a module, or use `admin-categorias/adicionar-filho/?id=<slug>` on an existing category. Edit name, module, or plugin at `admin-categorias/editar/?id=<slug>`. The edit screen shows ancestor breadcrumbs. Routes are identical in both languages; category records have no language column.

## Technical reference

The controller handles `listar`, `adicionar`, `adicionar-filho`, and `editar`; status and deletion use the shared interface. JSON has no active module AJAX case, widget, template, hooks, or `hooks.api`. `adicionar-filho` finds a nondeleted parent by slug, inherits its `id_modulos`, and saves its `id_categorias` as `id_categorias_pai`. Ancestors are assembled recursively for the edit breadcrumb. The module JS adds no separate data flow.

`categorias` has numeric `id_categorias`, `id_usuarios`, `id_modulos`, `id_categorias_pai`, `nome`, `id`, `plugin`, `status`, `versao`, and timestamps. The initial migration declares no FK, unique slug index, or `language` column. JSON uses PK synchronization. The file manager uses `arquivos_disco_categorias.id_categorias` to associate categories with physical paths; old `arquivos_categorias` remains for legacy records.

## Confirmed limitations

> [!WARNING]
> Renaming a category may change its slug, but children refer to its numeric id and preserve the hierarchy. Recursive breadcrumbs have no cycle detection; a corrupted parent chain in the database may prevent the screen from rendering.

> [!CAUTION]
> Child creation inherits the parent's module, but editing can change `id_modulos` without traversing descendants. This can leave a tree spanning different modules. Physical file associations use numeric category ids regardless of slug.

## See also

- [Files](admin-arquivos.md)
- [Modules](modulos.md)
