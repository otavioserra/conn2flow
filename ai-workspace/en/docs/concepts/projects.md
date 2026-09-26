---
title: "Projects and overlays"
description: "How a project uses the core, synchronizes data, and preserves local authorship."
section: concepts
order: 80
sources:
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectSyncDbCommand.php
  - cli/src/Commands/ProjectSyncCoreCommand.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: 3b099ff0
---

# Projects and overlays

A project keeps its own content, configuration, and resources over the shared Gestor. `project:update-all <id>` runs the project's update stages and ends with `css:rebuild --project=<id>`; `project:sync-core` and `project:sync-db` run specific stages. The target and transport come from project configuration, rather than manual copies into a test directory. See the [deployment guide](../guides/deploy-a-project.md).

The compiler distinguishes global and project resources. In the database, records touched by a project deployment receive `project=<id>`; a later core update preserves project resources according to `schema-metadata.json`. Fields edited through the panel can be protected by `user_modified`; the new system version goes to `*_updated` fields until explicitly applied. See [resources](resources.md).

> [!WARNING]
> Project deployment does not automatically refresh `sitemap.xml`. After publishing or changing URLs, generate and publish the sitemap through its separate procedure and check the result.

Synchronizing files alone leaves database records and derived CSS at their previous version. Use the full update for changes to HTML, metadata, or resources.
