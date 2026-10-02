# BATCH-197: Ativação de parcelamento no PaymentIntent Stripe (req-193)

Implementação da extensão compartilhada da biblioteca Stripe solicitada pela REQ-072 do `conn2flow-site`.

**Status**: `completed`.

## Escopo

- `stripe_criar_payment_intent()` aceita a opção explícita de parcelamento de cartão.
- A opção é omitida por padrão e não modifica assinaturas ou outros fluxos.
- Teste unitário cobre o payload legado e o payload com parcelamento habilitado.
- Referência da biblioteca Stripe atualizada em PT-BR e EN.

## Validação

- [x] Teste focado do PaymentIntent: 3 testes, 5 asserções.
- [x] `php -l gestor/bibliotecas/stripe.php` sem erros.
- [x] `git diff --check` nos arquivos do batch sem erros.

O payload legado permanece inalterado quando `installments` está omitido/desabilitado. Homologação comercial do parcelamento pertence ao projeto consumidor e não faz parte deste batch de biblioteca.