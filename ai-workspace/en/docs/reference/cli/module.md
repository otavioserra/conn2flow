---
title: "CLI: Commands module"
description: "Reference for module commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/ModuleCreateCommand.php
verified_at: e5b61f8e
---

# CLI: Commands module

Syntax from executable help; check the source before running commands with external effects.

## `module:create`

Source: `cli/src/Commands/ModuleCreateCommand.php`.

```text
Usage: c2f module:create <module-id> [options]

Creates a complete canonical module in gestor/modulos/<module-id>/ with:
  - <module-id>.php (Controller with lifecycle hooks)
  - <module-id>.json (Schema metadata with natural_key strategy)
  - <module-id>.js (Frontend script)
  - resources/pt-br/ & resources/en/ (Pages, templates, variables)

Arguments:
  module-id     Kebab-case identifier of the module (e.g. 'relatorios-gerenciais').

Options:
  --table=NAME  Custom database table name (default: same as module identifier with underscores).
```

## After scaffolding

`module:create` normalizes the id to kebab case and refuses an existing directory. It generates controller, JSON, JS and pt-br/en resources; review permissions, CRUD and history before sync. See [create a module](../../guides/create-a-module.md).
