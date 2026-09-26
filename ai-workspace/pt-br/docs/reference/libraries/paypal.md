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
  Registra um log de evento relacionado ao PayPal.
- `paypal_gateways_pagamentos_configurar(array|false $params = false): bool` — [linha 64](../../../../../gestor/bibliotecas/paypal.php#L64)
  Configura a biblioteca PayPal para usar um gateway de pagamentos do banco.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['id']`: ID específico do gateway (opcional)
  - `$params['tipo']`: Tipo do gateway, ex: 'paypal' (opcional, padrão: 'paypal')
  Retorno: True se configurado com sucesso, false caso contrário
- `paypal_gateway_persistir_token(array $token_data): bool` — [linha 169](../../../../../gestor/bibliotecas/paypal.php#L169)
  Persiste o token OAuth no banco de dados do gateway ativo.
  Parâmetros:
  - `$token_data`: Dados do token (access_token, token_type, expires_at, etc)
  Retorno: True se persistido, false se não está em modo gateway
- `paypal_gateway_registrar_transacao(array|false $params = false): bool` — [linha 207](../../../../../gestor/bibliotecas/paypal.php#L207)
  Registra uma transação nas estatísticas do gateway ativo.
  Parâmetros:
  - `$params`: Parâmetros
  - `$params['valor']`: Valor da transação (opcional, padrão: 0)
  - `$params['moeda']`: Moeda da transação (opcional)
  Retorno: True se registrado, false se não está em modo gateway
- `paypal_gateway_atualizar_conexao(bool $sucesso, string $mensagem = ''): bool` — [linha 242](../../../../../gestor/bibliotecas/paypal.php#L242)
  Atualiza o status de conexão do gateway ativo.
  Parâmetros:
  - `$sucesso`: Se a conexão foi bem-sucedida
  - `$mensagem`: Mensagem descritiva do resultado
  Retorno: True se atualizado, false se não está em modo gateway
- `paypal_is_modo_gateway(): bool` — [linha 270](../../../../../gestor/bibliotecas/paypal.php#L270)
  Verifica se a biblioteca está operando em modo gateway (banco de dados).
  Retorno: True se está em modo gateway
- `paypal_obter_gateway_id(): string|null` — [linha 282](../../../../../gestor/bibliotecas/paypal.php#L282)
  Obtém o ID do gateway ativo.
  Retorno: ID alfanumérico do gateway ou null se não está em modo gateway
- `paypal_obter_gateway_dados(): array|null` — [linha 294](../../../../../gestor/bibliotecas/paypal.php#L294)
  Obtém os dados completos do gateway ativo.
  Retorno: Dados do gateway ou null se não está em modo gateway
- `paypal_padrao_persistir_token(array $token_data): bool` — [linha 313](../../../../../gestor/bibliotecas/paypal.php#L313)
  Persiste o token OAuth nas variáveis do sistema (modo padrão).
  Parâmetros:
  - `$token_data`: Dados do token (access_token, token_type, expires_at, etc)
  Retorno: True se persistido com sucesso
- `paypal_padrao_restaurar_token(): bool` — [linha 355](../../../../../gestor/bibliotecas/paypal.php#L355)
  Restaura o token OAuth persistido nas variáveis do sistema (modo padrão).
  Retorno: True se token restaurado e válido, false caso contrário
- `paypal_auto_configurar(): void` — [linha 403](../../../../../gestor/bibliotecas/paypal.php#L403)
  Inicializa a biblioteca PayPal automaticamente baseado em $_CONFIG['paypal']['default'].
- `paypal_obter_url_api(): string` — [linha 431](../../../../../gestor/bibliotecas/paypal.php#L431)
  Obtém a URL base da API do PayPal baseado no modo (sandbox/live).
  Retorno: URL base da API
- `paypal_obter_credenciais(): array|false` — [linha 450](../../../../../gestor/bibliotecas/paypal.php#L450)
  Obtém as credenciais do PayPal baseado no modo (sandbox/live).
  Retorno: Array com client_id e client_secret ou false se não configurado
- `paypal_requisicao(array|false $params = false): array|false` — [linha 484](../../../../../gestor/bibliotecas/paypal.php#L484)
  Realiza requisição HTTP para a API do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da requisição
  - `$params['endpoint']`: Endpoint da API (ex: '/v1/oauth2/token')
  - `$params['method']`: Método HTTP (GET, POST, PATCH, etc) - padrão: GET
  - `$params['data']`: Dados para enviar no body (opcional)
  - `$params['headers']`: Headers customizados (opcional)
  - `$params['auth_basic']`: Usar Basic Auth com credenciais (opcional)
  - `$params['access_token']`: Token OAuth para autenticação (opcional)
  Retorno: Array com resposta ou false em erro
- `paypal_autenticar(array|false $params = false): array|false` — [linha 600](../../../../../gestor/bibliotecas/paypal.php#L600)
  Autentica com PayPal e obtém access token OAuth 2.0.
  Parâmetros:
  - `$params`: Parâmetros da função (opcional)
  - `$params['force_refresh']`: Forçar nova autenticação ignorando cache (opcional)
  Retorno: return['expires_at'] Timestamp de expiração
- `paypal_gerar_client_token(array|false $params = false): string|false` — [linha 673](../../../../../gestor/bibliotecas/paypal.php#L673)
  Gera o client token usado pelos Card Fields/Hosted Fields no navegador.
  Parâmetros:
  - `$params`: Parâmetros opcionais
  - `$params['customer_id']`: ID do cliente no vault do PayPal (opcional)
  Retorno: Client token ou false em erro
- `paypal_validar_payment_source(mixed $payment_source): bool` — [linha 731](../../../../../gestor/bibliotecas/paypal.php#L731)
  Valida a estrutura mínima de uma fonte de pagamento aceita neste fluxo.
  Parâmetros:
  - `$payment_source`: Fonte de pagamento recebida pelo backend
  Retorno: True para payment_source.card ou payment_source.token não vazios
- `paypal_criar_pedido(array|false $params = false): array|false` — [linha 770](../../../../../gestor/bibliotecas/paypal.php#L770)
  Cria um pedido (order) no PayPal.
  Parâmetros:
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
  Retorno: return['approve_url'] URL para aprovação do pedido
- `paypal_capturar_pedido(array|false $params = false): array|false` — [linha 944](../../../../../gestor/bibliotecas/paypal.php#L944)
  Captura um pedido (order) aprovado no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido a ser capturado (obrigatório)
  Retorno: return['capture_data'] Dados completos da captura
- `paypal_processar_pagamento_transparente(array|false $params = false): array|false` — [linha 1023](../../../../../gestor/bibliotecas/paypal.php#L1023)
  Processa um pagamento transparente recebido do frontend.
  Parâmetros:
  - `$params`: Parâmetros do fluxo
  - `$params['tipo']`: pedido ou assinatura (opcional, inferido por plan_id)
  - `$params['payment_source']`: payment_source.card ou payment_source.token
  - `$params['order_id']`: Ordem aprovada pelo Card Fields para captura (opcional)
  - `$params['capturar']`: Capturar ordem APPROVED automaticamente (padrão: true)
  Retorno: Resultado normalizado ou false para payload inválido/erro da API
- `paypal_consultar_pedido(array|false $params = false): array|false` — [linha 1125](../../../../../gestor/bibliotecas/paypal.php#L1125)
  Consulta detalhes de um pedido no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido a ser consultado (obrigatório)
  Retorno: Array com dados do pedido ou false em erro
- `paypal_reembolsar(array|false $params = false): array|false` — [linha 1181](../../../../../gestor/bibliotecas/paypal.php#L1181)
  Processa reembolso de uma captura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['capture_id']`: ID da captura a ser reembolsada (obrigatório)
  - `$params['valor']`: Valor do reembolso - se não informado, reembolsa total (opcional)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['nota']`: Nota ou motivo do reembolso (opcional)
  Retorno: return['status'] Status do reembolso
- `paypal_consultar_reembolso(array|false $params = false): array|false` — [linha 1258](../../../../../gestor/bibliotecas/paypal.php#L1258)
  Consulta detalhes de um reembolso no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['refund_id']`: ID do reembolso a ser consultado (obrigatório)
  Retorno: Array com dados do reembolso ou false em erro
- `paypal_validar_webhook(array|false $params = false): bool` — [linha 1311](../../../../../gestor/bibliotecas/paypal.php#L1311)
  Valida assinatura de webhook do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['headers']`: Headers HTTP recebidos no webhook (obrigatório)
  - `$params['body']`: Body JSON recebido no webhook (obrigatório)
  Retorno: True se válido, false caso contrário
- `paypal_processar_webhook(array|false $params = false): array|false` — [linha 1408](../../../../../gestor/bibliotecas/paypal.php#L1408)
  Processa evento de webhook do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['body']`: Body JSON do webhook (obrigatório)
  - `$params['callback']`: Função callback para processar evento (opcional)
  Retorno: return['event_data'] Dados completos do evento
- `paypal_criar_produto(array|false $params = false): array|false` — [linha 1482](../../../../../gestor/bibliotecas/paypal.php#L1482)
  Cria um produto no catálogo do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['nome']`: Nome do produto (obrigatório, 1-127 chars)
  - `$params['tipo']`: Tipo: PHYSICAL, DIGITAL ou SERVICE (obrigatório)
  - `$params['descricao']`: Descrição do produto (opcional, 1-256 chars)
  - `$params['categoria']`: Categoria do produto (opcional, ex: SOFTWARE)
  - `$params['imagem_url']`: URL da imagem do produto (opcional)
  - `$params['home_url']`: URL da página do produto (opcional)
  - `$params['id']`: ID customizado do produto (opcional, 6-50 chars)
  Retorno: Array com dados do produto ou false em erro
- `paypal_listar_produtos(array|false $params = false): array|false` — [linha 1559](../../../../../gestor/bibliotecas/paypal.php#L1559)
  Lista produtos do catálogo do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['pagina']`: Número da página (opcional, padrão: 1)
  - `$params['limite']`: Itens por página (opcional, padrão: 10, max: 20)
  - `$params['total_requerido']`: Incluir total de itens (opcional)
  Retorno: Array com lista de produtos ou false em erro
- `paypal_consultar_produto(array|false $params = false): array|false` — [linha 1612](../../../../../gestor/bibliotecas/paypal.php#L1612)
  Consulta detalhes de um produto no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto (obrigatório)
  Retorno: Array com dados do produto ou false em erro
- `paypal_atualizar_produto(array|false $params = false): bool` — [linha 1660](../../../../../gestor/bibliotecas/paypal.php#L1660)
  Atualiza um produto no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto (obrigatório)
  - `$params['descricao']`: Nova descrição (opcional)
  - `$params['categoria']`: Nova categoria (opcional)
  - `$params['imagem_url']`: Nova URL da imagem (opcional)
  - `$params['home_url']`: Nova URL da página (opcional)
  Retorno: True se atualizado com sucesso, false em erro
- `paypal_criar_plano(array|false $params = false): array|false` — [linha 1759](../../../../../gestor/bibliotecas/paypal.php#L1759)
  Cria um plano de assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: ID do produto associado (obrigatório)
  - `$params['nome']`: Nome do plano (obrigatório, 1-127 chars)
  - `$params['ciclos']`: Ciclos de cobrança (obrigatório)
  - `$params['descricao']`: Descrição do plano (opcional)
  - `$params['status']`: Status: ACTIVE ou INACTIVE (opcional, padrão: ACTIVE)
  - `$params['preferencias_pagamento']`: Preferências de pagamento (opcional)
  - `$params['impostos']`: Impostos aplicáveis (opcional)
  Retorno: Array com dados do plano ou false em erro
- `paypal_listar_planos(array|false $params = false): array|false` — [linha 1881](../../../../../gestor/bibliotecas/paypal.php#L1881)
  Lista planos de assinatura do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['product_id']`: Filtrar por produto (opcional)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 20)
  - `$params['total_requerido']`: Incluir total (opcional)
  Retorno: Array com lista de planos ou false em erro
- `paypal_consultar_plano(array|false $params = false): array|false` — [linha 1937](../../../../../gestor/bibliotecas/paypal.php#L1937)
  Consulta detalhes de um plano no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Retorno: Array com dados do plano ou false em erro
- `paypal_ativar_plano(array|false $params = false): bool` — [linha 1981](../../../../../gestor/bibliotecas/paypal.php#L1981)
  Ativa um plano de assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Retorno: True se ativado com sucesso, false em erro
- `paypal_desativar_plano(array|false $params = false): bool` — [linha 2025](../../../../../gestor/bibliotecas/paypal.php#L2025)
  Desativa um plano de assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  Retorno: True se desativado com sucesso, false em erro
- `paypal_atualizar_precos_plano(array|false $params = false): bool` — [linha 2075](../../../../../gestor/bibliotecas/paypal.php#L2075)
  Atualiza os preços de um plano no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  - `$params['precos']`: Array de esquemas de preços (obrigatório)
  Retorno: True se atualizado com sucesso, false em erro
- `paypal_criar_assinatura(array|false $params = false): array|false` — [linha 2150](../../../../../gestor/bibliotecas/paypal.php#L2150)
  Cria uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['plan_id']`: ID do plano (obrigatório)
  - `$params['data_inicio']`: Data de início (opcional, ISO 8601)
  - `$params['referencia']`: ID de referência customizado (opcional)
  - `$params['assinante']`: Dados do assinante (opcional)
  - `$params['application_context']`: Contexto da aplicação (opcional)
  - `$params['url_retorno']`: URL de retorno após aprovação (opcional)
  - `$params['url_cancelamento']`: URL de retorno após cancelamento (opcional)
  - `$params['payment_source']`: Cartão ou token de pagamento (opcional)
  Retorno: Array com dados da assinatura ou false em erro
- `paypal_consultar_assinatura(array|false $params = false): array|false` — [linha 2280](../../../../../gestor/bibliotecas/paypal.php#L2280)
  Consulta detalhes de uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  Retorno: Array com dados da assinatura ou false em erro
- `paypal_suspender_assinatura(array|false $params = false): bool` — [linha 2325](../../../../../gestor/bibliotecas/paypal.php#L2325)
  Suspende uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo da suspensão (obrigatório)
  Retorno: True se suspensa com sucesso, false em erro
- `paypal_cancelar_assinatura(array|false $params = false): bool` — [linha 2371](../../../../../gestor/bibliotecas/paypal.php#L2371)
  Cancela uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo do cancelamento (obrigatório)
  Retorno: True se cancelada com sucesso, false em erro
- `paypal_ativar_assinatura(array|false $params = false): bool` — [linha 2417](../../../../../gestor/bibliotecas/paypal.php#L2417)
  Ativa uma assinatura suspensa no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['motivo']`: Motivo da reativação (obrigatório)
  Retorno: True se ativada com sucesso, false em erro
- `paypal_capturar_assinatura(array|false $params = false): array|false` — [linha 2465](../../../../../gestor/bibliotecas/paypal.php#L2465)
  Captura pagamento autorizado de uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['valor']`: Valor a capturar (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['nota']`: Nota sobre a captura (obrigatório)
  Retorno: Array com dados da captura ou false em erro
- `paypal_listar_transacoes_assinatura(array|false $params = false): array|false` — [linha 2526](../../../../../gestor/bibliotecas/paypal.php#L2526)
  Lista transações de uma assinatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['subscription_id']`: ID da assinatura (obrigatório)
  - `$params['data_inicio']`: Data de início (obrigatório, ISO 8601)
  - `$params['data_fim']`: Data de fim (obrigatório, ISO 8601)
  Retorno: Array com lista de transações ou false em erro
- `paypal_criar_fatura(array|false $params = false): array|false` — [linha 2603](../../../../../gestor/bibliotecas/paypal.php#L2603)
  Cria uma fatura no PayPal.
  Parâmetros:
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
  Retorno: Array com dados da fatura ou false em erro
- `paypal_listar_faturas(array|false $params = false): array|false` — [linha 2792](../../../../../gestor/bibliotecas/paypal.php#L2792)
  Lista faturas do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 100)
  - `$params['total_requerido']`: Incluir total (opcional)
  Retorno: Array com lista de faturas ou false em erro
- `paypal_consultar_fatura(array|false $params = false): array|false` — [linha 2845](../../../../../gestor/bibliotecas/paypal.php#L2845)
  Consulta detalhes de uma fatura no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  Retorno: Array com dados da fatura ou false em erro
- `paypal_enviar_fatura(array|false $params = false): bool` — [linha 2892](../../../../../gestor/bibliotecas/paypal.php#L2892)
  Envia uma fatura para o destinatário.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['enviar_para_emissor']`: Enviar cópia para emissor (opcional)
  - `$params['enviar_para_destinatario']`: Enviar para destinatário (opcional, padrão: true)
  - `$params['nota']`: Nota adicional (opcional)
  Retorno: True se enviada com sucesso, false em erro
- `paypal_cancelar_fatura(array|false $params = false): bool` — [linha 2950](../../../../../gestor/bibliotecas/paypal.php#L2950)
  Cancela uma fatura enviada no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['assunto']`: Assunto do email de cancelamento (opcional)
  - `$params['nota']`: Nota sobre o cancelamento (opcional)
  - `$params['notificar']`: Notificar destinatário (opcional, padrão: true)
  Retorno: True se cancelada com sucesso, false em erro
- `paypal_lembrete_fatura(array|false $params = false): bool` — [linha 3009](../../../../../gestor/bibliotecas/paypal.php#L3009)
  Envia lembrete de pagamento de uma fatura.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['assunto']`: Assunto do lembrete (opcional)
  - `$params['nota']`: Nota do lembrete (opcional)
  Retorno: True se enviado com sucesso, false em erro
- `paypal_registrar_pagamento_fatura(array|false $params = false): array|false` — [linha 3068](../../../../../gestor/bibliotecas/paypal.php#L3068)
  Registra pagamento de uma fatura.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['valor']`: Valor do pagamento (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['metodo']`: Método de pagamento (opcional): BANK_TRANSFER, CASH, CHECK, CREDIT_CARD, DEBIT_CARD, PAYPAL, WIRE_TRANSFER, OTHER
  - `$params['data']`: Data do pagamento (opcional, formato: YYYY-MM-DD)
  - `$params['nota']`: Nota sobre o pagamento (opcional)
  Retorno: Array com ID do pagamento ou false em erro
- `paypal_registrar_reembolso_fatura(array|false $params = false): array|false` — [linha 3140](../../../../../gestor/bibliotecas/paypal.php#L3140)
  Registra reembolso de uma fatura.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['valor']`: Valor do reembolso (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional)
  - `$params['metodo']`: Método do reembolso (opcional)
  - `$params['data']`: Data do reembolso (opcional, formato: YYYY-MM-DD)
  Retorno: Array com ID do reembolso ou false em erro
- `paypal_gerar_qrcode_fatura(array|false $params = false): string|false` — [linha 3207](../../../../../gestor/bibliotecas/paypal.php#L3207)
  Gera QR Code para pagamento de uma fatura.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  - `$params['largura']`: Largura em pixels (opcional, 150-500)
  - `$params['altura']`: Altura em pixels (opcional, 150-500)
  Retorno: Base64 da imagem do QR Code ou false em erro
- `paypal_buscar_faturas(array|false $params = false): array|false` — [linha 3265](../../../../../gestor/bibliotecas/paypal.php#L3265)
  Busca faturas no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['email_destinatario']`: Email do destinatário (opcional)
  - `$params['email_emissor']`: Email do emissor (opcional)
  - `$params['status']`: Status da fatura (opcional): DRAFT, SENT, SCHEDULED, PAID, MARKED_AS_PAID, CANCELLED, REFUNDED
  - `$params['data_inicio']`: Data de início (opcional, formato: YYYY-MM-DD)
  - `$params['data_fim']`: Data de fim (opcional, formato: YYYY-MM-DD)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 100)
  Retorno: Array com lista de faturas ou false em erro
- `paypal_deletar_fatura(array|false $params = false): bool` — [linha 3334](../../../../../gestor/bibliotecas/paypal.php#L3334)
  Deleta uma fatura (apenas rascunhos).
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['invoice_id']`: ID da fatura (obrigatório)
  Retorno: True se deletada com sucesso, false em erro
- `paypal_gerar_numero_fatura(): string|false` — [linha 3375](../../../../../gestor/bibliotecas/paypal.php#L3375)
  Gera próximo número de fatura disponível.
  Retorno: Próximo número de fatura ou false em erro
- `paypal_criar_payout(array|false $params = false): array|false` — [linha 3430](../../../../../gestor/bibliotecas/paypal.php#L3430)
  Cria um payout (pagamento em lote) no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['itens']`: Array de itens de pagamento (obrigatório)
  - `$params['assunto_email']`: Assunto do email (opcional)
  - `$params['mensagem_email']`: Mensagem do email (opcional)
  - `$params['sender_batch_id']`: ID do lote (opcional, gerado se não informado)
  Retorno: Array com dados do payout ou false em erro
- `paypal_consultar_payout(array|false $params = false): array|false` — [linha 3538](../../../../../gestor/bibliotecas/paypal.php#L3538)
  Consulta detalhes de um payout no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['payout_batch_id']`: ID do lote de payout (obrigatório)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional)
  - `$params['total_requerido']`: Incluir total (opcional)
  Retorno: Array com dados do payout ou false em erro
- `paypal_consultar_item_payout(array|false $params = false): array|false` — [linha 3596](../../../../../gestor/bibliotecas/paypal.php#L3596)
  Consulta detalhes de um item de payout no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['payout_item_id']`: ID do item de payout (obrigatório)
  Retorno: Array com dados do item ou false em erro
- `paypal_cancelar_item_payout(array|false $params = false): array|false` — [linha 3642](../../../../../gestor/bibliotecas/paypal.php#L3642)
  Cancela um item de payout não reclamado no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['payout_item_id']`: ID do item de payout (obrigatório)
  Retorno: Array com dados do item cancelado ou false em erro
- `paypal_gerar_link_pagamento(array|false $params = false): array|false` — [linha 3699](../../../../../gestor/bibliotecas/paypal.php#L3699)
  Gera um link de pagamento do PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['valor']`: Valor do pagamento (obrigatório)
  - `$params['moeda']`: Código da moeda (opcional, padrão: BRL)
  - `$params['descricao']`: Descrição do pagamento (opcional)
  - `$params['itens']`: Itens do pagamento (opcional)
  - `$params['referencia']`: Referência customizada (opcional)
  - `$params['url_retorno']`: URL de retorno após aprovação (opcional)
  - `$params['url_cancelamento']`: URL de retorno após cancelamento (opcional)
  Retorno: Array com link e dados do pedido ou false em erro
- `paypal_verificar_link_pagamento(array|false $params = false): array|false` — [linha 3724](../../../../../gestor/bibliotecas/paypal.php#L3724)
  Verifica status de um link de pagamento.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['order_id']`: ID do pedido (obrigatório)
  Retorno: Array com status do pedido ou false em erro
- `paypal_listar_disputas(array|false $params = false): array|false` — [linha 3760](../../../../../gestor/bibliotecas/paypal.php#L3760)
  Lista disputas no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['status']`: Status da disputa (opcional): OPEN, WAITING_FOR_SELLER_RESPONSE, WAITING_FOR_BUYER_RESPONSE, UNDER_REVIEW, RESOLVED, EXPIRED
  - `$params['motivo']`: Motivo da disputa (opcional): MERCHANDISE_OR_SERVICE_NOT_RECEIVED, MERCHANDISE_OR_SERVICE_NOT_AS_DESCRIBED, UNAUTHORISED, CREDIT_NOT_PROCESSED, DUPLICATE_TRANSACTION, INCORRECT_AMOUNT, PAYMENT_BY_OTHER_MEANS, CANCELED_RECURRING_BILLING, PROBLEM_WITH_REMITTANCE
  - `$params['data_inicio']`: Data de início (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
  - `$params['data_fim']`: Data de fim (opcional, formato: YYYY-MM-DDTHH:MM:SS.SSSZ)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, 1-50)
  Retorno: Array com lista de disputas ou false em erro
- `paypal_consultar_disputa(array|false $params = false): array|false` — [linha 3823](../../../../../gestor/bibliotecas/paypal.php#L3823)
  Consulta detalhes de uma disputa no PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  Retorno: Array com dados da disputa ou false em erro
- `paypal_aceitar_disputa(array|false $params = false): array|false` — [linha 3870](../../../../../gestor/bibliotecas/paypal.php#L3870)
  Aceita reclamação e faz reembolso ao comprador.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['nota']`: Nota explicativa (opcional)
  - `$params['valor_reembolso']`: Valor do reembolso (opcional, padrão: valor total)
  - `$params['moeda']`: Código da moeda (opcional)
  Retorno: Array com resultado ou false em erro
- `paypal_contestar_disputa(array|false $params = false): array|false` — [linha 3941](../../../../../gestor/bibliotecas/paypal.php#L3941)
  Contesta uma disputa fornecendo evidências.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['mensagem']`: Mensagem de contestação (obrigatório)
  - `$params['evidencias']`: Array de evidências (opcional)
  Retorno: Array com resultado ou false em erro
- `paypal_mensagem_disputa(array|false $params = false): array|false` — [linha 4015](../../../../../gestor/bibliotecas/paypal.php#L4015)
  Envia mensagem para uma disputa.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['mensagem']`: Mensagem para o comprador (obrigatório)
  Retorno: Array com resultado ou false em erro
- `paypal_escalar_disputa(array|false $params = false): array|false` — [linha 4066](../../../../../gestor/bibliotecas/paypal.php#L4066)
  Escala uma disputa para reclamação.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['dispute_id']`: ID da disputa (obrigatório)
  - `$params['nota']`: Nota explicativa (opcional)
  Retorno: Array com resultado ou false em erro
- `paypal_listar_transacoes(array|false $params = false): array|false` — [linha 4126](../../../../../gestor/bibliotecas/paypal.php#L4126)
  Lista transações da conta PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['data_inicio']`: Data de início (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
  - `$params['data_fim']`: Data de fim (obrigatório, formato: YYYY-MM-DDTHH:MM:SSZ)
  - `$params['transaction_id']`: ID da transação específica (opcional)
  - `$params['status']`: Status da transação (opcional): D (Denied), P (Pending), S (Successful), V (Reversed)
  - `$params['pagina']`: Número da página (opcional)
  - `$params['limite']`: Itens por página (opcional, max: 500)
  Retorno: Array com lista de transações ou false em erro
- `paypal_consultar_saldo(array|false $params = false): array|false` — [linha 4192](../../../../../gestor/bibliotecas/paypal.php#L4192)
  Consulta saldo da conta PayPal.
  Parâmetros:
  - `$params`: Parâmetros da função
  - `$params['moeda']`: Código da moeda (opcional, retorna todas se não especificado)
  Retorno: Array com saldos ou false em erro
- `paypal_formatar_valor(float $valor, string $moeda = 'BRL'): string` — [linha 4242](../../../../../gestor/bibliotecas/paypal.php#L4242)
  Formata valor monetário para exibição.
  Parâmetros:
  - `$valor`: Valor numérico
  - `$moeda`: Código da moeda (padrão: BRL)
  Retorno: Valor formatado
- `paypal_traduzir_status(string $status, string $tipo = 'order'): string` — [linha 4269](../../../../../gestor/bibliotecas/paypal.php#L4269)
  Traduz status do PayPal para português.
  Parâmetros:
  - `$status`: Status em inglês
  - `$tipo`: Tipo: order, subscription, invoice, dispute, payout
  Retorno: Status traduzido
- `paypal_assinatura_ativa(string $subscription_id): bool` — [linha 4344](../../../../../gestor/bibliotecas/paypal.php#L4344)
  Verifica se uma assinatura está ativa.
  Parâmetros:
  - `$subscription_id`: ID da assinatura
  Retorno: True se ativa, false caso contrário
- `paypal_pedido_pago(string $order_id): bool` — [linha 4363](../../../../../gestor/bibliotecas/paypal.php#L4363)
  Verifica se um pedido foi pago.
  Parâmetros:
  - `$order_id`: ID do pedido
  Retorno: True se pago, false caso contrário
- `paypal_fatura_paga(string $invoice_id): bool` — [linha 4382](../../../../../gestor/bibliotecas/paypal.php#L4382)
  Verifica se uma fatura foi paga.
  Parâmetros:
  - `$invoice_id`: ID da fatura
  Retorno: True se paga, false caso contrário
- `paypal_calcular_taxa(float $valor, string $tipo = 'nacional'): array` — [linha 4405](../../../../../gestor/bibliotecas/paypal.php#L4405)
  Calcula taxa do PayPal para um valor.
  Parâmetros:
  - `$valor`: Valor da transação
  - `$tipo`: Tipo: nacional, internacional
  Retorno: Array com valor líquido e taxa estimada
- `paypal_gerar_id(string $prefixo = 'TXN'): string` — [linha 4440](../../../../../gestor/bibliotecas/paypal.php#L4440)
  Gera um ID único para transações.
  Parâmetros:
  - `$prefixo`: Prefixo do ID (opcional)
  Retorno: ID único
- `paypal_validar_email(string $email): bool` — [linha 4451](../../../../../gestor/bibliotecas/paypal.php#L4451)
  Valida email do PayPal.
  Parâmetros:
  - `$email`: Email a validar
  Retorno: True se válido
- `paypal_formatar_data(string|int $data, bool $incluir_hora = true): string` — [linha 4463](../../../../../gestor/bibliotecas/paypal.php#L4463)
  Converte data para formato PayPal.
  Parâmetros:
  - `$data`: Data (timestamp ou string)
  - `$incluir_hora`: Incluir hora no formato
  Retorno: Data formatada
- `paypal_info(): array` — [linha 4482](../../../../../gestor/bibliotecas/paypal.php#L4482)
  Obtém informações da biblioteca PayPal.
  Retorno: Array com informações da biblioteca

<!-- c2f:extract:end -->
