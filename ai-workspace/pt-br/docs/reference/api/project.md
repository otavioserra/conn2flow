---
title: "API de projetos"
description: "Upload de atualização e exportação de recursos do projeto."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: de0e4549
---

# API de projetos

Ambas as rotas exigem bearer token válido e método POST.

## `/_api/project/update`

Recebe `multipart/form-data` com arquivo `project_zip`; o cabeçalho `X-Project-ID` informa o contexto do projeto. Aceita somente nome com extensão `.zip` e arquivo até 100 MB. Descompacta na área temporária de logs, copia o conteúdo para o gestor, executa a atualização do banco, sincroniza hooks e regenera o `sitemap.xml`. `full_log` no POST inclui logs detalhados. A resposta JSON informa `file_size`, `updated_at`, `status`, `db_logs`, `full_log` `sitemap` (`updated`, `failed` ou `error: …`; falha no sitemap não invalida o deploy) e `migrations` (`removidos`, `choques`, `log`).

Antes da cópia, as migrações obsoletas do projeto saem do servidor (req-194): as que o projeto entregou antes e não entrega mais — pela lista completa em `db/.c2f-migrations-projeto.json`, que o `deploy-project-v2.sh` põe no pacote — e a cópia antiga de uma migração renomeada. Migrações do core nunca são apagadas; mesma versão com outra classe é registrada como choque e o Phinx recusa na atualização do banco.

> [!WARNING]
> A implementação extrai o ZIP antes de copiar os arquivos. Trate o endpoint como operação administrativa de alto privilêgio e use o fluxo [de deploy](../../guides/deploy-a-project.md). Acompanhamento de segurança: req-181.

## `/_api/project/recover`

Aceita JSON `{"tables":["paginas"],"recover_contents":false}` ou campo POST `tables` em CSV. Sem lista, exporta todas as tabelas do schema do core e do manifesto transitório do projeto. Retorna `application/zip` com arquivos `*Data.json`; com `recover_contents`, acrescenta `contents/`. A lista de nomes é normalizada para letras minúsculas, dígitos e sublinhado. O ZIP é removido após o streaming.
