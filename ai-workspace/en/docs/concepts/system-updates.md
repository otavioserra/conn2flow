---
title: "System updates"
description: "Bootstrap, package integrity, application, and web stages."
section: concepts
order: 110
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
  - gestor/modulos/admin-atualizacoes/admin-atualizacoes.php
  - gestor/controladores/atualizacoes/atualizacoes-migracoes.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: 253e8e04
---

# System updates

`atualizacoes-sistema.php` supports CLI execution and a web flow. Full mode downloads or uses a local artifact, verifies SHA-256 when a checksum file is available, extracts into staging, and validates critical files. It first replaces its own updater script and runs the new version; it then applies files and updates the database. The web flow has `start`, `deploy`, `db`, and `finalize` stages linked by a session ID.

The `contents/`, `logs/`, `backups/`, `temp/`, and `autenticacoes/` directories are protected from file overwrite. `--dry-run` simulates changes, `--backup` makes a backup before applying them, and `--only-files` and `--only-db` run separate stages; the latter two cannot be combined. The database updater follows `schema-metadata.json` rules, including preservation of user-modified fields. See [resources](resources.md).

Before moving staging, core migrations the artifact no longer ships are removed from `db/migrations` (req-194, `atualizacoes-migracoes.php`). The folder has two owners — core and project —, each with its own manifest (`db/.c2f-migrations-core.json`, `db/.c2f-migrations-projeto.json`); a core update never deletes a project migration and logs version or class clashes.

After the database step, `db/` stays in place, in both CLI and web (req-197): the folder holds both owners' migrations and the manifests above.

**Deploy lock (req-197).** Every update except `--dry-run` runs under the environment lock at `temp/deploy.lock`, the same one used by the API project deploy. While another deploy is running, the CLI refuses with exit code 8 and the web `start` refuses with `locked`; the message says who holds the lock. An expired lock (a process that died) is taken over, with a warning in the log. In the CLI, the bootstrap process takes the lock and passes the token to the child (`--lock-token`), which adopts it. On the web, `start` takes it and `finalize`/`cancel` release it. `--backup` copies the whole installation to `backups/atualizacoes/full/<date>/`, without the protected folders, before touching any file.

**Layers and clashes (req-198).** Every delivery records what it delivered in `installation/manifests/<layer>.json` (path and sha256), and precedence is `projeto` > `plugin:<id>` > `core`. Before moving staging, a core update:

- **does not overwrite** a file the project or a plugin overrides: the new version goes to `backups/overrides/<version>/` and becomes an `sobreposto` clash;
- **keeps** a file changed on the server when no layer delivered that content (`editado` clash);
- **removes** what the core stopped delivering when it is intact; when it was edited, it becomes a `retirado-editado` clash.

The first delivery, without a manifest, behaves as before and records the baseline. Clashes are stored in `installation/choques/` and, after the database stage, in the `atualizacoes_choques` table (the same clash still pending does not become another row on every update); the "Delivery clashes" section of `admin-atualizacoes` lists them with the diff, and the detail page holds the per-file decision: overwrite, keep or merge (req-199; also through the API and `c2f update:conflicts` / `update:resolve`). "Keep" becomes a rule for later deliveries of the same file, and a layer delivering the same content as before creates no clash. `installation/` is a protected folder.

**Snapshot, check and rollback (req-198).** Before applying, the update keeps in `backups/atualizacoes/snapshots/exec-<id>/`:
- only the files that will be overwritten or removed, the list of new ones and the manifests;
- a database dump (`banco.sql.gz`, via `mysqldump`), before the database stage.

Afterwards it checks for a new fatal error in `logs/php-error.log` and that the site root answers below 500:
- the request goes to `https://<domain>/`, through normal DNS and, when it cannot connect, through `127.0.0.1`;
- `--health-url=<url>` and `--health-ip=<ip>` (or `ATUALIZACOES_SAUDE_URL` and `ATUALIZACOES_SAUDE_IP` in `.env`) point to another address, for example an nginx on an internal port;
- when no attempt connects, the HTTP check becomes a warning and does not fail (the server may listen only on its public IP);
- it waits 3 s before the request, because the PHP-FPM OPcache still serves the old code for a few seconds.
 When the check fails, files are restored automatically (exit code 6). The database is restored only by operator decision: `--rollback=exec-<id> --com-banco`. `--rollback=exec-<id>` alone restores only the files; `--no-health` and `--no-rollback` turn off the check or the automatic restore. The 5 most recent snapshots are kept. The API project deploy does the same (`api-…` snapshots), and rollback can also be triggered from the development machine with [`c2f update:rollback`](../reference/cli/update.md) or `POST /_api/project/rollback` (BATCH-204).

> [!WARNING]
> Running only the file stage can leave SQL resources at an older version. For page or layout changes, finish the database update and rebuild derived CSS too.
