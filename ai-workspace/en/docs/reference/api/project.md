---
title: "Project API"
description: "Project update upload and resource export."
section: reference
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
  - ai-workspace/en/scripts/projects/project-file-manifest.php
  - gestor/controladores/api/api.php
verified_at: 5e61b186
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

## `/_api/project/recover`

Accepts JSON `{"tables":["paginas"],"recover_contents":false}` or CSV POST field `tables`. Without a list it exports all tables in the Core schema and the project's transient schema manifest. Returns `application/zip` with `*Data.json` files; `recover_contents` also includes `contents/`. Table names are normalized to lowercase letters, digits and underscores. The ZIP is removed after streaming.
