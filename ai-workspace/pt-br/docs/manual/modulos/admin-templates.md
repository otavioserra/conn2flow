# 📋 Administração de Templates - Manual do Usuário

## O que é Administração de Templates?

O módulo **Admin Templates** oferece gerenciamento avançado de templates de conteúdo. Enquanto Templates é para usar templates, Admin Templates lida com criação, versões e configurações técnicas.

---

## 🎯 Primeiros Passos

### Acessando Admin Templates
1. No Dashboard, encontre o card **Admin Templates**
2. Clique para abrir o módulo
3. Você verá todos os templates do sistema

> 🔒 Esta é uma área de administrador. Você precisa de permissões de admin.

---

## 📋 Lista de Templates

### O que Você Verá
Para cada template:
- **ID** - Identificador único
- **Nome** - Nome de exibição
- **Categoria** - Tipo de template
- **Versão** - Versão do template
- **Status** - Ativo/Inativo
- **Ações** - Editar, duplicar, excluir

---

## 🔧 Tipos de Templates

| Tipo | Propósito |
|------|-----------|
| **Página** | Templates de página completa |
| **Seção** | Blocos de conteúdo |
| **Email** | Templates de email |
| **Componente** | Elementos reutilizáveis |

---

## ➕ Criando Templates

### Como Criar
1. Clique em **"Adicionar Template"**
2. Preencha:
   - **Nome** - Nome descritivo
   - **ID** - Identificador único
   - **Categoria** - Tipo de template
   - **Conteúdo** - Estrutura HTML
   - **Estilos** - CSS (opcional)
3. Clique em **"Salvar"**

### Estrutura de Template
```html
<!-- Bom exemplo de template -->
<section class="hero-section">
    <h1>{{titulo}}</h1>
    <p>{{descricao}}</p>
    <a href="{{cta_link}}" class="btn">{{cta_texto}}</a>
</section>
```

---

## 🔤 Placeholders

### Usando Placeholders
Marque áreas editáveis:
- `{{nome_placeholder}}` - Conteúdo de texto
- `<!-- editavel -->` - Blocos editáveis

### Exemplo
```html
<div class="feature-card">
    <h3>{{titulo_feature}}</h3>
    <p>{{descricao_feature}}</p>
</div>
```

---

## ⚙️ Gerenciamento de Versões

### Versionamento
- Templates podem ter versões
- Reverter para versões anteriores
- Rastrear mudanças ao longo do tempo

### Melhor Prática
- Atualize a versão ao fazer mudanças significativas
- Documente o que mudou em cada versão

---

## ❓ Perguntas Frequentes

### P: Diferença do Templates normal?
**R:** Admin Templates é para criar/gerenciar; Templates é para usar.

### P: Posso importar templates?
**R:** Verifique funcionalidade de importação ou adicione manualmente via este módulo.

### P: Template não aparece?
**R:** Verifique:
1. Status está Ativo
2. Categoria correta selecionada
3. Sem erros de sintaxe

---

## 💡 Melhores Práticas

1. **Use placeholders claros** - Nomes descritivos
2. **Inclua preview** - Ajude usuários a visualizar
3. **Documente uso** - Adicione descrição
4. **Teste completamente** - Tente com diferentes conteúdos
5. **Mantenha organizado** - Use categorias

---

## 🆘 Precisa de Ajuda?

- Confira **Templates** para usar templates
- Confira **Layouts** para estruturas de página
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
