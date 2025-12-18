# Library: pagina.php

> 📄 Page cell and variable manipulation

## Overview

The `pagina.php` library provides functions to manipulate cells (content blocks) and variables in HTML pages, allowing dynamic content substitution and template management.

**Location**: `gestor/bibliotecas/pagina.php`  
**Version**: 1.0.0  
**Total Functions**: 7

## Dependencies

- **Libraries**: modelo.php
- **Global Variables**: `$_GESTOR`

## Global Variables

```php
$_GESTOR['biblioteca-pagina'] = Array(
    'versao' => '1.0.0',
);

// Current page being processed
$_GESTOR['pagina'] // Page HTML

// Global variable delimiters
$_GESTOR['variavel-global'] = Array(
    'open' => '[[',
    'close' => ']]',
    'openText' => '@[[',
    'closeText' => ']]@'
);
```

---

## Concepts

### Cells
HTML blocks delimited by special comments:
- `<!-- name < -->content<!-- name > -->` - Normal cell
- `<!-- name [[content]] name -->` - Cell with comment

### Global Variables
Placeholders in the format `[[variable-name]]` substituted by dynamic values.

---

## Main Functions

### pagina_celula()

Extracts and marks page cell for processing.

**Signature:**
```php
function pagina_celula($nome, $comentario = false, $apagar = false)
```

**Parameters:**
- `$nome` (string) - **Required** - Cell name
- `$comentario` (bool) - **Optional** - Use comment format
- `$apagar` (bool) - **Optional** - Remove cell from page

**Return:**
- (string) - Cell content

**Usage Example:**
```php
// Page HTML
$_GESTOR['pagina'] = '
    <!-- header < -->
    <header>Header</header>
    <!-- header > -->
    <main>Main content</main>
';

// Extract cell
$header = pagina_celula('header');
// Returns: "<header>Header</header>"

// Page now has: <!-- header --><main>...</main>

// With delete flag
$header = pagina_celula('header', false, true);
// Page now has: <main>...</main> (cell removed)
```

---

### pagina_celula_trocar_variavel_valor()

Substitutes variable in a specific cell.

**Signature:**
```php
function pagina_celula_trocar_variavel_valor($celula, $variavel, $valor, $variavelEspecifica = false)
```

**Parameters:**
- `$celula` (string) - **Required** - Cell HTML
- `$variavel` (string) - **Required** - Variable name
- `$valor` (string) - **Required** - Substitution value
- `$variavelEspecifica` (bool) - **Optional** - Use variable without delimiters

**Usage Example:**
```php
$cell = '<h1>[[title]]</h1><p>[[description]]</p>';

// Substitute with automatic delimiters
$cell = pagina_celula_trocar_variavel_valor($cell, 'title', 'My Title');
// Result: "<h1>My Title</h1><p>[[description]]</p>"

// Substitute multiple variables
$cell = pagina_celula_trocar_variavel_valor($cell, 'description', 'Descriptive text');
// Result: "<h1>My Title</h1><p>Descriptive text</p>"
```

---

### pagina_celula_incluir()

Inserts cell content back into the page.

**Signature:**
```php
function pagina_celula_incluir($celula, $valor)
```

**Parameters:**
- `$celula` (string) - **Required** - Cell name
- `$valor` (string) - **Required** - Content to insert

**Usage Example:**
```php
// Page has: <!-- menu --><main>...</main>

// Insert processed menu
$menu_html = '<nav><a href="/">Home</a></nav>';
pagina_celula_incluir('menu', $menu_html);

// Page now: <nav><a href="/">Home</a></nav><main>...</main>
```

---

### pagina_trocar_variavel_valor()

Substitutes variable directly in the global page.

**Signature:**
```php
function pagina_trocar_variavel_valor($variavel, $valor, $variavelEspecifica = false)
```

**Usage Example:**
```php
// Page has: <title>[[site-title]]</title>

pagina_trocar_variavel_valor('site-title', 'My Site');

// Page now: <title>My Site</title>
```

---

### pagina_trocar_variavel()

Substitutes variable in arbitrary code.

**Signature:**
```php
function pagina_trocar_variavel($params = false)
```

**Parameters (Associative Array):**
- `codigo` (string) - **Required** - HTML with variable
- `variavel` (string) - **Required** - Variable name
- `valor` (string) - **Required** - Substitution value

**Usage Example:**
```php
$template = '<div class="[[class]]">[[content]]</div>';

$html = pagina_trocar_variavel(Array(
    'codigo' => $template,
    'variavel' => 'class',
    'valor' => 'highlight'
));

$html = pagina_trocar_variavel(Array(
    'codigo' => $html,
    'variavel' => 'content',
    'valor' => 'Important text'
));

// Result: <div class="highlight">Important text</div>
```

---

### pagina_variaveis_globais_mascarar()

Masks global variables for database storage.

**Signature:**
```php
function pagina_variaveis_globais_mascarar($params = false)
```

**Parameters (Associative Array):**
- `valor` (string) - **Required** - HTML with variables

**Return:**
- (string) - Masked HTML

**Usage Example:**
```php
// Convert to database format
$html = '<h1>[[title]]</h1>';
$masked = pagina_variaveis_globais_mascarar(Array(
    'valor' => $html
));
// Returns: "<h1>@[[title]]@</h1>"

// Save to database
banco_update_campo('content', $masked);
```

---

### pagina_variaveis_globais_desmascarar()

Unmasks global variables coming from database.

**Signature:**
```php
function pagina_variaveis_globais_desmascarar($params = false)
```

**Usage Example:**
```php
// Retrieve from database
$data = banco_select(Array(
    'campos' => Array('content'),
    'tabela' => 'pages',
    'extra' => "WHERE id='123'",
    'unico' => true
));

// Unmask
$html = pagina_variaveis_globais_desmascarar(Array(
    'valor' => $data['content']
));
// Converts "@[[variable]]@" back to "[[variable]]"
```

---

## Common Use Cases

### 1. Template System

```php
function render_page($template_id, $data) {
    // Load template
    $template = banco_select(Array(
        'campos' => Array('html'),
        'tabela' => 'templates',
        'extra' => "WHERE id='$template_id'",
        'unico' => true
    ));
    
    $_GESTOR['pagina'] = $template['html'];
    
    // Substitute variables
    foreach ($data as $variable => $value) {
        pagina_trocar_variavel_valor($variable, $value);
    }
    
    return $_GESTOR['pagina'];
}

// Usage
$html = render_page('product-detail', Array(
    'product-name' => 'Notebook',
    'price' => '$ 2,500.00',
    'description' => 'High performance notebook'
));
```

### 2. Conditional Cell Processing

```php
function process_product_page($product) {
    // Extract cells
    $cel_discount = pagina_celula('discount');
    $cel_stock = pagina_celula('out-of-stock');
    
    // Show discount only if exists
    if ($product['discount'] > 0) {
        $cel_discount = pagina_celula_trocar_variavel_valor(
            $cel_discount, 
            'percentage', 
            $product['discount'] . '%'
        );
        pagina_celula_incluir('discount', $cel_discount);
    } else {
        // Remove discount cell
        pagina_celula('discount', false, true);
    }
    
    // Show warning if out of stock
    if ($product['stock'] == 0) {
        pagina_celula_incluir('out-of-stock', $cel_stock);
    } else {
        pagina_celula('out-of-stock', false, true);
    }
}
```

### 3. Content Editor

```php
function save_editor_content($page_id, $content) {
    // Mask variables before saving
    $masked_content = pagina_variaveis_globais_mascarar(Array(
        'valor' => $content
    ));
    
    banco_update(
        "content='" . banco_escape_field($masked_content) . "'",
        'pages',
        "WHERE id='$page_id'"
    );
}

function load_editor_content($page_id) {
    $page = banco_select(Array(
        'campos' => Array('content'),
        'tabela' => 'pages',
        'extra' => "WHERE id='$page_id'",
        'unico' => true
    ));
    
    // Unmask for editing
    return pagina_variaveis_globais_desmascarar(Array(
        'valor' => $page['content']
    ));
}
```

### 4. Listing with Item Template

```php
function list_products() {
    // Page template has item cell
    $cel_item = pagina_celula('item');
    
    $products = banco_select(Array(
        'campos' => Array('name', 'price', 'image'),
        'tabela' => 'products'
    ));
    
    $html_items = '';
    
    foreach ($products as $product) {
        $item = $cel_item;
        $item = pagina_celula_trocar_variavel_valor($item, 'name', $product['name']);
        $item = pagina_celula_trocar_variavel_valor($item, 'price', $product['price']);
        $item = pagina_celula_trocar_variavel_valor($item, 'image', $product['image']);
        
        $html_items .= $item;
    }
    
    pagina_celula_incluir('item', $html_items);
}
```

---

## Patterns and Best Practices

### Cell Naming

```php
// ✅ GOOD - Descriptive names
<!-- header < -->...<!-- header > -->
<!-- main-menu < -->...<!-- main-menu > -->
<!-- dynamic-content < -->...<!-- dynamic-content > -->

// ❌ AVOID - Generic names
<!-- div1 < -->...<!-- div1 > -->
<!-- block < -->...<!-- block > -->
```

### Processing Order

```php
// ✅ Process cells before variables
$cell = pagina_celula('product');
$cell = pagina_celula_trocar_variavel_valor($cell, 'name', $name);
pagina_celula_incluir('product', $cell);

pagina_trocar_variavel_valor('page-title', 'Product List');
```

### Consistent Masking

```php
// ✅ Always mask when saving
$html_save = pagina_variaveis_globais_mascarar(Array('valor' => $html));

// ✅ Always unmask when loading
$html_edit = pagina_variaveis_globais_desmascarar(Array('valor' => $html_db));
```

---

## Limitations and Considerations

### Performance

- String operations can be slow with large HTML
- Cache results when possible

### Nesting

- Cells should not be nested
- One cell per content block

### Encoding

- Careful with special characters in variables
- Use `htmlspecialchars()` when necessary

---

## See Also

- [LIBRARY-TEMPLATE.md](./LIBRARY-TEMPLATE.md) - Template functions
- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Components and layouts

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
