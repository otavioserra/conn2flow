---
title: "Gateway endpoints"
description: "Payment webhooks and returns in the separate gateway controller."
section: reference
sources:
  - gestor/controladores/plataforma-gateways/plataforma-gateways.php
verified_at: e5b61f8e
---

# Gateway endpoints

`/_gateways/` is a separate controller from `/_api/`. It accepts legacy `/_gateways/{gateway}/{endpoint}` and module `/_gateways/{module-id}/{gateway}/{endpoint}` routes.

| Gateway | Implemented endpoints |
|---|---|
| PayPal | `webhook`, `return`, `cancel` |
| Stripe | `webhook` |
| PagBank/PagSeguro | returns 404; handler not implemented yet |

PayPal and Stripe webhooks accept POST. In module form, the controller passes payload and headers to the module hook, which must validate the signature with the correct gateway secret. In legacy form, the controller uses gateway libraries to validate. PayPal returns redirect the browser after processing hooks. The controller keeps a file based rate limit per identifier.

> [!WARNING]
> Validate signatures in module flows before any financial effect. IP validation reads `X-Forwarded-For`; configure trusted proxies carefully. See security follow-up in req-181.
