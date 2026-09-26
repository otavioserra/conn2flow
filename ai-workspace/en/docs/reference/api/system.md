---
title: "System update API"
description: "Session based administrative update actions."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
verified_at: e5b61f8e
---

# System update API

`POST /_api/system/update` requires a bearer token and `action`. Accepted actions: `start`, `deploy`, `db`, `finalize`, `status` and `cancel`. The last five require the `sid` from the started session. `start` passes options such as `domain`, `tag`, `dry_run`, `only_files`, `only_db`, `backup` and `tables` to the updater; omitted `domain` uses the server name.

The response uses the [JSON envelope](index.md). Invalid session errors return 400; other updater errors return 500. Even `status` uses POST and needs `sid`, so GET is not the implemented progress contract. The endpoint includes the updater controller in the request process.
