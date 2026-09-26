---
title: "hooks.php library"
label: "Hooks"
description: "Hooks API reference: hook_do_action, hook_apply_filters, hook_has_* and the registration functions used by the pipeline."
section: reference
order: 30
sources:
  - gestor/bibliotecas/hooks.php
verified_at: f2653c2b
---

# `hooks.php` library

Always loaded by `config.php`. How it works (JSON declaration, the `hooks` table, ordering and the event catalog) is in [Hooks: actions and filters](../../concepts/hooks.md). This page is only the API reference.

## Firing

| Function | |
|---|---|
| `hook_do_action($namespace, $event, ...$args)` | Runs the actions. No return value |
| `hook_apply_filters($namespace, $event, $value, ...$args)` | Passes `$value` through each filter and returns the result |
| `hook_has_actions($namespace, $event)` / `hook_has_filters(…)` | `true` if something is registered (including in the `*` namespace) |

They all delegate to the `HookManager` (singleton), which loads from the database on the first lookup of each `namespace.event` and memoizes per request.

## Registering (pipeline use)

| Function | |
|---|---|
| `hooks_registrar_modulo($module, $plugin, $config)` | Deletes the module's hooks and reinserts the ones in the JSON `hooks` section |
| `hooks_registrar_projeto()` | The same for `project/hooks/hooks.json` (records with `projeto=1`) |
| `hooks_inserir_callbacks(…)` | Normalizes the three callback forms (string, object, list) and writes them |

Do not call these functions in a regular request: they rewrite the table. They are called by `atualizacoes_hooks_sincronizar()`, at the end of the database updater and of the deploy.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/hooks.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `hook_do_action(string $namespace, string $evento, mixed ...$args): void` — [line 357](../../../../../gestor/bibliotecas/hooks.php#L357)
- `hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed` — [line 370](../../../../../gestor/bibliotecas/hooks.php#L370)
- `hook_has_actions(string $namespace, string $evento): bool` — [line 377](../../../../../gestor/bibliotecas/hooks.php#L377)
- `hook_has_filters(string $namespace, string $evento): bool` — [line 384](../../../../../gestor/bibliotecas/hooks.php#L384)
- `hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): int` — [line 401](../../../../../gestor/bibliotecas/hooks.php#L401)
- `hooks_registrar_projeto(): int` — [line 434](../../../../../gestor/bibliotecas/hooks.php#L434)
- `hooks_inserir_callbacks(?string $modulo, ?string $plugin, string $namespace, string $evento, mixed $callbackDef, string $tipo, ?int $projeto): int` — [line 487](../../../../../gestor/bibliotecas/hooks.php#L487)

<!-- c2f:extract:end -->
