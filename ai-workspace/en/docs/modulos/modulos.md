# Module: modules

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `modulos` |
| **Name** | Modules Administration |
| **Version** | `1.2.1` |
| **Category** | Core Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **modules** module is the **central registry for all system modules** in Conn2Flow. It manages module metadata, permissions, variables, and inter-module relationships. This is where administrators can view, configure, and manage all installed modules.

## 🏗️ Main Features

### 📦 **Module Management**
- **List modules**: View all installed modules
- **Add modules**: Register new modules
- **Edit modules**: Modify module settings
- **Module variables**: Manage module-specific variables

### 🔧 **Module Configuration**
- **Name and ID**: Module identification
- **Group assignment**: Organize modules into groups
- **Status control**: Enable/disable modules
- **Permission settings**: Control access to modules

### 📊 **Variable Management**
- **View variables**: List all module variables
- **Edit variables**: Modify variable values
- **Copy variables**: Duplicate variable sets

## 🗄️ Database Structure

### Main Table: `modulos`
```sql
CREATE TABLE modulos (
    id_modulos INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    id_modulos_grupos INT,               -- Group reference
    descricao TEXT,
    icone VARCHAR(100),
    ordem INT DEFAULT 0,
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_modulos_grupos) REFERENCES modulos_grupos(id_modulos_grupos)
);
```

## 📁 File Structure

```
gestor/modulos/modulos/
├── modulos.php                  # Main module controller
├── modulos.js                   # Client-side functionality
├── modulos.json                 # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── modulos/
    │       ├── modulos-adicionar/
    │       ├── modulos-editar/
    │       └── modulos-variaveis/
    └── en/
        └── ... (same structure)
```

## 🔧 Module Properties

| Property | Description |
|----------|-------------|
| `id` | Unique module identifier |
| `nome` | Display name |
| `descricao` | Module description |
| `id_modulos_grupos` | Group assignment |
| `icone` | Icon for display |
| `ordem` | Display order |
| `status` | Active/Inactive |

## 🎨 User Interface

### Module List
- Table of all registered modules
- Group filter
- Status filter
- Edit/Delete actions

### Add/Edit Module Form
- **Name**: Display name
- **ID**: Unique identifier
- **Description**: Module purpose
- **Group**: Module group selection
- **Icon**: Icon selection
- **Order**: Display order
- **Status**: Active/Inactive

### Variables Page
- List of module variables
- Variable ID and value
- Edit inline
- Filter by variable ID

## 🔧 Special Operations

### Sync Database
- Synchronize module data
- Update variable references
- Rebuild module cache

### Copy Variables
- Copy variables between languages
- Duplicate variable sets
- Bulk variable operations

## 💡 Best Practices

### Module Organization
- Use descriptive module names
- Assign modules to appropriate groups
- Maintain consistent naming conventions
- Document module purpose in description

### Variable Management
- Use clear variable IDs
- Group related variables
- Document variable purposes
- Use consistent value formats

## 🔗 Related Modules
- `modulos-grupos`: Module grouping
- `modulos-operacoes`: Module operations
- `usuarios-perfis`: Module permissions
