---
title: "Biblioteca hooks.php"
description: "Referência da API de hooks: hook_do_action, hook_apply_filters, hook_has_* e as funções de registro usadas pelo pipeline."
section: reference
order: 30
sources:
  - gestor/bibliotecas/hooks.php
verified_at: f2653c2b
---

# Biblioteca `hooks.php`

Carregada sempre pelo `config.php`. O funcionamento (declaração em JSON, tabela `hooks`, ordem e catálogo de eventos) está em [Hooks: actions e filters](../../concepts/hooks.md). Esta página é só a referência da API.

## Disparar

| Função | |
|---|---|
| `hook_do_action($namespace, $evento, ...$args)` | Executa as actions. Sem retorno |
| `hook_apply_filters($namespace, $evento, $valor, ...$args)` | Passa `$valor` por cada filter e devolve o resultado |
| `hook_has_actions($namespace, $evento)` / `hook_has_filters(…)` | `true` se há algo registrado (inclusive no namespace `*`) |

Todas delegam ao `HookManager` (singleton), que carrega do banco na primeira consulta de cada `namespace.evento` e memoriza por requisição.

## Registrar (uso do pipeline)

| Função | |
|---|---|
| `hooks_registrar_modulo($modulo, $plugin, $config)` | Apaga os hooks do módulo e reinsere os da seção `hooks` do JSON |
| `hooks_registrar_projeto()` | O mesmo para `project/hooks/hooks.json` (registros com `projeto=1`) |
| `hooks_inserir_callbacks(…)` | Normaliza as três formas de callback (texto, objeto, lista) e grava |

Não chame essas funções numa requisição comum: elas reescrevem a tabela. Quem as chama é `atualizacoes_hooks_sincronizar()`, no fim do atualizador de banco e do deploy.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/hooks.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `hook_do_action(string $namespace, string $evento, mixed ...$args): void` — [linha 357](../../../../../gestor/bibliotecas/hooks.php#L357)
- `hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed` — [linha 370](../../../../../gestor/bibliotecas/hooks.php#L370)
- `hook_has_actions(string $namespace, string $evento): bool` — [linha 377](../../../../../gestor/bibliotecas/hooks.php#L377)
- `hook_has_filters(string $namespace, string $evento): bool` — [linha 384](../../../../../gestor/bibliotecas/hooks.php#L384)
- `hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): int` — [linha 401](../../../../../gestor/bibliotecas/hooks.php#L401)
- `hooks_registrar_projeto(): int` — [linha 434](../../../../../gestor/bibliotecas/hooks.php#L434)
- `hooks_inserir_callbacks(?string $modulo, ?string $plugin, string $namespace, string $evento, mixed $callbackDef, string $tipo, ?int $projeto): int` — [linha 487](../../../../../gestor/bibliotecas/hooks.php#L487)

<!-- c2f:extract:end -->
