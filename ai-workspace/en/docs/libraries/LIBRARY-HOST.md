# Library: host.php

> 🌍 Utilities for host/domain management

## Overview

The `host.php` library provides functions for retrieving information about system hosts (domains), including URLs, public identifiers, and specific store configurations.

**Location**: `gestor/bibliotecas/host.php`  
**Version**: 1.0.2  
**Total Functions**: 3

## Dependencies

- **Libraries**: banco.php
- **Global Variables**: `$_GESTOR`, `$_HOST`

## Global Variables

```php
$_GESTOR['biblioteca-host'] = Array(
    'versao' => '1.0.2',
);

// Current host information
$_GESTOR['host-id'] // Current host ID

// Information cache
$_HOST['dominio']   // Host domain
$_HOST['pubID']     // Public identifier
$_HOST['lojaNome']  // Store name
```

---

## Main Functions

### host_url()

Returns the host URL.

**Signature:**
```php
function host_url($params = false)
```

**Parameters (Associative Array):**
- `opcao` (string) - **Optional** - Return format ('full' for complete URL)
- `id_hosts` (int) - **Optional** - Specific host ID (default: current host)

**Return:**
- (string|false) - Host URL or false if not found

**Options:**
- `'full'`: Returns `https://domain.com/`
- Default: Returns `domain.com`

**Usage Example:**
```php
// Current host URL (domain only)
$domain = host_url();
// Returns: "mysite.com.br"

// Full URL
$full_url = host_url(Array('opcao' => 'full'));
// Returns: "https://mysite.com.br/"

// Specific host URL
$other_url = host_url(Array('id_hosts' => 5));
// Returns: "othersite.com"

// Use in links
$link = host_url(Array('opcao' => 'full')) . 'page/contact';
// Returns: "https://mysite.com.br/page/contact"
```

**Notes:**
- Caches in `$_HOST['dominio']`
- Always uses HTTPS in 'full' option

---

### host_pub_id()

Returns the host's public identifier.

**Signature:**
```php
function host_pub_id($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Optional** - Specific host ID (default: current host)

**Return:**
- (string|false) - Public identifier or false

**Usage Example:**
```php
// Current host PubID
$pub_id = host_pub_id();
// Returns: "abc123def456"

// Specific host PubID
$pub_id_other = host_pub_id(Array('id_hosts' => 3));

// Use in integrations
$api_endpoint = "https://api.example.com/client/" . host_pub_id();

// Generate API key
$api_key = md5(host_pub_id() . SECRET_KEY);
```

**Notes:**
- Caches in `$_HOST['pubID']`
- Useful for external identification
- Unique per host

---

### host_loja_nome()

Returns the host's store name.

**Signature:**
```php
function host_loja_nome($params = false)
```

**Parameters (Associative Array):**
- `id_hosts` (int) - **Optional** - Specific host ID (default: current host)

**Return:**
- (string|false) - Store name or false

**Usage Example:**
```php
// Current store name
$store_name = host_loja_nome();
// Returns: "Virtual Store ABC"

// Use in page title
echo "<title>" . host_loja_nome() . " - Products</title>";

// Use in emails
$message = "Thank you for shopping at " . host_loja_nome();

// Specific store name
$store_other = host_loja_nome(Array('id_hosts' => 10));
```

**Behavior:**
- Searches in `hosts_variaveis` (module: `loja-configuracoes`, id: `nome`)
- If not found, returns "My Store {id_hosts}"
- Caches in `$_HOST['lojaNome']`

---

## Common Use Cases

### 1. Multi-Host Absolute Links

```php
function generate_product_link($product_id, $host_id = null) {
    $base_url = host_url(Array(
        'opcao' => 'full',
        'id_hosts' => $host_id
    ));
    
    return $base_url . "product/" . $product_id;
}

// Link on current host
$link = generate_product_link(123);
// https://mysite.com.br/product/123

// Link on another host
$link_other = generate_product_link(123, 5);
// https://othersite.com/product/123
```

### 2. Personalized Emails

```php
function send_confirmation_email($order) {
    $store_name = host_loja_nome();
    $store_url = host_url(Array('opcao' => 'full'));
    
    $subject = "Order confirmed - $store_name";
    
    $message = "
        <h1>Thank you for shopping at $store_name!</h1>
        <p>Your order #{$order['number']} has been confirmed.</p>
        <p>Track it at: {$store_url}my-orders/{$order['id']}</p>
    ";
    
    comunicacao_email(Array(
        'para' => $order['client_email'],
        'assunto' => $subject,
        'mensagem' => $message
    ));
}
```

### 3. External API Integration

```php
function sync_with_platform() {
    $pub_id = host_pub_id();
    $store_name = host_loja_nome();
    
    $data = Array(
        'store_id' => $pub_id,
        'store_name' => $store_name,
        'products' => get_products()
    );
    
    $ch = curl_init('https://api.platform.com/sync');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
        'Content-Type: application/json',
        'X-Store-ID: ' . $pub_id
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
```

### 4. Multi-tenancy

```php
function list_all_stores() {
    $hosts = banco_select(Array(
        'campos' => Array('id_hosts', 'dominio', 'ativo'),
        'tabela' => 'hosts',
        'extra' => "WHERE ativo=1 ORDER BY dominio"
    ));
    
    if ($hosts) {
        foreach ($hosts as $host) {
            $store_name = host_loja_nome(Array('id_hosts' => $host['id_hosts']));
            $url = host_url(Array(
                'opcao' => 'full',
                'id_hosts' => $host['id_hosts']
            ));
            
            echo "<tr>";
            echo "<td>{$host['dominio']}</td>";
            echo "<td>$store_name</td>";
            echo "<td><a href='$url' target='_blank'>Visit</a></td>";
            echo "</tr>";
        }
    }
}
```

### 5. Multi-Host Reports

```php
function sales_report_by_host($start_date, $end_date) {
    $hosts = banco_select(Array(
        'campos' => Array('id_hosts'),
        'tabela' => 'hosts',
        'extra' => "WHERE ativo=1"
    ));
    
    $report = Array();
    
    foreach ($hosts as $host) {
        $id_hosts = $host['id_hosts'];
        
        $sales = banco_select(Array(
            'campos' => Array('SUM(total) as total', 'COUNT(*) as quantity'),
            'tabela' => 'orders',
            'extra' => "WHERE id_hosts='$id_hosts' 
                        AND date BETWEEN '$start_date' AND '$end_date'
                        AND status='paid'",
            'unico' => true
        ));
        
        $report[] = Array(
            'host' => host_url(Array('id_hosts' => $id_hosts)),
            'store' => host_loja_nome(Array('id_hosts' => $id_hosts)),
            'total' => $sales['total'] ?? 0,
            'quantity' => $sales['quantity'] ?? 0
        );
    }
    
    return $report;
}
```

---

## Table Structure

### Table: hosts

```sql
CREATE TABLE hosts (
    id_hosts INT PRIMARY KEY AUTO_INCREMENT,
    dominio VARCHAR(255) NOT NULL,
    pub_id VARCHAR(255) UNIQUE,
    ativo TINYINT DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Table: hosts_variaveis

```sql
CREATE TABLE hosts_variaveis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_hosts INT NOT NULL,
    modulo VARCHAR(100),
    id VARCHAR(100) NOT NULL,
    valor TEXT,
    FOREIGN KEY (id_hosts) REFERENCES hosts(id_hosts)
);
```

---

## Patterns and Best Practices

### Information Caching

```php
// ✅ GOOD - Uses automatic cache
$name1 = host_loja_nome();
$name2 = host_loja_nome(); // Does not make new query

// ❌ AVOID - Multiple calls without need
for ($i = 0; $i < 100; $i++) {
    echo host_url(); // Works but unnecessary
}

// ✅ BETTER - Manual cache
$url = host_url();
for ($i = 0; $i < 100; $i++) {
    echo $url;
}
```

### Host Validation

```php
// Always validate before using
$url = host_url();
if ($url === false) {
    // Host not configured
    die("Configuration error");
}
```

### Multi-tenancy

```php
// For multi-tenant systems, always pass id_hosts
function get_host_config($id_hosts) {
    return Array(
        'url' => host_url(Array('id_hosts' => $id_hosts)),
        'pub_id' => host_pub_id(Array('id_hosts' => $id_hosts)),
        'name' => host_loja_nome(Array('id_hosts' => $id_hosts))
    );
}
```

---

## Limitations and Considerations

### Hardcoded HTTPS

- `host_url()` function with 'full' option always returns HTTPS
- Does not support pure HTTP
- For development environment, adjust manually

### Global Cache

- Cache in `$_HOST` is per request
- Multiple hosts in the same request may cause conflict
- Use `id_hosts` parameter explicitly when necessary

### Database Dependency

- All functions make queries
- In high performance environments, consider additional cache (Redis, Memcached)

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations
- [LIBRARY-VARIABLES.md](./LIBRARY-VARIABLES.md) - Variable system

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
