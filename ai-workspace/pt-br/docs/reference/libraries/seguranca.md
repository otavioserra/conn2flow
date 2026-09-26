---
title: "Biblioteca seguranca.php"
description: "CSRF do painel, verificação de CAPTCHA (reCAPTCHA e Turnstile), checagem de User-Agent e IP da sessão, tokens aleatórios e normalização do retorno pós-login."
section: reference
order: 40
sources:
  - gestor/bibliotecas/seguranca.php
  - gestor/gestor.php
  - gestor/bibliotecas/formulario.php
verified_at: ffb8b0fa
---

# Biblioteca `seguranca.php`

Reúne as defesas de requisição do Gestor. O roteador a carrega em toda requisição, para validar o CSRF antes de qualquer módulo ([ciclo de uma requisição](../../concepts/request-lifecycle.md)).

## CSRF

`seguranca_csrf_requisicao_validar()` roda em `gestor_start()` e só exige o token quando **todas** as condições valem:

- o método é POST, PUT, PATCH ou DELETE;
- a requisição traz o cookie de autenticação (`COOKIE_AUTHNAME`): formulários públicos de visitantes não passam por aqui e se protegem com CAPTCHA;
- a rota não é isenta: `_api/…`, `api/…` e `_gestor-csrf-token`, além das exceções de transição do `admin-atualizacoes` para clientes anteriores à 2.9.25.

Falhou, a resposta é **403** com `{"status":"error","code":"CSRF_INVALID_OR_EXPIRED",…}`.

| Função | |
|---|---|
| `gestor_csrf_token()` | O token da sessão (64 hex), criado na primeira chamada. Vai em `<meta name="csrf-token">` e em `gestor.csrfToken` |
| `seguranca_csrf_token_requisicao()` | Lê o token do cabeçalho `X-CSRF-Token`, do POST `_csrf_token` ou do `$_REQUEST` |
| `gestor_csrf_validar($token, $esperado = null)` | Compara com `hash_equals()` |
| `seguranca_csrf_token_resposta($ctx)` | Decide a resposta da rota `_gestor-csrf-token/` (o `global.js` a usa para renovar um token vencido): 200 com o token, ou 401 `AUTH_EXPIRED` com `X-Gestor-Auth-Redirect` se o login expirou |
| `seguranca_csrf_retorno_normalizar($r)` | Aceita só um caminho relativo (sem esquema, `//`, `:`, `..` mesmo codificado, nem controles) para voltar depois do login |

> [!WARNING]
> Requisições **GET** nunca são verificadas. Toda ação que altera dados precisa ser POST; as ações por GET da interface de CRUD são o item A2 da req-181. Uma página de projeto com caminho começando por `api/` também fica isenta.

Em código próprio, formulários enviados por `fetch`/`$.ajax` já levam o token (o `global.js` o anexa). Num `<form>` HTML comum, inclua o campo:

```html
<input type="hidden" name="_csrf_token" value="…valor de gestor_csrf_token()…">
```

## CAPTCHA

`gestor_captcha_validar($token = null, $opcoes = [])` confere o token no provedor de `CAPTCHA_PROVIDER` (`google-recaptcha`, `cloudflare-turnstile` ou `none`, que sempre aprova).

- Sem `$token`, lê `cf-turnstile-response`, `g-recaptcha-response` ou `token` do POST.
- Opções: `provider`, `secret`, `v2` (usa a chave do reCAPTCHA v2), `action` (exigida no v3: confere a ação e nota mínima **0,5**), `return_response`, `transport` (injeção para testes).
- Devolve o array de resposta do provedor em caso de sucesso, ou `false`. Tempo limite de 5 s.

No reCAPTCHA v3, a nota só é conferida quando `action` é passada: sem ela, qualquer token válido passa, mesmo de robô.

## Sessão: User-Agent e IP

No login, `seguranca_sessao_registrar()` guarda na sessão o User-Agent (255 caracteres) e o bloco de IP (`/24` no IPv4; o endereço inteiro no IPv6). A cada requisição autenticada, `gestor_permissao_token()` chama `seguranca_sessao_validar()`; divergiu, `seguranca_sessao_invalidar()` apaga o token de `usuarios_tokens` e o usuário volta ao login.

> [!WARNING]
> A validação aceita sessões **sem** as marcas (as anteriores à proteção). Como a sessão dura 3 horas e o cookie de login 15 dias, um cookie de autenticação copiado e usado sem o cookie de sessão ganha sessão nova e passa. Veja a req-181, item A7. No IPv6, a troca periódica de endereço (endereços temporários) derruba o login.

O IP usado é o `REMOTE_ADDR` cru, não o de [ip_get()](ip.md): atrás de proxy reverso, é sempre o IP do proxy e a checagem de IP não distingue ninguém.

## Utilitários

- `seguranca_token_aleatorio($bytes = 32)`: hexadecimal com `random_bytes()`, mínimo de 16 bytes.
- `seguranca_ip_bloco($ip = null)`, `seguranca_user_agent()`.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `seguranca_csrf_rota_isenta()`, `seguranca_csrf_resposta_invalida_corpo()`, `seguranca_csrf_atualizador_transicao_isento()`, `seguranca_csrf_atualizador_status_isento()`, `seguranca_csrf_atualizador_sessao_legada_isento()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/seguranca.php` por `c2f docs:extract` — 18 funções. Não edite dentro deste bloco.

- `gestor_captcha_validar(?string $token = null, array $opcoes = []): array|bool` — [linha 23](../../../../../gestor/bibliotecas/seguranca.php#L23)
- `seguranca_token_aleatorio(int $bytes = 32): string` — [linha 78](../../../../../gestor/bibliotecas/seguranca.php#L78)
- `seguranca_ip_bloco(string|null $ip = null): string` — [linha 91](../../../../../gestor/bibliotecas/seguranca.php#L91)
- `seguranca_user_agent(): string` — [linha 108](../../../../../gestor/bibliotecas/seguranca.php#L108)
- `seguranca_sessao_registrar(): void` — [linha 119](../../../../../gestor/bibliotecas/seguranca.php#L119)
- `seguranca_sessao_validar(): bool` — [linha 132](../../../../../gestor/bibliotecas/seguranca.php#L132)
- `seguranca_sessao_invalidar(string|null $tokenPubId = null): void` — [linha 152](../../../../../gestor/bibliotecas/seguranca.php#L152)
- `gestor_csrf_token(): string` — [linha 168](../../../../../gestor/bibliotecas/seguranca.php#L168)
- `gestor_csrf_validar(string $token, $esperado = null): bool` — [linha 185](../../../../../gestor/bibliotecas/seguranca.php#L185)
- `seguranca_csrf_token_requisicao(): string` — [linha 198](../../../../../gestor/bibliotecas/seguranca.php#L198)
- `seguranca_csrf_rota_isenta(array $caminho): bool` — [linha 213](../../../../../gestor/bibliotecas/seguranca.php#L213)
- `seguranca_csrf_resposta_invalida_corpo(string $mensagem): array` — [linha 240](../../../../../gestor/bibliotecas/seguranca.php#L240)
- `seguranca_csrf_token_resposta(array $contexto): array` — [linha 260](../../../../../gestor/bibliotecas/seguranca.php#L260)
- `seguranca_csrf_retorno_normalizar(mixed $retorno): string` — [linha 307](../../../../../gestor/bibliotecas/seguranca.php#L307)
- `seguranca_csrf_atualizador_transicao_isento(array $caminho, string $versao): bool` — [linha 342](../../../../../gestor/bibliotecas/seguranca.php#L342)
- `seguranca_csrf_atualizador_status_isento(array $caminho, array $requisicao): bool` — [linha 360](../../../../../gestor/bibliotecas/seguranca.php#L360)
- `seguranca_csrf_atualizador_sessao_legada_isento(array $caminho, array $requisicao, string|null $rootPath = null, int|null $agora = null): bool` — [linha 381](../../../../../gestor/bibliotecas/seguranca.php#L381)
- `seguranca_csrf_requisicao_validar(): bool` — [linha 429](../../../../../gestor/bibliotecas/seguranca.php#L429)

<!-- c2f:extract:end -->
