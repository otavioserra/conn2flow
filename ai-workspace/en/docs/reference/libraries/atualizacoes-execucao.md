---
title: "atualizacoes-execucao.php library"
label: "Background update"
description: "System update triggered through the API: allow-listed arguments, command-line PHP, trigger and state (req-201)."
section: reference
order: 367
sources:
  - gestor/bibliotecas/atualizacoes-execucao.php
verified_at: c8db1447
---

# `atualizacoes-execucao.php` library

Basis of `POST /_api/system/update` with `action=run` and `run-status` (req-201 / BATCH-209). It is **pure** (no Gestor). What runs on the server is the same CLI updater (`atualizacoes-sistema.php`), with lock, snapshot, dump, check and automatic restore.

## Usage

- `atualizacoes_execucao_argv($opcoes, $dominio)` translates API options into updater arguments. It accepts only those in `ATUALIZACOES_EXECUCAO_OPCOES` and sanitizes values (tag, URL without `&`/`;`/quotes, IP, tables, days). What fails comes back in `recusadas`. It always adds `--log-stdout`, so the run log carries the snapshot and check lines.
- `atualizacoes_execucao_php_cli($preferido)` finds the command-line PHP. Inside PHP-FPM, `PHP_BINARY` is FPM itself.
- `atualizacoes_execucao_disparar($base, $id, $argv, $meta, $php)` triggers in the background (`nohup` and `setsid`), with output in `<id>.log` and the exit code in `<id>.exit`, inside `temp/atualizacoes/runs/`. It uses pipes, not `/dev/null`, because HestiaCP's `open_basedir` blocks opening `/dev/null` from PHP. On failure it writes exit code 1 so the run is not left "running".
- `atualizacoes_execucao_estado($log, $exit)` reads the state: no code yet means `running`; updater codes become `success`, `rolled_back` (6), `locked` (8) and `error-*`. It also extracts snapshot, check, automatic restore, errors and the end of the log.
- `atualizacoes_execucao_novo_id()`, `atualizacoes_execucao_id_valido()` and `atualizacoes_execucao_pasta()` handle the `run-<date>-<suffix>` id and the folder.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/atualizacoes-execucao.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `atualizacoes_execucao_id_valido(string $id): bool` — [line 25](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L25)
  Id de execução válido (`run-<data>-<sufixo>`).
- `atualizacoes_execucao_novo_id(): string` — [line 30](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L30)
  Novo id de execução.
- `atualizacoes_execucao_pasta(string $base): string` — [line 35](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L35)
  Pasta das execuções (`temp/atualizacoes/runs/`).
- `atualizacoes_execucao_argv(array $opcoes, string $dominio): array` — [line 45](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L45)
  Argumentos do atualizador a partir das opções da API. Só entram as opções da lista branca; valores são limpos (tag, URL, IP, tabelas, dias). Opção desconhecida ou valor inválido vai para `recusadas`.
  Returns: ['argv' => string[], 'recusadas' => string[]]
- `atualizacoes_execucao_php_cli(?string $preferido = null): string` — [line 76](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L76)
  PHP de linha de comando. Dentro do PHP-FPM, `PHP_BINARY` é o próprio FPM; procura, na ordem: `$preferido` (ex.: `ATUALIZACOES_PHP_CLI`), `php<maior>.<menor>` e `php` na pasta dos binários do PHP, e por fim `php` no PATH.
- `atualizacoes_execucao_disparar(string $base, string $id, array $argv, array $meta, string $php): array` — [line 92](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L92)
  Dispara o atualizador em segundo plano, desligado da requisição (`nohup` + `setsid` quando existe). A saída vai para `<id>.log` e o código de saída para `<id>.exit`; `<id>.json` guarda o pedido.
  Returns: ['ok' => bool, 'erro' => string, 'id' => string, 'log' => string]
- `atualizacoes_execucao_estado(string $log, ?string $exit): array` — [line 127](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L127)
  Estado de uma execução a partir do log e do código de saída (sem o código: ainda rodando).
  Returns: ['status' => string, 'codigo' => int|null, 'snapshot' => string|null, 'saude' => string|null,

<!-- c2f:extract:end -->
