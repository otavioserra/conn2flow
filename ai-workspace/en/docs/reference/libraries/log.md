---
title: "log.php library"
description: "log_disco() for daily files in gestor/logs/, log_backtrace() for diagnostics and the legacy functions that write to the historico table."
section: reference
order: 160
sources:
  - gestor/bibliotecas/log.php
  - gestor/db/migrations/20250723165441_create_historico_table.php
verified_at: 9ea84638
---

# `log.php` library

Two different things under the same prefix: **file logs** (`log_disco`, `log_backtrace`) and **database history** (`log_debugar` and three legacy functions). Registered as `log`; command-line scripts include it directly.

When included, it ensures `$_GESTOR['debug']` (default `false`) and `$_GESTOR['logs-path']` (default `gestor/logs/`, created if missing).

## File log

```php
log_disco('Import finished: 42 records', 'import');
// gestor/logs/import-2026-09-26.log
// [2026-09-26 14:03:11] Import finished: 42 records
```

- `log_disco($msg, $file = 'gestor', $deleteBefore = false)` appends the line with date and time to `<logs-path><file>-<YYYY-MM-DD>.log`. With `$deleteBefore`, it restarts the day's file.
- With `$_GESTOR['debug']` on, it **prints** the message instead of writing it (useful on the CLI; in a web request, it lands in the middle of the HTML).
- `log_backtrace($title, $file, $deleteBefore, $context)` writes the current call stack with the request metadata (method, path, module, option, language and only the **names** of the GET/POST fields, never their values) and returns the stack. It is the tool to find out what triggered an unexpected alert or redirect.

> [!WARNING]
> `log_disco()` reads the whole file and rewrites it on every call. With large files it gets slow, and two simultaneous requests can lose each other's lines. Folders are created with `0777` permissions and files with `0666`.

Other files in `gestor/logs/`: `hooks-errors.log` ([hooks](../../concepts/hooks.md)), `cron-<dd-mm-yyyy>.log` ([cron](cron.md)) and the `atualizacoes/` and `plugins/` subfolders of the update scripts.

## Database history (`historico`)

The `historico` table records who changed what. The normal way to write it is through [interface.php](interface.md), which records the changes of CRUD forms. In this library:

- `log_debugar(['alteracoes' => [[ 'modulo' => …, 'id' => …, 'alteracao' => …, 'alteracao_txt' => … ]]])` writes one row per change with the current user and the date. A single caller in the core.
- `log_controladores()`, `log_usuarios()` and `log_hosts_usuarios()` belong to the multi-host mode: they require `id_hosts`, read the record version from the given table (with `id` unescaped in the SQL) and **have no callers**.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/log.php` by `c2f docs:extract` — 6 functions. Do not edit inside this block.

- `log_debugar(array|false $params = false): void` — [line 60](../../../../../gestor/bibliotecas/log.php#L60)
- `log_controladores(array|false $params = false): void` — [line 123](../../../../../gestor/bibliotecas/log.php#L123)
- `log_usuarios(array|false $params = false): void` — [line 202](../../../../../gestor/bibliotecas/log.php#L202)
- `log_hosts_usuarios(array|false $params = false): void` — [line 281](../../../../../gestor/bibliotecas/log.php#L281)
- `log_backtrace(string $titulo = 'Encadeamento de chamada:', string $logFilename = 'gestor', bool $deleteFileAfter = false, array $contexto = Array()): string` — [line 348](../../../../../gestor/bibliotecas/log.php#L348)
- `log_disco(string $msg, string $logFilename = "gestor", bool $deleteFileAfter = false): void` — [line 408](../../../../../gestor/bibliotecas/log.php#L408)

<!-- c2f:extract:end -->
