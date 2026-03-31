# Hook Apply Filters for Multi-User - Legacy 1 (March 2026)

## Adding hook_apply_filters to html-editor.php, ia.php and id_usuarios to admin-prompts-ia.php

## Context and Objectives

This development session was part of a larger Multi-User Hooks v2.0 implementation in the Conn2Flow Site project. The modifications to the core repository (`conn2flow`) were required to add **extension points** (filter hooks) in two central libraries, allowing downstream projects to inject filtering logic without modifying core code.

The work on the core repository consisted of 3 surgical modifications.

---

## Detailed Scope

### Modification 1: html-editor.php — Filter Hook for Templates

**File:** `gestor/bibliotecas/html-editor.php`

**Problem:** The template loading query in html-editor was fixed — there was no way for a project to filter which templates are visible to a user.

**Solution:** The WHERE construction was extracted into a dedicated variable and `hook_apply_filters()` was inserted before query execution:

```php
$where_templates = "WHERE status = 'A' AND framework_css = '...' AND language = '...' AND target = '...'";

// Hook: allows filtering the templates WHERE clause (e.g., multi-user)
$where_templates = hook_apply_filters('html-editor', 'templates.load.where', $where_templates);

$retorno_bd = banco_select([...]);
```

**Impact:** None for projects that don't register filter hooks — `hook_apply_filters()` returns the original value if no callback is registered.

---

### Modification 2: ia.php — Filter Hook for Prompts

**File:** `gestor/bibliotecas/ia.php`

**Problem:** The AI prompts loading query was fixed — same situation as html-editor.php.

**Solution:** Same pattern — `$where_prompts` variable + `hook_apply_filters()`:

```php
$where_prompts = "WHERE alvo = '...' AND status = 'A' AND language = '...'";

// Hook: allows filtering the prompts WHERE clause (e.g., multi-user)
$where_prompts = hook_apply_filters('ia', 'prompts.load.where', $where_prompts);

$prompts = banco_select([...]);
```

---

### Modification 3: admin-prompts-ia.php — id_usuarios Field in INSERT

**File:** `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

**Problem:** When creating a new prompt, the `id_usuarios` field was not populated, making it impossible to track per-user ownership.

**Solution:** Added a line to the INSERT fields array in the `admin_prompts_ia_adicionar()` function:

```php
$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios'];
$campos[] = Array($campo_nome, $campo_valor, $campo_sem_aspas_simples);
```

**Prerequisite:** The `id_usuarios` column must exist in the `prompts_ia` table (migration created in the conn2flow-site repository).

---

## Modified Files

| File | Change |
|------|--------|
| `gestor/bibliotecas/html-editor.php` | `hook_apply_filters('html-editor', 'templates.load.where', $where_templates)` |
| `gestor/bibliotecas/ia.php` | `hook_apply_filters('ia', 'prompts.load.where', $where_prompts)` |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | `id_usuarios` added to INSERT |

---

## Design Decision

The filter hooks were placed in the **core libraries** (not in modules) because templates and prompts are loaded across **all application contexts** — not just in admin modules. A restricted user should not see other users' templates/prompts anywhere in the application.

---

## Session End State

- ✅ `html-editor.php` with hook_apply_filters
- ✅ `ia.php` with hook_apply_filters
- ✅ `admin-prompts-ia.php` with id_usuarios in INSERT
- ⬜ Commit/Push pending

_Detailed Session - Future Agent Reference (Hook Apply Filters)_
