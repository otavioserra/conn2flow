# Module: module-groups

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `modulos-grupos` |
| **Name** | Module Groups Administration |
| **Version** | `1.0.0` |
| **Category** | Core Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **module-groups** module manages **logical groupings of modules** in Conn2Flow. Groups help organize modules in the dashboard and sidebar navigation, making it easier for users to find related functionality.

## 🏗️ Main Features

### 🗂️ **Group Management**
- **Create groups**: Define new module categories
- **Edit groups**: Modify group properties
- **Delete groups**: Remove empty groups
- **Order groups**: Set display order

### 📊 **Group Properties**
- **Name**: Display name for the group
- **ID**: Unique identifier
- **Host**: Associated host (for multi-tenant)
- **Order**: Display sequence

## 🗄️ Database Structure

### Main Table: `modulos_grupos`
```sql
CREATE TABLE modulos_grupos (
    id_modulos_grupos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    id_hosts INT,                        -- Host reference
    ordem INT DEFAULT 0,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 File Structure

```
gestor/modulos/modulos-grupos/
├── modulos-grupos.php           # Main module controller
├── modulos-grupos.js            # Client-side functionality
├── modulos-grupos.json          # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── modulos-grupos/
    │       ├── modulos-grupos-adicionar/
    │       └── modulos-grupos-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Default Groups

| Group | Description |
|-------|-------------|
| `administrativo` | Administrative modules |
| `usuarios` | User management modules |
| `conteudo` | Content management modules |
| `configuracao` | Configuration modules |
| `ecommerce` | E-commerce modules |

## 🎨 User Interface

### Group List
- Table of all groups
- Module count per group
- Edit/Delete actions
- Reorder functionality

### Add/Edit Group Form
- **Name**: Group display name
- **ID**: Unique identifier
- **Order**: Display sequence

## 💡 Use Cases

### Dashboard Organization
- Groups determine card sections in dashboard
- Each group can have different colors
- Modules within groups appear together

### Sidebar Navigation
- Groups appear as menu sections
- Collapsed/expanded state
- Custom icons per group

## 🔗 Related Modules
- `modulos`: Modules assigned to groups
- `dashboard`: Displays modules by group
