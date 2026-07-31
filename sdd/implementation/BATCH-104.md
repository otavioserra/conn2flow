# BATCH-104 — Checkout Transparente e Tokenização PayPal

## Origem

- Intake: `sdd/human-requests/req-098.md`
- Decisão: DEC-100
- Status: `complete`
- Data de fechamento: 2026-07-31

## Entrega

- `paypal_gerar_client_token()` com `customer_id` opcional e falha fechada para HTTP diferente de 200.
- `paypal_criar_pedido()` com `payment_source`, `CAPTURE`/`AUTHORIZE`, representação completa e idempotência para chamadas one-step.
- `paypal_criar_assinatura()` com a fonte encaminhada em `subscriber.payment_source`.
- `paypal_processar_pagamento_transparente()` cobrindo captura por `order_id`, pedido com token/vault e assinatura.
- `gestor/assets/paypal/paypal.js` com loader deduplicado do SDK, Card Fields atual, fallback Hosted Fields, validação visual e submit normalizado.
- Biblioteca PHP versionada como `3.1.0` e novas funções publicadas por `paypal_info()`.

## Contrato de segurança

- `client_token` é atributo `data-client-token`; não entra na query string.
- Card Fields mantém PAN/CVV no iframe PayPal. O backend recebe a ordem aprovada (`order_id`) para captura.
- `payment_source` só é retornado pelo helper JS quando veio efetivamente do SDK/callback; nenhum token fictício é derivado de dados mascarados.
- A validação local é estrutural. Elegibilidade, 3DS, bandeira, região e permissões da conta continuam sendo decisões da API PayPal.

## Evidência automatizada

- `php -l gestor/bibliotecas/paypal.php`: OK.
- `node --check gestor/assets/paypal/paypal.js`: OK.
- `git diff --check`: OK.
- Vitest: **90/90**, incluindo `tests/Unit/JS/paypal.test.js` **7/7**.
- PHPUnit: **172/172**, incluindo `PaypalTransparentCheckoutTest` **7/7**; 4 testes preexistentes pulados.

## Homologação operacional pendente

- Renderizar Card Fields com conta Sandbox habilitada para Advanced Credit and Debit Card Payments.
- Confirmar pedido `CAPTURE`, fluxo `AUTHORIZE`, 3DS quando aplicável e captura por `order_id`.
- Confirmar assinatura por cartão apenas em conta/região elegível segundo a API PayPal.
- Repetir em Live somente após validação Sandbox e revisão das credenciais/CSP.

Nenhum commit ou push foi executado.
