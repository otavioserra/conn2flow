---
title: "Atualize e publique um projeto"
description: "Pipeline local, modalidades de deploy, cron e sitemap."
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

# Atualize e publique um projeto

O projeto é identificado por `<id>` nas configurações de desenvolvimento. Confira se o destino é local ou remoto antes de executar comandos. Na raiz do Core, `php cli/c2f.php project:update-all <id>` executa oito etapas sequenciais: Core, banco, recursos, arquivos, banco novamente, `css:rebuild`, minificação JS e publicação de assets. `--contents=Não` omite a pasta de conteúdo na sincronização. Em projeto SSH marcado `local=true`, o pipeline autoriza as etapas remotas locais; outro destino remoto exige `--confirmar-remoto`.

A etapa CSS pode falhar e emitir aviso sem abortar o retorno final do pipeline; confira `php cli/c2f.php css:audit --project=<id>` e os logs. A publicação de assets também pode avisar e seguir com entrega pelo controlador PHP. Não interprete a mensagem final sozinha como validação de CSS e assets.

`php cli/c2f.php project:deploy <id>` chama `deploy-project-v2.sh`, que empacota e envia pela [API de projeto](../reference/api/project.md). `project:recover` faz o fluxo inverso para os dados locais. Os comandos de sincronização por etapa estão na [CLI de projetos](../reference/cli/project.md).

## Confira que o conteúdo chegou

A mensagem final de sucesso diz que as etapas terminaram, não que a página mudou. Depois de publicar uma alteração de conteúdo, confira três pontos:

1. **O dado compilado.** O texto novo aparece no `gestor/db/data/PaginasData.json` do projeto. O arquivo do recurso estar certo não basta.
2. **O log do banco.** Na etapa de validação final, a linha `SYNC_FIM tabela=<tabela> +i ~u =s` mostra inserções, atualizações e registros sem mudança. `~0` numa tabela que você alterou pede investigação. `SKIP_NO_CHECKSUM_CHANGE` significa que a tabela nem foi comparada; `--tables=<tabela> --force-all` no atualizador força a comparação.
3. **A página no ar.** O texto novo na resposta HTTP.

> [!WARNING]
> O pipeline por SSH não tem trava: dois deploys ao mesmo tempo no mesmo ambiente produzem respostas 500 e 503 passageiras e validações falsas. Publique e valide com o ambiente ocioso.

> [!CAUTION]
> Publicar a partir de uma fonte incompleta desativa registros de verdade: o que o dono deixa de entregar recebe `status='D'`. Veja [atualizações do sistema](../concepts/system-updates.md).

## Pós-deploy

O pipeline não cria o agendamento de `gestor/cron.php` no servidor: configure os ticks conforme a [referência cron](../reference/libraries/cron.md). O deploy pela API regenera o `sitemap.xml` inteiro depois do banco; a sincronização por SSH não. Detalhes na biblioteca [sitemap](../reference/libraries/sitemap.md). As páginas servidas em produção vêm do banco, como explica [recursos](../concepts/resources.md).

Se o projeto envia e-mail, configure SMTPS com TLS implícito e porta 465. A [biblioteca de comunicação](../reference/libraries/comunicacao.md) ativa a criptografia mesmo quando `EMAIL_SECURE=false`; a configuração STARTTLS na porta 587 não funciona nesse fluxo.
