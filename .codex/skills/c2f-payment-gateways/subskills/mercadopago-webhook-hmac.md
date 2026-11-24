# Subskill — Reenvio/Teste de Webhook Assinado do Mercado Pago (HMAC-SHA256)

> Skill-mãe: `c2f-payment-gateways` (§5.4). Leia antes de reenviar, simular ou depurar um webhook assinado do Mercado Pago no ambiente de teste.

Fonte da verdade no Core: `mercadopago_validar_webhook()` em `gestor/bibliotecas/mercadopago.php`, acionada por `plataforma_gateways_webhook_universal()` em `gestor/controladores/plataforma-gateways/plataforma-gateways.php`. Ali o Core valida a assinatura do Mercado Pago também nas rotas modulares, então o módulo não revalida a assinatura. Conferência de 2026-11-24 na branch `feat/req-305-mercadopago-orders-api` do Core (REQ-305), ainda não integrada à `main`: reconfira o código após a integração.

1. **Rota**: `POST /_gateways/mercadopago/webhook` (broadcast) ou `POST /_gateways/<modulo-id>/mercadopago/webhook` (módulo alvo). O Core aceita corpo JSON ou apenas a query `?data.id=<id>&type=<tipo>`. O PHP entrega `data.id` como `$_GET['data_id']`.
2. **Cabeçalhos obrigatórios**: `x-signature: ts=<timestamp>,v1=<hmac-hex>` e `x-request-id: <uuid>`. Sem `x-signature`, `ts` numérico ou `v1`, a validação falha.
3. **Manifest**: `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`. Partes ausentes saem do manifest. Um `data.id` alfanumérico entra em minúsculas no manifest e mantém a grafia original na consulta.
4. **HMAC**: `hash_hmac('sha256', $manifest, <webhook_secret>)` em hex minúsculo. O segredo é o `webhook_secret` do gateway Mercado Pago ativo no ambiente de teste. Leia-o da configuração local sem imprimi-lo nem registrá-lo, e use IDs fictícios ou de sandbox, nunca IDs reais de pedidos.
5. **Envio e retorno**:

```bash
TS=$(date +%s); RID=$(uuidgen); ID="<id-sandbox>"
SIG=$(printf 'id:%s;request-id:%s;ts:%s;' "$(echo "$ID" | tr 'A-Z' 'a-z')" "$RID" "$TS" \
  | openssl dgst -sha256 -hmac "$MP_WEBHOOK_SECRET" | sed 's/^.* //')
curl -i -X POST "https://<host-de-teste>/_gateways/mercadopago/webhook?data.id=$ID&type=payment" \
  -H "x-signature: ts=$TS,v1=$SIG" -H "x-request-id: $RID" -H "Content-Type: application/json" \
  -d "{\"type\":\"payment\",\"data\":{\"id\":\"$ID\"}}"
```

O `tr` só se aplica a `data.id` alfanumérico, conforme o passo 3.

- **HTTP 200** (`processed: true`): assinatura aceita e evento tratado por um módulo.
- **401** `Invalid webhook signature`: manifest, segredo ou cabeçalhos incorretos, ou nenhum gateway Mercado Pago ativo.
- **400** `Invalid Order ID` ou **503** `Order temporarily unavailable`: assinatura válida, mas a Order não foi confirmada pela API autenticada.
- **503** `Webhook processing incomplete`: nenhum módulo devolveu `processed`. O Mercado Pago reenviaria o evento.
