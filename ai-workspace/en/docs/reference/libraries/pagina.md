---
title: "pagina.php library"
description: "Shortcuts over $_GESTOR['pagina']: cutting cells, replacing global variables and masking markers for the database."
section: reference
order: 100
sources:
  - gestor/bibliotecas/pagina.php
  - gestor/config.php
verified_at: a6e51e29
---

# The `pagina.php` library

`pagina.php` gathers shortcuts over `$_GESTOR['pagina']`, the HTML of the page the module is building, and over the global variable markers. Every function is a thin layer over the [modelo.php library](modelo.md).

It is not loaded by default. Request `pagina` in the module JSON (`admin-paginas`, `pages-index` and `publisher-pages` do this) or use `gestor_incluir_biblioteca('pagina')`.

## Global variable markers

The markers are defined in `gestor/config.php`:

| `$_GESTOR['variavel-global']` key | Value | Use |
|---|---|---|
| `open` / `close` | `@[[` / `]]@` | Form **stored in the database** and resolved at runtime |
| `openText` / `closeText` | `[[` / `]]` | Form **typed** by users in the editors |

## Page cells

- `pagina_celula($nome, $comentario = false, $apagar = false)` cuts the `<!-- name < -->…<!-- name > -->` block out of `$_GESTOR['pagina']` and returns its content. In its place it leaves the `<!-- name -->` marker (to re-insert with `pagina_celula_incluir()`), or nothing when `$apagar` is `true`. With `$comentario = true`, the block format becomes `<!-- name [[ … ]] name -->` and the marker left behind is `<!-- [[name]] -->`.
- `pagina_celula_incluir($celula, $valor)` inserts `$valor` **before** the `<!-- cell -->` marker and keeps the marker, so it can be called in a loop to repeat rows.

> [!NOTE]
> This is the same "cell" pattern as `modelo_tag_val()` + `modelo_tag_in()` + `modelo_var_in()`, with the same limits: only the first occurrence of the block is handled. Today the only core user is `perfil-usuario`, which calls `pagina_celula($name, false, true)` to **remove** the `bloqueado-mensagem` and `formulario` blocks depending on access.

## Replacing global variables

- `pagina_trocar_variavel_valor($variavel, $valor, $variavelEspecifica = false)` replaces `@[[variable]]@` with `$valor` across `$_GESTOR['pagina']`. With `$variavelEspecifica = true`, it looks for the literal string `$variavel` without adding the markers.
- `pagina_celula_trocar_variavel_valor($celula, $variavel, $valor, $variavelEspecifica = false)` does the same on a cell string and returns the result (a `null` value becomes empty).
- `pagina_trocar_variavel(['codigo' => …, 'variavel' => …, 'valor' => …])` does the same on any string. It returns `null` when any of the three parameters is missing.

All three use `modelo_var_troca_tudo()`: they replace **every** occurrence, case-insensitively. `pagina_trocar_variavel_valor()` does nothing when `$valor` is `null`.

## Masking for the database

- `pagina_variaveis_globais_mascarar(['valor' => …])` turns `[[x]]` into `@[[x]]@`: this is what happens when an editor saves content typed by the user.
- `pagina_variaveis_globais_desmascarar(['valor' => …])` does the reverse, `@[[x]]@` → `[[x]]`, for display in the editor.

> [!WARNING]
> Both functions apply `strtolower()` to the **replacement pattern** (`"@[[$1]]@"`), not to the captured name. The variable name **keeps its original case**, despite what the code suggests. Modules that mask on their own (for example `publisher` and `publisher-pages`) use the same expression and behave the same way.

None of the variable functions has callers in the core today. Modules do the same work directly with `modelo_var_troca_tudo()` and `preg_replace()`.

## See also

- [modelo.php library](modelo.md)
- [Gestor libraries](index.md)

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/pagina.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `pagina_celula(string $nome, bool $comentario = false, bool $apagar = false): string` — [line 39](../../../../../gestor/bibliotecas/pagina.php#L39)
- `pagina_celula_trocar_variavel_valor(string $celula, string $variavel, string $valor, bool $variavelEspecifica = false): string` — [line 75](../../../../../gestor/bibliotecas/pagina.php#L75)
- `pagina_celula_incluir(string $celula, string $valor): void` — [line 106](../../../../../gestor/bibliotecas/pagina.php#L106)
- `pagina_trocar_variavel_valor(string $variavel, string $valor, bool $variavelEspecifica = false): void` — [line 128](../../../../../gestor/bibliotecas/pagina.php#L128)
- `pagina_trocar_variavel(array|false $params = false): string|null` — [line 160](../../../../../gestor/bibliotecas/pagina.php#L160)
- `pagina_variaveis_globais_mascarar(array|false $params = false): string` — [line 189](../../../../../gestor/bibliotecas/pagina.php#L189)
- `pagina_variaveis_globais_desmascarar(array|false $params = false): string` — [line 223](../../../../../gestor/bibliotecas/pagina.php#L223)

<!-- c2f:extract:end -->
