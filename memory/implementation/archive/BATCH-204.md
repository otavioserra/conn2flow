# BATCH-204: Atualização segura, fase 2c — snapshot, verificação e rollback no deploy por API; `c2f update:rollback` (req-198)

Execução do acréscimo do Humano à [req-198](../../human-requests/archive/req-198.md) em 2026-09-30 ("inclui aí também na API"). Continua o [BATCH-203](BATCH-203.md), na mesma worktree e no mesmo tenant isolado.

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.

## Desenho

| Peça | O quê |
|---|---|
| Biblioteca | dump, restauração, verificação e pasta do snapshot saem do atualizador e vão para `instalacao-manifesto.php` (`instalacao_banco_dump`, `instalacao_banco_restaurar`, `instalacao_saude_verificar`, `instalacao_snapshot_dir`…). O atualizador mantém `backupBancoSnapshot`, `saudeVerificar` e `rollbackExecucao` como invólucros finos |
| `/_api/project/update` | snapshot `api-<data>-<sufixo>` antes de aplicar; dump antes do banco; verificação no fim (host da requisição, `health_url`/`health_ip`); falhou, os arquivos voltam e a resposta é 500 `rolled_back`; `no_health`, `no_rollback`; `snapshot` e `health` na resposta |
| `/_api/project/rollback` (novo) | volta um snapshot (`api-…` ou `exec-…`) com o banco opcional, sob a trava de deploy |
| `c2f update:rollback` (novo) | projeto SSH: `--rollback` do atualizador pelo SSH; os outros: o endpoint, com o token do projeto |
| Carregamento da biblioteca | sem staging informado, o atualizador procura o do contexto ou o `--staging-root` do filho do bootstrap; `saudeLogOffset` não depende da biblioteca |

## Validação

### Automatizada
- `UpdateRollbackCommandTest` (3), `InstalacaoManifestoTest` (14) e os demais do lote: **37 testes, 165 asserções, verdes**.
- `docs:audit`: 0 erros; os 7 avisos são anteriores ao lote.

### Tenant isolado
Token pessoal (`c2f_pat_`, escopos read/write/deploy) gerado pelo perfil do admin do tenant e gravado em `devProjects.project-test.api.access_token` (as duas cópias do `environment.json`). Pacotes de projeto pequenos, montados com o manifesto da lista completa, enviados por `curl` (`api204.py` no scratchpad).

| Verificação | Resultado |
|---|---|
| Deploy por API normal | 200; `snapshot: api-20260930-190739-eb6c`; `banco_dump` 0,59 MB; `health` OK (HTTP 200) |
| Deploy por API com fatal em `gestor.php` (só web) | 500 `rolled_back`: "HTTP 500 em https://c2f-teste.local:8443/"; 4 arquivos restaurados; `depois_do_rollback` HTTP 200; `gestor.php` e `ok.php` na versão anterior |
| `POST /_api/project/rollback` do primeiro deploy com `com_banco` | 200; 1 restaurado, 2 novos removidos, banco restaurado |
| Snapshot `../nada` | 404 |
| `c2f update:rollback project-test exec-13` (do Windows, pelo SSH) | "Rollback exec-13: 3 arquivo(s) restaurado(s)"; o próprio atualizador voltou à versão anterior (comportamento certo) |
| `--dry-run` | mostra a linha do SSH com `sudo -u c2ftest` e as aspas certas |

### Achados corrigidos durante a homologação
- **De novo a biblioteca da versão anterior:** o `saudeLogOffset`, chamado no início do filho do bootstrap, carregava a `instalacao-manifesto.php` instalada antes de o staging estar no contexto. A partir daí, a do pacote nunca entrava (`function_exists`). Correção: `saudeLogOffset` autônomo, e o carregador procura o `--staging-root` do filho.
- **Atualizador instalado quebrado:** como o bootstrap copia o script novo antes de o filho rodar, uma versão de teste com defeito ficou como atualizador do tenant e passou a falhar já no processo pai. Recuperado recolocando o script à mão. Em produção isso só acontece se uma release sair com o atualizador quebrado; vale como alerta para a req-201 (atualização em massa).

### Pendências
- O rollback remove arquivos, mas deixa as pastas vazias que eles criaram.
- O ramo API do `c2f update:rollback` é coberto por teste unitário; a chamada real foi feita por `curl`, porque o Windows não resolve `c2f-teste.local` (fora do `hosts` e do Caddy).
- Homologação humana.
