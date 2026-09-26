---
title: "Biblioteca log.php"
description: "log_disco() para arquivos diários em gestor/logs/, log_backtrace() para diagnóstico e as funções legadas que gravam na tabela historico."
section: reference
order: 160
sources:
  - gestor/bibliotecas/log.php
  - gestor/db/migrations/20250723165441_create_historico_table.php
verified_at: 9ea84638
---

# Biblioteca `log.php`

Duas coisas diferentes com o mesmo prefixo: **logs em arquivo** (`log_disco`, `log_backtrace`) e **histórico no banco** (`log_debugar` e três funções legadas). Registrada como `log`; os scripts de linha de comando a incluem direto.

Ao ser incluída, garante `$_GESTOR['debug']` (padrão `false`) e `$_GESTOR['logs-path']` (padrão `gestor/logs/`, criado se não existir).

## Log em arquivo

```php
log_disco('Importação concluída: 42 registros', 'importacao');
// gestor/logs/importacao-2026-09-26.log
// [2026-09-26 14:03:11] Importação concluída: 42 registros
```

- `log_disco($msg, $arquivo = 'gestor', $apagarAntes = false)` acrescenta a linha com data e hora em `<logs-path><arquivo>-<AAAA-MM-DD>.log`. Com `$apagarAntes`, recomeça o arquivo do dia.
- Com `$_GESTOR['debug']` ligado, **imprime** a mensagem em vez de gravar (útil no CLI; numa requisição web, sai no meio do HTML).
- `log_backtrace($titulo, $arquivo, $apagarAntes, $contexto)` grava a pilha de chamadas atual com os metadados da requisição (método, caminho, módulo, opção, idioma e só os **nomes** dos campos GET/POST, nunca os valores) e devolve a pilha. É a ferramenta para descobrir quem disparou um alerta ou um redirecionamento inesperado.

> [!WARNING]
> `log_disco()` lê o arquivo inteiro e o regrava a cada chamada. Com arquivos grandes fica lento, e duas requisições simultâneas podem perder linhas uma da outra. As pastas são criadas com permissão `0777` e os arquivos com `0666`.

Outros arquivos em `gestor/logs/`: `hooks-errors.log` ([hooks](../../concepts/hooks.md)), `cron-<dd-mm-aaaa>.log` ([cron](cron.md)) e as subpastas `atualizacoes/` e `plugins/` dos scripts de atualização.

## Histórico no banco (`historico`)

A tabela `historico` guarda quem mudou o quê. O caminho normal para gravá-la é o da [interface.php](interface.md), que registra as alterações dos formulários de CRUD. Nesta biblioteca:

- `log_debugar(['alteracoes' => [[ 'modulo' => …, 'id' => …, 'alteracao' => …, 'alteracao_txt' => … ]]])` grava uma linha por alteração com o usuário atual e a data. Um único chamador no core.
- `log_controladores()`, `log_usuarios()` e `log_hosts_usuarios()` são do modo multi-host: exigem `id_hosts`, leem a versão do registro na tabela informada (com `id` sem escape no SQL) e **não têm chamadores**.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/log.php` por `c2f docs:extract` — 6 funções. Não edite dentro deste bloco.

- `log_debugar(array|false $params = false): void` — [linha 60](../../../../../gestor/bibliotecas/log.php#L60)
- `log_controladores(array|false $params = false): void` — [linha 123](../../../../../gestor/bibliotecas/log.php#L123)
- `log_usuarios(array|false $params = false): void` — [linha 202](../../../../../gestor/bibliotecas/log.php#L202)
- `log_hosts_usuarios(array|false $params = false): void` — [linha 281](../../../../../gestor/bibliotecas/log.php#L281)
- `log_backtrace(string $titulo = 'Encadeamento de chamada:', string $logFilename = 'gestor', bool $deleteFileAfter = false, array $contexto = Array()): string` — [linha 348](../../../../../gestor/bibliotecas/log.php#L348)
- `log_disco(string $msg, string $logFilename = "gestor", bool $deleteFileAfter = false): void` — [linha 408](../../../../../gestor/bibliotecas/log.php#L408)

<!-- c2f:extract:end -->
