# Biblioteca: autenticacao.php

> 🔐 Sistema de autenticação e controle de acesso

## Visão Geral

A biblioteca `autenticacao.php` fornece sistema completo de autenticação com JWT, controle de acesso, rate limiting, verificação de IP e gestão de sessões seguras.

**Localização**: `gestor/bibliotecas/autenticacao.php`  
**Total de Funções**: 18

## Dependências

- **Extensões**: OpenSSL
- **Bibliotecas**: banco.php, gestor.php, ip.php, usuario.php
- **Tabelas**: `usuarios_tokens`, `acesso_bloqueios`

---

## Funções Principais

### autenticacao_openssl_gerar_chaves()

Gera par de chaves RSA para JWT.

**Exemplo:**
```php
$chaves = autenticacao_openssl_gerar_chaves(Array(
    'tipo' => 'RSA',
    'senha' => 'senha_segura'
));
file_put_contents('public.key', $chaves['publica']);
file_put_contents('private.key', $chaves['privada']);
```

### autenticacao_cliente_gerar_jwt()

Gera token JWT para cliente.

**Exemplo:**
```php
$token = autenticacao_cliente_gerar_jwt(Array(
    'host' => 'site.com',
    'expiration' => time() + 3600,
    'pubID' => md5(uniqid()),
    'chavePublica' => $chave_publica
));
```

### autenticacao_cliente_validar_jwt()

Valida token JWT do cliente.

**Exemplo:**
```php
$valido = autenticacao_cliente_validar_jwt(Array(
    'token' => $_COOKIE['auth_token'],
    'chavePrivada' => $chave_privada
));
```

### autenticacao_verificar()

Verifica se usuário está autenticado.

**Exemplo:**
```php
if (!autenticacao_verificar()) {
    header('Location: /login');
    exit;
}
```

### autenticacao_login()

Processa login de usuário.

**Exemplo:**
```php
$resultado = autenticacao_login(Array(
    'email' => $_POST['email'],
    'senha' => $_POST['senha'],
    'lembrar' => isset($_POST['lembrar'])
));

if ($resultado['sucesso']) {
    header('Location: /dashboard');
} else {
    echo $resultado['erro'];
}
```

### autenticacao_logout()

Encerra sessão do usuário.

**Exemplo:**
```php
autenticacao_logout();
header('Location: /');
```

### autenticacao_acesso_verificar()

Verifica permissão de acesso com rate limiting.

**Exemplo:**
```php
$acesso = autenticacao_acesso_verificar(Array(
    'tipo' => 'login',
    'limite' => 5,  // 5 tentativas
    'periodo' => 300  // em 5 minutos
));

if (!$acesso['permitido']) {
    echo "Muitas tentativas. Aguarde {$acesso['tempo_restante']}s";
    exit;
}
```

### autenticacao_acesso_registrar()

Registra tentativa de acesso.

**Exemplo:**
```php
autenticacao_acesso_registrar(Array(
    'tipo' => 'login',
    'sucesso' => false
));
```

### autenticacao_bloquear_ip()

Bloqueia IP por tentativas excessivas.

**Exemplo:**
```php
autenticacao_bloquear_ip(Array(
    'ip' => $_SERVER['REMOTE_ADDR'],
    'duracao' => 3600  // 1 hora
));
```

### autenticacao_desbloquear_ip()

Desbloqueia IP manualmente.

**Exemplo:**
```php
autenticacao_desbloquear_ip(Array(
    'ip' => '192.168.1.100'
));
```

### autenticacao_verificar_permissao()

Verifica se usuário tem permissão específica.

**Exemplo:**
```php
if (autenticacao_verificar_permissao('editar-produtos')) {
    // Permitir edição
} else {
    echo "Sem permissão";
}
```

### autenticacao_gerar_token_recuperacao()

Gera token para recuperação de senha.

**Exemplo:**
```php
$token = autenticacao_gerar_token_recuperacao(Array(
    'id_usuarios' => 123,
    'validade' => 3600  // 1 hora
));

$link = "https://site.com/resetar-senha?token=$token";
```

### autenticacao_validar_token_recuperacao()

Valida token de recuperação de senha.

**Exemplo:**
```php
$valido = autenticacao_validar_token_recuperacao(Array(
    'token' => $_GET['token']
));

if ($valido) {
    // Exibir formulário de nova senha
} else {
    echo "Token inválido ou expirado";
}
```

### autenticacao_alterar_senha()

Altera senha do usuário.

**Exemplo:**
```php
autenticacao_alterar_senha(Array(
    'id_usuarios' => 123,
    'senha_nova' => $_POST['senha'],
    'hash' => true  // Fazer hash automático
));
```

### autenticacao_verificar_senha()

Verifica se senha está correta.

**Exemplo:**
```php
if (autenticacao_verificar_senha($senha_digitada, $hash_banco)) {
    echo "Senha correta";
}
```

### autenticacao_gerar_hash_senha()

Gera hash seguro da senha.

**Exemplo:**
```php
$hash = autenticacao_gerar_hash_senha('minha_senha');
// Usa password_hash() com BCRYPT
```

### autenticacao_renovar_sessao()

Renova sessão para prevenir fixação.

**Exemplo:**
```php
autenticacao_renovar_sessao();
```

### autenticacao_verificar_2fa()

Verifica código de autenticação de dois fatores.

**Exemplo:**
```php
if (autenticacao_verificar_2fa($codigo_usuario, $secret_2fa)) {
    echo "2FA válido";
}
```

---

## Casos de Uso Comuns

### 1. Sistema de Login Completo

```php
function processar_login() {
    // Verificar rate limiting
    $acesso = autenticacao_acesso_verificar(Array(
        'tipo' => 'login',
        'limite' => 5,
        'periodo' => 300
    ));
    
    if (!$acesso['permitido']) {
        return Array(
            'sucesso' => false,
            'erro' => "Muitas tentativas. Aguarde {$acesso['tempo_restante']}s"
        );
    }
    
    // Tentar autenticar
    $resultado = autenticacao_login(Array(
        'email' => $_POST['email'],
        'senha' => $_POST['senha'],
        'lembrar' => isset($_POST['lembrar'])
    ));
    
    // Registrar tentativa
    autenticacao_acesso_registrar(Array(
        'tipo' => 'login',
        'sucesso' => $resultado['sucesso']
    ));
    
    return $resultado;
}
```

### 2. Recuperação de Senha

```php
function iniciar_recuperacao_senha($email) {
    $usuario = buscar_usuario_por_email($email);
    
    if (!$usuario) {
        return false;
    }
    
    $token = autenticacao_gerar_token_recuperacao(Array(
        'id_usuarios' => $usuario['id'],
        'validade' => 3600
    ));
    
    $link = "https://site.com/resetar?token=$token";
    
    comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $email)),
        'mensagem' => Array(
            'assunto' => 'Recuperação de Senha',
            'html' => "Clique aqui: <a href='$link'>Redefinir Senha</a>"
        )
    ));
}

function processar_nova_senha() {
    if (!autenticacao_validar_token_recuperacao(Array(
        'token' => $_POST['token']
    ))) {
        return 'Token inválido';
    }
    
    autenticacao_alterar_senha(Array(
        'token' => $_POST['token'],
        'senha_nova' => $_POST['senha'],
        'hash' => true
    ));
    
    return 'Senha alterada com sucesso!';
}
```

### 3. Proteção contra Brute Force

```php
function login_com_protecao() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Verificar se IP está bloqueado
    $bloqueado = verificar_ip_bloqueado($ip);
    
    if ($bloqueado) {
        http_response_code(429);
        die('IP bloqueado. Tente novamente mais tarde.');
    }
    
    // Verificar tentativas
    $tentativas = contar_tentativas_falhas($ip);
    
    if ($tentativas >= 10) {
        autenticacao_bloquear_ip(Array(
            'ip' => $ip,
            'duracao' => 3600
        ));
        die('Muitas tentativas. IP bloqueado por 1 hora.');
    }
    
    // Processar login normalmente
    $resultado = autenticacao_login($_POST);
    
    if (!$resultado['sucesso']) {
        incrementar_tentativas_falhas($ip);
    }
    
    return $resultado;
}
```

### 4. Middleware de Autenticação

```php
function require_auth($permissao = null) {
    if (!autenticacao_verificar()) {
        header('Location: /login');
        exit;
    }
    
    if ($permissao && !autenticacao_verificar_permissao($permissao)) {
        http_response_code(403);
        die('Acesso negado');
    }
}

// Uso
require_auth();  // Apenas autenticado
require_auth('admin');  // Requer permissão admin
```

---

## Segurança

### Hash de Senhas

```php
// ✅ Usa BCRYPT automático
$hash = autenticacao_gerar_hash_senha('senha');

// ✅ Verificação segura
autenticacao_verificar_senha($senha, $hash);
```

### Rate Limiting

```php
// ✅ Limitar tentativas por IP
autenticacao_acesso_verificar(Array(
    'tipo' => 'login',
    'limite' => 5,
    'periodo' => 300
));
```

### Renovação de Sessão

```php
// ✅ Prevenir fixação de sessão
autenticacao_renovar_sessao();
```

---

## Veja Também

- [BIBLIOTECA-USUARIO.md](./BIBLIOTECA-USUARIO.md) - Tokens JWT
- [BIBLIOTECA-IP.md](./BIBLIOTECA-IP.md) - Detecção de IP

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
