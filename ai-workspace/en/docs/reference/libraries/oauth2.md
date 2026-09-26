---
title: "oauth2.php library"
description: "Token server of the Gestor API: access and refresh tokens signed with the RSA key, a per-user limit, Bearer validation and renewal."
section: reference
order: 240
sources:
  - gestor/bibliotecas/oauth2.php
  - gestor/controladores/api/api.php
  - gestor/controladores/api/api-auth.php
verified_at: f35b22ad
---

# `oauth2.php` library

Issues and validates the tokens external systems use to call the Gestor API (`_api/…`). It is not the social login ([oauth.php](oauth.md)).

## Tokens

`oauth2_gerar_token_client_credentials(['id_usuarios' => …, 'grant_type' => 'client_credentials', 'scope' => …])` takes an **already authenticated** user and returns:

```json
{ "access_token": "…", "token_type": "Bearer", "expires_in": 3600, "refresh_token": "…", "scope": "read" }
```

- Both tokens use the Gestor JWT format, signed with the site's **private RSA key** (`chaves/gestor/privada.key`, via `autenticacao_gerar_jwt_chave_privada()`).
- Each one has a random `pubID`, stored in `oauth2_tokens` with the `USUARIO_HASH_PASSWORD` HMAC, IP, User-Agent and expiration. Revoking a token means deleting the row.
- Lifetime: `oauth2.token-expiration` (3600 s) and `oauth2.refresh-token-expiration` (30 days), from `$_CONFIG`.
- A user has at most `oauth2.maximo-tokens-usuario` (5) active access tokens: the oldest is revoked (`oauth2_fifo_ids_para_revogar()`).
- Every issuance first deletes the expired tokens of all users (`oauth2_limpar_tokens_expirados()`).

## Validating and renewing

- `oauth2_autorizar_requisicao()` reads `Authorization: Bearer <token>` and calls `oauth2_validar_token()`, which checks the signature with the public key, the `pubID` and HMAC in the database, the `access` type, the expiration and whether the user is active. It returns `id_usuarios`, `nome`, `email`, `usuario`, `id_usuarios_perfis` and `scope`, or `false`.
- `oauth2_renovar_token(['refresh_token' => …])` validates the refresh token, **deletes it** (single use) and issues a new pair. The previous access token stays valid until it expires.

> [!WARNING]
> The `scope` is stored in the token and returned, but **no endpoint checks it**: a token grants access to everything the user can do. The `iss` comes from the request's `Host` header and is not validated either.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/oauth2.php` by `c2f docs:extract` — 6 functions. Do not edit inside this block.

- `oauth2_fifo_ids_para_revogar($tokens, $maximo)` — [line 23](../../../../../gestor/bibliotecas/oauth2.php#L23)
- `oauth2_gerar_token_client_credentials(array $params = false): array|false` — [line 56](../../../../../gestor/bibliotecas/oauth2.php#L56)
- `oauth2_validar_token(array $params = false): array|false` — [line 244](../../../../../gestor/bibliotecas/oauth2.php#L244)
- `oauth2_autorizar_requisicao(array $params = false): array|false` — [line 366](../../../../../gestor/bibliotecas/oauth2.php#L366)
- `oauth2_limpar_tokens_expirados(): bool` — [line 400](../../../../../gestor/bibliotecas/oauth2.php#L400)
- `oauth2_renovar_token(array $params = false): array|false` — [line 426](../../../../../gestor/bibliotecas/oauth2.php#L426)

<!-- c2f:extract:end -->
