---
title: "Project API"
description: "Project update upload and resource export."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# Project API

Both routes require a valid bearer token and POST.

## `/_api/project/update`

Accepts `multipart/form-data` with a `project_zip` file; `X-Project-ID` supplies the project context. Only a `.zip` filename and files up to 100 MB are accepted. It extracts the archive under temporary logs, copies contents into the manager, updates the database, and synchronizes hooks. POST `full_log` includes detailed logs. JSON response data includes `file_size`, `updated_at`, `status`, `db_logs` and `full_log`.

> [!WARNING]
> The implementation extracts the ZIP before copying files. Treat this as a high privilege administrative operation and use the [deploy flow](../../guides/deploy-a-project.md). Security follow-up: req-181.

## `/_api/project/recover`

Accepts JSON `{"tables":["paginas"],"recover_contents":false}` or CSV POST field `tables`. Without a list it exports all tables in the Core schema and the project's transient schema manifest. Returns `application/zip` with `*Data.json` files; `recover_contents` also includes `contents/`. Table names are normalized to lowercase letters, digits and underscores. The ZIP is removed after streaming.
