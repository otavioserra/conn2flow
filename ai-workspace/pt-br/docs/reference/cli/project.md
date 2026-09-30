---
title: "CLI: Comandos project"
description: "Referência dos comandos project registrados no console c2f."
section: reference
sources:
  - ai-workspace/en/scripts/projects/synchronize-project.sh
  - cli/src/Console/Application.php
  - cli/src/Commands/ProjectDeployCommand.php
  - cli/src/Commands/ProjectRecoverCommand.php
  - cli/src/Commands/ProjectSyncCoreCommand.php
  - cli/src/Commands/ProjectSyncDbCommand.php
  - cli/src/Commands/ProjectSyncFilesCommand.php
  - cli/src/Commands/ProjectSyncHooksCommand.php
  - cli/src/Commands/ProjectSyncResourcesCommand.php
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectUpdateSystemCommand.php
verified_at: b371b53d
---

# CLI: Comandos project

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `project:deploy`

Código: `cli/src/Commands/ProjectDeployCommand.php`.

```text
Usage: c2f project:deploy [projectID] [--contents=Sim|Não]

Deploys current project or specified project ID to the remote server.
```

## `project:recover`

Código: `cli/src/Commands/ProjectRecoverCommand.php`.

```text
Usage: c2f project:recover [projectID] [--contents]

Downloads and recovers remote project data into the local environment.
```

## `project:sync-core`

Código: `cli/src/Commands/ProjectSyncCoreCommand.php`.

```text
Usage: c2f project:sync-core <projectID>

Runs sync-core-to-project.sh --project <projectID>
```

## `project:sync-db`

Código: `cli/src/Commands/ProjectSyncDbCommand.php`.

```text
Usage: c2f project:sync-db <projectID>

Runs updates-manager-database.sh --project <projectID>
```

## `project:sync-files`

Código: `cli/src/Commands/ProjectSyncFilesCommand.php`.

```text
Usage: c2f project:sync-files <projectID> [--contents=Sim|Não]

Runs synchronize-project.sh
```

## `project:sync-hooks`

Código: `cli/src/Commands/ProjectSyncHooksCommand.php`.

```text
Usage: c2f project:sync-hooks <projectID>

Runs the project database transport in hooks-only mode.
```

## `project:sync-resources`

Código: `cli/src/Commands/ProjectSyncResourcesCommand.php`.

```text
Usage: c2f project:sync-resources [projectID]

Runs update-resource-data.sh [--project projectID]
```

## `project:update-all`

Código: `cli/src/Commands/ProjectUpdateAllCommand.php`.

```text
Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto] [--no-wait] [--lock-wait=<minutes>]

Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked local=true receives remote confirmation automatically; production remains explicit.
req-197: checks migrations first (db:check-migrations) and runs under a deploy lock per target (SSH host+path or local folder, in GIT/.c2f-deploy-locks/). A second pipeline waits for the lock (default 30 min); --no-wait fails at once.
```

## `project:update-system`

Código: `cli/src/Commands/ProjectUpdateSystemCommand.php`.

```text
Usage: c2f project:update-system [projectID] [--insecure]

Runs update-system.sh [--project projectID]. The --insecure flag is restricted to self-signed TLS endpoints in local development.
```

## Comportamento do pipeline

`project:update-all` passa o id do projeto por argumento ou `--project`. Primeiro sincroniza Core, banco, recursos, arquivos e banco novamente. Depois reconstrói CSS, minifica JS e publica assets. Antes da etapa de banco, e de novo depois do envio dos arquivos, remove no destino as migrações obsoletas do projeto (`synchronize-project.sh --migrations-only`, req-194): o rsync não apaga nada e a pasta do destino também tem as migrações do core, então a limpeza é por dono e só registra choques. Falhas nas três últimas etapas viram avisos e o comando ainda pode retornar sucesso; confira os relatórios de CSS e assets. Projetos SSH marcados `local=true` recebem confirmação remota automática; outros exigem `--confirmar-remoto`. `project:deploy` chama o script Bash de upload para `/_api/project/update`; `project:recover` baixa dados por `/_api/project/recover`. Veja o [guia de deploy](../../guides/deploy-a-project.md).

**Trava de deploy (req-197).** Antes da etapa 1, o pipeline roda `db:check-migrations` do projeto (versão ou classe duplicada interrompe tudo, sem enviar nada). Em seguida, pega a trava do **destino**: o host e o caminho do SSH, ou a pasta local. A trava fica em `GIT/.c2f-deploy-locks/`, pasta comum a todos os clones e worktrees do core, a mesma no Windows e no WSL (`C2F_LOCK_DIR` sobrescreve). Um segundo pipeline para o mesmo destino espera a trava liberar, avisando a cada minuto quem está com ela, até `--lock-wait` minutos (padrão 30); `--no-wait` falha na hora. A trava é liberada no fim, também quando uma etapa falha. A atualização do sistema e o deploy por API usam a trava do próprio ambiente (`temp/deploy.lock`); com ela viva, recusam (API: HTTP 409; atualização pelo CLI: código de saída 8).
