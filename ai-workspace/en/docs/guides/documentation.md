---
title: "How to write and publish documentation"
description: "The Conn2Flow docs contract: where docs live, the required frontmatter, the audit routine against the code and publishing to the website."
section: guides
order: 90
sources:
  - cli/src/Support/Docs
  - cli/src/Commands/DocsAuditCommand.php
  - cli/src/Commands/DocsExtractCommand.php
  - cli/src/Commands/DocsBuildCommand.php
verified_at: 5b4348ab
---

# How to write and publish documentation

Conn2Flow documentation has **a single source**: the Markdown files in `ai-workspace/<lang>/docs/` in the Core repository. The public website (`/docs/`) is generated from them; nothing is written directly in the admin panel.

The golden rule is that **the code is the authority**. A doc only counts once it has been checked against the code, and the commit of that check is recorded in it.

## Where each doc lives

```
ai-workspace/<lang>/docs/
├── index.md          docs home page
├── guides/           task-oriented walkthroughs
├── concepts/         how the system works and why
├── reference/
│   ├── libraries/    one doc per gestor/bibliotecas/<name>.php
│   ├── modules/      one doc per gestor/modulos/<id>/
│   ├── api/  cli/  hooks/
└── whats-new/        release highlights
```

- **Folders and file names are identical in both languages.** `pt-br/docs/guides/x.md` pairs with `en/docs/guides/x.md`. That is what links translations and defines the URL (`/docs/guides/x/`).
- Write both versions **in the same pass**, with the same context in mind. There is no "translate later".
- The UPPERCASE files at the root of `docs/` are **legacy**. When a subject is migrated, the new doc is born in the tree above and the matching legacy file is removed.

## Frontmatter

```yaml
---
title: "modelo.php library"
description: "One sentence: shown in indexes, social sharing and llms.txt."
section: reference          # guides | concepts | reference | whats-new (same as the folder)
order: 20                   # position in the menu (default 100)
visibility: public          # public (default) | restricted (reserved)
module: menus               # optional: id of the documented module
sources:                    # required in reference/: the code this doc describes
  - gestor/bibliotecas/modelo.php
verified_at: 5b4348ab       # Core commit the doc was checked against
---
```

`sources` and `verified_at` are what make drift measurable: if any source changes after `verified_at`, the doc climbs the `docs:audit` ranking.

## How to write a doc

1. **Read the code before the old text.** For a library, read the whole file, its callers (`grep -rn "function_name(" gestor`) and its tests. For a module, read `<id>.php`, `<id>.json`, the JS, the widget (`<id>.widget.php`), the `resources/` (pages, templates, `ai_modes`), the table migrations and the hooks.
2. **Describe the actual behavior**, including the surprising parts: case sensitivity, first occurrence only, defaults, what happens when something is missing. When the code contradicts its own docblock, the doc follows the code and points out the mismatch.
3. **Flag legacy.** Functions without callers, functions that call something that does not exist and dead options are useful information, not embarrassment.
4. **Show an example** taken from a real use in the core, whenever one exists.
5. Fill in `sources` and `verified_at` with the current commit (`git rev-parse --short HEAD`).

Available callouts (rendered as colored boxes on the website):

```markdown
> [!NOTE]
> Additional information.

> [!TIP]
> Shortcut or good practice.

> [!IMPORTANT]
> Something that changes the outcome.

> [!WARNING]
> Risk of error.

> [!CAUTION]
> Security or data-loss risk.
```

Links between docs are relative and point to the `.md` file (`../modules/menus.md`). The build turns them into website URLs and **fails** when the target does not exist.

## Reference generated from the code

Docs in `reference/libraries/` end with a generated block:

```markdown
<!-- c2f:extract:start -->
…list of functions with signature and line…
<!-- c2f:extract:end -->
```

- `php cli/c2f.php docs:extract modelo` regenerates the `modelo.php` block in both languages.
- `php cli/c2f.php docs:extract --all --check` fails when any block is stale.
- `php cli/c2f.php docs:extract 2fa --create` scaffolds the doc for a library that is not documented yet.

Do not edit inside the block. The text outside it is yours, and the audit warns you when a function in the code is not mentioned in the text.

## The routine: `docs:audit`

```bash
php cli/c2f.php docs:audit            # ranking (top 30)
php cli/c2f.php docs:audit --limit=0  # everything
php cli/c2f.php docs:audit --json     # for tooling
php cli/c2f.php docs:audit --strict   # exit 1 on errors (CI)
```

The ranking adds 10 points per error and 3 per warning. The command checks:
- frontmatter;
- language pairs;
- missing sources;
- sources changed after `verified_at`;
- broken links;
- stale extracted blocks;
- unexplained functions;
- libraries and modules without docs.

On every agent pass over the documentation:

1. Run `docs:audit` and take the top items.
2. For each item, read the code in depth and rewrite both languages.
3. Run `docs:extract --all` and `docs:audit` again until the item leaves the ranking.
4. Publish locally with `docs:build` (below) and check it in the browser.

## Publishing to the website: `docs:build`

```bash
php cli/c2f.php docs:build --project=conn2flow-site-local --dry-run
php cli/c2f.php docs:build --project=conn2flow-site-local
```

- **Configuration:** the command reads `docs.config.json` in the project's `gestor/` folder. It sets the languages, base path, layout, which publisher receives each section and the sidebar menu id.
- **What gets generated:** the Markdown becomes HTML with Tailwind classes and is written as the project's **system resources**:
  - publications (`publisher_pages`);
  - pages with a `publisher_id`;
  - the sidebar menu;
  - `llms.txt`.
- **Publishing:** from there the path is the same as any resource. Run `project:update-all` to sync and rebuild the CSS in the **local** environment. The production deploy is done by the operator.

> [!IMPORTANT]
> The build overwrites the documentation pages on every run. Fixes made in the admin panel are lost; always fix the Markdown.

## See also

- [modelo.php library](../reference/libraries/modelo.md): an example of a library reference doc.
- [Menus module](../reference/modules/menus.md): an example of a module doc.
