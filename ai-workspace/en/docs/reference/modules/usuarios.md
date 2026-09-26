---
title: "Users module"
description: "Administrative account creation and maintenance."
section: reference
module: usuarios
sources:
  - gestor/modulos/usuarios/usuarios.php
  - gestor/modulos/usuarios/usuarios.js
  - gestor/modulos/usuarios/usuarios.json
  - gestor/modulos/usuarios/resources
  - gestor/db/migrations/20250723165538_create_usuarios_table.php
  - gestor/db/migrations/20260706100000_add_two_factor_to_usuarios_table.php
  - gestor/db/migrations/20260706100010_create_usuarios_provedores_table.php
  - gestor/db/migrations/20260818100000_create_usuarios_api_tokens_table.php
verified_at: b6839aa1
---

# `usuarios` module

Manages accounts, credentials, and their assigned permission profiles. This is the administrative CRUD for `usuarios`; self-service profile editing, authentication, and password recovery live in `perfil-usuario`.

## How to use

Open `usuarios/` to list, `usuarios/adicionar/` to create, and `usuarios/editar/?id=<slug>` to change an account. Creation requires name, profile, email, username, and a password of at least 12 characters. The panel proposes the email as username when that field is empty. Inactivating or deleting an account removes its session tokens. Routes are identical in pt-br and en.

## Technical reference

The controller handles `listar`, `adicionar`, `editar`, `status`, and `excluir`; the AJAX switch has no active administrative case (the shown case is commented out). The shared interface provides listing, history, status, and deletion. On creation, the code checks duplicate `usuario` and `email`, derives a slug from the name, splits name parts, and hashes the password with `PASSWORD_ARGON2I`. Editing updates changed fields, records history, and removes the user's `usuarios_tokens` after changes. JS normalizes the name, displays its parts, and fills `usuario` from email when empty.

`usuarios` has numeric `id_usuarios`, `id_hosts`, `id_usuarios_perfis`, `nome_conta`, `nome`, `id`, `usuario`, `senha`, `email`, `primeiro_nome`, `nome_do_meio`, `ultimo_nome`, `status`, `versao`, timestamps, `email_confirmado`, `gestor`, and `gestor_perfil`. Later migrations add 2FA fields and recovery codes. Personal API tokens live in `usuarios_api_tokens`, and social providers in `usuarios_provedores`; this CRUD does not edit them directly. The initial migration declares no SQL uniqueness constraint on `usuario` or `email`: duplicate checks are in the controller. This module supplies no widget or templates, and its JSON declares no hooks or `hooks.api`.

## Confirmed limitations

> [!WARNING]
> The block intended to propagate a profile change to manager accounts on a host is guarded by `$desativado_hosts = true` and does not run. Changing the account profile does not recompute those child permissions.

> [!CAUTION]
> Explicit cleanup in `status`/`excluir` targets `usuarios_tokens` (sessions); this routine does not revoke `usuarios_api_tokens` records. Do not assume CRUD inactivation physically removes all stored credentials.

## See also

- [User profiles](usuarios-perfis.md)
- [My profile and authentication](perfil-usuario.md)
