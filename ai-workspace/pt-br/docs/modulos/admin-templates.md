# Módulo: admin-templates

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-templates` |
| **Nome** | Administração de Templates |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-templates** gerencia **templates de conteúdo reutilizáveis** no Conn2Flow. Templates são estruturas de conteúdo pré-projetadas que podem ser duplicadas para criar novas páginas rapidamente. Diferente de layouts (estrutura de página) ou componentes (fragmentos de UI), templates fornecem um ponto de partida completo para a criação de conteúdo.

## 🏗️ Funcionalidades Principais

### 📄 **Gerenciamento de Templates**
- **Criar templates**: Projetar estruturas de conteúdo reutilizáveis
- **Editar templates**: Modificar HTML com destaque de sintaxe
- **Categorização**: Organizar templates por tipo/propósito
- **Controle de versão**: Rastrear mudanças no template

### 🎨 **Recursos de Design**
- **Suporte a frameworks**: Fomantic-UI e TailwindCSS
- **Integração de variáveis**: Placeholders dinâmicos de conteúdo
- **Preview**: Visualização antes de usar
- **Inclusão de componentes**: Uso de componentes existentes

### 🔄 **Caso de Uso**
Templates são ideais para:
- Páginas de marketing com seções consistentes
- Estruturas de artigos de blog
- Páginas de produto
- Landing pages
- Templates de newsletter

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `templates`
```sql
CREATE TABLE templates (
    id_templates INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    corpo TEXT,                          -- Conteúdo HTML
    css TEXT,                            -- Estilos CSS
    framework_css VARCHAR(50),           -- fomantic-ui ou tailwindcss
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Estrutura de Arquivos

```
gestor/modulos/admin-templates/
├── admin-templates.php          # Controlador principal
├── admin-templates.js           # Funcionalidade client-side
├── admin-templates.json         # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-template/
    │   └── pages/
    │       ├── admin-templates/
    │       ├── admin-templates-adicionar/
    │       └── admin-templates-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Exemplo de Template

### Template de Landing Page
```html
<!-- Template: landing-page-basic -->
<section class="hero">
    <h1>@[[variavel#hero-title]]@</h1>
    <p class="subtitle">@[[variavel#hero-subtitle]]@</p>
    <a href="#cta" class="ui primary button">Saiba Mais</a>
</section>

<section class="features">
    @[[componente#feature-grid]]@
</section>

<section class="testimonials">
    @[[componente#testimonial-slider]]@
</section>

<section id="cta" class="call-to-action">
    @[[componente#cta-form]]@
</section>
```

### Template de Artigo de Blog
```html
<!-- Template: blog-post -->
<article class="blog-post">
    <header>
        <h1>@[[pagina#titulo]]@</h1>
        <div class="meta">
            <span class="author">@[[usuario#nome]]@</span>
            <span class="date">@[[pagina#data-publicacao]]@</span>
        </div>
    </header>
    
    <div class="featured-image">
        <!-- Área para imagem destaque -->
    </div>
    
    <div class="content">
        <!-- Área de conteúdo do artigo -->
    </div>
    
    <footer>
        @[[componente#share-buttons]]@
        @[[componente#author-bio]]@
    </footer>
</article>
```

## 🎨 Interface do Usuário

### Lista de Templates
- Grade de cards mostrando preview do template
- Nome e categoria do template
- Data da última modificação
- Ações rápidas (usar, editar, duplicar, excluir)

### Formulário de Edição
- **Nome**: Nome de exibição do template
- **ID**: Identificador único
- **Corpo HTML**: Editor de código com destaque de sintaxe
- **CSS**: Estilos específicos do template
- **Framework**: Seleção de framework CSS

## 💡 Boas Práticas

### Design
- Crie templates com propósito claro
- Use variáveis para conteúdo dinâmico
- Mantenha estrutura consistente
- Documente áreas editáveis

### Organização
- Nomeie templates descritivamente
- Agrupe por tipo de conteúdo
- Versione templates importantes
- Revise periodicamente

## 🔗 Módulos Relacionados
- `admin-layouts`: Templates usam layouts
- `admin-componentes`: Templates incluem componentes
- `publisher-paginas`: Páginas criadas a partir de templates
