# Biblioteca: ip.php

> 🌐 Validação e obtenção de endereços IP

## Visão Geral

A biblioteca `ip.php` fornece funções para validação de endereços IP e detecção do IP real do cliente, considerando proxies reversos. Essencial para segurança, logging e geolocalização.

**Localização**: `gestor/bibliotecas/ip.php`  
**Versão**: 1.0.0  
**Total de Funções**: 2

## Dependências

- **Extensões PHP**: filter (nativa)
- **Variáveis Globais**: `$_GESTOR`, `$_SERVER`

## Variáveis Globais

```php
$_GESTOR['biblioteca-ip'] = Array(
    'versao' => '1.0.0',
);
```

---

## Funções Principais

### ip_check()

Valida se um endereço IP é válido.

**Assinatura:**
```php
function ip_check($ip, $allow_private = false, $proxy_ip = [])
```

**Parâmetros:**
- `$ip` (string) - **Obrigatório** - Endereço IP a validar
- `$allow_private` (bool) - **Opcional** - Se true, permite IPs privados (padrão: false)
- `$proxy_ip` (array) - **Opcional** - IPs de proxy confiáveis para excluir da validação

**Retorno:**
- (bool) - true se válido, false se inválido

**Validação Padrão:**
- ✅ Aceita: IPs públicos válidos
- ❌ Rejeita: 
  - IPs reservados
  - IPs privados (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
  - Loopback (127.0.0.1)
  - IPs de proxy confiáveis

**Exemplo de Uso:**
```php
// Validação básica
if (ip_check('8.8.8.8')) {
    echo "IP público válido";
}

if (!ip_check('192.168.1.1')) {
    echo "IP privado - inválido por padrão";
}

// Permitir IPs privados
if (ip_check('192.168.1.1', true)) {
    echo "IP privado válido quando allow_private=true";
}

// Excluir IPs de proxy confiáveis
$proxies = ['127.0.0.1', '10.0.0.1'];
if (!ip_check('127.0.0.1', false, $proxies)) {
    echo "IP de proxy - excluído da validação";
}

// Validar entrada do usuário
$ip_usuario = $_POST['ip'];
if (ip_check($ip_usuario)) {
    // IP válido - prosseguir
} else {
    echo "IP inválido";
}
```

**Notas:**
- Usa `FILTER_VALIDATE_IP` do PHP
- Bloqueia loopback (127.x.x.x) manualmente quando `$allow_private=false`
- Útil para whitelist/blacklist de IPs

---

### ip_get()

Obtém o endereço IP real do cliente considerando proxies reversos.

**Assinatura:**
```php
function ip_get($allow_private = false)
```

**Parâmetros:**
- `$allow_private` (bool) - **Opcional** - Se true, permite IPs privados (padrão: false)

**Retorno:**
- (string|null) - IP do cliente ou null se nenhum IP válido

**Comportamento:**
1. Verifica `$_SERVER['REMOTE_ADDR']` primeiro
2. Se for proxy confiável, verifica `HTTP_X_FORWARDED_FOR`
3. Percorre cadeia de proxies de trás para frente
4. Retorna primeiro IP válido encontrado

**Exemplo de Uso:**
```php
// Obter IP do cliente
$ip_cliente = ip_get();

if ($ip_cliente) {
    echo "Cliente: $ip_cliente";
} else {
    echo "IP não detectado";
}

// Permitir IPs privados (desenvolvimento local)
$ip_cliente = ip_get(true);

// Usar em logging
$log_entry = Array(
    'usuario_id' => $_SESSION['usuario_id'],
    'ip' => ip_get(),
    'acao' => 'login',
    'timestamp' => date('Y-m-d H:i:s')
);

banco_insert_name(Array(
    Array('usuario_id', $log_entry['usuario_id']),
    Array('ip', $log_entry['ip']),
    Array('acao', $log_entry['acao']),
    Array('timestamp', $log_entry['timestamp'])
), 'logs_acesso');

// Verificar bloqueio de IP
$ip = ip_get();
$bloqueado = banco_select(Array(
    'campos' => Array('id'),
    'tabela' => 'ips_bloqueados',
    'extra' => "WHERE ip='$ip'",
    'unico' => true
));

if ($bloqueado) {
    die("Acesso negado");
}
```

---

## Casos de Uso Comuns

### 1. Logging de Acessos

```php
function registrar_acesso($usuario_id, $acao) {
    $ip = ip_get();
    
    banco_insert_name(Array(
        Array('usuario_id', $usuario_id),
        Array('ip', $ip),
        Array('acao', $acao),
        Array('user_agent', $_SERVER['HTTP_USER_AGENT']),
        Array('timestamp', 'NOW()', true, false)
    ), 'logs_acesso');
}

// Uso
registrar_acesso($_SESSION['usuario_id'], 'login');
registrar_acesso($_SESSION['usuario_id'], 'alterou_senha');
```

### 2. Bloqueio de IPs

```php
function verificar_bloqueio() {
    $ip = ip_get();
    
    if (!$ip) {
        return false; // IP não detectado - permitir
    }
    
    // Verificar se está bloqueado
    $bloqueado = banco_select(Array(
        'campos' => Array('motivo', 'bloqueado_ate'),
        'tabela' => 'ips_bloqueados',
        'extra' => "WHERE ip='$ip' AND (bloqueado_ate IS NULL OR bloqueado_ate > NOW())",
        'unico' => true
    ));
    
    if ($bloqueado) {
        header('HTTP/1.1 403 Forbidden');
        echo "Acesso negado: " . $bloqueado['motivo'];
        exit;
    }
}

// Chamar no início de páginas protegidas
verificar_bloqueio();
```

### 3. Whitelist de IPs

```php
function verificar_whitelist() {
    $ip = ip_get();
    
    $permitidos = Array(
        '8.8.8.8',
        '200.100.50.25',
        '192.168.1.0/24' // Subnet local
    );
    
    foreach ($permitidos as $permitido) {
        if (strpos($permitido, '/') !== false) {
            // Subnet - verificação CIDR
            if (ip_in_subnet($ip, $permitido)) {
                return true;
            }
        } else {
            // IP único
            if ($ip === $permitido) {
                return true;
            }
        }
    }
    
    return false;
}

// Área administrativa - apenas IPs da whitelist
if (!verificar_whitelist()) {
    die("Acesso restrito");
}
```

### 4. Rate Limiting por IP

```php
function verificar_rate_limit($limite = 100, $janela = 3600) {
    $ip = ip_get();
    
    if (!$ip) {
        return true; // Permitir se não detectar IP
    }
    
    // Contar requisições na janela de tempo
    $count = banco_select(Array(
        'campos' => Array('COUNT(*) as total'),
        'tabela' => 'rate_limit',
        'extra' => "WHERE ip='$ip' AND timestamp > DATE_SUB(NOW(), INTERVAL $janela SECOND)",
        'unico' => true
    ));
    
    if ($count && $count['total'] >= $limite) {
        header('HTTP/1.1 429 Too Many Requests');
        echo "Muitas requisições. Tente novamente em " . ($janela / 60) . " minutos.";
        exit;
    }
    
    // Registrar requisição
    banco_insert_name(Array(
        Array('ip', $ip),
        Array('timestamp', 'NOW()', true, false)
    ), 'rate_limit');
    
    return true;
}

// Proteger API
verificar_rate_limit(100, 3600); // 100 req/hora
```

### 5. Geolocalização

```php
function obter_localizacao($ip = null) {
    if ($ip === null) {
        $ip = ip_get();
    }
    
    if (!$ip || !ip_check($ip)) {
        return null;
    }
    
    // Usar serviço de geolocalização (exemplo: ipapi.co)
    $url = "https://ipapi.co/$ip/json/";
    $response = file_get_contents($url);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Uso
$localizacao = obter_localizacao();

if ($localizacao) {
    echo "País: " . $localizacao['country_name'];
    echo "Cidade: " . $localizacao['city'];
}
```

---

## Configuração de Proxy Reverso

### Editando a Função ip_get()

Se você usa Nginx, Apache ou outro proxy reverso, configure os IPs e headers corretos:

```php
function ip_get($allow_private = false){
    // CONFIGURAR: IPs dos seus proxies reversos
    $proxy_ip = [
        '127.0.0.1',        // Localhost
        '10.0.0.1',         // Proxy interno
        '172.17.0.1',       // Docker gateway
        // Adicione seus IPs de proxy aqui
    ];

    // CONFIGURAR: Header usado pelo seu proxy
    // Opções comuns:
    // - HTTP_X_FORWARDED_FOR (Nginx, Apache mod_proxy)
    // - HTTP_CLIENT_IP (Apache mod_remoteip)
    // - HTTP_X_REAL_IP (Nginx com proxy_set_header)
    // - HTTP_CF_CONNECTING_IP (Cloudflare)
    $header = 'HTTP_X_FORWARDED_FOR';

    // Resto da função permanece igual...
}
```

### Exemplo para Cloudflare

```php
$proxy_ip = [
    // IPs do Cloudflare
    '103.21.244.0/22',
    '103.22.200.0/22',
    // ... (adicione todos os ranges do Cloudflare)
];

$header = 'HTTP_CF_CONNECTING_IP';
```

---

## Padrões e Melhores Práticas

### Segurança

1. **Nunca confie cegamente em headers HTTP:**
```php
// ❌ INSEGURO - Pode ser falsificado
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

// ✅ SEGURO - Valida contra proxies confiáveis
$ip = ip_get();
```

2. **Configure corretamente os IPs de proxy:**
```php
// ❌ ERRADO - Lista vazia permite spoofing
$proxy_ip = [];

// ✅ CORRETO - Apenas proxies realmente confiáveis
$proxy_ip = ['10.0.0.1']; // Seu proxy verificado
```

3. **Use em combinação com outras medidas:**
```php
// IP + User Agent + CSRF Token
$fingerprint = md5(
    ip_get() . 
    $_SERVER['HTTP_USER_AGENT'] . 
    $_SESSION['csrf_token']
);
```

### Performance

1. **Cache resultados quando possível:**
```php
// Uma vez por requisição
if (!isset($_GESTOR['client_ip'])) {
    $_GESTOR['client_ip'] = ip_get();
}

$ip = $_GESTOR['client_ip'];
```

2. **Considere rate limiting de chamadas externas:**
```php
// Não chame serviços de geolocalização em cada request
// Use cache ou fila de processamento
```

---

## Limitações e Considerações

### Headers HTTP podem ser falsificados

- Apenas confie em `REMOTE_ADDR` direto ou proxies verificados
- Configure whitelist de proxies corretamente

### IPv6

- Funções suportam IPv6 via `FILTER_VALIDATE_IP`
- Teste adequadamente em ambientes IPv6

### Proxies em cadeia

- `X-Forwarded-For` pode conter múltiplos IPs
- Função percorre de trás para frente (mais confiável)
- Formato: `client, proxy1, proxy2`

### IPs dinâmicos

- Bloqueios por IP podem afetar usuários inocentes
- Considere bloqueios temporários e CAPTCHA

---

## Veja Também

- [PHP filter_var](https://www.php.net/manual/pt_BR/function.filter-var.php) - Documentação oficial
- [X-Forwarded-For](https://en.wikipedia.org/wiki/X-Forwarded-For) - Formato e uso
- [BIBLIOTECA-LOG.md](./BIBLIOTECA-LOG.md) - Sistema de logs

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
