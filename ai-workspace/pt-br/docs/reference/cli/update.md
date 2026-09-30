---
title: "CLI: Atualizações"
description: "Comandos c2f da família update: rollback de uma entrega pelo snapshot."
section: reference
order: 175
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/UpdateRollbackCommand.php
verified_at: 22018336
---

# CLI: Atualizações

A família `update:*` tem 1 comando registrado em Application.php.

## `update:rollback`

Código: `cli/src/Commands/UpdateRollbackCommand.php` (req-198 / BATCH-204). Alias: `rollback`.

```text
Usage: c2f update:rollback <projectID> <snapshot> [--com-banco] [--dry-run]
```

Volta uma entrega pelo snapshot que ela deixou no servidor:
- `<snapshot>` é o id da resposta do deploy por API (`api-…`) ou da atualização do sistema (`exec-<id>`, ou só o número);
- `--com-banco` também restaura o dump do banco feito antes da entrega;
- `--dry-run` mostra o que seria executado.

O caminho depende do projeto no `environment.json`:
- `deploy_mode: "ssh"` (Lab): roda `atualizacoes-sistema.php --rollback=<id> --domain=<host>` no servidor, pelo SSH, como o `ssh_run_as`;
- os outros: `POST /_api/project/rollback` com o `api.access_token` do projeto. Token recusado (401): renove e tente de novo.

Veja [atualizações do sistema](../../concepts/system-updates.md) e a [API de projeto](../api/project.md).
