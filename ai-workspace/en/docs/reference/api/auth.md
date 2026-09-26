---
title: "Authentication API"
description: "Mobile login, logout, current user and available modules."
section: reference
sources:
  - gestor/controladores/api/api-auth.php
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# Authentication API

`api-auth.php` dispatches four `/_api/auth/` subroutes. `login` accepts POST JSON with `usuario` and `senha`. Missing credentials return 400, invalid ones 401, and attempt or session limits 429. On success it issues OAuth2 tokens and user/profile data. Attempt throttling stores a cache file under API logs.

`logout` requires POST and a bearer token. It revokes the current access token and accepts an optional `refresh_token` in the body. `me` (normally GET; the handler does not enforce the method) returns the current token's user and profile. `modules` (normally GET; the handler does not enforce the method) builds groups and modules allowed for that profile, using the handler's resolved language.

Send `Authorization: Bearer <token>` to authenticated routes. Invalid tokens or inactive users return 401. Responses use the [API envelope](index.md). A stored token `scope` is not enforced per route; see the [OAuth2 library](../libraries/oauth2.md). Security follow-up: req-181.
