# BATCH-201: Atualização segura, fase 1 (req-197)

Execução da [req-197](../human-requests/req-197.md), fase 1 do [BL-028](../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md).

**Status**: `in-progress`.
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`. É separada do diretório principal do core, onde outro agente trabalha na req-196 (layout por perfil) e mexe em `atualizacoes-banco-de-dados.php` e `atualizacao-dados-recursos.php`; este lote não toca esses dois arquivos.

## Plano

| Item | Onde | O quê |
|---|---|---|
| Trava | `gestor/bibliotecas/deploy-lock.php` (nova) | criação atômica (`fopen 'x'`), dono/execução/TTL, trava vencida assumida com aviso, liberação por dono |
| Trava | `atualizacoes-sistema.php` | CLI: trava em `main_update` (liberada no fim, no erro, no cancelamento); web: trava no `webStart`, liberada no `webFinalize`, no `webCancel` e no erro |
| Trava | `controladores/api/api.php` | `api_project_update` recusa com 409 quando a trava está viva |
| Trava | `cli/src/Commands/ProjectUpdateAllCommand.php` | trava local por projeto em `dev-environment/data/locks/`; espera com aviso periódico (limite padrão de 30 min); `--no-wait` |
| Backup | `atualizacoes-sistema.php` | `backupTotal()` implementado |
| Migrações | `cli/src/Commands/DbCheckMigrationsCommand.php` (novo) | versão e classe duplicadas entre core, projeto e plugins; primeira verificação do `project:update-all` |
| `db/` | `atualizacoes-sistema.php` | o CLI deixa de apagar `db/` (alinhado ao web) |
| Tailwind | `controladores/agents/arquitetura/tailwind-recursos.php` | tentativa automática curta no `rename` da substituição atômica |
| Testes | `tests/Unit/PHP/` | trava, backup, checagem de migrações |

## Validação

_(em andamento)_
