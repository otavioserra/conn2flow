---
title: "User profile module"
description: "Authentication, signup, profile, 2FA, sessions, and personal API keys."
section: reference
module: perfil-usuario
sources:
  - gestor/modulos/perfil-usuario/perfil-usuario.php
  - gestor/modulos/perfil-usuario/perfil-usuario.js
  - gestor/modulos/perfil-usuario/perfil-usuario.json
  - gestor/modulos/perfil-usuario/resources
  - gestor/db/migrations/20250723165538_create_usuarios_table.php
  - gestor/db/migrations/20250723165548_create_usuarios_tokens_table.php
  - gestor/db/migrations/20260706100000_add_two_factor_to_usuarios_table.php
  - gestor/db/migrations/20260706100010_create_usuarios_provedores_table.php
  - gestor/db/migrations/20260818100000_create_usuarios_api_tokens_table.php
verified_at: b6839aa1
---

# `perfil-usuario` module

Holds user identity flows: sign in and out, signup, email confirmation, password recovery, social login, OAuth, second factor, and self-service account editing. It also manages sessions and personal API keys when policy allows.

## How to use

Use `signin/` to enter, `signin-2fa/` to complete the second factor, and `signout/` to end a session. `signup/` creates an account and `email-confirmation/` confirms the address. For recovery, follow `forgot-password/`, `forgot-password-confirmation/`, `redefine-password/`, and `redefine-password-confirmation/`. `perfil-usuario/` edits the current account and has security, sessions, and, when allowed, API key tabs. Social login uses `social-login/` and `oauth-callback/`; `oauth-authenticate/` and `oauth-authenticate-2fa/` handle API authentication. `restrict-area/` and `validate-user/` are also available. Routes are identical in pt-br and en.

## Technical reference

The `$_GESTOR['opcao']` switch dispatches the routes above as `signin`, `signin-2fa`, `signup`, `editar`, `signout`, `area-restrita`, `validar-usuario`, `confirmacao-email`, `forgot-password*`, `redefine-password*`, `social-login`, `oauth-callback`, and `oauth-authenticate*`. The shared interface forces self-editing to the authenticated user's id. The `$_GESTOR['ajax-opcao']` switch handles `seguranca-2fa-email-enviar`, `seguranca-2fa-ativar`, `seguranca-2fa-desativar`, `seguranca-social-vincular`, `seguranca-social-desvincular`, `sessoes-revogar`, `sessoes-revogar-outras`, `api-token-gerar`, and `api-token-revogar`.

Primary data is in `usuarios` (`id_usuarios`, id, name, email, login, password hash, profile, status, and confirmation). Migrations add `two_factor_secret`, `two_factor_enabled`, `two_factor_type`, email code/expiration, and `two_factor_recovery_codes`. Social links are in `usuarios_provedores`; sessions in `usuarios_tokens`. `usuarios_api_tokens` stores owner, name, prefix, unique token hash, scopes, expiration, last use, status, and timestamps. The personal key secret is shown once in the creation response; listing displays only its prefix. The tab and issuance endpoints require a profile allowed by `AUTH_API_ALLOWED_PROFILES` and an available table; the list defaults to `1`.

Signup validates name, email, password of at least 12 characters, access, and CAPTCHA; creates the user and confirmation token, and conditionally sends email. The code emits actions `signup.start`, `signup.banco`, `signup.pos_banco`, `signup.end` and filters `signup.email` and `signup.redirect`. `signup.banco` receives the numeric id and name/email/id/plan/domain data after INSERT. `signup.email` can suppress the automatic email; `signup.redirect` changes the destination after signup. `signup.pos_banco` and `signup.end` are outside the write-only branch and can also run while displaying the page. Callback execution depends on registration in the hooks system. The module has no public widget or widget template; it supplies pages and UI components.

Normal sign-in may require TOTP or an email code; OAuth has a separate 2FA interception path. Security settings activate or deactivate 2FA, generate recovery codes, and link providers. Sessions settings revoke the user's other sessions; the current session has no revoke button. `signout` removes the current session token, clears auth cookies, and redirects to `signin/`.

## Confirmed limitations

> [!CAUTION]
> Personal API key authorization uses `AUTH_API_ALLOWED_PROFILES`, rather than a module CRUD permission. The clear-text token appears only on creation; copy it then. Revoking a session in `usuarios_tokens` and revoking a key in `usuarios_api_tokens` are distinct operations.

> [!WARNING]
> `signup.pos_banco` receives no user id argument and its position does not guarantee that a write occurred. Integrations that depend on the newly created account should use `signup.banco` and handle later failures separately.

## See also

- [Users](usuarios.md)
- [User profiles](usuarios-perfis.md)
