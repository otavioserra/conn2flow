---
title: "User profiles module"
description: "Profiles granting access to modules and operations."
section: reference
module: usuarios-perfis
sources:
  - gestor/modulos/usuarios-perfis/usuarios-perfis.php
  - gestor/modulos/usuarios-perfis/usuarios-perfis.js
  - gestor/modulos/usuarios-perfis/usuarios-perfis.json
  - gestor/modulos/usuarios-perfis/resources
  - gestor/db/migrations/20250723165543_create_usuarios_perfis_table.php
  - gestor/db/migrations/20250723165544_create_usuarios_perfis_modulos_table.php
  - gestor/db/migrations/20250723165545_create_usuarios_perfis_modulos_operacoes_table.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20250904103000_alter_usuarios_perfis_permissoes_add_plugin.php
verified_at: b6839aa1
---

# `usuarios-perfis` module

Defines permission sets for accounts. A profile associates module and specific operation slugs; a user receives the profile through `usuarios.id_usuarios_perfis`.

## How to use

At `usuarios-perfis/`, inspect profiles. Use `usuarios-perfis/adicionar/` to enter a name, select modules and operations, and optionally mark the profile as default. At `usuarios-perfis/editar/?id=<slug>`, review selections and change the default. Routes are the same in both languages. Group controls in the panel check or uncheck all boxes.

## Technical reference

The controller handles `listar`, `adicionar`, and `editar`; there is no active module AJAX case. Status and deletion belong to the shared interface. Creation and editing accept only slugs found among active modules and operations in the language; modules in the `bibliotecas` group or with a `host` value are excluded from selection. Editing compares current and checked associations, inserts or removes links, records history, and, when the name changes, generates a new slug and updates `perfil` in both link tables.

`usuarios_perfis` has numeric `id_usuarios_perfis`, `nome`, `id`, `padrao`, `status`, `versao`, and timestamps; later migrations add `language` and `plugin`. `usuarios_perfis_modulos` stores `perfil` and `modulo`; `usuarios_perfis_modulos_operacoes` stores `perfil` and `operacao`, with `plugin` added later. Links use slug strings, without foreign keys in the initial migrations. JSON specifies synchronization by `(language,id)`; that is compiler configuration, not a unique constraint declared in the examined migrations. The module has no widget, template, hooks, or `hooks.api`.

## Confirmed limitations

> [!WARNING]
> During creation, the code clears `padrao` from all profiles before inserting the new one, even when the default box was not checked. The update has no language filter. An ordinary creation can leave the installation without a default profile; review this setting after creation.

> [!CAUTION]
> Associations are written across multiple queries without a visible controller transaction. The controller also reads checkbox counts from the session when iterating over the form; a session/POST mismatch can omit items. Module and operation permissions are separate links.

## See also

- [Users](usuarios.md)
- [Module operations](modulos-operacoes.md)
