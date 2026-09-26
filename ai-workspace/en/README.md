# 🤖 AI Workspace — Conn2Flow (en)

Core support area for technical documentation and development utilities. Work governance lives in [`sdd/`](../../sdd/README.md), and the agents' skills live in the `.claude/`, `.github/`, `.cursor/`, `.gemini/` and `.codex/` kits.

## 📁 Structure

```
ai-workspace/en/
├── docs/       # Technical documentation (source of the public documentation at /docs/)
├── scripts/    # Utilities used by the CLI, VS Code tasks and pipelines (live code)
└── utils/      # Supporting operational notes
```

## 📚 Documentation

New documentation follows the contract described in [docs/guides/documentation.md](docs/guides/documentation.md):

- **Tree:** `guides/`, `concepts/`, `reference/{libraries,modules,api,cli,hooks}/` and `whats-new/`, mirrored in `../pt-br/docs/` with the same relative path.
- **Frontmatter** with `title`, `description`, `section`, `sources` and `verified_at`.
- **Commands:**
  - `php cli/c2f.php docs:audit` produces the drift ranking;
  - `php cli/c2f.php docs:extract` generates library reference from the code;
  - `php cli/c2f.php docs:build --project=<id>` publishes to the website as system resources.

Concept and release-note pages are now in the new tree. Legacy library files still appear in `legacy` in `docs:audit` until their migration wave is complete.

## 🗄️ History

The `agents-history/`, `prompts/` and `templates/` folders were removed from `main` on 2026-09-25 (req-176). Their full content remains on the `legacy/ai-workspace-pre-docs` branch.
