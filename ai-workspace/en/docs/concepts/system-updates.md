---
title: "System updates"
description: "Bootstrap, package integrity, application, and web stages."
section: concepts
order: 110
sources:
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: eb96c5c7
---

# System updates

`atualizacoes-sistema.php` supports CLI execution and a web flow. Full mode downloads or uses a local artifact, verifies SHA-256 when a checksum file is available, extracts into staging, and validates critical files. It first replaces its own updater script and runs the new version; it then applies files and updates the database. The web flow has `start`, `deploy`, `db`, and `finalize` stages linked by a session ID.

The `contents/`, `logs/`, `backups/`, `temp/`, and `autenticacoes/` directories are protected from file overwrite. `--dry-run` simulates changes, `--backup` makes a backup before applying them, and `--only-files` and `--only-db` run separate stages; the latter two cannot be combined. The database updater follows `schema-metadata.json` rules, including preservation of user-modified fields. See [resources](resources.md).

Before moving staging, core migrations the artifact no longer ships are removed from `db/migrations` (req-194, `atualizacoes-migracoes.php`). The folder has two owners — core and project —, each with its own manifest (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a core update never deletes a project migration and logs version or class clashes.

After the database step, `db/` stays in place, in both CLI and web (req-197): the folder holds both owners' migrations and the manifests above.

**Deploy lock (req-197).** Every update except `--dry-run` runs under the environment lock at `temp/deploy.lock`, the same one used by the API project deploy. While another deploy is running, the CLI refuses with exit code 8 and the web `start` refuses with `locked`; the message says who holds the lock. An expired lock (a process that died) is taken over, with a warning in the log. In the CLI, the bootstrap process takes the lock and passes the token to the child (`--lock-token`), which adopts it. On the web, `start` takes it and `finalize`/`cancel` release it. `--backup` copies the whole installation to `backups/atualizacoes/full/<date>/`, without the protected folders, before touching any file.

> [!WARNING]
> Running only the file stage can leave SQL resources at an older version. For page or layout changes, finish the database update and rebuild derived CSS too.
