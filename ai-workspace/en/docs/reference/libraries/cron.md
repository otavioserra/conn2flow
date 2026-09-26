---
title: "cron.php library"
label: "Scheduled routines"
description: "Scheduled routines: declaring a task in the module, scheduling the ticks on the server and what the gestor/cron.php engine actually runs."
section: reference
order: 130
sources:
  - gestor/bibliotecas/cron.php
  - gestor/cron.php
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
  - gestor/modulos/admin-cron/admin-cron.php
verified_at: f2653c2b
---

# `cron.php` library

Scheduled routines have three parts:

1. **The declaration**, in the `cron` key of the module JSON, which the pipeline writes to the `cron_tarefas` table.
2. **The engine** `gestor/cron.php`, a command-line script that runs the tasks of one frequency.
3. **The server scheduler** (crontab, hosting panel), which calls the engine. **Nothing in Conn2Flow creates this schedule**: neither the installer nor the panel. Without it, no task runs.

The `cron.php` library holds what the engine, the resource compiler and the `admin-cron` panel share.

## Declaring a task

At the **root** of `<module>.json`:

```json
{
  "cron": [
    {
      "id": "trial-expiration",
      "nome": "Expire trial subscriptions",
      "funcao": "my_module_cron_expire_trials",
      "frequencia": "diario",
      "hora": "03:30",
      "parametros": { "days": 7 },
      "ativo": true
    }
  ]
}
```

| Field | |
|---|---|
| `id` | Unique across **all** modules |
| `funcao` | The PHP function. If it does not exist yet, the engine includes `gestor/modulos/<module>/<module>.cron.php`; `<module>.php` is never included |
| `frequencia` | `minutario`, `horario`, `diario` (default), `mensal` or `customizado` |
| `hora`, `dia` | `HH:MM` and day of month (1–31, `mensal` only), used to build `expressao_cron` |
| `expressao_cron` | Five fields; required for `customizado` and overrides `hora`/`dia` |
| `parametros` | Object passed to the function |
| `ativo` | `false` stores the task paused |

A task without `id` or `funcao`, with an unknown frequency or an invalid expression becomes an **orphan** at compile time and never reaches the database ([resources](../../concepts/resources.md)). In the panel, `ativo`, `expressao_cron` and `parametros` can be changed and are preserved on deploy.

## The task function

```php
// gestor/modulos/my-module/my-module.cron.php
function my_module_cron_expire_trials($parametros) {
    $days = (int)($parametros['days'] ?? 7);
    // ...
    echo "12 subscriptions expired\n";           // becomes the run log
    return ['status' => 'aviso', 'log' => '3 without e-mail']; // optional
}
```

`cron_tarefa_executar()` captures everything the function prints and its return value:
- `['status' => 'sucesso|erro|aviso', 'log' => …]` sets the result;
- `false` is an error; any other return is success;
- an exception becomes an error and **does not stop** the other tasks.

The result goes to the task's own row in `cron_tarefas` (`ultimo_disparo`, `ultima_duracao_ms`, `ultimo_status` and `ultimo_log`, cut at 4000 bytes) and to `gestor/logs/cron-<dd-mm-yyyy>.log`.

## Scheduling the engine

```
php gestor/cron.php frequencia=<frequency> [server=<domain>] [debug]
php gestor/cron.php tarefa=<id> [debug]      # one task, outside its window (a paused one does not run)
php gestor/cron.php listar
```

Register **one tick per frequency** in the server scheduler, with the library's default times (`cron_expressao_padrao()`):

```
*/10 * * * *  php /path/gestor/cron.php frequencia=minutario
0 * * * *     php /path/gestor/cron.php frequencia=horario
0 3 * * *     php /path/gestor/cron.php frequencia=diario
0 4 1 * *     php /path/gestor/cron.php frequencia=mensal
```

With a single folder with a `.env` in `autenticacoes/`, the domain is detected; with several, `server=<domain>` is required (without it, the engine exits with an error instead of running against the wrong database).

> [!WARNING]
> **The engine does not evaluate `expressao_cron`.** Each tick runs every active task of that frequency, at the time the tick runs. `hora`, `dia` and `expressao_cron` are only stored and shown in the panel. A `diario` task with `hora: "03:30"` runs when the server's `diario` tick runs. `customizado` tasks only run with a `frequencia=customizado` tick (which runs them all together) or with `tarefa=<id>`.

> [!NOTE]
> There is no lock against overlapping runs: if a `minutario` task takes longer than 10 minutes, the next tick runs it again in parallel. Long tasks need their own lock.

The `admin-cron` panel only **infers** whether there is a scheduler: whether any active task ran in the last 24 hours.

For compatibility, each tick also fires the `cron.<frequency>` hook ([hooks](../../concepts/hooks.md)).

## Helper functions

- `cron_frequencias_validas()`, `cron_status_validos()`: the vocabularies.
- `cron_expressao_valida($e)`: only the shape (five fields with digits and `* , - /`), not whether the values make sense.
- `cron_expressao_declarada($tarefa, $frequencia)`: the final expression, or `null` if invalid.
- `cron_tarefas_carregar($frequencia, $tarefaId, $todas, $campos)`: reads `cron_tarefas` (without deleted ones; without paused ones unless `$todas`).

**Internal helpers** (used by the functions above; rarely called directly): `cron_callback_preparar()`, `cron_tarefa_registrar()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/cron.php` by `c2f docs:extract` — 9 functions. Do not edit inside this block.

- `cron_frequencias_validas()` — [line 24](../../../../../gestor/bibliotecas/cron.php#L24)
- `cron_status_validos()` — [line 31](../../../../../gestor/bibliotecas/cron.php#L31)
- `cron_expressao_valida(string $expressao): bool` — [line 45](../../../../../gestor/bibliotecas/cron.php#L45)
- `cron_expressao_padrao(string $frequencia): string|null` — [line 60](../../../../../gestor/bibliotecas/cron.php#L60)
- `cron_expressao_declarada(array $tarefa, string $frequencia): string|null` — [line 85](../../../../../gestor/bibliotecas/cron.php#L85)
- `cron_callback_preparar(array $tarefa): string|null` — [line 126](../../../../../gestor/bibliotecas/cron.php#L126)
- `cron_tarefa_executar(array $tarefa): array{status: string, duracao: int, log: string}` — [line 164](../../../../../gestor/bibliotecas/cron.php#L164)
- `cron_tarefa_registrar(string $id, string $status, int $duracaoMs, string $log): void` — [line 222](../../../../../gestor/bibliotecas/cron.php#L222)
- `cron_tarefas_carregar(string|null $frequencia = null, string|null $tarefaId = null, bool $todas = false, array $campos = null): array` — [line 245](../../../../../gestor/bibliotecas/cron.php#L245)

<!-- c2f:extract:end -->
