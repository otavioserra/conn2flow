# Module: admin-templates

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-templates` |
| **Name** | Template Administration |
| **Version** | `1.0.1` |
| **Category** | Administrative Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `html-editor` |

## 🎯 Purpose

The **admin-templates** module manages **content templates** in Conn2Flow. Templates are pre-designed HTML structures that can be used when creating new pages or content. They serve as starting points for common page types (landing pages, blog posts, product pages, etc.).

## 🏗️ Main Features

### 📄 **Template Management**
- **Create templates**: Design reusable content structures
- **Edit templates**: Modify HTML with syntax highlighting
- **Clone templates**: Duplicate existing templates
- **Framework support**: Fomantic-UI and TailwindCSS

### 🎨 **Template Types**
- **Page templates**: Full page content structures
- **Section templates**: Reusable page sections
- **Publisher templates**: For dynamic content publishing
- **Target association**: Link templates to specific modules

### 🔄 **Integration**
- **Page creation**: Select template when adding new pages
- **Publisher**: Use templates for dynamic content
- **AI generation**: Templates can be generated via AI

## 🗄️ Database Structure

### Main Table: `templates`
```sql
CREATE TABLE templates (
    id_templates INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    alvo VARCHAR(100),                   -- Target module (paginas, publisher, etc.)
    corpo TEXT,                          -- HTML content
    css TEXT,                            -- Template CSS
    framework_css VARCHAR(50),           -- fomantic-ui or tailwindcss
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-templates/
├── admin-templates.php          # Main module controller
├── admin-templates.js           # Client-side functionality
├── admin-templates.json         # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── admin-templates/
    │       ├── admin-templates-adicionar/
    │       ├── admin-templates-editar/
    │       └── admin-templates-clonar/
    └── en/
        └── ... (same structure)
```

## 🔧 Template Structure Example

### Landing Page Template
```html
<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 class="title">{{titulo}}</h1>
        <p class="subtitle">{{subtitulo}}</p>
        <a href="{{cta_link}}" class="btn primary">{{cta_texto}}</a>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <h2>{{features_titulo}}</h2>
        <div class="grid">
            {{#each features}}
            <div class="feature-card">
                <i class="{{icone}}"></i>
                <h3>{{titulo}}</h3>
                <p>{{descricao}}</p>
            </div>
            {{/each}}
        </div>
    </div>
</section>
```

## 🎨 User Interface

### Template List
- Card or table view
- Template name and target
- Framework indicator
- Clone/Edit/Delete actions

### Edit Form
- **Name**: Template display name
- **ID**: Unique identifier
- **Target**: Associated module
- **HTML Body**: Content editor
- **CSS**: Template styles
- **Framework**: CSS framework

## 🔧 Available Targets

| Target | Description |
|--------|-------------|
| `paginas` | Standard pages |
| `publisher` | Dynamic publisher content |
| `componentes` | Component templates |

## 💡 Best Practices

### Design
- Use semantic HTML structure
- Include placeholder variables
- Document required fields
- Make templates responsive

### Organization
- Name templates descriptively
- Group by purpose (landing, blog, product)
- Keep templates focused
- Document variable requirements

## 🔗 Related Modules
- `admin-paginas`: Pages using templates
- `publisher`: Content using templates
- `admin-ia`: AI-generated templates
