# 🎨 Administração de Layouts - Manual do Usuário

## O que é Administração de Layouts?

O módulo **Admin Layouts** oferece gerenciamento avançado de layouts de página. Enquanto o módulo Layouts é para criar templates, Admin Layouts lida com configurações técnicas, layouts do sistema e configuração.

---

## 🎯 Primeiros Passos

### Acessando Admin Layouts
1. No Dashboard, encontre o card **Admin Layouts**
2. Clique para abrir o módulo
3. Você verá todos os layouts do sistema

> 🔒 Esta é uma área de administrador. Você precisa de permissões de admin.

---

## 📋 Lista de Layouts

### O que Você Verá
Para cada layout:
- **ID** - Identificador único
- **Nome** - Nome de exibição
- **Tipo** - Sistema ou personalizado
- **Framework** - Framework CSS usado
- **Páginas** - Número de páginas usando
- **Ações** - Editar, excluir

---

## 🔧 Gerenciamento de Layouts

### Layouts do Sistema
- Layouts core para páginas do sistema
- Não podem ser excluídos
- Podem ser personalizados

### Layouts Personalizados
- Criados por usuários
- Controle total
- Podem ser excluídos (se nenhuma página usar)

---

## ⚙️ Configurações Técnicas

### Configuração do Layout
- **ID** - Deve ser único
- **Template HTML** - Estrutura completa da página
- **Framework CSS** - Fomantic-UI, TailwindCSS, etc.
- **Conteúdo Head** - Meta tags, links
- **Conteúdo Scripts** - Arquivos JavaScript

### Variável Obrigatória
Todo layout DEVE incluir:
```html
@[[pagina#corpo]]@
```
É aqui que o conteúdo da página aparece.

---

## ❓ Perguntas Frequentes

### P: Posso excluir um layout usado por páginas?
**R:** Não, reatribua as páginas primeiro.

### P: Qual a diferença do Layouts normal?
**R:** Admin Layouts é para gerenciamento técnico e layouts do sistema.

### P: Como mudo o layout de uma página?
**R:** Use Admin Páginas ou o editor de página.

---

## 💡 Melhores Práticas

1. **Nunca remova @[[pagina#corpo]]@** - Páginas não exibirão
2. **Teste em todos dispositivos** - Garanta design responsivo
3. **Backup antes de editar** - Especialmente layouts do sistema
4. **Use nomenclatura consistente** - Fácil identificação

---

## 🆘 Precisa de Ajuda?

- Confira **Layouts** para criar layouts
- Confira **Componentes** para elementos reutilizáveis
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
