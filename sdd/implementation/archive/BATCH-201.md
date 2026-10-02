# BATCH-201: Atualização segura, fase 1 (req-197)

Execução da [req-197](../../human-requests/archive/req-197.md), fase 1 do [BL-028](../../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md).

**Status**: `complete` (implementado e validado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`. É separada do diretório principal do core, onde outro agente trabalha na req-196 (layout por perfil) e mexe em `atualizacoes-banco-de-dados.php` e `atualizacao-dados-recursos.php`; este lote não toca esses dois arquivos.

## Plano

| Item | Onde | O quê |
|---|---|---|
| Trava | `gestor/bibliotecas/deploy-lock.php` (nova) | criação atômica (`fopen 'x'`), dono/execução/TTL, trava vencida assumida com aviso, liberação por dono |
| Trava | `atualizacoes-sistema.php` | CLI: trava em `main_update` (liberada no fim, no erro, no cancelamento); web: trava no `webStart`, liberada no `webFinalize`, no `webCancel` e no erro |
| Trava | `controladores/api/api.php` | `api_project_update` recusa com 409 quando a trava está viva |
| Trava | `cli/src/Commands/ProjectUpdateAllCommand.php` | trava local por destino (host e caminho do SSH, ou a pasta local) em `GIT/.c2f-deploy-locks/`, comum a todos os clones e worktrees; espera com aviso periódico (limite padrão de 30 min); `--no-wait` |
| Backup | `atualizacoes-sistema.php` | `backupTotal()` implementado |
| Migrações | `cli/src/Commands/DbCheckMigrationsCommand.php` (novo) | versão e classe duplicadas entre core, projeto e plugins; primeira verificação do `project:update-all` |
| `db/` | `atualizacoes-sistema.php` | o CLI deixa de apagar `db/` (alinhado ao web) |
| Tailwind | `controladores/agents/arquitetura/tailwind-recursos.php` | tentativa automática curta no `rename` da substituição atômica |
| Testes | `tests/Unit/PHP/` | trava, backup, checagem de migrações |

## Validação

### Automatizada
- **Testes novos** (21 com o de migrações da req-194, 79 asserções, verdes):
  - `DeployLockTest` (6): pegar e liberar, segundo dono recusado com identificação, só o token libera, vencida assumida, ilegível, refresh;
  - `MigrationCheckerTest` (5): sem choque, versão duplicada entre origens, classe duplicada, mesmo arquivo em duas origens não é choque, nome fora do padrão;
  - `AtualizacoesSistemaTravaBackupTest` (4): `backupTotal` respeita as pastas protegidas; CLI recusa com trava viva (código 8, a trava do outro fica); `webStart` recusa com a trava viva; o filho do bootstrap adota a trava do pai sem liberá-la.
- **Suíte completa:** 1.291 testes. Cinco falhas (`CoreHelpersTest` RSA e quatro `Stripe*Test`) aparecem **iguais sem as mudanças** do lote (stash): vêm do ambiente da worktree.
- **Docs do CLI** (`reference/cli/db.md`, `project.md`, `help.md`, `index.md`, pt-br/en): `docs:audit` sem aviso nos arquivos tocados.

### Manual (CLI real, sem deploy)
| Verificação | Resultado |
|---|---|
| `db:check-migrations` (core) | 91 arquivos, sem choque |
| `db:check-migrations conn2flow-site-local` | 167 arquivos (core + projeto), sem choque |
| Versão repetida em `--dir` | "Versão duplicada 20250723165435: … (core) \| … (dir)", código de saída 1 |
| Pipeline com a trava do destino segura e `--no-wait` | checagem de migrações OK, depois recusa: "Outro pipeline deste projeto está em execução: teste-manual, …, desde …, vence …", antes da etapa 1 |
| Pipeline com `--lock-wait=1` | avisou "Aguardando a trava…" e recusou depois de 61 s |
| Windows × WSL | trava criada pelo PHP do Windows foi vista pelo `./c2f` no WSL |
| Pasta de travas depois dos testes | vazia (tudo liberado) |

### Pendências
- **Homologação humana.** Os agentes precisam atualizar o diretório principal do core (`git pull`) para que o `./c2f` de todos use a trava; o diretório principal tem trabalho não commitado da req-196, que não foi tocado.
- **Atualização do sistema e deploy por API:** a trava foi validada por testes; uma execução real no Lab fica para a primeira atualização com esta versão.
- **Memória de execução:** podada nesta rodada (341 → 200 linhas, antes da entrada do BATCH-201); as seções de 2026-09-02/03 estão em `sdd/archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md`.
