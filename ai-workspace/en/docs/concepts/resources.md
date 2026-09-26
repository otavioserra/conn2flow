---
title: "Resource system"
description: "How layouts, pages, components, templates, variables and other resources go from files in resources/ to *Data.json and into the database, and how deploys preserve what the user edited."
section: concepts
order: 30
sources:
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
  - gestor/resources/resources.map.php
  - gestor/resources/tables_config.json
  - cli/src/Commands/ResourcesSyncCommand.php
  - cli/src/Commands/CssRebuildCommand.php
  - gestor/controladores/agents/arquitetura/atualizacao-versoes-assets.php
  - gestor/bibliotecas/gestor.php
verified_at: ceb259cb
---

# Resource system

A **resource** is any database record born from versioned files: a layout, a page, a component, a text variable, an AI prompt, a scheduled task, a menu. You edit files; the pipeline compiles them into `*Data.json`; the updater writes them to the database.

> [!IMPORTANT]
> In production, the site is served **from the database**. Editing a file in `resources/` changes nothing live until the pipeline runs. Only with `DEVELOPMENT_ENV=true` does the runtime read HTML and CSS straight from the files (layouts, pages and components), and even then the metadata (path, layout, permission) still comes from the database.

## The path of a resource

```
resources/<language>/<type>/<id>/<id>.html (+ .css, .precompiled.css, .md)
resources/<language>/<type>.json            metadata (name, path, layout, version, checksum)
        │  c2f resources:sync   (atualizacao-dados-recursos.php)
        ▼
gestor/db/data/<Table>Data.json   +   gestor/db/data/schema-metadata.json
        │  updater (atualizacoes-banco-de-dados.php), on deploy
        ▼
tables paginas, layouts, componentes, templates, variaveis, …
        │  c2f css:rebuild
        ▼
css_precompiled regenerated from the database HTML, with a provenance signature
```

For the system, the full pipeline is `c2f manager:update-all`. For a project, `c2f project:update-all <id>`. Both end with `css:rebuild`.

## Where resources live

| Origin | Folder | Types |
|---|---|---|
| Core or project globals | `gestor/resources/<language>/` with the files listed in `resources.map.php` | `layouts`, `pages`, `components`, `templates`, `variables` |
| Modules | `gestor/modulos/<id>/resources/<language>/`, declared in the `resources.<language>` block of `<id>.json` | the same plus `ai_prompts`, `ai_prompts_targets`, `ai_modes`, `widgets` (record only, no files) and `forms` |
| Scheduled tasks | `cron` key at the **root** of the module's `<id>.json` | one per `id`, no language (`cron.php` library) |
| Declarative tables | `tables_config.json` (core), `project_tables_config.json` (project) or the module's `tabela` block, with `sync_resources: true` | any table: `menus`, `publisher_pages`, `pages_index`… |

Languages come from `resources.map.php` (`pt-br` and `en` in the core). A resource that exists in only one language simply does not exist in the other.

### Anatomy

```
gestor/resources/pt-br/pages.json
gestor/resources/pt-br/pages/404-pagina-nao-encontrada/404-pagina-nao-encontrada.html
```

```json
{
    "name": "404 - Página Não Encontrada",
    "id": "404-pagina-nao-encontrada",
    "layout": "layout-pagina-sem-permissao",
    "path": "404/",
    "type": "page",
    "without_permission": true,
    "version": "1.4",
    "checksum": { "html": "ee70…", "css": "", "combined": "ee70…" }
}
```

Metadata uses English names that the compiler maps to the columns: `name` → `nome`, `layout` → `layout_id`, `path` → `caminho`, `type` → `tipo` (`page` → `pagina`, `system` → `sistema`), `module` → `modulo`, `option` → `opcao`, `root` → `raiz`, `without_permission` → `sem_permissao`. The Portuguese forms are accepted too. A page without `path` gets `<id>/`.

## Version and checksum

- **Do not edit `version` or `checksum` by hand.** Step 2 of `resources:sync` recomputes each resource's checksum and, when it changed, increments `version` and **rewrites the source JSON**. That is why a `resources:sync` usually leaves `pages.json`, `layouts.json` and the `<module>.json` files changed in Git: this is expected and should be committed along with the change.
- The checksum is **MD5** (`html`, `css`, `css_precompiled` and `combined`); prompts use the MD5 of the `.md`. It is not a security check, only a change detector.
- The version has the `X.Y` format and only `Y` goes up (`1.27` → `1.28`). A value outside this format (`2.0.1`, `v2`) is **reset to `1.0`**.
- `*Data.json` has two versions: `file_version` (the source one) and `versao`, an integer that goes up when the checksum differs from the previous `*Data.json`.

## Uniqueness and orphans

The compiler rejects duplicates and writes them to `gestor/db/orphans/<Type>Data.json`, outside the deploy:

| Type | Unique by |
|---|---|
| Layouts, components | language + module + id |
| Pages | language + module + id **and** language + path (no slashes, lowercase), even across modules |
| Templates | language + `target` + id |
| Variables | language + module + id + group. Without a group, only one |
| AI prompts, modes, targets, widgets | language + id |
| Cron tasks | id (also rejected without a callback or with an invalid frequency) |

> [!WARNING]
> An orphan resource disappears from the deploy **with no error**: `resources:sync` finishes successfully. After adding resources, check the final report or the `gestor/db/orphans/` folder. Two modules with a page on the same path is the most common case.

## The updater: what a deploy overwrites

The updater compares each `*Data.json` with its table and only processes the tables whose file changed since the last run. Each table's rules come from `schema-metadata.json`, generated by the compiler from `tables_config.json`, the modules' `tabela` block and `project_tables_config.json`:

- **`strategy`**: `natural_key` (matches on the `natural_key_columns`, such as `language, modulo, id`) or `pk`.
- **`insert_only`**: inserts only, never updates (the case of `usuarios`).
- **`preserve_on_user_modified`**: fields protected when the record has `user_modified=1`, that is, it was edited in the admin panel. In `paginas`: `nome, layout_id, caminho, framework_css, sem_permissao, html, css, css_compiled`.
- **`deletar`** and **`forcar_atualizacao`**: lists of records to remove or to overwrite ignoring the protections (and setting `user_modified` back to 0).

When a protected field differs, the system's new value **is not lost**: it goes to the mirror column (`html_updated`, `css_updated`; in variables, `value_updated`) and the record gets `system_updated=1`. Today no panel screen reads these columns: the system version is kept, but applying it is manual (or via `forcar_atualizacao`).

**Projects.** On a project deploy (`--project=<id>`), the records touched get `project=<id>`. A later **core** update does not overwrite project records, except for unprotected fields of user-edited records, and never replaces their `css_precompiled`.

Updater options (`php gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`): `--dry-run`, `--log-diff`, `--debug`, `--force-all`, `--tables=paginas,variaveis`, `--skip-migrate`, `--backup`, `--reverse` (exports the database to `*Data.json`), `--orphans-mode=ignore|export|log`, `--hooks-only`, `--project=<id>`, `--env-dir=<domain>`.

## CSS: authored and derived

Each layout, page, component and template has four CSS columns:

| Column | Role | Written by |
|---|---|---|
| `css` | Authored: hand-written CSS (`<id>.css`) | you |
| `css_precompiled` | Derived: Tailwind compiled for that HTML (`<id>.precompiled.css`) | `resources:sync` (from the files) and `css:rebuild` (from the database) |
| `css_compiled` | Derived: the delta generated by the online editor | the visual editor |
| `css_source_hash` | Provenance signature: which HTML, CSS, layout and Tailwind version the derived CSS came from | whoever generates the derived CSS |

In the final HTML, each one goes into a tagged `<style>` (`data-c2f-css-role="authored"`, `data-tailwind-role="page-precompiled"`, `data-c2f-css-role="compiled"`), deduplicated by hash.

When a deploy changes the authored or derived CSS, the updater clears `css_source_hash`: the current CSS keeps being served, but the resource now counts as **stale**, and `c2f css:rebuild` recompiles it against the HTML in the database. This is what prevents the hybrid state "new HTML with old CSS". See `c2f css:rebuild --help` for `--project`, `--tipo`, `--id`, `--todos` and `--dry-run`.

## Declarative tables (`sync_resources`)

Any table can become a resource with no new code. In the project's `project_tables_config.json` (or a module's `tabela.config` block):

```json
{
  "tabelas": {
    "menus": {
      "nome": "menus",
      "id": "id",
      "id_numerico": "id_menus",
      "config": {
        "scope": "global",
        "strategy": "natural_key",
        "natural_key_columns": ["language", "id"],
        "sync_resources": true,
        "metadata_file": "menus.json",
        "field_types": { "html": "file:html", "css": "file:css", "fields_schema": "json" }
      }
    }
  }
}
```

- `scope`: `global` (files in `gestor/resources/`) or `module` (files in `gestor/modulos/<module>/resources/`, with `"modulo": "<id>"`; the `modulo`/`module` column is filled in automatically).
- `metadata_file`: JSON with the list of records, in `<base>/<language>/<resources_dir or table>/`. Without it, records live inline in `resources.<language>.<table>`.
- `field_types`: `json` serializes the value; `file:<ext>` injects the content of `<id>/<id>.<ext>`.
- `id`: the column that identifies the record (it can be another one, such as `page_id`).
- Records get `language` and `status='A'` when missing.
- The compiler generates `<Table>Data.json` (`menus` → `MenusData.json`).

When `project_tables_config.json` exists, the compiler also writes `project-schema-metadata.json` at the project's Gestor root. It ships with the deploy so the server, which has no `resources/`, knows which tables belong to the project when answering `_api/project/recover` (the database pull).

**Data hooks.** A `data-hooks.php` in `gestor/resources/`, in `gestor/db/` or in a module folder is included at the end of the compilation, to post-process the generated data.

## Details and pitfalls

- `c2f resources:sync --force` shows up in the help, but it is **not passed** to the compiler: it has no effect. Likewise, the script's own options (such as `--no-origin-update`) only work by calling `php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` directly.
- After compiling, `resources:sync` publishes the assets to `public_html/dist/` (`assets:publish --opcional`); without `PUBLIC_PATH`, it only warns.
- **Static** JavaScript, CSS and images (served by URL, outside `resources/`) get an automatic cache token: `resources:sync` computes the SHA-256 of each module's files and writes `asset_version` to `<module>.json`, and does the same for `gestor/assets/` in `assets/asset-versions.json`. There is no need to bump the module version by hand; just commit those changed files.
- In development mode the runtime reads layouts, pages and components from disk, but **not** templates, variables or declarative tables.

## See also

- [Request lifecycle](request-lifecycle.md): where the database HTML becomes a page.
- [gestor.php library](../reference/libraries/gestor.md): `gestor_componente()`, `gestor_layout()`, `gestor_pagina_recursos_incluir()`.
- Skills `c2f-resources-system`, `c2f-project-pipeline-and-tasks` and `c2f-html-css-pages-and-components`.
