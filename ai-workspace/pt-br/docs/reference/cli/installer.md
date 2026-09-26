---
title: "CLI: Comandos installer"
description: "Referência dos comandos installer registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/InstallerBuildCommand.php
  - cli/src/Commands/InstallerNewCommand.php
  - cli/src/Commands/InstallerReleaseCommand.php
  - cli/src/Commands/InstallerSyncCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos installer

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `installer:build`

Código: `cli/src/Commands/InstallerBuildCommand.php`.

```text
Usage: c2f installer:build

Runs build-local-manager-installer.sh
```

## `installer:new`

Código: `cli/src/Commands/InstallerNewCommand.php`.

```text
Usage: c2f installer:new

Runs create-new-installation.sh
```

## `installer:release`

Código: `cli/src/Commands/InstallerReleaseCommand.php`.

```text
Usage: c2f installer:release <type> <tagMsg> <commitMsg> [mode]

Runs release-installer.sh with version bumps and changelog.
```

## `installer:sync`

Código: `cli/src/Commands/InstallerSyncCommand.php`.

```text
Usage: c2f installer:sync

Runs synchronize-manager-installer.sh checksum
```
