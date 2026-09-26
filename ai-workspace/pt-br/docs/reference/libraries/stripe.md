---
title: "Biblioteca stripe.php"
label: "Stripe"
description: "Cliente REST da API do Stripe: credenciais do gateway, clientes, pagamentos, assinaturas, catálogo de produtos e preços, reembolso e validação de webhook."
section: reference
order: 310
sources:
  - gestor/bibliotecas/stripe.php
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: 2ede88ff
---

# Biblioteca `stripe.php`

Chamadas diretas à API REST do Stripe (`api.stripe.com`) com cURL, sem o SDK oficial. A versão da API é fixada em `STRIPE_API_VERSION` (`2024-06-20`), para o formato das respostas não mudar com a configuração da conta.

## Credenciais

O caminho normal é um registro da tabela `gateways_pagamentos` (cadastrado no módulo de gateways) do tipo `stripe`:

| Coluna | Uso |
|---|---|
| `client_id` | Publishable key (`pk_…`) |
| `client_secret` | Secret key (`sk_…`) |
| `webhook_secret` | Signing secret do webhook (`whsec_…`) |
| `ambiente` | `P` = produção; qualquer outro valor = sandbox |
| `moeda` | Padrão `BRL` |

```php
gestor_incluir_biblioteca('stripe');
stripe_gateways_pagamentos_configurar();            // o gateway padrão (padrao='S') ou o primeiro ativo
stripe_gateways_pagamentos_configurar(['id' => 7]); // um gateway específico
```

A configuração vai para `$_CONFIG['stripe']`, que também pode ser preenchido à mão. `stripe_obter_credenciais()` devolve `false` sem `secret_key`; `stripe_info()` resume o estado; `stripe_testar_conexao()` chama `GET /v1/balance`. `stripe_is_modo_gateway()`, `stripe_obter_gateway_id()` e `stripe_obter_gateway_dados()` informam qual gateway foi carregado.

> [!NOTE]
> As chaves ficam em texto claro na tabela `gateways_pagamentos`.

## Requisição

`stripe_requisicao(['endpoint' => '/v1/…', 'method' => 'GET'|'POST'|'DELETE', 'data' => [...], 'idempotency_key' => …])` devolve `['http_code', 'data']` ou `false` (sem credencial ou erro de rede). **Erro da API (4xx/5xx) não é `false`**: confira `http_code`. Erros vão para `gestor/logs/stripe-<data>.log` via [log.php](log.md), sem a chave.

Valores: `stripe_valor_menor_unidade(19.9, 'BRL')` → `1990`; `stripe_valor_decimal()` faz o inverso; `stripe_moeda_sem_decimais()` cobre JPY, KRW, VND, CLP e PYG.

## Operações

| Área | Funções |
|---|---|
| Clientes | `stripe_obter_ou_criar_cliente(['email', 'nome', 'metadata'])`: procura pelo e-mail antes de criar |
| Pagamento único | `stripe_criar_payment_intent()`, `stripe_consultar_payment_intent()`, `stripe_consultar_setup_intent()` |
| Assinaturas | `stripe_criar_assinatura()` (nasce `default_incomplete` e devolve o `client_secret` da primeira fatura, ou `trialing` com `trial_period_days`), `stripe_consultar_assinatura()` (com `expand` opcional), `stripe_definir_metodo_padrao_assinatura()`, `stripe_trocar_preco_assinatura()` (upgrade/downgrade com *proration*, padrão `always_invoice`; corpo em `stripe_troca_preco_payload()`), `stripe_suspender_assinatura()` (`pause_collection`), `stripe_ativar_assinatura()` (desfaz pausa ou cancelamento agendado), `stripe_cancelar_assinatura()` |
| Reembolso | `stripe_reembolsar()` |
| Catálogo | `stripe_criar_produto()`, `stripe_atualizar_produto()` (merge parcial; corpo em `stripe_produto_payload()`), `stripe_consultar_produto()`, `stripe_listar_produtos()`, `stripe_arquivar_produto()`; `stripe_criar_preco()`, `stripe_consultar_preco()`, `stripe_listar_precos()`, `stripe_arquivar_preco()` (um preço é imutável no Stripe: mudar valor é criar outro) |
| Apoio | `stripe_traduzir_status()` (status → texto em português), `stripe_log_registro()` |

## Webhook

`stripe_validar_webhook(['payload' => $corpoCru, 'signature_header' => $cabecalho, 'tolerancia' => 300])` confere o cabeçalho `Stripe-Signature` (HMAC-SHA256 de `t.payload` com o `webhook_secret`, comparação em tempo constante, janela de 300 s) e devolve o evento decodificado ou `false`.

O endpoint é `/_gateways/stripe/webhook` (controlador `plataforma-gateways`), que valida com o gateway padrão e dispara o hook `stripe.webhook`.

> [!WARNING]
> Na forma modular, `/_gateways/<modulo>/stripe/webhook`, o controlador **não valida** a assinatura: repassa ao hook do módulo com `needs_validation => true`, o corpo cru e o cabeçalho, e responde sucesso ao Stripe. O módulo que recebe precisa chamar `stripe_validar_webhook()` com o seu gateway antes de confiar no evento.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/stripe.php` por `c2f docs:extract` — 37 funções. Não edite dentro deste bloco.

- `stripe_log_registro($logData = [])` — [linha 20](../../../../../gestor/bibliotecas/stripe.php#L20)
- `stripe_gateways_pagamentos_configurar(array|false $params = false): bool` — [linha 31](../../../../../gestor/bibliotecas/stripe.php#L31)
  Configura a biblioteca a partir de um registro da tabela `gateways_pagamentos`.
  Parâmetros:
  - `$params`: ['id' => id do gateway] ou vazio para o gateway padrão do tipo stripe.
- `stripe_is_modo_gateway()` — [linha 87](../../../../../gestor/bibliotecas/stripe.php#L87)
- `stripe_obter_gateway_id()` — [linha 92](../../../../../gestor/bibliotecas/stripe.php#L92)
- `stripe_obter_gateway_dados()` — [linha 97](../../../../../gestor/bibliotecas/stripe.php#L97)
- `stripe_obter_credenciais(): array|false` — [linha 105](../../../../../gestor/bibliotecas/stripe.php#L105)
  Retorno: ['secret_key','publishable_key','webhook_secret','currency','mode'] ou false.
- `stripe_requisicao(array|false $params = false): array|false` — [linha 134](../../../../../gestor/bibliotecas/stripe.php#L134)
  Requisição HTTP à API do Stripe.
  Parâmetros:
  - `$params['endpoint']`: Ex.: '/v1/payment_intents' (obrigatório)
  - `$params['method']`: GET|POST|DELETE (padrão GET)
  - `$params['data']`: Corpo (POST) ou query string (GET)
  - `$params['idempotency_key']`: Chave de idempotência (opcional)
  Retorno: ['http_code' => int, 'data' => array] ou false
- `stripe_moeda_sem_decimais($moeda)` — [linha 204](../../../../../gestor/bibliotecas/stripe.php#L204)
  Moedas sem casas decimais na API do Stripe (subset relevante).
- `stripe_valor_menor_unidade($valor, $moeda = 'BRL')` — [linha 209](../../../../../gestor/bibliotecas/stripe.php#L209)
  Converte valor decimal para a menor unidade da moeda (centavos em BRL).
- `stripe_valor_decimal($valor, $moeda = 'BRL')` — [linha 215](../../../../../gestor/bibliotecas/stripe.php#L215)
  Converte da menor unidade para decimal.
- `stripe_obter_ou_criar_cliente(array $params = Array()): array|false` — [linha 230](../../../../../gestor/bibliotecas/stripe.php#L230)
  Localiza um cliente pelo e-mail ou cria um novo. Evita duplicar customers a cada checkout.
  Parâmetros:
  - `$params`: ['email' => string, 'nome' => string opcional, 'metadata' => array opcional]
  Retorno: Dados do customer ou false.
- `stripe_criar_payment_intent(array $params = Array()): array|false` — [linha 264](../../../../../gestor/bibliotecas/stripe.php#L264)
  Cria um PaymentIntent para pagamento único no Payment Element.
  Parâmetros:
  - `$params`: ['valor' => decimal, 'moeda' => 'BRL', 'customer_id' => opc,
  Retorno: ['id','client_secret','status'] ou false.
- `stripe_consultar_payment_intent($params = Array())` — [linha 292](../../../../../gestor/bibliotecas/stripe.php#L292)
- `stripe_consultar_setup_intent(array $params = Array()): array|false` — [linha 309](../../../../../gestor/bibliotecas/stripe.php#L309)
  Consulta um SetupIntent (`seti_...`).
  Parâmetros:
  - `$params`: ['setup_intent_id' => obrig]
- `stripe_criar_assinatura(array $params = Array()): array|false` — [linha 335](../../../../../gestor/bibliotecas/stripe.php#L335)
  Cria uma assinatura em `default_incomplete` para confirmação no Payment Element.
  Parâmetros:
  - `$params`: ['customer_id' => obrig, 'price_id' => obrig, 'referencia' => opc,
  Retorno: ['id','status','client_secret','secret_type' => 'payment'|'setup','subscription_data'] ou false.
- `stripe_consultar_assinatura(array $params = Array()): array|false` — [linha 401](../../../../../gestor/bibliotecas/stripe.php#L401)
  Consulta uma assinatura.
  Parâmetros:
  - `$params`: ['subscription_id' => obrig, 'expand' => array opcional]
- `stripe_definir_metodo_padrao_assinatura(array $params = Array()): array|false` — [linha 428](../../../../../gestor/bibliotecas/stripe.php#L428)
  Fixa o método de pagamento padrão de uma assinatura.
  Parâmetros:
  - `$params`: ['subscription_id' => obrig, 'payment_method_id' => obrig]
  Retorno: Assinatura atualizada ou false.
- `stripe_cancelar_assinatura($params = Array())` — [linha 441](../../../../../gestor/bibliotecas/stripe.php#L441)
  Cancela definitivamente uma assinatura.
- `stripe_troca_preco_payload(array $sub, array $params = Array()): array|false` — [linha 468](../../../../../gestor/bibliotecas/stripe.php#L468)
  Monta o corpo da troca de preço de uma assinatura, resolvendo `trial_end` contra a assinatura em curso. Separada da requisição para ser verificável sem rede.
  Parâmetros:
  - `$sub`: Assinatura consultada (precisa de items.data[0].id).
  - `$params`: Mesmos parâmetros de stripe_trocar_preco_assinatura.
  Retorno: Corpo da requisição ou false quando a assinatura não tem item de preço.
- `stripe_trocar_preco_assinatura(array $params = Array()): array|false` — [linha 503](../../../../../gestor/bibliotecas/stripe.php#L503)
  Troca o preço de uma assinatura ativa (upgrade/downgrade) com proration nativa.
  Parâmetros:
  - `$params`: ['subscription_id' => obrig, 'price_id' => obrig,
  Retorno: Assinatura atualizada ou false.
- `stripe_suspender_assinatura($params = Array())` — [linha 522](../../../../../gestor/bibliotecas/stripe.php#L522)
  Suspende a cobrança (pause_collection) mantendo a assinatura viva.
- `stripe_ativar_assinatura($params = Array())` — [linha 541](../../../../../gestor/bibliotecas/stripe.php#L541)
  Reativa uma assinatura pausada ou com cancelamento agendado. Não ressuscita uma assinatura que já alcançou o estado terminal `canceled`.
- `stripe_reembolsar(array $params = Array()): array|false` — [linha 577](../../../../../gestor/bibliotecas/stripe.php#L577)
  Reembolsa um pagamento.
  Parâmetros:
  - `$params`: ['payment_intent_id' => ou 'charge_id', 'valor' => decimal opc (total se ausente), 'moeda' => opc]
- `stripe_criar_produto(array $params = Array()): array|false` — [linha 603](../../../../../gestor/bibliotecas/stripe.php#L603)
  Cria um produto no catálogo (`POST /v1/products`).
  Parâmetros:
  - `$params`: ['nome' => obrig, 'descricao' => opc, 'imagens' => array de URLs opc,
  Retorno: Produto criado (`prod_...`) ou false.
- `stripe_produto_payload(array $params = Array()): array` — [linha 635](../../../../../gestor/bibliotecas/stripe.php#L635)
  Monta o corpo de uma atualização de produto.
  Parâmetros:
  - `$params`: Mesmos campos de `stripe_atualizar_produto`.
  Retorno: Corpo pronto para `http_build_query`.
- `stripe_atualizar_produto(array $params = Array()): array|false` — [linha 662](../../../../../gestor/bibliotecas/stripe.php#L662)
  Atualiza um produto existente (`POST /v1/products/{id}`).
  Parâmetros:
  - `$params`: ['product_id' => obrig, 'nome' => opc, 'descricao' => opc,
  Retorno: Produto atualizado ou false.
- `stripe_consultar_produto($params = Array())` — [linha 677](../../../../../gestor/bibliotecas/stripe.php#L677)
- `stripe_listar_produtos(array $params = Array()): array|false` — [linha 690](../../../../../gestor/bibliotecas/stripe.php#L690)
  Lista produtos da conta (`GET /v1/products`).
  Parâmetros:
  - `$params`: ['limite' => 1..100 (padrão 100), 'ativo' => bool opc, 'starting_after' => opc]
  Retorno: ['data' => array de produtos, 'has_more' => bool] ou false.
- `stripe_arquivar_produto($params = Array())` — [linha 705](../../../../../gestor/bibliotecas/stripe.php#L705)
  Arquiva um produto (o Stripe não permite exclusão de produto com preços associados).
- `stripe_criar_preco(array $params = Array()): array|false` — [linha 722](../../../../../gestor/bibliotecas/stripe.php#L722)
  Cria um preço (`POST /v1/prices`).
  Parâmetros:
  - `$params`: ['product_id' => obrig, 'valor' => decimal obrig, 'moeda' => 'BRL',
  Retorno: Preço criado (`price_...`) ou false.
- `stripe_consultar_preco($params = Array())` — [linha 753](../../../../../gestor/bibliotecas/stripe.php#L753)
- `stripe_listar_precos(array $params = Array()): array|false` — [linha 771](../../../../../gestor/bibliotecas/stripe.php#L771)
  Lista preços da conta (`GET /v1/prices`).
  Parâmetros:
  - `$params`: ['product_id' => opc (filtra por produto), 'limite' => 1..100,
  Retorno: ['data' => array de preços, 'has_more' => bool] ou false.
- `stripe_arquivar_preco($params = Array())` — [linha 788](../../../../../gestor/bibliotecas/stripe.php#L788)
  Arquiva um preço (`active=false`). Preço em uso por assinatura viva continua cobrando.
- `stripe_validar_webhook(array $params = Array()): array|false` — [linha 808](../../../../../gestor/bibliotecas/stripe.php#L808)
  Valida a assinatura de um webhook (`Stripe-Signature`) por HMAC-SHA256.
  Parâmetros:
  - `$params`: ['payload' => corpo cru, 'signature_header' => header, 'tolerancia' => seg (300)]
  Retorno: Evento decodificado ou false.
- `stripe_testar_conexao()` — [linha 848](../../../../../gestor/bibliotecas/stripe.php#L848)
  Testa a conexão com as credenciais atuais (GET /v1/balance).
- `stripe_traduzir_status($status)` — [linha 853](../../../../../gestor/bibliotecas/stripe.php#L853)
- `stripe_info()` — [linha 871](../../../../../gestor/bibliotecas/stripe.php#L871)

<!-- c2f:extract:end -->
