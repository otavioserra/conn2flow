# 🔐 Operações de Módulos - Manual do Usuário

## O que são Operações de Módulos?

**Operações de Módulos** definem o que os usuários podem FAZER dentro de cada módulo. Operações são as permissões individuais como Ver, Criar, Editar, Excluir que controlam o acesso em nível granular.

---

## 🎯 Primeiros Passos

### Acessando Operações de Módulos
1. No Dashboard, encontre o card **Operações de Módulos**
2. Clique para abrir o módulo
3. Você verá todas as operações definidas

> 🔒 Esta é uma área de administrador. Você precisa de permissões de admin.

---

## 📋 Lista de Operações

### O que Você Verá
Para cada operação:
- **Nome** - Nome de exibição da operação
- **ID** - Identificador único
- **Módulo** - Módulo associado
- **Tipo** - Tipo de operação
- **Ações** - Editar, excluir

### Operações Comuns
| Operação | Significado |
|----------|-------------|
| **view** | Pode ver o módulo |
| **create** | Pode adicionar novos itens |
| **edit** | Pode modificar itens existentes |
| **delete** | Pode remover itens |
| **export** | Pode exportar dados |
| **admin** | Acesso administrativo completo |

---

## 🔧 Como as Operações Funcionam

### Cadeia de Permissões
```
Perfil de Usuário → Tem Operações → Determina Acesso

Exemplo:
├── Perfil Admin
│   ├── view ✓
│   ├── create ✓
│   ├── edit ✓
│   └── delete ✓
│
└── Perfil Editor
    ├── view ✓
    ├── create ✓
    ├── edit ✓
    └── delete ✗
```

---

## ➕ Criando Operações

### Como Criar
1. Clique em **"Adicionar Operação"**
2. Preencha:
   - **Nome** - Nome descritivo
   - **ID** - Identificador único
   - **Módulo** - Selecione o módulo
   - **Descrição** - O que permite
3. Clique em **"Salvar"**

### Convenção de Nomenclatura
Use nomenclatura consistente:
- `modulo-view`
- `modulo-create`
- `modulo-edit`
- `modulo-delete`

---

## 🔗 Vinculando a Perfis

### Em Perfis de Usuário
1. Vá para **Perfis de Usuário**
2. Edite um perfil
3. Marque/desmarque operações
4. Salve

### Testando Permissões
1. Faça login como usuário com aquele perfil
2. Tente acessar o módulo
3. Verifique se as operações corretas estão disponíveis

---

## ❓ Perguntas Frequentes

### P: Usuário pode ver mas não editar
**R:** Ele tem operação `view` mas não `edit`.

### P: Novo módulo não aparece para usuários
**R:** Verifique se os perfis dos usuários têm a operação `view` para aquele módulo.

### P: Como restrinjo exclusão?
**R:** Remova a operação `delete` dos perfis que não devem excluir.

---

## 💡 Melhores Práticas

1. **Princípio do menor privilégio** - Dê apenas permissões necessárias
2. **Nomenclatura padrão** - Use nomes de operação consistentes
3. **Documente** - Descreva o que cada operação permite
4. **Teste** - Verifique se permissões funcionam como esperado
5. **Revise regularmente** - Atualize conforme funções mudam

---

## 🆘 Precisa de Ajuda?

- Confira **Perfis de Usuário** para gerenciamento de perfis
- Confira **Módulos** para configurações de módulos
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
