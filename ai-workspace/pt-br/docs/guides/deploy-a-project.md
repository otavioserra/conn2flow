---
title: "Atualize e publique um projeto"
description: "Pipeline local, modalidades de deploy, cron e sitemap."
section: guides
sources:
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectDeployCommand.php
  - ai-workspace/en/scripts/projects/deploy-project-v2.sh
  - gestor/bibliotecas/cron.php
  - gestor/bibliotecas/sitemap.php
verified_at: e5b61f8e
---

# Atualize e publique um projeto

O projeto é identificado por `<id>` nas configurações de desenvolvimento. Confira se o destino é local ou remoto antes de executar comandos. Na raiz do Core, `php cli/c2f.php project:update-all <id>` executa oito etapas sequenciais: Core, banco, recursos, arquivos, banco novamente, `css:rebuild`, minificação JS e publicação de assets. `--contents=Não` omite a pasta de conteúdo na sincronização. Em projeto SSH marcado `local=true`, o pipeline autoriza as etapas remotas locais; outro destino remoto exige `--confirmar-remoto`.

A etapa CSS pode falhar e emitir aviso sem abortar o retorno final do pipeline; confira `php cli/c2f.php css:audit --project=<id>` e os logs. A publicação de assets também pode avisar e seguir com entrega pelo controlador PHP. Não interprete a mensagem final sozinha como validação de CSS e assets.

`php cli/c2f.php project:deploy <id>` chama `deploy-project-v2.sh`, que empacota e envia pela [API de projeto](../reference/api/project.md). `project:recover` faz o fluxo inverso para os dados locais. Os comandos de sincronização por etapa estão na [CLI de projetos](../reference/cli/project.md).

## Pós-deploy

O pipeline não cria o agendamento de `gestor/cron.php` no servidor: configure os ticks conforme a [referência cron](../reference/libraries/cron.md). O deploy também não regenera `sitemap.xml`; a biblioteca [sitemap](../reference/libraries/sitemap.md) atualiza páginas individualmente ao salvar ou permite regeneração completa. Planeje essa atualização ao publicar conteúdo novo. As páginas servidas em produção vêm do banco, como explica [recursos](../concepts/resources.md).
