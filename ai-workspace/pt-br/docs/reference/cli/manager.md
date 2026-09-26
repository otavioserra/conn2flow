---
title: "CLI: Comandos manager"
description: "Referência dos comandos manager registrados no console c2f."
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

# CLI: Comandos manager

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `manager:build`

Código: `cli/src/Commands/ManagerBuildCommand.php`.

```text
Usage: c2f manager:build

Runs ai-workspace/en/scripts/updates/build-local-manager.sh
```

## `manager:commit`

Código: `cli/src/Commands/ManagerCommitCommand.php`.

```text
Usage: c2f manager:commit <commit-message>

Executes commit.sh with standard message conventions.
```

## `manager:release`

Código: `cli/src/Commands/ManagerReleaseCommand.php`.

```text
Usage: c2f manager:release <type> <tagMsg> <commitMsg> [mode]

Arguments:
  type       Release type (patch, minor, major)
  tagMsg     Tag annotation message
  commitMsg  Release commit message
  mode       automatic (default) or manual
```

## `manager:sync-files`

Código: `cli/src/Commands/ManagerSyncFilesCommand.php`.

```text
Usage: c2f manager:sync-files

Runs ai-workspace/en/scripts/dev-environment/synchronize-manager.sh checksum
```

## `manager:update-all`

Código: `cli/src/Commands/ManagerUpdateAllCommand.php`.

```text
Usage: c2f manager:update-all

Executes sequential update of resources, files, and database.
```

## Atualização local

`manager:update-all` é o orquestrador para o ambiente local do Core. Ele sincroniza recursos e arquivos, atualiza o banco e reconstrói o CSS derivado. Execute uma atualização por vez; o runtime publicado lê HTML e CSS do banco.
