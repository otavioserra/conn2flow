# Módulo: contatos

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `contatos` |
| **Nome** | Gerenciamento de Contatos |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo de Comunicação |
| **Complexidade** | 🟢 Baixa |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco` |

## 🎯 Propósito

O módulo **contatos** gerencia os **registros de contato recebidos** no Conn2Flow. Ele armazena mensagens enviadas através de formulários de contato do site, permitindo visualização, resposta e gerenciamento dessas comunicações.

## 🏗️ Funcionalidades Principais

### 📨 **Recebimento de Contatos**
- **Captura**: Receber mensagens de formulários
- **Validação**: Verificar dados obrigatórios
- **Armazenamento**: Salvar no banco de dados
- **Notificação**: Alertar administradores

### 📋 **Gerenciamento**
- **Listar**: Visualizar todos os contatos
- **Filtrar**: Por status, data, origem
- **Marcar**: Lido/Não lido, respondido
- **Excluir**: Remover contatos

### 📧 **Resposta**
- **Responder**: Enviar email de resposta
- **Templates**: Usar respostas padrão
- **Histórico**: Manter registro de respostas

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `contatos`
```sql
CREATE TABLE contatos (
    id_contatos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    assunto VARCHAR(255),
    mensagem TEXT NOT NULL,
    origem VARCHAR(100),                 -- Formulário de origem
    ip VARCHAR(45),                      -- IP do visitante
    user_agent TEXT,                     -- Navegador
    lido CHAR(1) DEFAULT 'N',            -- S = Lido
    respondido CHAR(1) DEFAULT 'N',      -- S = Respondido
    data_resposta DATETIME,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela de Respostas
```sql
CREATE TABLE contatos_respostas (
    id_contatos_respostas INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    id_contato VARCHAR(255) NOT NULL,
    id_usuario VARCHAR(255) NOT NULL,    -- Quem respondeu
    mensagem TEXT NOT NULL,
    status CHAR(1) DEFAULT 'A',
    data_criacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/contatos/
├── contatos.php                 # Controlador principal
├── contatos.js                  # Funcionalidade client-side
├── contatos.json                # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── modal-contato/
    │   │   └── form-resposta/
    │   └── pages/
    │       ├── contatos/
    │       └── contatos-detalhe/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Integração com Formulários

### Endpoint de Recebimento
```php
// POST /api/contato
function receberContato($dados) {
    // 1. Validar dados
    $erros = validar($dados, [
        'nome' => 'obrigatorio|min:2',
        'email' => 'obrigatorio|email',
        'mensagem' => 'obrigatorio|min:10'
    ]);
    
    if ($erros) {
        return ['sucesso' => false, 'erros' => $erros];
    }
    
    // 2. Sanitizar
    $dados = sanitizar($dados);
    
    // 3. Adicionar metadados
    $dados['ip'] = obterIP();
    $dados['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $dados['origem'] = $dados['origem'] ?? 'form-contato';
    
    // 4. Inserir
    $id = inserir('contatos', $dados);
    
    // 5. Notificar admin
    notificarAdmin('Novo contato recebido', $dados);
    
    return ['sucesso' => true, 'id' => $id];
}
```

### Formulário HTML
```html
<form id="form-contato" action="/api/contato" method="POST">
    <input type="text" name="nome" required placeholder="Seu nome">
    <input type="email" name="email" required placeholder="Seu email">
    <input type="tel" name="telefone" placeholder="Telefone (opcional)">
    <input type="text" name="assunto" placeholder="Assunto">
    <textarea name="mensagem" required placeholder="Sua mensagem"></textarea>
    <input type="hidden" name="origem" value="pagina-contato">
    <button type="submit">Enviar</button>
</form>
```

## 🎨 Interface do Usuário

### Lista de Contatos
- Tabela com indicadores visuais
- Badge de não lidos
- Filtros por status
- Busca por nome/email
- Ações em massa

### Detalhe do Contato
- Informações completas
- Metadados (IP, data, origem)
- Formulário de resposta
- Histórico de respostas

## 📧 Sistema de Notificações

### Notificação de Novo Contato
```php
function notificarAdmin($assunto, $contato) {
    $admins = listar('usuarios', [
        'perfil' => 'admin',
        'notificacoes_contato' => 'S'
    ]);
    
    foreach ($admins as $admin) {
        enviarEmail([
            'para' => $admin['email'],
            'assunto' => "Novo contato: {$contato['assunto']}",
            'template' => 'email-novo-contato',
            'dados' => $contato
        ]);
    }
}
```

## 🔐 Anti-Spam

### Medidas de Proteção
- **reCAPTCHA**: Validação de humanos
- **Honeypot**: Campo oculto para bots
- **Rate Limiting**: Limite de envios por IP
- **Blacklist**: IPs bloqueados

### Implementação Honeypot
```html
<!-- Campo invisível para humanos -->
<input type="text" name="website" style="display:none" tabindex="-1">
```

```php
// Verificar honeypot
if (!empty($dados['website'])) {
    // Provavelmente um bot
    return ['sucesso' => false, 'erro' => 'Spam detectado'];
}
```

## 💡 Boas Práticas

### Formulários
- Valide no front e backend
- Use HTTPS
- Implemente anti-spam
- Confirme recebimento ao usuário

### Gerenciamento
- Responda rapidamente
- Marque como lido após visualizar
- Arquive contatos antigos
- Mantenha histórico de respostas

### Privacidade
- Informe sobre coleta de dados
- Permita exclusão sob solicitação
- Não exponha dados sensíveis
- Cumpra LGPD/GDPR

## 🔗 Módulos Relacionados
- `admin-email`: Configurações de email
- `admin-templates`: Templates de email
- `usuarios`: Notificações para admins
