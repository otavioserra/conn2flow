---
id: "BATCH-286"
title: "Hotfix do atualizador pelo painel: trava órfã e memória da sincronização"
status: "implemented-pending-homologation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Erro fatal numa etapa web libera a trava; etapas pesadas com 1 GB de memória; dono morto no mesmo servidor libera a trava; painel oferece liberar e tentar de novo."
summary_medium: "Ocorrência do temp.conn2flow.com em 15/10: fatal de memória (128 MB) em db-data.php deixou deploy.lock preso por 2 h.\nPHPUnit 17 (7 novos, com fatal real em processo separado), E2E do botão 8/8 no Lab."
---

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req277 (worktree).
REQ-277; BATCH-286; topologia dupla; autonomo_monitorado; branch `feat/req-277` (da tag `gestor-v3.0.4`). Vai na 3.0.5.

## Live Todo

- [x] `deploy_lock_orphan()` (dono morto no mesmo servidor, Linux) e `deploy_lock_force_release()` (só um dono exigido).
- [x] Atualizador web: `atualizacoes_web_recursos()` (1 GB, sem limite de tempo) e `atualizacoes_web_trava_no_fatal()` em `start`, `deploy` e `db`.
- [x] Ação `unlock` (só trava `update-system`/`web`) e `unlockable` na recusa do `start`.
- [x] Painel: diálogo "liberar a trava e tentar de novo" (pt-br/en) e botão Iniciar reabilitado após recusa.
- [x] PHPUnit e E2E no Lab.
- [ ] Release 3.0.5 e atualização do `temp` pelo painel.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `AtualizadorHotfixReq277Test` + `DeployLockTest` + `AtualizacoesSistemaTravaBackupTest` | 17 testes OK. O de erro fatal roda um PHP à parte que pega a trava, registra a liberação e estoura 32 MB: processo morre com "Allowed memory size" e a trava some |
| Pipeline `project:update-all conn2flow-site-local --skip-css` (da worktree) | saída 0 |
| E2E [req277-unlock-e2e.sh](../validation/req277-unlock-e2e.sh) | 8/8: tela com a mensagem; trava do painel → `start` recusa com `unlockable:true`, `unlock` remove; trava do pipeline → `unlockable:false`, `unlock` recusa e a trava fica |
| PHPUnit completo | ver commit: a primeira rodada na worktree falhou em 28 testes alheios por fim de linha CRLF do checkout (os mesmos passam na árvore principal com LF) |

## Observações

- O PID da trava web é o do worker do PHP-FPM, que sobrevive a erro fatal: a liberação no desligamento da etapa é o que resolve; a regra do dono morto cobre reinício do servidor e processo morto pelo sistema.
- Worktree no Windows com `core.autocrlf=true` faz testes de contrato com `"\n"` falharem; refazer o checkout com LF antes da suíte.
