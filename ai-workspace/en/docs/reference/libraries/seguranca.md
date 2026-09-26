---
title: "seguranca.php library"
label: "Security"
description: "Admin panel CSRF, CAPTCHA verification (reCAPTCHA and Turnstile), session User-Agent and IP checks, random tokens and normalization of the post-login return path."
section: reference
order: 40
sources:
  - gestor/bibliotecas/seguranca.php
  - gestor/gestor.php
  - gestor/bibliotecas/formulario.php
verified_at: ffb8b0fa
---

# `seguranca.php` library

It gathers the Gestor's request defenses. The router loads it on every request, to validate CSRF before any module runs ([request lifecycle](../../concepts/request-lifecycle.md)).

## CSRF

`seguranca_csrf_requisicao_validar()` runs in `gestor_start()` and only requires the token when **all** these conditions hold:

- the method is POST, PUT, PATCH or DELETE;
- the request carries the authentication cookie (`COOKIE_AUTHNAME`): visitors' public forms do not go through here and are protected by CAPTCHA;
- the route is not exempt: `_api/…`, `api/…` and `_gestor-csrf-token`, plus the `admin-atualizacoes` transition exceptions for clients older than 2.9.25.

On failure, the answer is **403** with `{"status":"error","code":"CSRF_INVALID_OR_EXPIRED",…}`.

| Function | |
|---|---|
| `gestor_csrf_token()` | The session token (64 hex), created on the first call. It goes into `<meta name="csrf-token">` and `gestor.csrfToken` |
| `seguranca_csrf_token_requisicao()` | Reads the token from the `X-CSRF-Token` header, the `_csrf_token` POST field or `$_REQUEST` |
| `gestor_csrf_validar($token, $expected = null)` | Compares with `hash_equals()` |
| `seguranca_csrf_token_resposta($ctx)` | Decides the answer of the `_gestor-csrf-token/` route (`global.js` uses it to renew an expired token): 200 with the token, or 401 `AUTH_EXPIRED` with `X-Gestor-Auth-Redirect` if the login expired |
| `seguranca_csrf_retorno_normalizar($r)` | Accepts only a relative path (no scheme, `//`, `:`, `..` even encoded, or control characters) to return to after login |

> [!WARNING]
> **GET** requests are never checked. Every action that changes data must be a POST; the GET actions of the CRUD interface are item A2 of req-181. A project page whose path starts with `api/` is exempt too.

In your own code, forms sent with `fetch`/`$.ajax` already carry the token (`global.js` attaches it). In a plain HTML `<form>`, include the field:

```html
<input type="hidden" name="_csrf_token" value="…value of gestor_csrf_token()…">
```

## CAPTCHA

`gestor_captcha_validar($token = null, $options = [])` checks the token with the `CAPTCHA_PROVIDER` provider (`google-recaptcha`, `cloudflare-turnstile` or `none`, which always approves).

- Without `$token`, it reads `cf-turnstile-response`, `g-recaptcha-response` or `token` from POST.
- Options: `provider`, `secret`, `v2` (uses the reCAPTCHA v2 key), `action` (required in v3: checks the action and a minimum score of **0.5**), `return_response`, `transport` (injection for tests).
- It returns the provider's response array on success, or `false`. 5 s timeout.

In reCAPTCHA v3, the score is only checked when `action` is passed: without it, any valid token passes, even a bot's.

## Session: User-Agent and IP

At login, `seguranca_sessao_registrar()` stores in the session the User-Agent (255 characters) and the IP block (`/24` for IPv4; the full address for IPv6). On every authenticated request, `gestor_permissao_token()` calls `seguranca_sessao_validar()`; on a mismatch, `seguranca_sessao_invalidar()` deletes the token from `usuarios_tokens` and the user goes back to the login.

> [!WARNING]
> The validation accepts sessions **without** the markers (those older than the protection). Since the session lasts 3 hours and the login cookie 15 days, a copied authentication cookie used without the session cookie gets a new session and passes. See req-181, item A7. With IPv6, periodic address changes (temporary addresses) log the user out.

The IP used is the raw `REMOTE_ADDR`, not the one from [ip_get()](ip.md): behind a reverse proxy, it is always the proxy's IP and the IP check tells nobody apart.

## Utilities

- `seguranca_token_aleatorio($bytes = 32)`: hexadecimal from `random_bytes()`, at least 16 bytes.
- `seguranca_ip_bloco($ip = null)`, `seguranca_user_agent()`.

**Internal helpers** (used by the functions above; rarely called directly): `seguranca_csrf_rota_isenta()`, `seguranca_csrf_resposta_invalida_corpo()`, `seguranca_csrf_atualizador_transicao_isento()`, `seguranca_csrf_atualizador_status_isento()`, `seguranca_csrf_atualizador_sessao_legada_isento()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/seguranca.php` by `c2f docs:extract` — 18 functions. Do not edit inside this block.

- `gestor_captcha_validar(?string $token = null, array $opcoes = []): array|bool` — [line 23](../../../../../gestor/bibliotecas/seguranca.php#L23)
  Valida o provedor configurado. O transporte pode ser injetado nos testes.
- `seguranca_token_aleatorio(int $bytes = 32): string` — [line 78](../../../../../gestor/bibliotecas/seguranca.php#L78)
  Gera um identificador hexadecimal com entropia criptograficamente segura.
  Parameters:
  - `$bytes`: Quantidade de bytes aleatórios (mínimo: 16 / 128 bits).
- `seguranca_ip_bloco(string|null $ip = null): string` — [line 91](../../../../../gestor/bibliotecas/seguranca.php#L91)
  Retorna o bloco de rede do IP (3 primeiros octetos no IPv4).
  Parameters:
  - `$ip`: IP a avaliar (padrão: REMOTE_ADDR).
  Returns: Bloco de rede (ex.: "200.100.50") ou o IP original.
- `seguranca_user_agent(): string` — [line 108](../../../../../gestor/bibliotecas/seguranca.php#L108)
  Retorna o User-Agent atual (truncado).
- `seguranca_sessao_registrar(): void` — [line 119](../../../../../gestor/bibliotecas/seguranca.php#L119)
  Registra na sessão o User-Agent e o bloco de IP do cliente no momento do login.
- `seguranca_sessao_validar(): bool` — [line 132](../../../../../gestor/bibliotecas/seguranca.php#L132)
  Valida a conformidade do User-Agent e bloco de IP atuais com os registrados.
  Returns: true se conforme (ou não registrado); false em discrepância suspeita.
- `seguranca_sessao_invalidar(string|null $tokenPubId = null): void` — [line 152](../../../../../gestor/bibliotecas/seguranca.php#L152)
  Invalida a sessão/token atual em caso de sequestro suspeito.
  Parameters:
  - `$tokenPubId`: pubID do token de autorização a remover.
- `gestor_csrf_token(): string` — [line 168](../../../../../gestor/bibliotecas/seguranca.php#L168)
  Obtém o token CSRF da sessão, gerando-o na primeira chamada.
- `gestor_csrf_validar(string $token, $esperado = null): bool` — [line 185](../../../../../gestor/bibliotecas/seguranca.php#L185)
  Valida um token CSRF recebido contra o armazenado na sessão.
  Parameters:
  - `$token`: Token recebido na requisição.
- `seguranca_csrf_token_requisicao(): string` — [line 198](../../../../../gestor/bibliotecas/seguranca.php#L198)
  Obtém o token CSRF enviado em campo de formulário ou cabeçalho HTTP.
- `seguranca_csrf_rota_isenta(array $caminho): bool` — [line 213](../../../../../gestor/bibliotecas/seguranca.php#L213)
  Informa se a rota usa autenticação M2M/Bearer e, portanto, não usa cookie de sessão. O canal distribuído é protegido por HMAC dentro do controlador da API.
  Parameters:
  - `$caminho`: Segmentos normalizados da rota.
- `seguranca_csrf_resposta_invalida_corpo(string $mensagem): array` — [line 240](../../../../../gestor/bibliotecas/seguranca.php#L240)
  Corpo JSON da recusa por CSRF (req-107, `code` acrescentado na req-175).
  Parameters:
  - `$mensagem`: Mensagem legível já existente.
- `seguranca_csrf_token_resposta(array $contexto): array` — [line 260](../../../../../gestor/bibliotecas/seguranca.php#L260)
  Decide a resposta da rota `_gestor-csrf-token` (req-175). Função PURA: o roteador só emite.
  Parameters:
  - `$contexto`: token, tem_cookie_auth, autenticado, url_raiz.
  Returns: ['http' => int, 'headers' => array, 'corpo' => array]
- `seguranca_csrf_retorno_normalizar(mixed $retorno): string` — [line 307](../../../../../gestor/bibliotecas/seguranca.php#L307)
  Normaliza o caminho de retorno enviado pelo cliente para o pós-login (req-175).
  Parameters:
  - `$retorno`: Valor bruto recebido.
  Returns: Caminho terminado em `/`, ou ''.
- `seguranca_csrf_atualizador_transicao_isento(array $caminho, string $versao): bool` — [line 342](../../../../../gestor/bibliotecas/seguranca.php#L342)
  Mantém compatibilidade somente no autoatualizador que introduziu o CSRF.
  Parameters:
  - `$caminho`: Segmentos normalizados da rota.
  - `$versao`: Versão atual do Gestor.
- `seguranca_csrf_atualizador_status_isento(array $caminho, array $requisicao): bool` — [line 360](../../../../../gestor/bibliotecas/seguranca.php#L360)
  Reconhece a consulta de status do autoatualizador como operação de leitura.
  Parameters:
  - `$caminho`: Segmentos normalizados da rota.
  - `$requisicao`: Parâmetros recebidos pela requisição.
- `seguranca_csrf_atualizador_sessao_legada_isento(array $caminho, array $requisicao, string|null $rootPath = null, int|null $agora = null): bool` — [line 381](../../../../../gestor/bibliotecas/seguranca.php#L381)
  Permite concluir uma sessao do autoatualizador iniciada por um cliente anterior ao CSRF. O SID aleatorio e o estado persistido limitam a isencao a uma transicao real, recente, inacabada e na etapa esperada.
  Parameters:
  - `$caminho`: Segmentos normalizados da rota.
  - `$requisicao`: Parametros recebidos pela requisicao.
  - `$rootPath`: Raiz fisica do Gestor (injetavel nos testes).
  - `$agora`: Timestamp atual (injetavel nos testes).
- `seguranca_csrf_requisicao_validar(): bool` — [line 429](../../../../../gestor/bibliotecas/seguranca.php#L429)
  Exige CSRF em métodos mutáveis autenticados pelo cookie do painel.
  Returns: true quando a requisição pode continuar.

<!-- c2f:extract:end -->
