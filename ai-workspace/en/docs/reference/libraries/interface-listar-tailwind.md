---
title: "interface-listar-tailwind.php library"
description: "Administrative interface-listar-tailwind behavior, persistence and permissions."
section: reference
order: 100
sources:
  - gestor/bibliotecas/interface-listar-tailwind.php
verified_at: 914c7b10
---

# interface-listar-tailwind.php library


The Tailwind listing variant shares interface configuration and AJAX. It does not load DataTables; interface-listar-tailwind.js consumes authorized columns and server responses.

- interface_listar_tailwind_configurar receives table/column settings, validates sortable IDs with interface_listar_coluna_segura and restores offset/page size. Allowed sizes are 10, 25, 50 or 100; default 25. Without declared ordering it uses the first sortable column. HTML is allowed only for columns whose declared formatar is an array.
- interface_listar_tailwind_finalizar counts non-deleted records using the declared filter, corrects offsets beyond the total, stores configuration in a module/action/user session, publishes JS variables and assembles the appropriate interface-listar variant. Actions translate icons to Lucide; texts come from interface variables.

Status and deletion retain the interface library's authorized flow. See [Interface](interface.md) and [Controls](controles.md).

## Generated functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/interface-listar-tailwind.php` by `c2f docs:extract` — 2 functions. Do not edit inside this block.

- `interface_listar_tailwind_configurar($params, $anterior = Array())` — [line 4](../../../../../gestor/bibliotecas/interface-listar-tailwind.php#L4)
  Listagem Tailwind (req-220): mesmo contrato AJAX e allowlist do servidor.
- `interface_listar_tailwind_finalizar($params)` — [line 44](../../../../../gestor/bibliotecas/interface-listar-tailwind.php#L44)

<!-- c2f:extract:end -->
