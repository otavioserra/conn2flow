---
title: "CLI: Commands project"
description: "Reference for project commands registered in the c2f console."
section: reference
sources:
  - ai-workspace/en/scripts/projects/synchronize-project.sh
  - cli/src/Console/Application.php
  - cli/src/Commands/ProjectDeployCommand.php
  - cli/src/Commands/ProjectRecoverCommand.php
  - cli/src/Commands/ProjectRecoverFilesCommand.php
  - cli/src/Commands/ProjectSyncCoreCommand.php
  - cli/src/Commands/ProjectSyncDbCommand.php
  - cli/src/Commands/ProjectSyncFilesCommand.php
  - cli/src/Commands/ProjectSyncHooksCommand.php
  - cli/src/Commands/ProjectSyncResourcesCommand.php
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectUpdateSystemCommand.php
verified_at: 152dfd72
---

# CLI: Commands project

Syntax from executable help; check the source before running commands with external effects.

## `project:deploy`

Source: `cli/src/Commands/ProjectDeployCommand.php`.

```text
Usage: c2f project:deploy [projectID] [--contents=Sim|Não]

Deploys current project or specified project ID to the remote server.
```

## `project:recover`

Source: `cli/src/Commands/ProjectRecoverCommand.php`.

```text
Usage: c2f project:recover [projectID] [--contents]

Downloads and recovers remote project data into the local environment.
```

## `project:recover-files`

```text
c2f project:recover-files <projectID> [--simular|--aplicar] [--camada=projeto|core|plugin:<id>] [--caminho=PATH --acao=sobrescrever|manter|mesclar --arquivo=PATH] [--json]
```

By default, simulates and downloads differences into `temp/recover-files/<project>/<run>/servidor/`, with `relatorio.json`. `--aplicar` copies project-layer files when the local file still matches the manifest hash. If the local file also changed, it reports the three valid actions; decide one file by repeating with `--aplicar --caminho=<path> --acao=<action>`. `mesclar` requires `--arquivo` with the merged result. Core and plugin files are downloaded for inspection without writing to the local repository. `--json` emits one structured line for tools.

## `project:sync-core`

Source: `cli/src/Commands/ProjectSyncCoreCommand.php`.

```text
Usage: c2f project:sync-core <projectID>

Runs sync-core-to-project.sh --project <projectID>
```

## `project:sync-db`

Source: `cli/src/Commands/ProjectSyncDbCommand.php`.

```text
Usage: c2f project:sync-db <projectID>

Runs updates-manager-database.sh --project <projectID>
```

## `project:sync-files`

Source: `cli/src/Commands/ProjectSyncFilesCommand.php`.

```text
Usage: c2f project:sync-files <projectID> [--contents=Sim|Não]

Runs synchronize-project.sh
```

## `project:sync-hooks`

Source: `cli/src/Commands/ProjectSyncHooksCommand.php`.

```text
Usage: c2f project:sync-hooks <projectID>

Runs the project database transport in hooks-only mode.
```

## `project:sync-resources`

Source: `cli/src/Commands/ProjectSyncResourcesCommand.php`.

```text
Usage: c2f project:sync-resources [projectID]

Runs update-resource-data.sh [--project projectID]
```

## `project:update-all`

Source: `cli/src/Commands/ProjectUpdateAllCommand.php`.

```text
Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto] [--no-wait] [--lock-wait=<minutes>]

Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked local=true receives remote confirmation automatically; production remains explicit.
req-197: checks migrations first (db:check-migrations) and runs under a deploy lock per target (SSH host+path or local folder, in GIT/.c2f-deploy-locks/). A second pipeline waits for the lock (default 30 min); --no-wait fails at once.
```

## `project:update-system`

Source: `cli/src/Commands/ProjectUpdateSystemCommand.php`.

```text
Usage: c2f project:update-system [projectID] [--insecure]

Runs update-system.sh [--project projectID]. The --insecure flag is restricted to self-signed TLS endpoints in local development.
```

## Pipeline behavior

`project:update-all` accepts a project id as argument or `--project`. It syncs Core, database, resources, files, then database again. It rebuilds CSS, minifies JS and publishes assets last. Before the database stage, and again after the files are sent, it removes obsolete project migrations on the target (`synchronize-project.sh --migrations-only`, req-194): rsync deletes nothing and the target folder also holds the core migrations, so the cleanup is per owner and only reports clashes. Failures in those last three stages become warnings and the command may still return success; inspect CSS and asset reports. SSH projects marked `local=true` receive automatic remote confirmation; others require `--confirmar-remoto`. `project:deploy` calls a Bash upload script for `/_api/project/update`; `project:recover` downloads data via `/_api/project/recover`. See the [deploy guide](../../guides/deploy-a-project.md).

**Deploy lock (req-197).** Before stage 1 the pipeline runs `db:check-migrations` for the project (a duplicated version or class stops everything before anything is sent). Then it takes the lock of the **target**: the SSH host and path, or the local folder. The lock lives in `GIT/.c2f-deploy-locks/`, a folder shared by every clone and worktree of the core, the same on Windows and WSL (`C2F_LOCK_DIR` overrides). A second pipeline for the same target waits for the lock, reporting every minute who holds it, up to `--lock-wait` minutes (default 30); `--no-wait` fails at once. The lock is released at the end, also when a stage fails. The system update and the API deploy use the environment lock (`temp/deploy.lock`); while it is alive they refuse (API: HTTP 409; CLI update: exit code 8).
