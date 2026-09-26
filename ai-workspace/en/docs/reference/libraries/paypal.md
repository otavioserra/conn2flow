---
title: "paypal.php library"
label: "PayPal"
description: "PayPal REST API client: credentials from the .env or from the gateway, cached OAuth token, orders, refunds, catalog, plans, subscriptions, invoices, payouts, disputes and webhook."
section: reference
order: 320
sources:
  - gestor/bibliotecas/paypal.php
  - gestor/config.php
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: b1acb1b6
---

# `paypal.php` library

Direct calls to the PayPal REST API (`api-m.paypal.com` in production, `api-m.sandbox.paypal.com` in sandbox), without an SDK. The registered version is `3.1.0` (`paypal_info()`).

## Credentials: two modes

| Mode | Where credentials come from | Where the OAuth token is kept |
|---|---|---|
| **default** | `.env`: `PAYPAL_MODE` (`sandbox`/`live`), `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_WEBHOOK_ID` | `admin-environment` module variables (`paypal-access-token`…) |
| **gateway** | A `paypal` record of the `gateways_pagamentos` table | The gateway's own `access_token`, `token_expires_at` and `token_data` columns |

`PAYPAL_DEFAULT` (`padrao` or `gateway`) chooses the mode of `paypal_auto_configurar()`. For a specific gateway: `paypal_gateways_pagamentos_configurar(['id' => 7])`. The `.env` only fills the credentials of the `PAYPAL_MODE` environment.

`paypal_autenticar()` reuses the token in `$_GESTOR['paypal-token']` while more than 5 minutes remain before it expires, and requests another one (`client_credentials`) otherwise. `['force_refresh' => true]` forces it. In gateway mode, an authentication failure marks the gateway as disconnected (`paypal_gateway_atualizar_conexao()`), and each transaction can add to its statistics (`paypal_gateway_registrar_transacao()`).

> [!NOTE]
> The token and the credentials are stored in plain text in the database (variables or `gateways_pagamentos`).

## Request

`paypal_requisicao(['endpoint', 'method', 'data', 'headers', 'auth_basic', 'access_token'])` returns `['http_code', 'data', 'raw']` or `false`. As with [Stripe](stripe.md), an API error is not `false`: check `http_code`. Errors go to `gestor/logs/paypal-<date>.log`.

## Operations

| Area | Functions |
|---|---|
| Orders (Orders v2) | `paypal_criar_pedido()`, `paypal_capturar_pedido()`, `paypal_consultar_pedido()`, `paypal_processar_pagamento_transparente()`, `paypal_gerar_client_token()` and `paypal_validar_payment_source()` (card directly on the page) |
| Refunds | `paypal_reembolsar()`, `paypal_consultar_reembolso()` |
| Catalog and plans | `paypal_criar_produto()`, `paypal_listar_produtos()`, `paypal_consultar_produto()`, `paypal_atualizar_produto()`; `paypal_criar_plano()`, `paypal_listar_planos()`, `paypal_consultar_plano()`, `paypal_ativar_plano()`, `paypal_desativar_plano()`, `paypal_atualizar_precos_plano()` |
| Subscriptions | `paypal_criar_assinatura()`, `paypal_consultar_assinatura()`, `paypal_suspender_assinatura()`, `paypal_ativar_assinatura()`, `paypal_cancelar_assinatura()`, `paypal_capturar_assinatura()`, `paypal_listar_transacoes_assinatura()` |
| Invoices (Invoicing v2) | `paypal_criar_fatura()`, `paypal_listar_faturas()`, `paypal_buscar_faturas()`, `paypal_consultar_fatura()`, `paypal_enviar_fatura()`, `paypal_lembrete_fatura()`, `paypal_cancelar_fatura()`, `paypal_deletar_fatura()`, `paypal_registrar_pagamento_fatura()`, `paypal_registrar_reembolso_fatura()`, `paypal_gerar_qrcode_fatura()`, `paypal_gerar_numero_fatura()` |
| Payouts | `paypal_criar_payout()`, `paypal_consultar_payout()`, `paypal_consultar_item_payout()`, `paypal_cancelar_item_payout()` |
| Payment links | `paypal_gerar_link_pagamento()`, `paypal_verificar_link_pagamento()` |
| Disputes | `paypal_listar_disputas()`, `paypal_consultar_disputa()`, `paypal_aceitar_disputa()`, `paypal_contestar_disputa()`, `paypal_mensagem_disputa()`, `paypal_escalar_disputa()` |
| Reports | `paypal_listar_transacoes()`, `paypal_consultar_saldo()` |
| Checks | `paypal_pedido_pago()`, `paypal_assinatura_ativa()`, `paypal_fatura_paga()` |

## Webhook

`paypal_validar_webhook(['headers' => getallheaders(), 'body' => $rawBody])` asks PayPal itself to verify the signature (`verify-webhook-signature`, with `PAYPAL_WEBHOOK_ID`) and only accepts `verification_status = SUCCESS`. `paypal_processar_webhook(['body' => …, 'callback' => …])` decodes the event and calls the `callback`; it **does not validate**, so call `paypal_validar_webhook()` first.

The `/_gateways/paypal/webhook` endpoint (the `plataforma-gateways` controller) validates and fires the `paypal.webhook` hook; there are also the checkout return and cancel routes.

> [!WARNING]
> In the modular form, `/_gateways/<module>/paypal/webhook`, the controller hands the event to the module's hook with `needs_validation => true`, **without validating**. The module must validate before acting.

## Utilities and pitfalls

- `paypal_formatar_valor()`, `paypal_traduzir_status()` (to Portuguese), `paypal_gerar_id()`, `paypal_validar_email()` and `paypal_log_registro()`.
- `paypal_formatar_data()` produces `YYYY-MM-DDTHH:MM:SSZ` with `date()`, which uses the server time zone: the `Z` (UTC) is only true if PHP runs in UTC. The Gestor does not set a time zone.
- `paypal_calcular_taxa($value, 'nacional'|'internacional')` uses hard-coded fees (4.99% + 0.60 and 5.99% + 0.60) that do not follow PayPal's actual pricing. Use it only as an estimate.
- `admin-environment` uses the library to test the credentials.

**Internal helpers:** `paypal_gateway_persistir_token()`, `paypal_is_modo_gateway()`, `paypal_obter_gateway_id()`, `paypal_obter_gateway_dados()`, `paypal_padrao_persistir_token()`, `paypal_padrao_restaurar_token()`, `paypal_obter_url_api()`, `paypal_obter_credenciais()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/paypal.php` by `c2f docs:extract` — 78 functions. Do not edit inside this block.

- `paypal_log_registro($logData = []): void` — [line 39](../../../../../gestor/bibliotecas/paypal.php#L39)
- `paypal_gateways_pagamentos_configurar(array|false $params = false): bool` — [line 64](../../../../../gestor/bibliotecas/paypal.php#L64)
- `paypal_gateway_persistir_token(array $token_data): bool` — [line 169](../../../../../gestor/bibliotecas/paypal.php#L169)
- `paypal_gateway_registrar_transacao(array|false $params = false): bool` — [line 207](../../../../../gestor/bibliotecas/paypal.php#L207)
- `paypal_gateway_atualizar_conexao(bool $sucesso, string $mensagem = ''): bool` — [line 242](../../../../../gestor/bibliotecas/paypal.php#L242)
- `paypal_is_modo_gateway(): bool` — [line 270](../../../../../gestor/bibliotecas/paypal.php#L270)
- `paypal_obter_gateway_id(): string|null` — [line 282](../../../../../gestor/bibliotecas/paypal.php#L282)
- `paypal_obter_gateway_dados(): array|null` — [line 294](../../../../../gestor/bibliotecas/paypal.php#L294)
- `paypal_padrao_persistir_token(array $token_data): bool` — [line 313](../../../../../gestor/bibliotecas/paypal.php#L313)
- `paypal_padrao_restaurar_token(): bool` — [line 355](../../../../../gestor/bibliotecas/paypal.php#L355)
- `paypal_auto_configurar(): void` — [line 403](../../../../../gestor/bibliotecas/paypal.php#L403)
- `paypal_obter_url_api(): string` — [line 431](../../../../../gestor/bibliotecas/paypal.php#L431)
- `paypal_obter_credenciais(): array|false` — [line 450](../../../../../gestor/bibliotecas/paypal.php#L450)
- `paypal_requisicao(array|false $params = false): array|false` — [line 484](../../../../../gestor/bibliotecas/paypal.php#L484)
- `paypal_autenticar(array|false $params = false): array|false` — [line 600](../../../../../gestor/bibliotecas/paypal.php#L600)
- `paypal_gerar_client_token(array|false $params = false): string|false` — [line 673](../../../../../gestor/bibliotecas/paypal.php#L673)
- `paypal_validar_payment_source(mixed $payment_source): bool` — [line 731](../../../../../gestor/bibliotecas/paypal.php#L731)
- `paypal_criar_pedido(array|false $params = false): array|false` — [line 770](../../../../../gestor/bibliotecas/paypal.php#L770)
- `paypal_capturar_pedido(array|false $params = false): array|false` — [line 944](../../../../../gestor/bibliotecas/paypal.php#L944)
- `paypal_processar_pagamento_transparente(array|false $params = false): array|false` — [line 1023](../../../../../gestor/bibliotecas/paypal.php#L1023)
- `paypal_consultar_pedido(array|false $params = false): array|false` — [line 1125](../../../../../gestor/bibliotecas/paypal.php#L1125)
- `paypal_reembolsar(array|false $params = false): array|false` — [line 1181](../../../../../gestor/bibliotecas/paypal.php#L1181)
- `paypal_consultar_reembolso(array|false $params = false): array|false` — [line 1258](../../../../../gestor/bibliotecas/paypal.php#L1258)
- `paypal_validar_webhook(array|false $params = false): bool` — [line 1311](../../../../../gestor/bibliotecas/paypal.php#L1311)
- `paypal_processar_webhook(array|false $params = false): array|false` — [line 1408](../../../../../gestor/bibliotecas/paypal.php#L1408)
- `paypal_criar_produto(array|false $params = false): array|false` — [line 1482](../../../../../gestor/bibliotecas/paypal.php#L1482)
- `paypal_listar_produtos(array|false $params = false): array|false` — [line 1559](../../../../../gestor/bibliotecas/paypal.php#L1559)
- `paypal_consultar_produto(array|false $params = false): array|false` — [line 1612](../../../../../gestor/bibliotecas/paypal.php#L1612)
- `paypal_atualizar_produto(array|false $params = false): bool` — [line 1660](../../../../../gestor/bibliotecas/paypal.php#L1660)
- `paypal_criar_plano(array|false $params = false): array|false` — [line 1759](../../../../../gestor/bibliotecas/paypal.php#L1759)
- `paypal_listar_planos(array|false $params = false): array|false` — [line 1881](../../../../../gestor/bibliotecas/paypal.php#L1881)
- `paypal_consultar_plano(array|false $params = false): array|false` — [line 1937](../../../../../gestor/bibliotecas/paypal.php#L1937)
- `paypal_ativar_plano(array|false $params = false): bool` — [line 1981](../../../../../gestor/bibliotecas/paypal.php#L1981)
- `paypal_desativar_plano(array|false $params = false): bool` — [line 2025](../../../../../gestor/bibliotecas/paypal.php#L2025)
- `paypal_atualizar_precos_plano(array|false $params = false): bool` — [line 2075](../../../../../gestor/bibliotecas/paypal.php#L2075)
- `paypal_criar_assinatura(array|false $params = false): array|false` — [line 2150](../../../../../gestor/bibliotecas/paypal.php#L2150)
- `paypal_consultar_assinatura(array|false $params = false): array|false` — [line 2280](../../../../../gestor/bibliotecas/paypal.php#L2280)
- `paypal_suspender_assinatura(array|false $params = false): bool` — [line 2325](../../../../../gestor/bibliotecas/paypal.php#L2325)
- `paypal_cancelar_assinatura(array|false $params = false): bool` — [line 2371](../../../../../gestor/bibliotecas/paypal.php#L2371)
- `paypal_ativar_assinatura(array|false $params = false): bool` — [line 2417](../../../../../gestor/bibliotecas/paypal.php#L2417)
- `paypal_capturar_assinatura(array|false $params = false): array|false` — [line 2465](../../../../../gestor/bibliotecas/paypal.php#L2465)
- `paypal_listar_transacoes_assinatura(array|false $params = false): array|false` — [line 2526](../../../../../gestor/bibliotecas/paypal.php#L2526)
- `paypal_criar_fatura(array|false $params = false): array|false` — [line 2603](../../../../../gestor/bibliotecas/paypal.php#L2603)
- `paypal_listar_faturas(array|false $params = false): array|false` — [line 2792](../../../../../gestor/bibliotecas/paypal.php#L2792)
- `paypal_consultar_fatura(array|false $params = false): array|false` — [line 2845](../../../../../gestor/bibliotecas/paypal.php#L2845)
- `paypal_enviar_fatura(array|false $params = false): bool` — [line 2892](../../../../../gestor/bibliotecas/paypal.php#L2892)
- `paypal_cancelar_fatura(array|false $params = false): bool` — [line 2950](../../../../../gestor/bibliotecas/paypal.php#L2950)
- `paypal_lembrete_fatura(array|false $params = false): bool` — [line 3009](../../../../../gestor/bibliotecas/paypal.php#L3009)
- `paypal_registrar_pagamento_fatura(array|false $params = false): array|false` — [line 3068](../../../../../gestor/bibliotecas/paypal.php#L3068)
- `paypal_registrar_reembolso_fatura(array|false $params = false): array|false` — [line 3140](../../../../../gestor/bibliotecas/paypal.php#L3140)
- `paypal_gerar_qrcode_fatura(array|false $params = false): string|false` — [line 3207](../../../../../gestor/bibliotecas/paypal.php#L3207)
- `paypal_buscar_faturas(array|false $params = false): array|false` — [line 3265](../../../../../gestor/bibliotecas/paypal.php#L3265)
- `paypal_deletar_fatura(array|false $params = false): bool` — [line 3334](../../../../../gestor/bibliotecas/paypal.php#L3334)
- `paypal_gerar_numero_fatura(): string|false` — [line 3375](../../../../../gestor/bibliotecas/paypal.php#L3375)
- `paypal_criar_payout(array|false $params = false): array|false` — [line 3430](../../../../../gestor/bibliotecas/paypal.php#L3430)
- `paypal_consultar_payout(array|false $params = false): array|false` — [line 3538](../../../../../gestor/bibliotecas/paypal.php#L3538)
- `paypal_consultar_item_payout(array|false $params = false): array|false` — [line 3596](../../../../../gestor/bibliotecas/paypal.php#L3596)
- `paypal_cancelar_item_payout(array|false $params = false): array|false` — [line 3642](../../../../../gestor/bibliotecas/paypal.php#L3642)
- `paypal_gerar_link_pagamento(array|false $params = false): array|false` — [line 3699](../../../../../gestor/bibliotecas/paypal.php#L3699)
- `paypal_verificar_link_pagamento(array|false $params = false): array|false` — [line 3724](../../../../../gestor/bibliotecas/paypal.php#L3724)
- `paypal_listar_disputas(array|false $params = false): array|false` — [line 3760](../../../../../gestor/bibliotecas/paypal.php#L3760)
- `paypal_consultar_disputa(array|false $params = false): array|false` — [line 3823](../../../../../gestor/bibliotecas/paypal.php#L3823)
- `paypal_aceitar_disputa(array|false $params = false): array|false` — [line 3870](../../../../../gestor/bibliotecas/paypal.php#L3870)
- `paypal_contestar_disputa(array|false $params = false): array|false` — [line 3941](../../../../../gestor/bibliotecas/paypal.php#L3941)
- `paypal_mensagem_disputa(array|false $params = false): array|false` — [line 4015](../../../../../gestor/bibliotecas/paypal.php#L4015)
- `paypal_escalar_disputa(array|false $params = false): array|false` — [line 4066](../../../../../gestor/bibliotecas/paypal.php#L4066)
- `paypal_listar_transacoes(array|false $params = false): array|false` — [line 4126](../../../../../gestor/bibliotecas/paypal.php#L4126)
- `paypal_consultar_saldo(array|false $params = false): array|false` — [line 4192](../../../../../gestor/bibliotecas/paypal.php#L4192)
- `paypal_formatar_valor(float $valor, string $moeda = 'BRL'): string` — [line 4242](../../../../../gestor/bibliotecas/paypal.php#L4242)
- `paypal_traduzir_status(string $status, string $tipo = 'order'): string` — [line 4269](../../../../../gestor/bibliotecas/paypal.php#L4269)
- `paypal_assinatura_ativa(string $subscription_id): bool` — [line 4344](../../../../../gestor/bibliotecas/paypal.php#L4344)
- `paypal_pedido_pago(string $order_id): bool` — [line 4363](../../../../../gestor/bibliotecas/paypal.php#L4363)
- `paypal_fatura_paga(string $invoice_id): bool` — [line 4382](../../../../../gestor/bibliotecas/paypal.php#L4382)
- `paypal_calcular_taxa(float $valor, string $tipo = 'nacional'): array` — [line 4405](../../../../../gestor/bibliotecas/paypal.php#L4405)
- `paypal_gerar_id(string $prefixo = 'TXN'): string` — [line 4440](../../../../../gestor/bibliotecas/paypal.php#L4440)
- `paypal_validar_email(string $email): bool` — [line 4451](../../../../../gestor/bibliotecas/paypal.php#L4451)
- `paypal_formatar_data(string|int $data, bool $incluir_hora = true): string` — [line 4463](../../../../../gestor/bibliotecas/paypal.php#L4463)
- `paypal_info(): array` — [line 4482](../../../../../gestor/bibliotecas/paypal.php#L4482)

<!-- c2f:extract:end -->
