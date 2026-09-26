---
title: "CLI: CSS"
description: "Comandos c2f da família css, argumentos e opções declarados no código."
section: reference
order: 50
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/CssAuditCommand.php
  - cli/src/Commands/CssRebuildCommand.php
verified_at: e5b61f8e
---

# CLI: CSS

A família `css:*` tem 2 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `css:audit`

Código: `cli/src/Commands/CssAuditCommand.php`.

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

Código: `cli/src/Commands/CssRebuildCommand.php`.

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

## CSS derivado

`css:audit` mede recursos cujo CSS derivado não corresponde é origem; `css:rebuild` recompila a partir do HTML no banco. Os dois aceitam seleção de projeto nas opções declaradas pela ajuda. Uma mensagem final de `project:update-all` não substitui essa auditoria.
