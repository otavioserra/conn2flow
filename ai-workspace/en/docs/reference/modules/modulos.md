---
title: "Modules catalog"
description: "Administrative module records and configuration variables."
section: reference
module: modulos
sources:
  - gestor/modulos/modulos/modulos.php
  - gestor/modulos/modulos/modulos.js
  - gestor/modulos/modulos/modulos.json
  - gestor/modulos/modulos/resources
  - gestor/db/migrations/20250723165527_create_modulos_table.php
  - gestor/db/migrations/20250902180000_alter_modulos_table_grupo_id.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20260217100000_add_hooks_to_modulos_table.php
  - gestor/db/migrations/20260820140000_alter_modulos_add_icone_tailwind.php
  - gestor/db/migrations/20260821100000_alter_modulos_update_icones_projetos.php
verified_at: b6839aa1
---

# `modulos` module

Manages the panel's module catalog: name, group, title, icons, plugin, menu presence, and host flag. It also provides a configuration variables page for each module.

## How to use

Open `modulos/`, create at `modulos/adicionar/`, and edit at `modulos/editar/?id=<slug>`. Choose a group, Fomantic and, where available, Tailwind/Lucide icons, and menu display. At `modulos/variaveis/?id=<slug>`, edit that module's configuration variables. JSON also lists `modulos/sincronizar-bancos/` and `modulos/copiar-variaveis/`; read the limitations below before using them. Routes are identical in both languages.

## Technical reference

The controller handles `listar`, `adicionar`, `editar`, `variaveis`, and `copiar-variaveis`. For `variaveis`, it switches the shared interface to `alteracoes` and calls `configuracao_administracao`/`configuracao_administracao_salvar`. Status and deletion are shared actions. The AJAX switch has no active case. JSON supplies no widget, template, hooks, or `hooks.api` for this module.

`modulos` has numeric `id_modulos`, `id_usuarios`, `nome`, `id`, `titulo`, `icone`, `icone2`, `nao_menu_principal`, `plugin`, `host`, `status`, `versao`, and timestamps. Migrations replaced `id_modulos_grupos` with string `modulo_grupo_id` and added `language`, `hooks`, `icone_tailwind`, and `icone2_tailwind`. Menu rendering selects icons by framework, falling back to Fomantic fields. JSON configures synchronization with natural key `(language,id)`; the migrations examined do not declare an equivalent SQL uniqueness constraint. `hooks` signals to the gateway that hooks are configured; it does not contain callbacks.

## Confirmed limitations

> [!WARNING]
> `copiar-variaveis` is dispatched but returns immediately because `$ativar = false`; the copying code below that guard does not run. `sincronizar-bancos` is in the page JSON but has no case in this controller's `$_GESTOR['opcao']` switch.

> [!CAUTION]
> `nao_menu_principal` controls menu presentation, not access. Profile permissions are separate links in `usuarios_perfis_modulos` and `usuarios_perfis_modulos_operacoes`.

## See also

- [Module operations](modulos-operacoes.md)
- [User profiles](usuarios-perfis.md)
