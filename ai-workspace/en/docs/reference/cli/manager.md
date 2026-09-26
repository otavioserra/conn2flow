---
title: "CLI: Commands manager"
description: "Reference for manager commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/ManagerBuildCommand.php
  - cli/src/Commands/ManagerCommitCommand.php
  - cli/src/Commands/ManagerReleaseCommand.php
  - cli/src/Commands/ManagerSyncFilesCommand.php
  - cli/src/Commands/ManagerUpdateAllCommand.php
verified_at: e5b61f8e
---

# CLI: Commands manager

Syntax from executable help; check the source before running commands with external effects.

## `manager:build`

Source: `cli/src/Commands/ManagerBuildCommand.php`.

```text
Usage: c2f manager:build

Runs ai-workspace/en/scripts/updates/build-local-manager.sh
```

## `manager:commit`

Source: `cli/src/Commands/ManagerCommitCommand.php`.

```text
Usage: c2f manager:commit <commit-message>

Executes commit.sh with standard message conventions.
```

## `manager:release`

Source: `cli/src/Commands/ManagerReleaseCommand.php`.

```text
Usage: c2f manager:release <type> <tagMsg> <commitMsg> [mode]

Arguments:
  type       Release type (patch, minor, major)
  tagMsg     Tag annotation message
  commitMsg  Release commit message
  mode       automatic (default) or manual
```

## `manager:sync-files`

Source: `cli/src/Commands/ManagerSyncFilesCommand.php`.

```text
Usage: c2f manager:sync-files

Runs ai-workspace/en/scripts/dev-environment/synchronize-manager.sh checksum
```

## `manager:update-all`

Source: `cli/src/Commands/ManagerUpdateAllCommand.php`.

```text
Usage: c2f manager:update-all

Executes sequential update of resources, files, and database.
```

## Local update

`manager:update-all` orchestrates the local Core environment. It syncs resources and files, updates the database and rebuilds derived CSS. Run one update at a time; published runtime reads HTML and CSS from the database.
