# 🧩 Componentes - Manual do Usuário

## O que são Componentes?

**Componentes** são blocos de construção reutilizáveis que você pode incluir em layouts, páginas ou outros componentes. Pense neles como peças de LEGO - você constrói uma vez e usa em qualquer lugar. Exemplos incluem cabeçalhos, rodapés, menus de navegação, botões de chamada para ação e mais.

---

## 🎯 Primeiros Passos

### Acessando Componentes
1. No Dashboard, encontre o card **Componentes**
2. Clique para abrir o módulo
3. Você verá todos os componentes disponíveis

---

## 🏗️ Entendendo Componentes

### Como os Componentes Funcionam
```
Crie um componente uma vez:
┌─────────────────────────────┐
│   componente site-footer    │
│   ───────────────────────   │
│   <footer>                  │
│     Entre em contato...     │
│     © 2024 Empresa          │
│   </footer>                 │
└─────────────────────────────┘

Use em qualquer lugar:
┌─────────────────────────────┐
│   Layout da Página          │
│   ...                       │
│   @[[componente#site-footer]]@
│   ...                       │
└─────────────────────────────┘
```

Quando você atualiza o componente, TODOS os lugares usando-o atualizam automaticamente!

---

## 📋 Lista de Componentes

### O que Você Verá
- **Nome** - Identificador do componente
- **Descrição** - Para que serve
- **Última modificação** - Quando foi alterado
- **Ações** - Editar, duplicar, excluir

---

## ➕ Criando um Novo Componente

### Passo a Passo
1. Clique em **"Adicionar Componente"**
2. Preencha os detalhes:
   - **Nome** - Nome descritivo (ex: "Botão de Chamada para Ação")
   - **ID** - Identificador único (gerado automaticamente)
3. Digite o HTML no editor de código
4. Adicione CSS se necessário
5. Clique em **"Salvar"**

### Exemplo de Componente
```html
<!-- Componente de Botão de Chamada para Ação -->
<div class="cta-container">
    <h3>Pronto para começar?</h3>
    <p>Junte-se a milhares de clientes satisfeitos</p>
    <a href="/cadastro" class="cta-button">
        Cadastre-se Agora
    </a>
</div>
```

---

## 🔧 Usando Componentes

### Incluindo em um Layout ou Página
```html
<!-- Inclua um componente pelo seu ID -->
@[[componente#component-id]]@

<!-- Exemplos -->
@[[componente#site-header]]@
@[[componente#newsletter-signup]]@
@[[componente#testimonials]]@
```

### Componentes em Componentes
Sim! Componentes podem incluir outros componentes:
```html
<!-- No componente site-footer -->
<footer>
    @[[componente#footer-links]]@
    @[[componente#social-icons]]@
    @[[componente#copyright]]@
</footer>
```

---

## 🔄 Conteúdo Dinâmico em Componentes

### Usando Variáveis
Torne componentes dinâmicos com variáveis:
```html
<div class="company-info">
    <h2>@[[variavel#nome-empresa]]@</h2>
    <p>@[[variavel#endereco-empresa]]@</p>
    <p>Telefone: @[[variavel#telefone-empresa]]@</p>
</div>
```

### Variáveis Disponíveis
| Sintaxe | Descrição |
|---------|-----------|
| `@[[variavel#nome]]@` | Variáveis do site |
| `@[[usuario#nome]]@` | Nome do usuário logado |
| `@[[pagina#titulo]]@` | Título da página atual |
| `@[[sistema#ano-atual]]@` | Ano atual |

---

## ✏️ Editando Componentes

### O Editor de Código
- **Aba HTML** - Marcação do componente
- **Aba CSS** - Estilos específicos do componente
- Destaque de sintaxe
- Preview ao vivo (se disponível)

### Dicas
1. Mantenha componentes focados em um propósito
2. Use nomes de classe significativos
3. Torne componentes auto-contidos
4. Documente com comentários HTML

---

## 🎨 Estilizando Componentes

### Aba CSS
Adicione estilos específicos do componente:
```css
.cta-container {
    text-align: center;
    padding: 2rem;
    background: #f5f5f5;
}

.cta-button {
    display: inline-block;
    padding: 1rem 2rem;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.cta-button:hover {
    background: #0056b3;
}
```

---

## 📦 Componentes Comuns

### Componente de Cabeçalho
```html
<header class="site-header">
    <div class="logo">
        <a href="/">
            <img src="@[[variavel#logo-url]]@" alt="Logo">
        </a>
    </div>
    <nav class="main-nav">
        <a href="/">Início</a>
        <a href="/sobre">Sobre</a>
        <a href="/contato">Contato</a>
    </nav>
</header>
```

### Componente de Rodapé
```html
<footer class="site-footer">
    <div class="footer-content">
        <p>&copy; @[[sistema#ano-atual]]@ @[[variavel#nome-empresa]]@</p>
        <p>Todos os direitos reservados</p>
    </div>
</footer>
```

---

## ❓ Perguntas Frequentes

### P: Onde posso usar componentes?
**R:** Em layouts, páginas, templates, e até dentro de outros componentes.

### P: Se eu atualizar um componente, todas as páginas atualizam?
**R:** Sim! Esse é o principal benefício - altere uma vez, atualize em todos os lugares.

### P: Posso usar JavaScript em componentes?
**R:** Sim, inclua tags `<script>` no HTML. Certifique-se de que os scripts não conflitem.

### P: Meu componente não está aparecendo
**R:** Verifique:
1. O ID está escrito corretamente? (sensível a maiúsculas)
2. O componente está salvo?
3. O status do componente está ativo?

---

## 💡 Melhores Práticas

### Organização
- **Nomeie claramente** - "newsletter-signup" não "comp1"
- **Agrupe relacionados** - Use prefixos como "footer-", "header-"
- **Documente** - Adicione comentários explicando o propósito

### Design
- **Propósito único** - Um componente, uma função
- **Auto-contido** - Inclua todo HTML/CSS necessário
- **Responsivo** - Funcione em todos os tamanhos de tela
- **Reutilizável** - Projete para múltiplos usos

### Manutenção
- **Revise regularmente** - Remova componentes não usados
- **Mantenha CSS isolado** - Use nomes de classe únicos
- **Teste alterações** - Verifique todos os lugares usando-o

---

## 🆘 Precisa de Ajuda?

- Confira o módulo **Layouts** para ver onde componentes são usados
- Confira **Variáveis** para opções de conteúdo dinâmico
- Entre em contato com seu administrador do sistema
- Visite nossa documentação em [conn2flow.com/docs](https://conn2flow.com/docs)
