---
title: "CLI: Documentação"
description: "Comandos c2f da família docs, argumentos e opções declarados no código."
section: reference
order: 80
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/DocsAuditCommand.php
  - cli/src/Commands/DocsBuildCommand.php
  - cli/src/Commands/DocsExtractCommand.php
verified_at: ef6da761
---

# CLI: Documentação

A família `docs:*` tem 3 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `docs:audit`

Código: `cli/src/Commands/DocsAuditCommand.php`.

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

Código: `cli/src/Commands/DocsBuildCommand.php`.

```text
Usage: c2f docs:build --project=<id> [--dry-run] [--source=<dir>]

Reads <project gestor>/docs.config.json and ai-workspace/<lang>/docs/ (guides, concepts, reference,
whats-new) and writes, per language, resources/<lang>/{pages,publisher_pages,menus}/ plus pages.json,
publisher-pages.json and menus.json (merged by id: only ids 'docs' and 'docs-*' are managed), and
assets/docs/llms*.txt. With sdd.enabled in docs.config.json, also publishes filtered Core SDD
documents (pt-br only) under /docs/sdd/. Broken docs links or invalid frontmatter abort the build.

Next step (local only): php cli/c2f.php project:update-all <id>
```

## `docs:extract`

Código: `cli/src/Commands/DocsExtractCommand.php`.

```text
Usage: c2f docs:extract <library>|--all [--check] [--create] [--source=<dir>]

Rewrites the <!-- c2f:extract:start --> block of reference/libraries/<library>.md in every language.

  --all      every library that already has a doc (with --create: every library)
  --check    do not write; exit 1 if any block is missing or stale
  --create   scaffold missing docs (pt-br and en) with frontmatter and the block
```

## Fluxo das docs

`docs:audit --json` relata pares de idioma, frontmatter, fontes, links e cobertura; `--strict` sai com código 1 se houver erro. `docs:extract --all --check` verifica blocos de bibliotecas sem escrever. `docs:build` gera recursos do projeto configurado e exige pipeline posterior para chegar ao banco. Leia o [contrato editorial](../../guides/documentation.md).
