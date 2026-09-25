---
title: "Gestor libraries"
description: "How the libraries in gestor/bibliotecas/ are registered and loaded, which ones are always loaded and how a module requests its own."
section: reference
order: 1
sources:
  - gestor/config.php
  - gestor/bibliotecas/gestor.php
  - gestor/bibliotecas/interface.php
verified_at: a6e51e29
---

# Gestor libraries

Libraries are procedural PHP files in `gestor/bibliotecas/`, each grouping functions under the same prefix (`banco_*`, `modelo_*`, `interface_*`…). There is no autoload and no namespace: they are loaded with `require_once` from a central registry.

## The registry: `$_GESTOR['bibliotecas-dados']`

In `gestor/config.php`, each logical name points to one or more files:

```php
$_GESTOR['bibliotecas-dados'] = Array(
    'banco'   => Array('banco.php'),
    'gestor'  => Array('gestor.php'),
    'modelo'  => Array('modelo.php'),
    // ...
    'cron'    => Array('cron.php'),
);
```

Modules and functions request a library by its logical name, not by file.

> [!WARNING]
> The registry has two entries without a file: `api-cliente` (`api-cliente.php`) and `cpanel` (`cpanel.php`). Neither exists in `gestor/bibliotecas/`, and requesting either one ends in a `require_once` fatal error.

## What is always loaded

`config.php` always loads `banco`, `gestor`, `modelo` and `hooks` (the `$_GESTOR['bibliotecas']` list). Anywhere in the Gestor, functions such as `banco_select()`, `gestor_variaveis()`, `modelo_var_troca()` and `hook_do_action()` are available.

## How a module requests libraries

1. The module JSON declares the list in `bibliotecas`:

```json
{ "versao": "1.0.4", "bibliotecas": ["interface", "html", "html-editor"] }
```

2. The module controller reads its JSON into `$_GESTOR['modulo#<id>']` and, in its `<module>_start()` function, calls `gestor_incluir_bibliotecas()`, which runs `require_once` on each registered file and records the name in `$_GESTOR['bibliotecas-inseridas']`.
3. In standalone code (widgets, controllers, routines), use `gestor_incluir_biblioteca('name')` or `gestor_incluir_biblioteca(['a', 'b'])`, which skips what is already included.

> [!NOTE]
> `gestor_incluir_bibliotecas()` does not check that the name exists in the registry. A misspelled library in the module JSON triggers an undefined-index warning and is not loaded. `gestor_incluir_biblioteca()`, on the other hand, silently ignores names without a path.

Libraries may also have their own AJAX routines: `interface.php` forwards interface AJAX requests to `html_editor_ajax_interface()` when `html-editor` is among the Gestor's or the module's libraries.

`cron.php` runs outside the module lifecycle and therefore includes its libraries directly instead of using `gestor_incluir_bibliotecas()`.

## Reference per library

Each library has a page with the function list generated from the code (`c2f docs:extract`) and an explanation of its actual behavior. See the "Reference → Libraries" menu.
