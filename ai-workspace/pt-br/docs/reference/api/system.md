---
title: "API de atualização do sistema"
description: "Ações da atualização administrativa por sessão."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
  - gestor/bibliotecas/atualizacoes-execucao.php
verified_at: b1a69317
---

# API de atualização do sistema

`POST /_api/system/update` exige bearer token e campo `action`. Ações aceitas: `start`, `deploy`, `db`, `finalize`, `status` e `cancel`. As cinco últimas exigem `sid` da sessão iniciada. A ação `start` repassa parâmetros como `domain`, `tag`, `dry_run`, `only_files`, `only_db`, `backup` e `tables` ao controlador de atualizações; `domain` usa o nome do servidor quando omitido.

A resposta usa o [envelope JSON](index.md). Erro de sessão inválida retorna 400; demais erros do atualizador, 500. Como a ação `status` também usa POST e exige `sid`, uma consulta GET de progresso não corresponde ao contrato implementado. O endpoint inclui o controlador de atualizações no processo da requisição.

## Atualização completa em segundo plano (req-201)

Para disparar de fora (host-manager, CLI) sem manter uma requisição aberta por minutos:

- **`action=run`** (corpo JSON ou formulário): dispara em segundo plano o mesmo atualizador do CLI, com trava de deploy, snapshot, dump do banco, verificação e volta automática. O pacote vem do GitHub: a última release do gestor ou a `tag` informada. As opções vão em `opcoes` (ou no próprio corpo), só da lista branca: `tag`, `only_files`, `only_db`, `no_db`, `dry_run`, `backup`, `no_verify`, `no_health`, `no_rollback`, `health_url`, `health_ip`, `force_all`, `tables`, `logs_retention_days`, `local_artifact`, `debug`. Opção desconhecida ou valor inválido: 400, com a lista das aceitas. Trava de deploy viva: 409. Sucesso: **202** com `run`, o id da execução.
- **`action=run-status`** com `run`: `status` (`running`, `success`, `rolled_back`, `locked`, `error-*`), `codigo`, `snapshot` (`exec-<id>`), `saude`, `rollback`, `erros` e `fim_do_log`. Enquanto os arquivos são trocados, a própria API pode responder 5xx por alguns segundos.
- **`action=runs`**: as 20 execuções mais recentes com o estado.
- **`POST /_api/system/rollback`**: `{"snapshot":"exec-<id>","com_banco":false}`, o mesmo rollback de [`/_api/project/rollback`](project.md).

A atualização **por etapas** (`start` … `finalize`, a do painel) também verifica e volta no `finalize` e aceita `no_health`, `no_rollback`, `health_url` e `health_ip` no `start`. Como cada etapa passa pelo próprio Gestor, uma entrega que quebre o `gestor.php` impede as etapas seguintes; para esse caso, use `action=run` (fora do servidor web) ou o rollback manual.

O servidor precisa de `proc_open` e de um PHP de linha de comando: procura `php<versão>` e `php` na pasta dos binários do PHP, ou o caminho em `ATUALIZACOES_PHP_CLI` no `.env`. Os arquivos da execução ficam em `temp/atualizacoes/runs/` (`<run>.json`, `.log`, `.exit`). Pelo CLI de desenvolvimento: [`c2f update:core`](../cli/update.md).
