---
title: "oauth2.php library"
label: "API tokens (OAuth2)"
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
  Calcula os IDs que devem ser revogados antes de inserir um novo access token. Mantém no máximo $maximo tokens pelo critério FIFO (data e ID como desempate).
- `oauth2_gerar_token_client_credentials(array $params = false): array|false` — [line 56](../../../../../gestor/bibliotecas/oauth2.php#L56)
  Gera tokens OAuth 2.0 usando credenciais de usuário do sistema.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['id_usuarios']`: ID do usuário (obrigatório)
  - `$params['grant_type']`: Tipo de grant (obrigatório, deve ser 'client_credentials')
  - `$params['scope']`: Escopo opcional (padrão: 'read')
  - `$params['url_redirect']`: URL para redirecionamento após autenticação (opcional)
  Returns: Array com tokens ou false em erro
- `oauth2_validar_token(array $params = false): array|false` — [line 244](../../../../../gestor/bibliotecas/oauth2.php#L244)
  Valida token OAuth 2.0.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['token']`: Access token a ser validado (obrigatório)
  Returns: Dados do usuário se válido, false caso contrário
- `oauth2_autorizar_requisicao(array $params = false): array|false` — [line 366](../../../../../gestor/bibliotecas/oauth2.php#L366)
  Autoriza requisição usando token OAuth 2.0.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['header_authorization']`: Valor do header Authorization (opcional, pega automaticamente)
  Returns: Dados do usuário autorizado ou false
- `oauth2_limpar_tokens_expirados(): bool` — [line 400](../../../../../gestor/bibliotecas/oauth2.php#L400)
  Limpa tokens OAuth 2.0 expirados da tabela oauth2_tokens.
  Returns: True se limpeza executada com sucesso
- `oauth2_renovar_token(array $params = false): array|false` — [line 426](../../../../../gestor/bibliotecas/oauth2.php#L426)
  Renova access token usando refresh token.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['refresh_token']`: Refresh token para renovação (obrigatório)
  - `$params['scope']`: Escopo opcional (padrão: 'read')
  Returns: Novos tokens ou false em erro

<!-- c2f:extract:end -->
