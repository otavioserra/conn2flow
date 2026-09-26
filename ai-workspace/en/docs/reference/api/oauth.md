---
title: "OAuth2 API"
description: "HTTP access token renewal endpoint."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/bibliotecas/oauth2.php
verified_at: e5b61f8e
---

# OAuth2 API

`POST /_api/oauth/refresh` accepts JSON with `refresh_token`. A missing field returns 400; an invalid or expired token returns 401. On success, `data` contains `access_token`, `token_type`, `expires_in`, `refresh_token` and `scope`. Renewal calls `oauth2_renovar_token()`.

`/_api/oauth/` without `refresh` redirects to `oauth-authenticate/`. The `scope` field is issued, but `api_authenticate()` does not check scopes per endpoint. Use profile permissions and each handler's contract; see [authentication](auth.md) and [OAuth2](../libraries/oauth2.md).
