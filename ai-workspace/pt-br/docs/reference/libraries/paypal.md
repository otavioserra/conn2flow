---
title: "Biblioteca paypal.php"
label: "PayPal"
description: "Cliente da API REST do PayPal: credenciais pelo .env ou pelo gateway, token OAuth com cache, pedidos, reembolsos, catálogo, planos, assinaturas, faturas, payouts, disputas e webhook."
section: reference
order: 320
sources:
  - gestor/bibliotecas/paypal.php
  - gestor/config.php
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: b1acb1b6
---

# Biblioteca `paypal.php`

Chamadas diretas à API REST do PayPal (`api-m.paypal.com` em produção, `api-m.sandbox.paypal.com` em sandbox), sem SDK. A versão registrada é `3.1.0` (`paypal_info()`).

## Credenciais: dois modos

| Modo | De onde vêm as credenciais | Onde o token OAuth é guardado |
|---|---|---|
| **padrão** | `.env`: `PAYPAL_MODE` (`sandbox`/`live`), `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_WEBHOOK_ID` | Variáveis do módulo `admin-environment` (`paypal-access-token`…) |
| **gateway** | Registro `paypal` da tabela `gateways_pagamentos` | Colunas `access_token`, `token_expires_at` e `token_data` do próprio gateway |

`PAYPAL_DEFAULT` (`padrao` ou `gateway`) escolhe o modo de `paypal_auto_configurar()`. Para um gateway específico: `paypal_gateways_pagamentos_configurar(['id' => 7])`. O `.env` só preenche as credenciais do ambiente de `PAYPAL_MODE`.

`paypal_autenticar()` reaproveita o token em `$_GESTOR['paypal-token']` enquanto faltarem mais de 5 minutos para vencer, e pede outro (`client_credentials`) quando não. `['force_refresh' => true]` força. No modo gateway, uma falha de autenticação marca o gateway como desconectado (`paypal_gateway_atualizar_conexao()`), e cada transação pode somar às estatísticas dele (`paypal_gateway_registrar_transacao()`).

> [!NOTE]
> O token e as credenciais ficam em texto claro no banco (variáveis ou `gateways_pagamentos`).

## Requisição

`paypal_requisicao(['endpoint', 'method', 'data', 'headers', 'auth_basic', 'access_token'])` devolve `['http_code', 'data', 'raw']` ou `false`. Como no [Stripe](stripe.md), erro da API não é `false`: confira `http_code`. Erros vão para `gestor/logs/paypal-<data>.log`.

## Operações

| Área | Funções |
|---|---|
| Pedidos (Orders v2) | `paypal_criar_pedido()`, `paypal_capturar_pedido()`, `paypal_consultar_pedido()`, `paypal_processar_pagamento_transparente()`, `paypal_gerar_client_token()` e `paypal_validar_payment_source()` (cartão direto na página) |
| Reembolsos | `paypal_reembolsar()`, `paypal_consultar_reembolso()` |
| Catálogo e planos | `paypal_criar_produto()`, `paypal_listar_produtos()`, `paypal_consultar_produto()`, `paypal_atualizar_produto()`; `paypal_criar_plano()`, `paypal_listar_planos()`, `paypal_consultar_plano()`, `paypal_ativar_plano()`, `paypal_desativar_plano()`, `paypal_atualizar_precos_plano()` |
| Assinaturas | `paypal_criar_assinatura()`, `paypal_consultar_assinatura()`, `paypal_suspender_assinatura()`, `paypal_ativar_assinatura()`, `paypal_cancelar_assinatura()`, `paypal_capturar_assinatura()`, `paypal_listar_transacoes_assinatura()` |
| Faturas (Invoicing v2) | `paypal_criar_fatura()`, `paypal_listar_faturas()`, `paypal_buscar_faturas()`, `paypal_consultar_fatura()`, `paypal_enviar_fatura()`, `paypal_lembrete_fatura()`, `paypal_cancelar_fatura()`, `paypal_deletar_fatura()`, `paypal_registrar_pagamento_fatura()`, `paypal_registrar_reembolso_fatura()`, `paypal_gerar_qrcode_fatura()`, `paypal_gerar_numero_fatura()` |
| Payouts | `paypal_criar_payout()`, `paypal_consultar_payout()`, `paypal_consultar_item_payout()`, `paypal_cancelar_item_payout()` |
| Links de pagamento | `paypal_gerar_link_pagamento()`, `paypal_verificar_link_pagamento()` |
| Disputas | `paypal_listar_disputas()`, `paypal_consultar_disputa()`, `paypal_aceitar_disputa()`, `paypal_contestar_disputa()`, `paypal_mensagem_disputa()`, `paypal_escalar_disputa()` |
| Relatórios | `paypal_listar_transacoes()`, `paypal_consultar_saldo()` |
| Conferências | `paypal_pedido_pago()`, `paypal_assinatura_ativa()`, `paypal_fatura_paga()` |

## Webhook

`paypal_validar_webhook(['headers' => getallheaders(), 'body' => $corpoCru])` pede ao próprio PayPal a verificação da assinatura (`verify-webhook-signature`, com o `PAYPAL_WEBHOOK_ID`) e só aceita `verification_status = SUCCESS`. `paypal_processar_webhook(['body' => …, 'callback' => …])` decodifica o evento e chama o `callback`; ele **não valida**, então chame antes o `paypal_validar_webhook()`.

O endpoint `/_gateways/paypal/webhook` (controlador `plataforma-gateways`) valida e dispara o hook `paypal.webhook`; há também as rotas de retorno e cancelamento do checkout.

> [!WARNING]
> Na forma modular, `/_gateways/<modulo>/paypal/webhook`, o controlador repassa o evento ao hook do módulo com `needs_validation => true`, **sem validar**. O módulo precisa validar antes de agir.

## Utilitários e armadilhas

- `paypal_formatar_valor()`, `paypal_traduzir_status()` (para português), `paypal_gerar_id()`, `paypal_validar_email()` e `paypal_log_registro()`.
- `paypal_formatar_data()` gera `AAAA-MM-DDTHH:MM:SSZ` com `date()`, que usa o fuso do servidor: o `Z` (UTC) só é verdadeiro se o PHP estiver em UTC. O Gestor não define fuso horário.
- `paypal_calcular_taxa($valor, 'nacional'|'internacional')` usa taxas fixas no código (4,99% + 0,60 e 5,99% + 0,60), que não acompanham a tabela real do PayPal. Serve só como estimativa.
- O `admin-environment` usa a biblioteca para testar as credenciais.

**Auxiliares internos:** `paypal_gateway_persistir_token()`, `paypal_is_modo_gateway()`, `paypal_obter_gateway_id()`, `paypal_obter_gateway_dados()`, `paypal_padrao_persistir_token()`, `paypal_padrao_restaurar_token()`, `paypal_obter_url_api()`, `paypal_obter_credenciais()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/paypal.php` por `c2f docs:extract` — 78 funções. Não edite dentro deste bloco.

- `paypal_log_registro($logData = []): void` — [linha 39](../../../../../gestor/bibliotecas/paypal.php#L39)
- `paypal_gateways_pagamentos_configurar(array|false $params = false): bool` — [linha 64](../../../../../gestor/bibliotecas/paypal.php#L64)
- `paypal_gateway_persistir_token(array $token_data): bool` — [linha 169](../../../../../gestor/bibliotecas/paypal.php#L169)
- `paypal_gateway_registrar_transacao(array|false $params = false): bool` — [linha 207](../../../../../gestor/bibliotecas/paypal.php#L207)
- `paypal_gateway_atualizar_conexao(bool $sucesso, string $mensagem = ''): bool` — [linha 242](../../../../../gestor/bibliotecas/paypal.php#L242)
- `paypal_is_modo_gateway(): bool` — [linha 270](../../../../../gestor/bibliotecas/paypal.php#L270)
- `paypal_obter_gateway_id(): string|null` — [linha 282](../../../../../gestor/bibliotecas/paypal.php#L282)
- `paypal_obter_gateway_dados(): array|null` — [linha 294](../../../../../gestor/bibliotecas/paypal.php#L294)
- `paypal_padrao_persistir_token(array $token_data): bool` — [linha 313](../../../../../gestor/bibliotecas/paypal.php#L313)
- `paypal_padrao_restaurar_token(): bool` — [linha 355](../../../../../gestor/bibliotecas/paypal.php#L355)
- `paypal_auto_configurar(): void` — [linha 403](../../../../../gestor/bibliotecas/paypal.php#L403)
- `paypal_obter_url_api(): string` — [linha 431](../../../../../gestor/bibliotecas/paypal.php#L431)
- `paypal_obter_credenciais(): array|false` — [linha 450](../../../../../gestor/bibliotecas/paypal.php#L450)
- `paypal_requisicao(array|false $params = false): array|false` — [linha 484](../../../../../gestor/bibliotecas/paypal.php#L484)
- `paypal_autenticar(array|false $params = false): array|false` — [linha 600](../../../../../gestor/bibliotecas/paypal.php#L600)
- `paypal_gerar_client_token(array|false $params = false): string|false` — [linha 673](../../../../../gestor/bibliotecas/paypal.php#L673)
- `paypal_validar_payment_source(mixed $payment_source): bool` — [linha 731](../../../../../gestor/bibliotecas/paypal.php#L731)
- `paypal_criar_pedido(array|false $params = false): array|false` — [linha 770](../../../../../gestor/bibliotecas/paypal.php#L770)
- `paypal_capturar_pedido(array|false $params = false): array|false` — [linha 944](../../../../../gestor/bibliotecas/paypal.php#L944)
- `paypal_processar_pagamento_transparente(array|false $params = false): array|false` — [linha 1023](../../../../../gestor/bibliotecas/paypal.php#L1023)
- `paypal_consultar_pedido(array|false $params = false): array|false` — [linha 1125](../../../../../gestor/bibliotecas/paypal.php#L1125)
- `paypal_reembolsar(array|false $params = false): array|false` — [linha 1181](../../../../../gestor/bibliotecas/paypal.php#L1181)
- `paypal_consultar_reembolso(array|false $params = false): array|false` — [linha 1258](../../../../../gestor/bibliotecas/paypal.php#L1258)
- `paypal_validar_webhook(array|false $params = false): bool` — [linha 1311](../../../../../gestor/bibliotecas/paypal.php#L1311)
- `paypal_processar_webhook(array|false $params = false): array|false` — [linha 1408](../../../../../gestor/bibliotecas/paypal.php#L1408)
- `paypal_criar_produto(array|false $params = false): array|false` — [linha 1482](../../../../../gestor/bibliotecas/paypal.php#L1482)
- `paypal_listar_produtos(array|false $params = false): array|false` — [linha 1559](../../../../../gestor/bibliotecas/paypal.php#L1559)
- `paypal_consultar_produto(array|false $params = false): array|false` — [linha 1612](../../../../../gestor/bibliotecas/paypal.php#L1612)
- `paypal_atualizar_produto(array|false $params = false): bool` — [linha 1660](../../../../../gestor/bibliotecas/paypal.php#L1660)
- `paypal_criar_plano(array|false $params = false): array|false` — [linha 1759](../../../../../gestor/bibliotecas/paypal.php#L1759)
- `paypal_listar_planos(array|false $params = false): array|false` — [linha 1881](../../../../../gestor/bibliotecas/paypal.php#L1881)
- `paypal_consultar_plano(array|false $params = false): array|false` — [linha 1937](../../../../../gestor/bibliotecas/paypal.php#L1937)
- `paypal_ativar_plano(array|false $params = false): bool` — [linha 1981](../../../../../gestor/bibliotecas/paypal.php#L1981)
- `paypal_desativar_plano(array|false $params = false): bool` — [linha 2025](../../../../../gestor/bibliotecas/paypal.php#L2025)
- `paypal_atualizar_precos_plano(array|false $params = false): bool` — [linha 2075](../../../../../gestor/bibliotecas/paypal.php#L2075)
- `paypal_criar_assinatura(array|false $params = false): array|false` — [linha 2150](../../../../../gestor/bibliotecas/paypal.php#L2150)
- `paypal_consultar_assinatura(array|false $params = false): array|false` — [linha 2280](../../../../../gestor/bibliotecas/paypal.php#L2280)
- `paypal_suspender_assinatura(array|false $params = false): bool` — [linha 2325](../../../../../gestor/bibliotecas/paypal.php#L2325)
- `paypal_cancelar_assinatura(array|false $params = false): bool` — [linha 2371](../../../../../gestor/bibliotecas/paypal.php#L2371)
- `paypal_ativar_assinatura(array|false $params = false): bool` — [linha 2417](../../../../../gestor/bibliotecas/paypal.php#L2417)
- `paypal_capturar_assinatura(array|false $params = false): array|false` — [linha 2465](../../../../../gestor/bibliotecas/paypal.php#L2465)
- `paypal_listar_transacoes_assinatura(array|false $params = false): array|false` — [linha 2526](../../../../../gestor/bibliotecas/paypal.php#L2526)
- `paypal_criar_fatura(array|false $params = false): array|false` — [linha 2603](../../../../../gestor/bibliotecas/paypal.php#L2603)
- `paypal_listar_faturas(array|false $params = false): array|false` — [linha 2792](../../../../../gestor/bibliotecas/paypal.php#L2792)
- `paypal_consultar_fatura(array|false $params = false): array|false` — [linha 2845](../../../../../gestor/bibliotecas/paypal.php#L2845)
- `paypal_enviar_fatura(array|false $params = false): bool` — [linha 2892](../../../../../gestor/bibliotecas/paypal.php#L2892)
- `paypal_cancelar_fatura(array|false $params = false): bool` — [linha 2950](../../../../../gestor/bibliotecas/paypal.php#L2950)
- `paypal_lembrete_fatura(array|false $params = false): bool` — [linha 3009](../../../../../gestor/bibliotecas/paypal.php#L3009)
- `paypal_registrar_pagamento_fatura(array|false $params = false): array|false` — [linha 3068](../../../../../gestor/bibliotecas/paypal.php#L3068)
- `paypal_registrar_reembolso_fatura(array|false $params = false): array|false` — [linha 3140](../../../../../gestor/bibliotecas/paypal.php#L3140)
- `paypal_gerar_qrcode_fatura(array|false $params = false): string|false` — [linha 3207](../../../../../gestor/bibliotecas/paypal.php#L3207)
- `paypal_buscar_faturas(array|false $params = false): array|false` — [linha 3265](../../../../../gestor/bibliotecas/paypal.php#L3265)
- `paypal_deletar_fatura(array|false $params = false): bool` — [linha 3334](../../../../../gestor/bibliotecas/paypal.php#L3334)
- `paypal_gerar_numero_fatura(): string|false` — [linha 3375](../../../../../gestor/bibliotecas/paypal.php#L3375)
- `paypal_criar_payout(array|false $params = false): array|false` — [linha 3430](../../../../../gestor/bibliotecas/paypal.php#L3430)
- `paypal_consultar_payout(array|false $params = false): array|false` — [linha 3538](../../../../../gestor/bibliotecas/paypal.php#L3538)
- `paypal_consultar_item_payout(array|false $params = false): array|false` — [linha 3596](../../../../../gestor/bibliotecas/paypal.php#L3596)
- `paypal_cancelar_item_payout(array|false $params = false): array|false` — [linha 3642](../../../../../gestor/bibliotecas/paypal.php#L3642)
- `paypal_gerar_link_pagamento(array|false $params = false): array|false` — [linha 3699](../../../../../gestor/bibliotecas/paypal.php#L3699)
- `paypal_verificar_link_pagamento(array|false $params = false): array|false` — [linha 3724](../../../../../gestor/bibliotecas/paypal.php#L3724)
- `paypal_listar_disputas(array|false $params = false): array|false` — [linha 3760](../../../../../gestor/bibliotecas/paypal.php#L3760)
- `paypal_consultar_disputa(array|false $params = false): array|false` — [linha 3823](../../../../../gestor/bibliotecas/paypal.php#L3823)
- `paypal_aceitar_disputa(array|false $params = false): array|false` — [linha 3870](../../../../../gestor/bibliotecas/paypal.php#L3870)
- `paypal_contestar_disputa(array|false $params = false): array|false` — [linha 3941](../../../../../gestor/bibliotecas/paypal.php#L3941)
- `paypal_mensagem_disputa(array|false $params = false): array|false` — [linha 4015](../../../../../gestor/bibliotecas/paypal.php#L4015)
- `paypal_escalar_disputa(array|false $params = false): array|false` — [linha 4066](../../../../../gestor/bibliotecas/paypal.php#L4066)
- `paypal_listar_transacoes(array|false $params = false): array|false` — [linha 4126](../../../../../gestor/bibliotecas/paypal.php#L4126)
- `paypal_consultar_saldo(array|false $params = false): array|false` — [linha 4192](../../../../../gestor/bibliotecas/paypal.php#L4192)
- `paypal_formatar_valor(float $valor, string $moeda = 'BRL'): string` — [linha 4242](../../../../../gestor/bibliotecas/paypal.php#L4242)
- `paypal_traduzir_status(string $status, string $tipo = 'order'): string` — [linha 4269](../../../../../gestor/bibliotecas/paypal.php#L4269)
- `paypal_assinatura_ativa(string $subscription_id): bool` — [linha 4344](../../../../../gestor/bibliotecas/paypal.php#L4344)
- `paypal_pedido_pago(string $order_id): bool` — [linha 4363](../../../../../gestor/bibliotecas/paypal.php#L4363)
- `paypal_fatura_paga(string $invoice_id): bool` — [linha 4382](../../../../../gestor/bibliotecas/paypal.php#L4382)
- `paypal_calcular_taxa(float $valor, string $tipo = 'nacional'): array` — [linha 4405](../../../../../gestor/bibliotecas/paypal.php#L4405)
- `paypal_gerar_id(string $prefixo = 'TXN'): string` — [linha 4440](../../../../../gestor/bibliotecas/paypal.php#L4440)
- `paypal_validar_email(string $email): bool` — [linha 4451](../../../../../gestor/bibliotecas/paypal.php#L4451)
- `paypal_formatar_data(string|int $data, bool $incluir_hora = true): string` — [linha 4463](../../../../../gestor/bibliotecas/paypal.php#L4463)
- `paypal_info(): array` — [linha 4482](../../../../../gestor/bibliotecas/paypal.php#L4482)

<!-- c2f:extract:end -->
