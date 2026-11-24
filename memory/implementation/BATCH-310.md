---
id: "BATCH-310"
title: "REQ-301 — Biblioteca Mercado Pago, Interface Plug-and-Play de Gateways e Ponte Universal em plataforma-gateways"
status: "implemented-pending-review"
date: "2026-11-10"
author: "architect"
target_repo: "conn2flow"
request: "REQ-301"
topology: "dupla"
autonomy: "autonomo_monitorado"
summary_short: "Cria mercadopago.php e pagamentos-gateways.php no Core e desacopla plataforma-gateways.php para operar com envelope canônico plug-and-play."
summary_medium: "Implementa o cliente REST do Mercado Pago, a interface unificada de gateways (Stripe, Mercado Pago, PayPal) com suporte a prioridades e capacidades (parcelamento, modo de renderização), e a ponte universal de webhooks em plataforma-gateways.php."
---

# BATCH-310 — Arquitetura Plug-and-Play de Gateways no Core (REQ-301)

* **Repositório Alvo:** `conn2flow` (`C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`)
* **Requisição:** [REQ-301](../human-requests/req-301.md)
* **Coordenado com (`conn2flow-site`):** [REQ-149](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site/memory/human-requests/ecommerce/req-149-mercado-pago-parcelamento-caixa-e-planos.md) e [REQ-151](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-site/memory/human-requests/subscriptions/req-151-arquitetura-plug-and-play-gateways-assinaturas-e-webhooks.md)
* **Modo de Autonomia:** `autonomo_monitorado`

---

## Live Todo List

- [x] **1. Preparação e Leitura de Contratos**
  - [x] Verificar `git branch --show-current` e `git status --short` em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`.
  - [x] Inspecionar `gestor/bibliotecas/stripe.php`, `gestor/bibliotecas/paypal.php` e `gestor/controladores/plataforma-gateways/plataforma-gateways.php`.
- [x] **2. Biblioteca `gestor/bibliotecas/mercadopago.php`**
  - [x] Implementar configuração via tabela `gateways_pagamentos` (`mercadopago_gateways_pagamentos_configurar`, `mercadopago_obter_credenciais`, `mercadopago_obter_gateway_id`, `mercadopago_obter_gateway_dados`).
  - [x] Implementar cliente HTTP `mercadopago_requisicao()` com Bearer Token, `X-Idempotency-Key` em métodos mutáveis e hook de transporte injetável para testes unitários (mesmo padrão de `stripe.php` ou callback global de teste).
  - [x] Implementar `mercadopago_testar_conexao()`, `mercadopago_criar_pagamento()`, `mercadopago_consultar_pagamento()`, `mercadopago_reembolsar()`, `mercadopago_validar_webhook()` e `mercadopago_traduzir_status()`.
- [x] **3. Interface Plug-and-Play `gestor/bibliotecas/pagamentos-gateways.php`**
  - [x] Implementar catálogo de drivers (`stripe`, `mercadopago`, `paypal`) extensível por hook (`pagamentos`, `gateways.drivers`).
  - [x] Implementar `pagamentos_gateways_listar_ativos($filtros)` respeitando `prioridade ASC`, `padrao DESC`, `id_gateways_pagamentos ASC` e filtros opcionais (`supports_installments`, `supports_one_time`, `supports_recurring`, `currency`, `tipo`).
  - [x] Implementar `pagamentos_gateway_capacidades($gatewayRow)` lendo `configuracoes` JSON (`installments`, `render_mode` = `'auto'|'button_first'`) e regras de negócio (Stripe BR sem parcelamento; Mercado Pago com parcelamento configurável; PayPal com parcelamento configurável).
  - [x] Implementar fachada unificada: `pagamentos_gateway_configurar()`, `pagamentos_gateway_testar()`, `pagamentos_gateway_iniciar_cobranca()`, `pagamentos_gateway_sincronizar()`, `pagamentos_gateway_reembolsar()`, `pagamentos_gateway_dashboard_url()` e `pagamentos_gateway_validar_e_normalizar_webhook()`.
- [x] **4. Ponte Universal em `gestor/controladores/plataforma-gateways/plataforma-gateways.php`**
  - [x] Substituir a lista estática `$known_gateways` por consulta aos drivers registrados em `pagamentos-gateways.php` (incluindo `mercadopago`).
  - [x] Implementar `plataforma_gateways_mercadopago_webhook()` (ou despachante unificado) suportando modo modular (`/_gateways/{modulo}/mercadopago/webhook`) e modo global (`/_gateways/mercadopago/webhook`), aceitando também notificações onde o id vem na query string (`?data.id=...&type=payment`) além do corpo JSON.
  - [x] Anexar o envelope `canonical` em `$data['canonical']` para todos os gateways antes de acionar `{modulo}_plataforma_gateways()`, mantendo 100% dos campos legados intactos.
- [x] **5. Testes Automatizados e Validação**
  - [x] Criar `tests/php/unit/MercadoPagoLibraryTest.php` e `tests/php/unit/PagamentosGatewaysInterfaceTest.php`.
  - [x] Executar PHPUnit e Vitest do Core; registrar evidências neste lote.

---

## Rodada 1 — Execução (executor, 2026-11-10)

**Escopo:** Frentes 1–3 da REQ-301 no Core; categoria A (backend PHP, sem HTML/CSS/recursos). Sem deploy: nada a sincronizar no Lab para validar bibliotecas e controlador por testes.

**Arquivos**

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/mercadopago.php` | Novo. Configuração por id/linha/maior prioridade, cliente REST com Bearer, `X-Idempotency-Key` automática em POST, transporte injetável (`$_GESTOR['biblioteca-mercadopago']['transporte']`), pagamento cartão/Pix/Boleto, consulta, reembolso total/parcial, HMAC `x-signature`, status canônico (inclui `partially_refunded`), `mercadopago_ultimo_erro()`. |
| `gestor/bibliotecas/pagamentos-gateways.php` | Novo. Drivers `stripe`/`mercadopago`/`paypal` + filtro `pagamentos`/`gateways.drivers`; listagem ordenada com fallback sem `prioridade`; capacidades; fachada configurar/testar/iniciar/sincronizar (prova de posse valor/moeda/referência)/reembolsar/dashboard; envelope canônico de webhook com escolha do gateway pela assinatura e restauração do estado global. |
| `gestor/controladores/plataforma-gateways/plataforma-gateways.php` | v2.1.0. Gateways conhecidos vindos dos drivers; `plataforma_gateways_resolver_rota()`; handlers universais de webhook (corpo JSON ou só query) e return/cancel/pending; `canonical` injetado nos hooks de Stripe e PayPal (modular e legacy) com campos legados intactos; resultado do hook capturado para todo webhook; guarda `PLATAFORMA_GATEWAYS_SEM_INICIAR` para testes. |
| `gestor/config.php` | Registro das bibliotecas `mercadopago` e `pagamentos-gateways`. |
| `tests/Fixtures/PagamentosGatewaysHarness.php`, `tests/Unit/PHP/MercadoPagoLibraryTest.php`, `tests/Unit/PHP/PagamentosGatewaysInterfaceTest.php` | Novos. Testes em subprocesso (padrão `ApiCrudHarness`); o lote citava `tests/php/unit/`, mas a suíte do repositório fica em `tests/Unit/PHP/`. |

**Comandos e resultados**

- `vendor/bin/phpunit tests/Unit/PHP/MercadoPagoLibraryTest.php tests/Unit/PHP/PagamentosGatewaysInterfaceTest.php` → **OK (22 testes, 262 asserções)** no Windows e no `ssh lab` (PHP 8.5.12).
- `ssh lab … php vendor/bin/phpunit --no-coverage` → 2022 testes, **21 falhas preexistentes** (Dashboard Req247/251/258, Manutencao Req210: arquivos CRLF não tocados neste lote, baseline do BATCH-306); nenhuma falha nova.
- `npx vitest run` → **716/716**.
- Smoke real somente leitura no sandbox do Mercado Pago (credenciais de usuário de teste fornecidas pelo operador, só por variável de ambiente, fora do repositório): `mercadopago_testar_conexao()` e `pagamentos_gateway_testar()` → `ok`, HTTP 200; `GET /v1/payments/1` → `not_found` (404) mapeado; token ausente dos logs.
- Defeito pego pelo teste e corrigido: corpo vazio de POST saía `[]` em vez de `{}`.

**Pendências / para o Macro-Arquiteto**

- Migration da coluna `gateways_pagamentos.prioridade` pertence ao `conn2flow-site` (REQ-149/151); o Core já ordena com e sem ela.
- Webhook do Mercado Pago precisa ser cadastrado no painel (URL `/_gateways/{modulo}/mercadopago/webhook`, evento Pagamentos) e a assinatura secreta gravada em `webhook_secret`: depende do operador.
- O handler universal exige `processed` do módulo (semântica do Stripe): módulo sem retorno → 503 e reentrega.
- Sem commit (Nível 1/monitorado sem pedido de commit) e sem deploy.

## Rodada 2 — Integração e Lab (executor, 2026-11-10)

- **`formulario.php` (REQ-148 do site) integrado a este lote** por pedido do operador: `formulario_email_habilitado()` e a guarda antes do envio, com `tests/Unit/PHP/FormsEmailToggleSiteReq148Test.php` (3/3). Revisão do executor aprovada: a guarda vem depois do INSERT, grava `email_status = email-disabled` no mesmo formato do caminho normal e devolve a mesma resposta; só o booleano `false` desliga.
- **Core publicado no Lab `conn2flow.local`** junto com o BATCH-141 do site (lote misto: `php cli/c2f.php project:update-all conn2flow-site-local`, 8/8, conferência por hash 856 arquivos). `GET https://conn2flow-dev.conn2flow.com/_gateways/` responde v2.1.0; webhook do Mercado Pago sem assinatura → 401.
- Teste de conexão do Mercado Pago pelo painel do site, via `pagamentos_gateway_testar()`, OK no Lab.

## Rodada 3 — Webhook global do PayPal (executor, 2026-11-10, junto do BATCH-143 do site)

- `plataforma-gateways.php` (modo legacy do PayPal): sem `paypal.default` no `.env`, nada configurava a biblioteca e `paypal_validar_webhook` falhava sempre. Agora, sem `webhook_id` configurado, o Core carrega o gateway PayPal ativo do banco (`paypal_gateways_pagamentos_configurar(['tipo' => 'paypal'])`) antes de validar.
- Lab: `POST https://conn2flow-dev.conn2flow.com/_gateways/paypal/webhook` com assinatura falsa → 401 sem o erro de "Webhook ID não configurado"; testes do Core de gateways, REQ-148 e REQ-265 → 26/26.
