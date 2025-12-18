# Library: modelo.php

> 📝 Template engine and variable substitution

## Overview

The `modelo.php` library provides functions to manipulate HTML templates, substitute variables, extract and insert content using special tags. Fundamental system for dynamic page generation.

**Location**: `gestor/bibliotecas/modelo.php`  
**Total Functions**: 10

## Dependencies

- **Libraries**: None (standalone)
- **Global Variables**: None

---

## Concepts

### Variables
Placeholders in the format `[[variable-name]]` substituted by values.

### Tags
Special delimiters for content blocks:
- `<!-- tag < -->content<!-- tag > -->` - Normal block
- `<!-- [[variable]] -->` - Insertion point

---

## Main Functions

### modelo_var_troca()

Substitutes first occurrence of variable with value.

**Signature:**
```php
function modelo_var_troca($modelo, $var, $valor)
```

**Parameters:**
- `$modelo` (string) - HTML Template
- `$var` (string) - Variable to substitute
- `$valor` (string) - Substitution value

**Return:**
- (string) - Template with substituted variable

**Usage Example:**
```php
$template = "Hello [[name]], welcome to [[site]]!";

$template = modelo_var_troca($template, '[[name]]', 'John');
// "Hello John, welcome to [[site]]!"

$template = modelo_var_troca($template, '[[site]]', 'Conn2Flow');
// "Hello John, welcome to Conn2Flow!"
```

---

### modelo_var_troca_tudo()

Substitutes all occurrences of variable.

**Signature:**
```php
function modelo_var_troca_tudo($modelo, $var, $valor)
```

**Usage Example:**
```php
$template = "[[product]] costs $ 10. Buy [[product]] now!";

$result = modelo_var_troca_tudo($template, '[[product]]', 'Notebook');
// "Notebook costs $ 10. Buy Notebook now!"
```

---

### modelo_var_in()

Inserts value at marked insertion point.

**Signature:**
```php
function modelo_var_in($modelo, $var, $valor)
```

**Usage Example:**
```php
$template = "
<div>
    <!-- content -->
</div>";

$result = modelo_var_in($template, '<!-- content -->', '<p>Inserted text</p>');
/*
<div>
    <p>Inserted text</p>
</div>
*/
```

---

### modelo_tag_val()

Extracts content between tags.

**Signature:**
```php
function modelo_tag_val($modelo, $tag_in, $tag_out)
```

**Parameters:**
- `$modelo` (string) - HTML Template
- `$tag_in` (string) - Opening tag
- `$tag_out` (string) - Closing tag

**Return:**
- (string) - Content between tags

**Usage Example:**
```php
$template = "
<div>
    <!-- item < -->
    <li>[[title]]</li>
    <!-- item > -->
</div>";

$item_template = modelo_tag_val($template, '<!-- item < -->', '<!-- item > -->');
// "<li>[[title]]</li>"
```

---

### modelo_tag_in()

Substitutes content between tags.

**Signature:**
```php
function modelo_tag_in($modelo, $tag_in, $tag_out, $valor)
```

**Usage Example:**
```php
$template = "
<!-- menu < -->
<nav>Default menu</nav>
<!-- menu > -->";

$result = modelo_tag_in($template, '<!-- menu < -->', '<!-- menu > -->', 
    '<nav><a href="/">Home</a></nav>');
// Substitutes default menu with new one
```

---

### modelo_tag_del()

Removes block between tags.

**Signature:**
```php
function modelo_tag_del($modelo, $tag_in, $tag_out)
```

**Usage Example:**
```php
$template = "
<div>Main content</div>
<!-- debug < -->
<div>Debug info</div>
<!-- debug > -->";

$result = modelo_tag_del($template, '<!-- debug < -->', '<!-- debug > -->');
// Removes debug block completely
```

---

### modelo_tag_troca_val()

Extracts and substitutes content between tags.

**Signature:**
```php
function modelo_tag_troca_val($modelo, $tag_in, $tag_out, $valor)
```

**Usage Example:**
```php
// Similar to tag_in, but returns old value
$old_content = modelo_tag_troca_val($template, '<!-- block < -->', 
    '<!-- block > -->', $new_content);
```

---

### modelo_input_in()

Substitutes HTML input value.

**Signature:**
```php
function modelo_input_in($modelo, $name_input_in, $name_input_out, $valor)
```

**Parameters:**
- `$modelo` (string) - HTML Template
- `$name_input_in` (string) - Input start name
- `$name_input_out` (string) - Input end name
- `$valor` (string) - New value

**Usage Example:**
```php
$form = '<input type="text" name="email" value="">';

$result = modelo_input_in($form, 'name="email"', '>', 'user@example.com');
// '<input type="text" name="email" value="user@example.com">'
```

---

### modelo_var_troca_fim()

Substitutes variable from end to beginning.

**Signature:**
```php
function modelo_var_troca_fim($modelo, $var, $valor)
```

---

### modelo_abrir()

Loads template from file.

**Signature:**
```php
function modelo_abrir($modelo_local)
```

**Parameters:**
- `$modelo_local` (string) - File path

**Return:**
- (string) - Template content

**Usage Example:**
```php
$template = modelo_abrir('/templates/email.html');
$template = modelo_var_troca_tudo($template, '[[name]]', 'John');
```

---

## Common Use Cases

### 1. Email Template System

```php
function send_welcome_email($user) {
    // Load template
    $template = modelo_abrir('/templates/welcome.html');
    
    // Substitute variables
    $template = modelo_var_troca_tudo($template, '[[name]]', $user['name']);
    $template = modelo_var_troca_tudo($template, '[[email]]', $user['email']);
    $template = modelo_var_troca_tudo($template, '[[date]]', date('d/m/Y'));
    
    // Send
    comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $user['email'])),
        'mensagem' => Array(
            'assunto' => 'Welcome!',
            'html' => $template
        )
    ));
}
```

### 2. Listing with Item Template

```php
function list_products() {
    // Page template
    $page = modelo_abrir('/templates/products.html');
    
    // Extract item template
    $item_template = modelo_tag_val($page, '<!-- item < -->', '<!-- item > -->');
    
    // Remove original template
    $page = modelo_tag_in($page, '<!-- item < -->', '<!-- item > -->', '<!-- items -->');
    
    // Fetch products
    $products = banco_select(Array(
        'campos' => Array('name', 'price', 'image'),
        'tabela' => 'products'
    ));
    
    // Generate items HTML
    $html_items = '';
    foreach ($products as $product) {
        $item = $item_template;
        $item = modelo_var_troca_tudo($item, '[[name]]', $product['name']);
        $item = modelo_var_troca_tudo($item, '[[price]]', $product['price']);
        $item = modelo_var_troca_tudo($item, '[[image]]', $product['image']);
        $html_items .= $item;
    }
    
    // Insert items into page
    $page = modelo_var_in($page, '<!-- items -->', $html_items);
    
    echo $page;
}
```

### 3. Conditional Content

```php
function render_profile($user) {
    $template = modelo_abrir('/templates/profile.html');
    
    // Show/hide blocks conditionally
    if ($user['is_premium']) {
        // Keep premium block
        $premium_content = modelo_tag_val($template, '<!-- premium < -->', '<!-- premium > -->');
        $template = modelo_tag_in($template, '<!-- premium < -->', '<!-- premium > -->', $premium_content);
        
        // Remove free block
        $template = modelo_tag_del($template, '<!-- free < -->', '<!-- free > -->');
    } else {
        // Remove premium block
        $template = modelo_tag_del($template, '<!-- premium < -->', '<!-- premium > -->');
        
        // Keep free block
        $free_content = modelo_tag_val($template, '<!-- free < -->', '<!-- free > -->');
        $template = modelo_tag_in($template, '<!-- free < -->', '<!-- free > -->', $free_content);
    }
    
    // Substitute user variables
    $template = modelo_var_troca_tudo($template, '[[name]]', $user['name']);
    
    echo $template;
}
```

### 4. Report Generator

```php
function generate_sales_report($period) {
    $template = modelo_abrir('/templates/report.html');
    
    // Report data
    $sales = fetch_sales($period);
    $total = array_sum(array_column($sales, 'amount'));
    
    // Header
    $template = modelo_var_troca_tudo($template, '[[period]]', $period);
    $template = modelo_var_troca_tudo($template, '[[total]]', number_format($total, 2));
    $template = modelo_var_troca_tudo($template, '[[date-generated]]', date('d/m/Y H:i'));
    
    // Items
    $row_template = modelo_tag_val($template, '<!-- row < -->', '<!-- row > -->');
    $template = modelo_tag_in($template, '<!-- row < -->', '<!-- row > -->', '<!-- rows -->');
    
    $html_rows = '';
    foreach ($sales as $sale) {
        $row = $row_template;
        $row = modelo_var_troca_tudo($row, '[[date]]', $sale['date']);
        $row = modelo_var_troca_tudo($row, '[[client]]', $sale['client']);
        $row = modelo_var_troca_tudo($row, '[[amount]]', number_format($sale['amount'], 2));
        $html_rows .= $row;
    }
    
    $template = modelo_var_in($template, '<!-- rows -->', $html_rows);
    
    return $template;
}
```

---

## Patterns and Best Practices

### Variable Naming

```php
// ✅ GOOD - Descriptive and consistent
[[user-name]]
[[registration-date]]
[[order-total]]

// ❌ AVOID - Ambiguous
[[n]]
[[d]]
[[t]]
```

### Tag Organization

```php
// ✅ GOOD - Clear and well-defined tags
<!-- header < -->
<header>...</header>
<!-- header > -->

<!-- item < -->
<div class="item">...</div>
<!-- item > -->

// ❌ AVOID - Generic tags
<!-- a < -->
...
<!-- a > -->
```

---

## See Also

- [LIBRARY-PAGE.md](./LIBRARY-PAGE.md) - Page manipulation
- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Components and layouts

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
