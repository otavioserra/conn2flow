# Hook Apply Filters — html-editor.php and ia.php

> **Version:** 1.0.0 | **Date:** March 31, 2026  
> **Project:** Conn2Flow Core (conn2flow)

---

## Table of Contents

- [Overview](#overview)
- [html-editor.php — Templates Filter](#html-editorphp--templates-filter)
- [ia.php — Prompts Filter](#iaphp--prompts-filter)
- [admin-prompts-ia.php — id_usuarios Field in INSERT](#admin-prompts-iaphp--id_usuarios-field-in-insert)
- [How to Use Filters in Projects](#how-to-use-filters-in-projects)
- [Modified Files](#modified-files)

---

## Overview

This update adds extension points via `hook_apply_filters()` in two Conn2Flow core libraries:

1. **`html-editor.php`** — allows filtering the WHERE clause for loading HTML templates
2. **`ia.php`** — allows filtering the WHERE clause for loading AI prompts

These filters enable projects (such as Conn2Flow Site) to inject multi-user restrictions, global records, or any other filtering logic without modifying core code.

Additionally, the `admin-prompts-ia.php` module was updated to include the `id_usuarios` field in the INSERT for new prompts, enabling per-user ownership tracking.

---

## html-editor.php — Templates Filter

**File:** `gestor/bibliotecas/html-editor.php`

Template loading in the HTML editor now passes the WHERE clause through a filter hook before executing the query:

```php
// Build WHERE clause
$where_templates = "WHERE status = 'A' AND framework_css = '" . banco_escape_field($framework_css) . "' "
    . "AND language = '" . banco_escape_field($idioma) . "' "
    . "AND target = '" . banco_escape_field($alvo) . "'";

// Hook: allows filtering the templates WHERE clause (e.g., multi-user)
$where_templates = hook_apply_filters('html-editor', 'templates.load.where', $where_templates);

$retorno_bd = banco_select([
    'tabela' => 'templates',
    'campos' => [...],
    'extra' => $where_templates
]);
```

### Filter Parameters

| Parameter | Value |
|-----------|-------|
| Namespace | `html-editor` |
| Hook ID | `templates.load.where` |
| Filtered value | SQL WHERE string |

### Callback Example

```php
// In hooks.json:
// "filters": { "html-editor": { "templates.load.where": "my_callback" } }

function my_callback($where) {
    // Add user filter
    return $where . " AND id_usuarios = '5'";
}
```

---

## ia.php — Prompts Filter

**File:** `gestor/bibliotecas/ia.php`

Prompt loading in the AI library now passes the WHERE clause through a filter hook:

```php
// Build WHERE clause for prompts
$where_prompts = "WHERE alvo = '" . banco_escape_field($alvo) . "' "
    . "AND status = 'A' AND language = '" . $_GESTOR['linguagem-codigo'] . "'";

// Hook: allows filtering the prompts WHERE clause (e.g., multi-user)
$where_prompts = hook_apply_filters('ia', 'prompts.load.where', $where_prompts);

$prompts = banco_select(Array(
    'tabela' => 'prompts_ia',
    'campos' => Array('id', 'nome'),
    'extra' => $where_prompts
));
```

### Filter Parameters

| Parameter | Value |
|-----------|-------|
| Namespace | `ia` |
| Hook ID | `prompts.load.where` |
| Filtered value | SQL WHERE string |

### Callback Example

```php
// In hooks.json:
// "filters": { "ia": { "prompts.load.where": "my_prompts_callback" } }

function my_prompts_callback($where) {
    return $where . " AND id_usuarios = '5'";
}
```

---

## admin-prompts-ia.php — id_usuarios Field in INSERT

**File:** `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

In the `admin_prompts_ia_adicionar()` function, the `id_usuarios` field was added to the INSERT fields array:

```php
$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios'];
$campos[] = Array($campo_nome, $campo_valor, $campo_sem_aspas_simples);
```

This ensures that newly created prompts are linked to the user who created them, enabling:
- Multi-user filtering via hooks
- Per-user limit counting
- Global records based on `id_usuarios`

> **Note:** The `prompts_ia` table requires the `id_usuarios` column (added via migration in the conn2flow-site project). Projects that do not use multi-user functionality are not affected, as the column allows NULL and defaults to `1`.

---

## How to Use Filters in Projects

To use these filters in a Conn2Flow project:

### 1. Register in the project's hooks.json

```json
{
    "controllers": {
        "html-editor": "my-controller.php",
        "ia": "my-controller.php"
    },
    "filters": {
        "html-editor": {
            "templates.load.where": "callback_function_name"
        },
        "ia": {
            "prompts.load.where": "callback_function_name_prompts"
        }
    }
}
```

### 2. Implement the callbacks in the controller

```php
function callback_function_name($where) {
    // Your filtering logic
    return $where . " AND your_condition";
}

function callback_function_name_prompts($where) {
    return $where . " AND your_condition";
}
```

### 3. Important Notes

- The callback **must** return the WHERE string (modified or not)
- If no callback is registered, `hook_apply_filters()` returns the original value unchanged
- Multiple callbacks can be chained (each receives the result of the previous one)

---

## Modified Files

| File | Change Type | Description |
|------|-------------|-------------|
| `gestor/bibliotecas/html-editor.php` | Modified | Added `hook_apply_filters('html-editor', 'templates.load.where', $where)` |
| `gestor/bibliotecas/ia.php` | Modified | Added `hook_apply_filters('ia', 'prompts.load.where', $where)` |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modified | Added `id_usuarios` to the INSERT for new prompts |
