---
title: "CLI: Commands env"
description: "Reference for env commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/EnvSetCommand.php
  - cli/src/Commands/EnvStatusCommand.php
verified_at: e5b61f8e
---

# CLI: Commands env

Syntax from executable help; check the source before running commands with external effects.

## `env:set`

Source: `cli/src/Commands/EnvSetCommand.php`.

```text
Usage:
  c2f env:set development [--project=ID]    Set DEVELOPMENT_ENV=true
  c2f env:set production [--project=ID]     Set DEVELOPMENT_ENV=false
  c2f dev:on                 Shortcut for env:set development
  c2f dev:off                Shortcut for env:set production
  c2f env:toggle             Toggle current value
```

## `env:status`

Source: `cli/src/Commands/EnvStatusCommand.php`.

```text
Usage: c2f env:status [--project=ID]

Shows the current DEVELOPMENT_ENV flag, project target, and related configuration.
```
