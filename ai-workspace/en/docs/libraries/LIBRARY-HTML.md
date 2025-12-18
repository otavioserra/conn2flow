# Library: html.php

> 🏗️ HTML element generation and manipulation

## Overview

The `html.php` library provides utility functions to build, manipulate, and format HTML elements programmatically, facilitating dynamic interface generation.

**Location**: `gestor/bibliotecas/html.php`  
**Total Functions**: 8

## Dependencies

- **Libraries**: None (standalone)
- **Global Variables**: None

---

## Main Functions

### html_iniciar()

Starts building an HTML element with an opening tag.

**Signature:**
```php
function html_iniciar($params = false)
```

**Parameters (Associative Array):**
- `tag` (string) - **Required** - HTML tag name
- `atributos` (array) - **Optional** - Element attributes

**Return:**
- (string) - HTML opening tag

**Usage Example:**
```php
// Simple tag
echo html_iniciar(Array('tag' => 'div'));
// Output: <div>

// With attributes
echo html_iniciar(Array(
    'tag' => 'div',
    'atributos' => Array(
        'class' => 'container',
        'id' => 'main',
        'data-role' => 'wrapper'
    )
));
// Output: <div class="container" id="main" data-role="wrapper">
```

---

### html_finalizar()

Ends HTML element with a closing tag.

**Signature:**
```php
function html_finalizar($params = false)
```

**Parameters (Associative Array):**
- `tag` (string) - **Required** - HTML tag name

**Return:**
- (string) - HTML closing tag

**Usage Example:**
```php
echo html_iniciar(Array('tag' => 'div'));
echo "Content";
echo html_finalizar(Array('tag' => 'div'));
// Output: <div>Content</div>
```

---

### html_elemento()

Creates a complete HTML element (opening + content + closing).

**Signature:**
```php
function html_elemento($params = false)
```

**Parameters (Associative Array):**
- `tag` (string) - **Required** - Tag name
- `conteudo` (string) - **Optional** - Inner content
- `atributos` (array) - **Optional** - Attributes

**Return:**
- (string) - Complete HTML element

**Usage Example:**
```php
// Simple element
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => 'Paragraph text'
));
// Output: <p>Paragraph text</p>

// With attributes
echo html_elemento(Array(
    'tag' => 'button',
    'conteudo' => 'Click Here',
    'atributos' => Array(
        'class' => 'btn btn-primary',
        'type' => 'submit',
        'onclick' => 'handleClick()'
    )
));
// Output: <button class="btn btn-primary" type="submit" onclick="handleClick()">Click Here</button>
```

---

### html_atributo()

Generates HTML attributes string from array.

**Signature:**
```php
function html_atributo($params = false)
```

**Parameters (Associative Array):**
- `atributos` (array) - **Required** - Array of attributes

**Return:**
- (string) - Formatted attributes string

**Usage Example:**
```php
$attrs = html_atributo(Array(
    'atributos' => Array(
        'class' => 'form-control',
        'id' => 'email',
        'required' => 'required',
        'placeholder' => 'Enter your email'
    )
));

echo "<input type='text' $attrs>";
// Output: <input type='text' class="form-control" id="email" required="required" placeholder="Enter your email">
```

---

### html_valor()

Extracts attribute value from an HTML element.

**Signature:**
```php
function html_valor($params = false)
```

**Parameters (Associative Array):**
- `html` (string) - **Required** - HTML string
- `atributo` (string) - **Required** - Attribute name

**Return:**
- (string) - Attribute value or empty

**Usage Example:**
```php
$html = '<input type="text" name="username" value="john_doe">';

$value = html_valor(Array(
    'html' => $html,
    'atributo' => 'value'
));

echo $value;  // john_doe
```

---

### html_adicionar_classe()

Adds CSS class to HTML element.

**Signature:**
```php
function html_adicionar_classe($params = false)
```

**Parameters (Associative Array):**
- `html` (string) - **Required** - HTML string
- `classe` (string) - **Required** - Class to add

**Return:**
- (string) - HTML with added class

**Usage Example:**
```php
$html = '<div class="container">Content</div>';

$html = html_adicionar_classe(Array(
    'html' => $html,
    'classe' => 'active'
));

echo $html;
// Output: <div class="container active">Content</div>
```

---

### html_consulta()

Queries/extracts HTML elements using selectors.

**Signature:**
```php
function html_consulta($params = false)
```

**Parameters (Associative Array):**
- `html` (string) - **Required** - HTML string
- `seletor` (string) - **Required** - CSS selector

**Return:**
- (array) - Found elements

**Usage Example:**
```php
$html = '<div><p class="text">Paragraph 1</p><p class="text">Paragraph 2</p></div>';

$paragraphs = html_consulta(Array(
    'html' => $html,
    'seletor' => 'p.text'
));

// Returns array with <p> elements
```

---

### html_beautify()

Formats/indents HTML for better readability.

**Signature:**
```php
function html_beautify($html)
```

**Parameters:**
- `$html` (string) - **Required** - Unformatted HTML

**Return:**
- (string) - Formatted and indented HTML

**Usage Example:**
```php
$html = '<div><p>Text</p><ul><li>Item 1</li><li>Item 2</li></ul></div>';

$formatted = html_beautify($html);

echo $formatted;
/* Output:
<div>
    <p>Text</p>
    <ul>
        <li>Item 1</li>
        <li>Item 2</li>
    </ul>
</div>
*/
```

---

## Common Use Cases

### 1. Form Builder

```php
function generate_form($fields) {
    $html = html_iniciar(Array(
        'tag' => 'form',
        'atributos' => Array(
            'method' => 'post',
            'action' => '/submit',
            'class' => 'form-horizontal'
        )
    ));
    
    foreach ($fields as $field) {
        $html .= html_elemento(Array(
            'tag' => 'div',
            'atributos' => Array('class' => 'form-group'),
            'conteudo' => 
                html_elemento(Array(
                    'tag' => 'label',
                    'conteudo' => $field['label']
                )) .
                html_iniciar(Array(
                    'tag' => 'input',
                    'atributos' => Array(
                        'type' => $field['type'],
                        'name' => $field['name'],
                        'class' => 'form-control',
                        'required' => $field['required'] ? 'required' : null
                    )
                ))
        ));
    }
    
    $html .= html_finalizar(Array('tag' => 'form'));
    
    return $html;
}
```

### 2. Table Generator

```php
function generate_table($data, $columns) {
    $html = html_iniciar(Array(
        'tag' => 'table',
        'atributos' => Array('class' => 'table table-striped')
    ));
    
    // Header
    $html .= html_iniciar(Array('tag' => 'thead'));
    $html .= html_iniciar(Array('tag' => 'tr'));
    foreach ($columns as $column) {
        $html .= html_elemento(Array(
            'tag' => 'th',
            'conteudo' => $column['title']
        ));
    }
    $html .= html_finalizar(Array('tag' => 'tr'));
    $html .= html_finalizar(Array('tag' => 'thead'));
    
    // Body
    $html .= html_iniciar(Array('tag' => 'tbody'));
    foreach ($data as $row) {
        $html .= html_iniciar(Array('tag' => 'tr'));
        foreach ($columns as $column) {
            $html .= html_elemento(Array(
                'tag' => 'td',
                'conteudo' => $row[$column['field']]
            ));
        }
        $html .= html_finalizar(Array('tag' => 'tr'));
    }
    $html .= html_finalizar(Array('tag' => 'tbody'));
    
    $html .= html_finalizar(Array('tag' => 'table'));
    
    return $html;
}
```

### 3. Responsive Cards

```php
function generate_card($title, $description, $image = null) {
    $content = '';
    
    if ($image) {
        $content .= html_iniciar(Array(
            'tag' => 'img',
            'atributos' => Array(
                'src' => $image,
                'class' => 'card-img-top',
                'alt' => $title
            )
        ));
    }
    
    $content .= html_elemento(Array(
        'tag' => 'div',
        'atributos' => Array('class' => 'card-body'),
        'conteudo' => 
            html_elemento(Array(
                'tag' => 'h5',
                'atributos' => Array('class' => 'card-title'),
                'conteudo' => $title
            )) .
            html_elemento(Array(
                'tag' => 'p',
                'atributos' => Array('class' => 'card-text'),
                'conteudo' => $description
            ))
    ));
    
    return html_elemento(Array(
        'tag' => 'div',
        'atributos' => Array('class' => 'card'),
        'conteudo' => $content
    ));
}
```

### 4. Dynamic Breadcrumbs

```php
function generate_breadcrumb($path) {
    $items = '';
    
    foreach ($path as $index => $item) {
        $active = ($index === count($path) - 1);
        
        $classes = 'breadcrumb-item';
        if ($active) {
            $classes .= ' active';
        }
        
        $content = $active ? $item['title'] : 
            html_elemento(Array(
                'tag' => 'a',
                'atributos' => Array('href' => $item['url']),
                'conteudo' => $item['title']
            ));
        
        $items .= html_elemento(Array(
            'tag' => 'li',
            'atributos' => Array('class' => $classes),
            'conteudo' => $content
        ));
    }
    
    return html_elemento(Array(
        'tag' => 'nav',
        'conteudo' => html_elemento(Array(
            'tag' => 'ol',
            'atributos' => Array('class' => 'breadcrumb'),
            'conteudo' => $items
        ))
    ));
}
```

---

## Patterns and Best Practices

### Content Escaping

```php
// ✅ GOOD - Escape user content
$name = htmlspecialchars($_POST['name']);
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => $name
));

// ❌ AVOID - XSS Injection
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => $_POST['name']  // Dangerous!
));
```

### Reusability

```php
// ✅ Create helper functions
function button($text, $type = 'button') {
    return html_elemento(Array(
        'tag' => 'button',
        'atributos' => Array(
            'type' => $type,
            'class' => 'btn btn-primary'
        ),
        'conteudo' => $text
    ));
}
```

---

## See Also

- [LIBRARY-INTERFACE.md](./LIBRARY-INTERFACE.md) - UI components
- [LIBRARY-FORM.md](./LIBRARY-FORM.md) - Forms

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
