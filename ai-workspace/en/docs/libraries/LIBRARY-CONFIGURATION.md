# Library: configuracao.php

> ⚙️ System configuration management

## Overview

The `configuracao.php` library provides functions to manage administration and host (multi-tenant) configurations, allowing saving and retrieving system configuration variables.

**Location**: `gestor/bibliotecas/configuracao.php`  
**Total Functions**: 4

## Dependencies

- **Libraries**: banco.php, gestor.php
- **Global Variables**: `$_GESTOR`, `$_CONFIG`
- **Tables**: `administracao_variaveis`, `hosts_variaveis`

---

## Main Functions

### configuracao_administracao_salvar()

Saves system administration configurations.

**Signature:**
```php
function configuracao_administracao_salvar($params = false)
```

**Parameters (Associative Array):**
- `modulo` (string) - **Required** - Configuration module/category
- `variaveis` (array) - **Required** - Array of variables to save (key => value)

**Usage Example:**
```php
// Save email settings
configuracao_administracao_salvar(Array(
    'modulo' => 'email-config',
    'variaveis' => Array(
        'smtp-host' => 'smtp.gmail.com',
        'smtp-port' => '587',
        'smtp-user' => 'noreply@site.com',
        'smtp-secure' => '1'
    )
));

// Save payment settings
configuracao_administracao_salvar(Array(
    'modulo' => 'payment-gateway',
    'variaveis' => Array(
        'gateway-active' => '1',
        'gateway-api-key' => 'key_123456',
        'gateway-mode' => 'production'
    )
));
```

---

### configuracao_administracao()

Retrieves administration configurations.

**Signature:**
```php
function configuracao_administracao($params = false)
```

**Parameters (Associative Array):**
- `modulo` (string) - **Required** - Configuration module/category

**Return:**
- (array) - Associative array with variables

**Usage Example:**
```php
// Load email settings
$config = configuracao_administracao(Array(
    'modulo' => 'email-config'
));

echo $config['smtp-host'];  // smtp.gmail.com
echo $config['smtp-port'];  // 587

// Use in email sending
$_CONFIG['email']['host'] = $config['smtp-host'];
$_CONFIG['email']['port'] = $config['smtp-port'];
```

---

### configuracao_hosts_salvar()

Saves specific host configurations (multi-tenant).

**Signature:**
```php
function configuracao_hosts_salvar($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Required** - Host ID
- `modulo` (string) - **Required** - Module/category
- `variaveis` (array) - **Required** - Variables to save

**Usage Example:**
```php
// Configure custom theme for host
configuracao_hosts_salvar(Array(
    'id_hosts' => 5,
    'modulo' => 'theme-config',
    'variaveis' => Array(
        'primary-color' => '#0066cc',
        'secondary-color' => '#ff6600',
        'logo-url' => '/uploads/host5/logo.png',
        'font-family' => 'Arial'
    )
));

// Configure host specific email
configuracao_hosts_salvar(Array(
    'id_hosts' => 5,
    'modulo' => 'email-settings',
    'variaveis' => Array(
        'from-email' => 'contact@host5.com',
        'from-name' => 'Host 5 Store'
    )
));
```

---

### configuracao_hosts_variaveis()

Retrieves configurations for a specific host.

**Signature:**
```php
function configuracao_hosts_variaveis($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Required** - Host ID
- `modulo` (string) - **Required** - Module/category

**Return:**
- (array) - Associative array with variables

**Usage Example:**
```php
// Load host theme
$theme = configuracao_hosts_variaveis(Array(
    'id_hosts' => 5,
    'modulo' => 'theme-config'
));

echo "<style>";
echo ":root {";
echo "  --primary-color: {$theme['primary-color']};";
echo "  --secondary-color: {$theme['secondary-color']};";
echo "}";
echo "</style>";

// Load host email settings
$email_config = configuracao_hosts_variaveis(Array(
    'id_hosts' => 5,
    'modulo' => 'email-settings'
));

$_CONFIG['email']['from'] = $email_config['from-email'];
```

---

## Common Use Cases

### 1. Admin Settings Panel

```php
function save_admin_settings() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        configuracao_administracao_salvar(Array(
            'modulo' => 'system-settings',
            'variaveis' => Array(
                'site-title' => $_POST['site_title'],
                'site-description' => $_POST['site_description'],
                'maintenance-mode' => isset($_POST['maintenance']) ? '1' : '0',
                'items-per-page' => $_POST['items_per_page'],
                'timezone' => $_POST['timezone']
            )
        ));
        
        echo "Settings saved successfully!";
    }
}

function load_config_form() {
    $config = configuracao_administracao(Array(
        'modulo' => 'system-settings'
    ));
    
    echo "<form method='post'>";
    echo "  <input name='site_title' value='{$config['site-title']}'>";
    echo "  <textarea name='site_description'>{$config['site-description']}</textarea>";
    echo "  <input type='checkbox' name='maintenance' " . 
         ($config['maintenance-mode'] ? 'checked' : '') . ">";
    echo "  <button>Save</button>";
    echo "</form>";
}
```

### 2. Multi-Tenant with Custom Settings

```php
function apply_host_config($id_hosts) {
    // Load theme
    $theme = configuracao_hosts_variaveis(Array(
        'id_hosts' => $id_hosts,
        'modulo' => 'appearance'
    ));
    
    // Apply custom CSS
    if ($theme) {
        echo "<style>";
        if (isset($theme['custom-css'])) {
            echo $theme['custom-css'];
        }
        echo "</style>";
        
        // Add custom logo
        if (isset($theme['logo-url'])) {
            $_GESTOR['site-logo'] = $theme['logo-url'];
        }
    }
    
    // Load business settings
    $business = configuracao_hosts_variaveis(Array(
        'id_hosts' => $id_hosts,
        'modulo' => 'business-settings'
    ));
    
    if ($business) {
        $_CONFIG['business-hours'] = $business['hours'] ?? '9:00-18:00';
        $_CONFIG['currency'] = $business['currency'] ?? 'BRL';
        $_CONFIG['tax-rate'] = $business['tax-rate'] ?? '0';
    }
}
```

### 3. Feature Flags System

```php
function check_feature_active($feature_name) {
    $features = configuracao_administracao(Array(
        'modulo' => 'feature-flags'
    ));
    
    return isset($features[$feature_name]) && $features[$feature_name] === '1';
}

// Usage
if (check_feature_active('new-checkout')) {
    include 'checkout-v2.php';
} else {
    include 'checkout-v1.php';
}

// Activate feature
configuracao_administracao_salvar(Array(
    'modulo' => 'feature-flags',
    'variaveis' => Array(
        'new-checkout' => '1',
        'ai-recommendations' => '1',
        'dark-mode' => '0'
    )
));
```

### 4. Integration Settings

```php
function configure_payment_gateway() {
    configuracao_administracao_salvar(Array(
        'modulo' => 'payment-gateway',
        'variaveis' => Array(
            'provider' => 'stripe',
            'public-key' => 'pk_live_xxxxx',
            'secret-key' => 'sk_live_xxxxx',
            'webhook-secret' => 'whsec_xxxxx',
            'mode' => 'live'
        )
    ));
}

function process_payment() {
    $gateway = configuracao_administracao(Array(
        'modulo' => 'payment-gateway'
    ));
    
    if ($gateway['provider'] === 'stripe') {
        require_once 'stripe-php/init.php';
        
        \Stripe\Stripe::setApiKey($gateway['secret-key']);
        
        $charge = \Stripe\Charge::create([
            'amount' => 1000,
            'currency' => 'brl',
            'source' => $_POST['stripe_token']
        ]);
    }
}
```

---

## Patterns and Best Practices

### Organization by Module

```php
// ✅ GOOD - Group related settings
configuracao_administracao_salvar(Array(
    'modulo' => 'smtp-config',
    'variaveis' => Array(/* SMTP configs */)
));

configuracao_administracao_salvar(Array(
    'modulo' => 'payment-config',
    'variaveis' => Array(/* payment configs */)
));

// ❌ AVOID - Mixing everything in one module
configuracao_administracao_salvar(Array(
    'modulo' => 'general',
    'variaveis' => Array(
        'smtp-host' => '...',
        'payment-key' => '...',
        'logo-url' => '...'
    )
));
```

### Default Values

```php
// ✅ Always have fallback
$config = configuracao_administracao(Array(
    'modulo' => 'app-settings'
));

$items_per_page = $config['items-per-page'] ?? 20;
$timezone = $config['timezone'] ?? 'America/Sao_Paulo';
```

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations
- [LIBRARY-HOST.md](./LIBRARY-HOST.md) - Multi-tenancy
- [LIBRARY-VARIABLES.md](./LIBRARY-VARIABLES.md) - System variables

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
