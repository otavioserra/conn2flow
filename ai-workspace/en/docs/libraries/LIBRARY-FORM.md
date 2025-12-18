# Library: formulario.php

> 📋 Form validation and processing

## Overview

The `formulario.php` library provides functions for form validation with support for Google reCAPTCHA v3, mandatory field validation, and JavaScript integration.

**Location**: `gestor/bibliotecas/formulario.php`  
**Total Functions**: 5

## Dependencies

- **Libraries**: gestor.php
- **Global Variables**: `$_GESTOR`, `$_CONFIG`
- **JavaScript**: FormValidator.js
- **External API**: Google reCAPTCHA v3

---

## Main Functions

### formulario_incluir_js()

Includes form validation JavaScript.

**Signature:**
```php
function formulario_incluir_js()
```

**Usage Example:**
```php
// Include validation script
formulario_incluir_js();
// Adds <script src=".../FormValidator.js"></script>
```

---

### formulario_validacao()

Configures validation rules for a form.

**Signature:**
```php
function formulario_validacao($params = false)
```

**Parameters (Associative Array):**
- `formId` (string) - **Required** - Form ID
- `validacao` (array) - **Required** - Array of validation rules

**Validation Rules:**
- `texto-obrigatorio` - Text field not empty
- `nao-vazio` - Field not empty
- `email` - Valid email
- `cpf` - Valid CPF
- `cnpj` - Valid CNPJ
- `telefone` - Valid phone
- `cep` - Valid ZIP code (CEP)
- `numero` - Valid number
- `data` - Valid date
- `url` - Valid URL

**Usage Example:**
```php
// Validate registration form
formulario_validacao(Array(
    'formId' => 'form-register',
    'validacao' => Array(
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'nome',
            'label' => 'Full Name'
        ),
        Array(
            'regra' => 'email',
            'campo' => 'email',
            'label' => 'E-mail'
        ),
        Array(
            'regra' => 'telefone',
            'campo' => 'telefone',
            'label' => 'Phone'
        ),
        Array(
            'regra' => 'cpf',
            'campo' => 'cpf',
            'label' => 'CPF'
        )
    )
));

// Validate contact form
formulario_validacao(Array(
    'formId' => 'form-contact',
    'validacao' => Array(
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'nome',
            'label' => 'Name'
        ),
        Array(
            'regra' => 'email',
            'campo' => 'email',
            'label' => 'Email'
        ),
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'mensagem',
            'label' => 'Message'
        )
    )
));
```

---

### formulario_validacao_campos_obrigatorios()

Validates if mandatory fields were filled.

**Signature:**
```php
function formulario_validacao_campos_obrigatorios($params = false)
```

**Parameters (Associative Array):**
- `campos` (array) - **Required** - List of mandatory fields

**Return:**
- (bool) - true if all filled, false otherwise

**Usage Example:**
```php
// Validate on server
$valid = formulario_validacao_campos_obrigatorios(Array(
    'campos' => Array('nome', 'email', 'telefone')
));

if (!$valid) {
    echo "Fill in all mandatory fields!";
    exit;
}

// Process form
process_registration($_POST);
```

---

### formulario_google_recaptcha()

Validates Google reCAPTCHA v3 token.

**Signature:**
```php
function formulario_google_recaptcha()
```

**Return:**
- (bool) - true if valid, false otherwise

**Usage Example:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate reCAPTCHA
    if (!formulario_google_recaptcha()) {
        echo json_encode(Array(
            'erro' => 'reCAPTCHA validation failed'
        ));
        exit;
    }
    
    // Process form
    process_submission($_POST);
}
```

**Configuration:**
```php
// In configuracao.php
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'your-site-key';
$_CONFIG['usuario-recaptcha-secret'] = 'your-secret-key';
$_CONFIG['usuario-recaptcha-score'] = 0.5;  // Minimum score (0-1)
```

---

### formulario_google_recaptcha_tipo()

Returns configured reCAPTCHA type/version.

**Signature:**
```php
function formulario_google_recaptcha_tipo()
```

**Return:**
- (string) - reCAPTCHA type ('v3', 'v2', etc.)

**Usage Example:**
```php
$type = formulario_google_recaptcha_tipo();

if ($type === 'v3') {
    // Include v3 script
    echo '<script src="https://www.google.com/recaptcha/api.js?render=' . 
         $_CONFIG['usuario-recaptcha-site'] . '"></script>';
}
```

---

## Common Use Cases

### 1. Complete Form with Validation

```php
// Form HTML
?>
<form id="form-register" method="post">
    <input type="text" name="nome" placeholder="Full Name">
    <input type="email" name="email" placeholder="E-mail">
    <input type="tel" name="telefone" placeholder="Phone">
    <input type="text" name="cpf" placeholder="CPF">
    <button type="submit">Register</button>
</form>

<?php
// Configure validation
formulario_incluir_js();

formulario_validacao(Array(
    'formId' => 'form-register',
    'validacao' => Array(
        Array('regra' => 'texto-obrigatorio', 'campo' => 'nome', 'label' => 'Name'),
        Array('regra' => 'email', 'campo' => 'email', 'label' => 'E-mail'),
        Array('regra' => 'telefone', 'campo' => 'telefone', 'label' => 'Phone'),
        Array('regra' => 'cpf', 'campo' => 'cpf', 'label' => 'CPF')
    )
));

// Process submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (formulario_validacao_campos_obrigatorios(Array(
        'campos' => Array('nome', 'email', 'telefone', 'cpf')
    ))) {
        // Save to database
        banco_insert_name(Array(
            Array('nome', $_POST['nome']),
            Array('email', $_POST['email']),
            Array('telefone', $_POST['telefone']),
            Array('cpf', $_POST['cpf'])
        ), 'clientes');
        
        echo "Registration successful!";
    }
}
```

### 2. Form with reCAPTCHA v3

```php
// Configure reCAPTCHA
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'site_key';
$_CONFIG['usuario-recaptcha-secret'] = 'secret_key';
$_CONFIG['usuario-recaptcha-score'] = 0.5;

// HTML
?>
<form id="form-contact" method="post">
    <input type="text" name="nome" required>
    <input type="email" name="email" required>
    <textarea name="mensagem" required></textarea>
    <button type="submit">Send</button>
</form>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $_CONFIG['usuario-recaptcha-site']; ?>"></script>
<script>
document.getElementById('form-contact').addEventListener('submit', function(e) {
    e.preventDefault();
    
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo $_CONFIG['usuario-recaptcha-site']; ?>', {
            action: 'submit'
        }).then(function(token) {
            // Add token to form
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'recaptcha_token';
            input.value = token;
            e.target.appendChild(input);
            
            // Submit
            e.target.submit();
        });
    });
});
</script>

<?php
// Process with reCAPTCHA validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!formulario_google_recaptcha()) {
        die('reCAPTCHA failed!');
    }
    
    // Process form
    send_contact_email($_POST);
}
```

### 3. AJAX Validation

```php
// endpoint-validate.php
header('Content-Type: application/json');

$field = $_POST['campo'] ?? '';
$value = $_POST['valor'] ?? '';

$errors = Array();

switch ($field) {
    case 'email':
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid E-mail';
        }
        
        // Check if already exists
        $exists = banco_select(Array(
            'campos' => Array('COUNT(*) as total'),
            'tabela' => 'usuarios',
            'extra' => "WHERE email='$value'",
            'unico' => true
        ));
        
        if ($exists['total'] > 0) {
            $errors[] = 'E-mail already registered';
        }
        break;
        
    case 'cpf':
        if (!validate_cpf($value)) {
            $errors[] = 'Invalid CPF';
        }
        break;
}

echo json_encode(Array(
    'valid' => empty($errors),
    'errors' => $errors
));
```

### 4. Multi-Step Form

```php
session_start();

$step = $_GET['step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save current step data
    $_SESSION['form_data'][$step] = $_POST;
    
    if ($step < 3) {
        // Go to next step
        header("Location: ?step=" . ($step + 1));
        exit;
    } else {
        // Final validation
        if (formulario_google_recaptcha()) {
            // Process all data
            $full_data = array_merge(
                $_SESSION['form_data'][1],
                $_SESSION['form_data'][2],
                $_SESSION['form_data'][3]
            );
            
            save_full_registration($full_data);
            
            // Clear session
            unset($_SESSION['form_data']);
            
            header("Location: /success");
            exit;
        }
    }
}

// Display current step form
switch ($step) {
    case 1:
        include 'form-step-1.php';
        formulario_validacao(Array(
            'formId' => 'form-step-1',
            'validacao' => Array(/* step 1 rules */)
        ));
        break;
    // ...
}
```

---

## Patterns and Best Practices

### Client + Server Validation

```php
// ✅ GOOD - Validate both sides
// Client (UX)
formulario_validacao(Array(
    'formId' => 'form',
    'validacao' => Array(/* rules */)
));

// Server (Security)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!formulario_validacao_campos_obrigatorios(Array(
        'campos' => Array('email', 'senha')
    ))) {
        die('Mandatory fields not filled');
    }
}
```

### Clear Error Messages

```php
// ✅ GOOD - Descriptive labels
Array(
    'regra' => 'email',
    'campo' => 'email',
    'label' => 'E-mail Address'  // Clear and specific
)

// ❌ AVOID - Generic labels
Array(
    'regra' => 'email',
    'campo' => 'email',
    'label' => 'Email'  // Too generic
)
```

---

## See Also

- [LIBRARY-WIDGETS.md](./LIBRARY-WIDGETS.md) - Form widgets
- [LIBRARY-HTML.md](./LIBRARY-HTML.md) - HTML generation
- [Google reCAPTCHA v3](https://developers.google.com/recaptcha/docs/v3)

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
