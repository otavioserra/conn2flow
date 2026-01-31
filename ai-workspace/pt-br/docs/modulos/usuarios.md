# Módulo: usuarios

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `usuarios` |
| **Nome** | Administração de Usuários |
| **Versão** | `1.0.2` |
| **Categoria** | Módulo Core |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html`, `banco`, `usuario` |

## 🎯 Propósito

O módulo **usuarios** gerencia todos os **usuários do sistema** no Conn2Flow. Isso inclui criação de contas, edição de perfis, controle de acesso, e gerenciamento de sessões. É um módulo fundamental para autenticação e autorização.

## 🏗️ Funcionalidades Principais

### 👤 **Gerenciamento de Usuários**
- **Criar usuários**: Adicionar novas contas
- **Editar usuários**: Modificar informações
- **Desativar**: Bloquear acesso sem excluir
- **Excluir**: Remover usuários permanentemente

### 🔐 **Autenticação**
- **Login/Logout**: Controle de sessão
- **Recuperação de senha**: Fluxo de reset
- **Tokens JWT**: Autenticação stateless
- **Multi-sessão**: Controle de sessões ativas

### 👥 **Perfis e Permissões**
- **Vincular perfis**: Associar usuários a perfis
- **Permissões**: Controle granular de acesso
- **Herança**: Permissões herdadas de perfis

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `usuarios`
```sql
CREATE TABLE usuarios (
    id_usuarios INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,         -- Hash bcrypt
    id_perfil VARCHAR(255),              -- Perfil de usuário
    avatar VARCHAR(255),                 -- URL do avatar
    ultimo_acesso DATETIME,
    status CHAR(1) DEFAULT 'A',          -- A=Ativo, I=Inativo, B=Bloqueado
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Tabela: `sessoes`
```sql
CREATE TABLE sessoes (
    id_sessoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    id_usuario VARCHAR(255) NOT NULL,
    token VARCHAR(500),                  -- JWT token
    ip VARCHAR(45),
    user_agent TEXT,
    expira_em DATETIME,
    status CHAR(1) DEFAULT 'A',
    data_criacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/usuarios/
├── usuarios.php                 # Controlador principal
├── usuarios.js                  # Funcionalidade client-side
├── usuarios.json                # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── modal-usuario/
    │   │   └── tabela-usuarios/
    │   └── pages/
    │       ├── usuarios/
    │       ├── usuarios-adicionar/
    │       └── usuarios-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Fluxos de Autenticação

### Login
```php
function login($email, $senha) {
    // 1. Buscar usuário
    $usuario = buscar('usuarios', ['email' => $email]);
    
    // 2. Verificar senha
    if (!password_verify($senha, $usuario['senha'])) {
        throw new Exception('Credenciais inválidas');
    }
    
    // 3. Gerar token JWT
    $token = gerarJWT($usuario['id']);
    
    // 4. Criar sessão
    inserir('sessoes', [
        'id_usuario' => $usuario['id'],
        'token' => $token,
        'ip' => obterIP(),
        'expira_em' => date('Y-m-d H:i:s', time() + 3600)
    ]);
    
    // 5. Atualizar último acesso
    atualizar('usuarios', 
        ['ultimo_acesso' => date('Y-m-d H:i:s')], 
        ['id' => $usuario['id']]
    );
    
    return $token;
}
```

### Verificação de Token
```php
function verificarAutenticacao() {
    $token = obterTokenHeader();
    
    // Validar JWT
    $payload = validarJWT($token);
    
    // Verificar sessão ativa
    $sessao = buscar('sessoes', [
        'token' => $token,
        'status' => 'A'
    ]);
    
    if (!$sessao || strtotime($sessao['expira_em']) < time()) {
        throw new Exception('Sessão expirada');
    }
    
    return $payload['usuario_id'];
}
```

## 🎨 Interface do Usuário

### Lista de Usuários
- Tabela com paginação
- Filtros por status e perfil
- Busca por nome/email
- Ações rápidas (editar, desativar)

### Formulário de Usuário
- **Nome**: Nome completo
- **Email**: Email único
- **Senha**: Campo com validação de força
- **Perfil**: Dropdown de perfis
- **Avatar**: Upload de imagem
- **Status**: Ativo/Inativo/Bloqueado

## 🔐 Segurança

### Senhas
- Mínimo 8 caracteres
- Hash bcrypt (custo 10+)
- Verificação de força
- Histórico de senhas (opcional)

### Sessões
- Expiração automática
- Invalidação em logout
- Limite de sessões simultâneas
- Garbage collector

### Proteções
- Rate limiting em login
- Bloqueio após tentativas falhas
- CSRF tokens
- XSS protection

## 🔗 Módulos Relacionados
- `usuarios-perfis`: Perfis de usuário
- `perfil-usuario`: Perfil do próprio usuário
- `modulos-operacoes`: Permissões

## 💡 Boas Práticas

### Gerenciamento
- Desative em vez de excluir
- Use perfis para permissões
- Revise acessos periodicamente

### Segurança
- Senhas fortes obrigatórias
- 2FA quando possível
- Monitore sessões ativas
