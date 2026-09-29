# BATCH-196: Cupom de desconto na criação de assinatura do Stripe (req-192)

Execução da [req-192](../human-requests/req-192.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/stripe.php` | `stripe_criar_assinatura()` com `coupon_id` → `discounts` |
| `tests/Unit/PHP/StripeAssinaturaCupomTest.php` | Com e sem cupom |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/stripe.md` | Parâmetro descrito |

## Validação

- `StripeAssinaturaCupomTest`: 2 testes.
- Lab (`conn2flow-site-local`, REQ-069 do site): plano `starter` com cupom de 10% → "−R$ 9,90 na primeira cobrança", pago com cartão de teste, "Assinatura confirmada!", uso do cupom confirmado.
