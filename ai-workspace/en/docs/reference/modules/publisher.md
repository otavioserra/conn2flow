---
title: "publisher module"
description: "Publication definitions: templates, path prefixes and the field schema used by publication pages."
section: reference
module: publisher
sources:
  - gestor/modulos/publisher/publisher.php
  - gestor/modulos/publisher/publisher.js
  - gestor/modulos/publisher/publisher.json
  - gestor/modulos/publisher/resources
  - gestor/db/migrations/20260106180000_create_publisher_table.php
  - gestor/db/migrations/20260106180001_add_path_prefix_to_publisher_table.php
  - gestor/db/data/ModulosData.json
  - gestor/db/data/ModulosOperacoesData.json
verified_at: 33ce53d9
---

# `publisher` module

`publisher` defines publication types: name, path prefix, template and custom fields. The `publisher-pages` module uses these definitions to populate publications. The module appears as **Publisher Definitions** in the Gestor administration group.

## How to use

1. Open `publisher/` and choose add (`publisher/adicionar/`). Declared routes are identical in pt-br and en resources, relative to the language root.
2. Enter `name`, review `path_prefix` and choose an active template in the current language with `target=publisher`. The browser formats the prefix in lowercase, without accents, with hyphens and a trailing slash; editing the name can also recalculate this prefix.
3. Load the template fields. Add all at once or create fields individually, defining label, description, type and required status. Link each field to its template variable; linking adopts that variable's identifier and type. Use the arrows to reorder.
4. Save. Editing opens at `publisher/editar/?id=<slug>`. `publisher/clonar/?id=<slug>` reuses the prefix and schema, but requires a name and template selection for the new record.
5. The listing offers edit, clone, activate/deactivate and delete. Use `publisher-pages` to create the actual publication content.

Templates already used by publishers that have not been deleted are disabled in the selector; when editing, the current record's template remains selectable. Clone the template in `admin-templates` to reuse its design.

## Technical reference

### Dispatch and operations

`publisher_start()` includes the declared libraries (`interface`, `html`). The normal flow configures listing through `publisher_interfaces_padroes()`, initializes the interface and dispatches `adicionar`, `editar` or `clonar`; the shared interface finalizes the operation. The listing configures `status` and `excluir` actions for that interface.

The only module AJAX case is `template-load`, between `interface_ajax_iniciar()` and `interface_ajax_finalizar()`. It receives `params.template_id` and `params.fields_schema`, queries an active template in the current language with target `publisher`, and returns `status=Ok`, `modelo`, `campos` and `publisherFields`. A missing template produces `status=Erro`. Extraction recognizes `@[[publisher#type#id]]@` and removes duplicates by type/id pair, preserving the first occurrence.

The JSON declares neither `hooks` nor `hooks.api`; this directory has no public widget of its own. `ModulosOperacoesData.json` seeds no operation specific to `publisher`. Administrative availability belongs to the module and shared interface; selector controls do not constitute an additional permission.

### `publisher` table

| Columns | Migration contract |
|---|---|
| `id_publisher` | Generated numeric key |
| `id`, `language` | Slug up to 100 characters, language up to 10 (default `pt-br`); joint unique index |
| `name` | Required string, up to 255 |
| `path_prefix`, `template_id` | Optional strings, up to 255 |
| `fields_schema` | Optional JSON |
| `id_usuarios`, `plugin` | Author (nullable integer, default 1) and plugin origin (string up to 255) |
| `status`, `versao` | Character, default `A`; integer, default 1 |
| `data_criacao`, `data_modificacao` | Timestamps; the latter updates automatically |
| `user_modified`, `system_updated` | Integer flags, default 0 |

Creation generates a slug from the name in the current language, status `A` and version 1. Editing compares previous values, records history and prefix/template backups when applicable, increments the version and sets `user_modified=1`. Changing the name may change the slug unless `_gestor-nao-alterar-id` is present.

### `fields_schema` and templates

The browser saves two lists in the order of the fields on screen:

```json
{
  "fields": [
    {"id":"title","label":"Title","description":"Main title","type":"text","mandatory":true}
  ],
  "template_map": [
    {"id":"title","variable":"[[publisher#text#title]]","found_template":true,"linked_template":true}
  ]
}
```

`fields` contains fields with both id and label filled in. Available UI types are `text`, `textarea`, `html` and `image`. `template_map` also includes template variables that have not been linked. Links are restored from `linked_template`, using the same id; `template_field_id` is interface state, not a property persisted by this serializer.

When saving, PHP converts `[[...]]` markers into `@[[...]]@`; editing/cloning reverses that conversion. `publisher_normalize_array()` recursively sorts keys to compare JSON on update, while retaining the effective order of list elements.

Bundled templates are `noticias-simples` and `noticias-imagem-destaque`, both Tailwind with target `publisher`. The first uses text, description and HTML; the second adds an image and alternative text. Variable ids differ across languages (`titulo`/`title`, `conteudo`/`content`, for example). The `publisher` AI mode guides HTML body and marker generation from the fields.

## Limitations and legacy discrepancies

> [!WARNING]
> Template exclusivity is enforced by the selector, without a unique index on `template_id` or equivalent INSERT/UPDATE checks in this controller. It does not guarantee integrity against requests constructed outside the UI.

> [!WARNING]
> Renaming a definition's slug does not update references in other tables in this controller. Preserve the identifier when publications or widgets already reference it.

> [!CAUTION]
> The received schema is not fully validated structurally. `template-load` assumes `fields` is a list; valid JSON with an unexpected shape can cause warnings or type errors. The schema is inserted into a `<script>` using `json_encode()` without `JSON_HEX_TAG`; do not treat this editor as a safe input boundary for untrusted authors.

The old Portuguese documentation invented `publicador_tipos`/`publicador_taxonomias` tables and approval workflows. The controller uses `publisher`; it implements neither those workflows nor the draft dashboard described in the old manual. The bundled templates are the two news templates listed above.

## See also

- [Menus](menus.md)
- [Database library](../libraries/banco.md)
- [Documentation contract](../../guides/documentation.md)
