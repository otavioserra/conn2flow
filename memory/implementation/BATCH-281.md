---
id: "BATCH-281"
title: "REQ-272: release 3.0.0 e projeto de implantação do pré-lançamento"
status: "implemented-pending-homologation"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "Releases gestor-v3.0.0, 3.0.1 e 3.0.2 (changelog, READMEs, notas do workflow, migração corrigida para MySQL 8) e projeto conn2flow-site-temp."
summary_medium: "1ª tentativa do CI falhou: chave primária anulável em distributed_exchanges (MySQL 8 recusa, MariaDB aceita). Corrigido, migrações testadas num banco vazio no lab e a tag refeita no commit corrigido.\nProjeto conn2flow-site-temp aponta só para temp.conn2flow.com, banco conn2flow_temp."
---

# BATCH-281 — REQ-272: release 3.0.0 e pré-lançamento

- Projeto: `conn2flow` — `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Requisição: [REQ-272](../human-requests/req-272.md); site coordenado: REQ-127 / BATCH-121
- Topologia e autonomia: `dupla`; produção e GitHub com autorização expressa do Humano em 2026-10-14
- Status: `implemented-pending-homologation`

## Live Todo

- [x] Commits pendentes das duas árvores (Humano autorizou): core `76ea7c3d`, `999ebd83`.
- [x] Changelog 3.0.0 (en/pt-br) e READMEs (`a4b14c1f`).
- [x] `./c2f manager:release major ...`: commit `1daf7b88`, tag `gestor-v3.0.0`, push.
- [x] CI da 1ª tentativa falhou em "Run Phinx migrations" (ver Achados); correção `9dcdc10d`.
- [x] Notas da release no workflow trocadas da 2.10.13 para a 3.0.0 (`853fc7d0`).
- [x] Tag refeita no commit corrigido, por decisão do Humano.
- [x] 2ª tentativa: migrações OK, PHPUnit com 14 falhas (Achado 4); corrigido em `b2e484c6`.
- [x] 3ª tentativa: CI verde; release `gestor-v3.0.0` publicada em 14/10 18:49 UTC como Latest, com `gestor.zip` (18,8 MB) e `gestor.zip.sha256` (tag em `b2e484c6`).
- [x] Reserva de versão do site (`site-pages/releases.json`) em 3.0.0 (site BATCH-121).
- [x] Sincronizador de banco grava booleanos como 0/1 (Achado 5, `1670880c`), fora do pacote 3.0.0.
- [x] **3.0.1** publicada (Achados 7 e 8): deploy por API sem a tela de manutenção na verificação, booleanos 0/1, `db-data.php` carregado pelo próprio sincronizador.
- [x] **3.0.2** publicada (Achado 9): contrato das tabelas do projeto na atualização do sistema e falha de banco que falha a atualização.
- [x] Validação real no `transformamp.local` (Lab): `c2f update:core transformamp-local --tag=gestor-v3.0.2` com `TRANSACAO_COMMIT` (23 layouts, 245 páginas, 226 templates, 92 componentes novos; `publisher` sem duplicar) e `project:deploy` com HTTP 200. O Humano conferiu o painel novo em todas as telas.
- [x] Branches de linha: `2.10.x` ➔ `gestor-v2.10.13`; `3.0.x` ➔ `gestor-v3.0.2`; rascunho antigo da `3.0.x` em `legacy/3.0.x-postgres-planejamento` (decisão do Humano).
- [x] Projeto `conn2flow-site-temp` no `environment.json` (não versionado; cópia anterior no scratchpad da sessão).

## Achados

1. **Migração recusada no MySQL 8.** `20261002143002_create_distributed_exchanges_table.php` declarava a chave primária `id` sem `'null' => false`; esta versão do Phinx cria a coluna anulável. O MariaDB (Lab e produção) força NOT NULL em silêncio; o `mysql:8.0` do CI recusa (`1171 All parts of a PRIMARY KEY must be NOT NULL`). Nenhuma outra migração tem o padrão. Validação: as 106 migrações do core rodaram do zero num banco vazio no `lab` (62 tabelas, `id` NOT NULL); banco de teste apagado depois.
2. **Notas fixas no workflow.** O corpo da release em `.github/workflows/release-gestor.yml` era texto fixo da 2.10.13; a 3.0.0 sairia com as novidades erradas.
3. **`conn2flow.com` sem cron.** Nenhuma tarefa agendada aponta para o usuário `conn2flow` no servidor de produção (nem HestiaCP nem crontab do sistema). Afeta rotinas como renovação e lembretes; tratar antes da migração final (REQ-127).
4. **Testes que só falham no modo do CI.** O CI roda com `CONN2FLOW_TEST_BOOTSTRAP_CONFIG=1` (carrega o `config.php` real), que liga `confirmacao-origem` (req-217): 13 testes dos módulos distribuídos usavam Central de mentira e passaram a ser recusados. Cada classe agora desliga a conferência no próprio `setUp` (a Req217 continua testando-a). A 14ª falha era o `ai:sync` com os nomes `sdd-*` antigos (REQ-076 do ai-workspace): os quatro nomes foram trocados no `AiSyncCommand`. Reproduzido e validado no `lab` com o `.env` e o banco do CI: 1.788 testes, 0 falhas, nos dois modos; Vitest 664; E2E 3 pulados.
5. **Booleano do JSON vira string vazia no PDO.** `"sem_permissao": false` (duas páginas do site) chegava como `''` no INSERT de página nova e o MariaDB estrito recusava (1366); a 2ª execução do pipeline do `temp` parou na etapa 6/8 com rollback. `paramsBanco()` em `atualizacoes-banco-de-dados.php` converte booleanos nos cinco pontos de gravação; teste `AtualizacaoBancoBooleanosReq272Test`. As sementes do core não têm `false`, por isso o pacote 3.0.0 não é afetado.
6. **Trava de deploy órfã.** A execução interrompida com o fim da sessão deixou `../.c2f-deploy-locks/deploy-189b3e7a0bb03feb.lock` (PID 43740, já morto, venceria em 3 h); removida à mão depois de conferir que o processo não existia.

7. **Deploy por API sempre desfeito.** A tela de manutenção (req-210) responde 503 à verificação pós-deploy (req-198); toda entrega por `project:deploy` era revertida (`transformamp.local`). A manutenção sai antes da verificação; teste em `ManutencaoReq210Test`.
8. **Atualização pelo painel vinda da 2.10.x.** O processo já tinha o `gestor.php` antigo; o `require_once` do sincronizador novo era pulado, o `db-data.php` (novo na 3.0) não entrava e a etapa de banco parava em `db_data_list_tables()`. O painel ficava em Fomantic-UI porque as sementes não eram aplicadas. Os dois sincronizadores carregam o `db-data.php` direto.
9. **Tabelas de projeto e falha silenciosa.** Na atualização do sistema, `db/data/schema-metadata.json` é o do core e os `*Data.json` do projeto ficam na pasta: `publisher` sem chave natural ➔ `1062 Duplicate entry`. `schemaMetadata()` completa o contrato com o `project-schema-metadata.json` (core tem precedência). E o retorno `1` do sincronizador incluído era ignorado: a atualização terminava "success" com o banco antigo; agora falha.
10. **Mídia do `transformamp.local`.** As imagens `/files/...` (1,7 GB em produção) nunca estiveram no Lab; não é defeito da atualização. O Humano decidiu não copiar.

## Pendências

- Página "Novidades" (`ai-workspace/*/docs/whats-new/`) sem a 3.0.0.
