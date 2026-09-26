---
title: "Update and deploy a project"
description: "Local pipeline, deployment modes, cron and sitemap."
section: guides
sources:
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectDeployCommand.php
  - ai-workspace/en/scripts/projects/deploy-project-v2.sh
  - gestor/bibliotecas/cron.php
  - gestor/bibliotecas/sitemap.php
  - gestor/bibliotecas/comunicacao.php
verified_at: e5b61f8e
---

# Update and deploy a project

A project uses an `<id>` in development configuration. Check whether its destination is local or remote before running commands. At the Core root, `php cli/c2f.php project:update-all <id>` runs eight sequential stages: Core, database, resources, files, database again, `css:rebuild`, JS minification and asset publication. `--contents=Não` omits the contents folder during sync. For an SSH project marked `local=true`, the pipeline authorizes local VM remote stages; other remote destinations require `--confirmar-remoto`.

The CSS stage can fail with a warning without aborting the pipeline's final return; inspect `php cli/c2f.php css:audit --project=<id>` and logs. Asset publication can also warn and continue with PHP controller delivery. The final success message alone does not validate CSS and assets.

`php cli/c2f.php project:deploy <id>` invokes `deploy-project-v2.sh`, which packages and uploads through the [project API](../reference/api/project.md). `project:recover` pulls data back into the local environment. Per stage commands are in the [project CLI](../reference/cli/project.md).

## After deploy

The pipeline does not create server scheduling for `gestor/cron.php`: configure ticks using the [cron reference](../reference/libraries/cron.md). Deploy also does not regenerate `sitemap.xml`; the [sitemap library](../reference/libraries/sitemap.md) updates individual pages on save or can perform full regeneration. Plan for it when publishing new content. Production pages are served from the database, as [resources](../concepts/resources.md) explains.

If the project sends email, configure SMTPS with implicit TLS on port 465. The [communication library](../reference/libraries/comunicacao.md) enables encryption even when `EMAIL_SECURE=false`; STARTTLS on port 587 does not work in this flow.
