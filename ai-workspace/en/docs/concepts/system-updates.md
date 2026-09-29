---
title: "System updates"
description: "Bootstrap, package integrity, application, and web stages."
section: concepts
order: 110
sources:
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: 2d9a8c71
---

# System updates

`atualizacoes-sistema.php` supports CLI execution and a web flow. Full mode downloads or uses a local artifact, verifies SHA-256 when a checksum file is available, extracts into staging, and validates critical files. It first replaces its own updater script and runs the new version; it then applies files and updates the database. The web flow has `start`, `deploy`, `db`, and `finalize` stages linked by a session ID.

The `contents/`, `logs/`, `backups/`, `temp/`, and `autenticacoes/` directories are protected from file overwrite. `--dry-run` simulates changes, `--backup` makes a backup before applying them, and `--only-files` and `--only-db` run separate stages; the latter two cannot be combined. The database updater follows `schema-metadata.json` rules, including preservation of user-modified fields. See [resources](resources.md).

Before moving staging, core migrations the artifact no longer ships are removed from `db/migrations` (req-194, `atualizacoes-migracoes.php`). The folder has two owners — core and project —, each with its own manifest (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a core update never deletes a project migration and logs version or class clashes.

> [!WARNING]
> Running only the file stage can leave SQL resources at an older version. For page or layout changes, finish the database update and rebuild derived CSS too.
