---
title: "dashboard module"
description: "Home page, cards, and the site's visual editing toolbar."
section: reference
module: dashboard
sources:
  - gestor/modulos/dashboard/dashboard.php
  - gestor/modulos/dashboard/dashboard.js
  - gestor/modulos/dashboard/dashboard.toolbar.js
  - gestor/modulos/dashboard/dashboard.iframe-toolbar.js
  - gestor/modulos/dashboard/dashboard.json
  - gestor/modulos/dashboard/resources
  - gestor/db/migrations/20250723165526_create_layouts_table.php
  - gestor/db/migrations/20250723165530_create_paginas_table.php
  - gestor/bibliotecas/html-editor.php
verified_at: 914c7b10
---

# dashboard module

This module provides Gestor's home page, permitted-module cards, update notices, a 3D dashboard, and a toolbar for editing site pages. The toolbar runs in an iframe, while dashboard.toolbar.js runs on the host page and can edit content, layouts, widgets, and metadata without opening the CRUD screen.

## How to use

Open dashboard/ for cards and notices; dashboard-3d/ is the alternate presentation. When viewing a site page with the toolbar available, dashboard-site-toolbar/ provides menus to edit the page, add widgets, inspect backups, restore a version, or open advanced editing. JSON declares dashboard-testes/ with the listar option, but the main switch has no handler for it. All four routes appear in pt-br and en.

## Technical reference

The page switch handles inicio, dashboard-3d, and dashboard-site-toolbar. AJAX handles site-toolbar-render/save, widget-types/widgets-list/widget-render, backups/backup-get/backup-restore, templates-load, ia-init/prompt/mode/request/prompt-new/prompt-edit/prompt-del, and page-config/page-config-save. Host-page JavaScript reconstructs variable/widget markers before saving; the iframe script coordinates menus and messages. site_toolbar.widgets_por_pagina defaults to 10.

The dashboard has no own table. Cards query modules/permissions; the toolbar reads and writes paginas and layouts in the current language. Saving updates changed content, version, user_modified, backup/history, and sitemap; layout saving preserves the head and requires the body marker. Page configuration stores OG, meta description, keywords, and featured image. The module fires hook_do_action('dashboard','start') on the home page and applies site-toolbar.permissao-pagina to the page record. JSON defines no own public widget, template, or hooks.api; the toolbar lists and renders widgets from other modules.

## Confirmed limitations

> [!WARNING]
> The per-page permission filter defaults to true without a handler; projects must register an extra restriction where users may edit only their own pages. AJAX handlers require the admin-paginas editar operation, and the toolbar hides controls when the filter denies a page. Visual availability of the toolbar does not replace handler checks.

> [!CAUTION]
> Saving a layout changes a resource shared by every page that uses it. dashboard-testes/ has no dedicated controller action. The home page also removes installation-success page records when found, so it is not solely a read-only screen.

## See also

- [Pages](admin-paginas.md)
- [Layouts](admin-layouts.md)
- [Published pages](publisher-pages.md)

## Dashboard V3.4: modules and widgets

The **Modules** tab lists authorized cards. P, M and G change density: P favors compact icons; M and G use the cover when available, falling back to an icon. Order and density are persisted per user. Cards open the module and expose editing/documentation when available.

In **Widgets and Metrics**, use Options to edit the grid: select a type and record, drag the handle, replace content or remove the instance. The grid has 12 columns; width follows columns and height ranges from 120 to 960 px in 20 px steps. Resize using the bottom-right corner. Content fills the remaining height below the header; each instance retains its parameters and selected record.

### Isolated document and CSS precedence

dashboard_ajax_widget_render validates the active record and language, selects only existing columns and resolves the template by target. It returns HTML, head, CSS, scripts, theme contract and tailwindcss-browser/Lucide asset URLs. JavaScript creates a srcdoc iframe with sandbox="allow-scripts", an opaque origin and fullscreen permission.

Head order is: layout precompiled CSS, theme contract, partial/template/record sheets and authored CSS, then the browser Tailwind compiler. The compiler's complete stylesheet closes the utilities layer. Loading a partial sheet afterward would let grid-cols-1 override responsive slide rules. Unlayered authored CSS keeps its precedence. Scripts and icons initialize within the isolated document; temporary server widget state is restored in finally.

Each iframe loads its own compiler, so cost grows with widget count. Fullscreen permission preserves isolation. See [CSS and Tailwind](../../concepts/css-and-tailwind.md).
