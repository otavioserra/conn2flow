---
title: "CLI: Updates"
description: "c2f commands of the update family: roll back a delivery and clashes (list, download, decide)."
section: reference
order: 175
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/UpdateRollbackCommand.php
  - cli/src/Commands/UpdateConflictsCommand.php
  - cli/src/Commands/UpdateResolveCommand.php
  - cli/src/Support/ProjectApiClient.php
verified_at: 22018336
---

# CLI: Updates

The `update:*` family has 3 commands registered in Application.php.

## `update:rollback`

Code: `cli/src/Commands/UpdateRollbackCommand.php` (req-198 / BATCH-204). Alias: `rollback`.

```text
Usage: c2f update:rollback <projectID> <snapshot> [--com-banco] [--dry-run]
```

Rolls back a delivery from the snapshot it left on the server:
- `<snapshot>` is the id from the API deploy response (`api-…`) or from the system update (`exec-<id>`, or just the number);
- `--com-banco` also restores the database dump taken before the delivery;
- `--dry-run` prints what would run.

The path depends on the project in `environment.json`:
- `deploy_mode: "ssh"` (Lab): runs `atualizacoes-sistema.php --rollback=<id> --domain=<host>` on the server over SSH, as `ssh_run_as`;
- the others: `POST /_api/project/rollback` with the project's `api.access_token`. When the token is refused (401), renew it and try again.

## `update:conflicts`

Code: `cli/src/Commands/UpdateConflictsCommand.php` (req-199 / BATCH-205). Alias: `conflicts`.

```text
Usage: c2f update:conflicts <projectID> [clashID] [--todos] [--abrir] [--json]
```

Without an id, lists the project's pending clashes through the API (`--todos` includes resolved ones). With an id, downloads both versions to `temp/conflicts/<project>/<id>/`: `no-ar.<ext>`, `nova.<ext>` and `mesclado.<ext>`, which starts as the live version and is not wiped by a new download. `--abrir` runs `code --diff no-ar nova`.

## `update:resolve`

Code: `cli/src/Commands/UpdateResolveCommand.php`. Alias: `resolve`.

```text
Usage: c2f update:resolve <projectID> <clashID> --acao=sobrescrever|manter|mesclar [--arquivo=PATH] [--local] [--json]
```

Sends the decision. `mesclar` sends the merged file (default: the downloaded `mesclado.<ext>`); `--local` writes the same merge into the local project repository so the next delivery already carries it.

`--json` prints one JSON line for the VS Code extension and other consumers: the list (`{ok, projeto, choques}`), the downloaded detail (`{ok, choque, binario, pasta, arquivos}`) or the resolution (`{ok, id, acao, resolvidos, local}`); errors come as `{ok: false, erro}` with exit code 1.

The three commands that talk to the API use the project's `api.access_token` and, optionally, `api_resolve_ip` in `environment.json`: it resolves the URL host to that IP (test environment without DNS for its own name; the certificate is not checked in that case).

See [system updates](../../concepts/system-updates.md) and the [project API](../api/project.md).
