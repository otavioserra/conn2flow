# 👥 Perfis de Usuários - Manual do Usuário

## O que são Perfis de Usuários?

**Perfis de Usuários** são modelos de permissão que definem o que os usuários podem fazer no sistema. Em vez de configurar permissões para cada usuário individualmente, você cria perfis e os atribui aos usuários.

---

## 🎯 Primeiros Passos

### Acessando Perfis de Usuários
1. No Dashboard, encontre o card **Perfis de Usuários**
2. Clique para abrir o módulo
3. Você verá todos os perfis existentes

---

## 📋 Entendendo os Perfis

### Como os Perfis Funcionam
```
Perfil (ex: "Editor")
    └── Tem permissões para:
        ├── Módulo Páginas (ver, adicionar, editar)
        ├── Módulo Mídia (ver, upload)
        └── Módulo Publicador (ver, adicionar, editar, excluir)
            
Usuário "João" 
    └── Perfil Atribuído: "Editor"
        └── João pode fazer tudo que o perfil Editor permite
```

---

## 📦 Perfis Padrão

O Conn2Flow vem com estes perfis integrados:

| Perfil | Descrição | Uso Típico |
|--------|-----------|------------|
| **Super Admin** | Acesso total a todos os módulos e operações | Proprietário do sistema, administrador TI |
| **Admin** | Maioria dos recursos, algumas restrições | Gerentes de departamento |
| **Editor** | Criação e gerenciamento de conteúdo | Escritores, gerentes de conteúdo |
| **Usuário** | Acesso básico, principalmente visualização | Equipe geral, visualizadores |

---

## ➕ Criando um Novo Perfil

### Passo a Passo
1. Clique em **"Adicionar Perfil"**
2. Preencha as informações básicas:
   - **Nome** - Nome descritivo (ex: "Equipe de Marketing")
   - **Descrição** - Para que serve este perfil
   - **Nível** - Número de hierarquia (maior = mais autoridade)
3. Configure as permissões usando a **Matriz de Permissões**
4. Clique em **"Salvar"**

---

## 🎛️ A Matriz de Permissões

A matriz de permissões é uma grade mostrando:
- **Linhas** = Módulos
- **Colunas** = Operações (Ver, Adicionar, Editar, Excluir, etc.)

### Como Configurar Permissões
1. Encontre a linha do módulo
2. Marque as caixas para operações permitidas:
   - ☑️ **Ver** - Pode ver o módulo e seu conteúdo
   - ☑️ **Adicionar** - Pode criar novos itens
   - ☑️ **Editar** - Pode modificar itens existentes
   - ☑️ **Excluir** - Pode remover itens

### Seleção Rápida
- **Marcar cabeçalho da linha** - Seleciona todas as operações desse módulo
- **Marcar cabeçalho da coluna** - Seleciona essa operação para todos os módulos

---

## ✏️ Editando um Perfil

### O que Você Pode Alterar
1. Encontre o perfil na lista
2. Clique em **Editar**
3. Modifique:
   - Nome e descrição
   - Checkboxes de permissões
4. Clique em **"Salvar"**

> ⚠️ **Aviso:** Alterações afetam TODOS os usuários com este perfil imediatamente!

---

## 🔗 Herança de Perfis

Você pode criar perfis que herdam de outros perfis:

### Como Funciona
1. Crie um perfil base (ex: "Equipe - Básico")
2. Crie um perfil filho (ex: "Equipe - Avançado")
3. Defina "Equipe - Básico" como pai
4. O filho recebe todas as permissões do pai MAIS as suas próprias

### Benefícios
- Menos trabalho mantendo permissões
- Permissões base consistentes
- Fácil criar variações

---

## 📊 Níveis de Perfil

Níveis determinam a hierarquia:

| Nível | Perfil Exemplo | Pode Gerenciar |
|-------|----------------|----------------|
| 100 | Super Admin | Todos |
| 80 | Admin | Níveis abaixo de 80 |
| 50 | Editor | Níveis abaixo de 50 |
| 20 | Usuário | Apenas a si mesmo |

> 💡 **Regra:** Usuários só podem gerenciar usuários com perfis de nível inferior.

---

## ❓ Perguntas Frequentes

### P: Posso excluir um perfil com usuários atribuídos?
**R:** Não. Primeiro reatribua os usuários a outro perfil, depois exclua.

### P: O que acontece se eu alterar permissões?
**R:** Todos os usuários com esse perfil recebem as novas permissões imediatamente (no próximo carregamento de página).

### P: Um usuário pode ter múltiplos perfis?
**R:** Não. Cada usuário tem um perfil. Crie um novo perfil combinado se necessário.

### P: Como vejo quais usuários têm um perfil?
**R:** Vá em **Usuários** e filtre por perfil.

---

## 💡 Melhores Práticas

### Criando Perfis
1. **Nomeie claramente** - "Editor de Marketing" é melhor que "Perfil 3"
2. **Comece mínimo** - Adicione permissões conforme necessário
3. **Documente o propósito** - Use o campo de descrição
4. **Teste** - Crie um usuário de teste com o perfil para verificar

### Segurança
1. **Limite admins** - Nem todos precisam de acesso admin
2. **Auditorias regulares** - Revise perfis trimestralmente
3. **Remova não utilizados** - Exclua perfis que ninguém usa
4. **Separe deveres** - Tarefas diferentes = perfis diferentes

---

## 🆘 Precisa de Ajuda?

- Confira o módulo **Usuários** para atribuir perfis
- Confira **Operações de Módulos** para entender as operações disponíveis
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
