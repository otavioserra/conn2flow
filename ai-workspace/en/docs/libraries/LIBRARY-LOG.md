# Library: log.php

> 📝 Logging and Auditing System

## Overview

The `log.php` library provides functions for recording actions and changes in the system, supporting logging in the database (history/audit) and on disk (log files). Essential for traceability and debugging.

**Location**: `gestor/bibliotecas/log.php`  
**Version**: 1.1.0  
**Total Functions**: 5

## Dependencies

- **Libraries**: banco.php, gestor.php
- **Global Variables**: `$_GESTOR`
- **Table**: `historico` (database)

## Global Variables

```php
$_GESTOR['biblioteca-log'] = Array(
    'versao' => '1.1.0',
);

// Log settings
$_GESTOR['debug'] // If true, prints logs to screen instead of file
$_GESTOR['logs-path'] // Directory for log files (default: gestor/logs/)
```

---

## Main Functions

### log_debugar()

Records changes in history with current user context.

**Signature:**
```php
function log_debugar($params = false)
```

**Parameters (Associative Array):**
- `alteracoes` (array) - **Optional** - List of changes to record
  - `alteracao` (string) - Change ID (language key)
  - `alteracao_txt` (string) - Literal text of the change
  - `modulo` (string) - Source module
  - `id` (string) - ID of the affected record

**Return:**
- (void)

**Usage Example:**
```php
// Record simple change
log_debugar(Array(
    'alteracoes' => Array(
        Array(
            'alteracao' => 'product-name-changed',
            'alteracao_txt' => 'Name changed to: Dell Notebook',
            'modulo' => 'products',
            'id' => '123'
        )
    )
));

// Multiple changes
log_debugar(Array(
    'alteracoes' => Array(
        Array(
            'alteracao' => 'price-changed',
            'alteracao_txt' => 'Price: $ 1,500.00 → $ 1,200.00'
        ),
        Array(
            'alteracao' => 'stock-updated',
            'alteracao_txt' => 'Stock: 10 → 15'
        )
    )
));
```

**Behavior:**
- Gets current user via `gestor_usuario()`
- Automatically records `id_usuarios`
- Timestamp with `NOW()`
- Inserts into `historico` table

---

### log_controladores()

Records controller actions with versioning.

**Signature:**
```php
function log_controladores($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Required** - Host ID
- `controlador` (string) - **Required** - Controller name
- `id` (int) - **Required** - Record ID
- `alteracoes` (array) - **Required** - List of changes
- `tabela` (array) - **Required** - Table definition
  - `nome` (string) - Table name
  - `versao` (string) - Version field
  - `id_numerico` (string) - ID field
- `sem_id` (bool) - **Optional** - Do not link ID
- `versao` (int) - **Optional** - Manual version (if `sem_id=true`)

**Usage Example:**
```php
// Record product update
log_controladores(Array(
    'id_hosts' => 1,
    'controlador' => 'products-admin',
    'id' => 456,
    'tabela' => Array(
        'nome' => 'products',
        'versao' => 'version',
        'id_numerico' => 'id_products'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'product-updated',
            'alteracao_txt' => 'Product updated via admin',
            'modulo' => 'products'
        )
    )
));

// Without linking specific ID
log_controladores(Array(
    'id_hosts' => 1,
    'controlador' => 'import-products',
    'id' => 0,
    'tabela' => Array(
        'nome' => 'products',
        'versao' => 'version',
        'id_numerico' => 'id_products'
    ),
    'sem_id' => true,
    'versao' => 1,
    'alteracoes' => Array(
        Array(
            'alteracao' => 'bulk-import',
            'alteracao_txt' => 'Bulk import: 100 products'
        )
    )
));
```

**Behavior:**
- Fetches current record version from database
- Automatically increments version
- Links to specific controller
- Supports multi-tenant (id_hosts)

---

### log_usuarios()

Records specific user actions.

**Signature:**
```php
function log_usuarios($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Required** - Host ID
- `id_usuarios` (int) - **Required** - User ID
- `id` (int) - **Required** - Record ID
- `alteracoes` (array) - **Required** - List of changes
- `tabela` (array) - **Required** - Table definition
- `sem_id` (bool) - **Optional** - Do not link ID
- `versao` (int) - **Optional** - Manual version

**Usage Example:**
```php
// Record profile edit
log_usuarios(Array(
    'id_hosts' => 1,
    'id_usuarios' => 789,
    'id' => 789,
    'tabela' => Array(
        'nome' => 'users',
        'versao' => 'version',
        'id_numerico' => 'id_users'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'profile-updated',
            'alteracao_txt' => 'Email changed',
            'modulo' => 'users'
        )
    )
));
```

---

### log_hosts_usuarios()

Records host user actions (multi-tenant).

**Signature:**
```php
function log_hosts_usuarios($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Required** - Host ID
- `id_hosts_usuarios` (int) - **Required** - Host User ID
- `id` (int) - **Required** - Record ID
- `alteracoes` (array) - **Required** - List of changes
- `tabela` (array) - **Required** - Table definition
- `sem_id` (bool) - **Optional** - Do not link ID
- `versao` (int) - **Optional** - Manual version

**Usage Example:**
```php
// Tenant user changing configuration
log_hosts_usuarios(Array(
    'id_hosts' => 5,
    'id_hosts_usuarios' => 42,
    'id' => 100,
    'tabela' => Array(
        'nome' => 'hosts_config',
        'versao' => 'version',
        'id_numerico' => 'id'
    ),
    'alteracoes' => Array(
        Array(
            'alteracao' => 'config-changed',
            'alteracao_txt' => 'Store logo updated'
        )
    )
));
```

---

### log_disco()

Writes messages to log file on disk.

**Signature:**
```php
function log_disco($msg, $logFilename = "gestor", $deleteFileAfter = false)
```

**Parameters:**
- `$msg` (string) - **Required** - Message to record
- `$logFilename` (string) - **Optional** - Base filename (default: "gestor")
- `$deleteFileAfter` (bool) - **Optional** - Delete file before writing

**Return:**
- (void)

**Usage Example:**
```php
// Simple log
log_disco("Process started");
// Writes to: logs/gestor-2025-10-27.log

// Specific log
log_disco("Error connecting SMTP", "email");
// Writes to: logs/email-2025-10-27.log

// Error log with details
try {
    // operation
} catch (Exception $e) {
    log_disco(
        "Critical error: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString(),
        "errors"
    );
}

// Clear and write
log_disco("New process", "cron", true);
// Deletes logs/cron-2025-10-27.log and creates new
```

**Log Format:**
```
[2025-10-27 15:30:45] Process started
[2025-10-27 15:30:46] Error connecting SMTP
```

**Behavior:**
- Automatically adds timestamp
- One file per day (format: `name-YYYY-MM-DD.log`)
- If `$_GESTOR['debug']=true`, prints to screen instead of writing
- Automatically creates directory if it doesn't exist
- Append by default (does not overwrite)

---

## Common Use Cases

### 1. Change Audit

```php
function update_product($product_id, $new_data) {
    // Fetch old data
    $old_product = banco_select(Array(
        'campos' => Array('name', 'price'),
        'tabela' => 'products',
        'extra' => "WHERE id='$product_id'",
        'unico' => true
    ));
    
    // Update
    banco_update(
        "name='{$new_data['name']}', price='{$new_data['price']}'",
        'products',
        "WHERE id='$product_id'"
    );
    
    // Record changes
    $changes = Array();
    
    if ($old_product['name'] != $new_data['name']) {
        $changes[] = Array(
            'alteracao' => 'product-name-changed',
            'alteracao_txt' => "Name: {$old_product['name']} → {$new_data['name']}"
        );
    }
    
    if ($old_product['price'] != $new_data['price']) {
        $changes[] = Array(
            'alteracao' => 'product-price-changed',
            'alteracao_txt' => "Price: $ {$old_product['price']} → $ {$new_data['price']}"
        );
    }
    
    if (!empty($changes)) {
        log_debugar(Array(
            'alteracoes' => $changes
        ));
    }
}
```

### 2. Batch Processing Log

```php
function process_import($csv_file) {
    $total = 0;
    $errors = 0;
    
    log_disco("Starting import: $csv_file", "import");
    
    $lines = file($csv_file);
    
    foreach ($lines as $line) {
        try {
            process_line($line);
            $total++;
        } catch (Exception $e) {
            $errors++;
            log_disco("Error line $total: " . $e->getMessage(), "import");
        }
    }
    
    $summary = "Import completed: $total successes, $errors errors";
    log_disco($summary, "import");
    
    // Also record in history
    log_debugar(Array(
        'alteracoes' => Array(
            Array(
                'alteracao' => 'import-completed',
                'alteracao_txt' => $summary,
                'modulo' => 'import'
            )
        )
    ));
}
```

### 3. Critical Action Tracking

```php
function delete_user($user_id) {
    // Fetch data before deleting
    $user = banco_select(Array(
        'campos' => Array('name', 'email'),
        'tabela' => 'users',
        'extra' => "WHERE id='$user_id'",
        'unico' => true
    ));
    
    // Delete
    banco_delete('users', "WHERE id='$user_id'");
    
    // Critical log on disk
    log_disco(
        "DELETION: User #{$user_id} ({$user['name']} - {$user['email']}) deleted",
        "critical"
    );
    
    // History
    log_usuarios(Array(
        'id_hosts' => $_GESTOR['host-id'],
        'id_usuarios' => gestor_usuario()['id_usuarios'],
        'id' => $user_id,
        'tabela' => Array(
            'nome' => 'users',
            'versao' => 'version',
            'id_numerico' => 'id_users'
        ),
        'alteracoes' => Array(
            Array(
                'alteracao' => 'user-deleted',
                'alteracao_txt' => "User deleted: {$user['name']}",
                'modulo' => 'users'
            )
        )
    ));
}
```

### 4. System Debug

```php
function debug_payment_process($order) {
    if ($_GESTOR['debug']) {
        log_disco("=== PAYMENT DEBUG ===", "debug");
        log_disco("Order: " . print_r($order, true), "debug");
        log_disco("Gateway: {$order['gateway']}", "debug");
        log_disco("Amount: $ {$order['total']}", "debug");
    }
    
    try {
        $result = process_gateway($order);
        log_disco("Payment processed: {$result['transaction_id']}", "payments");
        return $result;
    } catch (Exception $e) {
        log_disco("ERROR in payment: " . $e->getMessage(), "payments");
        log_disco("Stack trace: " . $e->getTraceAsString(), "payments");
        throw $e;
    }
}
```

### 5. History Report

```php
function generate_changes_report($start_date, $end_date) {
    $history = banco_select(Array(
        'campos' => Array(
            'h.*',
            'u.name as user_name'
        ),
        'tabela' => 'historico h',
        'extra' => "
            LEFT JOIN users u ON h.id_usuarios = u.id_usuarios
            WHERE h.data BETWEEN '$start_date' AND '$end_date'
            ORDER BY h.data DESC
        "
    ));
    
    echo "<h2>Changes from $start_date to $end_date</h2>";
    echo "<table>";
    echo "<tr><th>Date</th><th>User</th><th>Change</th></tr>";
    
    foreach ($history as $item) {
        echo "<tr>";
        echo "<td>{$item['data']}</td>";
        echo "<td>{$item['user_name']}</td>";
        echo "<td>{$item['alteracao_txt']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
```

---

## Table Structure

### Table: historico

```sql
CREATE TABLE historico (
    id_historico INT PRIMARY KEY AUTO_INCREMENT,
    id_hosts INT,
    id_usuarios INT,
    id_hosts_usuarios INT,
    controlador VARCHAR(100),
    versao INT,
    id INT,
    modulo VARCHAR(100),
    alteracao VARCHAR(100),
    alteracao_txt TEXT,
    data DATETIME,
    INDEX idx_data (data),
    INDEX idx_usuario (id_usuarios),
    INDEX idx_modulo (modulo)
);
```

---

## Patterns and Best Practices

### Consistent Logging

```php
// ✅ GOOD - Always log critical changes
function update_price($id, $new_price) {
    // update
    banco_update("price='$new_price'", 'products', "WHERE id='$id'");
    
    // log
    log_debugar(Array(
        'alteracoes' => Array(
            Array('alteracao_txt' => "Price changed: $new_price")
        )
    ));
}

// ❌ AVOID - Forgetting to log
function update_price($id, $new_price) {
    banco_update("price='$new_price'", 'products', "WHERE id='$id'");
    // No log - impossible to track
}
```

### Descriptive Messages

```php
// ✅ GOOD - Clear and useful message
log_disco("Payment approved - Order #123, Gateway: PayPal, Amount: $ 150.00", "payments");

// ❌ AVOID - Generic message
log_disco("Payment OK", "payments");
```

### Log Rotation

```php
// Clear old logs (daily cron)
function clear_old_logs($days = 30) {
    $path = $_GESTOR['logs-path'];
    $files = glob($path . '*.log');
    $limit = time() - ($days * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (filemtime($file) < $limit) {
            unlink($file);
            log_disco("Old log removed: " . basename($file), "system");
        }
    }
}
```

---

## Limitations and Considerations

### Performance

- Database logs can impact performance
- Use disk logs for frequent operations
- Consider proper indexing on history table

### Disk Space

- Logs grow indefinitely
- Implement log rotation
- Monitor disk usage

### Privacy

- Do not log sensitive data (passwords, cards)
- Comply with GDPR/LGPD
- Implement data retention

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations
- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Current user

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
