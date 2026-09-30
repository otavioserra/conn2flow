# BATCH-199: Itens avulsos na primeira fatura da assinatura Stripe (req-195)

Execução da [req-195](../human-requests/req-195.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/stripe.php` | `stripe_criar_assinatura()` com `add_invoice_items` → `price_data` (produto, valor em centavos, moeda, quantidade) |
| `tests/Unit/PHP/StripeAssinaturaItensAvulsosTest.php` | 3 testes: itens viram `price_data`, inválidos ignorados, payload intacto sem itens |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/stripe.md` | Parâmetro descrito |

## Ajuste após o E2E (2026-09-30)

Com `add_invoice_items`, o `coupon_id` passa a ir em `items[0].discounts` (item do plano), não em `discounts` da assinatura: no nível da assinatura o Stripe aplicava o cupom também à linha avulsa, que já chega com o desconto do pedido (o E2E do caixa cobrou R$ 148,58 em vez de R$ 151,45). Sem itens avulsos, nada muda. Teste novo: `testComItensOCupomVaiSoNoItemDoPlano`.

## Validação

- `StripeAssinaturaItensAvulsosTest`: 4 testes, 6 asserções; `StripeAssinaturaCupomTest`: 2 testes.
- Integração validada no site pela REQ-071 (Lab, Stripe sandbox).
