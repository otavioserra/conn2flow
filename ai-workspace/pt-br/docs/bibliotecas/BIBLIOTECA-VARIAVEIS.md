# Biblioteca: variaveis.php

> 🔧 Gerenciamento de variáveis de sistema

## Visão Geral

A biblioteca `variaveis.php` fornece funções para gerenciar variáveis de configuração do sistema, organizadas por grupos. Permite obter, incluir e atualizar variáveis armazenadas no banco de dados.

**Localização**: `gestor/bibliotecas/variaveis.php`  
**Versão**: 1.0.0  
**Total de Funções**: 3

## Dependências

- **Bibliotecas**: banco.php
- **Variáveis Globais**: `$_GESTOR`, `$_VARIAVEIS_SISTEMA`

## Variáveis Globais

```php
$_GESTOR['biblioteca-variaveis'] = Array(
    'versao' => '1.0.0',
);

// Cache de variáveis do sistema
$_VARIAVEIS_SISTEMA[$grupo][$id] = 'valor';
```

---

## Funções Principais

### variaveis_sistema()

Retorna uma variável ou grupo de variáveis do sistema.

**Assinatura:**
```php
function variaveis_sistema($grupo, $id = false)
```

**Parâmetros:**
- `$grupo` (string) - **Obrigatório** - Grupo da variável
- `$id` (string) - **Opcional** - ID da variável específica

**Retorno:**
- (string|array|null) - Valor da variável, array do grupo, ou null

**Exemplo de Uso:**
```php
// Obter variável específica
$valor = variaveis_sistema('email', 'smtp_host');
echo $valor; // 'smtp.example.com'

// Obter grupo completo
$config_email = variaveis_sistema('email');
// Array(
//     'smtp_host' => 'smtp.example.com',
//     'smtp_port' => '587',
//     'smtp_user' => 'user@example.com'
// )

// Variável não existente
$valor = variaveis_sistema('email', 'nao_existe');
echo $valor; // null
```

**Comportamento:**
- Faz cache em `$_VARIAVEIS_SISTEMA` após primeira consulta
- Consultas subsequentes usam cache (não consultam banco)
- Se `$id` não informado, retorna array com todo o grupo
- Se `$id` informado, retorna valor específico ou null

**Notas:**
- Busca apenas variáveis com `modulo='_sistema'`
- Cache persiste durante toda a requisição

---

### variaveis_sistema_incluir()

Inclui uma nova variável do sistema se não existir.

**Assinatura:**
```php
function variaveis_sistema_incluir($grupo, $id, $valor, $tipo = 'string')
```

**Parâmetros:**
- `$grupo` (string) - **Obrigatório** - Grupo da variável
- `$id` (string) - **Obrigatório** - ID da variável
- `$valor` (string) - **Obrigatório** - Valor a incluir
- `$tipo` (string) - **Opcional** - Tipo da variável (padrão: 'string')

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Incluir configuração de email
variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
variaveis_sistema_incluir('email', 'smtp_port', '587');
variaveis_sistema_incluir('email', 'smtp_ssl', '1', 'bool');

// Incluir configuração de API
variaveis_sistema_incluir('api', 'key', 'abc123xyz');
variaveis_sistema_incluir('api', 'timeout', '30', 'int');

// Se variável já existe, não faz nada
variaveis_sistema_incluir('email', 'smtp_host', 'outro.servidor.com');
// smtp_host permanece 'smtp.gmail.com'
```

**Comportamento:**
- Verifica se variável já existe antes de inserir
- Se existir, não faz nada (não atualiza)
- Insere com `modulo='_sistema'`

**Notas:**
- Use `variaveis_sistema_atualizar()` para modificar valores existentes
- Parâmetro `$tipo` é armazenado mas não afeta validação

---

### variaveis_sistema_atualizar()

Atualiza o valor de uma variável existente.

**Assinatura:**
```php
function variaveis_sistema_atualizar($grupo, $id, $valor)
```

**Parâmetros:**
- `$grupo` (string) - **Obrigatório** - Grupo da variável
- `$id` (string) - **Obrigatório** - ID da variável
- `$valor` (string) - **Obrigatório** - Novo valor

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Atualizar configuração
variaveis_sistema_atualizar('email', 'smtp_host', 'smtp.outlook.com');
variaveis_sistema_atualizar('email', 'smtp_port', '465');

// Atualizar chave de API
variaveis_sistema_atualizar('api', 'key', 'nova_chave_123');

// Definir como NULL
variaveis_sistema_atualizar('cache', 'redis_host', null);
```

**Comportamento:**
- Atualiza valor mesmo que variável não exista
- Se `$valor` é null, define campo como NULL no banco
- Não valida se variável existe antes de atualizar

**Notas:**
- Não limpa cache automaticom ente
- Para garantir valor atualizado, recarregue página ou reinicie aplicação

---

## Casos de Uso Comuns

### 1. Configuração Inicial do Sistema

```php
function inicializar_configuracoes() {
    // Configurações de email
    variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
    variaveis_sistema_incluir('email', 'smtp_port', '587');
    variaveis_sistema_incluir('email', 'smtp_user', 'noreply@example.com');
    variaveis_sistema_incluir('email', 'smtp_ssl', '1', 'bool');
    
    // Configurações de API
    variaveis_sistema_incluir('api', 'google_maps_key', '');
    variaveis_sistema_incluir('api', 'recaptcha_site', '');
    variaveis_sistema_incluir('api', 'recaptcha_secret', '');
    
    // Configurações de sistema
    variaveis_sistema_incluir('sistema', 'manutencao', '0', 'bool');
    variaveis_sistema_incluir('sistema', 'debug', '0', 'bool');
    variaveis_sistema_incluir('sistema', 'versao', '1.0.0');
}
```

### 2. Painel de Configurações

```php
function salvar_configuracoes_email($dados) {
    // Atualizar configurações
    variaveis_sistema_atualizar('email', 'smtp_host', $dados['smtp_host']);
    variaveis_sistema_atualizar('email', 'smtp_port', $dados['smtp_port']);
    variaveis_sistema_atualizar('email', 'smtp_user', $dados['smtp_user']);
    variaveis_sistema_atualizar('email', 'smtp_pass', $dados['smtp_pass']);
    
    return true;
}

// Recuperar para exibir no form
function obter_configuracoes_email() {
    return Array(
        'smtp_host' => variaveis_sistema('email', 'smtp_host'),
        'smtp_port' => variaveis_sistema('email', 'smtp_port'),
        'smtp_user' => variaveis_sistema('email', 'smtp_user')
    );
}
```

### 3. Feature Flags

```php
function feature_ativa($feature) {
    $valor = variaveis_sistema('features', $feature);
    return $valor === '1';
}

// Ativar/desativar features
variaveis_sistema_incluir('features', 'novo_dashboard', '0', 'bool');
variaveis_sistema_incluir('features', 'modo_escuro', '1', 'bool');
variaveis_sistema_incluir('features', 'chat_suporte', '1', 'bool');

// Usar
if (feature_ativa('novo_dashboard')) {
    // Exibir novo dashboard
} else {
    // Exibir dashboard legado
}
```

### 4. Configuração de Módulos

```php
function configurar_modulo_pagamento() {
    // Verificar se já configurado
    $gateway = variaveis_sistema('pagamento', 'gateway');
    
    if (!$gateway) {
        // Primeira configuração
        variaveis_sistema_incluir('pagamento', 'gateway', 'stripe');
        variaveis_sistema_incluir('pagamento', 'moeda', 'BRL');
        variaveis_sistema_incluir('pagamento', 'taxa_servico', '2.5');
    }
    
    return variaveis_sistema('pagamento');
}
```

### 5. Cache de Configuração

```php
class ConfigManager {
    private $cache = Array();
    
    public function get($grupo, $id = null) {
        $key = "$grupo:$id";
        
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = variaveis_sistema($grupo, $id);
        }
        
        return $this->cache[$key];
    }
    
    public function set($grupo, $id, $valor) {
        variaveis_sistema_atualizar($grupo, $id, $valor);
        
        // Limpar cache
        $key = "$grupo:$id";
        unset($this->cache[$key]);
    }
}

$config = new ConfigManager();
$smtp = $config->get('email', 'smtp_host');
```

---

## Estrutura de Tabela

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

## Padrões e Melhores Práticas

### Nomenclatura

```php
// ✅ BOM - Nomes descritivos
variaveis_sistema_incluir('email', 'smtp_host', 'smtp.gmail.com');
variaveis_sistema_incluir('email', 'smtp_port', '587');

// ❌ EVITAR - Nomes genéricos
variaveis_sistema_incluir('config', 'var1', 'valor');
```

### Organização por Grupos

```php
// ✅ BOM - Agrupar configurações relacionadas
variaveis_sistema_incluir('email', 'smtp_host', '...');
variaveis_sistema_incluir('email', 'smtp_port', '...');
variaveis_sistema_incluir('email', 'smtp_user', '...');

variaveis_sistema_incluir('api', 'google_key', '...');
variaveis_sistema_incluir('api', 'stripe_key', '...');
```

### Valores Padrão

```php
// ✅ BOM - Usar valor padrão se não existir
$host = variaveis_sistema('email', 'smtp_host');
if (!$host) {
    $host = 'smtp.gmail.com'; // Padrão
}

// ✅ MELHOR - Com operador ternário
$host = variaveis_sistema('email', 'smtp_host') ?: 'smtp.gmail.com';
```

---

## Limitações e Considerações

### Cache Não Atualiza Automaticamente

```php
// ⚠️ Cache não é atualizado após update
variaveis_sistema_atualizar('email', 'smtp_host', 'novo.host.com');
$host = variaveis_sistema('email', 'smtp_host'); // Ainda retorna valor antigo

// Solução: Recarregar página ou limpar cache manualmente
unset($_VARIAVEIS_SISTEMA['email']);
```

### Sem Validação de Tipo

```php
// Tipo é apenas informativo, não valida
variaveis_sistema_incluir('config', 'numero', 'abc', 'int');
// Aceita 'abc' mesmo sendo tipo 'int'
```

### Apenas Módulo '_sistema'

```php
// Apenas busca variáveis com modulo='_sistema'
// Outras variáveis requerem consulta direta ao banco
```

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco de dados
- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Variáveis do gestor

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
