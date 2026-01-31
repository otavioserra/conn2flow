# Module: admin-components

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-componentes` |
| **Name** | Component Administration |
| **Version** | `1.0.0` |
| **Category** | Administrative Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-components** module manages **reusable UI components** in Conn2Flow. Components are modular pieces of HTML/CSS that can be included in pages and layouts using the variable syntax `@[[componente#component-id]]@`. This promotes code reuse and maintainability.

## 🏗️ Main Features

### 🧩 **Component Management**
- **Create components**: Build reusable HTML/CSS blocks
- **Edit components**: Modify content with syntax highlighting
- **Version control**: Track component changes
- **Framework support**: Fomantic-UI and TailwindCSS

### 📝 **Code Editor**
- **HTML editing**: Body content with syntax highlighting
- **CSS editing**: Component-specific styles
- **Preview**: Real-time preview of changes
- **Variable support**: Use dynamic variables in components

### 🔄 **Integration**
- **Variable syntax**: Include via `@[[componente#id]]@`
- **Layouts**: Embed components in page layouts
- **Pages**: Use components within page content
- **Nested components**: Components can include other components

## 🗄️ Database Structure

### Main Table: `componentes`
```sql
CREATE TABLE componentes (
    id_componentes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    corpo TEXT,                          -- HTML content
    css TEXT,                            -- CSS styles
    framework_css VARCHAR(50),           -- fomantic-ui or tailwindcss
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-componentes/
├── admin-componentes.php        # Main module controller
├── admin-componentes.js         # Client-side functionality
├── admin-componentes.json       # Module configuration
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-componente/
    │   └── pages/
    │       ├── admin-componentes/
    │       ├── admin-componentes-adicionar/
    │       └── admin-componentes-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Component Syntax

### Including a Component
```html
<!-- In a page or layout -->
<div class="container">
    @[[componente#header-navigation]]@
    <main>
        <!-- Page content -->
    </main>
    @[[componente#footer-links]]@
</div>
```

### Component with Variables
```html
<!-- Component: user-greeting -->
<div class="greeting">
    <h2>Welcome, @[[usuario#nome]]@!</h2>
    <p>@[[variavel#welcome-message]]@</p>
</div>
```

## 🎨 User Interface

### Component List
- Card grid or table view
- Component name and ID
- Last modification date
- Quick edit/delete actions

### Edit Form
- **Name**: Display name for the component
- **ID**: Unique identifier (auto-generated from name)
- **HTML Body**: Code editor with syntax highlighting
- **CSS**: Component-specific styles
- **Framework**: CSS framework selection

## 🔧 Best Practices

### Naming Convention
- Use descriptive, lowercase IDs
- Prefix by function: `nav-`, `form-`, `card-`
- Example: `nav-main-menu`, `card-product`, `form-contact`

### Code Organization
- Keep components focused (single responsibility)
- Document with HTML comments
- Use consistent indentation
- Avoid inline styles (use CSS section)

## 🔗 Related Modules
- `admin-layouts`: Layout templates that use components
- `admin-paginas`: Pages that include components
- `admin-templates`: Content templates using components
