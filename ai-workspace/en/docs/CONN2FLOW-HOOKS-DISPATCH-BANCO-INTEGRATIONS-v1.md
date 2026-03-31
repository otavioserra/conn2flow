# Conn2Flow Core — Hooks Dispatch Points, banco_select Fix & Integrations v1 (March 2026)

## Overview

This document records the modifications made to the `conn2flow` core repository (branch `main`) during the implementation of the project hooks system, `banco_select` fix, and integrations required to support the multi-user system from `conn2flow-site`.

**Total**: 21 files modified across ~20 commits (03/16 to 03/31/2026).

**Related documentation**: For complete hook system details, see `CONN2FLOW-HOOKS.md`.

---

## 1. Library `hooks.php` — HookManager (New)

**File**: `gestor/bibliotecas/hooks.php`

Complete implementation of `HookManager` — a singleton class that manages loading and execution of hooks registered in the database.

### Features Implemented

- **Singleton pattern**: `HookManager::getInstance()` ensures a single instance per request
- **Lazy loading**: Hooks are only loaded from the database when `doAction()` or `applyFilters()` are called for the first time for a specific namespace+event
- **Controller auto-include**: The controller PHP file is automatically included (via `require_once`) before the first callback execution
- **`ReflectionFunction` for argument padding**: Callbacks with fewer parameters than the passed arguments receive only what they declare. Callbacks with more required parameters receive `null` for missing arguments. This ensures legacy hooks don't break when new arguments are added to events.
- **Error logging**: Execution failures logged to `logs/hooks-errors.log`
- **4 global functions**: `hook_do_action()`, `hook_apply_filters()`, `hook_has_actions()`, `hook_has_filters()`

### Detail: ReflectionFunction Argument Padding

Problem found: a callback declared as `function my_hook()` (no parameters) was called with `hook_do_action('ns', 'event', $id, $data)`. PHP threw warning: `Too few/many arguments`.

Solution:
```php
$reflection = new \ReflectionFunction($callback);
$requiredParams = $reflection->getNumberOfRequiredParameters();
$totalParams = $reflection->getNumberOfParameters();

// If passed args > declared params → truncate
$callArgs = array_slice($args, 0, max($totalParams, 1));

// If passed args < required params → pad with null
while (count($callArgs) < $requiredParams) {
    $callArgs[] = null;
}

call_user_func_array($callback, $callArgs);
```

---

## 2. Library `interface.php` — Hook Dispatch Points (Modified)

**File**: `gestor/bibliotecas/interface.php`

Addition of hook dispatch points in the module interface lifecycle. `interface.php` is the core library that processes all CRUD operations for all system modules.

### 5 Types of Dispatch Points Added

| Event | When Fired | Arguments |
|-------|------------|-----------|
| `{operation}.pre-banco` | Before INSERT/UPDATE/DELETE | — |
| `{operation}.banco` | After successful database operation | `$id`, `$data[]` |
| `{operation}.parametros` | Before page render (GET) | — |
| `{operation}.pagina` | After page render (GET) | — |
| `{operation}.where` | Filter: allows modifying WHERE clauses for listings | `$where` → returns modified `$where` |

Where `{operation}` is the current action: `adicionar` (add), `editar` (edit), `excluir` (delete), `clonar` (clone), `listar` (list), `status`.

### Dispatch Example in Code

```php
// Before INSERT:
hook_do_action($module_id, $operation . '.pre-banco');

// After successful INSERT:
hook_do_action($module_id, $operation . '.banco', $id, $data);

// Before render:
hook_do_action($module_id, $operation . '.parametros');

// Filter WHERE for listings:
$where = hook_apply_filters($module_id, $operation . '.where', $where);
```

---

## 3. Controller `atualizacoes-hooks.php` (New)

**File**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Function `atualizacoes_hooks_sincronizar($options = [])` that synchronizes hooks from JSON to the database.

### Synchronization Flow

1. **Reads source JSONs**: `modulos/*/module.json` (module hooks) + `project/hooks/hooks.json` (project hooks)
2. **For each defined hook**:
   - Checks if it already exists in the database (by `namespace + event + callback + module/project`)
   - If new → INSERT
   - If changed (priority, enabled) → UPDATE
   - If removed from JSON → DELETE from database
3. **Option `apenas_projeto`**: When `$options['apenas_projeto'] === true`, synchronizes only hooks from `project/hooks/hooks.json` (used during project deploy to avoid affecting module hooks)

### Deploy Integration

**Modified file**: `gestor/controladores/api/api.php`

On the project deploy route (`/_api/project/update`), after extracting and applying files:

```php
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);
```

**Modified file**: `gestor/controladores/atualizacoes/atualizacoes-sistema.php`

In `hookAfterAll()` (executed after all system updates):

```php
atualizacoes_hooks_sincronizar();  // synchronizes modules + project
```

---

## 4. Migration `create_hooks_table` (New)

**File**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

Creates the `hooks` table with columns documented in `CONN2FLOW-HOOKS.md`:
- `id_hooks` (PK), `modulo`, `plugin`, `namespace`, `evento`, `callback`, `tipo`, `prioridade`, `habilitado`, `projeto`, `status`, `data_criacao`, `data_modificacao`
- Composite index: `namespace + evento + habilitado` to optimize lazy loading

---

## 5. Library `banco.php` — Fix `banco_select` (Modified)

**File**: `gestor/bibliotecas/banco.php`

### Bug Fixed

The `banco_select()` function only accepted arrays for the `$campos` (fields) parameter. When a module passed a simple string (e.g., `banco_select('table', 'id, name, status')`), the function silently failed and returned `SELECT * FROM table` instead of the specified fields.

### Root Cause

Internal check `if (is_array($campos))` prevented string processing. The string was ignored and the default `*` was used.

### Fix

```php
// Before:
if (is_array($campos)) {
    $select = implode(', ', $campos);
}

// After:
if (is_array($campos)) {
    $select = implode(', ', $campos);
} elseif (is_string($campos) && !empty($campos)) {
    $select = $campos;
}
```

---

## 6. Module `admin-paginas` — Dispatch Points (Modified)

**File**: `gestor/modulos/admin-paginas/admin-paginas.php`

Addition of `hook_do_action()` and `hook_apply_filters()` calls at strategic points in the pages CRUD, allowing project hooks to intercept page creation, editing, and listing operations.

---

## 7. Module `perfil-usuario` — Display Modes (Modified)

**File**: `gestor/modulos/perfil-usuario/perfil-usuario.php`

Modifications to support display of module operations linked to the profile, including the new full-access/restricted-access operations created in conn2flow-site.

---

## 8. Library `html-editor.php` — Filter Hooks (Modified, Unstaged)

**File**: `gestor/bibliotecas/html-editor.php`

Addition of dispatch points to filter template and AI prompt lists in the HTML editor:

```php
// Filter available templates in the editor selector
$templates = hook_apply_filters('editor-html', 'templates.listar', $templates);

// Filter available AI prompts in the editor selector
$prompts = hook_apply_filters('editor-html', 'prompts.listar', $prompts);
```

This allows project hooks to restrict which templates and prompts are visible to each user.

---

## 9. Library `ia.php` — Prompt Hooks (Modified, Unstaged)

**File**: `gestor/bibliotecas/ia.php`

Addition of a hook filter to filter AI prompts when loaded for use by AI modules:

```php
$prompts = hook_apply_filters('ia', 'prompts.carregar', $prompts);
```

---

## 10. Module `admin-prompts-ia` — Isolation (Modified, Unstaged)

**File**: `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

Addition of hook dispatch points to support multi-user isolation of AI prompts (list, add, edit).

---

## Modified Files Summary

| File | Type | Status |
|------|------|--------|
| `gestor/bibliotecas/hooks.php` | Created | Committed |
| `gestor/bibliotecas/interface.php` | Modified | Committed |
| `gestor/bibliotecas/banco.php` | Modified | Committed |
| `gestor/bibliotecas/html-editor.php` | Modified | Unstaged |
| `gestor/bibliotecas/ia.php` | Modified | Unstaged |
| `gestor/controladores/api/api.php` | Modified | Committed |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Created | Committed |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Modified | Committed |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Created | Committed |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Modified | Committed |
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | Modified | Committed |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modified | Unstaged |

**Note**: Files marked as "Unstaged" were modified but not yet committed at the time of documentation. They will be included in the next commit.
