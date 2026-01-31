# Módulo: admin-ambiente

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-ambiente` |
| **Nome** | Administração do Ambiente |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-ambiente** gerencia as **configurações de ambiente** do Conn2Flow. Isso inclui variáveis de ambiente, configurações de banco de dados, integrações externas, e outras configurações que afetam o comportamento do sistema como um todo.

## 🏗️ Funcionalidades Principais

### ⚙️ **Configuração de Ambiente**
- **Variáveis de ambiente**: Gerenciar valores do .env
- **Configurações de banco**: Parâmetros de conexão
- **Chaves de API**: Credenciais para serviços externos
- **Configurações de email**: SMTP e serviços de email

### 🔐 **Segurança**
- **Chaves secretas**: JWT, criptografia, etc.
- **Tokens de API**: Gerenciamento seguro
- **Mascaramento**: Valores sensíveis ocultos
- **Auditoria**: Log de alterações

### 🌐 **Multi-tenant**
- **Domínios**: Configuração por domínio
- **Ambientes**: Desenvolvimento, staging, produção
- **Isolamento**: Configurações separadas por tenant

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `ambiente_configuracoes`
```sql
CREATE TABLE ambiente_configuracoes (
    id_ambiente_configuracoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    chave VARCHAR(255) NOT NULL,
    valor TEXT,
    tipo VARCHAR(50),                    -- string, number, boolean, json
    categoria VARCHAR(100),              -- database, email, api, security
    sensivel CHAR(1) DEFAULT 'N',        -- Se valor é sensível
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-ambiente/
├── admin-ambiente.php           # Controlador principal
├── admin-ambiente.js            # Funcionalidade client-side
├── admin-ambiente.json          # Configuração do módulo
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-ambiente/
    │       └── admin-ambiente-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Categorias de Configuração

### 🗄️ Banco de Dados
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=conn2flow
DB_USERNAME=root
DB_PASSWORD=secret
```

### 📧 Email
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=user@gmail.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@site.com
MAIL_FROM_NAME=Conn2Flow
```

### 🔐 Segurança
```
JWT_SECRET=sua-chave-jwt-secreta
JWT_TTL=3600
ENCRYPTION_KEY=chave-de-criptografia
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### 🤖 Integrações de IA
```
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_MODEL=gpt-4
AI_MAX_TOKENS=2000
```

### 🌐 Aplicação
```
APP_NAME=Conn2Flow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-site.com
APP_TIMEZONE=America/Sao_Paulo
```

## 🎨 Interface do Usuário

### Lista de Configurações
- Agrupamento por categoria
- Indicador de valor sensível
- Tipo de dado
- Ações de edição

### Formulário de Edição
- **Chave**: Nome da variável (readonly)
- **Valor**: Campo de entrada apropriado ao tipo
- **Descrição**: Explicação do propósito
- **Categoria**: Agrupamento

### Seções Especiais
- **Teste de Email**: Enviar email de teste
- **Teste de Conexão**: Verificar banco de dados
- **Validação de API**: Testar chaves de API

## 🔄 Fluxo de Configuração

### 1. Inicial (Instalação)
- Valores padrão carregados
- Assistente de configuração guia setup
- Validação de valores críticos

### 2. Modificação
- Edição via interface admin
- Validação automática de formato
- Backup antes de alteração

### 3. Sincronização
- Atualização do arquivo .env
- Cache limpo automaticamente
- Serviços reiniciados se necessário

## ⚠️ Considerações de Segurança

### Valores Sensíveis
- Nunca expostos na interface (mascarados)
- Criptografados no banco de dados
- Logs não registram valores sensíveis

### Permissões
- Apenas super admins podem acessar
- Auditoria de todas as alterações
- Confirmação para mudanças críticas

## 🔗 Módulos Relacionados
- `admin-atualizacoes`: Configurações afetam atualizações
- `admin-plugins`: Plugins podem adicionar configurações

## 💡 Boas Práticas

### Configuração
- Use valores de ambiente, não hardcoded
- Documente todas as variáveis
- Mantenha backup de .env

### Segurança
- Rotacione chaves periodicamente
- Use senhas fortes
- Limite acesso ao módulo
