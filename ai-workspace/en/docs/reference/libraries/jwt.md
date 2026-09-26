---
title: "jwt.php library"
label: "JWT"
description: "HS256 JWT tokens with versioned symmetric keys in the database, rotation and a grace period. It is not the Gestor login token."
section: reference
order: 140
sources:
  - gestor/bibliotecas/jwt.php
  - gestor/modulos/admin-environment/admin-environment.php
verified_at: f2653c2b
---

# `jwt.php` library

Generates and validates **HS256** JWTs with a set of symmetric keys stored in the database, with rotation and a grace period.

> [!IMPORTANT]
> This library is **not** the admin panel authentication. The login cookie uses another format, signed with the site's RSA key, in `gestor_permissao_token()` ([request lifecycle](../../concepts/request-lifecycle.md)). Today nothing in the core generates or validates tokens with `jwt.php`; the only use is the "rotate keys" button in `admin-environment`. It exists for project integrations.

## Keys

The set is stored as JSON in the `variaveis` table (`modulo='sistema'`, `id='jwt_keys'`), each key with `key_id`, `key_secret` (256 bits), `created_at`, `status` (`active`/`expired`) and `expired_at`. The first call to `jwt_generate_token()` creates the active key if there is none.

`jwt_rotate_keys()` expires the active key, creates a new one and deletes the expired ones whose grace period is over. The grace period is `AUTH_JWT_GRACE_HOURS` (default 24) hours counted from `expired_at`.

> [!WARNING]
> Rotation is **manual only** (the button in `admin-environment` or a call in code). `AUTH_JWT_ROTATION_DAYS` shows up in the panel, but no code rotates the keys by itself.

## Usage

```php
gestor_incluir_biblioteca('jwt');

$token = jwt_generate_token(['sub' => $userId, 'exp' => time() + 3600]);

try {
    $r = jwt_validate_token($token);          // ['status' => 'Active'|'Grace', 'payload' => [...]]
    if (($r['payload']['exp'] ?? 0) < time()) throw new Exception('Expired token.');
    if ($r['status'] === 'Grace') { /* issue a new token */ }
} catch (Exception $e) {
    // malformed, unknown kid, invalid signature or key outside the grace period
}
```

> [!CAUTION]
> `jwt_validate_token()` only checks the signature and the key. **It does not check `exp`, `nbf`, `iss` or `aud`**: a token without expiration is valid while the key is active or in its grace period. The validating code must check the claims, as in the example.

Other details:
- `iat` is added when missing; the header carries `kid` with the `key_id`.
- The header's `alg` is ignored (verification is always HMAC-SHA256), which prevents algorithm-confusion attacks.
- The secret keys are stored in plain text in the `variaveis` table: anyone who can read the database (or a backup) can issue tokens.

**Internal helpers** (used by the functions above; rarely called directly): `jwt_base64url_encode()`, `jwt_base64url_decode()`, `jwt_rotation_days()`, `jwt_grace_hours()`, `jwt_keys_load()`, `jwt_keys_save()`, `jwt_nova_chave()`, `jwt_get_active_key()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/jwt.php` by `c2f docs:extract` — 11 functions. Do not edit inside this block.

- `jwt_base64url_encode($data)` — [line 28](../../../../../gestor/bibliotecas/jwt.php#L28)
- `jwt_base64url_decode($data)` — [line 32](../../../../../gestor/bibliotecas/jwt.php#L32)
- `jwt_rotation_days()` — [line 40](../../../../../gestor/bibliotecas/jwt.php#L40)
- `jwt_grace_hours()` — [line 44](../../../../../gestor/bibliotecas/jwt.php#L44)
- `jwt_keys_load(): array` — [line 55](../../../../../gestor/bibliotecas/jwt.php#L55)
  Carrega o conjunto de chaves JWT do banco.
  Returns: Lista de chaves (cada uma com key_id, key_secret, created_at, status).
- `jwt_keys_save(array $keys): void` — [line 78](../../../../../gestor/bibliotecas/jwt.php#L78)
  Persiste o conjunto de chaves JWT no banco (insert ou update).
  Parameters:
  - `$keys`: Lista de chaves.
- `jwt_nova_chave(string $status = 'active'): array` — [line 106](../../../../../gestor/bibliotecas/jwt.php#L106)
  Cria uma nova estrutura de chave JWT.
  Parameters:
  - `$status`: 'active' ou 'expired'.
  Returns: Chave gerada.
- `jwt_get_active_key(): array` — [line 120](../../../../../gestor/bibliotecas/jwt.php#L120)
  Obtém a chave ativa; cria uma se ainda não existir.
  Returns: Chave ativa.
- `jwt_generate_token(array $payload): string` — [line 142](../../../../../gestor/bibliotecas/jwt.php#L142)
  Gera um token JWT assinado com a chave ativa.
  Parameters:
  - `$payload`: Reivindicações do token.
  Returns: Token JWT (header.payload.signature).
- `jwt_validate_token(string $token): array` — [line 169](../../../../../gestor/bibliotecas/jwt.php#L169)
  Valida um token JWT.
  Parameters:
  - `$token`: Token JWT.
  Returns: ['status' => 'Active'|'Grace', 'payload' => array]
- `jwt_rotate_keys(): array` — [line 216](../../../../../gestor/bibliotecas/jwt.php#L216)
  Rotaciona as chaves JWT: marca a ativa como expirada, gera uma nova ativa e purga as chaves expiradas que já ultrapassaram o período de carência.
  Returns: A nova chave ativa.

<!-- c2f:extract:end -->
