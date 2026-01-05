# Biblioteca: host.php

> 🌍 Utilitários para gerenciamento de hosts/domínios

## Visão Geral

A biblioteca `host.php` fornece funções para obtenção de informações sobre hosts (domínios) do sistema, incluindo URLs, identificadores públicos e configurações específicas de loja.

**Localização**: `gestor/bibliotecas/host.php`  
**Versão**: 1.0.2  
**Total de Funções**: 3

## Dependências

- **Bibliotecas**: banco.php
- **Variáveis Globais**: `$_GESTOR`, `$_HOST`

## Variáveis Globais

```php
$_GESTOR['biblioteca-host'] = Array(
    'versao' => '1.0.2',
);

// Informações do host atual
$_GESTOR['host-id'] // ID do host atual

// Cache de informações
$_HOST['dominio']   // Domínio do host
$_HOST['pubID']     // Identificador público
$_HOST['lojaNome']  // Nome da loja
```

---

## Funções Principais

### host_url()

Retorna a URL do host.

**Assinatura:**
```php
function host_url($params = false)
```

**Parâmetros (Array Associativo):**
- `opcao` (string) - **Opcional** - Formato de retorno ('full' para URL completa)
- `id_hosts` (int) - **Opcional** - ID específico do host (padrão: host atual)

**Retorno:**
- (string|false) - URL do host ou false se não encontrado

**Opções:**
- `'full'`: Retorna `https://dominio.com/`
- Padrão: Retorna `dominio.com`

**Exemplo de Uso:**
```php
// URL do host atual (apenas domínio)
$dominio = host_url();
// Retorna: "meusite.com.br"

// URL completa
$url_completa = host_url(Array('opcao' => 'full'));
// Retorna: "https://meusite.com.br/"

// URL de host específico
$url_outro = host_url(Array('id_hosts' => 5));
// Retorna: "outrosite.com"

// Usar em links
$link = host_url(Array('opcao' => 'full')) . 'pagina/contato';
// Retorna: "https://meusite.com.br/pagina/contato"
```

**Notas:**
- Faz cache em `$_HOST['dominio']`
- Sempre usa HTTPS na opção 'full'

---

### host_pub_id()

Retorna o identificador público do host.

**Assinatura:**
```php
function host_pub_id($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Opcional** - ID específico do host (padrão: host atual)

**Retorno:**
- (string|false) - Identificador público ou false

**Exemplo de Uso:**
```php
// PubID do host atual
$pub_id = host_pub_id();
// Retorna: "abc123def456"

// PubID de host específico
$pub_id_outro = host_pub_id(Array('id_hosts' => 3));

// Usar em integrações
$api_endpoint = "https://api.exemplo.com/cliente/" . host_pub_id();

// Gerar chave de API
$api_key = md5(host_pub_id() . SECRET_KEY);
```

**Notas:**
- Faz cache em `$_HOST['pubID']`
- Útil para identificação externa
- Único por host

---

### host_loja_nome()

Retorna o nome da loja do host.

**Assinatura:**
```php
function host_loja_nome($params = false)
```

**Parâmetros (Array Associativo):**
- `id_hosts` (int) - **Opcional** - ID específico do host (padrão: host atual)

**Retorno:**
- (string|false) - Nome da loja ou false

**Exemplo de Uso:**
```php
// Nome da loja atual
$loja_nome = host_loja_nome();
// Retorna: "Loja Virtual ABC"

// Usar em título de página
echo "<title>" . host_loja_nome() . " - Produtos</title>";

// Usar em emails
$mensagem = "Obrigado por comprar em " . host_loja_nome();

// Nome de loja específica
$loja_outro = host_loja_nome(Array('id_hosts' => 10));
```

**Comportamento:**
- Busca em `hosts_variaveis` (módulo: `loja-configuracoes`, id: `nome`)
- Se não encontrar, retorna "Minha Loja {id_hosts}"
- Faz cache em `$_HOST['lojaNome']`

---

## Casos de Uso Comuns

### 1. Links Absolutos Multi-Host

```php
function gerar_link_produto($produto_id, $host_id = null) {
    $url_base = host_url(Array(
        'opcao' => 'full',
        'id_hosts' => $host_id
    ));
    
    return $url_base . "produto/" . $produto_id;
}

// Link no host atual
$link = gerar_link_produto(123);
// https://meusite.com.br/produto/123

// Link em outro host
$link_outro = gerar_link_produto(123, 5);
// https://outrosite.com/produto/123
```

### 2. Emails Personalizados

```php
function enviar_email_confirmacao($pedido) {
    $loja_nome = host_loja_nome();
    $url_loja = host_url(Array('opcao' => 'full'));
    
    $assunto = "Pedido confirmado - $loja_nome";
    
    $mensagem = "
        <h1>Obrigado por comprar em $loja_nome!</h1>
        <p>Seu pedido #{$pedido['numero']} foi confirmado.</p>
        <p>Acompanhe em: {$url_loja}meus-pedidos/{$pedido['id']}</p>
    ";
    
    comunicacao_email(Array(
        'para' => $pedido['cliente_email'],
        'assunto' => $assunto,
        'mensagem' => $mensagem
    ));
}
```

### 3. Integração com API Externa

```php
function sincronizar_com_plataforma() {
    $pub_id = host_pub_id();
    $loja_nome = host_loja_nome();
    
    $dados = Array(
        'store_id' => $pub_id,
        'store_name' => $loja_nome,
        'products' => obter_produtos()
    );
    
    $ch = curl_init('https://api.plataforma.com/sync');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
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
function listar_todas_lojas() {
    $hosts = banco_select(Array(
        'campos' => Array('id_hosts', 'dominio', 'ativo'),
        'tabela' => 'hosts',
        'extra' => "WHERE ativo=1 ORDER BY dominio"
    ));
    
    if ($hosts) {
        foreach ($hosts as $host) {
            $loja_nome = host_loja_nome(Array('id_hosts' => $host['id_hosts']));
            $url = host_url(Array(
                'opcao' => 'full',
                'id_hosts' => $host['id_hosts']
            ));
            
            echo "<tr>";
            echo "<td>{$host['dominio']}</td>";
            echo "<td>$loja_nome</td>";
            echo "<td><a href='$url' target='_blank'>Visitar</a></td>";
            echo "</tr>";
        }
    }
}
```

### 5. Relatórios Multi-Host

```php
function relatorio_vendas_por_host($data_inicio, $data_fim) {
    $hosts = banco_select(Array(
        'campos' => Array('id_hosts'),
        'tabela' => 'hosts',
        'extra' => "WHERE ativo=1"
    ));
    
    $relatorio = Array();
    
    foreach ($hosts as $host) {
        $id_hosts = $host['id_hosts'];
        
        $vendas = banco_select(Array(
            'campos' => Array('SUM(total) as total', 'COUNT(*) as quantidade'),
            'tabela' => 'pedidos',
            'extra' => "WHERE id_hosts='$id_hosts' 
                        AND data BETWEEN '$data_inicio' AND '$data_fim'
                        AND status='pago'",
            'unico' => true
        ));
        
        $relatorio[] = Array(
            'host' => host_url(Array('id_hosts' => $id_hosts)),
            'loja' => host_loja_nome(Array('id_hosts' => $id_hosts)),
            'total' => $vendas['total'] ?? 0,
            'quantidade' => $vendas['quantidade'] ?? 0
        );
    }
    
    return $relatorio;
}
```

---

## Estrutura de Tabelas

### Tabela: hosts

```sql
CREATE TABLE hosts (
    id_hosts INT PRIMARY KEY AUTO_INCREMENT,
    dominio VARCHAR(255) NOT NULL,
    pub_id VARCHAR(255) UNIQUE,
    ativo TINYINT DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tabela: hosts_variaveis

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

## Padrões e Melhores Práticas

### Cache de Informações

```php
// ✅ BOM - Usa cache automático
$nome1 = host_loja_nome();
$nome2 = host_loja_nome(); // Não faz nova query

// ❌ Evitar - Múltiplas chamadas sem necessidade
for ($i = 0; $i < 100; $i++) {
    echo host_url(); // Funciona mas desnecessário
}

// ✅ MELHOR - Cache manual
$url = host_url();
for ($i = 0; $i < 100; $i++) {
    echo $url;
}
```

### Validação de Host

```php
// Sempre validar antes de usar
$url = host_url();
if ($url === false) {
    // Host não configurado
    die("Erro de configuração");
}
```

### Multi-tenancy

```php
// Para sistemas multi-tenant, sempre passe id_hosts
function obter_config_host($id_hosts) {
    return Array(
        'url' => host_url(Array('id_hosts' => $id_hosts)),
        'pub_id' => host_pub_id(Array('id_hosts' => $id_hosts)),
        'nome' => host_loja_nome(Array('id_hosts' => $id_hosts))
    );
}
```

---

## Limitações e Considerações

### HTTPS Hardcoded

- Função `host_url()` com opção 'full' sempre retorna HTTPS
- Não suporta HTTP puro
- Para ambiente de desenvolvimento, ajuste manualmente

### Cache Global

- Cache em `$_HOST` é por requisição
- Múltiplos hosts na mesma requisição podem causar conflito
- Use parâmetro `id_hosts` explicitamente quando necessário

### Dependência de Banco

- Todas as funções fazem queries
- Em ambientes de alta performance, considere cache adicional (Redis, Memcached)

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Operações de banco de dados
- [BIBLIOTECA-VARIAVEIS.md](./BIBLIOTECA-VARIAVEIS.md) - Sistema de variáveis

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
