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
  Registra um log de evento relacionado ao PayPal.
- `paypal_gateways_pagamentos_configurar(array|false $params = false): bool` — [line 64](../../../../../gestor/bibliotecas/paypal.php#L64)
  Configura a biblioteca PayPal para usar um gateway de pagamentos do banco.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['id']`: ID específico do gateway (opcional)
  - `$params['tipo']`: Tipo do gateway, ex: 'paypal' (opcional, padrão: 'paypal')
  Returns: True se configurado com sucesso, false caso contrário
- `paypal_gateway_persistir_token(array $token_data): bool` — [line 169](../../../../../gestor/bibliotecas/paypal.php#L169)
  Persiste o token OAuth no banco de dados do gateway ativo.
  Parameters:
  - `$token_data`: Dados do token (access_token, token_type, expires_at, etc)
  Returns: True se persistido, false se não está em modo gateway
- `paypal_gateway_registrar_transacao(array|false $params = false): bool` — [line 207](../../../../../gestor/bibliotecas/paypal.php#L207)
  Registra uma transação nas estatísticas do gateway ativo.
  Parameters:
  - `$params`: Parâmetros
  - `$params['valor']`: Valor da transação (opcional, padrão: 0)
  - `$params['moeda']`: Moeda da transação (opcional)
  Returns: True se registrado, false se não está em modo gateway
- `paypal_gateway_atualizar_conexao(bool $sucesso, string $mensagem = ''): bool` — [line 242](../../../../../gestor/bibliotecas/paypal.php#L242)
  Atualiza o status de conexão do gateway ativo.
  Parameters:
  - `$sucesso`: Se a conexão foi bem-sucedida
  - `$mensagem`: Mensagem descritiva do resultado
  Returns: True se atualizado, false se não está em modo gateway
- `paypal_is_modo_gateway(): bool` — [line 270](../../../../../gestor/bibliotecas/paypal.php#L270)
  Verifica se a biblioteca está operando em modo gateway (banco de dados).
  Returns: True se está em modo gateway
- `paypal_obter_gateway_id(): string|null` — [line 282](../../../../../gestor/bibliotecas/paypal.php#L282)
  Obtém o ID do gateway ativo.
  Returns: ID alfanumérico do gateway ou null se não está em modo gateway
- `paypal_obter_gateway_dados(): array|null` — [line 294](../../../../../gestor/bibliotecas/paypal.php#L294)
  Obtém os dados completos do gateway ativo.
  Returns: Dados do gateway ou null se não está em modo gateway
- `paypal_padrao_persistir_token(array $token_data): bool` — [line 313](../../../../../gestor/bibliotecas/paypal.php#L313)
  Persiste o token OAuth nas variáveis do sistema (modo padrão).
  Parameters:
  - `$token_data`: Dados do token (access_token, token_type, expires_at, etc)
  Returns: True se persistido com sucesso
- `paypal_padrao_restaurar_token(): bool` — [line 355](../../../../../gestor/bibliotecas/paypal.php#L355)
  Restaura o token OAuth persistido nas variáveis do sistema (modo padrão).
  Returns: True se token restaurado e válido, false caso contrário
- `paypal_auto_configurar(): void` — [line 403](../../../../../gestor/bibliotecas/paypal.php#L403)
  Inicializa a biblioteca PayPal automaticamente baseado em $_CONFIG['paypal']['default'].
- `paypal_obter_url_api(): string` — [line 431](../../../../../gestor/bibliotecas/paypal.php#L431)
  Obtém a URL base da API do PayPal baseado no modo (sandbox/live).
  Returns: URL base da API
- `paypal_obter_credenciais(): array|false` — [line 450](../../../../../gestor/bibliotecas/paypal.php#L450)
  Obtém as credenciais do PayPal baseado no modo (sandbox/live).
  Returns: Array com client_id e client_secret ou false se não configurado
- `paypal_requisicao(array|false $params = false): array|false` — [line 484](../../../../../gestor/bibliotecas/paypal.php#L484)
  Realiza requisição HTTP para a API do PayPal.
  Parameters:
  - `$params`: Parâmetros da requisição
  - `$params['endpoint']`: Endpoint da API (ex: '/v1/oauth2/token')
  - `$params['method']`: Método HTTP (GET, POST, PATCH, etc) - padrão: GET
  - `$params['data']`: Dados para enviar no body (opcional)
  - `$params['headers']`: Headers customizados (opcional)
  - `$params['auth_basic']`: Usar Basic Auth com credenciais (opcional)
  - `$params['access_token']`: Token OAuth para autenticação (opcional)
  Returns: Array com resposta ou false em erro
- `paypal_autenticar(array|false $params = false): array|false` — [line 600](../../../../../gestor/bibliotecas/paypal.php#L600)
  Autentica com PayPal e obtém access token OAuth 2.0.
  Parameters:
  - `$params`: Parâmetros da função (opcional)
  - `$params['force_refresh']`: Forçar nova autenticação ignorando cache (opcional)
  Returns: return['expires_at'] Timestamp de expiração
- `paypal_gerar_client_token(array|false $params = false): string|false` — [line 673](../../../../../gestor/bibliotecas/paypal.php#L673)
  Gera o client token usado pelos Card Fields/Hosted Fields no navegador.
  Parameters:
  - `$params`: Parâmetros opcionais
  - `$params['customer_id']`: ID do cliente no vault do PayPal (opcional)
  Returns: Client token ou false em erro
- `paypal_validar_payment_source(mixed $payment_source): bool` — [line 731](../../../../../gestor/bibliotecas/paypal.php#L731)
  Valida a estrutura mínima de uma fonte de pagamento aceita neste fluxo.
  Parameters:
  - `$payment_source`: Fonte de pagamento recebida pelo backend
  Returns: True para payment_source.card ou payment_source.token não vazios
- `paypal_criar_pedido(array|false $params = false): array|false` — [line 770](../../../../../gestor/bibliotecas/paypal.php#L770)
  Cria um pedido (order) no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['valor']`: Valor total do pedido (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['descricao']`: Descrição do pedido (opcional)
  - `$params['itens']`: Array de itens do pedido (opcional)
  - `$params['url_retorno']`: URL de retorno após aprovação (opcional)
  - `$params['url_cancelamento']`: URL de retorno após cancelamento (opcional)
  - `$params['referencia']`: ID de referência customizado (opcional)
  - `$params['intent']`: Intenção CAPTURE ou AUTHORIZE (opcional, padrão: CAPTURE)
  - `$params['payment_source']`: Cartão ou token para pagamento direto (opcional)
  - `$params['request_id']`: Chave de idempotência PayPal-Request-Id (opcional)
  Returns: return['approve_url'] URL para aprovação do pedido
- `paypal_capturar_pedido(array|false $params = false): array|false` — [line 944](../../../../../gestor/bibliotecas/paypal.php#L944)
  Captura um pedido (order) aprovado no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido a ser capturado (obrigatório)
  Returns: return['capture_data'] Dados completos da captura
- `paypal_processar_pagamento_transparente(array|false $params = false): array|false` — [line 1023](../../../../../gestor/bibliotecas/paypal.php#L1023)
  Processa um pagamento transparente recebido do frontend.
  Parameters:
  - `$params`: Parâmetros do fluxo
  - `$params['tipo']`: pedido ou assinatura (opcional, inferido por plan_id)
  - `$params['payment_source']`: payment_source.card ou payment_source.token
  - `$params['order_id']`: Ordem aprovada pelo Card Fields para captura (opcional)
  - `$params['capturar']`: Capturar ordem APPROVED automaticamente (padrão: true)
  Returns: Resultado normalizado ou false para payload inválido/erro da API
- `paypal_consultar_pedido(array|false $params = false): array|false` — [line 1125](../../../../../gestor/bibliotecas/paypal.php#L1125)
  Consulta detalhes de um pedido no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido a ser consultado (obrigatório)
  Returns: Array com dados do pedido ou false em erro
- `paypal_reembolsar(array|false $params = false): array|false` — [line 1181](../../../../../gestor/bibliotecas/paypal.php#L1181)
  Processa reembolso de uma captura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['capture_id']`: ID da captura a ser reembolsada (obrigatório)
  - `$params['valor']`: Valor do reembolso - se não informado, reembolsa total (opcional)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['nota']`: Nota ou motivo do reembolso (opcional)
  Returns: return['status'] Status do reembolso
- `paypal_consultar_reembolso(array|false $params = false): array|false` — [line 1258](../../../../../gestor/bibliotecas/paypal.php#L1258)
  Consulta detalhes de um reembolso no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['refund_id']`: ID do reembolso a ser consultado (obrigatório)
  Returns: Array com dados do reembolso ou false em erro
- `paypal_validar_webhook(array|false $params = false): bool` — [line 1311](../../../../../gestor/bibliotecas/paypal.php#L1311)
  Valida assinatura de webhook do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['headers']`: Headers HTTP recebidos no webhook (obrigatório)
  - `$params['body']`: Body JSON recebido no webhook (obrigatório)
  Returns: True se válido, false caso contrário
- `paypal_processar_webhook(array|false $params = false): array|false` — [line 1408](../../../../../gestor/bibliotecas/paypal.php#L1408)
  Processa evento de webhook do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['body']`: Body JSON do webhook (obrigatório)
  - `$params['callback']`: Função callback para processar evento (opcional)
  Returns: return['event_data'] Dados completos do evento
- `paypal_criar_produto(array|false $params = false): array|false` — [line 1482](../../../../../gestor/bibliotecas/paypal.php#L1482)
  Cria um produto no catálogo do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['nome']`: Nome do produto (obrigatório, 1-127 chars)
  - `$params['tipo']`: Tipo: PHYSICAL, DIGITAL ou SERVICE (obrigatório)
  - `$params['descricao']`: Descrição do produto (opcional, 1-256 chars)
  - `$params['categoria']`: Categoria do produto (opcional, ex: SOFTWARE)
  - `$params['imagem_url']`: URL da imagem do produto (opcional)
  - `$params['home_url']`: URL da página do produto (opcional)
  - `$params['id']`: ID customizado do produto (opcional, 6-50 chars)
  Returns: Array com dados do produto ou false em erro
- `paypal_listar_produtos(array|false $params = false): array|false` — [line 1559](../../../../../gestor/bibliotecas/paypal.php#L1559)
  Lista produtos do catálogo do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['pagina']`: Número da página (opcional, padrão: 1)
  - `$params['limite']`: Itens por página (opcional, padrão: 10, max: 20)
  - `$params['total_requerido']`: Incluir total de itens (opcional)
  Returns: Array com lista de produtos ou false em erro
- `paypal_consultar_produto(array|false $params = false): array|false` — [line 1612](../../../../../gestor/bibliotecas/paypal.php#L1612)
  Consulta detalhes de um produto no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto (obrigatório)
  Returns: Array com dados do produto ou false em erro
- `paypal_atualizar_produto(array|false $params = false): bool` — [line 1660](../../../../../gestor/bibliotecas/paypal.php#L1660)
  Atualiza um produto no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto (obrigatório)
  - `$params['descricao']`: Nova descrição (opcional)
  - `$params['categoria']`: Nova categoria (opcional)
  - `$params['imagem_url']`: Nova URL da imagem (opcional)
  - `$params['home_url']`: Nova URL da página (opcional)
  Returns: True se atualizado com sucesso, false em erro
- `paypal_criar_plano(array|false $params = false): array|false` — [line 1759](../../../../../gestor/bibliotecas/paypal.php#L1759)
  Cria um plano de assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto associado (obrigatório)
  - `$params['nome']`: Nome do plano (obrigatório, 1-127 chars)
  - `$params['ciclos']`: Ciclos de cobrança (obrigatório)
  - `$params['descricao']`: Descrição do plano (opcional)
  - `$params['status']`: Status: ACTIVE ou INACTIVE (opcional, padrão: ACTIVE)
  - `$params['preferencias_pagamento']`: Preferências de pagamento (opcional)
  - `$params['impostos']`: Impostos aplicáveis (opcional)
  Returns: Array com dados do plano ou false em erro
- `paypal_listar_planos(array|false $params = false): array|false` — [line 1881](../../../../../gestor/bibliotecas/paypal.php#L1881)
  Lista planos de assinatura do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: Filtrar por produto (opcional)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 20)
  - `$params['total_requerido']`: Incluir total (opcional)
  Returns: Array com lista de planos ou false em erro
- `paypal_consultar_plano(array|false $params = false): array|false` — [line 1937](../../../../../gestor/bibliotecas/paypal.php#L1937)
  Consulta detalhes de um plano no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Returns: Array com dados do plano ou false em erro
- `paypal_ativar_plano(array|false $params = false): bool` — [line 1981](../../../../../gestor/bibliotecas/paypal.php#L1981)
  Ativa um plano de assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Returns: True se ativado com sucesso, false em erro
- `paypal_desativar_plano(array|false $params = false): bool` — [line 2025](../../../../../gestor/bibliotecas/paypal.php#L2025)
  Desativa um plano de assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Returns: True se desativado com sucesso, false em erro
- `paypal_atualizar_precos_plano(array|false $params = false): bool` — [line 2075](../../../../../gestor/bibliotecas/paypal.php#L2075)
  Atualiza os preços de um plano no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  - `$params['precos']`: Array de esquemas de preços (obrigatório)
  Returns: True se atualizado com sucesso, false em erro
- `paypal_criar_assinatura(array|false $params = false): array|false` — [line 2150](../../../../../gestor/bibliotecas/paypal.php#L2150)
  Cria uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  - `$params['data_inicio']`: Data de início (opcional, ISO 8601)
  - `$params['referencia']`: ID de referência customizado (opcional)
  - `$params['assinante']`: Dados do assinante (opcional)
  - `$params['application_context']`: Contexto da aplicação (opcional)
  - `$params['url_retorno']`: URL de retorno após aprovação (opcional)
  - `$params['url_cancelamento']`: URL de retorno após cancelamento (opcional)
  - `$params['payment_source']`: Cartão ou token de pagamento (opcional)
  Returns: Array com dados da assinatura ou false em erro
- `paypal_consultar_assinatura(array|false $params = false): array|false` — [line 2280](../../../../../gestor/bibliotecas/paypal.php#L2280)
  Consulta detalhes de uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  Returns: Array com dados da assinatura ou false em erro
- `paypal_suspender_assinatura(array|false $params = false): bool` — [line 2325](../../../../../gestor/bibliotecas/paypal.php#L2325)
  Suspende uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo da suspensão (obrigatório)
  Returns: True se suspensa com sucesso, false em erro
- `paypal_cancelar_assinatura(array|false $params = false): bool` — [line 2371](../../../../../gestor/bibliotecas/paypal.php#L2371)
  Cancela uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo do cancelamento (obrigatório)
  Returns: True se cancelada com sucesso, false em erro
- `paypal_ativar_assinatura(array|false $params = false): bool` — [line 2417](../../../../../gestor/bibliotecas/paypal.php#L2417)
  Ativa uma assinatura suspensa no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo da reativação (obrigatório)
  Returns: True se ativada com sucesso, false em erro
- `paypal_capturar_assinatura(array|false $params = false): array|false` — [line 2465](../../../../../gestor/bibliotecas/paypal.php#L2465)
  Captura pagamento autorizado de uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['valor']`: Valor a capturar (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['nota']`: Nota sobre a captura (obrigatório)
  Returns: Array com dados da captura ou false em erro
- `paypal_listar_transacoes_assinatura(array|false $params = false): array|false` — [line 2526](../../../../../gestor/bibliotecas/paypal.php#L2526)
  Lista transações de uma assinatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['data_inicio']`: Data de início (obrigatório, ISO 8601)
  - `$params['data_fim']`: Data de fim (obrigatório, ISO 8601)
  Returns: Array com lista de transações ou false em erro
- `paypal_criar_fatura(array|false $params = false): array|false` — [line 2603](../../../../../gestor/bibliotecas/paypal.php#L2603)
  Cria uma fatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['destinatario']`: Dados do destinatário (obrigatório)
  - `$params['itens']`: Itens da fatura (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['numero']`: Número da fatura (opcional)
  - `$params['data_fatura']`: Data da fatura (opcional, formato: YYYY-MM-DD)
  - `$params['data_vencimento']`: Data de vencimento (opcional, formato: YYYY-MM-DD)
  - `$params['nota']`: Nota para o pagador (opcional)
  - `$params['termos']`: Termos e condições (opcional)
  - `$params['emissor']`: Dados do emissor (opcional)
  - `$params['desconto']`: Desconto da fatura (opcional)
  - `$params['envio']`: Dados de envio/frete (opcional)
  Returns: Array com dados da fatura ou false em erro
- `paypal_listar_faturas(array|false $params = false): array|false` — [line 2792](../../../../../gestor/bibliotecas/paypal.php#L2792)
  Lista faturas do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 100)
  - `$params['total_requerido']`: Incluir total (opcional)
  Returns: Array com lista de faturas ou false em erro
- `paypal_consultar_fatura(array|false $params = false): array|false` — [line 2845](../../../../../gestor/bibliotecas/paypal.php#L2845)
  Consulta detalhes de uma fatura no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  Returns: Array com dados da fatura ou false em erro
- `paypal_enviar_fatura(array|false $params = false): bool` — [line 2892](../../../../../gestor/bibliotecas/paypal.php#L2892)
  Envia uma fatura para o destinatário.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['enviar_para_emissor']`: Enviar cópia para emissor (opcional)
  - `$params['enviar_para_destinatario']`: Enviar para destinatário (opcional, padrão: true)
  - `$params['nota']`: Nota adicional (opcional)
  Returns: True se enviada com sucesso, false em erro
- `paypal_cancelar_fatura(array|false $params = false): bool` — [line 2950](../../../../../gestor/bibliotecas/paypal.php#L2950)
  Cancela uma fatura enviada no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['assunto']`: Assunto do email de cancelamento (opcional)
  - `$params['nota']`: Nota sobre o cancelamento (opcional)
  - `$params['notificar']`: Notificar destinatário (opcional, padrão: true)
  Returns: True se cancelada com sucesso, false em erro
- `paypal_lembrete_fatura(array|false $params = false): bool` — [line 3009](../../../../../gestor/bibliotecas/paypal.php#L3009)
  Envia lembrete de pagamento de uma fatura.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['assunto']`: Assunto do lembrete (opcional)
  - `$params['nota']`: Nota do lembrete (opcional)
  Returns: True se enviado com sucesso, false em erro
- `paypal_registrar_pagamento_fatura(array|false $params = false): array|false` — [line 3068](../../../../../gestor/bibliotecas/paypal.php#L3068)
  Registra pagamento de uma fatura.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['valor']`: Valor do pagamento (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['metodo']`: Método de pagamento (opcional): BANK_TRANSFER, CASH, CHECK, CREDIT_CARD, DEBIT_CARD, PAYPAL, WIRE_TRANSFER, OTHER
  - `$params['data']`: Data do pagamento (opcional, formato: YYYY-MM-DD)
  - `$params['nota']`: Nota sobre o pagamento (opcional)
  Returns: Array com ID do pagamento ou false em erro
- `paypal_registrar_reembolso_fatura(array|false $params = false): array|false` — [line 3140](../../../../../gestor/bibliotecas/paypal.php#L3140)
  Registra reembolso de uma fatura.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['valor']`: Valor do reembolso (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['metodo']`: Método do reembolso (opcional)
  - `$params['data']`: Data do reembolso (opcional, formato: YYYY-MM-DD)
  Returns: Array com ID do reembolso ou false em erro
- `paypal_gerar_qrcode_fatura(array|false $params = false): string|false` — [line 3207](../../../../../gestor/bibliotecas/paypal.php#L3207)
  Gera QR Code para pagamento de uma fatura.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['largura']`: Largura em pixels (opcional, 150-500)
  - `$params['altura']`: Altura em pixels (opcional, 150-500)
  Returns: Base64 da imagem do QR Code ou false em erro
- `paypal_buscar_faturas(array|false $params = false): array|false` — [line 3265](../../../../../gestor/bibliotecas/paypal.php#L3265)
  Busca faturas no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['email_destinatario']`: Email do destinatário (opcional)
  - `$params['email_emissor']`: Email do emissor (opcional)
  - `$params['status']`: Status da fatura (opcional): DRAFT, SENT, SCHEDULED, PAID, MARKED_AS_PAID, CANCELLED, REFUNDED
  - `$params['data_inicio']`: Data de início (opcional, formato: YYYY-MM-DD)
  - `$params['data_fim']`: Data de fim (opcional, formato: YYYY-MM-DD)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 100)
  Returns: Array com lista de faturas ou false em erro
- `paypal_deletar_fatura(array|false $params = false): bool` — [line 3334](../../../../../gestor/bibliotecas/paypal.php#L3334)
  Deleta uma fatura (apenas rascunhos).
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  Returns: True se deletada com sucesso, false em erro
- `paypal_gerar_numero_fatura(): string|false` — [line 3375](../../../../../gestor/bibliotecas/paypal.php#L3375)
  Gera próximo número de fatura disponível.
  Returns: Próximo número de fatura ou false em erro
- `paypal_criar_payout(array|false $params = false): array|false` — [line 3430](../../../../../gestor/bibliotecas/paypal.php#L3430)
  Cria um payout (pagamento em lote) no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['itens']`: Array de itens de pagamento (obrigatório)
  - `$params['assunto_email']`: Assunto do email (opcional)
  - `$params['mensagem_email']`: Mensagem do email (opcional)
  - `$params['sender_batch_id']`: ID do lote (opcional, gerado se não informado)
  Returns: Array com dados do payout ou false em erro
- `paypal_consultar_payout(array|false $params = false): array|false` — [line 3538](../../../../../gestor/bibliotecas/paypal.php#L3538)
  Consulta detalhes de um payout no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['payout_batch_id']`: ID do lote de payout (obrigatório)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional)
  - `$params['total_requerido']`: Incluir total (opcional)
  Returns: Array com dados do payout ou false em erro
- `paypal_consultar_item_payout(array|false $params = false): array|false` — [line 3596](../../../../../gestor/bibliotecas/paypal.php#L3596)
  Consulta detalhes de um item de payout no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['payout_item_id']`: ID do item de payout (obrigatório)
  Returns: Array com dados do item ou false em erro
- `paypal_cancelar_item_payout(array|false $params = false): array|false` — [line 3642](../../../../../gestor/bibliotecas/paypal.php#L3642)
  Cancela um item de payout não reclamado no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['payout_item_id']`: ID do item de payout (obrigatório)
  Returns: Array com dados do item cancelado ou false em erro
- `paypal_gerar_link_pagamento(array|false $params = false): array|false` — [line 3699](../../../../../gestor/bibliotecas/paypal.php#L3699)
  Gera um link de pagamento do PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['valor']`: Valor do pagamento (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['descricao']`: Descrição do pagamento (opcional)
  - `$params['itens']`: Itens do pagamento (opcional)
  - `$params['referencia']`: Referência customizada (opcional)
  - `$params['url_retorno']`: URL de retorno após aprovação (opcional)
  - `$params['url_cancelamento']`: URL de retorno após cancelamento (opcional)
  Returns: Array com link e dados do pedido ou false em erro
- `paypal_verificar_link_pagamento(array|false $params = false): array|false` — [line 3724](../../../../../gestor/bibliotecas/paypal.php#L3724)
  Verifica status de um link de pagamento.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido (obrigatório)
  Returns: Array com status do pedido ou false em erro
- `paypal_listar_disputas(array|false $params = false): array|false` — [line 3760](../../../../../gestor/bibliotecas/paypal.php#L3760)
  Lista disputas no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['status']`: Status da disputa (opcional): OPEN, WAITING_FOR_SELLER_RESPONSE, WAITING_FOR_BUYER_RESPONSE, UNDER_REVIEW, RESOLVED, EXPIRED
  - `$params['motivo']`: Motivo da disputa (opcional): MERCHANDISE_OR_SERVICE_NOT_RECEIVED, MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED, UNAUTHORISED, CREDIT_NOT_PROCESSED, DUPLICATE_TRANSACTION, INCORRECT_AMOUNT, PAYMENT_BY_OTHER_MEANS, CANCELED_RECURRING_BILLING, PROBLEM_WITH_REMITTANCE
  - `$params['data_inicio']`: Data de início (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
  - `$params['data_fim']`: Data de fim (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, 1-50)
  Returns: Array com lista de disputas ou false em erro
- `paypal_consultar_disputa(array|false $params = false): array|false` — [line 3823](../../../../../gestor/bibliotecas/paypal.php#L3823)
  Consulta detalhes de uma disputa no PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  Returns: Array com dados da disputa ou false em erro
- `paypal_aceitar_disputa(array|false $params = false): array|false` — [line 3870](../../../../../gestor/bibliotecas/paypal.php#L3870)
  Aceita reclamação e faz reembolso ao comprador.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['nota']`: Nota explicativa (opcional)
  - `$params['valor_reembolso']`: Valor do reembolso (opcional, padrão: valor total)
  - `$params['moeda']`: Código da moeda (opcional)
  Returns: Array com resultado ou false em erro
- `paypal_contestar_disputa(array|false $params = false): array|false` — [line 3941](../../../../../gestor/bibliotecas/paypal.php#L3941)
  Contesta uma disputa fornecendo evidências.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['mensagem']`: Mensagem de contestação (obrigatório)
  - `$params['evidencias']`: Array de evidências (opcional)
  Returns: Array com resultado ou false em erro
- `paypal_mensagem_disputa(array|false $params = false): array|false` — [line 4015](../../../../../gestor/bibliotecas/paypal.php#L4015)
  Envia mensagem para uma disputa.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['mensagem']`: Mensagem para o comprador (obrigatório)
  Returns: Array com resultado ou false em erro
- `paypal_escalar_disputa(array|false $params = false): array|false` — [line 4066](../../../../../gestor/bibliotecas/paypal.php#L4066)
  Escala uma disputa para reclamação.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['nota']`: Nota explicativa (opcional)
  Returns: Array com resultado ou false em erro
- `paypal_listar_transacoes(array|false $params = false): array|false` — [line 4126](../../../../../gestor/bibliotecas/paypal.php#L4126)
  Lista transações da conta PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['data_inicio']`: Data de início (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
  - `$params['data_fim']`: Data de fim (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
  - `$params['transaction_id']`: ID da transação específica (opcional)
  - `$params['status']`: Status da transação (opcional): D (Denied), P (Pending), S (Successful), V (Reversed)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 500)
  Returns: Array com lista de transações ou false em erro
- `paypal_consultar_saldo(array|false $params = false): array|false` — [line 4192](../../../../../gestor/bibliotecas/paypal.php#L4192)
  Consulta saldo da conta PayPal.
  Parameters:
  - `$params`: Parâmetros da função
  - `$params['moeda']`: Código da moeda (opcional, retorna todas se não especificado)
  Returns: Array com saldos ou false em erro
- `paypal_formatar_valor(float $valor, string $moeda = 'BRL'): string` — [line 4242](../../../../../gestor/bibliotecas/paypal.php#L4242)
  Formata valor monetário para exibição.
  Parameters:
  - `$valor`: Valor numérico
  - `$moeda`: Código da moeda (padrão: BRL)
  Returns: Valor formatado
- `paypal_traduzir_status(string $status, string $tipo = 'order'): string` — [line 4269](../../../../../gestor/bibliotecas/paypal.php#L4269)
  Traduz status do PayPal para português.
  Parameters:
  - `$status`: Status em inglês
  - `$tipo`: Tipo: order, subscription, invoice, dispute, payout
  Returns: Status traduzido
- `paypal_assinatura_ativa(string $subscription_id): bool` — [line 4344](../../../../../gestor/bibliotecas/paypal.php#L4344)
  Verifica se uma assinatura está ativa.
  Parameters:
  - `$subscription_id`: ID da assinatura
  Returns: True se ativa, false caso contrário
- `paypal_pedido_pago(string $order_id): bool` — [line 4363](../../../../../gestor/bibliotecas/paypal.php#L4363)
  Verifica se um pedido foi pago.
  Parameters:
  - `$order_id`: ID do pedido
  Returns: True se pago, false caso contrário
- `paypal_fatura_paga(string $invoice_id): bool` — [line 4382](../../../../../gestor/bibliotecas/paypal.php#L4382)
  Verifica se uma fatura foi paga.
  Parameters:
  - `$invoice_id`: ID da fatura
  Returns: True se paga, false caso contrário
- `paypal_calcular_taxa(float $valor, string $tipo = 'nacional'): array` — [line 4405](../../../../../gestor/bibliotecas/paypal.php#L4405)
  Calcula taxa do PayPal para um valor.
  Parameters:
  - `$valor`: Valor da transação
  - `$tipo`: Tipo: nacional, internacional
  Returns: Array com valor líquido e taxa estimada
- `paypal_gerar_id(string $prefixo = 'TXN'): string` — [line 4440](../../../../../gestor/bibliotecas/paypal.php#L4440)
  Gera um ID único para transações.
  Parameters:
  - `$prefixo`: Prefixo do ID (opcional)
  Returns: ID único
- `paypal_validar_email(string $email): bool` — [line 4451](../../../../../gestor/bibliotecas/paypal.php#L4451)
  Valida email do PayPal.
  Parameters:
  - `$email`: Email a validar
  Returns: True se válido
- `paypal_formatar_data(string|int $data, bool $incluir_hora = true): string` — [line 4463](../../../../../gestor/bibliotecas/paypal.php#L4463)
  Converte data para formato PayPal.
  Parameters:
  - `$data`: Data (timestamp ou string)
  - `$incluir_hora`: Incluir hora no formato
  Returns: Data formatada
- `paypal_info(): array` — [line 4482](../../../../../gestor/bibliotecas/paypal.php#L4482)
  Obtém informações da biblioteca PayPal.
  Returns: Array com informações da biblioteca

<!-- c2f:extract:end -->
