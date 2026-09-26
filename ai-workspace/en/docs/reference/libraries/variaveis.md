---
title: "variaveis.php library"
label: "Variables (legacy)"
description: "Reading and writing system variables (module _sistema) in the variaveis table — a library unused by the core, distinct from the gestor_variaveis() text system."
section: reference
order: 100
sources:
  - gestor/bibliotecas/variaveis.php
  - gestor/config.php
  - gestor/gestor.php
verified_at: a6e51e29
---

# The `variaveis.php` library

`variaveis.php` reads and writes **system variables**: records of the `variaveis` table with `modulo = '_sistema'`, organized by `grupo`.

> [!IMPORTANT]
> Do not confuse it with the Gestor's text and label system. Interface texts (`form-name-label`, messages, alerts) are read by `gestor_variaveis()`, which lives in `gestor.php` and is always loaded. That is what modules use. `variaveis.php` is something else, and today it **has no callers in the core**; the core data (`gestor/db/data/VariaveisData.json`) contains no `_sistema` variable.

The library is registered as `variaveis` in `$_GESTOR['bibliotecas-dados']`. The only place in the core that includes it is the language selector in `gestor.php`, and even there the functions used are `gestor_variaveis()`: the include is unnecessary.

## Functions

- `variaveis_sistema($grupo, $id = false)` loads **every** variable of the group in a single query and caches it in the global `$_VARIAVEIS_SISTEMA[$grupo]`. With `$id`, it returns the value (or `null` when missing). Without `$id`, it returns the group's `id => value` array (empty when there is nothing).
- `variaveis_sistema_incluir($grupo, $id, $valor, $tipo = 'string')` creates the variable only when it does not exist yet (it never updates an existing one). A `null` `$valor` is stored as `NULL`.
- `variaveis_sistema_atualizar($grupo, $id, $valor)` runs the `UPDATE` of the value (`null` stores `NULL`).

## Caveats

> [!WARNING]
> `$grupo` and `$id` go into SQL **unescaped** in all three functions (only the value in `variaveis_sistema_atualizar()` goes through `banco_escape_field()`, and the one in `variaveis_sistema_incluir()` through `banco_insert_name_campo()`). Never pass user input in those parameters.

- The queries **do not filter by `language`**. If the same variable exists in more than one language, `variaveis_sistema()` keeps the last record read.
- `variaveis_sistema_atualizar()` **does not update the cache**: within the same request, `variaveis_sistema()` keeps returning the old value. The function declares `global $_VARIAVEIS_id`, which is never used.

## See also

- [Gestor libraries](index.md): registry and loading.

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/variaveis.php` by `c2f docs:extract` — 3 functions. Do not edit inside this block.

- `variaveis_sistema(string $grupo, string|false $id = false): string|array|null` — [line 41](../../../../../gestor/bibliotecas/variaveis.php#L41)
  Retorna variável(is) do sistema.
  Parameters:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID específico da variável (opcional).
  Returns: Se $id fornecido, retorna o valor da variável específica.
- `variaveis_sistema_incluir(string $grupo, string $id, string $valor, string $tipo = 'string'): void` — [line 88](../../../../../gestor/bibliotecas/variaveis.php#L88)
  Inclui uma nova variável do sistema.
  Parameters:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID da variável (obrigatório).
  - `$valor`: Valor que será incluído (obrigatório).
  - `$tipo`: Tipo da variável (opcional, padrão: 'string').
- `variaveis_sistema_atualizar(string $grupo, string $id, string $valor): void` — [line 132](../../../../../gestor/bibliotecas/variaveis.php#L132)
  Atualiza o valor de uma variável do sistema.
  Parameters:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID da variável (obrigatório).
  - `$valor`: Novo valor que será atribuído (obrigatório).

<!-- c2f:extract:end -->
