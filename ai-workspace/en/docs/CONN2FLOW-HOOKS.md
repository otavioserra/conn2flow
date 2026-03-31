# Conn2Flow Hooks System

## Overview

The Conn2Flow hooks system is inspired by the WordPress actions and filters mechanism. It allows modules and projects to **intercept and react to events** from other modules **without modifying the source module's code**.

> **Analogy:** Think of hooks as electrical outlets. The core module installs the outlet (`hook_do_action`). Any other module can "plug in" its function via JSON — when the event fires, all connected plugs are triggered.

---

## Core Concepts

### Action vs Filter

| Type       | Purpose                                        | Return value        | PHP Function           |
|------------|------------------------------------------------|---------------------|------------------------|
| **Action** | Pure side-effect (log, email, UI widget, etc.) | `void`              | `hook_do_action()`     |
| **Filter** | Value transformation (modifies data)           | `mixed` (new value) | `hook_apply_filters()` |

### Namespace and Event

Every hook is identified by two components:

```
namespace . event
```

- **Namespace** → identifies the module or context emitting the event (e.g., `admin-paginas`, `produtos`, `global`).
- **Event** → describes the specific action (e.g., `adicionar.banco`, `editar.pagina`, `excluir.banco`).

Special namespace `*` → registers the hook for **any** namespace.

---

## Architecture

```
Module/project JSON
        │
        ▼
atualizacoes_hooks_sincronizar()   ← deploy/update pipeline
        │
        ▼
`hooks` table (database)
        │
        ▼
hook_do_action() / hook_apply_filters()
        │
        ▼
HookManager::getInstance()->doAction() / applyFilters()
        │ lazy-load per namespace+event
        ▼
PHP callback executed
```

### HookManager (Singleton)

- `HookManager` class, single instance per request.
- **Lazy loading**: only queries the database when `hook_do_action()` is called for the first time for a given namespace+event.
- Automatically includes the PHP controller file mapped in JSON before executing the callback.
- Errors logged to `logs/hooks-errors.log`.

---

## `hooks` Table

Created by migration `20260630100000_create_hooks_table.php`.

| Column             | Type         | Description                                                      |
|--------------------|--------------|------------------------------------------------------------------|
| `id_hooks`         | INT (PK)     | Primary key                                                      |
| `modulo`           | VARCHAR(255) | Module ID that registered the hook (NULL = project hook)         |
| `plugin`           | VARCHAR(255) | Plugin ID (if the module belongs to a plugin)                    |
| `namespace`        | VARCHAR(255) | Target namespace (e.g., `paginas`, `global`, `*`)                |
| `evento`           | VARCHAR(255) | Specific event (e.g., `editar`, `adicionar.banco`)               |
| `callback`         | VARCHAR(500) | PHP function name to be called                                   |
| `tipo`             | VARCHAR(10)  | `action` or `filter`                                             |
| `prioridade`       | SMALLINT     | Execution order — lower = executed first (default: 10)           |
| `habilitado`       | TINYINT      | `1` = active, `0` or NULL = disabled                             |
| `projeto`          | TINYINT      | `1` = came from `project/hooks/hooks.json`                       |
| `status`           | CHAR(1)      | `A` = active, `I` = inactive                                     |
| `data_criacao`     | DATETIME     | Created automatically                                            |
| `data_modificacao` | DATETIME     | Updated automatically                                            |

---

## Execution API (4 Global Functions)

### `hook_do_action()`

```php
hook_do_action(string $namespace, string $evento, mixed ...$args): void
```

Executes all **action** callbacks registered for the namespace+event.

**Important:** Callbacks with fewer parameters than the `$args` passed are compatible — extra arguments are ignored. Callbacks with more required parameters than the available `$args` receive `null` for missing arguments (via `ReflectionFunction`).

---

### `hook_apply_filters()`

```php
hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed
```

Applies all **filter** callbacks registered for the namespace+event to `$value` and returns the transformed value.

---

### `hook_has_actions()`

```php
hook_has_actions(string $namespace, string $evento): bool
```

Checks if any action is registered (useful to avoid unnecessary processing).

---

### `hook_has_filters()`

```php
hook_has_filters(string $namespace, string $evento): bool
```

Checks if any filter is registered.

---

## Native Platform Events

The `interface.php` library automatically fires the following hooks for **every module** using the standard interface system:

- **Default**:
| Namespace     | Event              | When fired                              | Args |
|---------------|--------------------|-----------------------------------------|------|
| `{modulo-id}` | `{opcao}.{evento}` | Virtually all events are mapped         | —    |

- **CRUD Examples**:
| Namespace     | Event                 | When fired                                       | Args                  |
|---------------|-----------------------|--------------------------------------------------|-----------------------|
| `{modulo-id}` | `adicionar.pre-banco` | Before INSERT (validation/data pre-processing)   | —                     |
| `{modulo-id}` | `adicionar.banco`     | After successful INSERT                          | `$id`, `$dados[]`     |
| `{modulo-id}` | `adicionar.parametros`| Before rendering the add form (GET)              | —                     |
| `{modulo-id}` | `adicionar.pagina`    | After rendering the add form (GET)               | —                     |
| `{modulo-id}` | `editar.pre-banco`    | Before UPDATE                                    | —                     |
| `{modulo-id}` | `editar.banco`        | After successful UPDATE                          | `$id`, `$dados[]`     |
| `{modulo-id}` | `editar.parametros`   | Before rendering the edit form (GET)             | —                     |
| `{modulo-id}` | `editar.pagina`       | After rendering the edit form (GET)              | —                     |
| `{modulo-id}` | `excluir.banco`       | After successful DELETE                          | `$id`                 |
| `{modulo-id}` | `status.banco`        | After status change                              | `$id`, `$new_status`  |
| `{modulo-id}` | `clonar.banco`        | After clone/duplicate                            | `$id`, `$dados[]`     |
| `{modulo-id}` | `clonar.parametros`   | Before rendering the clone form (GET)            | —                     |
| `{modulo-id}` | `clonar.pagina`       | After rendering the clone form (GET)             | —                     |

> `{modulo-id}` is the value of `$_GESTOR['modulo-id']` of the running module (e.g., `admin-paginas`, `produtos`).

---

## Registering Hooks via JSON

JSON files are the single source of truth. **Never register hooks directly in the database** — they will be overwritten on the next sync.

### 1. Module Hook (in `modulos/<module>/<module>.json`)

```json
{
    "hooks": {
        "controllers": {
            "admin-paginas": "my-module.hooks.php"
        },
        "actions": {
            "admin-paginas": {
                "adicionar.banco": "my_module_page_added_hook",
                "editar.banco": {
                    "callback": "my_module_page_edited_hook",
                    "prioridade": 5,
                    "habilitado": 1
                }
            }
        },
        "filters": {
            "admin-paginas": {
                "titulo.formatar": "my_module_format_title_filter"
            }
        }
    }
}
```

### 2. Project Hook (in `project/hooks/hooks.json`)

```json
{
    "controllers": {
        "admin-paginas": "admin-paginas.hooks.php"
    },
    "actions": {
        "admin-paginas": {
            "adicionar.pagina": {
                "callback": "project_page_added_hook",
                "prioridade": 5,
                "habilitado": 1
            },
            "excluir.banco": "project_page_deleted_hook"
        }
    },
    "filters": {}
}
```

### 3. Callback Definition Formats

#### Simple string (priority 10, enabled by default)
```json
"adicionar.banco": "function_name"
```

#### Object with options
```json
"adicionar.banco": {
    "callback": "function_name",
    "prioridade": 5,
    "habilitado": 1
}
```

#### Array of callbacks (multiple listeners on the same event)
```json
"adicionar.banco": [
    "first_function",
    {
        "callback": "second_function",
        "prioridade": 20,
        "habilitado": 1
    }
]
```

---

## The `habilitado` Field

The `habilitado` field in JSON controls whether the hook will be **loaded** by `HookManager` at runtime.

- `"habilitado": 1` → hook executes normally.
- `"habilitado": 0` → hook is synced to the database BUT **not loaded** (`loadFromDb` filters with `AND habilitado=1`).

This allows temporarily disabling a callback without removing its JSON definition. To re-enable, change to `1` and redeploy/sync.

---

## File Structure

```
project/
└── hooks/
    ├── hooks.json                       ← project hook definitions
    └── controllers/
        └── admin-paginas.hooks.php      ← callback functions (hooks controller)

modulos/
└── my-module/
    ├── my-module.json                   ← contains "hooks" section
    └── my-module.hooks.php              ← callback functions (hooks controller)
```

---

## Synchronization Pipeline

Synchronization runs automatically at two points:

1. **Project deploy** → `api.php` calls `atualizacoes_hooks_sincronizar(['apenas_projeto' => true])` after receiving the update package via `/_api/project/update`.
2. **Full system update** → `atualizacoes-sistema.php` `hookAfterAll()` calls `atualizacoes_hooks_sincronizar()` (modules + plugins + project).

Synchronization is **idempotent** — can be called multiple times without side effects.

```php
// Sync everything (modules + plugins + project) — recommended!
atualizacoes_hooks_sincronizar();

// Sync only the project if needed (project-specific hooks, without touching module/plugin hooks)
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);

// Sync only modules/plugins (no project)
atualizacoes_hooks_sincronizar(['apenas_modulos' => true]);
```

---

## Practical Use Cases

### Case 1 — Social Integration When Adding a Page

**Scenario:** The `social-connections` module needs to be notified whenever a new page is created in `admin-paginas` to display a social networks configuration widget.

**`project/hooks/hooks.json`:**
```json
{
    "controllers": {
        "admin-paginas": "admin-paginas.hooks.php"
    },
    "actions": {
        "admin-paginas": {
            "adicionar.pagina": {
                "callback": "social_connections_pages_add_hook",
                "prioridade": 5,
                "habilitado": 1
            }
        }
    },
    "filters": {}
}
```

**Modifying page content using a hook — `project/hooks/controllers/admin-paginas.hooks.php`:**
```php
function social_connections_pages_add_hook(): void {
    global $_GESTOR;

    $_GESTOR['pagina'] .= '<div class="social-integration-widget">';
    $_GESTOR['pagina'] .= '<h3>Social Integration</h3>';
    $_GESTOR['pagina'] .= '<p>Configure social connections for this page.</p>';
    $_GESTOR['pagina'] .= '</div>';
}
```

**How it works:**
When `admin-paginas` renders the add form (GET), `interface.php` fires:
```php
hook_do_action('admin-paginas', 'adicionar.pagina');
```
`HookManager` loads the controller and executes `social_connections_pages_add_hook()` to inject the social integration widget into the page.

---

### Case 2 — Audit Log on Any Record Edit

**Scenario:** An audit module wants to log every edit made to pages.

**`modulos/audit/audit.json`:**
```json
{
    "hooks": {
        "controllers": {
            "admin-paginas": "audit.hooks.php"
        },
        "actions": {
            "admin-paginas": {
                "editar.banco": "audit_pages_edit_hook"
            }
        }
    }
}
```

**`modulos/audit/audit.hooks.php`:**
```php
function audit_pages_edit_hook(string $id, array $data = []): void {
    global $_GESTOR;
    $logPath = $_GESTOR['logs-path'] . 'audit.log';
    $ts = date('Y-m-d H:i:s');
    $msg = "[{$ts}] Page ID={$id} edited. Name: " . ($data['nome'] ?? '?') . PHP_EOL;
    file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
}
```

**How it is fired by the emitter (`admin-paginas.php`):**
```php
hook_do_action($_GESTOR['modulo-id'], 'editar.banco', $id, [
    'nome'    => $nome,
    'caminho' => $caminho,
]);
```

---

### Case 3 — Filter to Modify a Title

**Scenario:** The project needs to automatically title-case page titles before saving.

**`project/hooks/hooks.json`:**
```json
{
    "controllers": {
        "admin-paginas": "formatting.hooks.php"
    },
    "filters": {
        "admin-paginas": {
            "titulo.salvar": {
                "callback": "formatting_title_titlecase_filter",
                "prioridade": 1
            }
        }
    },
    "actions": {}
}
```

**`project/hooks/controllers/formatting.hooks.php`:**
```php
function formatting_title_titlecase_filter(string $title): string {
    return mb_convert_case(trim($title), MB_CASE_TITLE, 'UTF-8');
}
```

**Usage in the emitting module:**
```php
$title = hook_apply_filters($_GESTOR['modulo-id'], 'titulo.salvar', $title_raw);
// $title is now formatted before going to the database
```

---

### Case 4 — Global Hook (Wildcard `*`)

If you need a middleware that reacts to an event fired by **any module**, use the `*` namespace:

**Scenario:** A metrics module wants to count **every** type of record addition across the system.

**`modulos/metrics/metrics.json`:**
```json
{
    "hooks": {
        "controllers": {
            "*": "metrics.hooks.php"
        },
        "actions": {
            "*": {
                "adicionar.banco": "metrics_count_addition_hook"
            }
        }
    }
}
```

**`modulos/metrics/metrics.hooks.php`:**
```php
function metrics_count_addition_hook(string $id = null): void {
    // Executed for ANY module that fires 'adicionar.banco'
    metrics_increment_counter('total_additions');
}
```

---

### Case 5 — Temporarily Disabling a Hook

In `hooks.json`, set `"habilitado": 0`. On the next sync, the hook still exists in the database but is not loaded at runtime:

```json
"adicionar.pagina": {
    "callback": "social_connections_pages_add_hook",
    "prioridade": 5,
    "habilitado": 0
}
```

To re-enable: set `"habilitado": 1` + redeploy.

---

## Creating a New Hook in a Module

### Step 1 — Emit the event in your module

```php
hook_do_action($_GESTOR['modulo-id'], 'my-event', $relevant_data);
```

### Step 2 — Register the listener in the receiver's JSON

In `project/hooks/hooks.json` or `modulos/my-module/my-module.json`:
```json
{
    "controllers": {
        "my-namespace": "my-module.hooks.php"
    },
    "actions": {
        "my-namespace": {
            "my-event": {
                "callback": "my_hook_function",
                "prioridade": 10,
                "habilitado": 1
            }
        }
    }
}
```

### Step 3 — Create the controller file with the callback function or modify an existing one

```php
// project/hooks/controllers/my-module.hooks.php
function my_hook_function(mixed $data = null): void {
    // hook logic
}
```

### Step 4 — Synchronize

1. If it's a core Conn2Flow hook (modules) in the development environment, run VS Code task:
- **`🛠️ Manager - Update => All - Test Environment`** (to sync modules)

2. If it's a project-specific hook in the development environment, run VS Code task:
- **`🗃️ Projects - Deploy Current Project`** (to sync the project)

---

## Best Practices

| ✅ Do | ❌ Avoid |
|-------|----------|
| Register hooks via JSON | Inserting directly into the `hooks` table |
| Use priorities to control order | Assuming execution order without priorities |
| Stateless callbacks | Sharing state between hooks via unnecessary globals |
| Descriptive names: `{module}_{namespace}_{event}_hook` | Generic names: `my_function` |
| Accept `mixed ...$args` for future compatibility | Required parameters without defaults when avoidable |
| Use `"habilitado": 0` to disable | Deleting the hook from JSON (loses the record) |

---

## Relevant Files

| File | Purpose |
|------|---------|
| `gestor/bibliotecas/hooks.php` | Core library — `HookManager` and 4 global functions |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | `hooks` table migration |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Hook sync in the update pipeline |
| `gestor/bibliotecas/interface.php` | Emits native interface hooks (`adicionar.banco`, `editar.pagina`, etc.) |
