# Library: variaveis.php

> 🔧 System variable management

## Overview

The `variaveis.php` library provides functions to manage system configuration variables, organized by groups. Allows getting, including, and updating variables stored in the database.

**Location**: `gestor/bibliotecas/variaveis.php`  
**Version**: 1.0.0  
**Total Functions**: 3

## Dependencies

- **Libraries**: banco.php
- **Global Variables**: `$_GESTOR`, `$_VARIAVEIS_SISTEMA`

## Global Variables

```php
$_GESTOR['biblioteca-variaveis'] = Array(
    'versao' => '1.0.0',
);

// System variables cache
$_VARIAVEIS_SISTEMA[$group][$id] = 'value';
```

---

## Main Functions

### variaveis_sistema()

Returns a system variable or group of variables.

**Signature:**
```php
function variaveis_sistema($grupo, $id = false)
```

**Parameters:**
- `$grupo` (string) - **Required** - Variable group
- `$id` (string) - **Optional** - Specific variable ID

**Return:**
- (string|array|null) - Variable value, group array, or null

**Usage Example:**
```php
// Get specific variable
$value = variaveis_sistema('email', 'smtp_host');
echo $value; // 'smtp.example.com'

// Get complete group
$config_email = variaveis_sistema('email');
// Array(
//     'smtp_host' => 'smtp.example.com',
//     'smtp_port' => '587',
//     'smtp_user' => 'user@example.com'
// )

// Non-existent variable
$value = variaveis_sistema('email', 'not_exists');
echo $value; // null
```

**Behavior:**
- Caches in `$_VARIAVEIS_SISTEMA` after first query
- Subsequent queries use cache (do not query database)
- If `$id` not provided, returns array with entire group
- If `$id` provided, returns specific value or null

**Notes:**
- Fetches only variables with `modulo='_sistema'`
- Cache persists throughout the request

---

### variaveis_sistema_incluir()

Includes a new system variable if it doesn't exist.

**Signature:**
```php
function variaveis_sistema_incluir($grupo, $id, $valor, $tipo = 'string')
```

**Parameters:**
- `$grupo` (string) - **Required** - Variable group
- `$id` (string) - **Required** - Variable ID
- `$valor` (string) - **Required** - Value to include
- `$tipo` (string) - **Optional** - Variable type (default: 'string')

**Return:**
- (void)

**Usage Example:**
```php
// Include email configuration
variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
variaveis_sistema_incluir('email', 'smtp_port', '587');
variaveis_sistema_incluir('email', 'smtp_ssl', '1', 'bool');

// Include API configuration
variaveis_sistema_incluir('api', 'key', 'abc123xyz');
variaveis_sistema_incluir('api', 'timeout', '30', 'int');

// If variable already exists, does nothing
variaveis_sistema_incluir('email', 'smtp_host', 'other.server.com');
// smtp_host remains 'smtp.gmail.com'
```

**Behavior:**
- Checks if variable already exists before inserting
- If exists, does nothing (does not update)
- Inserts with `modulo='_sistema'`

**Notes:**
- Use `variaveis_sistema_atualizar()` to modify existing values
- `$tipo` parameter is stored but does not affect validation

---

### variaveis_sistema_atualizar()

Updates the value of an existing variable.

**Signature:**
```php
function variaveis_sistema_atualizar($grupo, $id, $valor)
```

**Parameters:**
- `$grupo` (string) - **Required** - Variable group
- `$id` (string) - **Required** - Variable ID
- `$valor` (string) - **Required** - New value

**Return:**
- (void)

**Usage Example:**
```php
// Update configuration
variaveis_sistema_atualizar('email', 'smtp_host', 'smtp.outlook.com');
variaveis_sistema_atualizar('email', 'smtp_port', '465');

// Update API key
variaveis_sistema_atualizar('api', 'key', 'new_key_123');

// Set as NULL
variaveis_sistema_atualizar('cache', 'redis_host', null);
```

**Behavior:**
- Updates value even if variable doesn't exist
- If `$valor` is null, sets field to NULL in database
- Does not validate if variable exists before updating

**Notes:**
- Does not clear cache automatically
- To ensure updated value, reload page or restart application

---

## Common Use Cases

### 1. Initial System Configuration

```php
function initialize_configurations() {
    // Email configurations
    variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
    variaveis_sistema_incluir('email', 'smtp_port', '587');
    variaveis_sistema_incluir('email', 'smtp_user', 'noreply@example.com');
    variaveis_sistema_incluir('email', 'smtp_ssl', '1', 'bool');
    
    // API configurations
    variaveis_sistema_incluir('api', 'google_maps_key', '');
    variaveis_sistema_incluir('api', 'recaptcha_site', '');
    variaveis_sistema_incluir('api', 'recaptcha_secret', '');
    
    // System configurations
    variaveis_sistema_incluir('sistema', 'maintenance', '0', 'bool');
    variaveis_sistema_incluir('sistema', 'debug', '0', 'bool');
    variaveis_sistema_incluir('sistema', 'version', '1.0.0');
}
```

### 2. Settings Panel

```php
function save_email_settings($data) {
    // Update settings
    variaveis_sistema_atualizar('email', 'smtp_host', $data['smtp_host']);
    variaveis_sistema_atualizar('email', 'smtp_port', $data['smtp_port']);
    variaveis_sistema_atualizar('email', 'smtp_user', $data['smtp_user']);
    variaveis_sistema_atualizar('email', 'smtp_pass', $data['smtp_pass']);
    
    return true;
}

// Retrieve to display in form
function get_email_settings() {
    return Array(
        'smtp_host' => variaveis_sistema('email', 'smtp_host'),
        'smtp_port' => variaveis_sistema('email', 'smtp_port'),
        'smtp_user' => variaveis_sistema('email', 'smtp_user')
    );
}
```

### 3. Feature Flags

```php
function feature_active($feature) {
    $value = variaveis_sistema('features', $feature);
    return $value === '1';
}

// Enable/disable features
variaveis_sistema_incluir('features', 'new_dashboard', '0', 'bool');
variaveis_sistema_incluir('features', 'dark_mode', '1', 'bool');
variaveis_sistema_incluir('features', 'support_chat', '1', 'bool');

// Use
if (feature_active('new_dashboard')) {
    // Show new dashboard
} else {
    // Show legacy dashboard
}
```

### 4. Module Configuration

```php
function configure_payment_module() {
    // Check if already configured
    $gateway = variaveis_sistema('payment', 'gateway');
    
    if (!$gateway) {
        // First configuration
        variaveis_sistema_incluir('payment', 'gateway', 'stripe');
        variaveis_sistema_incluir('payment', 'currency', 'USD');
        variaveis_sistema_incluir('payment', 'service_fee', '2.5');
    }
    
    return variaveis_sistema('payment');
}
```

### 5. Configuration Cache

```php
class ConfigManager {
    private $cache = Array();
    
    public function get($group, $id = null) {
        $key = "$group:$id";
        
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = variaveis_sistema($group, $id);
        }
        
        return $this->cache[$key];
    }
    
    public function set($group, $id, $value) {
        variaveis_sistema_atualizar($group, $id, $value);
        
        // Clear cache
        $key = "$group:$id";
        unset($this->cache[$key]);
    }
}

$config = new ConfigManager();
$smtp = $config->get('email', 'smtp_host');
```

---

## Table Structure

```sql
CREATE TABLE variaveis (
    id_variaveis INT PRIMARY KEY AUTO_INCREMENT,
    modulo VARCHAR(100),
    grupo VARCHAR(100),
    id VARCHAR(100),
    valor TEXT,
    tipo VARCHAR(50) DEFAULT 'string',
    INDEX idx_sistema (modulo, grupo, id)
);
```

---

## Patterns and Best Practices

### Naming

```php
// ✅ GOOD - Descriptive names
variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
variaveis_sistema_incluir('email', 'smtp_port', '587');

// ❌ AVOID - Generic names
variaveis_sistema_incluir('config', 'var1', 'value');
```

### Organization by Groups

```php
// ✅ GOOD - Group related settings
variaveis_sistema_incluir('email', 'smtp_host', '...');
variaveis_sistema_incluir('email', 'smtp_port', '...');
variaveis_sistema_incluir('email', 'smtp_user', '...');

variaveis_sistema_incluir('api', 'google_key', '...');
variaveis_sistema_incluir('api', 'stripe_key', '...');
```

### Default Values

```php
// ✅ GOOD - Use default value if not exists
$host = variaveis_sistema('email', 'smtp_host');
if (!$host) {
    $host = 'smtp.gmail.com'; // Default
}

// ✅ BETTER - With ternary operator
$host = variaveis_sistema('email', 'smtp_host') ?: 'smtp.gmail.com';
```

---

## Limitations and Considerations

### Cache Does Not Update Automatically

```php
// ⚠️ Cache is not updated after update
variaveis_sistema_atualizar('email', 'smtp_host', 'new.host.com');
$host = variaveis_sistema('email', 'smtp_host'); // Still returns old value

// Solution: Reload page or clear cache manually
unset($_VARIAVEIS_SISTEMA['email']);
```

### No Type Validation

```php
// Type is only informative, does not validate
variaveis_sistema_incluir('config', 'number', 'abc', 'int');
// Accepts 'abc' even being type 'int'
```

### Only Module '_sistema'

```php
// Only fetches variables with modulo='_sistema'
// Other variables require direct database query
```

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations
- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Manager variables

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
