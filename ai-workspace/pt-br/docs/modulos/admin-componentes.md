# Módulo: admin-componentes

## 📋 Informações Gerais

| Campo | Valor |
|-------|-------|
| **ID do Módulo** | `admin-componentes` |
| **Nome** | Administração de Componentes |
| **Versão** | `1.0.0` |
| **Categoria** | Módulo Administrativo |
| **Complexidade** | 🟡 Média |
| **Status** | ✅ Ativo |
| **Dependências** | `interface`, `html` |

## 🎯 Propósito

O módulo **admin-componentes** gerencia **componentes de UI reutilizáveis** no Conn2Flow. Componentes são blocos modulares de HTML/CSS que podem ser incluídos em páginas e layouts usando a sintaxe de variável `@[[componente#component-id]]@`. Isso promove reutilização de código e manutenibilidade.

## 🏗️ Funcionalidades Principais

### 🧩 **Gerenciamento de Componentes**
- **Criar componentes**: Construir blocos HTML/CSS reutilizáveis
- **Editar componentes**: Modificar conteúdo com destaque de sintaxe
- **Controle de versão**: Rastrear mudanças nos componentes
- **Suporte a frameworks**: Fomantic-UI e TailwindCSS

### 📝 **Editor de Código**
- **Edição HTML**: Conteúdo do corpo com destaque de sintaxe
- **Edição CSS**: Estilos específicos do componente
- **Preview**: Visualização em tempo real das mudanças
- **Suporte a variáveis**: Usar variáveis dinâmicas nos componentes

### 🔄 **Integração**
- **Sintaxe de variável**: Incluir via `@[[componente#id]]@`
- **Layouts**: Incorporar componentes em layouts de página
- **Páginas**: Usar componentes dentro do conteúdo da página
- **Componentes aninhados**: Componentes podem incluir outros componentes

## 🗄️ Estrutura do Banco de Dados

### Tabela Principal: `componentes`
```sql
CREATE TABLE componentes (
    id_componentes INT AUTO_INCREMENT PRIMARY KEY,
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
gestor/modulos/admin-componentes/
├── admin-componentes.php        # Controlador principal
├── admin-componentes.js         # Funcionalidade client-side
├── admin-componentes.json       # Configuração do módulo
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-componente/
    │   └── pages/
    │       ├── admin-componentes/
    │       ├── admin-componentes-adicionar/
    │       └── admin-componentes-editar/
    └── en/
        └── ... (mesma estrutura)
```

## 🔧 Sintaxe de Componentes

### Incluindo um Componente
```html
<!-- Em uma página ou layout -->
<div class="container">
    @[[componente#header-navigation]]@
    <main>
        <!-- Conteúdo da página -->
    </main>
    @[[componente#footer-links]]@
</div>
```

### Componente com Variáveis
```html
<!-- Componente: user-greeting -->
<div class="greeting">
    <h2>Bem-vindo, @[[usuario#nome]]@!</h2>
    <p>@[[variavel#welcome-message]]@</p>
</div>
```

## 🎨 Interface do Usuário

### Lista de Componentes
- Grade de cards ou visualização em tabela
- Nome e ID do componente
- Data da última modificação
- Ações rápidas de editar/excluir

### Formulário de Edição
- **Nome**: Nome de exibição do componente
- **ID**: Identificador único (gerado automaticamente do nome)
- **HTML Body**: Editor de código com destaque de sintaxe
- **CSS**: Estilos específicos do componente
- **Framework**: Seleção de framework CSS

## 🔧 Boas Práticas

### Convenção de Nomenclatura
- Use IDs descritivos em minúsculas
- Prefixe por função: `nav-`, `form-`, `card-`
- Exemplo: `nav-main-menu`, `card-product`, `form-contact`

### Organização de Código
- Mantenha componentes focados (responsabilidade única)
- Documente com comentários HTML
- Use indentação consistente
- Evite estilos inline (use seção CSS)

## 🔗 Módulos Relacionados
- `admin-layouts`: Templates de layout que usam componentes
- `admin-paginas`: Páginas que incluem componentes
- `admin-templates`: Templates de conteúdo usando componentes
