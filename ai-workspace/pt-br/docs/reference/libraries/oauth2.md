---
title: "Biblioteca oauth2.php"
description: "Servidor de tokens da API do Gestor: access e refresh tokens assinados com a chave RSA, limite por usuário, validação do Bearer e renovação."
section: reference
order: 240
sources:
  - gestor/bibliotecas/oauth2.php
  - gestor/controladores/api/api.php
  - gestor/controladores/api/api-auth.php
verified_at: f35b22ad
---

# Biblioteca `oauth2.php`

Emite e valida os tokens com que sistemas externos chamam a API do Gestor (`_api/…`). Não é o login social ([oauth.php](oauth.md)).

## Tokens

`oauth2_gerar_token_client_credentials(['id_usuarios' => …, 'grant_type' => 'client_credentials', 'scope' => …])` recebe um usuário **já autenticado** e devolve:

```json
{ "access_token": "…", "token_type": "Bearer", "expires_in": 3600, "refresh_token": "…", "scope": "read" }
```

- Os dois tokens usam o formato JWT do Gestor, assinados com a **chave RSA privada** do site (`chaves/gestor/privada.key`, via `autenticacao_gerar_jwt_chave_privada()`).
- Cada um tem um `pubID` aleatório, gravado em `oauth2_tokens` com o HMAC `USUARIO_HASH_PASSWORD`, IP, User-Agent e validade. Revogar um token é apagar a linha.
- Validade: `oauth2.token-expiration` (3600 s) e `oauth2.refresh-token-expiration` (30 dias), de `$_CONFIG`.
- Um usuário tem no máximo `oauth2.maximo-tokens-usuario` (5) access tokens ativos: o mais antigo é revogado (`oauth2_fifo_ids_para_revogar()`).
- Toda emissão apaga antes os tokens vencidos de todos os usuários (`oauth2_limpar_tokens_expirados()`).

## Validar e renovar

- `oauth2_autorizar_requisicao()` lê `Authorization: Bearer <token>` e chama `oauth2_validar_token()`, que confere a assinatura com a chave pública, o `pubID` e o HMAC no banco, o tipo `access`, a validade e se o usuário está ativo. Devolve `id_usuarios`, `nome`, `email`, `usuario`, `id_usuarios_perfis` e `scope`, ou `false`.
- `oauth2_renovar_token(['refresh_token' => …])` valida o refresh token, **apaga-o** (uso único) e emite um par novo. O access token anterior continua válido até vencer.

> [!WARNING]
> O `scope` é gravado no token e devolvido, mas **nenhum endpoint o confere**: um token dá acesso a tudo que o usuário pode fazer. O `iss` vem do cabeçalho `Host` da requisição e também não é validado.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/oauth2.php` por `c2f docs:extract` — 6 funções. Não edite dentro deste bloco.

- `oauth2_fifo_ids_para_revogar($tokens, $maximo)` — [linha 23](../../../../../gestor/bibliotecas/oauth2.php#L23)
- `oauth2_gerar_token_client_credentials(array $params = false): array|false` — [linha 56](../../../../../gestor/bibliotecas/oauth2.php#L56)
- `oauth2_validar_token(array $params = false): array|false` — [linha 244](../../../../../gestor/bibliotecas/oauth2.php#L244)
- `oauth2_autorizar_requisicao(array $params = false): array|false` — [linha 366](../../../../../gestor/bibliotecas/oauth2.php#L366)
- `oauth2_limpar_tokens_expirados(): bool` — [linha 400](../../../../../gestor/bibliotecas/oauth2.php#L400)
- `oauth2_renovar_token(array $params = false): array|false` — [linha 426](../../../../../gestor/bibliotecas/oauth2.php#L426)

<!-- c2f:extract:end -->
