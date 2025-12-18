# Library: usuario.php

> 🔐 JWT authentication and token management

## Overview

The `usuario.php` library provides functions for generating cryptographic keys, JWT (JSON Web Token) tokens, and managing authentication sessions using RSA. Robust authentication system with secure cookie support.

**Location**: `gestor/bibliotecas/usuario.php`  
**Version**: 1.1.0  
**Total Functions**: 6

## Dependencies

- **PHP Extensions**: OpenSSL
- **Libraries**: banco.php, gestor.php, ip.php
- **Global Variables**: `$_GESTOR`, `$_CONFIG`
- **Table**: `usuarios_tokens`

## Global Variables

```php
$_GESTOR['biblioteca-usuario'] = Array(
    'versao' => '1.1.0',
);

// Required configurations
$_GESTOR['openssl-path'] // OpenSSL keys path
$_CONFIG['cookie-lifetime'] // Cookie lifetime (seconds)
$_CONFIG['cookie-authname'] // Authentication cookie name
$_CONFIG['usuario-hash-algo'] // Hash algorithm (e.g., 'sha256')
$_CONFIG['usuario-hash-password'] // Password for HMAC
```

---

## Main Functions

### usuario_openssl_gerar_chaves()

Generates public/private key pair using OpenSSL.

**Signature:**
```php
function usuario_openssl_gerar_chaves($params = false)
```

**Parameters (Associative Array):**
- `tipo` (string) - **Required** - Key type ('RSA')
- `senha` (string) - **Optional** - Password to encrypt private key

**Return:**
- (array) - Array with 'publica' and 'privada', or false

**Usage Example:**
```php
// Generate RSA keys without password
$keys = usuario_openssl_gerar_chaves(Array(
    'tipo' => 'RSA'
));

echo $keys['publica'];  // Public key
echo $keys['privada'];  // Private key

// Generate RSA keys with password
$keys = usuario_openssl_gerar_chaves(Array(
    'tipo' => 'RSA',
    'senha' => 'my_secret_password'
));

// Save keys to files
file_put_contents('publica.key', $keys['publica']);
file_put_contents('privada.key', $keys['privada']);
```

**Specifications:**
- Algorithm: RSA 2048 bits
- Digest: SHA-512
- Format: PEM

---

### usuario_gerar_jwt()

Generates JWT token signed with RSA public key.

**Signature:**
```php
function usuario_gerar_jwt($params = false)
```

**Parameters (Associative Array):**
- `host` (string) - **Required** - Token issuer host
- `expiration` (int) - **Required** - Expiration timestamp
- `pubID` (string) - **Required** - Public token ID
- `chavePublica` (string) - **Required** - RSA public key

**Return:**
- (string) - JWT token, or false

**Usage Example:**
```php
// Load public key
$publicKey = file_get_contents('/path/to/publica.key');

// Generate JWT valid for 1 hour
$token = usuario_gerar_jwt(Array(
    'host' => 'mysite.com',
    'expiration' => time() + 3600,
    'pubID' => md5(uniqid()),
    'chavePublica' => $publicKey
));

// Token format: header.payload.signature
echo $token;
```

**JWT Structure:**
```json
{
  "header": {
    "alg": "RSA",
    "typ": "JWT"
  },
  "payload": {
    "iss": "mysite.com",
    "exp": 1698765432,
    "sub": "abc123..."
  }
}
```

---

### usuario_gerar_token_autorizacao()

Generates complete authentication token with cookie and database record.

**Signature:**
```php
function usuario_gerar_token_autorizacao($params = false)
```

**Parameters (Associative Array):**
- `id_usuarios` (int) - **Required** - User ID
- `sessao` (bool) - **Optional** - If true, cookie expires when browser closes

**Return:**
- (void) - Sets cookie and saves to database

**Usage Example:**
```php
// Permanent login (persistent cookie)
usuario_gerar_token_autorizacao(Array(
    'id_usuarios' => 123
));

// Session login (expires on browser close)
usuario_gerar_token_autorizacao(Array(
    'id_usuarios' => 123,
    'sessao' => true
));
```

**Behavior:**
- Generates unique JWT
- Sets secure cookie (HttpOnly, Secure, SameSite=Lax)
- Records IP and User-Agent
- Saves token in `usuarios_tokens` table

---

### usuario_app_gerar_token_autorizacao()

Generates authorization token for mobile apps (no cookie).

**Signature:**
```php
function usuario_app_gerar_token_autorizacao($params = false)
```

**Parameters (Associative Array):**
- `id_usuarios` (int) - **Required** - User ID
- `validade` (int) - **Optional** - Validity days (default: 365)

**Return:**
- (string) - JWT token

**Usage Example:**
```php
// Token for app with default validity (1 year)
$token = usuario_app_gerar_token_autorizacao(Array(
    'id_usuarios' => 456
));

// Return to mobile app
echo json_encode(Array(
    'token' => $token,
    'expires_in' => 365 * 24 * 3600
));

// Token with custom validity (30 days)
$token = usuario_app_gerar_token_autorizacao(Array(
    'id_usuarios' => 456,
    'validade' => 30
));
```

**Differences from web token:**
- Does not set cookie
- Returns token as string
- Configurable validity in days
- Ideal for mobile APIs

---

### usuario_autorizacao_provisoria()

Creates temporary authentication link (passwordless login).

**Signature:**
```php
function usuario_autorizacao_provisoria($params = false)
```

**Parameters (Associative Array):**
- `id_usuarios` (int) - **Required** - User ID
- `expiracao` (int) - **Optional** - Minutes until expiration (default: 60)
- `parametros` (string) - **Optional** - Extra parameters for URL

**Return:**
- (string) - Temporary access URL

**Usage Example:**
```php
// Access link valid for 1 hour
$link = usuario_autorizacao_provisoria(Array(
    'id_usuarios' => 789
));

// Send by email
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'user@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Quick Access',
        'html' => "Click here to access: <a href='$link'>Login</a>"
    )
));

// Link with short validity (15 minutes) and redirect
$link = usuario_autorizacao_provisoria(Array(
    'id_usuarios' => 789,
    'expiracao' => 15,
    'parametros' => 'redirect=/admin/dashboard'
));
```

**Use Cases:**
- Password recovery
- Email login (magic link)
- Temporary support access
- Email verification

---

### usuario_host_dados()

Returns complete user data for a host (multi-tenant).

**Signature:**
```php
function usuario_host_dados($params = false)
```

**Parameters (Associative Array):**
- `id_hosts_usuarios` (int) - **Required** - Host user ID

**Return:**
- (array) - User data, or false

**Usage Example:**
```php
// Fetch tenant user data
$user = usuario_host_dados(Array(
    'id_hosts_usuarios' => 42
));

if ($user) {
    echo "Name: " . $user['nome'];
    echo "Email: " . $user['email'];
    echo "Status: " . $user['status'];
}
```

---

## Common Use Cases

### 1. Complete Login System

```php
function do_login($email, $password) {
    // Validate credentials
    $user = banco_select(Array(
        'campos' => Array('id_usuarios', 'senha_hash'),
        'tabela' => 'usuarios',
        'extra' => "WHERE email='$email' AND status='A'",
        'unico' => true
    ));
    
    if (!$user) {
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $user['senha_hash'])) {
        return false;
    }
    
    // Generate auth token
    usuario_gerar_token_autorizacao(Array(
        'id_usuarios' => $user['id_usuarios']
    ));
    
    return true;
}
```

### 2. Mobile API with JWT

```php
// App login endpoint
function api_login($email, $password) {
    $user = validate_credentials($email, $password);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(Array('error' => 'Invalid credentials'));
        return;
    }
    
    // Generate app token
    $token = usuario_app_gerar_token_autorizacao(Array(
        'id_usuarios' => $user['id_usuarios'],
        'validade' => 90  // 90 days
    ));
    
    echo json_encode(Array(
        'token' => $token,
        'user' => Array(
            'id' => $user['id_usuarios'],
            'name' => $user['nome'],
            'email' => $user['email']
        ),
        'expires_in' => 90 * 24 * 3600
    ));
}
```

### 3. Magic Link (Email Login)

```php
function send_magic_link($email) {
    $user = banco_select(Array(
        'campos' => Array('id_usuarios', 'nome'),
        'tabela' => 'usuarios',
        'extra' => "WHERE email='$email'",
        'unico' => true
    ));
    
    if (!$user) {
        return false;
    }
    
    // Generate temporary link (15 minutes)
    $link = usuario_autorizacao_provisoria(Array(
        'id_usuarios' => $user['id_usuarios'],
        'expiracao' => 15
    ));
    
    // Send email
    comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $email, 'nome' => $user['nome'])
        ),
        'mensagem' => Array(
            'assunto' => 'Access Link - Valid for 15 minutes',
            'html' => "
                <p>Hello {$user['nome']},</p>
                <p>Click the link below to access your account:</p>
                <p><a href='$link'>Access Now</a></p>
                <p>This link expires in 15 minutes.</p>
            "
        )
    ));
    
    return true;
}
```

### 4. Initial System Configuration

```php
function configure_authentication() {
    // Generate RSA keys
    $keys = usuario_openssl_gerar_chaves(Array(
        'tipo' => 'RSA',
        'senha' => 'super_secret_password'
    ));
    
    // Create keys directory
    $openssl_path = $_GESTOR['raiz'] . 'openssl/';
    if (!is_dir($openssl_path)) {
        mkdir($openssl_path, 0700, true);
    }
    
    // Save keys
    file_put_contents($openssl_path . 'publica.key', $keys['publica']);
    file_put_contents($openssl_path . 'privada.key', $keys['privada']);
    
    // Set restricted permissions
    chmod($openssl_path . 'privada.key', 0600);
    chmod($openssl_path . 'publica.key', 0644);
    
    echo "Keys generated and saved in: $openssl_path";
}
```

### 5. Token Renewal

```php
function renew_token_if_near_expiration() {
    $user = gestor_usuario();
    
    if (!$user) {
        return;
    }
    
    // Fetch current token
    $token = banco_select(Array(
        'campos' => Array('expiration'),
        'tabela' => 'usuarios_tokens',
        'extra' => "WHERE id_usuarios='{$user['id_usuarios']}' 
                    AND pubID='{$_COOKIE[$_CONFIG['cookie-authname']]}'",
        'unico' => true
    ));
    
    // If less than 7 days to expire, renew
    $days_remaining = ($token['expiration'] - time()) / (24 * 3600);
    
    if ($days_remaining < 7) {
        // Invalidate old token
        banco_delete('usuarios_tokens', 
            "WHERE id_usuarios='{$user['id_usuarios']}'");
        
        // Generate new token
        usuario_gerar_token_autorizacao(Array(
            'id_usuarios' => $user['id_usuarios']
        ));
    }
}
```

---

## Security

### Secure Cookies

```php
// Cookies automatically generated with security flags:
setcookie($name, $value, [
    'expires' => $expiration,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'],
    'secure' => true,      // HTTPS only
    'httponly' => true,    // Not accessible via JavaScript
    'samesite' => 'Lax'   // CSRF protection
]);
```

### Key Storage

```php
// ✅ GOOD - Keys outside webroot
/var/www/
  ├── public_html/     (DocumentRoot)
  └── openssl/         (Outside webroot)
      ├── publica.key  (644)
      └── privada.key  (600)

// ❌ AVOID - Keys accessible via web
/var/www/public_html/
  └── openssl/
      ├── publica.key   (Accessible via HTTP!)
      └── privada.key   (Critical!)
```

### Token Validation

```php
// Always validate before trusting
function validate_jwt_token($token) {
    // Verify structure
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode($parts[1]), true);
    
    // Verify expiration
    if ($payload['exp'] < time()) {
        return false;
    }
    
    // Verify issuer
    if ($payload['iss'] !== $_SERVER['SERVER_NAME']) {
        return false;
    }
    
    return true;
}
```

---

## Limitations and Considerations

### Token Size

- JWT can get large (>1KB)
- Consider HTTP header limits
- Use refresh tokens for long tokens

### Revocation

- JWT cannot be easily revoked
- Use database blacklist if necessary
- Short tokens reduce risk window

### Performance

- RSA key generation is expensive
- Generate once, reuse
- Cache keys in memory when possible

---

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations
- [LIBRARY-IP.md](./LIBRARY-IP.md) - IP detection
- [LIBRARY-COMMUNICATION.md](./LIBRARY-COMMUNICATION.md) - Sending emails
- [JWT.io](https://jwt.io/) - JWT Debugger

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
