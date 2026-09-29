# BATCH-199: Itens avulsos na primeira fatura da assinatura Stripe (req-195)

Execução da [req-195](../human-requests/req-195.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/stripe.php` | `stripe_criar_assinatura()` com `add_invoice_items` → `price_data` (produto, valor em centavos, moeda, quantidade) |
| `tests/Unit/PHP/StripeAssinaturaItensAvulsosTest.php` | 3 testes: itens viram `price_data`, inválidos ignorados, payload intacto sem itens |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/stripe.md` | Parâmetro descrito |

## Validação

- `StripeAssinaturaItensAvulsosTest`: 3 testes, 4 asserções; `StripeAssinaturaCupomTest`: 2 testes.
- Integração validada no site pela REQ-071 (Lab, Stripe sandbox).
