---
title: "Biblioteca atualizacoes-execucao.php"
label: "Atualização em segundo plano"
description: "Atualização do sistema disparada pela API: argumentos em lista branca, PHP de linha de comando, disparo e estado (req-201)."
section: reference
order: 367
sources:
  - gestor/bibliotecas/atualizacoes-execucao.php
verified_at: cd1e6d7f
---

# Biblioteca `atualizacoes-execucao.php`

Base de `POST /_api/system/update` com `action=run` e `run-status` (req-201 / BATCH-209). É **pura** (sem Gestor). O que roda no servidor é o mesmo atualizador do CLI (`atualizacoes-sistema.php`), com trava, snapshot, dump, verificação e volta automática.

## Uso

- `atualizacoes_execucao_argv($opcoes, $dominio)` traduz as opções da API para os argumentos do atualizador. Só aceita as de `ATUALIZACOES_EXECUCAO_OPCOES` e limpa os valores (tag, URL sem `&`/`;`/aspas, IP, tabelas, dias). O que não passa volta em `recusadas`. Sempre acrescenta `--log-stdout`, para o log da execução ter as linhas de snapshot e verificação.
- `atualizacoes_execucao_php_cli($preferido)` acha o PHP de linha de comando. Dentro do PHP-FPM, `PHP_BINARY` é o próprio FPM.
- `atualizacoes_execucao_disparar($base, $id, $argv, $meta, $php)` dispara em segundo plano (`nohup` e `setsid`), com a saída em `<id>.log` e o código em `<id>.exit`, dentro de `temp/atualizacoes/runs/`. Usa pipes, não `/dev/null`, porque o `open_basedir` do HestiaCP impede abrir `/dev/null` pelo PHP. Se falhar, grava o código 1 para a execução não ficar "rodando".
- `atualizacoes_execucao_estado($log, $exit)` lê o estado: sem código ainda, `running`; os códigos do atualizador viram `success`, `rolled_back` (6), `locked` (8) e `error-*`. Também extrai snapshot, verificação, volta automática, erros e o fim do log.
- `atualizacoes_execucao_novo_id()`, `atualizacoes_execucao_id_valido()` e `atualizacoes_execucao_pasta()` cuidam do id `run-<data>-<sufixo>` e da pasta.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/atualizacoes-execucao.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `atualizacoes_execucao_id_valido(string $id): bool` — [linha 25](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L25)
  Id de execução válido (`run-<data>-<sufixo>`).
- `atualizacoes_execucao_novo_id(): string` — [linha 30](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L30)
  Novo id de execução.
- `atualizacoes_execucao_pasta(string $base): string` — [linha 35](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L35)
  Pasta das execuções (`temp/atualizacoes/runs/`).
- `atualizacoes_execucao_argv(array $opcoes, string $dominio): array` — [linha 45](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L45)
  Argumentos do atualizador a partir das opções da API. Só entram as opções da lista branca; valores são limpos (tag, URL, IP, tabelas, dias). Opção desconhecida ou valor inválido vai para `recusadas`.
  Retorno: ['argv' => string[], 'recusadas' => string[]]
- `atualizacoes_execucao_php_cli(?string $preferido = null): string` — [linha 76](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L76)
  PHP de linha de comando. Dentro do PHP-FPM, `PHP_BINARY` é o próprio FPM; procura, na ordem: `$preferido` (ex.: `ATUALIZACOES_PHP_CLI`), `php<maior>.<menor>` e `php` na pasta dos binários do PHP, e por fim `php` no PATH.
- `atualizacoes_execucao_disparar(string $base, string $id, array $argv, array $meta, string $php): array` — [linha 92](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L92)
  Dispara o atualizador em segundo plano, desligado da requisição (`nohup` + `setsid` quando existe). A saída vai para `<id>.log` e o código de saída para `<id>.exit`; `<id>.json` guarda o pedido.
  Retorno: ['ok' => bool, 'erro' => string, 'id' => string, 'log' => string]
- `atualizacoes_execucao_estado(string $log, ?string $exit): array` — [linha 127](../../../../../gestor/bibliotecas/atualizacoes-execucao.php#L127)
  Estado de uma execução a partir do log e do código de saída (sem o código: ainda rodando).
  Retorno: ['status' => string, 'codigo' => int|null, 'snapshot' => string|null, 'saude' => string|null,

<!-- c2f:extract:end -->
