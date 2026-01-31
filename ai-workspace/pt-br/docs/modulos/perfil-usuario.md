# Módulo: perfil-usuario

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `perfil-usuario` |
| **Nome** | Meu Perfil |
| **Versão** | `1.0.1` |
| **Categoria** | Módulo de Usuário |
| **Complexidade** | 🟢 Baixa |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **perfil-usuario** permite que os **usuários gerenciem seus próprios dados pessoais** no Conn2Flow. Diferente do módulo `usuarios` (que é administrativo), este módulo é acessível por qualquer usuário autenticado para editar suas próprias informações.

## 🏗️ Funcionalidades Principais

### 👤 **Dados Pessoais**
- **Editar nome**: Alterar nome de exibição
- **Alterar email**: Atualizar endereço de email
- **Avatar**: Upload de foto de perfil
- **Preferências**: Configurações pessoais

### 🔐 **Segurança da Conta**
- **Alterar senha**: Trocar senha atual
- **Ver sessões**: Sessões ativas
- **Encerrar sessões**: Logout de dispositivos
- **2FA**: Autenticação de dois fatores (se habilitado)

### ⚙️ **Preferências**
- **Idioma**: Preferência de idioma
- **Tema**: Claro/Escuro (se disponível)
- **Notificações**: Configurações de notificação
- **Fuso horário**: Timezone preferido

## 🗄️ Estrutura do Banco de Dados

O módulo utiliza principalmente a tabela `usuarios`, mas com acesso limitado aos próprios dados:

### Campos Editáveis pelo Usuário
```sql
-- Campos que o usuário pode alterar no próprio perfil
nome VARCHAR(255),
email VARCHAR(255),
avatar VARCHAR(255),
preferencias JSON    -- Preferências pessoais
```

### Tabela de Preferências (JSON)
```json
{
    "idioma": "pt-br",
    "tema": "claro",
    "notificacoes": {
        "email": true,
        "push": false
    },
    "timezone": "America/Sao_Paulo"
}
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/perfil-usuario/
├── perfil-usuario.php           # Controlador principal
├── perfil-usuario.js            # Funcionalidade client-side
├── perfil-usuario.json          # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   ├── form-dados-pessoais/
    │   │   ├── form-alterar-senha/
    │   │   └── lista-sessoes/
    │   └── pages/
    │       ├── perfil-usuario/
    │       └── perfil-usuario-seguranca/
    └── en/
        └── ... (mesma estrutura)
```

## 🎨 Interface do Usuário

### Página Principal do Perfil
- **Seção de Avatar**: Foto com opção de alterar
- **Dados Pessoais**: Nome, email
- **Preferências**: Idioma, tema, timezone
- **Link para Segurança**: Acesso às opções de segurança

### Página de Segurança
- **Alterar Senha**: Formulário com senha atual e nova
- **Sessões Ativas**: Lista de dispositivos logados
- **2FA**: Ativar/desativar autenticação de dois fatores

## 🔧 Fluxos Principais

### Alterar Senha
```php
function alterarSenha($usuarioId, $senhaAtual, $novaSenha) {
    // 1. Buscar usuário
    $usuario = buscar('usuarios', ['id' => $usuarioId]);
    
    // 2. Verificar senha atual
    if (!password_verify($senhaAtual, $usuario['senha'])) {
        throw new Exception('Senha atual incorreta');
    }
    
    // 3. Validar nova senha
    validarForcaSenha($novaSenha);
    
    // 4. Atualizar senha
    atualizar('usuarios', [
        'senha' => password_hash($novaSenha, PASSWORD_BCRYPT)
    ], ['id' => $usuarioId]);
    
    // 5. Invalidar outras sessões (opcional)
    invalidarSessoes($usuarioId, exceto: sessaoAtual());
    
    return true;
}
```

### Upload de Avatar
```php
function atualizarAvatar($usuarioId, $arquivo) {
    // 1. Validar arquivo
    validarImagem($arquivo, [
        'tipos' => ['image/jpeg', 'image/png'],
        'maxSize' => 2 * 1024 * 1024  // 2MB
    ]);
    
    // 2. Redimensionar
    $imagem = redimensionar($arquivo, 200, 200);
    
    // 3. Salvar
    $caminho = salvarArquivo($imagem, 'avatars/' . $usuarioId);
    
    // 4. Atualizar banco
    atualizar('usuarios', [
        'avatar' => $caminho
    ], ['id' => $usuarioId]);
    
    return $caminho;
}
```

## 🔐 Segurança

### Validações
- Senha atual obrigatória para alterações sensíveis
- Confirmação por email para trocar email
- Rate limiting para alteração de senha
- Validação de força de senha

### Sessões
- Listar apenas sessões do próprio usuário
- Encerrar sessões remotamente
- Identificação de dispositivo/navegador

## 💡 Boas Práticas

### Para Usuários
- Use senhas fortes e únicas
- Revise sessões ativas periodicamente
- Mantenha email atualizado
- Ative 2FA se disponível

### Para Desenvolvedores
- Nunca exponha hash de senha
- Sempre verifique identidade do usuário
- Log de alterações sensíveis
- Notificar alterações por email

## 🔗 Módulos Relacionados
- `usuarios`: Administração de todos os usuários
- `usuarios-perfis`: Perfis de permissão
