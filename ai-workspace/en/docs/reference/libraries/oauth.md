---
title: "oauth.php library"
label: "Social login"
description: "Social login with Google and Meta (Facebook): authorization URL with state, code exchange and profile reading."
section: reference
order: 230
sources:
  - gestor/bibliotecas/oauth.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: f35b22ad
---

# `oauth.php` library

An OAuth 2.0 client to **sign in with Google or Meta**. Not to be confused with [oauth2.php](oauth2.md), the **server** of the Gestor API tokens. The screens and the login decision live in `perfil-usuario` (options `social-login` and `oauth-callback`).

## Configuration

In the `.env`: `OAUTH_GOOGLE_CLIENT_ID`/`OAUTH_GOOGLE_CLIENT_SECRET` and `OAUTH_META_APP_ID`/`OAUTH_META_APP_SECRET`. Without the client id, the provider is unavailable (`oauth_redirect_url()` returns `false`).

In the provider console, register the return URL that `oauth_redirect_uri()` builds: `https://<domain>/<root>/oauth-callback/?provider=google` (or `meta`). It uses `url-full-http`, always with `https`.

The keys are read only from `$_ENV`, not from `$_CONFIG`.

## Flow

1. `oauth_redirect_url($provider)` draws a `state` (128 bits), stores it in the session with the provider and returns the authorization URL. Scopes: `openid email profile` (Google) and `email public_profile` (Meta, Graph API v19.0).
2. The provider comes back with `code` and `state`. `oauth_validate_state($state)` compares it with the session (`hash_equals`).
3. `oauth_authenticate_code($provider, $code)` exchanges the code for the token and reads the profile. It returns `['provider', 'uid', 'email', 'nome']` or `false`.

`oauth_http_post()` and `oauth_http_get()` are the cURL helpers (20 s timeout).

## What `perfil-usuario` does with the profile

- With a link in `usuarios_provedores` (provider + `uid`), it signs in as that user.
- **Without a link, it looks for an active user with the same e-mail and creates the link by itself.**
- From the security screen, the logged-in user can link a social account to theirs.
- After a social login, the user's 2FA still applies.

> [!WARNING]
> The automatic link by e-mail does not check `email_verified` (Google) or whether the e-mail was confirmed at the provider. A provider account with an unverified e-mail equal to a site user's would sign in as that user (req-181, item A8). The `state` is also not deleted after use.

**Internal helpers** (used by the functions above; rarely called directly): `oauth_config()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/oauth.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `oauth_config(string $provider): array|false` — [line 30](../../../../../gestor/bibliotecas/oauth.php#L30)
  Retorna a configuração de endpoints/credenciais de um provedor.
  Parameters:
  - `$provider`: 'google' ou 'meta'.
  Returns: Configuração ou false se o provedor não for suportado.
- `oauth_redirect_uri(string $provider): string` — [line 64](../../../../../gestor/bibliotecas/oauth.php#L64)
  Monta a URI de callback do provedor (calculada a partir do domínio atual).
  Parameters:
  - `$provider`: 'google' ou 'meta'.
  Returns: URI de redirecionamento (ex.: https://dominio/_api/auth/callback/google).
- `oauth_redirect_url(string $provider): string|false` — [line 84](../../../../../gestor/bibliotecas/oauth.php#L84)
  Gera o URL de redirecionamento para a tela de consentimento do provedor. Grava o `state` (proteção CSRF) e o provedor na sessão.
  Parameters:
  - `$provider`: 'google' ou 'meta'.
  Returns: URL de autorização ou false se não configurado.
- `oauth_validate_state(string $state): bool` — [line 114](../../../../../gestor/bibliotecas/oauth.php#L114)
  Valida o parâmetro `state` retornado pelo provedor contra o gravado na sessão.
  Parameters:
  - `$state`: Valor recebido no callback.
  Returns: true se conferir.
- `oauth_http_post($url, $data)` — [line 123](../../../../../gestor/bibliotecas/oauth.php#L123)
- `oauth_http_get($url, $bearer = null)` — [line 139](../../../../../gestor/bibliotecas/oauth.php#L139)
- `oauth_authenticate_code(string $provider, string $code): array|false` — [line 168](../../../../../gestor/bibliotecas/oauth.php#L168)
  Troca o código de autorização por um token de acesso e retorna o perfil verificado do usuário.
  Parameters:
  - `$provider`: 'google' ou 'meta'.
  - `$code`: Código de autorização recebido no callback.
  Returns: ['provider','uid','email','nome'] ou false em erro.

<!-- c2f:extract:end -->
