---
title: "CLI: IA e MDD"
description: "Comandos c2f da família ai, argumentos e opções declarados no código."
section: reference
order: 20
sources:
  - cli/src/Console/Application.php
  - cli/src/Commands/AiArchiveMemoryCommand.php
  - cli/src/Commands/AiMcpSetupCommand.php
  - cli/src/Commands/AiPruneMemoriesCommand.php
  - cli/src/Commands/AiSyncCommand.php
verified_at: b1a69317
---

# CLI: IA e MDD

A família `ai:*` tem 4 comando(s) registrado(s) em Application.php.

Execute php cli/c2f.php <comando> --help na raiz do Core para conferir a ajuda da versão instalada. O console usa contratos próprios; --help é interceptado antes de execute(). A sintaxe abaixo vem de cada comando.

## `ai:archive-memory`

Código: `cli/src/Commands/AiArchiveMemoryCommand.php`. Aliases: `ai:archive-sdd`, `memory:archive`, `ai:archive`, `sdd:archive`.

```text
Usage: c2f ai:archive-memory [options]   (alias: ai:archive-sdd)

Keeps only the most recent sequenced files in memory/human-requests/, memory/implementation/
and memory/reports/ (legacy fallback: sdd/). Older files are moved to the matching archive/
subfolder. Only the markdown links affected by the moves are rewritten (links that point to a
moved file, and the relative links inside each moved file); every other link keeps its exact text.

Options:
  --repo=PATH       Repository root to process (default: the current Core repository).
  --keep=N          How many sequenced files stay in each folder root (default: 10).
  --protect=A,B     Extra file names that must never be archived.
  --folders=A,B     Governed folders to process (default: human-requests,implementation,reports).
  --status=A,B      Only files whose status (frontmatter `status:` or a `**Status**:` line) is in the
                    list may be archived, e.g. --status=HOMOLOGATED,REVIEWED. Other files are held in
                    the root and occupy the active window, so pending reviews are never selected by
                    numeric order alone.
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
