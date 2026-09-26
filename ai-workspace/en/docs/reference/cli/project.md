---
title: "CLI: Commands project"
description: "Reference for project commands registered in the c2f console."
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
Usage: c2f project:update-all <projectID> [--contents=Sim|Não] [--confirmar-remoto]

Executes the full 8-stage synchronization pipeline. A deploy_mode=ssh project marked local=true receives remote confirmation automatically; production remains explicit.
```

## `project:update-system`

Source: `cli/src/Commands/ProjectUpdateSystemCommand.php`.

```text
Usage: c2f project:update-system [projectID] [--insecure]

Runs update-system.sh [--project projectID]. The --insecure flag is restricted to self-signed TLS endpoints in local development.
```

## Pipeline behavior

`project:update-all` accepts a project id as argument or `--project`. It syncs Core, database, resources, files, then database again. It rebuilds CSS, minifies JS and publishes assets last. Failures in those last three stages become warnings and the command may still return success; inspect CSS and asset reports. SSH projects marked `local=true` receive automatic remote confirmation; others require `--confirmar-remoto`. `project:deploy` calls a Bash upload script for `/_api/project/update`; `project:recover` downloads data via `/_api/project/recover`. See the [deploy guide](../../guides/deploy-a-project.md).
