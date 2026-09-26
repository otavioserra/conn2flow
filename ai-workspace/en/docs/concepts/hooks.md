---
title: "Hooks: actions and filters"
description: "How modules, plugins and projects react to core events without changing its code: JSON declaration, the hooks table, on-demand loading and the catalog of events the core fires."
section: concepts
order: 40
sources:
  - gestor/bibliotecas/hooks.php
  - gestor/controladores/atualizacoes/atualizacoes-hooks.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
  - gestor/bibliotecas/interface.php
  - gestor/gestor.php
  - gestor/cron.php
verified_at: ef15c05d
---

# Hooks: actions and filters

Hooks let a project, plugin or module react to a core event without editing the core file. It is the WordPress model with one important difference: **there is no runtime `add_action()`**. Every hook is declared in JSON, written to the `hooks` table by the deploy and loaded from the database when the event fires.

- **Action**: side effect. The return value is ignored. `hook_do_action('namespace', 'event', ...$args)`.
- **Filter**: receives a value and returns the value (changed or not). `$v = hook_apply_filters('namespace', 'event', $v, ...$args)`.

An event is identified by **namespace + event**. The namespace is usually a module id (`admin-paginas`) or a library (`gestor`, `ia`, `html-editor`).

## Declaring a hook

### In a project: `project/hooks/hooks.json`

At the project's Gestor root, with the callbacks in `project/hooks/controllers/`:

```json
{
    "controllers": {
        "gestor": "gestor.hooks.php",
        "perfil-usuario": "perfil-usuario.hooks.php"
    },
    "actions": {
        "perfil-usuario": {
            "signup.start": "my_project_signup_start"
        }
    },
    "filters": {
        "gestor": {
            "roteador.paginas": { "callback": "my_project_router_pages", "prioridade": 5 }
        }
    }
}
```

```php
// project/hooks/controllers/gestor.hooks.php
function my_project_router_pages($paginas) {
    // ... change $paginas ...
    return $paginas; // a filter ALWAYS returns the value
}
```

### In a module or plugin: the `hooks` key of `<module>.json`

Same format, inside `"hooks": { "controllers": {…}, "actions": {…}, "filters": {…} }`. The controller file is relative to the module folder (or `plugins/<plugin>/modules/<module>/`).

### Callback forms

| Form | Example |
|---|---|
| Function name | `"editar.pagina": "my_function"` |
| Object | `{ "callback": "my_function", "prioridade": 5, "habilitado": 1 }` |
| List | `["function_a", { "callback": "function_b", "prioridade": 20 }]` |

The default priority is 10; **lower runs first**. Ties follow insertion order. `habilitado: 0` keeps the record without running it.

> [!WARNING]
> **Every namespace you use needs an entry in `controllers`.** The PHP file is included by the event's namespace, not by the function name. A filter in the `interface` namespace whose function lives in `multiusuario.hooks.php` only works if `controllers` has `"interface": "multiusuario.hooks.php"`. Without it, the callback only exists when another namespace has already included the file in the same request; in the others, **it is silently skipped** (`is_callable()` is false, no log).

## From JSON to the database

The `hooks` table is rebuilt from the JSON files, with no manual editing:

- on every `project:update-all` / `manager:update-all` (end of the database updater; it does not run with `--dry-run`);
- hooks only: `php gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --hooks-only`;
- on a project deploy through the API (`_api/project/update`) and on a system update.

Synchronization is declarative: for each module it deletes that module's hooks and reinserts the ones in the JSON (without the `hooks` key, the old ones are gone); the project ones are deleted and reinserted from `project/hooks/hooks.json`. Project records get `projeto=1` and no `modulo`.

Changed a `hooks.json`? Run the pipeline or `--hooks-only`. Otherwise the database keeps the previous version.

## At runtime

1. On the first firing of `namespace.event` in the request, the `HookManager` fetches from the table the `status='A'` and `habilitado=1` records of that namespace **and of the `*` wildcard namespace**, ordered by priority.
2. It includes (once) each record's controller.
3. It runs the callbacks. If the function requires more parameters than the event passes, the missing ones arrive as `null`.
4. An exception in a callback is written to `logs/hooks-errors.log` (and to `error_log` in development) and **does not stop** the others. In a filter, the value stays as it was before the failing callback.

To know whether it is worth building expensive data before firing: `hook_has_actions('ns', 'event')` and `hook_has_filters('ns', 'event')`.

> [!CAUTION]
> A filter that forgets its `return` turns the value into `null` for every following callback and for the core. There is no type check.

## Catalog: events the core fires

### CRUD modules (`interface.php`)

Every module that uses `interface_iniciar()`/`interface_finalizar()` fires, with namespace = **module id** and event = `<opcao>.<moment>`:

| Event | When | Arguments |
|---|---|---|
| `<opcao>.pre-banco` | POST, before the module saves | — |
| `<opcao>.parametros` | GET, before the interface builds the screen | — |
| `<opcao>.pagina` | GET, after the interface builds the screen | — |
| `excluir.banco` | after deleting | `$id` |
| `status.banco` | after changing the status | `$id`, `$status` |

`<opcao>` is `listar`, `adicionar`, `editar`, `clonar`, `visualizar`, `config`… When the module sets a `$_GESTOR['interface-opcao']` different from `opcao`, the event fires for both. Hooks get their data from `$_GESTOR`/`$_REQUEST`, since there are no arguments: a typical `editar.parametros` adds fields or changes `$_GESTOR['interface']`.

### Specific modules

| Namespace | Event | Type | Arguments |
|---|---|---|---|
| `admin-paginas` | `adicionar.banco`, `editar.banco`, `clonar.banco` | action | `$id`, array with `nome`, `caminho`, `tipo`, `modulo` (already escaped) |
| `dashboard` | `start` | action | — |
| `dashboard` | `site-toolbar.permissao-pagina` | filter | `true`, `$pagina` → whether the Live Editor bar may show |
| `perfil-usuario` | `signup.start`, `signup.pos_banco`, `signup.end` | action | — |
| `perfil-usuario` | `signup.banco` | action | `$id_usuarios`, array with `nome`, `email`, `id`, `plano`, `domain` |
| `perfil-usuario` | `signup.email` | filter | `true`, `$id_usuarios`, sign-up data → `false` skips the e-mail |
| `perfil-usuario` | `signup.redirect` | filter | URL, `$id_usuarios` |
| `admin-prompts-ia` | `padrao.update.where` | filter | WHERE clause |
| `publisher-pages` | `listar.publisher-select` | filter | list of publishers in the selector |

### Libraries and core

| Namespace | Event | Type | Filtered value / arguments |
|---|---|---|---|
| `gestor` | `roteador.paginas` | filter | the pages found for the URL ([lifecycle](request-lifecycle.md)) |
| `formulario` | `email.mensagem` | filter | the message; array with `form_id`, `language`, `origem` |
| `interface` | `formulario_campos.imagepickJS` | filter | image picker JS |
| `html-editor` | `templates.load.where`, `html_editor_include.projectJS`, `html_editor_include.imagepickJS` | filter | templates WHERE; project JS; image picker JS |
| `ia` | `config`, `models.available`, `prompts.load.where`, `prompt.option` | filter | configuration; models; prompts WHERE; option HTML (`$prompt`) |
| `cron` | `<frequency>` (`diario`, `minutario`…) | action | legacy: prefer the module's `cron` key ([resources](resources.md)) |

The `ia`, `html-editor`, `interface` and `admin-prompts-ia` filters were created to isolate data per user (multi-user): a project restricts the WHERE to the record owner.

## Details

- There is no API to register hooks from code. A plugin that needs a hook declares it in its module JSON.
- A module's synchronization deletes by **module id**, regardless of the plugin: two modules with the same id (one from the core and one from a plugin) delete each other's hooks.
- Hooks run inside the request, with the same globals: `global $_GESTOR;` works as usual.

## See also

- [Request lifecycle](request-lifecycle.md) and [interface.php library](../reference/libraries/interface.md).
- Skill `c2f-hooks-system`.
