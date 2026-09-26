---
title: "autenticacao.php library"
label: "Authentication"
description: "The login token signed with the site's RSA key, RSA encryption of secrets, per-IP attempt control (login, sign-up, password recovery) and credential validation for the distributed channel."
section: reference
order: 50
sources:
  - gestor/bibliotecas/autenticacao.php
  - gestor/gestor.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
  - gestor/config.php
verified_at: 558004f2
---

# `autenticacao.php` library

Four topics: the **RSA JWT token** the Gestor uses in the login cookie and in the API, **encrypting and decrypting** values with the site's RSA keys, **per-IP attempt control** and the **distributed channel credentials**. The keys live in `autenticacoes/<domain>/chaves/gestor/` (`privada.key`, protected by `OPENSSL_PASSWORD`, and `publica.key`), see [global variables](../../concepts/global-variables.md).

## The RSA token

It is not a standard JWT (RS256): the header says `"alg": "RSA"`, and the "signature" is the `header.payload` itself **encrypted with the private key** (`openssl_private_encrypt`, in 245-byte blocks, each block base64-encoded and the whole again base64-encoded). Validating means decrypting with the public key and comparing with the received `header.payload`.

- `autenticacao_gerar_jwt_chave_privada(['host', 'expiration', 'pubID', 'chavePrivada', 'chavePrivadaSenha', 'payload' => optional])` builds the token. Without `payload`, it uses `{iss: host, exp: expiration, sub: pubID}`. It issues the [API](oauth2.md) tokens; the login cookie uses the same format, built by `usuario_gerar_token_autorizacao()` (usuario.php) and validated by `gestor_permissao_validar_jwt()`.
- `autenticacao_validar_jwt_chave_publica(['token', 'chavePublica', 'retornarPayloadCompleto'])` checks the signature, requires `exp` and `sub` and rejects expired tokens. It returns the `sub` (the `pubID`), the whole payload or `false`.

Validating the token is only the first step: `gestor_permissao_token()` still checks the `pubID` in `usuarios_tokens` with the `USUARIO_HASH_PASSWORD` HMAC ([request lifecycle](../../concepts/request-lifecycle.md)).

The inverted variants `autenticacao_gerar_jwt_chave_publica()` and `autenticacao_validar_jwt_chave_privada()`, and `autenticacao_cliente_gerar_jwt()` / `autenticacao_cliente_gerar_token_validacao()` (from the multi-host mode), **have no callers**.

## Encrypting secrets

| Function | Encrypts with | Decrypts with |
|---|---|---|
| `autenticacao_encriptar_chave_privada()` / `autenticacao_decriptar_chave_publica()` | private | public |
| `autenticacao_encriptar_chave_publica()` / `autenticacao_decriptar_chave_privada()` | public | private |

The first pair is the one used to store API keys ([AI](ia.md) servers): it hides the value from someone who only sees the database, but whoever has the database and the **public** key recovers it. For real confidentiality, encrypt with the public key and decrypt with the private one.

`autenticacao_openssl_gerar_chaves()` generates a 2048-bit RSA pair; `autenticacao_gerar_senha()` and `autenticacao_crypto_rand_secure()` generate passwords and random numbers. None of the three has callers in the core (the installer has its own key generator).

## Per-IP attempt control

The `acessos` table keeps, per **IP** (from [ip_get()](ip.md)) and **type** (`login`, `signup`, `forgot-password`), the count, the status and the block.

| Function | When to call |
|---|---|
| `autenticacao_acesso_verificar(['tipo'])` | Before showing/processing the form: `permitido`, `status` (`livre`, `antispam`, `bloqueado`) and `tempo_bloqueio` |
| `autenticacao_acesso_falha(['tipo'])` | Wrong password |
| `autenticacao_acesso_confirmar(['tipo'])` | Successful login: resets the count |
| `autenticacao_acesso_cadastrar(['tipo', 'antispam'])` | Each sign-up or recovery request |

Limits from the `.env`: at login, up to `ACESSOS_MAXIMO_LOGINS_SIMPLES` (3) failures the status is `livre`, up to `ACESSOS_MAXIMO_FALHAS_LOGINS` (10) it is `antispam`, and then `bloqueado`. For sign-ups, `ACESSOS_MAXIMO_CADASTROS_*` and `ACESSOS_MAXIMO_CADASTROS_SIMPLES_*` per type. Each block lasts `blocks × ACESSOS_TEMPO_BLOQUEIO_IP` seconds (growing with each repeat), and the record disappears after `ACESSOS_TEMPO_DESBLOQUEIO_IP` without activity. `autenticacao_acessos_limpeza()` runs on every check and applies both rules.

In `perfil-usuario`, the status decides the CAPTCHA: with reCAPTCHA, it is only required from `antispam` on; with Turnstile, always.

> [!NOTE]
> The control is per IP, not per account: attempts spread over many IPs against the same user are not slowed down, and behind a CDN every visitor may share the same IP (see [ip.php](ip.md)). The second factor does not go through here (req-181, item A5).

## Distributed channel

Used by the central side of the [distributed architecture](modulo-distribuido.md):
- `autenticacao_distribuido_validar_credenciais($user, $password)`: `password_verify` against `usuarios`, requires an active status;
- `autenticacao_distribuido_gerar_tokens($id_usuarios)`: [OAuth2](oauth2.md) tokens with the `distributed` scope;
- `autenticacao_distribuido_verificar_permissao_modulo()`: the user's profile has the target module;
- `autenticacao_distribuido_token_ativo()`: the channel access token is valid.

Channel credential validation does not go through the attempt control.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/autenticacao.php` by `c2f docs:extract` — 22 functions. Do not edit inside this block.

- `autenticacao_crypto_rand_secure(int $min, int $max): int` — [line 33](../../../../../gestor/bibliotecas/autenticacao.php#L33)
- `autenticacao_cliente_gerar_jwt(array|false $params = false): string|false` — [line 69](../../../../../gestor/bibliotecas/autenticacao.php#L69)
- `autenticacao_openssl_gerar_chaves(array|false $params = false): array|false` — [line 145](../../../../../gestor/bibliotecas/autenticacao.php#L145)
- `autenticacao_gerar_senha(int $length = 32): string` — [line 298](../../../../../gestor/bibliotecas/autenticacao.php#L298)
- `autenticacao_gerar_jwt_chave_publica(array|false $params = false): string|false` — [line 343](../../../../../gestor/bibliotecas/autenticacao.php#L343)
- `autenticacao_gerar_jwt_chave_privada(array|false $params = false): string|false` — [line 408](../../../../../gestor/bibliotecas/autenticacao.php#L408)
- `autenticacao_validar_jwt_chave_publica(array|false $params = false): array|string|false` — [line 480](../../../../../gestor/bibliotecas/autenticacao.php#L480)
- `autenticacao_validar_jwt_chave_privada(array|false $params = false): array|false` — [line 569](../../../../../gestor/bibliotecas/autenticacao.php#L569)
- `autenticacao_cliente_gerar_token_validacao(array|false $params = false): array` — [line 657](../../../../../gestor/bibliotecas/autenticacao.php#L657)
- `autenticacao_acesso_verificar(array|false $params = false): array` — [line 725](../../../../../gestor/bibliotecas/autenticacao.php#L725)
- `autenticacao_acesso_cadastrar(array|false $params = false): void` — [line 795](../../../../../gestor/bibliotecas/autenticacao.php#L795)
- `autenticacao_acesso_confirmar(array|false $params = false): void` — [line 914](../../../../../gestor/bibliotecas/autenticacao.php#L914)
- `autenticacao_acesso_falha(array|false $params = false): void` — [line 972](../../../../../gestor/bibliotecas/autenticacao.php#L972)
- `autenticacao_acessos_limpeza(array|false $params = false): void` — [line 1086](../../../../../gestor/bibliotecas/autenticacao.php#L1086)
- `autenticacao_encriptar_chave_publica(array|false $params = false): string|false` — [line 1116](../../../../../gestor/bibliotecas/autenticacao.php#L1116)
- `autenticacao_encriptar_chave_privada(array|false $params = false): string|false` — [line 1164](../../../../../gestor/bibliotecas/autenticacao.php#L1164)
- `autenticacao_decriptar_chave_publica(array|false $params = false): string|false` — [line 1212](../../../../../gestor/bibliotecas/autenticacao.php#L1212)
- `autenticacao_decriptar_chave_privada(array|false $params = false): string|false` — [line 1266](../../../../../gestor/bibliotecas/autenticacao.php#L1266)
- `autenticacao_distribuido_validar_credenciais(string $usuario, string $senha): array` — [line 1329](../../../../../gestor/bibliotecas/autenticacao.php#L1329)
- `autenticacao_distribuido_gerar_tokens(int $id_usuarios): array|false` — [line 1367](../../../../../gestor/bibliotecas/autenticacao.php#L1367)
- `autenticacao_distribuido_verificar_permissao_modulo(int $id_usuarios, string $modulo): bool` — [line 1397](../../../../../gestor/bibliotecas/autenticacao.php#L1397)
- `autenticacao_distribuido_token_ativo(string $token): bool` — [line 1505](../../../../../gestor/bibliotecas/autenticacao.php#L1505)

<!-- c2f:extract:end -->
