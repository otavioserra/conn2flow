---
title: "CLI: Documentation"
description: "c2f docs commands, arguments and options declared in source code."
section: reference
order: 80
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DocsAuditCommand.php
  - cli/src/Commands/DocsBuildCommand.php
  - cli/src/Commands/DocsExtractCommand.php
verified_at: e5b61f8e
---

# CLI: Documentation

The `docs:*` family has 3 command(s) registered in Application.php.

Run php cli/c2f.php <command> --help at the Core root to check the installed version. The console uses its own contracts; --help is handled before execute(). The syntax below comes from each command.

## `docs:audit`

Source: `cli/src/Commands/DocsAuditCommand.php`.

```text
Usage: c2f docs:audit [--limit=N] [--json] [--strict] [--source=<dir>]

Audits ai-workspace/<lang>/docs/ (guides, concepts, reference, whats-new) and ranks items by
score (error = 10, warning = 3). Also lists libraries and modules without documentation and
counts legacy docs still waiting for migration.

  --limit=N   show only the N highest-scoring items (default 30; 0 = all)
  --json      print the full report as JSON
  --strict    exit 1 when any error-level issue exists
  --source    directory containing <lang>/docs (default: <core>/ai-workspace)
```

## `docs:build`

Source: `cli/src/Commands/DocsBuildCommand.php`.

```text
Usage: c2f docs:build --project=<id> [--dry-run] [--source=<dir>]

Reads <project gestor>/docs.config.json and ai-workspace/<lang>/docs/ (guides, concepts, reference,
whats-new) and writes, per language, resources/<lang>/{pages,publisher_pages,menus}/ plus pages.json,
publisher-pages.json and menus.json (merged by id: only ids 'docs' and 'docs-*' are managed), and
assets/docs/llms*.txt. Broken links or invalid frontmatter abort the build without writing.

Next step (local only): php cli/c2f.php project:update-all <id>
```

## `docs:extract`

Source: `cli/src/Commands/DocsExtractCommand.php`.

```text
Usage: c2f docs:extract <library>|--all [--check] [--create] [--source=<dir>]

Rewrites the <!-- c2f:extract:start --> block of reference/libraries/<library>.md in every language.

  --all      every library that already has a doc (with --create: every library)
  --check    do not write; exit 1 if any block is missing or stale
  --create   scaffold missing docs (pt-br and en) with frontmatter and the block
```

## Documentation flow

`docs:audit --json` reports language pairs, frontmatter, sources, links and coverage; `--strict` exits with code 1 on an error. `docs:extract --all --check` verifies library blocks without writing. `docs:build` generates configured project resources, which then need the project pipeline to reach the database. Read the [editorial contract](../../guides/documentation.md).
