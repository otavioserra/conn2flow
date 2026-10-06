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
  - gestor/bibliotecas/controles.php
  - gestor/assets/interface/controles.js
  - gestor/assets/interface/controles.css
  - gestor/assets/interface/campo-moeda.js
  - gestor/bibliotecas/interface-listar-tailwind.php
  - gestor/bibliotecas/admin-topbar.php
verified_at: 914c7b10
---

# Administrative interface

The administrative panel uses Tailwind CSS v4, Lucide icons and the shared controls library. The migration programme started in REQ-219 and consolidated in REQ-236 through REQ-240 covers the Core's 51 administrative modules, replacing Fomantic screens with Tailwind screens. It includes content, AI, forms, galleries, files, users, profiles, modules and configuration. Older resources can still declare Fomantic: effective framework selection considers both page and layout.

## Canonical components

| Class | Purpose |
| --- | --- |
| c2fc-campo | Groups label, input, help and error |
| c2fc-campo-entrada | Input and textarea |
| c2fc-campo-selecao | Native select enhanced through data-c2f-select |
| c2fc-botao | Button; primario, perigo and fantasma variants |
| c2fc-tabela | Table; c2fc-tabela-caixa provides local scrolling |
| c2fc-cartao | White card with title and content |
| c2fc-abas | Tabs; data-c2f-aba and data-c2f-painel identify sections |

These classes have rules in controles.css and work in PHP-generated HTML. Tailwind utilities must appear in the resource's declared compilation sources. Labels and messages come from system variables; helpers escape values.

## Selects and messages

Selects retain the native form element, with search, multiple choices, keyboard interaction and optional declared AJAX lookup. Floating panels avoid container clipping. observarSelects initializes elements added after loading and rebuilds cloned controls. Selection does not bubble clicks into outer labels. Cloning an instance does not copy its event listeners.

c2fControles.formatado accepts only b, strong, i, em, u, br and code tags, without attributes for messages; alerts use this path with the formatado option. It is not an arbitrary HTML renderer.

## Input masks

data-c2f-mascara="moeda" or c2fc-campo-moeda displays currency using pt-BR formatting and sends a decimal without a symbol during formdata. data-c2f-moeda sets the currency (BRL by default); data-c2f-moeda-campo references a currency selector. data-c2f-mascara="percentual" limits input to 0–100 and two decimal places, displays a comma and submits a dot. controles_incluir also loads campo-moeda.js on screens without interface-generated forms.

## Lists and navigation

Tailwind lists support search, ordering, paging and page sizes of 10, 25, 50 or 100, with per-user state. Ordinary values are text; only server-declared formatter columns allow HTML. Status and delete operations use the interface's authorized AJAX flow.

The top bar shows the user, favorites and content width (normal, expanded or full). Its favorites catalog filters permissions and safe paths. The sidebar highlights the current module. Dashboard favorites and cards are personal preferences.

See [Dashboard](../reference/modules/dashboard.md), [Controls](../reference/libraries/controles.md) and [CSS and Tailwind](css-and-tailwind.md).
