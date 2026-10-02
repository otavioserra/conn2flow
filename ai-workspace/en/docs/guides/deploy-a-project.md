---
title: "Update and deploy a project"
description: "Local pipeline, deployment modes, cron and sitemap."
section: guides
sources:
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectDeployCommand.php
  - ai-workspace/en/scripts/projects/deploy-project-v2.sh
  - gestor/controladores/api/api.php
  - gestor/bibliotecas/cron.php
  - gestor/bibliotecas/sitemap.php
  - gestor/bibliotecas/comunicacao.php
verified_at: 554efe72
---

# Update and deploy a project

A project uses an `<id>` in development configuration. Check whether its destination is local or remote before running commands. At the Core root, `php cli/c2f.php project:update-all <id>` runs eight sequential stages: Core, database, resources, files, database again, `css:rebuild`, JS minification and asset publication. `--contents=Não` omits the contents folder during sync. For an SSH project marked `local=true`, the pipeline authorizes local VM remote stages; other remote destinations require `--confirmar-remoto`.

The CSS stage can fail with a warning without aborting the pipeline's final return; inspect `php cli/c2f.php css:audit --project=<id>` and logs. Asset publication can also warn and continue with PHP controller delivery. The final success message alone does not validate CSS and assets.

`php cli/c2f.php project:deploy <id>` invokes `deploy-project-v2.sh`, which packages and uploads through the [project API](../reference/api/project.md). `project:recover` pulls data back into the local environment. Per stage commands are in the [project CLI](../reference/cli/project.md).

## Check that the content arrived

The final success message says the stages finished, not that the page changed. After publishing a content change, check three things:

1. **The compiled data.** The new text appears in the project's `gestor/db/data/PaginasData.json`. The resource file being right is not enough.
2. **The database log.** In the final validation stage, the line `SYNC_FIM tabela=<table> +i ~u =s` shows inserts, updates and unchanged records. `~0` on a table you changed needs investigating. `SKIP_NO_CHECKSUM_CHANGE` means the table was not even compared; `--tables=<table> --force-all` on the updater forces the comparison.
3. **The live page.** The new text in the HTTP response.

> [!WARNING]
> The SSH pipeline has no lock: two deploys at once on the same environment produce passing 500 and 503 responses and false validations. Publish and validate with the environment idle.

> [!CAUTION]
> Publishing from an incomplete source really disables records: what the owner stops delivering gets `status='D'`. See [system updates](../concepts/system-updates.md).

## After deploy

The pipeline does not create server scheduling for `gestor/cron.php`: configure ticks using the [cron reference](../reference/libraries/cron.md). Deploying through the API regenerates the whole `sitemap.xml` after the database; SSH synchronization does not. Details in the [sitemap library](../reference/libraries/sitemap.md). Production pages are served from the database, as [resources](../concepts/resources.md) explains.

If the project sends email, configure SMTPS with implicit TLS on port 465. The [communication library](../reference/libraries/comunicacao.md) enables encryption even when `EMAIL_SECURE=false`; STARTTLS on port 587 does not work in this flow.
