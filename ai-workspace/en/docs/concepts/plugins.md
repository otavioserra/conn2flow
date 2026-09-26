---
title: "Plugins"
description: "Installable packages, module resources, and installer validation."
section: concepts
order: 70
sources:
  - gestor/bibliotecas/plugins-installer.php
  - gestor/modulos/admin-plugins/admin-plugins.php
verified_at: 3b099ff0
---

# Plugins

Plugins add modules and resources without editing the core. The installer accepts an uploaded ZIP, a public or private GitHub repository, or a local path. It extracts into `gestor/temp/plugins/<slug>/`, locates `manifest.json`, and installs into `gestor/plugins/<slug>/`. The manifest requires `id`, `name`, and `version` in `x.y.z` form. Invalid metadata stops installation.

When a `.sha256` file is available for the ZIP, `plugin_process()` checks its SHA-256 before extraction. Without that file, the computed checksum detects an unchanged package and skips reprocessing; it does not authenticate the source. Installation may run package migrations and synchronize declared module resources. The `admin-plugins` panel handles installation and updates.

A plugin module uses `modules/<id>/` or `modulos/<id>/`, with its JSON and `resources/<language>/`. IDs must be unique: [hook](hooks.md) synchronization deletes records by module ID without distinguishing a plugin from the core. Also, the current [widget](widgets.md) renderer only searches `gestor/modulos/`; a widget found only under `plugins/` is not resolved by that path.

> [!WARNING]
> Installing a package executes plugin PHP, including migrations. Review its source and manifest first.
