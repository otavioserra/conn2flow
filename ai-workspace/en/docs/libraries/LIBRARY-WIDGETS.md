# Library: widgets.php

> Modular dynamic widgets system

## Overview

The `widgets.php` library provides a system for rendering **dynamic components (widgets) directly into page HTML**. It serves as a bridge between static content and module back-end logic, allowing specific PHP functions of a module to be called directly from special HTML markers.

The flow is: `gestor.php` scans the page HTML looking for the `@[[widgets#...]]@` marker and passes the inner string to `widgets_get()`, which parses the format, includes the module's `.widget.php` file, and executes the requested function — returning the HTML result to replace the original marker.

**Location**: `gestor/bibliotecas/widgets.php`
**Version**: 2.0.0
**Total Functions**: 1 main

## Dependencies

- **Global Variables**: `$_GESTOR`
- **Context**: Loaded by `gestor.php` on-demand via `gestor_incluir_biblioteca('widgets')`

## Global Variables

Only version registration and the pending AJAX widgets list:

- `$_GESTOR['biblioteca-widgets']['versao']` = `2.0.0`
- `$_GESTOR['widgetsToAjax']` — string with identifiers separated by `<#;>` for widgets that need an AJAX callback

---

## Structure and Operation

### HTML Syntax

The marker inserted into Conn2Flow page HTML follows the format:

```
@[[widgets#MODULE_ID->FUNCTION(JSON_PARAMS)]]@
```

Where:
- `MODULE_ID`: The ID of the module that contains the widget logic.
- `FUNCTION`: The name of the PHP function to call.
- `JSON_PARAMS`: A valid JSON string with the parameters for the function.

**Example:**
```html
@[[widgets#my-module->render_list({"limit": 5, "order": "desc"})]]@
```

### Processing in gestor.php

`gestor.php` uses a regex pattern to find all widget markers in the page, then calls `widgets_get()` for each one and replaces the marker with the returned HTML.

```php
// @[[widgets#MODULE_ID->FUNCTION(JSON_PARAMS)]]@  (new modular format)
// @[[widgets#simple-name]]@                       (backward compatibility)
$pattern = "/".preg_quote($open)."widgets#(.+?)".preg_quote($close)."/i";
preg_match_all($pattern, $_GESTOR['pagina'], $matchesWidgets);

foreach($matchesWidgets[1] as $match){
    $widget = widgets_get(Array('id' => $match));
    if(existe($widget)){
        $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], $open."widgets#".$match.$close, $widget);
    }
}
```

### AJAX Support

When a widget is processed during a normal (non-AJAX) request, the system automatically registers its identifier in `$_GESTOR['widgetsToAjax']`. On subsequent AJAX requests, `gestor.php` calls `gestor_pagina_widgets_ajax()` which reuses `widgets_get()` — but this time calling the `_ajax` suffixed function (e.g., `render_list_ajax()`).

---

## Main Functions

### widgets_get()

Processes and renders a complete widget by ID.

**Signature:**
```php
function widgets_get($params = false)
```

**Parameters (Associative Array):**
- `id` (string) — **Required** — Widget identifier in the format `MODULE_ID->FUNCTION(JSON_PARAMS)` or a simple name for backward compatibility.

**Return:**
- (string) — Processed and complete widget HTML, or empty string if not found.

**Internal flow:**

```
widgets_get(['id' => 'my-module->render_list({"limit": 5})'])
  |
  +-- 1. preg_match extracts: module="my-module", func="render_list", json='{"limit": 5}'
  |
  +-- 2. json_decode converts to PHP array: ['limit' => 5]
  |
  +-- 3. require_once: gestor/modulos/my-module/my-module.widget.php
  |
  +-- 4. Checks if AJAX:
  |        +-- YES: calls render_list_ajax(['limit' => 5])
  |        +-- NO:  registers in $_GESTOR['widgetsToAjax'], calls render_list(['limit' => 5])
  |
  +-- 5. Returns resulting HTML (or '' if function doesn't exist)
```

**Usage example (internal use by gestor.php):**
```php
$widget_html = widgets_get(Array(
    'id' => 'my-module->render_list({"limit": 5})'
));
```

---

## How to Create a Widget in a Module

### 1. Create the widget file

In the module directory (`gestor/modulos/your-module/`), create: `your-module.widget.php`

### 2. Define the PHP function

```php
<?php

function render_list($params = array()) {
    $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
    $order = isset($params['order']) ? $params['order']       : 'asc';

    $items = banco_select(Array(
        'tabela' => 'my_items',
        'campos' => Array('id', 'titulo', 'descricao'),
        'extra'  => "WHERE status = 'A' ORDER BY titulo " . strtoupper($order) . " LIMIT " . $limit,
    ));

    if (empty($items)) {
        return '<p>No items found.</p>';
    }

    $html = '<ul class="my-module-list">';
    foreach ($items as $item) {
        $html .= '<li>' . htmlspecialchars($item['titulo']) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}
```

### 3. Insert the marker in the HTML page

```html
@[[widgets#my-module->render_list({"limit": 5, "order": "desc"})]]@
```

### 4. AJAX function (optional)

If the widget needs to respond to AJAX requests without reloading the page, create a function with the `_ajax` suffix:

```php
function render_list_ajax($params = array()) {
    // Return empty string on success, error message string on failure
    $data = [/* ... */];
    echo json_encode($data);
    exit;
}
```

---

## Backward Compatibility

Simple IDs (without modular notation) continue to work:

```html
@[[widgets#simple-widget-name]]@
```

In this case, if the ID doesn't match the `MODULE->FUNCTION(...)` pattern, the modular block is not activated and the function returns an empty string. This behavior can be expanded in the future to look up legacy widgets in database or resource files.

---

## Security

Always sanitize parameters received via JSON before using them in queries or HTML output:

```php
$id    = isset($params['id'])   ? (int)$params['id']                : 0;
$name  = isset($params['name']) ? htmlspecialchars($params['name'])  : '';
$field = isset($params['q'])    ? banco_escape_field($params['q'])   : '';
```

---

## Patterns and Best Practices

### Consistent Return

```php
// GOOD — always return string (never null or false)
function my_widget($params = array()) {
    if (empty($params)) return '';
    return $html;
}
```

### Naming Convention

- Function names must be unique across the entire project.
- Recommended: use module ID as prefix — e.g. `catalog_widget_list()`.
- The AJAX version must have the same name + `_ajax` suffix — e.g. `catalog_widget_list_ajax()`.

---

## See Also

- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) — System components and variables
- [INTERFACE-V2-ARCHITECTURE.md](../INTERFACE-V2-ARCHITECTURE.md) — Module CRUD operations
- [LIBRARY-TEMPLATE.md](./LIBRARY-TEMPLATE.md) — Template and variable substitution
- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) — Database operations

---

**Last Updated**: March 2026
**Documentation Version**: 2.0.0
**Maintainer**: Conn2Flow Team
