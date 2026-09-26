---
title: "stripe.php library"
label: "Stripe"
description: "REST client for the Stripe API: gateway credentials, customers, payments, subscriptions, product and price catalog, refunds and webhook validation."
section: reference
order: 310
sources:
  - gestor/bibliotecas/stripe.php
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: 2ede88ff
---

# `stripe.php` library

Direct calls to the Stripe REST API (`api.stripe.com`) with cURL, without the official SDK. The API version is pinned in `STRIPE_API_VERSION` (`2024-06-20`), so the response format does not change with the account settings.

## Credentials

The normal path is a `stripe`-type record of the `gateways_pagamentos` table (registered in the gateways module):

| Column | Use |
|---|---|
| `client_id` | Publishable key (`pk_…`) |
| `client_secret` | Secret key (`sk_…`) |
| `webhook_secret` | Webhook signing secret (`whsec_…`) |
| `ambiente` | `P` = production; any other value = sandbox |
| `moeda` | Default `BRL` |

```php
gestor_incluir_biblioteca('stripe');
stripe_gateways_pagamentos_configurar();            // the default gateway (padrao='S') or the first active one
stripe_gateways_pagamentos_configurar(['id' => 7]); // a specific gateway
```

The configuration goes into `$_CONFIG['stripe']`, which can also be filled by hand. `stripe_obter_credenciais()` returns `false` without a `secret_key`; `stripe_info()` summarizes the state; `stripe_testar_conexao()` calls `GET /v1/balance`. `stripe_is_modo_gateway()`, `stripe_obter_gateway_id()` and `stripe_obter_gateway_dados()` tell which gateway was loaded.

> [!NOTE]
> The keys are stored in plain text in the `gateways_pagamentos` table.

## Request

`stripe_requisicao(['endpoint' => '/v1/…', 'method' => 'GET'|'POST'|'DELETE', 'data' => [...], 'idempotency_key' => …])` returns `['http_code', 'data']` or `false` (no credentials or network error). **An API error (4xx/5xx) is not `false`**: check `http_code`. Errors go to `gestor/logs/stripe-<date>.log` through [log.php](log.md), without the key.

Amounts: `stripe_valor_menor_unidade(19.9, 'BRL')` → `1990`; `stripe_valor_decimal()` does the opposite; `stripe_moeda_sem_decimais()` covers JPY, KRW, VND, CLP and PYG.

## Operations

| Area | Functions |
|---|---|
| Customers | `stripe_obter_ou_criar_cliente(['email', 'nome', 'metadata'])`: looks up by e-mail before creating |
| One-off payment | `stripe_criar_payment_intent()`, `stripe_consultar_payment_intent()`, `stripe_consultar_setup_intent()` |
| Subscriptions | `stripe_criar_assinatura()` (born `default_incomplete`, returning the first invoice's `client_secret`, or `trialing` with `trial_period_days`), `stripe_consultar_assinatura()` (optional `expand`), `stripe_definir_metodo_padrao_assinatura()`, `stripe_trocar_preco_assinatura()` (upgrade/downgrade with proration, default `always_invoice`; body built by `stripe_troca_preco_payload()`), `stripe_suspender_assinatura()` (`pause_collection`), `stripe_ativar_assinatura()` (undoes a pause or a scheduled cancellation), `stripe_cancelar_assinatura()` |
| Refund | `stripe_reembolsar()` |
| Catalog | `stripe_criar_produto()`, `stripe_atualizar_produto()` (partial merge; body built by `stripe_produto_payload()`), `stripe_consultar_produto()`, `stripe_listar_produtos()`, `stripe_arquivar_produto()`; `stripe_criar_preco()`, `stripe_consultar_preco()`, `stripe_listar_precos()`, `stripe_arquivar_preco()` (a price is immutable in Stripe: changing the amount means creating another) |
| Support | `stripe_traduzir_status()` (status → Portuguese text), `stripe_log_registro()` |

## Webhook

`stripe_validar_webhook(['payload' => $rawBody, 'signature_header' => $header, 'tolerancia' => 300])` checks the `Stripe-Signature` header (HMAC-SHA256 of `t.payload` with the `webhook_secret`, constant-time comparison, 300 s window) and returns the decoded event or `false`.

The endpoint is `/_gateways/stripe/webhook` (the `plataforma-gateways` controller), which validates with the default gateway and fires the `stripe.webhook` hook.

> [!WARNING]
> In the modular form, `/_gateways/<module>/stripe/webhook`, the controller **does not validate** the signature: it hands the raw body and the header to the module's hook with `needs_validation => true`, and answers success to Stripe. The receiving module must call `stripe_validar_webhook()` with its gateway before trusting the event.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/stripe.php` by `c2f docs:extract` — 37 functions. Do not edit inside this block.

- `stripe_log_registro($logData = [])` — [line 20](../../../../../gestor/bibliotecas/stripe.php#L20)
- `stripe_gateways_pagamentos_configurar(array|false $params = false): bool` — [line 31](../../../../../gestor/bibliotecas/stripe.php#L31)
- `stripe_is_modo_gateway()` — [line 87](../../../../../gestor/bibliotecas/stripe.php#L87)
- `stripe_obter_gateway_id()` — [line 92](../../../../../gestor/bibliotecas/stripe.php#L92)
- `stripe_obter_gateway_dados()` — [line 97](../../../../../gestor/bibliotecas/stripe.php#L97)
- `stripe_obter_credenciais(): array|false` — [line 105](../../../../../gestor/bibliotecas/stripe.php#L105)
- `stripe_requisicao(array|false $params = false): array|false` — [line 134](../../../../../gestor/bibliotecas/stripe.php#L134)
- `stripe_moeda_sem_decimais($moeda)` — [line 204](../../../../../gestor/bibliotecas/stripe.php#L204)
- `stripe_valor_menor_unidade($valor, $moeda = 'BRL')` — [line 209](../../../../../gestor/bibliotecas/stripe.php#L209)
- `stripe_valor_decimal($valor, $moeda = 'BRL')` — [line 215](../../../../../gestor/bibliotecas/stripe.php#L215)
- `stripe_obter_ou_criar_cliente(array $params = Array()): array|false` — [line 230](../../../../../gestor/bibliotecas/stripe.php#L230)
- `stripe_criar_payment_intent(array $params = Array()): array|false` — [line 264](../../../../../gestor/bibliotecas/stripe.php#L264)
- `stripe_consultar_payment_intent($params = Array())` — [line 292](../../../../../gestor/bibliotecas/stripe.php#L292)
- `stripe_consultar_setup_intent(array $params = Array()): array|false` — [line 309](../../../../../gestor/bibliotecas/stripe.php#L309)
- `stripe_criar_assinatura(array $params = Array()): array|false` — [line 335](../../../../../gestor/bibliotecas/stripe.php#L335)
- `stripe_consultar_assinatura(array $params = Array()): array|false` — [line 401](../../../../../gestor/bibliotecas/stripe.php#L401)
- `stripe_definir_metodo_padrao_assinatura(array $params = Array()): array|false` — [line 428](../../../../../gestor/bibliotecas/stripe.php#L428)
- `stripe_cancelar_assinatura($params = Array())` — [line 441](../../../../../gestor/bibliotecas/stripe.php#L441)
- `stripe_troca_preco_payload(array $sub, array $params = Array()): array|false` — [line 468](../../../../../gestor/bibliotecas/stripe.php#L468)
- `stripe_trocar_preco_assinatura(array $params = Array()): array|false` — [line 503](../../../../../gestor/bibliotecas/stripe.php#L503)
- `stripe_suspender_assinatura($params = Array())` — [line 522](../../../../../gestor/bibliotecas/stripe.php#L522)
- `stripe_ativar_assinatura($params = Array())` — [line 541](../../../../../gestor/bibliotecas/stripe.php#L541)
- `stripe_reembolsar(array $params = Array()): array|false` — [line 577](../../../../../gestor/bibliotecas/stripe.php#L577)
- `stripe_criar_produto(array $params = Array()): array|false` — [line 603](../../../../../gestor/bibliotecas/stripe.php#L603)
- `stripe_produto_payload(array $params = Array()): array` — [line 635](../../../../../gestor/bibliotecas/stripe.php#L635)
- `stripe_atualizar_produto(array $params = Array()): array|false` — [line 662](../../../../../gestor/bibliotecas/stripe.php#L662)
- `stripe_consultar_produto($params = Array())` — [line 677](../../../../../gestor/bibliotecas/stripe.php#L677)
- `stripe_listar_produtos(array $params = Array()): array|false` — [line 690](../../../../../gestor/bibliotecas/stripe.php#L690)
- `stripe_arquivar_produto($params = Array())` — [line 705](../../../../../gestor/bibliotecas/stripe.php#L705)
- `stripe_criar_preco(array $params = Array()): array|false` — [line 722](../../../../../gestor/bibliotecas/stripe.php#L722)
- `stripe_consultar_preco($params = Array())` — [line 753](../../../../../gestor/bibliotecas/stripe.php#L753)
- `stripe_listar_precos(array $params = Array()): array|false` — [line 771](../../../../../gestor/bibliotecas/stripe.php#L771)
- `stripe_arquivar_preco($params = Array())` — [line 788](../../../../../gestor/bibliotecas/stripe.php#L788)
- `stripe_validar_webhook(array $params = Array()): array|false` — [line 808](../../../../../gestor/bibliotecas/stripe.php#L808)
- `stripe_testar_conexao()` — [line 848](../../../../../gestor/bibliotecas/stripe.php#L848)
- `stripe_traduzir_status($status)` — [line 853](../../../../../gestor/bibliotecas/stripe.php#L853)
- `stripe_info()` — [line 871](../../../../../gestor/bibliotecas/stripe.php#L871)

<!-- c2f:extract:end -->
