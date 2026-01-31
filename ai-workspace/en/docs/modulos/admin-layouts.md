# Module: admin-layouts

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-layouts` |
| **Name** | Layout Administration |
| **Version** | `1.0.1` |
| **Category** | Administrative Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-layouts** module manages **page layout templates** in Conn2Flow. Layouts define the overall structure of pages including headers, footers, navigation, and the main content area. Every page in the system uses a layout as its base template.

## 🏗️ Main Features

### 🎨 **Layout Management**
- **Create layouts**: Design new page structures
- **Edit layouts**: Modify HTML and CSS with code editor
- **Framework support**: Fomantic-UI and TailwindCSS
- **Version control**: Track layout changes

### 📐 **Template Structure**
- **Full HTML document**: Complete `<html>`, `<head>`, `<body>` structure
- **Page body placeholder**: `@[[pagina#corpo]]@` for page content
- **Head section**: Scripts, styles, meta tags
- **Variable integration**: Dynamic content via variables

### 🔄 **Critical Variable**
The most important variable in layouts:
```html
@[[pagina#corpo]]@
```
This placeholder is where the page's specific content gets inserted.

## 🗄️ Database Structure

### Main Table: `layouts`
```sql
CREATE TABLE layouts (
    id_layouts INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    html TEXT,                           -- Full HTML document
    css TEXT,                            -- Additional CSS
    framework_css VARCHAR(50),           -- fomantic-ui or tailwindcss
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/admin-layouts/
├── admin-layouts.php            # Main module controller
├── admin-layouts.js             # Client-side functionality
├── admin-layouts.json           # Module configuration
└── resources/
    ├── pt-br/
    │   ├── components/
    │   │   └── modal-layout/
    │   └── pages/
    │       ├── admin-layouts/
    │       ├── admin-layouts-adicionar/
    │       └── admin-layouts-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Layout Structure Example

### Basic Layout Template
```html
<!DOCTYPE html>
<html lang="@[[pagina#idioma]]@">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@[[pagina#titulo]]@</title>
    @[[pagina#head]]@
</head>
<body>
    <!-- Header Component -->
    @[[componente#site-header]]@
    
    <!-- Main Content Area -->
    <main class="container">
        @[[pagina#corpo]]@
    </main>
    
    <!-- Footer Component -->
    @[[componente#site-footer]]@
    
    @[[pagina#scripts]]@
</body>
</html>
```

## 🎨 User Interface

### Layout List
- Table view with layout names
- Last modification date
- Associated pages count
- Quick edit/delete actions

### Edit Form
- **Name**: Display name for the layout
- **ID**: Unique identifier
- **HTML**: Full document code editor
- **CSS**: Additional stylesheet
- **Framework**: CSS framework selection

## 🔧 Built-in Layouts

### `layout-administrativo-do-gestor`
The main administrative layout used by all backend modules. Includes:
- Admin navigation sidebar
- Top header with user info
- Main content area
- Toast notification system

### `layout-pagina-sem-permissao`
A minimal layout for pages that don't require authentication:
- Login pages
- Public error pages
- OAuth flows

## 💡 Best Practices

### Structure
- Always include `@[[pagina#corpo]]@` placeholder
- Use components for reusable sections
- Include proper meta tags and viewport
- Add `@[[pagina#head]]@` for page-specific head content

### Performance
- Minimize inline styles
- Use CSS file section for styles
- Defer non-critical scripts
- Optimize for mobile first

## 🔗 Related Modules
- `admin-componentes`: Reusable components in layouts
- `admin-paginas`: Pages that use layouts
- `admin-templates`: Content templates
