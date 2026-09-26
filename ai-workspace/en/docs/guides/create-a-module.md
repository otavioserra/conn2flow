---
title: "Create an administrative module"
description: "Module scaffold, generated code review and resource synchronization."
section: guides
sources:
  - cli/src/Commands/ModuleCreateCommand.php
  - gestor/modulos/menus/menus.json
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
verified_at: e5b61f8e
---

# Create an administrative module

At the Core root, inspect `php cli/c2f.php module:create --help` and run `php cli/c2f.php module:create my-module [--table=my_table]`. The command normalizes the id to lowercase kebab case; the default table name uses underscores. If `gestor/modulos/<id>/` exists, it returns an error instead of overwriting it.

The scaffold writes `<id>.php`, `<id>.json`, `<id>.js` and pt-br/en resources (pages and variables). Before publishing, review the controller and generated operations, including permissions, validation, CSRF, history and table names. Scaffolding does not replace functional review. Use [menus](../reference/modules/menus.md) as a real module example and [resources](../concepts/resources.md) for compilation.

Define pages and links under `resources/<language>/` and the module JSON. Then, in the Core development flow, run the local `php cli/c2f.php manager:update-all` pipeline. Runtime reads pages and styles from the database after updating. Review compiler orphan reports before treating the module as available.
