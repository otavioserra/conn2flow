---
title: "CLI: Comandos env"
description: "Referência dos comandos env registrados no console c2f."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/EnvSetCommand.php
  - cli/src/Commands/EnvStatusCommand.php
verified_at: e5b61f8e
---

# CLI: Comandos env

Sintaxe declarada na ajuda executável; confira o código antes de executar ações com efeitos externos.

## `env:set`

Código: `cli/src/Commands/EnvSetCommand.php`.

```text
Usage:
  c2f env:set development [--project=ID]    Set DEVELOPMENT_ENV=true
  c2f env:set production [--project=ID]     Set DEVELOPMENT_ENV=false
  c2f dev:on                 Shortcut for env:set development
  c2f dev:off                Shortcut for env:set production
  c2f env:toggle             Toggle current value
```

## `env:status`

Código: `cli/src/Commands/EnvStatusCommand.php`.

```text
Usage: c2f env:status [--project=ID]

Shows the current DEVELOPMENT_ENV flag, project target, and related configuration.
```
