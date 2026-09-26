---
title: "HTTP API"
description: "Core HTTP routes, authentication, response envelopes and endpoint families."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# HTTP API

The front controller sends `/_api/...` to `api_route_request()`. Public `status` and `health` return JSON; other authentication rules belong to each handler. `api_rate_limit_check()` runs before dispatch: it uses `api_rate_limits`, defaulting to 100 requests per 3600 seconds per route/subject. Exceeding it returns 429; an infrastructure failure in rate evaluation returns 503.

`api_response_success()` returns JSON with `status: success`, `data` and `message`. `api_response_error()` returns `status: error`, `message` and optional `details`. `api_get_request_body()` parses JSON and returns 400 for invalid JSON. CORS reads `api.cors-origins` from configuration; OPTIONS preflight is handled before dispatch.

| Family | Route | Contract |
|---|---|---|
| Authentication | [auth](auth.md) | Mobile login, logout, profile and modules |
| OAuth | [oauth](oauth.md) | Access token renewal |
| Projects | [project](project.md) | Upload and recovery |
| System | [system](system.md) | Session based update |
| Modules | [modules](modules.md) | Module hooks |
| Distributed channel | [distributed](distributed.md) | Actions between installations |
| Gateways | [gateways](gateways.md) | Separate `/_gateways/` controller |

`/_api/oauth/` without `refresh` redirects to `oauth-authenticate/`; it does not return an authorization JSON response. See the [OAuth2 library](../libraries/oauth2.md) for token storage.
