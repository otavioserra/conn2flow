---
title: "CLI: Updates"
description: "c2f commands of the update family: roll back a delivery from its snapshot."
section: reference
order: 175
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/UpdateRollbackCommand.php
verified_at: 22018336
---

# CLI: Updates

The `update:*` family has 1 command registered in Application.php.

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

See [system updates](../../concepts/system-updates.md) and the [project API](../api/project.md).
