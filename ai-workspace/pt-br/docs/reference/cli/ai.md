---
title: "CLI: IA e SDD"
description: "Comandos c2f da família ai, argumentos e opções declarados no código."
section: reference
order: 20
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/AiArchiveSddCommand.php
  - cli/src/Commands/AiMcpSetupCommand.php
  - cli/src/Commands/AiPruneMemoriesCommand.php
  - cli/src/Commands/AiSyncCommand.php
verified_at: e5b61f8e
---

# CLI: IA e SDD

A família `ai:*` tem 4 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `ai:archive-sdd`

Código: `cli/src/Commands/AiArchiveSddCommand.php`.

```text
Usage: c2f ai:archive-sdd [options]

Keeps only the most recent sequenced files in sdd/human-requests/ and sdd/implementation/.
Older files are moved to the matching archive/ subfolder and every markdown link that
referenced them (in BATCH-INDEX.md, VALIDATION-CHECKLIST.md, DECISION-LOG.md, CURRENT.md
and any other .md under sdd/) is rewritten to the new path.

Options:
  --repo=PATH       Repository root to process (default: the current Core repository).
  --keep=N          How many sequenced files stay in each folder root (default: 10).
  --protect=A,B     Extra file names that must never be archived.
  --repair-links    Also re-anchor links already broken by earlier manual archiving.
  --dry-run         Show the plan without moving files or rewriting links.
  --verbose         List every archived file and every rewritten link.
```

## `ai:mcp-setup`

Código: `cli/src/Commands/AiMcpSetupCommand.php`.

```text
Usage: c2f ai:mcp-setup

Detects and injects 'conn2flow-hub' MCP server into Claude Desktop, Cursor, and VS Code settings.
```

## `ai:prune-memories`

Código: `cli/src/Commands/AiPruneMemoriesCommand.php`.

```text
Usage: c2f ai:prune-memories

Validates sdd/MEMORIA-ENGENHARIA-EXECUCAO.md with a 50KB / 200-line warning, a 75KB / 300-line mandatory pruning ceiling, and a ~25KB post-pruning target.
```

## `ai:sync`

Código: `cli/src/Commands/AiSyncCommand.php`.

```text
Usage: c2f ai:sync [options]

Verifies the integrity and contracts of the 37 Core and SDD skills in .claude/, .cursor/, .gemini/, .github/ and .codex/.

Options:
  --verbose     Display details of each verified skill contract.
```
