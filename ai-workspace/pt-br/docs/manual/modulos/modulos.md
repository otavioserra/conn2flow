# 📦 Gerenciamento de Módulos - Manual do Usuário

## O que são Módulos?

**Módulos** são os blocos de construção do Conn2Flow. Cada módulo lida com uma funcionalidade específica - como gerenciar páginas, usuários ou contatos. Este módulo permite que administradores controlem quais módulos estão disponíveis e como eles são organizados.

---

## 🎯 Primeiros Passos

### Acessando Módulos
1. No Dashboard, encontre o card **Módulos**
2. Clique para abrir o módulo
3. Você verá todos os módulos instalados

> 🔒 Esta é uma área de administrador. Você precisa de permissões administrativas.

---

## 📋 Lista de Módulos

### O que Você Verá
Para cada módulo:
- **Nome** - Identificador do módulo
- **Grupo** - Categoria à qual pertence
- **Ordem** - Posição no menu
- **Status** - Ativo/Inativo
- **Ações** - Configurar, ativar/desativar

### Status de Módulos
| Status | Significado |
|--------|-------------|
| ✅ Ativo | Módulo está disponível para usuários |
| ⏸️ Inativo | Módulo está escondido/desabilitado |

---

## 🔧 Gerenciando Módulos

### Ativar/Desativar
1. Encontre o módulo na lista
2. Clique no toggle de status
3. Confirme a alteração

### Ordenando Módulos
1. Arraste módulos para reordenar
2. Ou edite o número de ordem
3. Alterações afetam a ordem do menu

### Movendo Entre Grupos
1. Edite o módulo
2. Altere o campo "Grupo"
3. Salve - o módulo muda de categoria

---

## 📂 Grupos de Módulos

Os módulos são organizados em grupos lógicos:

| Grupo | Propósito |
|-------|-----------|
| **Administração** | Configurações e sistema |
| **Publicador** | Gestão de conteúdo |
| **Usuários** | Gestão de usuários |
| **Módulos** | Gestão de módulos |

### Gerenciando Grupos
- Clique em **"Grupos de Módulos"** para ver/editar grupos
- Crie novos grupos para organizar módulos
- Reordene grupos conforme necessário

---

## 🔒 Permissões de Módulos

### Quem Pode Ver o Quê
Cada módulo pode ter permissões baseadas em:
- **Perfis de usuário** - Admin, Editor, Visualizador
- **Usuários específicos** - Permissões individuais
- **Operações** - Ver, criar, editar, excluir

### Configurando Permissões
1. Edite o módulo
2. Vá para aba de permissões
3. Marque/desmarque permissões por perfil
4. Salve alterações

---

## ⚙️ Configurações de Módulos

### Configurações Comuns
- **Nome de exibição** - Como aparece no menu
- **Ícone** - Ícone mostrado no dashboard
- **URL** - Caminho de acesso
- **Ordem** - Posição no menu

### Configurações Avançadas
- **Dependências** - Módulos que este requer
- **Callbacks** - Ações em eventos
- **Parâmetros** - Opções personalizadas

---

## ➕ Módulos Personalizados

### Adicionando Novos Módulos
Administradores podem:
1. Instalar plugins que adicionam módulos
2. Desenvolver módulos personalizados
3. Clonar módulos existentes

### Anatomia de um Módulo
- Arquivos controladores
- Templates de visualização
- Migrações de banco de dados
- Assets (CSS, JS, imagens)

---

## ❓ Perguntas Frequentes

### P: Se eu desativar um módulo, perco dados?
**R:** Não! Desativar apenas esconde o módulo. Todos os dados permanecem intactos.

### P: Posso excluir módulos do sistema?
**R:** Módulos core não podem ser excluídos. Apenas desative-os se não precisar.

### P: Como adiciono um novo módulo?
**R:** Instale via plugin ou desenvolva um módulo customizado.

### P: Por que não vejo certos módulos?
**R:** Seu perfil de usuário pode não ter permissão para vê-los.

---

## 💡 Melhores Práticas

### Organização
1. **Agrupe logicamente** - Mantenha módulos relacionados juntos
2. **Ordem significativa** - Mais usados primeiro
3. **Nomes claros** - Usuários devem entender o propósito

### Segurança
1. **Princípio do menor privilégio** - Dê apenas permissões necessárias
2. **Revise regularmente** - Atualize conforme funções mudam
3. **Documente** - Registre quem tem acesso a quê

### Manutenção
1. **Desative não usados** - Mantenha o dashboard limpo
2. **Mantenha atualizado** - Aplique atualizações de módulos
3. **Monitore uso** - Veja o que as pessoas realmente usam

---

## 🆘 Precisa de Ajuda?

- Confira **Grupos de Módulos** para organização
- Confira **Operações de Módulos** para controle detalhado
- Confira **Perfis de Usuário** para gerenciar permissões
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
