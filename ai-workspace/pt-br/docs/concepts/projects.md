---
title: "Projetos e sobreposições"
description: "Como um projeto usa o core, sincroniza dados e preserva autoria local."
section: concepts
order: 80
sources:
  - cli/src/Commands/ProjectUpdateAllCommand.php
  - cli/src/Commands/ProjectSyncDbCommand.php
  - cli/src/Commands/ProjectSyncCoreCommand.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: 3b099ff0
---

# Projetos e sobreposições

Um projeto mantém conteúdo, configuração e recursos próprios sobre o Gestor compartilhado. `project:update-all <id>` encadeia as etapas de atualização de um projeto e termina com `css:rebuild --project=<id>`; os comandos `project:sync-core` e `project:sync-db` permitem etapas específicas. O destino e o transporte vêm da configuração do projeto, não de cópia manual para uma pasta de teste. Consulte o [guia de deploy](../guides/deploy-a-project.md).

O compilador distingue recursos globais e de projeto. No banco, registros tocados no deploy do projeto recebem `project=<id>`; a atualização posterior do core preserva recursos do projeto conforme as regras de `schema-metadata.json`. Campos editados no painel podem ser protegidos por `user_modified`; a versão nova vai para os campos `*_updated` até aplicação explícita. Veja [recursos](resources.md).

> [!WARNING]
> O fluxo de deploy do projeto não atualiza automaticamente `sitemap.xml`. Após publicar ou mudar URLs, gere e publique o sitemap pelo procedimento próprio e confira o resultado.

Executar apenas a sincronização de arquivos deixa o banco e o CSS derivados no estado anterior. Use a atualização completa quando a mudança envolver HTML, metadados ou recursos.
