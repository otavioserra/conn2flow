---
title: "Admin files module"
description: "Browsing, uploading, and organizing physical site files."
section: reference
module: admin-arquivos
sources:
  - gestor/modulos/admin-arquivos/admin-arquivos.php
  - gestor/modulos/admin-arquivos/admin-arquivos.js
  - gestor/modulos/admin-arquivos/admin-arquivos.json
  - gestor/modulos/admin-arquivos/resources
  - gestor/bibliotecas/arquivo.php
  - gestor/db/migrations/20250723165436_create_arquivos_table.php
  - gestor/db/migrations/20250723165437_create_arquivos_categorias_table.php
  - gestor/db/migrations/20260717120000_create_arquivos_disco_categorias_table.php
verified_at: 45812d2e
---

# `admin-arquivos` module

Manages the physical file tree under `contents-path`: browsing folders, uploading, generating thumbnails, renaming, deleting, and assigning categories. Individual records in `arquivos` are legacy and do not drive the current listing.

## How to use

Open `admin-arquivos/` to browse, filter by date/category, and switch views. Create or rename folders, select files, copy their paths, or delete items. At `admin-arquivos/adicionar/`, choose a target folder and upload via file picker or drag and drop; categories may be assigned during or after upload. The panel offers image preview and media picking for other editors. Routes are identical in both languages. `admin-arquivos/emissao-teste/` appears in JSON without a controller option.

## Technical reference

The controller dispatches `listar-arquivos` and `upload`. Admin AJAX operations: `navegar`, `uploadFile`, `miniaturas`, `excluir`, `pasta-criar`, `renomear`, and `categorias-arquivo`. JSON declares no widget, template, hooks, or `hooks.api`. JS uses jQuery File Upload, requests pages and thumbnails in batches, and maintains media picker selections.

`admin_arquivos_ler_pasta()` scans the disk, skips hidden entries and the `mini` folder, lists folders before files, filters by date/category, and paginates files only. JSON defaults: 24 files per page, thumbnail batches of five, 20 MB upload limit, 200-pixel thumbnail width. GD thumbnails are generated only when read/write functions for the format exist. The `arquivo` library validates relative paths, blocks dangerous extensions, sanitizes names, and resolves collisions. Deleting a nonempty folder requires `recursivo=true`; renaming moves its thumbnail and category associations.

The current association is `arquivos_disco_categorias`: `id_arquivos_disco_categorias`, `caminho` up to 1024, MD5 `caminho_hash`, `id_categorias`, `data_criacao`, unique on `(caminho_hash,id_categorias)`. Legacy `arquivos` has `id_arquivos`, name, type, path, thumbnail, permission, and status; `arquivos_categorias` associates its ids, but current processing creates no per-file records there. Category filtering joins hashes of listed paths against the new table.

## Confirmed limitations

> [!WARNING]
> An upload `dir` rejected by `arquivo_caminho_relativo_seguro()` falls back to the root because the code uses `?: ''`; check the displayed destination before uploading. Recursive deletion removes physical files; results are returned per item and filesystem errors are suppressed in that routine.

> [!CAUTION]
> JSON still declares `tabela: arquivos`, even though current upload/listing use the disk and `arquivos_disco_categorias`. Reports or integrations querying only `arquivos` do not reflect the current collection. Thumbnail generation is best effort and may be absent after a successful upload.

## See also

- [Categories](admin-categorias.md)
- [Galleries](galleries.md)
