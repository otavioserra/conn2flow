# Module: variables

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `variables` |
| **Name** | Variables Administration |
| **Version** | `1.0.0` |
| **Category** | Core Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **variables** module provides a **centralized view of all system variables** in Conn2Flow. Variables are key-value pairs used throughout the system for internationalization (i18n), configuration, and dynamic content. This module allows administrators to view and edit variables across all modules.

## 🏗️ Main Features

### 📝 **Variable Management**
- **View variables**: See all variables by module
- **Edit variables**: Modify variable values
- **Filter**: Search and filter by module
- **Bulk view**: See all variables at once

### 🌐 **Variable Types**
- **String variables**: Text content
- **JSON variables**: Complex data structures
- **HTML variables**: Formatted content

## 🗄️ Database Structure

### Main Table: `variaveis`
```sql
CREATE TABLE variaveis (
    id_variaveis INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) NOT NULL,
    id_modulos INT NOT NULL,             -- Module reference
    valor TEXT,                          -- Variable value
    tipo VARCHAR(50) DEFAULT 'string',   -- Type (string, json, html)
    idioma VARCHAR(10) DEFAULT 'pt-br',  -- Language code
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_modulos) REFERENCES modulos(id_modulos),
    UNIQUE KEY unique_var (id, id_modulos, idioma)
);
```

## 📁 File Structure

```
gestor/modulos/variables/
├── variables.php                # Main module controller
├── variables.js                 # Client-side functionality
├── variables.json               # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       └── variables/
    └── en/
        └── pages/
            └── variables/
```

## 🔧 Variable Syntax

### Using Variables in Templates
```html
<!-- Module variable -->
@[[modulo-id#variable-id]]@

<!-- System variable -->
@[[variavel#variable-id]]@

<!-- Page variable -->
@[[pagina#titulo]]@

<!-- User variable -->
@[[usuario#nome]]@
```

### Example Usage
```html
<h1>@[[dashboard#welcome-title]]@</h1>
<p>@[[dashboard#welcome-message]]@</p>
<button>@[[interface#btn-save]]@</button>
```

## 🎨 User Interface

### Variables Page
- Module filter dropdown
- Table of variables
  - Variable ID
  - Current value
  - Edit button
- Inline editing support
- Search/filter functionality

### Variable Editor
- Variable ID (read-only)
- Value field (text area)
- Save button
- Cancel button

## 💡 Variable Categories

### Interface Variables
Common UI text across modules:
- Button labels
- Form placeholders
- Error messages
- Success messages

### Module Variables
Module-specific text:
- Page titles
- Descriptions
- Field labels
- Tooltips

### System Variables
System-wide configuration:
- Site name
- Contact email
- Default language

## 🌐 Internationalization

### Multi-language Support
Variables are stored per language:
```
Variable: welcome-title
├── pt-br: "Bem-vindo ao Sistema"
└── en: "Welcome to the System"
```

### Language Selection
- System detects user language
- Falls back to default language
- Variables automatically selected

## 🔗 Related Modules
- `modulos`: Module-variable association
- `interface`: Common interface variables
- All modules use their own variables
