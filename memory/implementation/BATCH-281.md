---
id: "BATCH-281"
title: "REQ-272: release 3.0.0 e projeto de implantação do pré-lançamento"
status: "in-progress"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "Release gestor-v3.0.0 (changelog, READMEs, notas do workflow, migração corrigida para MySQL 8) e projeto conn2flow-site-temp."
summary_medium: "1ª tentativa do CI falhou: chave primária anulável em distributed_exchanges (MySQL 8 recusa, MariaDB aceita). Corrigido, migrações testadas num banco vazio no lab e a tag refeita no commit corrigido.\nProjeto conn2flow-site-temp aponta só para temp.conn2flow.com, banco conn2flow_temp."
---

# BATCH-281 — REQ-272: release 3.0.0 e pré-lançamento

- Projeto: `conn2flow` — `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Requisição: [REQ-272](../human-requests/req-272.md); site coordenado: REQ-127 / BATCH-121
- Topologia e autonomia: `dupla`; produção e GitHub com autorização expressa do Humano em 2026-10-14
- Status: `in-progress`

## Live Todo

- [x] Commits pendentes das duas árvores (Humano autorizou): core `76ea7c3d`, `999ebd83`.
- [x] Changelog 3.0.0 (en/pt-br) e READMEs (`a4b14c1f`).
- [x] `./c2f manager:release major ...`: commit `1daf7b88`, tag `gestor-v3.0.0`, push.
- [x] CI da 1ª tentativa falhou em "Run Phinx migrations" (ver Achados); correção `9dcdc10d`.
- [x] Notas da release no workflow trocadas da 2.10.13 para a 3.0.0 (`853fc7d0`).
- [x] Tag refeita no commit corrigido, por decisão do Humano.
- [ ] CI da 2ª tentativa e release publicada com `gestor.zip` e checksum.
- [x] Projeto `conn2flow-site-temp` no `environment.json` (não versionado; cópia anterior no scratchpad da sessão).

## Achados

1. **Migração recusada no MySQL 8.** `20261002143002_create_distributed_exchanges_table.php` declarava a chave primária `id` sem `'null' => false`; esta versão do Phinx cria a coluna anulável. O MariaDB (Lab e produção) força NOT NULL em silêncio; o `mysql:8.0` do CI recusa (`1171 All parts of a PRIMARY KEY must be NOT NULL`). Nenhuma outra migração tem o padrão. Validação: as 106 migrações do core rodaram do zero num banco vazio no `lab` (62 tabelas, `id` NOT NULL); banco de teste apagado depois.
2. **Notas fixas no workflow.** O corpo da release em `.github/workflows/release-gestor.yml` era texto fixo da 2.10.13; a 3.0.0 sairia com as novidades erradas.
3. **`conn2flow.com` sem cron.** Nenhuma tarefa agendada aponta para o usuário `conn2flow` no servidor de produção (nem HestiaCP nem crontab do sistema). Afeta rotinas como renovação e lembretes; tratar antes da migração final (REQ-127).

## Pendências

- Publicação da release (CI em andamento).
- Página "Novidades" (`ai-workspace/*/docs/whats-new/`) sem a 3.0.0.
