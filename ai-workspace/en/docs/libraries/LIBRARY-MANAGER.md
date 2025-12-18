# Library: gestor.php

> 🚀 Core CMS Engine for Conn2Flow

## Overview

The `gestor.php` library is the heart of the Conn2Flow system, providing 24 essential functions for content management, components, layouts, sessions, users, and pages.

**Location**: `gestor/bibliotecas/gestor.php`  
**Total Functions**: 24

## Main Categories

### Library Management
- `gestor_incluir_biblioteca()` - Dynamically includes a library
- `gestor_biblioteca_existe()` - Checks if a library exists
- `gestor_carregar_bibliotecas()` - Loads all libraries

### Components and Layouts
- `gestor_componente()` - Loads component from database
- `gestor_layout()` - Loads page layout
- `gestor_template()` - Processes template
- `gestor_renderizar()` - Renders final content

### Session and User
- `gestor_sessao_iniciar()` - Starts session
- `gestor_sessao_variavel()` - Get/set session variable
- `gestor_sessao_variavel_del()` - Removes session variable
- `gestor_usuario()` - Returns logged user data
- `gestor_usuario_logado()` - Checks if logged in

### Pages and Routes
- `gestor_pagina()` - Loads page
- `gestor_pagina_atual()` - Returns current page
- `gestor_pagina_redirecionar()` - Redirects to URL
- `gestor_pagina_variaveis_globais()` - Replaces global variables
- `gestor_pagina_javascript_incluir()` - Adds JavaScript
- `gestor_pagina_css_incluir()` - Adds CSS

### Variables and Configuration
- `gestor_variaveis()` - Fetches system variable
- `gestor_configuracao()` - Fetches configuration
- `gestor_idioma()` - Returns current language

### Utilities
- `gestor_url()` - Generates system URL
- `gestor_upload()` - Processes file upload
- `gestor_cache()` - Cache system

## Usage Examples

### Include Library

```php
// Load library when needed
gestor_incluir_biblioteca('banco');
gestor_incluir_biblioteca('email');

// Use library functions
banco_select(/*...*/);
email_enviar(/*...*/);
```

### Load Component

```php
// Simple component
$menu = gestor_componente(Array(
    'id' => 'main-menu'
));

echo $menu['html'];

// Component with CSS
$card = gestor_componente(Array(
    'id' => 'product-card',
    'return_css' => true
));

gestor_pagina_css_incluir($card['css']);
echo $card['html'];
```

### Manage Session

```php
// Start session
gestor_sessao_iniciar();

// Save to session
gestor_sessao_variavel('cart', Array(
    'items' => Array(),
    'total' => 0
));

// Retrieve from session
$cart = gestor_sessao_variavel('cart');

// Remove from session
gestor_sessao_variavel_del('cart');
```

### Logged User

```php
// Check if logged in
if (gestor_usuario_logado()) {
    $user = gestor_usuario();
    
    echo "Hello, {$user['nome']}!";
    echo "Email: {$user['email']}";
    echo "Level: {$user['nivel']}";
} else {
    gestor_pagina_redirecionar('/login');
}
```

### Process Page

```php
// Load main layout
$layout = gestor_layout(Array(
    'id' => 'main-layout',
    'return_css' => true
));

// Process global variables
$html = gestor_pagina_variaveis_globais(Array(
    'html' => $layout['html']
));

// Add CSS and JS
gestor_pagina_css_incluir($layout['css']);
gestor_pagina_javascript_incluir('<script src="/app.js"></script>');

// Render
echo gestor_renderizar(Array(
    'html' => $html
));
```

### File Upload

```php
if (isset($_FILES['file'])) {
    $result = gestor_upload(Array(
        'campo' => 'file',
        'destino' => '/uploads/documents/',
        'tipos_permitidos' => Array('pdf', 'doc', 'docx'),
        'tamanho_maximo' => 5242880  // 5MB
    ));
    
    if ($result['sucesso']) {
        echo "File saved: " . $result['caminho'];
    } else {
        echo "Error: " . $result['erro'];
    }
}
```

### Cache

```php
// Save to cache
gestor_cache(Array(
    'acao' => 'set',
    'chave' => 'featured_products',
    'valor' => $products,
    'ttl' => 3600  // 1 hour
));

// Retrieve from cache
$products = gestor_cache(Array(
    'acao' => 'get',
    'chave' => 'featured_products'
));

if (!$products) {
    // Cache expired, fetch from DB
    $products = banco_select(/*...*/);
    gestor_cache(Array(
        'acao' => 'set',
        'chave' => 'featured_products',
        'valor' => $products
    ));
}
```

### System Variables

```php
// Fetch language variable
$btn_title = gestor_variaveis(Array(
    'modulo' => 'interface',
    'id' => 'btn-save'
));

echo "<button>$btn_title</button>";

// Fetch configuration
$items_per_page = gestor_configuracao(Array(
    'chave' => 'pagination.items_per_page',
    'padrao' => 20
));
```

## Common Use Cases

### 1. Blog Page

```php
// Load layout
$layout = gestor_layout(Array('id' => 'blog-layout'));

// Fetch posts
gestor_incluir_biblioteca('banco');
$posts = banco_select(Array(
    'campos' => Array('title', 'content', 'date'),
    'tabela' => 'posts',
    'extra' => 'ORDER BY date DESC LIMIT 10'
));

// Render post list
$html = '';
foreach ($posts as $post) {
    $html .= gestor_componente(Array(
        'id' => 'post-item',
        'variaveis' => Array(
            '[[title]]' => $post['title'],
            '[[content]]' => $post['content'],
            '[[date]]' => $post['date']
        )
    ))['html'];
}

// Insert into layout
$layout['html'] = str_replace('<!-- posts -->', $html, $layout['html']);

// Process global variables and render
echo gestor_renderizar(Array(
    'html' => gestor_pagina_variaveis_globais(Array(
        'html' => $layout['html']
    ))
));
```

### 2. Admin Area

```php
// Check authentication
if (!gestor_usuario_logado()) {
    gestor_pagina_redirecionar('/admin/login');
}

$user = gestor_usuario();

// Check permission
if ($user['nivel'] !== 'admin') {
    die('Access denied');
}

// Load dashboard
$dashboard = gestor_componente(Array(
    'id' => 'admin-dashboard'
));

// Include necessary libraries
gestor_incluir_biblioteca('interface');
gestor_incluir_biblioteca('banco');

// Fetch statistics
$stats = Array(
    'users' => banco_count('users'),
    'products' => banco_count('products'),
    'sales_today' => banco_sum('sales', 'amount', "WHERE DATE(date)=CURDATE()")
);

// Replace variables
$html = $dashboard['html'];
foreach ($stats as $key => $value) {
    $html = str_replace("[[$key]]", $value, $html);
}

echo gestor_renderizar(Array('html' => $html));
```

### 3. Multi-language System

```php
// Detect language
$lang = gestor_idioma();

// Load language texts
$texts = Array(
    'pt' => Array(
        'welcome' => 'Bem-vindo',
        'logout' => 'Sair'
    ),
    'en' => Array(
        'welcome' => 'Welcome',
        'logout' => 'Logout'
    )
);

// Use in template
$layout = gestor_layout(Array('id' => 'main'));
$layout['html'] = str_replace('[[welcome]]', $texts[$lang]['welcome'], $layout['html']);

echo gestor_renderizar(Array('html' => $layout['html']));
```

## Architecture

### Request Flow

1. `index.php` starts session and loads manager
2. `gestor_pagina()` identifies route
3. `gestor_layout()` loads base layout
4. `gestor_componente()` loads components
5. `gestor_pagina_variaveis_globais()` replaces variables
6. `gestor_renderizar()` generates final HTML

### Data Structure

```php
$_GESTOR = Array(
    'versao' => '2.3.0',
    'url-raiz' => '/var/www/',
    'host-id' => 1,
    'usuario' => Array(/*...*/),
    'pagina' => '...',
    'idioma' => 'pt',
    'bibliotecas' => Array(/*...*/),
    'javascript' => Array(/*...*/),
    'css' => Array(/*...*/)
);
```

## Patterns and Best Practices

### Load Libraries on Demand

```php
// ✅ GOOD - Load only when needed
if (isset($_POST['email'])) {
    gestor_incluir_biblioteca('comunicacao');
    comunicacao_email(/*...*/);
}

// ❌ AVOID - Loading everything always
gestor_incluir_biblioteca('comunicacao');
gestor_incluir_biblioteca('pdf');
gestor_incluir_biblioteca('ftp');
// ... minimal usage
```

### Smart Caching

```php
// ✅ Cache heavy components
$key = "component_menu_{$lang}";
$menu = gestor_cache(Array('acao' => 'get', 'chave' => $key));

if (!$menu) {
    $menu = gestor_componente(Array('id' => 'menu'));
    gestor_cache(Array('acao' => 'set', 'chave' => $key, 'valor' => $menu));
}
```

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database
- [LIBRARY-AUTHENTICATION.md](./LIBRARY-AUTHENTICATION.md) - Authentication
- [LIBRARY-INTERFACE.md](./LIBRARY-INTERFACE.md) - UI components

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
