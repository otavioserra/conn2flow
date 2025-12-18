# Library: ip.php

> 🌐 IP address validation and retrieval

## Overview

The `ip.php` library provides functions for IP address validation and detection of the client's real IP, considering reverse proxies. Essential for security, logging, and geolocation.

**Location**: `gestor/bibliotecas/ip.php`  
**Version**: 1.0.0  
**Total Functions**: 2

## Dependencies

- **PHP Extensions**: filter (native)
- **Global Variables**: `$_GESTOR`, `$_SERVER`

## Global Variables

```php
$_GESTOR['biblioteca-ip'] = Array(
    'versao' => '1.0.0',
);
```

---

## Main Functions

### ip_check()

Validates if an IP address is valid.

**Signature:**
```php
function ip_check($ip, $allow_private = false, $proxy_ip = [])
```

**Parameters:**
- `$ip` (string) - **Required** - IP address to validate
- `$allow_private` (bool) - **Optional** - If true, allows private IPs (default: false)
- `$proxy_ip` (array) - **Optional** - Trusted proxy IPs to exclude from validation

**Return:**
- (bool) - true if valid, false if invalid

**Default Validation:**
- ✅ Accepts: Valid public IPs
- ❌ Rejects: 
  - Reserved IPs
  - Private IPs (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
  - Loopback (127.0.0.1)
  - Trusted proxy IPs

**Usage Example:**
```php
// Basic validation
if (ip_check('8.8.8.8')) {
    echo "Valid public IP";
}

if (!ip_check('192.168.1.1')) {
    echo "Private IP - invalid by default";
}

// Allow private IPs
if (ip_check('192.168.1.1', true)) {
    echo "Valid private IP when allow_private=true";
}

// Exclude trusted proxy IPs
$proxies = ['127.0.0.1', '10.0.0.1'];
if (!ip_check('127.0.0.1', false, $proxies)) {
    echo "Proxy IP - excluded from validation";
}

// Validate user input
$user_ip = $_POST['ip'];
if (ip_check($user_ip)) {
    // Valid IP - proceed
} else {
    echo "Invalid IP";
}
```

**Notes:**
- Uses PHP's `FILTER_VALIDATE_IP`
- Manually blocks loopback (127.x.x.x) when `$allow_private=false`
- Useful for IP whitelisting/blacklisting

---

### ip_get()

Gets the client's real IP address considering reverse proxies.

**Signature:**
```php
function ip_get($allow_private = false)
```

**Parameters:**
- `$allow_private` (bool) - **Optional** - If true, allows private IPs (default: false)

**Return:**
- (string|null) - Client IP or null if no valid IP

**Behavior:**
1. Checks `$_SERVER['REMOTE_ADDR']` first
2. If trusted proxy, checks `HTTP_X_FORWARDED_FOR`
3. Traverses proxy chain backwards
4. Returns first valid IP found

**Usage Example:**
```php
// Get client IP
$client_ip = ip_get();

if ($client_ip) {
    echo "Client: $client_ip";
} else {
    echo "IP not detected";
}

// Allow private IPs (local development)
$client_ip = ip_get(true);

// Use in logging
$log_entry = Array(
    'user_id' => $_SESSION['user_id'],
    'ip' => ip_get(),
    'action' => 'login',
    'timestamp' => date('Y-m-d H:i:s')
);

banco_insert_name(Array(
    Array('user_id', $log_entry['user_id']),
    Array('ip', $log_entry['ip']),
    Array('action', $log_entry['action']),
    Array('timestamp', $log_entry['timestamp'])
), 'access_logs');

// Check IP block
$ip = ip_get();
$blocked = banco_select(Array(
    'campos' => Array('id'),
    'tabela' => 'blocked_ips',
    'extra' => "WHERE ip='$ip'",
    'unico' => true
));

if ($blocked) {
    die("Access denied");
}
```

---

## Common Use Cases

### 1. Access Logging

```php
function log_access($user_id, $action) {
    $ip = ip_get();
    
    banco_insert_name(Array(
        Array('user_id', $user_id),
        Array('ip', $ip),
        Array('action', $action),
        Array('user_agent', $_SERVER['HTTP_USER_AGENT']),
        Array('timestamp', 'NOW()', true, false)
    ), 'access_logs');
}

// Usage
log_access($_SESSION['user_id'], 'login');
log_access($_SESSION['user_id'], 'password_change');
```

### 2. IP Blocking

```php
function check_block() {
    $ip = ip_get();
    
    if (!$ip) {
        return false; // IP not detected - allow
    }
    
    // Check if blocked
    $blocked = banco_select(Array(
        'campos' => Array('reason', 'blocked_until'),
        'tabela' => 'blocked_ips',
        'extra' => "WHERE ip='$ip' AND (blocked_until IS NULL OR blocked_until > NOW())",
        'unico' => true
    ));
    
    if ($blocked) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied: " . $blocked['reason'];
        exit;
    }
}

// Call at start of protected pages
check_block();
```

### 3. IP Whitelist

```php
function check_whitelist() {
    $ip = ip_get();
    
    $allowed = Array(
        '8.8.8.8',
        '200.100.50.25',
        '192.168.1.0/24' // Local subnet
    );
    
    foreach ($allowed as $allow) {
        if (strpos($allow, '/') !== false) {
            // Subnet - CIDR check
            if (ip_in_subnet($ip, $allow)) {
                return true;
            }
        } else {
            // Single IP
            if ($ip === $allow) {
                return true;
            }
        }
    }
    
    return false;
}

// Admin area - whitelist IPs only
if (!check_whitelist()) {
    die("Restricted access");
}
```

### 4. Rate Limiting by IP

```php
function check_rate_limit($limit = 100, $window = 3600) {
    $ip = ip_get();
    
    if (!$ip) {
        return true; // Allow if IP not detected
    }
    
    // Count requests in time window
    $count = banco_select(Array(
        'campos' => Array('COUNT(*) as total'),
        'tabela' => 'rate_limit',
        'extra' => "WHERE ip='$ip' AND timestamp > DATE_SUB(NOW(), INTERVAL $window SECOND)",
        'unico' => true
    ));
    
    if ($count && $count['total'] >= $limit) {
        header('HTTP/1.1 429 Too Many Requests');
        echo "Too many requests. Try again in " . ($window / 60) . " minutes.";
        exit;
    }
    
    // Record request
    banco_insert_name(Array(
        Array('ip', $ip),
        Array('timestamp', 'NOW()', true, false)
    ), 'rate_limit');
    
    return true;
}

// Protect API
check_rate_limit(100, 3600); // 100 req/hour
```

### 5. Geolocation

```php
function get_location($ip = null) {
    if ($ip === null) {
        $ip = ip_get();
    }
    
    if (!$ip || !ip_check($ip)) {
        return null;
    }
    
    // Use geolocation service (example: ipapi.co)
    $url = "https://ipapi.co/$ip/json/";
    $response = file_get_contents($url);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Usage
$location = get_location();

if ($location) {
    echo "Country: " . $location['country_name'];
    echo "City: " . $location['city'];
}
```

---

## Reverse Proxy Configuration

### Editing ip_get() Function

If you use Nginx, Apache, or another reverse proxy, configure the correct IPs and headers:

```php
function ip_get($allow_private = false){
    // CONFIGURE: IPs of your reverse proxies
    $proxy_ip = [
        '127.0.0.1',        // Localhost
        '10.0.0.1',         // Internal proxy
        '172.17.0.1',       // Docker gateway
        // Add your proxy IPs here
    ];

    // CONFIGURE: Header used by your proxy
    // Common options:
    // - HTTP_X_FORWARDED_FOR (Nginx, Apache mod_proxy)
    // - HTTP_CLIENT_IP (Apache mod_remoteip)
    // - HTTP_X_REAL_IP (Nginx with proxy_set_header)
    // - HTTP_CF_CONNECTING_IP (Cloudflare)
    $header = 'HTTP_X_FORWARDED_FOR';

    // Rest of function remains same...
}
```

### Example for Cloudflare

```php
$proxy_ip = [
    // Cloudflare IPs
    '103.21.244.0/22',
    '103.22.200.0/22',
    // ... (add all Cloudflare ranges)
];

$header = 'HTTP_CF_CONNECTING_IP';
```

---

## Patterns and Best Practices

### Security

1. **Never blindly trust HTTP headers:**
```php
// ❌ INSECURE - Can be spoofed
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

// ✅ SECURE - Validates against trusted proxies
$ip = ip_get();
```

2. **Correctly configure proxy IPs:**
```php
// ❌ WRONG - Empty list allows spoofing
$proxy_ip = [];

// ✅ CORRECT - Only truly trusted proxies
$proxy_ip = ['10.0.0.1']; // Your verified proxy
```

3. **Use in combination with other measures:**
```php
// IP + User Agent + CSRF Token
$fingerprint = md5(
    ip_get() . 
    $_SERVER['HTTP_USER_AGENT'] . 
    $_SESSION['csrf_token']
);
```

### Performance

1. **Cache results when possible:**
```php
// Once per request
if (!isset($_GESTOR['client_ip'])) {
    $_GESTOR['client_ip'] = ip_get();
}

$ip = $_GESTOR['client_ip'];
```

2. **Consider rate limiting external calls:**
```php
// Don't call geolocation services on every request
// Use cache or processing queue
```

---

## Limitations and Considerations

### HTTP Headers can be spoofed

- Only trust direct `REMOTE_ADDR` or verified proxies
- Configure proxy whitelist correctly

### IPv6

- Functions support IPv6 via `FILTER_VALIDATE_IP`
- Test adequately in IPv6 environments

### Chained Proxies

- `X-Forwarded-For` can contain multiple IPs
- Function traverses backwards (more reliable)
- Format: `client, proxy1, proxy2`

### Dynamic IPs

- IP blocks can affect innocent users
- Consider temporary blocks and CAPTCHA

---

## See Also

- [PHP filter_var](https://www.php.net/manual/en/function.filter-var.php) - Official documentation
- [X-Forwarded-For](https://en.wikipedia.org/wiki/X-Forwarded-For) - Format and usage
- [LIBRARY-LOG.md](./LIBRARY-LOG.md) - Log system

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
