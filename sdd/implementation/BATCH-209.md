# BATCH-209: Atualização do sistema por API, em segundo plano, com opções, verificação e rollback (req-201)

Execução da parte do core da [req-201](../human-requests/req-201.md). O consumo pelo host-manager e o CLI em massa ficam no `conn2flow-site` (privado), em requisição de lá.

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.

## Desenho

| Peça | O quê |
|---|---|
| `gestor/bibliotecas/atualizacoes-execucao.php` (nova, pura) | opções da API → argumentos do atualizador (lista branca), localizador do PHP CLI, leitura do estado de uma execução (log + código de saída) |
| `POST /_api/system/update`, `action=run` | dispara o atualizador do CLI em segundo plano (mesmo caminho validado nas req-197/198: trava, snapshot, dump, verificação, volta automática). Pacote do GitHub (última ou `tag`); `local_artifact` para ambiente de teste. Recusa com 409 se a trava de deploy estiver viva |
| `action=run-status` (`run=<id>`), `action=runs` | estado: `running`, `success`, `rolled_back` (código 6), `locked` (8), `error`; snapshot, verificação, fim do log |
| `/_api/system/rollback` | o mesmo `api_project_rollback()` (aceita `exec-<id>`) |
| `c2f update:core <projeto>` | dispara pela API, com as opções, e `--wait` acompanha até o fim |
| Atualização por etapas (painel, `start…finalize`) | `no_health`, `no_rollback`, `health_url`, `health_ip`; posição do log guardada no `start`; verificação e volta automática no `finalize` |

## Validação

### Automatizada
- `AtualizacoesExecucaoTest` (5: lista branca, recusa de opção desconhecida e de valor perigoso — `;`, `&`, IP e tabelas inválidos —, estado pelo log e pelo código, opções do `update:core` todas aceitas pela API, ids) e os demais do lote: **56 testes, 238 asserções, verdes**.
- `docs:audit`: 0 erros; os 6 avisos são anteriores.

### Tenant isolado (`project-test`, pelo `c2f update:core` do Windows)

| Verificação | Resultado |
|---|---|
| `update:core project-test --local-artifact --health-url=… --wait` | 202 com `run-…`; em segundo plano; `success`, snapshot `exec-18`, "Verificação pós-atualização OK (HTTP 200)"; sugestão de `update:rollback` |
| Mesmo com artefato que quebra o `gestor.php` | "(API indisponível durante a atualização: HTTP 500; aguardando…)" e depois `rolled_back` (código 6), snapshot `exec-20`, a linha da verificação que falhou e a da volta automática; site em 200 |
| `update:core --runs` | lista das execuções com estado, código e snapshot |
| `POST /_api/system/rollback` `{"snapshot":"exec-18"}` | 200 (execução sem arquivos a voltar: 0 restaurados) |
| Opção `wipe` e `health_url` com `&` | 400 com a lista das opções aceitas |
| Atualização por etapas pela API (`start` → `deploy` → `db` → `finalize`) com artefato que quebra só as páginas (fora de `/_api/`) | `finalize` devolve `saude` com "HTTP 500" e `rollback` com 2 arquivos (snapshot `exec-22`); raiz em 200 depois da janela do OPcache |

### Achados corrigidos
- **`open_basedir` do HestiaCP:** abrir `/dev/null` como descritor do `proc_open` falha no PHP-FPM; o disparo usa pipes.
- **Disparo que falha ficava `running`:** agora grava o código 1 e o motivo.
- **Argumentos crus no bootstrap (anterior ao lote):** o atualizador juntava os argumentos de quem chamou na linha do filho sem escapar; um valor com `;`/`&` viraria comando. Agora vão escapados, e a lista branca da API também barra esses caracteres.
- **`php` do PATH no bootstrap:** o filho passa a rodar com o mesmo PHP do pai (`PHP_BINARY`), não com o `php` do PATH, que pode ser outra versão.
- **Acompanhamento durante a troca:** a API passa pelo Gestor que está sendo atualizado; o `--wait` tolera HTTP 0/5xx por até 5 minutos.

### Pendências
- **Lado privado (fora do core):** o CLI em massa e o consumo pelo host-manager ficam no `conn2flow-site`, em requisição de lá (decisão do Humano de 2026-09-30). Esse repositório tem trabalho do agente do host-manager em andamento; a requisição de lá é aberta quando o Humano indicar.
- **Limite da atualização por etapas** (painel e `start/deploy/db/finalize`): agora verifica e volta no `finalize`, mas cada etapa é uma requisição que passa pelo próprio Gestor. Se a entrega quebrar o `gestor.php` (ou o caminho da API), as etapas seguintes não rodam e a volta não acontece; nesse caso vale o rollback manual (`update:rollback` pelo SSH). O caminho robusto é o `action=run` / CLI, que roda fora do servidor web.
- Homologação humana.
