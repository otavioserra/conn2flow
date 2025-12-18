# Library: widgets.php

> 🧩 Reusable widgets system

## Overview

The `widgets.php` library provides a system for creating and managing widgets - reusable components with their own functionality, isolated CSS, and JavaScript. Supports form validation, reCAPTCHA integration, and access control.

**Location**: `gestor/bibliotecas/widgets.php`  
**Version**: 1.0.1  
**Total Functions**: 4 (3 main + 1 specific controller)

## Dependencies

- **Libraries**: gestor.php, modelo.php, formulario.php, autenticacao.php
- **Global Variables**: `$_GESTOR`, `$_CONFIG`
- **JavaScript**: widgets.js, jQuery-Mask-Plugin

## Global Variables

```php
$_GESTOR['biblioteca-widgets'] = Array(
    'versao' => '1.0.1',
    'widgets' => Array(
        'formulario-contato' => Array(
            'versao' => '1.0.2',
            'componenteID' => 'widgets-formulario-contato',
            'jsCaminho' => 'widgets.js',
            'modulosExtras' => 'contatos'
        ),
        // Add more widgets here
    ),
);

// Cache of included CSS/JS
$_GESTOR['widgets-css'][$widget_id] = true;
$_GESTOR['widgets-js'][$js_path] = true;
```

---

## Widget Structure

### Configuration

Each widget is defined in `$_GESTOR['biblioteca-widgets']['widgets']`:

```php
'widget-id' => Array(
    'versao' => '1.0.0',              // Widget version
    'componenteID' => 'componente-id', // HTML component ID
    'jsCaminho' => 'script.js',        // JavaScript file
    'modulosExtras' => 'modulo1,modulo2' // Modules for variables
)
```

### HTML Component

Stored in the `componentes` database table with:
- Widget HTML
- Isolated CSS
- Replaceable variables

---

## Main Functions

### widgets_get()

Renders and returns a complete widget.

**Signature:**
```php
function widgets_get($params = false)
```

**Parameters (Associative Array):**
- `id` (string) - **Required** - Unique widget identifier

**Return:**
- (string) - Rendered widget HTML

**Usage Example:**
```php
// Include contact form widget
$widget_html = widgets_get(Array(
    'id' => 'formulario-contato'
));

echo $widget_html;
```

**Behavior:**
1. Fetches widget configuration
2. Loads HTML component from database
3. Executes specific controller (if exists)
4. Includes CSS only once
5. Includes JavaScript only once
6. Registers extra modules for variables
7. Returns processed HTML

**Notes:**
- CSS and JS are included only once per page
- Uses cache to avoid duplication
- Global variables are automatically replaced

---

### widgets_search()

Searches for a widget configuration.

**Signature:**
```php
function widgets_search($params = false)
```

**Parameters (Associative Array):**
- `id` (string) - **Required** - Widget identifier

**Return:**
- (array|null) - Widget configuration or null

**Usage Example:**
```php
$config = widgets_search(Array(
    'id' => 'formulario-contato'
));

if ($config) {
    echo "Version: " . $config['versao'];
    echo "Component: " . $config['componenteID'];
}
```

---

### widgets_controller()

Central controller that dispatches to specific controllers.

**Signature:**
```php
function widgets_controller($params = false)
```

**Parameters (Associative Array):**
- `id` (string) - **Required** - Widget ID
- `html` (string) - **Required** - Widget HTML

**Return:**
- (string) - Processed HTML

**Usage Example:**
```php
// Internal use by widgets_get() function
$html = widgets_controller(Array(
    'id' => 'formulario-contato',
    'html' => $widget_html
));
```

**Available Controllers:**
- `'formulario-contato'` → `widgets_formulario_contato()`

---

### widgets_formulario_contato()

Specific controller for the contact form widget.

**Signature:**
```php
function widgets_formulario_contato($params = false)
```

**Parameters (Associative Array):**
- `html` (string) - **Required** - Widget HTML

**Return:**
- (string) - Processed HTML with validations and controls

**Features:**
1. **Form Validation**
   - Name required
   - Valid email
   - Phone not empty
   - Message required

2. **Access Control**
   - Checks rate limiting by IP
   - Shows message if blocked
   - Hides form if blocked

3. **reCAPTCHA**
   - Integrates Google reCAPTCHA v3
   - Activates only if configured
   - Bypass for whitelisted users

4. **Input Masks**
   - Includes jQuery Mask Plugin
   - Applies masks automatically

**Usage Example:**
```php
// Include widget on page
echo widgets_get(Array('id' => 'formulario-contato'));

// Resulting HTML includes:
// - Form with validation
// - reCAPTCHA (if configured)
// - Phone mask
// - Block message (if applicable)
```

---

## Common Use Cases

### 1. Create New Widget

```php
// 1. Register widget
$_GESTOR['biblioteca-widgets']['widgets']['my-widget'] = Array(
    'versao' => '1.0.0',
    'componenteID' => 'componente-my-widget',
    'jsCaminho' => 'my-widget.js',
    'modulosExtras' => 'my-module'
);

// 2. Create component in database
banco_insert_name(Array(
    Array('id', 'componente-my-widget'),
    Array('html', '<div class="my-widget">[[content]]</div>'),
    Array('css', '.my-widget { padding: 20px; }'),
    Array('language', 'en')
), 'componentes');

// 3. Create controller (optional)
function widgets_my_widget($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        // Process HTML
        $html = str_replace('[[content]]', 'My Content', $html);
        return $html;
    }
    
    return '';
}

// 4. Add to controller
function widgets_controller($params = false){
    // ... existing code ...
    switch($id){
        case 'formulario-contato': 
            $html = widgets_formulario_contato(Array('html' => $html)); 
            break;
        case 'my-widget':
            $html = widgets_my_widget(Array('html' => $html));
            break;
    }
    // ...
}

// 5. Use on page
echo widgets_get(Array('id' => 'my-widget'));
```

### 2. Newsletter Widget

```php
// Register
$_GESTOR['biblioteca-widgets']['widgets']['newsletter'] = Array(
    'versao' => '1.0.0',
    'componenteID' => 'widget-newsletter',
    'jsCaminho' => 'newsletter.js'
);

// Controller
function widgets_newsletter($params = false) {
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        gestor_incluir_biblioteca('formulario');
        
        // Validation
        formulario_validacao(Array(
            'formId' => 'form-newsletter',
            'validacao' => Array(
                Array(
                    'regra' => 'email',
                    'campo' => 'email',
                    'label' => 'Email'
                )
            )
        ));
        
        // Process submission
        if (isset($_POST['email'])) {
            $email = $_POST['email'];
            
            // Save to database
            banco_insert_name(Array(
                Array('email', $email),
                Array('data_cadastro', 'NOW()', true, false)
            ), 'newsletter_emails');
            
            // Show success message
            $html = str_replace('<!-- form < -->', '', $html);
            $html = str_replace('<!-- form > -->', '', $html);
        }
        
        return $html;
    }
    
    return '';
}
```

### 3. Search Widget

```php
function widgets_search($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        // Process search
        if (isset($_GET['q'])) {
            $term = banco_escape_field($_GET['q']);
            
            $results = banco_select(Array(
                'campos' => Array('titulo', 'resumo', 'url'),
                'tabela' => 'conteudos',
                'extra' => "WHERE titulo LIKE '%$term%' OR conteudo LIKE '%$term%' LIMIT 10"
            ));
            
            $cel_result = modelo_tag_val($html, '<!-- resultado < -->', '<!-- resultado > -->');
            $html = modelo_tag_in($html, '<!-- resultado < -->', '<!-- resultado > -->', '<!-- resultados -->');
            
            if ($results) {
                $html_results = '';
                
                foreach ($results as $result) {
                    $item = $cel_result;
                    $item = str_replace('[[titulo]]', $result['titulo'], $item);
                    $item = str_replace('[[resumo]]', $result['resumo'], $item);
                    $item = str_replace('[[url]]', $result['url'], $item);
                    $html_results .= $item;
                }
                
                $html = modelo_var_in($html, '<!-- resultados -->', $html_results);
            } else {
                $html = modelo_var_in($html, '<!-- resultados -->', '<p>No results found.</p>');
            }
        }
        
        return $html;
    }
    
    return '';
}
```

### 4. Widget with Authentication

```php
function widgets_user_area($params = false) {
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        gestor_incluir_biblioteca('autenticacao');
        
        $user = gestor_usuario();
        
        if ($user) {
            // User logged in
            $html = modelo_tag_in($html, '<!-- nao-logado < -->', '<!-- nao-logado > -->', '');
            $html = str_replace('[[nome-usuario]]', $user['nome'], $html);
            $html = str_replace('[[email-usuario]]', $user['email'], $html);
        } else {
            // User not logged in
            $html = modelo_tag_in($html, '<!-- logado < -->', '<!-- logado > -->', '');
        }
        
        return $html;
    }
    
    return '';
}
```

### 5. Widget with Ajax

```php
function widgets_comments($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        $page_id = $_GET['pagina_id'] ?? null;
        
        if ($page_id) {
            // Load comments
            $comments = banco_select(Array(
                'campos' => Array('autor', 'comentario', 'data'),
                'tabela' => 'comentarios',
                'extra' => "WHERE pagina_id='$page_id' AND aprovado=1 ORDER BY data DESC"
            ));
            
            $cel_comment = modelo_tag_val($html, '<!-- comentario < -->', '<!-- comentario > -->');
            $html = modelo_tag_in($html, '<!-- comentario < -->', '<!-- comentario > -->', '<!-- lista-comentarios -->');
            
            $html_comments = '';
            
            if ($comments) {
                foreach ($comments as $comment) {
                    $item = $cel_comment;
                    $item = str_replace('[[autor]]', htmlspecialchars($comment['autor']), $item);
                    $item = str_replace('[[comentario]]', htmlspecialchars($comment['comentario']), $item);
                    $item = str_replace('[[data]]', date('d/m/Y H:i', strtotime($comment['data'])), $item);
                    $html_comments .= $item;
                }
            } else {
                $html_comments = '<p>No comments yet. Be the first!</p>';
            }
            
            $html = modelo_var_in($html, '<!-- lista-comentarios -->', $html_comments);
            $html = str_replace('[[pagina-id]]', $page_id, $html);
        }
        
        return $html;
    }
    
    return '';
}
```

---

## reCAPTCHA Integration

### Configuration

```php
// In configuracao.php or similar
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'your-site-key';
$_CONFIG['usuario-recaptcha-secret'] = 'your-secret-key';
```

### Validation in Controller

```php
function validate_recaptcha($token) {
    global $_CONFIG;
    
    $secret = $_CONFIG['usuario-recaptcha-secret'];
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = Array(
        'secret' => $secret,
        'response' => $token
    );
    
    $options = Array(
        'http' => Array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response, true);
    
    return $result['success'] && $result['score'] >= 0.5;
}
```

---

## Patterns and Best Practices

### CSS Isolation

```php
// ✅ GOOD - CSS with specific prefix
.widget-formulario-contato input {
    /* styles */
}

// ❌ AVOID - Generic CSS
input {
    /* affects all inputs on page */
}
```

### Versioning

```php
// ✅ Increment version when updating JavaScript
'versao' => '1.0.1', // CSS/HTML changed
'versao' => '1.1.0', // JS changed (cache-busting)
```

### Validation

```php
// ✅ Always validate on server
// Do not trust only JavaScript validation
```

---

## Limitations and Considerations

### Performance

- Widgets include CSS/JS on page
- Multiple widgets can increase page size
- Use minification in production

### Cache

- CSS/JS are cached by version
- Incrementing version forces reload
- Browser cache can cause issues

### Security

- Always sanitize user input
- Use `htmlspecialchars()` in output
- Validate on server, not just client

---

## See Also

- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Components
- [LIBRARY-FORM.md](./LIBRARY-FORM.md) - Validation
- [LIBRARY-AUTHENTICATION.md](./LIBRARY-AUTHENTICATION.md) - Access control
- [LIBRARY-TEMPLATE.md](./LIBRARY-TEMPLATE.md) - Templates

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
