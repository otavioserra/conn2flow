---
title: "Module groups"
description: "Module catalog groups and menu presentation."
section: reference
module: modulos-grupos
sources:
  - gestor/modulos/modulos-grupos/modulos-grupos.php
  - gestor/modulos/modulos-grupos/modulos-grupos.js
  - gestor/modulos/modulos-grupos/modulos-grupos.json
  - gestor/modulos/modulos-grupos/resources
  - gestor/db/migrations/20250723165528_create_modulos_grupos_table.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20250904100000_alter_modulos_grupos_operacoes_add_plugin.php
  - gestor/db/migrations/20260820100000_alter_modulos_grupos_add_menu_label.php
verified_at: 45812d2e
---

# `modulos-grupos` module

Groups modules in the catalog and administrative menu. Each group has a name, slug, host option, menu label, and presentation order.

## How to use

Open `modulos-grupos/`, create at `modulos-grupos/adicionar/`, edit at `modulos-grupos/editar/?id=<slug>`, or clone at `modulos-grupos/clonar/?id=<slug>`. Enter a name; optionally set `menu_label`, `ordemMenu`, and host. Then assign modules to the group's slug in `modulos`. Routes are the same in pt-br and en.

## Technical reference

The controller handles `listar`, `adicionar`, `editar`, and `clonar`; status and deletion use the shared interface. The AJAX switch has no active case. Creation/cloning generates a slug within the language. Editing may generate a new slug after a name change, updates changed fields, and records history. Empty `menu_label` becomes NULL; `ordemMenu` accepts only numeric input converted to integer. JSON defines no widget, template, hooks, or `hooks.api`.

`modulos_grupos` has numeric `id_modulos_grupos`, `id_usuarios`, `nome`, `id`, `host`, `status`, `versao`, timestamps, and `ordemMenu`; later migrations add `language`, `plugin`, and `menu_label`. `modulos.modulo_grupo_id` stores the group's string slug. The migrations examined do not declare a foreign key for that link.

## Confirmed limitations

> [!WARNING]
> Editing the name changes the group slug but does not update `modulos.modulo_grupo_id`. Modules linked to the old slug need review. `host` and the menu label control classification/presentation; they do not grant access on their own.

## See also

- [Modules](modulos.md)
- [User profiles](usuarios-perfis.md)
