---
title: "CLI: Commands resources"
description: "Reference for resources commands registered in the c2f console."
section: reference
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/ResourcesSyncCommand.php
verified_at: e5b61f8e
---

# CLI: Commands resources

Syntax from executable help; check the source before running commands with external effects.

## `resources:sync`

Source: `cli/src/Commands/ResourcesSyncCommand.php`.

```text
Usage: c2f resources:sync [options]

Executes the full pipeline of resource compilation, scanning layouts, pages, components,
variables, AI modes, and forms, verifying checksums and updating gestor/db/data/*Data.json.

Options:
  --force       Force rebuild of precompiled CSS and assets cache.
```

## Actual effect

`resources:sync` runs the `resources/` to `gestor/db/data/*Data.json` compiler and publishes assets when a destination is configured. `--force` appears in help, but `ResourcesSyncCommand` does not pass it to the compiler. See [resources](../../concepts/resources.md).
