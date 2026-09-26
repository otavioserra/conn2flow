---
title: "CLI: CSS"
description: "c2f css commands, arguments and options declared in source code."
section: reference
order: 50
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/CssAuditCommand.php
  - cli/src/Commands/CssRebuildCommand.php
verified_at: e5b61f8e
---

# CLI: CSS

The `css:*` family has 2 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `css:audit`

Source: `cli/src/Commands/CssAuditCommand.php`.

```text
Usage: c2f css:audit [--project=ID] [--gestor=PATH] [--limite=N] [--json]

Reports, per resource table, how many records have derived CSS that no longer matches
the stored authorship (stale) and how many CSS classes the HTML uses without any rule
being delivered. Only resources with framework_css=tailwindcss are considered.

Options:
  --project=ID  Resolve the gestor path from environment.json (devProjects).
  --gestor=PATH Audit this gestor path directly (skips project resolution).
  --limite=N    How many worst cases to list (default 10).
  --json        Emit the raw report as JSON.
  --simular-remoto  Print the SSH command without executing it.
```

## `css:rebuild`

Source: `cli/src/Commands/CssRebuildCommand.php`.

```text
Usage: c2f css:rebuild [--project=ID] [--gestor=PATH] [--tipo=T] [--id=ID]
                       [--limite=N] [--todos] [--dry-run]

Compiles Tailwind against the HTML that the runtime actually serves (the database),
writes css_precompiled and stamps css_source_hash. Without --todos, only stale
resources are rebuilt. Requires the Tailwind CLI (local or available in PATH).

Options:
  --project=ID  Resolve the gestor path from environment.json (devProjects).
  --gestor=PATH Use this gestor path directly.
  --tipo=T      Restrict to one table (paginas, layouts, componentes, templates).
  --id=ID       Restrict to a single resource id.
  --limite=N    Stop after N recompilations.
  --todos       Rebuild every resource, not only the stale ones.
  --dry-run     Compile and report, writing nothing.
  --confirmar-remoto  Required when the project has local=false in environment.json,
                      and to dispatch the rebuild inside a deploy_mode="ssh" VM.
  --simular-remoto    Print the SSH command line for the VM without executing it.
```

## Derived CSS

`css:audit` measures resources whose derived CSS is out of sync with source; `css:rebuild` recompiles from database HTML. Both accept project selection through options shown in help. A final `project:update-all` success message does not replace this audit.
