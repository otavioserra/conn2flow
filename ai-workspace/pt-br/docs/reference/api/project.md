---
title: "API de projetos"
description: "Upload de atualização e exportação de recursos do projeto."
section: reference
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
  - ai-workspace/en/scripts/projects/project-file-manifest.php
  - gestor/controladores/api/api.php
verified_at: 152dfd72
---

# API de projetos

As rotas de projeto exigem bearer token válido. `conflicts` também aceita GET; as demais aqui descritas usam POST.

## `/_api/project/files`

`POST` com JSON opcional `{"camadas":["projeto"],"caminhos":["bibliotecas/"],"estados":["editado"]}`. Sem filtros, devolve `data.arquivos` e `data.total` para as divergências entre disco e manifestos por camada. Cada item informa `camada`, `caminho`, `estado` (`editado`, `ausente` ou `fora-do-manifesto`), `hash_disco` e `hash_manifesto`. Em sobreposição, a camada dona é a de maior precedência. Arquivos sem dono têm `camada: null`.

Para baixar, envie `{"baixar":true,"caminhos":["bibliotecas/a.php"]}` com caminhos **exatos** do inventário: a resposta é um ZIP com esses arquivos. Arquivo ausente não pode ser baixado. Caminhos privados, ocultos, travessia (`..`) e links para fora da instalação são recusados; `autenticacoes/`, `.env`, `logs/`, `backups/`, `temp/` e `installation/` nunca saem. A rota apenas lê e não usa trava de deploy. Pelo CLI: [`project:recover-files`](../cli/project.md).

## `/_api/project/update`

Recebe `multipart/form-data` com arquivo `project_zip`; o cabeçalho `X-Project-ID` informa o contexto do projeto. Aceita somente nome com extensão `.zip` e arquivo até 100 MB. Descompacta na área temporária de logs, copia o conteúdo para o gestor, executa a atualização do banco, sincroniza hooks e regenera o `sitemap.xml`. `full_log` no POST inclui logs detalhados. A resposta JSON informa `file_size`, `updated_at`, `status`, `db_logs`, `full_log` `sitemap` (`updated`, `failed` ou `error: …`; falha no sitemap não invalida o deploy) e `migrations` (`removidos`, `choques`, `log`).

Roda com a trava de deploy do ambiente (`temp/deploy.lock`, req-197), a mesma da atualização do sistema: com outro deploy em execução, responde **HTTP 409** dizendo quem está com a trava, antes de receber o pacote. A trava é liberada no fim da requisição, também em erro.

O pacote do `deploy-project-v2.sh` traz `.c2f-manifest-projeto.json` com todos os arquivos do projeto e seus hashes, também no gitDeploy (req-198). O servidor aplica os arquivos pela camada `projeto` do manifesto de instalação:
- o que o projeto deixou de entregar sai do servidor, ou volta a versão original do core que ele sobrepunha;
- a versão do core que o projeto sobrescreve fica guardada em `installation/originals/`.

A resposta traz `installation` com `versao`, `primeira`, `lista_completa`, `escritos`, `preservados`, `retirados`, `restaurados`, `choques` e `choques_gravados`. Sem o arquivo de lista (pacote antigo), o servidor só acrescenta e atualiza.

Antes da cópia, as migrações obsoletas do projeto saem do servidor (req-194): as que o projeto entregou antes e não entrega mais — pela lista completa em `db/.c2f-migrations-projeto.json`, que o `deploy-project-v2.sh` põe no pacote — e a cópia antiga de uma migração renomeada. Migrações do core nunca são apagadas; mesma versão com outra classe é registrada como choque e o Phinx recusa na atualização do banco.

> [!WARNING]
> A implementação extrai o ZIP antes de copiar os arquivos. Trate o endpoint como operação administrativa de alto privilêgio e use o fluxo [de deploy](../../guides/deploy-a-project.md). Acompanhamento de segurança: req-181.

**Snapshot, verificação e volta automática (req-198 / BATCH-204).** Como na atualização do sistema:
- antes de aplicar, guarda em `backups/atualizacoes/snapshots/api-<data>-<sufixo>/` o que vai ser sobrescrito ou removido, a lista dos novos e os manifestos (ficam os 5 mais recentes); antes do banco, o dump `banco.sql.gz`;
- no fim, verifica se há erro fatal novo no `logs/php-error.log` e se a raiz do site (pelo host da própria requisição) responde abaixo de 500. `health_url` e `health_ip` no POST (ou `ATUALIZACOES_SAUDE_URL` / `ATUALIZACOES_SAUDE_IP` no `.env`) apontam outro endereço; sem conexão, fica como aviso;
- se a verificação falhar, os arquivos voltam do snapshot e a resposta é **HTTP 500** com `details.status = "rolled_back"`, `snapshot`, `saude`, `rollback` e `depois_do_rollback`. O banco não volta sozinho;
- `no_health` e `no_rollback` no POST desligam a verificação ou a volta automática.

A resposta de sucesso traz `snapshot` (o id para o rollback), `health` e, em `installation`, `banco_dump`.

## `/_api/project/rollback`

`POST` com JSON `{"snapshot":"api-…","com_banco":false}` (ou os mesmos campos no POST). Volta os arquivos pelo snapshot (os que foram sobrescritos ou removidos voltam; os novos saem) e, com `com_banco`, restaura o dump. Aceita também os snapshots da atualização do sistema (`exec-<id>`). Usa a mesma trava de deploy (409 com outro deploy em execução); snapshot inexistente: 404. A resposta traz `snapshot`, `restaurados`, `removidos_novos`, `falhas` e `banco`. Pelo CLI: [`c2f update:rollback`](../cli/update.md).

## `/_api/project/conflicts` e `/_api/project/resolve`

Choques das entregas desta instalação (req-199 / BATCH-205), com a mesma decisão do painel:
- `conflicts` (GET ou POST): sem `id`, lista os pendentes (`todos=1` inclui os resolvidos; `limite` até 500), cada um com as decisões possíveis em `acoes`. Com `id`, devolve `choque`, `no_ar`, `nova` e `codificacao` (`texto` ou `base64`, para binário);
- `resolve` (POST JSON): `{"id":12,"acao":"sobrescrever|manter|mesclar","conteudo":"…","codificacao":"texto|base64"}`; `conteudo` só no `mesclar`. Roda sob a trava de deploy; quem decidiu fica como `api:<e-mail do token>`. Decisão que não vale para o motivo, ou choque já resolvido: 422.

Pelo CLI: [`c2f update:conflicts` e `c2f update:resolve`](../cli/update.md).

## `/_api/project/recover`

Aceita JSON `{"tables":["paginas"],"recover_contents":false}` ou campo POST `tables` em CSV. Sem lista, exporta todas as tabelas do schema do core e do manifesto transitório do projeto. Retorna `application/zip` com arquivos `*Data.json`; com `recover_contents`, acrescenta `contents/`. A lista de nomes é normalizada para letras minúsculas, dígitos e sublinhado. O ZIP é removido após o streaming.
