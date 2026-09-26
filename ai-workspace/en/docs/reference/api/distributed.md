---
title: "Distributed module API"
description: "HMAC channel between central and distributed installations."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/api/api-module-central.php
  - gestor/controladores/api/api-module-distributed.php
  - gestor/bibliotecas/modulo-distribuido.php
verified_at: e5b61f8e
---

# Distributed module API

The router accepts `/_api/modulo-distribuido/{slug}/{action}` and `/_api/v1/modulo-distribuido/{slug}/{action}`. The handler validates the body's HMAC signature through `X-C2F-Signature` using the configured channel secret, without bearer OAuth for transport between installations.

`signin`, `refresh` and `permissao` run on the central side; `db` and `ping` run on the distributed side. The router also sends `ativar` to the central handler, but its switch does not implement that action and returns 404. `db` executes the packaged operation against the client's local database. Use the channel client that signs messages; do not send ad hoc credentials or SQL. See the [distributed module library](../libraries/modulo-distribuido.md).
