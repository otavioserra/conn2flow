---
title: "modelo.php library"
label: "HTML templates"
description: "Variable replacement and delimited-block handling in HTML templates — the foundation of every screen the Gestor renders."
section: reference
order: 20
sources:
  - gestor/bibliotecas/modelo.php
verified_at: c267f123
---

# The `modelo.php` library

`modelo.php` is the Gestor's template library. Everything Conn2Flow renders on the server goes through it: pages, layouts, components and widget responses are **HTML strings** where code replaces markers with values and cuts, repeats or removes blocks. There is no template engine, AST or automatic escaping — only plain string functions.

The library is loaded during the Gestor bootstrap and registers its version in `$_GESTOR['biblioteca-modelo']` (currently `1.1.0`).

> [!WARNING]
> No function escapes HTML. Every value coming from users or from the database must be handled beforehand (for example, with `htmlspecialchars()`), or it becomes XSS.

## The two kinds of marker

| Marker | Format | Used for |
|---|---|---|
| Variable | Any literal string, by convention `#name#` or `@[[group#name]]@` | A single value |
| Block ("cell") | A pair of comments `<!-- name < -->` … `<!-- name > -->` | A fragment to extract, repeat, replace or remove |

The functions enforce no format: they look for exactly the string they receive. The block comment convention is followed by core modules and widgets.

## Replacing variables

| Function | Occurrences | Case |
|---|---|---|
| `modelo_var_troca($modelo, $var, $valor)` | **first** only | sensitive |
| `modelo_var_troca_fim($modelo, $var, $valor)` | **last** only | insensitive |
| `modelo_var_troca_tudo($modelo, $var, $valor)` | **all** | insensitive |
| `modelo_var_in($modelo, $var, $valor)` | inserts **before** the first one, keeping the variable | sensitive |

When the variable is not found, the template comes back unchanged; there is no error or warning.

`modelo_var_troca()` and `modelo_var_troca_tudo()` also accept an associative array instead of `$var`. Each **key is used literally** as the marker; no `#` is added, despite what the function's docblock says:

```php
$html = modelo_var_troca_tudo($html, [
    '#titulo#' => $titulo,
    '#url#'    => $url,
]);
```

`modelo_var_troca_tudo()` uses `str_ireplace()` on purpose. The old `preg_replace()` version treated `$19` inside the value as a group reference and corrupted prices such as `$19.97`.

## Working with blocks

| Function | Result |
|---|---|
| `modelo_tag_val($modelo, $in, $out)` | Returns the **content** between the tags (or `''`) |
| `modelo_tag_in($modelo, $in, $out, $valor)` | Replaces the **whole block, tags included**, with `$valor` |
| `modelo_tag_troca_val($modelo, $in, $out, $valor)` | Replaces only the **content**, keeping the tags |
| `modelo_tag_del($modelo, $in, $out)` | Removes the whole block, tags included |

> [!IMPORTANT]
> All four functions act **only on the first occurrence**, and the closing tag is also searched from the beginning of the text. With two blocks of the same name, `modelo_tag_del()` removes only the first; with a closing tag before the opening one, the cut goes wrong. Use unique block names per template.

## The repetition ("cell") pattern

This is how core modules build lists on the server: extract the block as a template, leave a marker in its place, and insert each row before the marker.

```php
$cel_nome = 'linha';
// 1. Keep the template and leave a marker where the block was.
$cel = modelo_tag_val($_GESTOR['pagina'], '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->', '<!-- '.$cel_nome.' -->');

// 2. For each record, fill a copy and insert it before the marker (order is preserved).
foreach ($registros as $r) {
    $linha = modelo_var_troca_tudo($cel, '#nome#', htmlspecialchars($r['nome']));
    $_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'], '<!-- '.$cel_nome.' -->', $linha);
}

// 3. Remove the marker.
$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '<!-- '.$cel_nome.' -->', '');
```

The same pair of functions handles conditional blocks: `modelo_tag_del()` drops the section when the condition is false — for example, the `publisher` module does this with its `templates-todos-linkados-msg` messages.

## Legacy functions

- `modelo_abrir($modelo_local)` reads a file and removes the development block `<!--!#del#(#!-->` … `<!--!#del#)#!-->`. It has no callers in the core: templates live in the **database** and in `resources/`, not in files read at runtime. It also removes only the first block.
- `modelo_input_in($modelo, $in, $out, $valor)` calls `paginaTrocaVarValor()`, which **does not exist** in the core. Calling it is a fatal error. It has no callers; do not use it.

## See also

- [Menus module](../modules/menus.md) — a widget that uses `<!-- item < -->` blocks with its own rules.
- [How to write documentation](../../guides/documentation.md)

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/modelo.php` by `c2f docs:extract` — 10 functions. Do not edit inside this block.

- `modelo_input_in(string $modelo, string $name_input_in, string $name_input_out, string $valor): string` — [line 33](../../../../../gestor/bibliotecas/modelo.php#L33)
  Substitui variáveis de input em um modelo HTML.
  Parameters:
  - `$modelo`: O template HTML onde as substituições serão feitas.
  - `$name_input_in`: Nome do placeholder de entrada (ex: 'campo').
  - `$name_input_out`: Nome do campo de saída que substituirá o placeholder.
  - `$valor`: Valor a ser atribuído ao campo.
  Returns: Retorna o modelo com as variáveis substituídas.
- `modelo_var_troca(string $modelo, string|array $var, string $valor = null): string` — [line 57](../../../../../gestor/bibliotecas/modelo.php#L57)
  Substitui a primeira ocorrência de uma variável em um modelo.
  Parameters:
  - `$modelo`: O template onde a substituição será feita.
  - `$var`: A variável/placeholder a ser substituída, ou array de variáveis.
  - `$valor`: O valor que substituirá a variável (ignorado se $var for array).
  Returns: Retorna o modelo com a primeira ocorrência da variável substituída.
- `modelo_var_troca_fim(string $modelo, string $var, string $valor): string` — [line 101](../../../../../gestor/bibliotecas/modelo.php#L101)
  Substitui a última ocorrência de uma variável em um modelo.
  Parameters:
  - `$modelo`: O template onde a substituição será feita.
  - `$var`: A variável/placeholder a ser substituída.
  - `$valor`: O valor que substituirá a variável.
  Returns: Retorna o modelo com a última ocorrência da variável substituída.
- `modelo_var_troca_tudo(string $modelo, string|array $var, string $valor = null): string` — [line 137](../../../../../gestor/bibliotecas/modelo.php#L137)
  Substitui todas as ocorrências de uma variável em um modelo.
  Parameters:
  - `$modelo`: O template onde as substituições serão feitas.
  - `$var`: A variável/placeholder a ser substituída, ou array de variáveis.
  - `$valor`: O valor que substituirá todas as ocorrências da variável (ignorado se $var for array).
  Returns: Retorna o modelo com todas as ocorrências da variável substituídas.
- `modelo_var_in(string $modelo, string $var, string $valor): string` — [line 165](../../../../../gestor/bibliotecas/modelo.php#L165)
  Insere um valor antes da variável mantendo a variável no modelo.
  Parameters:
  - `$modelo`: O template onde a inserção será feita.
  - `$var`: A variável/placeholder de referência.
  - `$valor`: O valor a ser inserido antes da variável.
  Returns: Retorna o modelo com o valor inserido antes da variável.
- `modelo_tag_val(string $modelo, string $tag_in, string $tag_out): string` — [line 199](../../../../../gestor/bibliotecas/modelo.php#L199)
  Extrai o conteúdo entre duas tags em um modelo.
  Parameters:
  - `$modelo`: O template de onde o conteúdo será extraído.
  - `$tag_in`: A tag de abertura.
  - `$tag_out`: A tag de fechamento.
  Returns: Retorna o conteúdo entre as tags ou string vazia se não encontrado.
- `modelo_tag_in(string $modelo, string $tag_in, string $tag_out, string $valor): string` — [line 231](../../../../../gestor/bibliotecas/modelo.php#L231)
  Substitui um bloco delimitado por tags incluindo as próprias tags.
  Parameters:
  - `$modelo`: O template onde a substituição será feita.
  - `$tag_in`: A tag de abertura do bloco.
  - `$tag_out`: A tag de fechamento do bloco.
  - `$valor`: O valor que substituirá todo o bloco (tags + conteúdo).
  Returns: Retorna o modelo com o bloco substituído pelo valor.
- `modelo_tag_del(string $modelo, string $tag_in, string $tag_out): string` — [line 267](../../../../../gestor/bibliotecas/modelo.php#L267)
  Remove um bloco delimitado por tags incluindo as próprias tags.
  Parameters:
  - `$modelo`: O template de onde o bloco será removido.
  - `$tag_in`: A tag de abertura do bloco a ser removido.
  - `$tag_out`: A tag de fechamento do bloco a ser removido.
  Returns: Retorna o modelo sem o bloco especificado.
- `modelo_tag_troca_val(string $modelo, string $tag_in, string $tag_out, string $valor): string` — [line 305](../../../../../gestor/bibliotecas/modelo.php#L305)
  Substitui apenas o conteúdo entre as tags, preservando as tags.
  Parameters:
  - `$modelo`: O template onde a substituição será feita.
  - `$tag_in`: A tag de abertura (será preservada).
  - `$tag_out`: A tag de fechamento (será preservada).
  - `$valor`: O novo conteúdo que ficará entre as tags.
  Returns: Retorna o modelo com o conteúdo entre as tags substituído.
- `modelo_abrir(string $modelo_local): string` — [line 340](../../../../../gestor/bibliotecas/modelo.php#L340)
  Carrega um arquivo de template e remove blocos de exclusão.
  Parameters:
  - `$modelo_local`: Caminho do arquivo de template a ser carregado.
  Returns: Retorna o conteúdo do template com os blocos de exclusão removidos.

<!-- c2f:extract:end -->
