---
title: "Administrative interface"
description: "Menus, CRUD, Tailwind components, and iframe previews."
section: concepts
order: 100
sources:
  - gestor/gestor.php
  - gestor/bibliotecas/interface.php
  - gestor/resources/en/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html
  - gestor/modulos/admin-layouts/resources/en/components/modal-layout/modal-layout.html
verified_at: 3b099ff0
---

# Administrative interface

The Gestor builds the menu from modules and groups available to the user. `gestor_pagina_menu()` selects `menu-principal-sistema-tailwind` for Tailwind screens and the classic menu otherwise. The Tailwind administrative layout provides the page structure; `interface.php` builds forms, listings, history, and alerts for modules using the standard CRUD interface.

The layout and component editors use modals with an iframe preview. The iframe receives a separate document to show the resource with its own CSS cascade. What appears there depends on how the preview resolves HTML, CSS, and variables; check the published result after saving. Alert modals can also appear on public screens, so their Tailwind styles are system dependencies.

Administrative listings use DataTables with module AJAX responses. Status and delete controls are assembled by the interface. See the [interface library reference](../reference/libraries/interface.md) for its contracts and current security limitations.

> [!NOTE]
> Tailwind and classic screens can coexist in one installation; the menu component follows the rendered page type.
