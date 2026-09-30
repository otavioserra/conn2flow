---
title: "Project API"
description: "Project update upload and resource export."
section: reference
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
  - ai-workspace/en/scripts/projects/project-file-manifest.php
  - gestor/controladores/api/api.php
verified_at: b371b53d
---

# Project API

Both routes require a valid bearer token and POST.

## `/_api/project/update`

Accepts `multipart/form-data` with a `project_zip` file; `X-Project-ID` supplies the project context. Only a `.zip` filename and files up to 100 MB are accepted. It extracts the archive under temporary logs, copies contents into the manager, updates the database, synchronizes hooks, and regenerates `sitemap.xml`. POST `full_log` includes detailed logs. JSON response data includes `file_size`, `updated_at`, `status`, `db_logs`, `full_log` `sitemap` (`updated`, `failed` or `error: …`; a sitemap failure does not fail the deploy) and `migrations` (`removidos`, `choques`, `log`).

Runs under the environment deploy lock (`temp/deploy.lock`, req-197), the same one as the system update: while another deploy is running it answers **HTTP 409**, saying who holds the lock, before processing the package. The lock is released at the end of the request, also on errors.

The `deploy-project-v2.sh` package carries `.c2f-manifest-projeto.json` with every project file and its hash, also in gitDeploy (req-198). The server applies files through the `projeto` layer of the installation manifest:
- files the project stopped delivering leave the server, or the core original it used to override comes back;
- the core version the project overwrites is kept in `installation/originals/`.

The response includes `installation` with `versao`, `primeira`, `lista_completa`, `escritos`, `preservados`, `retirados`, `restaurados`, `choques` and `choques_gravados`. Without the list file (older package), the server only adds and updates files.

Before copying, obsolete project migrations are removed from the server (req-194): those the project delivered before and no longer delivers — from the complete list in `db/.c2f-migrations-projeto.json`, which `deploy-project-v2.sh` adds to the package — and the old copy of a renamed migration. Core migrations are never deleted; the same version with another class is recorded as a clash and Phinx rejects it during the database update.

> [!WARNING]
> The implementation extracts the ZIP before copying files. Treat this as a high privilege administrative operation and use the [deploy flow](../../guides/deploy-a-project.md). Security follow-up: req-181.

**Snapshot, check and automatic restore (req-198 / BATCH-204).** As in the system update:
- before applying, it keeps in `backups/atualizacoes/snapshots/api-<date>-<suffix>/` what will be overwritten or removed, the list of new files and the manifests (the 5 most recent are kept); before the database step, the `banco.sql.gz` dump;
- at the end, it checks for a new fatal error in `logs/php-error.log` and that the site root (through the request's own host) answers below 500. `health_url` and `health_ip` in the POST (or `ATUALIZACOES_SAUDE_URL` / `ATUALIZACOES_SAUDE_IP` in `.env`) point to another address; without a connection it is only a warning;
- when the check fails, files come back from the snapshot and the response is **HTTP 500** with `details.status = "rolled_back"`, `snapshot`, `saude`, `rollback` and `depois_do_rollback`. The database is not restored automatically;
- `no_health` and `no_rollback` in the POST turn off the check or the automatic restore.

The success response includes `snapshot` (the id for rollback), `health` and, inside `installation`, `banco_dump`.

## `/_api/project/rollback`

`POST` with JSON `{"snapshot":"api-…","com_banco":false}` (or the same fields in the POST). Restores files from the snapshot (overwritten or removed files come back; new ones are removed) and, with `com_banco`, restores the dump. It also accepts system update snapshots (`exec-<id>`). It uses the same deploy lock (409 when another deploy is running); unknown snapshot: 404. The response includes `snapshot`, `restaurados`, `removidos_novos`, `falhas` and `banco`. From the CLI: [`c2f update:rollback`](../cli/update.md).

## `/_api/project/conflicts` and `/_api/project/resolve`

Delivery clashes of this installation (req-199 / BATCH-205), with the same decision as the panel:
- `conflicts` (GET or POST): without `id`, lists pending ones (`todos=1` includes resolved ones; `limite` up to 500), each with the possible decisions in `acoes`. With `id`, returns `choque`, `no_ar`, `nova` and `codificacao` (`texto` or `base64`, for binaries);
- `resolve` (POST JSON): `{"id":12,"acao":"sobrescrever|manter|mesclar","conteudo":"…","codificacao":"texto|base64"}`; `conteudo` only for `mesclar`. It runs under the deploy lock; the decision is recorded as `api:<token e-mail>`. A decision not valid for the reason, or an already resolved clash: 422.

From the CLI: [`c2f update:conflicts` and `c2f update:resolve`](../cli/update.md).

## `/_api/project/recover`

Accepts JSON `{"tables":["paginas"],"recover_contents":false}` or CSV POST field `tables`. Without a list it exports all tables in the Core schema and the project's transient schema manifest. Returns `application/zip` with `*Data.json` files; `recover_contents` also includes `contents/`. Table names are normalized to lowercase letters, digits and underscores. The ZIP is removed after streaming.
