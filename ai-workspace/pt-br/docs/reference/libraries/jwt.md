---
title: "Biblioteca jwt.php"
label: "JWT"
description: "Tokens JWT HS256 com chaves simétricas versionadas no banco, rotação e período de carência. Não é o token de login do Gestor."
section: reference
order: 140
sources:
  - gestor/bibliotecas/jwt.php
  - gestor/modulos/admin-environment/admin-environment.php
verified_at: f2653c2b
---

# Biblioteca `jwt.php`

Gera e valida JWT **HS256** com um conjunto de chaves simétricas guardado no banco, com rotação e período de carência.

> [!IMPORTANT]
> Esta biblioteca **não** é a autenticação do painel. O cookie de login usa outro formato, assinado com a chave RSA do site, em `gestor_permissao_token()` ([ciclo de uma requisição](../../concepts/request-lifecycle.md)). Hoje nada no core gera nem valida tokens com `jwt.php`; o único uso é o botão "rotacionar chaves" do `admin-environment`. Ela existe para integrações de projetos.

## Chaves

O conjunto fica em JSON na tabela `variaveis` (`modulo='sistema'`, `id='jwt_keys'`), cada chave com `key_id`, `key_secret` (256 bits), `created_at`, `status` (`active`/`expired`) e `expired_at`. A primeira chamada a `jwt_generate_token()` cria a chave ativa se não houver nenhuma.

`jwt_rotate_keys()` expira a chave ativa, cria uma nova e apaga as expiradas cuja carência já passou. A carência é `AUTH_JWT_GRACE_HOURS` (padrão 24) horas contadas de `expired_at`.

> [!WARNING]
> A rotação é **só manual** (botão no `admin-environment` ou chamada no código). `AUTH_JWT_ROTATION_DAYS` aparece no painel, mas nenhum código rotaciona as chaves sozinho.

## Uso

```php
gestor_incluir_biblioteca('jwt');

$token = jwt_generate_token(['sub' => $idUsuario, 'exp' => time() + 3600]);

try {
    $r = jwt_validate_token($token);          // ['status' => 'Active'|'Grace', 'payload' => [...]]
    if (($r['payload']['exp'] ?? 0) < time()) throw new Exception('Token expirado.');
    if ($r['status'] === 'Grace') { /* emitir um token novo */ }
} catch (Exception $e) {
    // malformado, kid desconhecido, assinatura inválida ou chave fora da carência
}
```

> [!CAUTION]
> `jwt_validate_token()` confere só a assinatura e a chave. **Não verifica `exp`, `nbf`, `iss` nem `aud`**: um token sem expiração vale enquanto a chave estiver ativa ou em carência. Quem valida precisa conferir as reivindicações, como no exemplo.

Outros detalhes:
- `iat` é acrescentado se ausente; o cabeçalho leva `kid` com o `key_id`.
- O `alg` do cabeçalho é ignorado (a verificação é sempre HMAC-SHA256), o que evita o ataque de troca de algoritmo.
- As chaves secretas ficam em texto claro na tabela `variaveis`: quem lê o banco (ou um *backup*) consegue emitir tokens.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `jwt_base64url_encode()`, `jwt_base64url_decode()`, `jwt_rotation_days()`, `jwt_grace_hours()`, `jwt_keys_load()`, `jwt_keys_save()`, `jwt_nova_chave()`, `jwt_get_active_key()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/jwt.php` por `c2f docs:extract` — 11 funções. Não edite dentro deste bloco.

- `jwt_base64url_encode($data)` — [linha 28](../../../../../gestor/bibliotecas/jwt.php#L28)
- `jwt_base64url_decode($data)` — [linha 32](../../../../../gestor/bibliotecas/jwt.php#L32)
- `jwt_rotation_days()` — [linha 40](../../../../../gestor/bibliotecas/jwt.php#L40)
- `jwt_grace_hours()` — [linha 44](../../../../../gestor/bibliotecas/jwt.php#L44)
- `jwt_keys_load(): array` — [linha 55](../../../../../gestor/bibliotecas/jwt.php#L55)
- `jwt_keys_save(array $keys): void` — [linha 78](../../../../../gestor/bibliotecas/jwt.php#L78)
- `jwt_nova_chave(string $status = 'active'): array` — [linha 106](../../../../../gestor/bibliotecas/jwt.php#L106)
- `jwt_get_active_key(): array` — [linha 120](../../../../../gestor/bibliotecas/jwt.php#L120)
- `jwt_generate_token(array $payload): string` — [linha 142](../../../../../gestor/bibliotecas/jwt.php#L142)
- `jwt_validate_token(string $token): array` — [linha 169](../../../../../gestor/bibliotecas/jwt.php#L169)
- `jwt_rotate_keys(): array` — [linha 216](../../../../../gestor/bibliotecas/jwt.php#L216)

<!-- c2f:extract:end -->
