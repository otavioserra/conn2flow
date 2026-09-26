---
title: "lang.php library"
label: "CLI translations"
description: "JSON dictionaries and __t() for the messages of command-line scripts (updater, compiler, plugins). It is not the site translation."
section: reference
order: 210
sources:
  - gestor/bibliotecas/lang.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: c267f123
---

# `lang.php` library

`lang.php` translates the **messages of internal scripts**: the database updater, the resource compiler, the plugin installer. It plays no part in translating the site or the admin panel, which use [text variables](gestor.md) (`gestor_variaveis()`) and the `resources/<language>/` folder.

It is not in the library registry: the scripts include it with `require_once`.

## How it works

- When included, it sets `$GLOBALS['lang']` (default `pt-br`) and loads `$GLOBALS['dicionario']` with `carregar_dicionario()`.
- `carregar_dicionario($lang = 'pt-br', $base = '')` reads `gestor/bibliotecas<base>/<lang>.json`. **That file does not exist in the `bibliotecas/` folder**, so the default dictionary is always empty.
- `set_lang($lang)` changes the language and reloads the dictionary (empty, for the same reason).
- `__t($key, $replacements = [])` returns the translation or, without one, **the key itself**. Each replacement substitutes `{name}` and `:name`.

The real dictionaries live next to each script, which merges them after `set_lang()`:

```php
set_lang('pt-br');
$file = __DIR__ . '/lang/' . $GLOBALS['lang'] . '.json';
if (is_file($file)) {
    $GLOBALS['dicionario'] = array_merge($GLOBALS['dicionario'], json_decode(file_get_contents($file), true));
}
echo __t('_compare_summary', ['tabela' => 'paginas', 'ins' => 3]);
```

Existing folders: `controladores/atualizacoes/lang/`, `controladores/plugins/lang/` and `controladores/agents/arquitetura/lang/`.

## Pitfalls

- The resource compiler (`atualizacao-dados-recursos.php`) calls `set_lang('pt-br')` but does **not merge** its `lang/` folder: its messages come out as the raw key (`_map_file_not_found`).
- The `:name` replacement also hits prefixes: with `['id' => 5]`, `:identifier` becomes `5entifier`. Prefer `{name}`.
- The functions are declared inside `if (!function_exists(…))`: if other code already defined `__t()`, this library's version is ignored.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/lang.php` by `c2f docs:extract` — 3 functions. Do not edit inside this block.

- `carregar_dicionario($lang = 'pt-br', $base = '')` — [line 25](../../../../../gestor/bibliotecas/lang.php#L25)
- `__t($key, $replacements = [])` — [line 68](../../../../../gestor/bibliotecas/lang.php#L68)
- `set_lang($lang)` — [line 92](../../../../../gestor/bibliotecas/lang.php#L92)

<!-- c2f:extract:end -->
