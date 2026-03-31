# Manager Development - Legacy 15

## Hooks System: Dispatch Points, HookManager, banco_select Fix & Multi-User Integrations

## Context and Objectives

This development session implemented in the Conn2Flow core (`conn2flow`) all the infrastructure pieces required to support the project hooks and multi-user system from `conn2flow-site`. The work was executed in parallel with extensive implementations on conn2flow-site (3D Catalog v1.7.14/v1.7.15, Files module, Social Networks v2.0) over ~2 weeks (03/16 to 03/31/2026).

**21 files modified** in the core, distributed across ~20 commits.

### Objectives

1. Create the `hooks.php` library with the HookManager singleton
2. Add dispatch points in `interface.php` for all CRUD events
3. Create hooks synchronization controller (JSON → database)
4. Create `hooks` table migration
5. Fix `banco_select` bug that ignored string fields
6. Integrate hooks synchronization into project deploy and system updates
7. Add filter hooks in `html-editor.php` and `ia.php`

---

## Detailed Implementations

### 1. Creation of `hooks.php` — HookManager

**File created**: `gestor/bibliotecas/hooks.php`

The `HookManager` is a singleton class that manages the entire hooks lifecycle: database loading, controller resolution, and callback execution.

**Lazy Loading**: The first call to `hook_do_action('admin-paginas', 'adicionar.banco')` triggers a database query fetching all registered hooks for `namespace='admin-paginas' AND evento='adicionar.banco' AND habilitado=1`. The result is cached in memory — subsequent calls for the same namespace+event don't make new queries.

**Controller Auto-Include**: When a hook is loaded, the `HookManager` does `require_once` on the controller file mapped in the database record. This ensures the callback function will be available before execution.

**ReflectionFunction for Padding**: Problem found during development: callbacks declared without parameters (`function my_hook()`) were called with arguments (`hook_do_action('ns', 'evt', $id, $data)`), generating PHP warnings. The solution uses `ReflectionFunction` to detect how many parameters the callback declares and adjust the argument list before `call_user_func_array()`.

```php
$reflection = new \ReflectionFunction($callback);
$requiredParams = $reflection->getNumberOfRequiredParameters();
$totalParams = $reflection->getNumberOfParameters();
$callArgs = array_slice($args, 0, max($totalParams, 1));
while (count($callArgs) < $requiredParams) {
    $callArgs[] = null;
}
call_user_func_array($callback, $callArgs);
```

**4 Global Functions Exposed**:
- `hook_do_action(string $namespace, string $evento, mixed ...$args): void`
- `hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed`
- `hook_has_actions(string $namespace, string $evento): bool`
- `hook_has_filters(string $namespace, string $evento): bool`

---

### 2. Dispatch Points in `interface.php`

**File modified**: `gestor/bibliotecas/interface.php`

`interface.php` is the library that processes ALL CRUD operations for all system modules. Each operation (add, edit, delete, clone, list, status) already followed a standardized internal flow. Hook calls were added at 5 points in this flow:

| Point | Type | Description |
|-------|------|-------------|
| `{operation}.pre-banco` | Action | Before database operation (INSERT/UPDATE/DELETE) |
| `{operation}.banco` | Action | After successful database operation (with $id and $data) |
| `{operation}.parametros` | Action | Before page render (GET request) |
| `{operation}.pagina` | Action | After page render (GET request) |
| `{operation}.where` | Filter | Allows modifying WHERE clauses for listings |

The `.where` filter is especially important for multi-user — it allows injecting `AND id_usuarios = ?` into listing queries of any module without modifying the module's code.

---

### 3. Controller `atualizacoes-hooks.php`

**File created**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Implements `atualizacoes_hooks_sincronizar($options = [])`:

1. Iterates through all modules in `gestor/modulos/` reading `*.json` → `hooks` section
2. Reads `project/hooks/hooks.json` (project hooks)
3. For each hook definition:
   - Builds record with `namespace`, `evento`, `callback`, `tipo`, `prioridade`, `habilitado`, `modulo/projeto`
   - Checks existence in database by composite key
   - INSERT if new, UPDATE if changed, keep if identical
4. Removes hooks from database that no longer exist in source JSONs
5. Option `['apenas_projeto' => true]` restricts synchronization to project hooks only (used during deploy)

**Integration**:
- `api.php` → On route `/_api/project/update` calls `atualizacoes_hooks_sincronizar(['apenas_projeto' => true])`
- `atualizacoes-sistema.php` → In `hookAfterAll()` calls `atualizacoes_hooks_sincronizar()` (all hooks)

---

### 4. Migration `create_hooks_table`

**File created**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

`hooks` table with:
- `id_hooks` INT PK AUTO_INCREMENT
- `modulo` VARCHAR(255) — Module ID (NULL = project hook)
- `plugin` VARCHAR(255) — Plugin ID (if applicable)
- `namespace` VARCHAR(255) — Target namespace
- `evento` VARCHAR(255) — Specific event
- `callback` VARCHAR(500) — PHP function name
- `tipo` VARCHAR(10) — `action` or `filter`
- `prioridade` SMALLINT DEFAULT 10 — Lower = executes first
- `habilitado` TINYINT DEFAULT 1
- `projeto` TINYINT DEFAULT 0 — 1 if from `project/hooks/hooks.json`
- `status` CHAR(1) DEFAULT 'A'
- `data_criacao`, `data_modificacao` DATETIME

Composite index `idx_hooks_lookup` on `(namespace, evento, habilitado)` to optimize lazy loading.

---

### 5. Fix `banco_select` — String Fields

**File modified**: `gestor/bibliotecas/banco.php`

**Bug**: `banco_select('table', 'id, name, status')` ignored the second parameter and returned `SELECT * FROM table`. The internal check was `if (is_array($campos))`, ignoring strings.

**Fix**: Added `elseif (is_string($campos) && !empty($campos))` to accept strings as field lists.

This bug was found when `arquivos.hooks.php` tried to do a select with specific fields for the listing API and received all table columns instead.

---

### 6. Dispatch Points in `admin-paginas.php`

**File modified**: `gestor/modulos/admin-paginas/admin-paginas.php`

Addition of `hook_do_action()` and `hook_apply_filters()` calls at the CRUD points of the pages module. This module was the first to be directly used by the multi-user hooks from conn2flow-site, so it served as the implementation reference.

---

### 7. Modifications in `perfil-usuario.php`

**File modified**: `gestor/modulos/perfil-usuario/perfil-usuario.php`

Adjustments to display the new module operations (full-access, restricted-access) in the profile editing interface. Operations are loaded from `modulos_operacoes` and displayed as checkboxes linked to the profile.

---

### 8. Hooks in `html-editor.php` (Unstaged)

**File modified**: `gestor/bibliotecas/html-editor.php`

Dispatch points to filter template and prompt lists in the HTML editor:
```php
$templates = hook_apply_filters('editor-html', 'templates.listar', $templates);
$prompts = hook_apply_filters('editor-html', 'prompts.listar', $prompts);
```

Allows project hooks from conn2flow-site to filter which templates/prompts are visible to each user profile.

---

### 9. Hooks in `ia.php` (Unstaged)

**File modified**: `gestor/bibliotecas/ia.php`

Filter for AI prompts when loaded by AI modules:
```php
$prompts = hook_apply_filters('ia', 'prompts.carregar', $prompts);
```

---

### 10. Dispatch Points in `admin-prompts-ia.php` (Unstaged)

**File modified**: `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

Isolation hooks for the AI prompts module (list, add, edit), analogous to `admin-paginas`.

---

## Technical Decisions

### Lazy Loading vs Eager Loading

Lazy loading was chosen (database query only when the hook is actually called) instead of loading all hooks at request start. Reason: most requests only fire 2-3 different hooks, and loading all ~40+ registered hooks would be wasteful.

### ReflectionFunction vs Type Checking

For argument padding, `ReflectionFunction` was chosen over manual type checking. Although reflection has overhead, it's called only once per callback (result cached internally by HookManager).

### Controller Auto-Include

Controllers are included via `require_once` (not `include_once`) to ensure that syntax errors or file-not-found generate catchable exceptions. The controller path is relative to the module directory or `project/hooks/controllers/`.

### Core/Project Separation

The core (`hooks.php`, `interface.php`) only fires events and applies filters. Business logic (multi-user isolation, plan limits) lives entirely in project hooks (`conn2flow-site/gestor/project/hooks/`). This ensures single-tenant projects work without any modifications.

---

## Bugs Found and Resolved

| # | Bug | Cause | Solution |
|---|-----|-------|---------|
| 1 | `banco_select` ignores string fields | `is_array()` check only | Added `is_string()` branch |
| 2 | Callbacks without params receive args | PHP warning in `call_user_func_array` | `ReflectionFunction` padding |
| 3 | Module `3d-catalog` (starts with digit) | Hook loader failed on `require` | Fallback with full path |

---

## Files Created/Modified (Complete Inventory)

| File | Type | Status |
|------|------|--------|
| `gestor/bibliotecas/hooks.php` | Created | ✅ Committed |
| `gestor/bibliotecas/interface.php` | Modified | ✅ Committed |
| `gestor/bibliotecas/banco.php` | Modified | ✅ Committed |
| `gestor/bibliotecas/html-editor.php` | Modified | ⏳ Unstaged |
| `gestor/bibliotecas/ia.php` | Modified | ⏳ Unstaged |
| `gestor/controladores/api/api.php` | Modified | ✅ Committed |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Created | ✅ Committed |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Modified | ✅ Committed |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Created | ✅ Committed |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Modified | ✅ Committed |
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | Modified | ✅ Committed |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modified | ⏳ Unstaged |
