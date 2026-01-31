# 👥 Gerenciamento de Usuários - Manual do Usuário

## O que é o Gerenciamento de Usuários?

O módulo **Usuários** permite que administradores gerenciem todas as contas de usuário no sistema. Você pode criar novos usuários, editar existentes, atribuir perfis e controlar o acesso ao sistema.

---

## 🎯 Primeiros Passos

### Acessando o Gerenciamento de Usuários
1. No Dashboard, encontre o card **Usuários**
2. Clique para abrir o módulo
3. Você verá uma lista de todos os usuários do sistema

---

## 📋 Lista de Usuários

### Entendendo a Lista
A lista de usuários mostra:
- **Nome** - Nome completo do usuário
- **Email** - Endereço de email para login
- **Perfil** - Nível de permissão atribuído
- **Status** - Ativo, Inativo ou Bloqueado
- **Último Acesso** - Quando fez login pela última vez
- **Ações** - Botões de editar ou excluir

### Filtrando Usuários
- Use a **barra de busca** para encontrar usuários por nome ou email
- Filtre por **status** (Ativo/Inativo)
- Filtre por **perfil** (Admin/Editor/Usuário)

---

## ➕ Adicionando um Novo Usuário

### Passo a Passo
1. Clique no botão **"Adicionar Usuário"** (geralmente no canto superior direito)
2. Preencha os campos obrigatórios:
   - **Nome** - Nome completo
   - **Email** - Deve ser único (usado para login)
   - **Senha** - Mínimo 8 caracteres
   - **Confirmar Senha** - Digite a mesma senha novamente
   - **Perfil** - Selecione o nível de permissão
3. Clique em **"Salvar"**

### Requisitos de Senha
- Mínimo 8 caracteres
- Recomendamos incluir:
  - Letras maiúsculas e minúsculas
  - Números
  - Caracteres especiais (!@#$%)

---

## ✏️ Editando um Usuário

### O que Você Pode Alterar
1. Encontre o usuário na lista
2. Clique no botão **Editar** (ícone de lápis)
3. Modifique qualquer campo:
   - Nome
   - Email
   - Perfil
   - Status
4. Clique em **"Salvar"**

### Alterando uma Senha
- Deixe os campos de senha **vazios** para manter a senha atual
- Preencha ambos os campos de senha para definir uma nova senha

---

## 🔐 Perfis de Usuário

Perfis determinam o que um usuário pode acessar:

| Perfil | Nível de Acesso |
|--------|-----------------|
| **Super Admin** | Acesso total a tudo |
| **Admin** | Maioria dos recursos, exceto configurações críticas |
| **Editor** | Apenas gerenciamento de conteúdo |
| **Usuário** | Acesso básico, apenas visualização |

> 💡 **Dica:** Atribua as permissões mínimas necessárias para a função de cada usuário.

---

## 🚫 Desativar vs Excluir

### Desativando um Usuário
- O usuário não pode fazer login
- Todos os dados são preservados
- Pode ser reativado depois
- **Recomendado** para a maioria dos casos

### Excluindo um Usuário
- Remove permanentemente o usuário
- Não pode ser desfeito
- Use apenas quando necessário

### Como Desativar
1. Edite o usuário
2. Altere o **Status** para "Inativo"
3. Salve

---

## 👤 Status do Usuário

| Status | Significado |
|--------|-------------|
| **Ativo** | Usuário pode fazer login normalmente |
| **Inativo** | Usuário não pode fazer login, pode ser reativado |
| **Bloqueado** | Usuário está bloqueado (geralmente por razões de segurança) |

---

## ❓ Perguntas Frequentes

### P: Um usuário esqueceu a senha
**R:** Você tem duas opções:
1. Editar o usuário e definir uma nova senha
2. Enviar um link de redefinição de senha (se disponível)

### P: Um usuário não consegue acessar certos módulos
**R:** Verifique o perfil do usuário. Ele pode precisar de um perfil com mais permissões, ou permissões específicas do módulo precisam ser habilitadas.

### P: Posso ter múltiplos admins?
**R:** Sim! Você pode atribuir o perfil Admin ou Super Admin a múltiplos usuários.

### P: Como vejo o que um usuário fez?
**R:** Verifique os logs do sistema (se disponível) ou revise a data do último acesso.

---

## 🔒 Melhores Práticas de Segurança

1. **Revisões regulares** - Revise periodicamente as contas de usuário
2. **Remova contas inativas** - Desative usuários que não precisam mais de acesso
3. **Senhas fortes** - Aplique requisitos de senha
4. **Permissões mínimas** - Dê aos usuários apenas o que precisam
5. **Monitore acessos** - Verifique as datas de último login

---

## 🆘 Precisa de Ajuda?

- Confira o módulo **Perfis de Usuários** para detalhes de permissões
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
