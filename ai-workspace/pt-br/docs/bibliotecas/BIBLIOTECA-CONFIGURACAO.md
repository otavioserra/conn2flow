# Biblioteca: configuracao.php

> ⚙️ Gerenciamento de configurações do sistema

## Visão Geral

A biblioteca `configuracao.php` fornece funções para gerenciar configurações da administração e de hosts (multi-tenant), permitindo salvar e recuperar variáveis de configuração do sistema.

**Localização**: `gestor/bibliotecas/configuracao.php`  
**Total de Funções**: 4

## Dependências

- **Bibliotecas**: banco.php, gestor.php
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`
- **Tabelas**: `administracao_variaveis`, `hosts_variaveis`

---

## Funções Principais

### configuracao_administracao_salvar()

Salva configurações da administração do sistema.

**Assinatura:**
```php
function configuracao_administracao_salvar($params = false)
```

**Parâmetros (Array Associativo):**
- `modulo` (string) - **Obrigatório** - Módulo/categoria da configuração
- `variaveis` (array) - **Obrigatório** - Array de variáveis a salvar (chave => valor)

**Exemplo de Uso:**
```php
// Salvar configurações de email
configuracao_administracao_salvar(Array(
    'modulo' => 'email-config',
    'variaveis' => Array(
        'smtp-host' => 'smtp.gmail.com',
        'smtp-port' => '587',
        'smtp-user' => 'noreply@site.com',
        'smtp-secure' => '1'
    )
));

// Salvar configurações de pagamento
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

Recupera configurações da administração.

**Assinatura:**
```php
function configuracao_administracao($params = false)
```

**Parâmetros (Array Associativo):**
- `modulo` (string) - **Obrigatório** - Módulo/categoria da configuração

**Retorno:**
- (array) - Array associativo com as variáveis

**Exemplo de Uso:**
```php
// Carregar configurações de email
$config = configuracao_administracao(Array(
    'modulo' => 'email-config'
));

echo $config['smtp-host'];  // smtp.gmail.com
echo $config['smtp-port'];  // 587

// Usar em envio de email
$_CONFIG['email']['host'] = $config['smtp-host'];
$_CONFIG['email']['port'] = $config['smtp-port'];
```

---

### configuracao_hosts_salvar()

Salva configurações específicas de um host (multi-tenant).

**Assinatura:**
```php
function configuracao_hosts_salvar($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Obrigatório** - ID do host
- `modulo` (string) - **Obrigatório** - Módulo/categoria
- `variaveis` (array) - **Obrigatório** - Variáveis a salvar

**Exemplo de Uso:**
```php
// Configurar tema personalizado para host
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

// Configurar email específico do host
configuracao_hosts_salvar(Array(
    'id_hosts' => 5,
    'modulo' => 'email-settings',
    'variaveis' => Array(
        'from-email' => 'contato@host5.com',
        'from-name' => 'Loja Host 5'
    )
));
```

---

### configuracao_hosts_variaveis()

Recupera configurações de um host específico.

**Assinatura:**
```php
function configuracao_hosts_variaveis($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Obrigatório** - ID do host
- `modulo` (string) - **Obrigatório** - Módulo/categoria

**Retorno:**
- (array) - Array associativo com as variáveis

**Exemplo de Uso:**
```php
// Carregar tema do host
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

// Carregar configurações de email do host
$email_config = configuracao_hosts_variaveis(Array(
    'id_hosts' => 5,
    'modulo' => 'email-settings'
));

$_CONFIG['email']['from'] = $email_config['from-email'];
```

---

## Casos de Uso Comuns

### 1. Painel de Configurações do Admin

```php
function salvar_configuracoes_admin() {
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
        
        echo "Configurações salvas com sucesso!";
    }
}

function carregar_formulario_config() {
    $config = configuracao_administracao(Array(
        'modulo' => 'system-settings'
    ));
    
    echo "<form method='post'>";
    echo "  <input name='site_title' value='{$config['site-title']}'>";
    echo "  <textarea name='site_description'>{$config['site-description']}</textarea>";
    echo "  <input type='checkbox' name='maintenance' " . 
         ($config['maintenance-mode'] ? 'checked' : '') . ">";
    echo "  <button>Salvar</button>";
    echo "</form>";
}
```

### 2. Multi-Tenant com Configurações Personalizadas

```php
function aplicar_config_host($id_hosts) {
    // Carregar tema
    $theme = configuracao_hosts_variaveis(Array(
        'id_hosts' => $id_hosts,
        'modulo' => 'appearance'
    ));
    
    // Aplicar CSS customizado
    if ($theme) {
        echo "<style>";
        if (isset($theme['custom-css'])) {
            echo $theme['custom-css'];
        }
        echo "</style>";
        
        // Adicionar logo personalizado
        if (isset($theme['logo-url'])) {
            $_GESTOR['site-logo'] = $theme['logo-url'];
        }
    }
    
    // Carregar configurações de negócio
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

### 3. Sistema de Features Flags

```php
function verificar_feature_ativa($feature_name) {
    $features = configuracao_administracao(Array(
        'modulo' => 'feature-flags'
    ));
    
    return isset($features[$feature_name]) && $features[$feature_name] === '1';
}

// Uso
if (verificar_feature_ativa('new-checkout')) {
    include 'checkout-v2.php';
} else {
    include 'checkout-v1.php';
}

// Ativar feature
configuracao_administracao_salvar(Array(
    'modulo' => 'feature-flags',
    'variaveis' => Array(
        'new-checkout' => '1',
        'ai-recommendations' => '1',
        'dark-mode' => '0'
    )
));
```

### 4. Configurações de Integração

```php
function configurar_gateway_pagamento() {
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

function processar_pagamento() {
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

## Padrões e Melhores Práticas

### Organização por Módulo

```php
// ✅ BOM - Agrupar configurações relacionadas
configuracao_administracao_salvar(Array(
    'modulo' => 'smtp-config',
    'variaveis' => Array(/* configs de SMTP */)
));

configuracao_administracao_salvar(Array(
    'modulo' => 'payment-config',
    'variaveis' => Array(/* configs de pagamento */)
));

// ❌ EVITAR - Misturar tudo em um módulo
configuracao_administracao_salvar(Array(
    'modulo' => 'general',
    'variaveis' => Array(
        'smtp-host' => '...',
        'payment-key' => '...',
        'logo-url' => '...'
    )
));
```

### Valores Padrão

```php
// ✅ Sempre ter fallback
$config = configuracao_administracao(Array(
    'modulo' => 'app-settings'
));

$items_per_page = $config['items-per-page'] ?? 20;
$timezone = $config['timezone'] ?? 'America/Sao_Paulo';
```

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco
- [BIBLIOTECA-HOST.md](./BIBLIOTECA-HOST.md) - Multi-tenancy
- [BIBLIOTECA-VARIAVEIS.md](./BIBLIOTECA-VARIAVEIS.md) - Variáveis do sistema

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
