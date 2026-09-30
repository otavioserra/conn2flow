---
title: "System update API"
description: "Session based administrative update actions."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/bibliotecas/atualizacoes-execucao.php
verified_at: c8db1447
---

# System update API

`POST /_api/system/update` requires a bearer token and `action`. Accepted actions: `start`, `deploy`, `db`, `finalize`, `status` and `cancel`. The last five require the `sid` from the started session. `start` passes options such as `domain`, `tag`, `dry_run`, `only_files`, `only_db`, `backup` and `tables` to the updater; omitted `domain` uses the server name.

The response uses the [JSON envelope](index.md). Invalid session errors return 400; other updater errors return 500. Even `status` uses POST and needs `sid`, so GET is not the implemented progress contract. The endpoint includes the updater controller in the request process.

## Full update in the background (req-201)

To trigger from outside (host-manager, CLI) without holding a request open for minutes:

- **`action=run`** (JSON body or form): runs in the background the same updater as the CLI, with deploy lock, snapshot, database dump, check and automatic restore. The package comes from GitHub: the latest gestor release or the given `tag`. Options go in `opcoes` (or in the body itself), allow-listed only: `tag`, `only_files`, `only_db`, `no_db`, `dry_run`, `backup`, `no_verify`, `no_health`, `no_rollback`, `health_url`, `health_ip`, `force_all`, `tables`, `logs_retention_days`, `local_artifact`, `debug`. Unknown option or invalid value: 400, with the accepted list. Live deploy lock: 409. Success: **202** with `run`, the run id.
- **`action=run-status`** with `run`: `status` (`running`, `success`, `rolled_back`, `locked`, `error-*`), `codigo`, `snapshot` (`exec-<id>`), `saude`, `rollback`, `erros` and `fim_do_log`. While files are being swapped, the API itself may answer 5xx for a few seconds.
- **`action=runs`**: the 20 most recent runs with their state.
- **`POST /_api/system/rollback`**: `{"snapshot":"exec-<id>","com_banco":false}`, the same rollback as [`/_api/project/rollback`](project.md).

The server needs `proc_open` and a command-line PHP: it looks for `php<version>` and `php` in PHP's binary folder, or the path in `ATUALIZACOES_PHP_CLI` in `.env`. Run files live in `temp/atualizacoes/runs/` (`<run>.json`, `.log`, `.exit`). From the development CLI: [`c2f update:core`](../cli/update.md).
