---
title: "Create a plugin"
description: "Plugin manifest, resources, build and installation according to the installer."
section: guides
sources:
  - dev-plugins/templates/plugin/manifest.json
  - gestor/bibliotecas/plugins-installer.php
  - cli/src/Commands/PluginBuildCommand.php
  - cli/src/Commands/PluginResourcesCommand.php
verified_at: e5b61f8e
---

# Create a plugin

The authoring template is `dev-plugins/templates/plugin/`. A package uses `manifest.json`, `modules/`, `resources/`, `db/data/` and `db/migrations/`. The installer searches for a manifest at the root, under `plugin/` or recursively after ZIP extraction. In the manifest, `id`, `name` and `version` are required; `version` must be `x.y.z`, and `id` allows lowercase letters, digits, hyphens and underscores.

Create package modules and resources with the needed language pairs. Compile plugin resources and inspect `*Data.json` before packaging. `php cli/c2f.php plugin:build private` calls the local build script; `plugin:resources` invokes the active plugin's compiler. See the [plugin CLI](../reference/cli/plugin.md) for syntax.

`plugins-installer.php` obtains local or GitHub packages, computes SHA-256 checksums, validates the manifest, uses staging and replaces the final directory. It may back up an existing installation. Validate install and uninstall in a local disposable environment; a `plugin.json` used by other ecosystems is not this installer's manifest: it searches for `manifest.json`.
