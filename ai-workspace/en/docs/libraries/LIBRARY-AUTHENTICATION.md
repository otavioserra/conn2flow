# Library: autenticacao.php

> 🔐 Authentication and access control system

## Overview

The `autenticacao.php` library provides a complete authentication system with JWT, access control, rate limiting, IP verification, and secure session management.

**Location**: `gestor/bibliotecas/autenticacao.php`  
**Total Functions**: 18

## Dependencies

- **Extensions**: OpenSSL
- **Libraries**: banco.php, gestor.php, ip.php, usuario.php
- **Tables**: `usuarios_tokens`, `acesso_bloqueios`

---

## Main Functions

### autenticacao_openssl_gerar_chaves()

Generates RSA key pair for JWT.

**Example:**
```php
$keys = autenticacao_openssl_gerar_chaves(Array(
    'tipo' => 'RSA',
    'senha' => 'secure_password'
));
file_put_contents('public.key', $keys['publica']);
file_put_contents('private.key', $keys['privada']);
```

### autenticacao_cliente_gerar_jwt()

Generates JWT token for client.

**Example:**
```php
$token = autenticacao_cliente_gerar_jwt(Array(
    'host' => 'site.com',
    'expiration' => time() + 3600,
    'pubID' => md5(uniqid()),
    'chavePublica' => $public_key
));
```

### autenticacao_cliente_validar_jwt()

Validates client JWT token.

**Example:**
```php
$valid = autenticacao_cliente_validar_jwt(Array(
    'token' => $_COOKIE['auth_token'],
    'chavePrivada' => $private_key
));
```

### autenticacao_verificar()

Verifies if user is authenticated.

**Example:**
```php
if (!autenticacao_verificar()) {
    header('Location: /login');
    exit;
}
```

### autenticacao_login()

Processes user login.

**Example:**
```php
$result = autenticacao_login(Array(
    'email' => $_POST['email'],
    'senha' => $_POST['senha'],
    'lembrar' => isset($_POST['lembrar'])
));

if ($result['sucesso']) {
    header('Location: /dashboard');
} else {
    echo $result['erro'];
}
```

### autenticacao_logout()

Ends user session.

**Example:**
```php
autenticacao_logout();
header('Location: /');
```

### autenticacao_acesso_verificar()

Verifies access permission with rate limiting.

**Example:**
```php
$access = autenticacao_acesso_verificar(Array(
    'tipo' => 'login',
    'limite' => 5,  // 5 attempts
    'periodo' => 300  // in 5 minutes
));

if (!$access['permitido']) {
    echo "Too many attempts. Wait {$access['tempo_restante']}s";
    exit;
}
```

### autenticacao_acesso_registrar()

Registers access attempt.

**Example:**
```php
autenticacao_acesso_registrar(Array(
    'tipo' => 'login',
    'sucesso' => false
));
```

### autenticacao_bloquear_ip()

Blocks IP due to excessive attempts.

**Example:**
```php
autenticacao_bloquear_ip(Array(
    'ip' => $_SERVER['REMOTE_ADDR'],
    'duracao' => 3600  // 1 hour
));
```

### autenticacao_desbloquear_ip()

Manually unblocks IP.

**Example:**
```php
autenticacao_desbloquear_ip(Array(
    'ip' => '192.168.1.100'
));
```

### autenticacao_verificar_permissao()

Verifies if user has specific permission.

**Example:**
```php
if (autenticacao_verificar_permissao('edit-products')) {
    // Allow editing
} else {
    echo "No permission";
}
```

### autenticacao_gerar_token_recuperacao()

Generates token for password recovery.

**Example:**
```php
$token = autenticacao_gerar_token_recuperacao(Array(
    'id_usuarios' => 123,
    'validade' => 3600  // 1 hour
));

$link = "https://site.com/reset-password?token=$token";
```

### autenticacao_validar_token_recuperacao()

Validates password recovery token.

**Example:**
```php
$valid = autenticacao_validar_token_recuperacao(Array(
    'token' => $_GET['token']
));

if ($valid) {
    // Show new password form
} else {
    echo "Invalid or expired token";
}
```

### autenticacao_alterar_senha()

Changes user password.

**Example:**
```php
autenticacao_alterar_senha(Array(
    'id_usuarios' => 123,
    'senha_nova' => $_POST['senha'],
    'hash' => true  // Auto hash
));
```

### autenticacao_verificar_senha()

Verifies if password is correct.

**Example:**
```php
if (autenticacao_verificar_senha($typed_password, $db_hash)) {
    echo "Correct password";
}
```

### autenticacao_gerar_hash_senha()

Generates secure password hash.

**Example:**
```php
$hash = autenticacao_gerar_hash_senha('my_password');
// Uses password_hash() with BCRYPT
```

### autenticacao_renovar_sessao()

Renews session to prevent fixation.

**Example:**
```php
autenticacao_renovar_sessao();
```

### autenticacao_verificar_2fa()

Verifies two-factor authentication code.

**Example:**
```php
if (autenticacao_verificar_2fa($user_code, $secret_2fa)) {
    echo "Valid 2FA";
}
```

---

## Common Use Cases

### 1. Complete Login System

```php
function process_login() {
    // Check rate limiting
    $access = autenticacao_acesso_verificar(Array(
        'tipo' => 'login',
        'limite' => 5,
        'periodo' => 300
    ));
    
    if (!$access['permitido']) {
        return Array(
            'sucesso' => false,
            'erro' => "Too many attempts. Wait {$access['tempo_restante']}s"
        );
    }
    
    // Attempt to authenticate
    $result = autenticacao_login(Array(
        'email' => $_POST['email'],
        'senha' => $_POST['senha'],
        'lembrar' => isset($_POST['lembrar'])
    ));
    
    // Register attempt
    autenticacao_acesso_registrar(Array(
        'tipo' => 'login',
        'sucesso' => $result['sucesso']
    ));
    
    return $result;
}
```

### 2. Password Recovery

```php
function start_password_recovery($email) {
    $user = find_user_by_email($email);
    
    if (!$user) {
        return false;
    }
    
    $token = autenticacao_gerar_token_recuperacao(Array(
        'id_usuarios' => $user['id'],
        'validade' => 3600
    ));
    
    $link = "https://site.com/reset?token=$token";
    
    comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $email)),
        'mensagem' => Array(
            'assunto' => 'Password Recovery',
            'html' => "Click here: <a href='$link'>Reset Password</a>"
        )
    ));
}

function process_new_password() {
    if (!autenticacao_validar_token_recuperacao(Array(
        'token' => $_POST['token']
    ))) {
        return 'Invalid token';
    }
    
    autenticacao_alterar_senha(Array(
        'token' => $_POST['token'],
        'senha_nova' => $_POST['senha'],
        'hash' => true
    ));
    
    return 'Password changed successfully!';
}
```

### 3. Brute Force Protection

```php
function login_with_protection() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if IP is blocked
    $blocked = check_blocked_ip($ip);
    
    if ($blocked) {
        http_response_code(429);
        die('IP blocked. Try again later.');
    }
    
    // Check attempts
    $attempts = count_failed_attempts($ip);
    
    if ($attempts >= 10) {
        autenticacao_bloquear_ip(Array(
            'ip' => $ip,
            'duracao' => 3600
        ));
        die('Too many attempts. IP blocked for 1 hour.');
    }
    
    // Process login normally
    $result = autenticacao_login($_POST);
    
    if (!$result['sucesso']) {
        increment_failed_attempts($ip);
    }
    
    return $result;
}
```

### 4. Authentication Middleware

```php
function require_auth($permission = null) {
    if (!autenticacao_verificar()) {
        header('Location: /login');
        exit;
    }
    
    if ($permission && !autenticacao_verificar_permissao($permission)) {
        http_response_code(403);
        die('Access denied');
    }
}

// Usage
require_auth();  // Authenticated only
require_auth('admin');  // Requires admin permission
```

---

## Security

### Password Hashing

```php
// ✅ Uses automatic BCRYPT
$hash = autenticacao_gerar_hash_senha('password');

// ✅ Secure verification
autenticacao_verificar_senha($password, $hash);
```

### Rate Limiting

```php
// ✅ Limit attempts by IP
autenticacao_acesso_verificar(Array(
    'tipo' => 'login',
    'limite' => 5,
    'periodo' => 300
));
```

### Session Renewal

```php
// ✅ Prevent session fixation
autenticacao_renovar_sessao();
```

---

## See Also

- [LIBRARY-USER.md](./LIBRARY-USER.md) - JWT Tokens
- [LIBRARY-IP.md](./LIBRARY-IP.md) - IP Detection

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
