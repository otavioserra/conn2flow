---
title: "Module operations"
description: "Registering granular operations associated with modules."
section: reference
module: modulos-operacoes
sources:
  - gestor/modulos/modulos-operacoes/modulos-operacoes.php
  - gestor/modulos/modulos-operacoes/modulos-operacoes.js
  - gestor/modulos/modulos-operacoes/modulos-operacoes.json
  - gestor/modulos/modulos-operacoes/resources
  - gestor/db/migrations/20250723165529_create_modulos_operacoes_table.php
  - gestor/db/migrations/20250902140000_alter_modulos_operacoes_table_add_modulo_id_remove_id_modulos.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
verified_at: b6839aa1
---

# `modulos-operacoes` module

Registers named module operations for granular grants in user profiles. The `operacao` slug is the link used by the permission table; `id` identifies the administrative record.

## How to use

Open `modulos-operacoes/`, create an entry at `modulos-operacoes/adicionar/` with a name and module, and enter an operation code when it should differ from the name. Edit at `modulos-operacoes/editar/?id=<slug>`. Select the operation on a profile screen to grant it. Routes are the same in both languages.

## Technical reference

The controller handles `listar`, `adicionar`, and `editar`; it has no active AJAX action. Status and deletion use the shared interface. Creation requires a name and module selection. `id` is derived from the name within the language; `operacao` is derived from its supplied value or the name, checking collisions within `modulo_id`. Editing can change name, module, and `operacao`, incrementing version and recording history. Current JS implements no editor behavior beyond page initialization.

`modulos_operacoes` has numeric `id_modulos_operacoes`, `id_usuarios`, `nome`, `id`, `operacao`, `status`, `versao`, and timestamps. A later migration replaced numeric `id_modulos` with string `modulo_id`; another added `language`. The examined migration declares neither a foreign key nor a unique slug index. The module JSON defines table synchronization but supplies no widget, template, hooks, or `hooks.api`.

`usuarios_perfis_modulos_operacoes.operacao` stores the slug granted to a profile. The operation must match the code checked by its consumer; registering it alone adds no permission check to module code.

## Confirmed limitations

> [!WARNING]
> Editing `operacao` updates the record but does not propagate the new slug to `usuarios_perfis_modulos_operacoes`. Profiles that granted the old code need review after a rename.

> [!CAUTION]
> The controller calculates `operacao` uniqueness within `modulo_id`, with no corresponding SQL constraint in the migration read. Direct database writes do not inherit that protection.

## See also

- [User profiles](usuarios-perfis.md)
- [Modules](modulos.md)
