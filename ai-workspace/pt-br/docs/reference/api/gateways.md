---
title: "Endpoints de gateways"
description: "Webhooks e retornos de pagamento no controlador separado."
section: reference
sources:
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: e5b61f8e
---

# Endpoints de gateways

`/_gateways/` é um controlador separado de `/_api/`. Aceita rotas legadas `/_gateways/{gateway}/{endpoint}` e rotas de módulo `/_gateways/{module-id}/{gateway}/{endpoint}`.

| Gateway | Endpoints implementados |
|---|---|
| PayPal | `webhook`, `return`, `cancel` |
| Stripe | `webhook` |
| PagBank/PagSeguro | retorna 404; handler ainda não implementado |

Webhooks PayPal e Stripe aceitam POST. No formato modular, o controlador passa payload e cabeçalhos ao hook do módulo, que precisa validar a assinatura com o segredo do gateway correto. No legado, o controlador usa as bibliotecas do gateway para validar. Retornos PayPal redirecionam o navegador após processar o hook. O controlador mantém rate limit em arquivo por identificador.

> [!WARNING]
> Valide a assinatura no fluxo do módulo antes de qualquer efeito financeiro. A verificação de IP lê `X-Forwarded-For`; configure o proxy de confiança com cuidado. Consulte o acompanhamento de segurança na req-181.
