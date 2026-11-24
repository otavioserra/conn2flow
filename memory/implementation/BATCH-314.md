---
id: "BATCH-314"
title: "REQ-305 — Mercado Pago na Orders API v1 (/v1/orders): criação, consulta, reembolso e webhooks type=order"
status: "implemented-pending-review"
date: "2026-11-23"
author: "executor"
target_repo: "conn2flow"
request: "REQ-305"
topology: "triade"
autonomy: "autonomo_monitorado"
summary_short: "mercadopago.php cria Cartão/Pix/Boleto em POST /v1/orders, consulta e reembolsa por ORD... com fallback numérico legado, webhooks type=order validados por HMAC e resolvidos por GET /v1/orders; PHPUnit 29/29; Lab: Pix com QR e cartão no modo parcelado pagos sem 401."
---

# BATCH-314 — REQ-305 (Categoria A: backend PHP, sem HTML/CSS)

- **Requisição:** [REQ-305](../human-requests/req-305.md).
- **Branch:** `feat/req-305-mercadopago-orders-api`, criada a partir de `feat/req-304-comunicacao-fila-e-redirect` (`91017a3e`). **Sem commit** (autônomo monitorado sem pedido de commit).
- **Árvore compartilhada:** o WIP sem commit das REQ-301 (`mercadopago.php`, `pagamentos-gateways.php` e os testes ainda não rastreados), REQ-303 e REQ-304 veio junto para a branch e não foi alterado fora dos hunks abaixo. `plataforma-gateways.php` não foi editado neste lote: a mudança dele é da REQ-301/304.

## Alterações

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/mercadopago.php` | Orders API v1: `mercadopago_ordem_payload()` (`type=online`, `processing_mode=automatic`, valores em string `"1999.00"`, `transactions.payments[0].payment_method` `{id, type, token, installments}`, Pix/Boleto com `expiration_time` ISO 8601), `mercadopago_criar_pagamento()` → `POST /v1/orders`, `mercadopago_consultar_ordem()` → `GET /v1/orders/{id}`, `mercadopago_reembolsar()` → `POST /v1/orders/{id}/refund` (total com corpo vazio; parcial com `transactions:[{id: PAY..., amount}]`, o PAY vem da Order quando não informado), `mercadopago_ordem_normalizar()`, `mercadopago_status_canonico_ordem()` (status + status_detail), `mercadopago_reembolso_resumo()`. Fallback: id numérico continua em `/v1/payments/{id}` e `/refunds`; `configuracoes.mercadopago.api = "payments"` mantém a criação legada para aplicação do tipo "API de Pagamentos". `external_reference` codificado de forma reversível (`:` → `_3A`, `_` → `_5F`; até 64) e decodificado em toda Order devolvida. Erros da Orders API (`errors[]`) lidos e registrados. Correção: o `data.id` do webhook só vai em minúsculas para o manifesto HMAC, não para o payload |
| `gestor/bibliotecas/pagamentos-gateways.php` | Driver Mercado Pago: contrato com `gateway_payment_id` = `gateway_order_id` = `ORD...` (o `PAY...` fica em `raw`), moeda do gateway quando a Order não traz (BR), Pix de `payment_method.qr_code*`, erro pelo `status_detail` do pagamento, reembolso pelo resumo (aceita `gateway_transaction_id`), webhook `type = order` normalizado e resolvido por `GET /v1/orders/{id}` autenticado (estado nunca vem do corpo) |
| `tests/Unit/PHP/MercadoPagoLibraryTest.php` | +5 cenários Orders API (cartão parcelado/Pix/débito/boleto, consulta e reembolso total/parcial, API legada por configuração, status canônicos, referência reversível, webhook `order.*` com HMAC). 14 testes |
| `tests/Unit/PHP/PagamentosGatewaysInterfaceTest.php` | Início de cobrança em Orders; +2 cenários (sincronizar/reembolsar Order com prova de posse; webhook `type=order` pela fachada e pela ponte `plataforma_gateways_envelope_canonico`). 15 testes |
| `memory/validation/evidence-req305/` | `req305-checkout.cjs` (derivado do `req149-checkout.cjs` do site; captura `payment-process` por XHR instrumentado), resultados, pedidos, respostas HTTP e capturas |

## Decisões de execução

1. **Id guardado = Order.** O site grava o `gateway_payment_id` do início da cobrança e chama `mercadopago_consultar_pagamento()`/`mercadopago_status_canonico_pagamento()` direto. Com o `ORD...` nesse campo, consulta, reembolso, "já pago" e webhook funcionam sem mudar o site.
2. **Sem chaves novas nos contratos.** O teste fixa as chaves do envelope e do contrato de sincronização; o `PAY...` fica em `raw.transactions.payments[0].id`.
3. **`metadata` e `notification_url` saem.** Não existem na Orders API (referência oficial lida em 2026-11-23); webhooks vêm do painel, evento "Order (Mercado Pago)".
4. **Referência recusada em vez de truncada.** Truncar quebraria a prova de posse da sincronização (`reference-mismatch`).
5. **Tipo do método inferido.** O Brick do site manda só `payment_method_id` (habilita `creditCard` e `bankTransfer: ['pix']`): `pix` → `bank_transfer`, `bolbradesco`/`pec` → `ticket`, demais → `credit_card`; `payment_type` opcional para `debit_card`.
6. **Reembolso total com corpo vazio** (página de reembolsos); a página de referência marca `transactions` como obrigatório — divergência registrada, não exercitada no Lab (ver pendências).

## Testes

- `vendor/bin/phpunit --filter 'MercadoPagoLibraryTest|PagamentosGatewaysInterfaceTest'` → **OK, 29 testes, 373 asserções** (4 "PHPUnit Deprecations" de configuração, já existentes).
- Suíte completa (antes da correção de `external_reference`): 2075 testes, **22 falhas + 1 erro preexistentes** (Dashboard Req247/251/258, Manutencao Req210, Req203, `CoreHelpersTest` RSA) — mesmo conjunto da linha de base do BATCH-313; nenhuma em gateways.

## Lab (`conn2flow-site-local`, https://conn2flow.local/)

- **Origem do site:** `lab/req-149-156` em `12697394` (integração com Pagamentos). O WIP de `feat/req-156-seo-whatsapp-canonicals` (4 arquivos) foi guardado em stash rotulado, a branch trocada e, ao final, restaurada com o mesmo estado. Os derivados que o pipeline regerou na branch do Lab (só `generated_at`/EOL em 4 `*Data.json`) ficaram no stash `req305-derivados` do site, sem descarte.
- **Antes:** Lab com o checkout da integração (`ecommerce-payments.php` igual a `lab/req-149-156`) e `mercadopago.php` sem `/v1/orders`.
- **Publicação:** `php cli/c2f.php project:update-all conn2flow-site-local --skip-css` (Categoria A; mesmo caminho dos BATCH-310/313 e da rodada final do BATCH-149 do site), 3 rodadas, saída 0, ~45 s cada. Hash no destino igual à origem: `mercadopago.php`, `pagamentos-gateways.php`, `plataforma-gateways.php` e `ecommerce-payments.php` (Pagamentos preservado).
- **Rodada 1:** criação foi a `/v1/orders` (sem 401), mas `400`. O log ainda não lia o formato `errors[]`.
- **Rodada 2:** com o parser novo: `400 property_value — '$.external_reference' - does not match pattern` (`ecommerce:261123-KF3CZA`). Corrigido com a codificação reversível + 1 teste.
- **Rodada 3 — `CENARIOS=cartao,pix node req305-checkout.cjs` → 5/5 OK** ([results](../validation/evidence-req305/results-checkout.json), [respostas](../validation/evidence-req305/respostas-payment-process.json)):
  - Cartão no modo **Pagamento Parcelado**, Visa `4235 6477 2802 5682`, titular `APRO`, `@testuser.com`: `payment-process` HTTP 200 `Ok/paid`, página de sucesso. Pedido `261123-4BU5C5` → `gateway_payment_id = ORDTST01M85340CRM92Z4YQHS9XT19MD`, `paid`/`processing`, transação `payment_paid` com `mercadopago_status=processed`, `status_detail=accredited`, `installments=1`. Mais dois pedidos pagos no mesmo caminho: `261123-Q5GPLN` e `261123-VPPMCQ`.
  - **Pix** à vista: HTTP 200 `Ok/awaiting`, QR Code e copia-e-cola (`000201265800…br.gov.bcb.pix…`) na tela ([1280](../validation/evidence-req305/checkout-1280-pix.png), [390](../validation/evidence-req305/checkout-390-pix.png)). Pedido `261123-8NG9HJ` → `ORDTST01M8534F5BQESK3WSNW06ZZXRD`, `awaiting`.
  - Log `mercadopago-2026-11-23.log`: nenhum erro de API depois de 20:51:14 UTC; os 401 (`/v1/payments`, code 7) param em 20:13:48 UTC, antes da publicação.
  - E-mails transacionais dos pedidos de teste saíram pela fila da REQ-304 (`MAIL_REDIRECT_TO`).

## Pendências

1. **Parcelamento > 1x não comprovado no Lab.** O Brick envia 1x por padrão; o roteiro já escolhe `PARCELAS` (padrão 3), mas a rodada não chegou ao pagamento: o formulário do comprador atingiu a **cota diária de envios** ("Cota diária de envios atingida!") com os pedidos de teste do dia. Não alterei cota nem envios do Lab. Repetir `CENARIOS=cartao node req305-checkout.cjs` após a renovação da cota (ou com a cota do `form-comprador` ajustada pelo operador). O envio de `installments` ao `/v1/orders` está coberto por teste (10x/12x).
2. **Webhook `type=order` real** não foi exercitado: depende do evento "Order (Mercado Pago)" no painel apontando para `/_gateways/mercadopago/webhook` ou `/_gateways/orders/mercadopago/webhook`. Coberto por testes (HMAC, consulta autenticada, envelope).
3. **Reembolso real** não exercitado (evita estornar no sandbox sem pedido); total com corpo vazio vs. `transactions` obrigatório na referência — conferir no primeiro estorno pelo painel do pedido.
4. **Conferência por hash do pipeline:** 350 arquivos `assets/*.js` no destino diferem da origem (ex.: `assets/global/global.js` modificado no Lab às 17:00, antes desta publicação; origem sem mudança desde 2026-10-02). Não é deste lote; o rsync não repõe arquivo mais novo no destino.
5. **`php-error.log` do Lab:** avisos `Trying to access array offset on null` em `gestor.php:2027` às 20:17 UTC (antes da primeira publicação deste lote; arquivo do WIP da REQ-304).
6. Leitura do `.env` do Lab: um `source` do arquivo pelo bash exibiu uma linha (senha SMTP) na saída de erro do terminal da sessão; nada foi gravado em arquivo. As consultas seguintes leram só as variáveis `DB_*`.

## Relatório de dificuldades e aprendizados (REQ-081)

1. **Contexto da Rodada** — `conn2flow` (Core), REQ-305 / BATCH-314, migração de integração de pagamento + validação real no Lab.
2. **Dificuldades e Atritos Enfrentados** — (a) Orders API recusa `external_reference` com `:`, descoberto só no Lab, porque o log não lia o formato `errors[]`; (b) a origem do pipeline é a árvore de trabalho do site, então publicar exigiu trocar a branch do site com WIP alheio (stash rotulado); (c) o Playwright perdia a resposta do `payment-process` quando o checkout navegava (`getResponseBody`), e `page.route` não interceptou o XHR; (d) a cota diária do formulário do comprador esgota com poucas rodadas de E2E.
3. **Lacunas ou Defasagens nas Skills** — `c2f-payment-gateways` não documenta a Orders API (padrões de `external_reference`, ausência de `metadata`/`notification_url`/moeda, formato `errors[]`). `c2f-project-pipeline-and-tasks` não diz como publicar quando o projeto precisa de uma branch de integração diferente da que está em checkout. `c2f-agent-visual-inspection` não cita a cota de formulários nem a captura de XHR antes de navegação.
4. **Solução Validada / Armadilhas Medidas** — codificação reversível `_XX` (teste de ida e volta); XHR instrumentado com `addInitScript` + `exposeFunction` (5/5); stash rotulado com caminhos explícitos + restauração conferida por `git status`; consultas ao banco do Lab lendo só `DB_*` do `.env` (o `source` do `.env` quebra em senhas com `)` e ecoa a linha).
5. **Sugestões de Melhoria para o AI Workspace** — `c2f-payment-gateways`: seção "Mercado Pago Orders API" com payload, status/status_detail, reembolso, webhook e as armadilhas acima. `c2f-project-pipeline-and-tasks`: procedimento "publicar com branch de integração" (stash rotulado → switch → `project:update-all --skip-css` → stash dos derivados → volta). `c2f-shell-and-windows-traps`: nunca `source` de `.env` remoto; ler variáveis com `grep`. Roteiros de E2E de checkout: um modo que reutiliza o link `order+token` para não consumir a cota do formulário.

## Rodada 2 — Pós-revisão (2026-11-23)

- **Webhook Core.** A validação e a fachada usam a mesma leitura de ID e tipo, incluindo `topic` legado. O ID assinado determina o GET autenticado. Os IDs, a referência e o estado canônico do envelope vêm da Order devolvida. Um recurso no corpo é descartado. GET indisponível, sem ID ou com ID divergente devolve `valid=false` e HTTP 503 antes de hooks. O Mercado Pago recebe 401 por assinatura inválida também na rota modular. A sonda sem assinatura retornou 401 no Lab.
- **PHPUnit.** Gateways 29 testes e 376 asserções. BATCH-313, BATCH-314 e REQ-203 juntos com 65 testes e 607 asserções. Suíte completa com 2.078 testes, 21 falhas de base, um erro de base e nove skips. Quatro depreciações da configuração do PHPUnit no filtro. Os testes cobrem IDs distintos na query e no corpo, `topic=order`, tentativa de fornecer `_resource`, GET indisponível e ID divergente na resposta.
- **Lab.** Origem do site em `lab/req-149-156` (`12697394`). A cota do formulário do comprador foi zerada e o bloqueio temporário correspondente expirado, preservando registros de compra. O pipeline do Core teve saída 0. A origem do site ficou na branch de integração e seu WIP anterior ficou no stash rotulado.
- **Cartão 3x nativo.** O Visa configurado no primeiro roteiro só ofereceu 1x. As sondas diagnósticas que enviaram 3 parcelas com Visa foram recusadas. A Mastercard de teste permitiu selecionar 3x no Brick com o e-book de R$ 150,00. O checkout enviou 3 sem reescrever a requisição, devolveu HTTP 200 `paid` e abriu a página de sucesso. Pedido `261123-FDZF3H`. O GET autenticado devolveu HTTP 200 `processed/accredited` e `installments=3`. [Roteiro e evidências](../validation/evidence-req305/pos-revisao-3x-nativo/results-checkout.json) · [GET da Order](../validation/evidence-req305/pos-revisao-3x-nativo/order-get.json)
- **Reembolso integral.** O pedido sandbox `261123-K2R6K7`, de R$ 1.999,00 em 1x, retornou HTTP 201 no POST `/v1/orders/{id}/refund` com corpo `{}`. O reembolso `REF01M855A5F1BYWTJ6S8TDN2H2FT` voltou `processed` e o GET posterior confirmou `refunded`. [Evidência](../validation/evidence-req305/pos-revisao-reembolso-webhook.json)
- **Webhook real no Lab.** O provedor entregou `order.refunded` para a mesma Order e o Core validou `payment.refunded`. O hook Social Apps encerrou a rota legada de broadcast com 404 antes de `orders`. O pedido permaneceu `paid` no site. A observação é uma pendência do repositório do site; a validação no Core chegou ao envelope autenticado. [Evidência](../validation/evidence-req305/pos-revisao-reembolso-webhook.json)
- **Git.** Branch baseada no BATCH-313. O registro das bibliotecas da REQ-301 em `gestor/config.php` ficou no commit `b96c8b03`. Código e testes do BATCH-314 em `7414a04e`. Os arquivos de autoria de outras REQs ficaram fora dos commits.

As evidências da rodada complementam o relatório REQ-081. O seletor de parcelas reconhece o texto e o valor das opções do Brick quando o `select` não declara `name` ou `id`. A prova da aprovação inclui a resposta do checkout e o GET autenticado com `installments=3`.
