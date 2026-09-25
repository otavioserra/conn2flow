---
title: "Menus module"
description: "Navigation menus curated in the admin panel and rendered by a widget in layouts and pages, with a tree of typed items and per-visitor variations."
section: reference
order: 30
module: menus
sources:
  - gestor/modulos/menus/menus.php
  - gestor/modulos/menus/menus.widget.php
  - gestor/modulos/menus/menus.widget.js
  - gestor/modulos/menus/menus.json
  - gestor/db/migrations/20260701110000_create_menus_table.php
verified_at: 5b4348ab
---

# The `menus` module

The `menus` module builds navigation menus (header, footer, sidebar, breadcrumb, dropdown, mobile) **without writing PHP**. The operator builds a tree of items in the admin panel, picks a visual template and drops the menu into any layout or page with a widget marker. The final HTML is assembled on the server on every request, always with the current canonical page links.

## How to use it

1. **Create the menu** at *Menus → Add* (`/menus/adicionar/`). Give it a name and pick a template; the available templates are the `templates` records with `target = 'menus'`.
2. **Build the tree** in the editor (`/menus/editar/?id=...`): add items, drag to reorder and nest, and search pages by name. The preview tab calls the `widget-preview` AJAX route, which uses the same renderer as the website.
3. **Embed it in a layout or page** with the widget wrapper. `grupo_slug` is the menu `id`:

```html
<!-- widgets#menus->render({"grupo_slug": "main-menu"}) < -->
<nav>…static mockup, only for whoever edits the layout…</nav>
<!-- widgets#menus->render({"grupo_slug": "main-menu"}) > -->
```

> [!NOTE]
> Whatever sits between the two markers is only a **mockup** for designers. At runtime the widget always replaces it with the template stored **in the database**. If the menu does not exist, is inactive or has an empty template, the widget returns an empty string, and the mockup does not show either.

## Item types

| `type` | Behavior |
|---|---|
| `pagina` | Points to a page by `page_id` (slug). URL and label come from the database in the current language; a `label` set on the item wins. |
| `link-custom` | Free label and URL; supports `target` (`_self`/`_blank`). |
| `link-action` | Same as `link-custom`, meant for actions (logout, modals) via `css_classes`. |
| `cabecalho` | Grouping header with children; falls back to `#` without a URL. |
| `publicador` | Expands a publisher's publications at runtime (`publisher_id`), limited by `count` (default 5) and sorted by `order_by` (`date_desc`, `date_asc`, `title_asc`, `title_desc`). Replaces manual children. |
| `separador` | Visual divider with an optional label. |

An old tree saved as a list of slugs (the initial format) still works: each string becomes a `pagina` item.

## Per-visitor visibility

`fields_schema.availability` decides whether the menu is the same for everyone (`todos`) or depends on the visitor (`condicional`). In conditional mode, the `conditions` are evaluated **in order, and the first match wins**:

| Condition | Matches when |
|---|---|
| `publico` | the visitor is **not** logged in |
| `logado` | there is an authenticated user with a valid token and permission |
| `perfil_usuario` | the logged-in user belongs to one of the `profile_ids` (plain id or its `sha256` hash) |

Each condition has a `slug`, and its items live in `fields_schema.menus[<slug>]`. If none matches, `menus.visible_to_all` is used. The template may carry different HTML per condition using `<!-- menu-conditional-<slug> < -->` blocks; otherwise it uses `<!-- menu-visible < -->` or the whole template.

> [!CAUTION]
> Menu visibility is **presentation, not access control**. Hiding a link does not protect the target page; the permission must be enforced on the page or module itself.

## Template contract

A menu template is HTML with model blocks that the renderer repeats:

| Block | Use |
|---|---|
| `<!-- item < -->` … `<!-- item > -->` | Item without children |
| `<!-- item-parent < -->` … `<!-- item-parent > -->` | Item with children; children go into `[[item#children]]`, recursively |
| `<!-- item-separator < -->` … `<!-- item-separator > -->` | Separator |
| `<!-- no-item < -->` … `<!-- no-item > -->` | Shown when the menu has no items |

Per-item variables: `[[item#label]]`, `[[item#url]]`, `[[item#target]]`, `[[item#slug]]`, `[[item#css_classes]]` and `[[item#children]]`. The database form with at signs (`@[[item#url]]@`) is also accepted, and the at signs are consumed by the replacement.

Rules worth knowing:
- **Without `item-parent`**, the tree is flattened: children are rendered at the same level with the `item` block. No item is lost.
- **Without `item-separator`**, separators are drawn with an empty `item` block.
- The rendered HTML replaces the **first** `item` (or `item-parent`) block; the other model blocks are removed.
- The menu's CSS, compiled CSS and `html_extra_head` are injected into `<head>` only once, even when the menu repeats on the page (`gestor_pagina_recursos_incluir()`).

Templates shipped with the core: `menus-horizontal-navbar`, `menus-vertical-sidebar`, `menus-footer-colunas`, `menus-dropdown`, `menus-breadcrumb` and `menus-mobile-hamburguer`.

### Browser behavior

Whenever the widget appears on a page, `menus.widget.js` is included automatically. Using event delegation, it:
- toggles the `.menu-mobile-list` list when `.menu-mobile-btn` is clicked (updating `aria-expanded`);
- opens and closes `.group-hover-sub-menu` on hover, as a fallback for when Tailwind group variants do not reach the final CSS.

## Data

The `menus` table: `id` (slug), `name`, `language`, `fields_schema` (JSON), `html`, `css`, `css_compiled`, `html_extra_head`, `status`, `versao`, `plugin`, `user_modified` and `system_updated`, plus the dates.

```json
{
  "template_id": "menus-vertical-sidebar",
  "availability": "todos",
  "conditions": [],
  "menus": {
    "visible_to_all": [
      { "type": "pagina", "page_id": "comece-a-construir", "label": "", "children": [] },
      { "type": "cabecalho", "label": "Reference", "children": [
        { "type": "pagina", "page_id": "docs-reference-libraries-modelo", "children": [] }
      ]}
    ]
  }
}
```

Because it is website configuration, a menu can be versioned as a project **resource**: declare the `menus` table with `sync_resources` in the project's `resources/project_tables_config.json`. Records live in `resources/<lang>/menus.json` and the HTML in `resources/<lang>/menus/<id>/<id>.html`. This is the format `c2f docs:build` generates for the documentation sidebar.

## See also

- [modelo.php library](../libraries/modelo.md): the string functions the Gestor's templates are built on.
- [How to write documentation](../../guides/documentation.md)
