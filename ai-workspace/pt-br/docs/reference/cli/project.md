---
title: "CLI: Comandos project"
description: "Referência dos comandos project registrados no console c2f."
section: reference
sources:
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
verified_at: e5b61f8e
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
Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto]

Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked local=true receives remote confirmation automatically; production remains explicit.
```

## `project:update-system`

Código: `cli/src/Commands/ProjectUpdateSystemCommand.php`.

```text
Usage: c2f project:update-system [projectID] [--insecure]

Runs update-system.sh [--project projectID]. The --insecure flag is restricted to self-signed TLS endpoints in local development.
```

## Comportamento do pipeline

`project:update-all` passa o id do projeto por argumento ou `--project`. Primeiro sincroniza Core, banco, recursos, arquivos e banco novamente. Depois reconstrói CSS, minifica JS e publica assets. Falhas nas três últimas etapas viram avisos e o comando ainda pode retornar sucesso; confira os relatórios de CSS e assets. Projetos SSH marcados `local=true` recebem confirmação remota automática; outros exigem `--confirmar-remoto`. `project:deploy` chama o script Bash de upload para `/_api/project/update`; `project:recover` baixa dados por `/_api/project/recover`. Veja o [guia de deploy](../../guides/deploy-a-project.md).
