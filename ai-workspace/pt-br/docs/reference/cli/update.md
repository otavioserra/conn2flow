---
title: "CLI: Atualizações"
description: "Comandos c2f da família update: rollback de uma entrega e choques (listar, baixar, decidir)."
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

# CLI: Atualizações

A família `update:*` tem 3 comandos registrados em Application.php.

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

## `update:conflicts`

Código: `cli/src/Commands/UpdateConflictsCommand.php` (req-199 / BATCH-205). Alias: `conflicts`.

```text
Usage: c2f update:conflicts <projectID> [clashID] [--todos] [--abrir]
```

Sem id, lista os choques pendentes do projeto pela API (`--todos` inclui os resolvidos). Com id, baixa as duas versões para `temp/conflicts/<projeto>/<id>/`: `no-ar.<ext>`, `nova.<ext>` e `mesclado.<ext>`, que começa como a versão no ar e não é apagado por um novo download. `--abrir` chama `code --diff no-ar nova`.

## `update:resolve`

Código: `cli/src/Commands/UpdateResolveCommand.php`. Alias: `resolve`.

```text
Usage: c2f update:resolve <projectID> <clashID> --acao=sobrescrever|manter|mesclar [--arquivo=PATH] [--local]
```

Envia a decisão. `mesclar` manda o arquivo mesclado (padrão: o `mesclado.<ext>` baixado); `--local` grava a mesma mescla no repositório local do projeto, para a próxima entrega já levá-la.

Os três comandos que falam com a API usam `api.access_token` do projeto e, opcionalmente, `api_resolve_ip` no `environment.json`: resolve o host da URL para esse IP (ambiente de teste sem DNS para o próprio nome; o certificado não é conferido nesse caso).

Veja [atualizações do sistema](../../concepts/system-updates.md) e a [API de projeto](../api/project.md).
