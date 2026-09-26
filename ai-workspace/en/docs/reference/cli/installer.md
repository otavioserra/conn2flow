---
title: "CLI: Commands installer"
description: "Reference for installer commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/InstallerBuildCommand.php
  - cli/src/Commands/InstallerNewCommand.php
  - cli/src/Commands/InstallerReleaseCommand.php
  - cli/src/Commands/InstallerSyncCommand.php
verified_at: e5b61f8e
---

# CLI: Commands installer

Syntax from executable help; check the source before running commands with external effects.

## `installer:build`

Source: `cli/src/Commands/InstallerBuildCommand.php`.

```text
Usage: c2f installer:build

Runs build-local-manager-installer.sh
```

## `installer:new`

Source: `cli/src/Commands/InstallerNewCommand.php`.

```text
Usage: c2f installer:new

Runs create-new-installation.sh
```

## `installer:release`

Source: `cli/src/Commands/InstallerReleaseCommand.php`.

```text
Usage: c2f installer:release <type> <tagMsg> <commitMsg> [mode]

Runs release-installer.sh with version bumps and changelog.
```

## `installer:sync`

Source: `cli/src/Commands/InstallerSyncCommand.php`.

```text
Usage: c2f installer:sync

Runs synchronize-manager-installer.sh checksum
```
