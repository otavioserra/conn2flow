---
title: "Module API"
description: "Authenticated dispatch to module API hooks."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# Module API

A `/_api/{module-id}/{action}` route that does not match a built-in family reaches `api_handle_modulo()`. The id is normalized to lowercase letters, digits, hyphens and underscores. It requires a valid bearer token. The handler merges query data and JSON body, with body values winning duplicate keys, then calls the target module's `api` hook.

A missing hook returns 404. A result with `erro` uses its supplied code or 500; success uses `dados`, `mensagem` and `code` when present. The general dispatcher does not enforce an HTTP method for each action; modules must validate their own contract. See [hooks](../../concepts/hooks.md).
