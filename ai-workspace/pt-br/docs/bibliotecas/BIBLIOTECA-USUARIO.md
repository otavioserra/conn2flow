# Biblioteca: usuario.php

> 🔐 Autenticação JWT e gerenciamento de tokens

## Visão Geral

A biblioteca `usuario.php` fornece funções para geração de chaves criptográficas, tokens JWT (JSON Web Token) e gerenciamento de sessões de autenticação usando RSA. Sistema de autenticação robusto com suporte a cookies seguros.

**Localização**: `gestor/bibliotecas/usuario.php`  
**Versão**: 1.1.0  
**Total de Funções**: 6

## Dependências

- **Extensões PHP**: OpenSSL
- **Bibliotecas**: banco.php, gestor.php, ip.php
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`
- **Tabela**: `usuarios_tokens`

## Variáveis Globais

```php
$_GESTOR['biblioteca-usuario'] = Array(
    'versao' => '1.1.0',
);

// Configurações necessárias
$_GESTOR['openssl-path'] // Caminho das chaves OpenSSL
$_CONFIG['cookie-lifetime'] // Tempo de vida do cookie (segundos)
$_CONFIG['cookie-authname'] // Nome do cookie de autenticação
$_CONFIG['usuario-hash-algo'] // Algoritmo de hash (ex: 'sha256')
$_CONFIG['usuario-hash-password'] // Senha para HMAC
```

---

## Funções Principais

### usuario_openssl_gerar_chaves()

Gera par de chaves pública/privada usando OpenSSL.

**Assinatura:**
```php
function usuario_openssl_gerar_chaves($params = false)
```

**Parâmetros (Array Associativo):**
- `tipo` (string) - **Obrigatório** - Tipo de chave ('RSA')
- `senha` (string) - **Opcional** - Senha para criptografar chave privada

**Retorno:**
- (array) - Array com 'publica' e 'privada', ou false

**Exemplo de Uso:**
```php
// Gerar chaves RSA sem senha
$chaves = usuario_openssl_gerar_chaves(Array(
    'tipo' => 'RSA'
));

echo $chaves['publica'];  // Chave pública
echo $chaves['privada'];  // Chave privada

// Gerar chaves RSA com senha
$chaves = usuario_openssl_gerar_chaves(Array(
    'tipo' => 'RSA',
    'senha' => 'minha_senha_secreta'
));

// Salvar chaves em arquivos
file_put_contents('publica.key', $chaves['publica']);
file_put_contents('privada.key', $chaves['privada']);
```

**Especificações:**
- Algoritmo: RSA 2048 bits
- Digest: SHA-512
- Formato: PEM

---

### usuario_gerar_jwt()

Gera token JWT assinado com chave pública RSA.

**Assinatura:**
```php
function usuario_gerar_jwt($params = false)
```

**Parâmetros (Array Associativo):**
- `host` (string) - **Obrigatório** - Host emissor do token
- `expiration` (int) - **Obrigatório** - Timestamp de expiração
- `pubID` (string) - **Obrigatório** - ID público do token
- `chavePublica` (string) - **Obrigatório** - Chave pública RSA

**Retorno:**
- (string) - Token JWT, ou false

**Exemplo de Uso:**
```php
// Carregar chave pública
$chavePublica = file_get_contents('/path/to/publica.key');

// Gerar JWT válido por 1 hora
$token = usuario_gerar_jwt(Array(
    'host' => 'meusite.com',
    'expiration' => time() + 3600,
    'pubID' => md5(uniqid()),
    'chavePublica' => $chavePublica
));

// Token formato: header.payload.signature
echo $token;
```

**Estrutura do JWT:**
```json
{
  "header": {
    "alg": "RSA",
    "typ": "JWT"
  },
  "payload": {
    "iss": "meusite.com",
    "exp": 1698765432,
    "sub": "abc123..."
  }
}
```

---

### usuario_gerar_token_autorizacao()

Gera token de autenticação completo com cookie e registro em banco.

**Assinatura:**
```php
function usuario_gerar_token_autorizacao($params = false)
```

**Parâmetros (Array Associativo):**
- `id_usuarios` (int) - **Obrigatório** - ID do usuário
- `sessao` (bool) - **Opcional** - Se true, cookie expira ao fechar navegador

**Retorno:**
- (void) - Define cookie e salva no banco

**Exemplo de Uso:**
```php
// Login permanente (cookie persistente)
usuario_gerar_token_autorizacao(Array(
    'id_usuarios' => 123
));

// Login de sessão (expira ao fechar navegador)
usuario_gerar_token_autorizacao(Array(
    'id_usuarios' => 123,
    'sessao' => true
));
```

**Comportamento:**
- Gera JWT único
- Define cookie seguro (HttpOnly, Secure, SameSite=Lax)
- Registra IP e User-Agent
- Salva token na tabela `usuarios_tokens`

---

### usuario_app_gerar_token_autorizacao()

Gera token de autorizaçãopara aplicativos móveis (sem cookie).

**Assinatura:**
```php
function usuario_app_gerar_token_autorizacao($params = false)
```

**Parâmetros (Array Associativo):**
- `id_usuarios` (int) - **Obrigatório** - ID do usuário
- `validade` (int) - **Opcional** - Dias de validade (padrão: 365)

**Retorno:**
- (string) - Token JWT

**Exemplo de Uso:**
```php
// Token para app com validade padrão (1 ano)
$token = usuario_app_gerar_token_autorizacao(Array(
    'id_usuarios' => 456
));

// Retornar para app mobile
echo json_encode(Array(
    'token' => $token,
    'expires_in' => 365 * 24 * 3600
));

// Token com validade customizada (30 dias)
$token = usuario_app_gerar_token_autorizacao(Array(
    'id_usuarios' => 456,
    'validade' => 30
));
```

**Diferenças do token web:**
- Não define cookie
- Retorna token como string
- Validade configurável em dias
- Ideal para APIs mobile

---

### usuario_autorizacao_provisoria()

Cria link de autenticação temporária (login sem senha).

**Assinatura:**
```php
function usuario_autorizacao_provisoria($params = false)
```

**Parâmetros (Array Associativo):**
- `id_usuarios` (int) - **Obrigatório** - ID do usuário
- `expiracao` (int) - **Opcional** - Minutos até expirar (padrão: 60)
- `parametros` (string) - **Opcional** - Parâmetros extras para URL

**Retorno:**
- (string) - URL de acesso temporário

**Exemplo de Uso:**
```php
// Link de acesso com validade de 1 hora
$link = usuario_autorizacao_provisoria(Array(
    'id_usuarios' => 789
));

// Enviar por email
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'usuario@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Acesso Rápido',
        'html' => "Clique aqui para acessar: <a href='$link'>Entrar</a>"
    )
));

// Link com validade curta (15 minutos) e redirecionamento
$link = usuario_autorizacao_provisoria(Array(
    'id_usuarios' => 789,
    'expiracao' => 15,
    'parametros' => 'redirect=/admin/dashboard'
));
```

**Casos de uso:**
- Recuperação de senha
- Login por email (magic link)
- Acesso temporário para suporte
- Verificação de email

---

### usuario_host_dados()

Retorna dados completos do usuário de um host (multi-tenant).

**Assinatura:**
```php
function usuario_host_dados($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts_usuarios` (int) - **Obrigatório** - ID do usuário do host

**Retorno:**
- (array) - Dados do usuário, ou false

**Exemplo de Uso:**
```php
// Buscar dados do usuário do tenant
$usuario = usuario_host_dados(Array(
    'id_hosts_usuarios' => 42
));

if ($usuario) {
    echo "Nome: " . $usuario['nome'];
    echo "Email: " . $usuario['email'];
    echo "Status: " . $usuario['status'];
}
```

---

## Casos de Uso Comuns

### 1. Sistema de Login Completo

```php
function fazer_login($email, $senha) {
    // Validar credenciais
    $usuario = banco_select(Array(
        'campos' => Array('id_usuarios', 'senha_hash'),
        'tabela' => 'usuarios',
        'extra' => "WHERE email='$email' AND status='A'",
        'unico' => true
    ));
    
    if (!$usuario) {
        return false;
    }
    
    // Verificar senha
    if (!password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }
    
    // Gerar token de autenticação
    usuario_gerar_token_autorizacao(Array(
        'id_usuarios' => $usuario['id_usuarios']
    ));
    
    return true;
}
```

### 2. API Mobile com JWT

```php
// Endpoint de login para app
function api_login($email, $senha) {
    $usuario = validar_credenciais($email, $senha);
    
    if (!$usuario) {
        http_response_code(401);
        echo json_encode(Array('error' => 'Credenciais inválidas'));
        return;
    }
    
    // Gerar token para app
    $token = usuario_app_gerar_token_autorizacao(Array(
        'id_usuarios' => $usuario['id_usuarios'],
        'validade' => 90  // 90 dias
    ));
    
    echo json_encode(Array(
        'token' => $token,
        'user' => Array(
            'id' => $usuario['id_usuarios'],
            'name' => $usuario['nome'],
            'email' => $usuario['email']
        ),
        'expires_in' => 90 * 24 * 3600
    ));
}
```

### 3. Magic Link (Login por Email)

```php
function enviar_magic_link($email) {
    $usuario = banco_select(Array(
        'campos' => Array('id_usuarios', 'nome'),
        'tabela' => 'usuarios',
        'extra' => "WHERE email='$email'",
        'unico' => true
    ));
    
    if (!$usuario) {
        return false;
    }
    
    // Gerar link temporário (15 minutos)
    $link = usuario_autorizacao_provisoria(Array(
        'id_usuarios' => $usuario['id_usuarios'],
        'expiracao' => 15
    ));
    
    // Enviar email
    comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $email, 'nome' => $usuario['nome'])
        ),
        'mensagem' => Array(
            'assunto' => 'Link de Acesso - Válido por 15 minutos',
            'html' => "
                <p>Olá {$usuario['nome']},</p>
                <p>Clique no link abaixo para acessar sua conta:</p>
                <p><a href='$link'>Acessar Agora</a></p>
                <p>Este link expira em 15 minutos.</p>
            "
        )
    ));
    
    return true;
}
```

### 4. Configuração Inicial do Sistema

```php
function configurar_autenticacao() {
    // Gerar chaves RSA
    $chaves = usuario_openssl_gerar_chaves(Array(
        'tipo' => 'RSA',
        'senha' => 'senha_super_secreta'
    ));
    
    // Criar diretório de chaves
    $openssl_path = $_GESTOR['raiz'] . 'openssl/';
    if (!is_dir($openssl_path)) {
        mkdir($openssl_path, 0700, true);
    }
    
    // Salvar chaves
    file_put_contents($openssl_path . 'publica.key', $chaves['publica']);
    file_put_contents($openssl_path . 'privada.key', $chaves['privada']);
    
    // Definir permissões restritas
    chmod($openssl_path . 'privada.key', 0600);
    chmod($openssl_path . 'publica.key', 0644);
    
    echo "Chaves geradas e salvas em: $openssl_path";
}
```

### 5. Renovação de Token

```php
function renovar_token_se_proximo_expiracao() {
    $usuario = gestor_usuario();
    
    if (!$usuario) {
        return;
    }
    
    // Buscar token atual
    $token = banco_select(Array(
        'campos' => Array('expiration'),
        'tabela' => 'usuarios_tokens',
        'extra' => "WHERE id_usuarios='{$usuario['id_usuarios']}' 
                    AND pubID='{$_COOKIE[$_CONFIG['cookie-authname']]}'",
        'unico' => true
    ));
    
    // Se falta menos de 7 dias para expirar, renovar
    $dias_restantes = ($token['expiration'] - time()) / (24 * 3600);
    
    if ($dias_restantes < 7) {
        // Invalidar token antigo
        banco_delete('usuarios_tokens', 
            "WHERE id_usuarios='{$usuario['id_usuarios']}'");
        
        // Gerar novo token
        usuario_gerar_token_autorizacao(Array(
            'id_usuarios' => $usuario['id_usuarios']
        ));
    }
}
```

---

## Segurança

### Cookies Seguros

```php
// Cookies gerados automaticamente com flags de segurança:
setcookie($nome, $valor, [
    'expires' => $expiracao,
    'path' => '/',
    'domain' => $_SERVER['SERVER_NAME'],
    'secure' => true,      // Apenas HTTPS
    'httponly' => true,    // Não acessível via JavaScript
    'samesite' => 'Lax'   // Proteção CSRF
]);
```

### Armazenamento de Chaves

```php
// ✅ BOM - Chaves fora do webroot
/var/www/
  ├── public_html/     (DocumentRoot)
  └── openssl/         (Fora do webroot)
      ├── publica.key  (644)
      └── privada.key  (600)

// ❌ EVITAR - Chaves acessíveis pela web
/var/www/public_html/
  └── openssl/
      ├── publica.key   (Acessível via HTTP!)
      └── privada.key   (Crítico!)
```

### Validação de Token

```php
// Sempre validar antes de confiar
function validar_token_jwt($token) {
    // Verificar estrutura
    $partes = explode('.', $token);
    if (count($partes) !== 3) {
        return false;
    }
    
    // Decodificar payload
    $payload = json_decode(base64_decode($partes[1]), true);
    
    // Verificar expiração
    if ($payload['exp'] < time()) {
        return false;
    }
    
    // Verificar emissor
    if ($payload['iss'] !== $_SERVER['SERVER_NAME']) {
        return false;
    }
    
    return true;
}
```

---

## Limitações e Considerações

### Tamanho do Token

- JWT pode ficar grande (>1KB)
- Considere limites de header HTTP
- Use refresh tokens para tokens longos

### Revogação

- JWT não pode ser revogado facilmente
- Use lista negra em banco se necessário
- Tokens curtos reduzem janela de risco

### Performance

- Geração de chaves RSA é cara
- Gere uma vez, reutilize
- Cache chaves em memória quando possível

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco
- [BIBLIOTECA-IP.md](./BIBLIOTECA-IP.md) - Detecção de IP
- [BIBLIOTECA-COMUNICACAO.md](./BIBLIOTECA-COMUNICACAO.md) - Envio de emails
- [JWT.io](https://jwt.io/) - Debugger de JWT

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
