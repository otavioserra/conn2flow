# Manager Development - Legacy 14 (March 2026)

## Complete Hooks System Implementation

## Context and Objectives

This development session was focused on designing and implementing a complete hooks system from scratch for the Conn2Flow platform, inspired by WordPress's `do_action` / `apply_filters` mechanism. The system was designed to allow modules and projects to react to events from other modules **without modifying the source module's code** — promoting extensibility and architectural decoupling.

Core requirements:
- Hook registration exclusively via JSON (source of truth) — never directly in the database.
- Automatic synchronization to the `hooks` table in the update/deploy pipeline.
- Lazy loading per namespace+event at runtime to avoid performance impact.
- Support for two types: **action** (side-effect, `void`) and **filter** (value transformation).
- `habilitado` field in JSON to enable/disable callbacks without removing them.

---

## Detailed Scope Realized

### 1. Migration: `hooks` Table

**File**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

Created via Phinx, the table stores all hook entries synchronized from module and project JSONs. Main columns:

| Column       | Type/Reference              | Notes                                               |
|--------------|-----------------------------|-----------------------------------------------------|
| `id_hooks`   | INT PK                      | Auto-increment primary key                          |
| `modulo`     | VARCHAR(255), nullable      | NULL when the hook belongs to the project           |
| `plugin`     | VARCHAR(255), nullable      | Filled when the module belongs to a plugin          |
| `namespace`  | VARCHAR(255)                | E.g.: `admin-paginas`, `produtos`, `*`              |
| `evento`     | VARCHAR(255)                | E.g.: `adicionar.banco`, `editar.pagina`            |
| `callback`   | VARCHAR(500)                | PHP function name                                   |
| `tipo`       | VARCHAR(10), default=action | `action` or `filter`                                |
| `prioridade` | SMALLINT, default=10        | Lower = executes first                              |
| `habilitado` | TINYINT, default=1          | 0 = synced but not loaded at runtime                |
| `projeto`    | TINYINT, nullable           | 1 = came from `project/hooks/hooks.json`            |
| `status`     | CHAR(1), default=A          | A = active                                          |

Indexes: `idx_hooks_lookup` on `(namespace, evento, tipo, status, habilitado)` — optimizes `loadFromDb`.

---

### 2. Core Library: `hooks.php`

**File**: `gestor/bibliotecas/hooks.php`

Structured in three layers:

#### A. `HookManager` Class (Singleton)

The heart of the system. Single instance per HTTP request, with lazy loading:

```php
// Only queries the database on the first call for that namespace+event
HookManager::getInstance()->doAction($ns, $evt, ...$args)
```

**Lazy loading** (`ensureLoaded` → `loadFromDb`):
```sql
WHERE namespace='...' AND evento='...' AND status='A' AND habilitado=1
ORDER BY prioridade ASC, id_hooks ASC
```

**Automatic controller resolution**: When loading a hook from the database, `HookManager` determines and includes the PHP file containing the callback functions:
- Project hook → reads `project/hooks/hooks.json`, finds `controllers[namespace]`, includes from `project/hooks/controllers/`
- Module hook → reads `modulos/<modulo>/<modulo>.json`, finds `hooks.controllers[namespace]`, includes from the module directory
- Inclusion via `require_once` with internal cache (no duplication)

**Argument compatibility with `ReflectionFunction`**: During development, a critical bug was discovered — callbacks with zero parameters received the `$args` from `doAction` and broke with "Too few arguments". The solution was to use `ReflectionFunction` to detect how many parameters the callback requires and fill missing ones with `null`:

```php
$ref    = new \ReflectionFunction($cb['callback']);
$needed = $ref->getNumberOfRequiredParameters();
if (count($argsToCall) < $needed) {
    $argsToCall = array_pad($argsToCall, $needed, null);
}
```

This made callbacks **completely flexible**: they can accept 0, 1 or N parameters, regardless of what the emitter passes.

#### B. 4 Global API Functions

```php
hook_do_action(string $namespace, string $evento, mixed ...$args): void
hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed
hook_has_actions(string $namespace, string $evento): bool
hook_has_filters(string $namespace, string $evento): bool
```

#### C. 3 Registration Functions (update pipeline)

```php
hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): void
hooks_registrar_projeto(): void
hooks_inserir_callbacks(?string $modulo, ?string $plugin, string $namespace, string $evento, mixed $callbackDef, string $tipo, ?int $projeto): void
```

The `hooks_inserir_callbacks` function supports **3 callback definition formats** in JSON:
- Simple string: `"my_function"`
- Object: `{"callback": "my_function", "prioridade": 5, "habilitado": 1}`
- Array (multiple): `["func_a", {"callback": "func_b", "prioridade": 20}]`

The `habilitado` field is read from JSON and persisted in the database. `loadFromDb` filters by `habilitado=1`, so control is 100% declarative via JSON.

---

### 3. Update Controller: `atualizacoes-hooks.php`

**File**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Central function `atualizacoes_hooks_sincronizar(array $opcoes = [])` with 3 modes:
- Without options: syncs modules + plugins + project (full operation, idempotent)
- `['apenas_projeto' => true]`: syncs only `project/hooks/hooks.json` — used in project deploy
- `['apenas_modulos' => true]`: syncs only modules/plugins — used in full system update

Sync logic:
1. **Modules**: Scans `$_GESTOR['modulos-path']`, reads `<module>.json` from each directory, calls `hooks_registrar_modulo` if `"hooks"` section exists.
2. **Plugins**: Scans `$_GESTOR['plugins-path']`, enters each `modules/`, repeats the process passing the plugin ID.
3. **Project**: Reads `project/hooks/hooks.json`, deletes existing project hooks from the database, re-inserts all.

---

### 4. Integration in the Update Pipeline

#### `atualizacoes-sistema.php` — Full Update

**Modification**: In the `hookAfterAll()` function, added a call after all module/plugin updates:
```php
atualizacoes_hooks_sincronizar();
```

#### `api.php` — Project Deploy

**Modification**: In the `api_executar_atualizacao_banco()` function (endpoint `/_api/project/update`), added a call at the end of processing:
```php
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);
```

This ensures that when deploying `conn2flow-site`, the project's `hooks.json` is automatically synchronized to the `hooks` table.

---

### 5. Core Module Integration: `interface.php`

**File**: `gestor/bibliotecas/interface.php`

`hook_do_action` calls were added to all strategic points in the standard interface lifecycle. Any module using the Conn2Flow interface system automatically emits these hooks:

| Moment | Event fired | Args |
|--------|------------|------|
| POST before INSERT | `{opcao}.pre-banco` | — |
| After INSERT/UPDATE/DELETE/Status/Clone | `{opcao}.banco` | `$id`, `$dados[]` |
| GET page rendering | `{opcao}.pagina` | — |

**Design decision**: The `namespace` used is always `$_GESTOR['modulo-id']`, the ID of the module currently executing the request. This means a hook registered for namespace `admin-paginas` only fires when the `admin-paginas` module is active — avoiding collisions.

---

### 6. Integration in the `admin-paginas` Module

**File**: `gestor/modulos/admin-paginas/admin-paginas.php`

The hooks in `interface.php` cover most cases. But `admin-paginas` also has its own clone/add logic that was directly instrumented:

```php
// After admin-paginas specific INSERT
hook_do_action($_GESTOR['modulo-id'], 'adicionar.banco', $id, [
    'nome' => $nome, 'caminho' => $caminho, ...
]);

// After clone
hook_do_action($_GESTOR['modulo-id'], 'clonar.banco', $id, [...]);
```

---

### 7. Technical Documentation: `CONN2FLOW-HOOKS.md`

Complete documentation created in two languages:
- **PT-BR**: `ai-workspace/pt-br/docs/CONN2FLOW-HOOKS.md`
- **EN**: `ai-workspace/en/docs/CONN2FLOW-HOOKS.md`

Coverage:
- Concepts (Action vs Filter, Namespace+Event, wildcard `*`)
- Full architecture (ASCII flow diagram)
- `hooks` table (all columns)
- 4 global API functions with signatures
- Native `interface.php` events table (including `*.pre-banco`, `*.parametros`, `*.pagina`)
- 3 callback definition formats in JSON
- `habilitado` field and how it works at runtime
- Sync pipeline with 3 modes
- 5 practical use cases with complete code
- Step-by-step guide to creating a hook
- Best practices table

---

## Files Created/Modified in the `conn2flow` Repository

### Created
| File | Type |
|------|------|
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | New |
| `gestor/bibliotecas/hooks.php` | New |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | New |
| `ai-workspace/pt-br/docs/CONN2FLOW-HOOKS.md` | New |
| `ai-workspace/en/docs/CONN2FLOW-HOOKS.md` | New |

### Modified
| File | Modification |
|------|-------------|
| `gestor/bibliotecas/interface.php` | Added `hook_do_action` calls at 5 lifecycle points |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Added `hook_do_action` in add and clone operations |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | `hookAfterAll()` now calls `atualizacoes_hooks_sincronizar()` |
| `gestor/controladores/api/api.php` | `api_executar_atualizacao_banco()` now calls `atualizacoes_hooks_sincronizar(['apenas_projeto' => true])` |

---

## Bugs Found and Fixed

### Bug 1: "Too few arguments to function"

**Symptom**: Callback functions registered with 0 parameters (e.g., `function my_hook(): void`) broke with `Fatal error: Too few arguments` when the emitter passed arguments via `hook_do_action(..., $id, $dados)`.

**Root cause**: `call_user_func_array` passed all `$args` directly to the callback without checking how many parameters it accepts.

**Solution**: Use of `\ReflectionFunction` to inspect the callback's signature at runtime:
```php
$needed = $ref->getNumberOfRequiredParameters();
if (count($argsToCall) < $needed) {
    $argsToCall = array_pad($argsToCall, $needed, null);
}
```
This approach also protects the opposite case (more args than the callback accepts) because `call_user_func_array` already ignores extra args.

### Bug 2: `habilitado` field not persisted from JSON

**Symptom**: When defining `"habilitado": 0` in `hooks.json`, the hook was still inserted into the database with `habilitado=1`.

**Root cause**: The `hooks_inserir_callbacks()` function in the initial implementation hardcoded `'habilitado' => '1'` regardless of what came from the JSON.

**Solution**: Complete refactoring of the function to parse the `habilitado` field in all 3 callback formats (string, object, array of objects) and use it in `banco_insert_name`.

---

## Design Decisions

1. **JSON as source of truth**: Hooks are never registered directly in the database. The `hooks` table is rebuilt on every sync and should be treated as a cache — never manually edited.

2. **Lazy loading per namespace+event**: Avoids loading all hooks at initialization. The database is only queried when `hook_do_action` is called for the first time for a specific pair.

3. **`*` (wildcard) namespace**: Allows creating middlewares that react to events from any module (e.g., global audit module, metrics). `HookManager` automatically loads both the specific namespace and `*` for each event.

4. **Controller isolation**: The PHP file with the callback functions lives in the directory of the module that **receives** the event (not the one that emits it). `HookManager` includes the file automatically via `require_once` on first use, with no manual includes required.

5. **ReflectionFunction**: A more robust option than requiring all callbacks to have the same signature. Allows "lean" callbacks with no parameters even when the emitter passes data.

---

## Lessons Learned

- **Idempotency is essential in syncs**: `hooks_registrar_modulo` and `hooks_registrar_projeto` always delete existing hooks before re-inserting. This ensures consistency even on re-runs.
- **ReflectionFunction has a performance cost**: In production with many callbacks per event, `ReflectionFunction` is called once per event execution (not per request, since lazy load avoids repetition). The cost is acceptable.
- **The `*` wildcard requires two queries**: `ensureLoaded` queries the specific namespace AND `*` separately. This was necessary to maintain granular lazy loading without duplicating entries in the database.

## Suggested Next Steps

1. **File cache**: For high-traffic environments, consider caching loaded hooks in `temp/` by namespace+event to eliminate the database query at runtime.
2. **Manager UI**: Create an admin screen to visualize all hooks registered in the table (namespace, event, callback, module, priority, enabled) with a manual sync button.
3. **Plugin hooks**: Specifically test controller resolution for plugins (path `plugins-path/<plugin>/modules/<module>/<file>`).
4. **Unit tests**: Create tests for `HookManager::doAction` and `applyFilters` with scenarios for wildcard, ReflectionFunction padding, and habilitado=0.
