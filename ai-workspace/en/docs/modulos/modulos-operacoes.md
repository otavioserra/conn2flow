# Module: module-operations

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `modulos-operacoes` |
| **Name** | Module Operations Administration |
| **Version** | `1.0.0` |
| **Category** | Core Module |
| **Complexity** | 🟢 Low |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **module-operations** module manages **granular permissions** within modules. While modules define broad functionality areas, operations define specific actions within those modules (like "add", "edit", "delete", "view"). This enables fine-grained access control.

## 🏗️ Main Features

### ⚙️ **Operation Management**
- **Create operations**: Define module-specific actions
- **Edit operations**: Modify operation properties
- **Delete operations**: Remove unused operations
- **Link to modules**: Associate operations with modules

### 🔐 **Permission Granularity**
- **CRUD operations**: Add, edit, delete, list
- **Custom operations**: Module-specific actions
- **Role integration**: Used by user profiles for permissions

## 🗄️ Database Structure

### Main Table: `modulos_operacoes`
```sql
CREATE TABLE modulos_operacoes (
    id_modulos_operacoes INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    id_modulos INT NOT NULL,             -- Module reference
    operacao VARCHAR(100) NOT NULL,      -- Operation identifier
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_modulos) REFERENCES modulos(id_modulos)
);
```

## 📁 File Structure

```
gestor/modulos/modulos-operacoes/
├── modulos-operacoes.php        # Main module controller
├── modulos-operacoes.js         # Client-side functionality
├── modulos-operacoes.json       # Module configuration
└── resources/
    ├── pt-br/
    │   └── pages/
    │       ├── modulos-operacoes/
    │       ├── modulos-operacoes-adicionar/
    │       └── modulos-operacoes-editar/
    └── en/
        └── ... (same structure)
```

## 🔧 Standard Operations

| Operation | Description |
|-----------|-------------|
| `listar` | List/view items |
| `adicionar` | Add new items |
| `editar` | Edit existing items |
| `excluir` | Delete items |
| `visualizar` | View details |
| `exportar` | Export data |
| `importar` | Import data |

## 🎨 User Interface

### Operation List
- Table of all operations
- Filter by module
- Edit/Delete actions
- Module association display

### Add/Edit Operation Form
- **Name**: Operation display name
- **ID**: Unique identifier
- **Module**: Associated module
- **Operation ID**: Operation code

## 💡 Permission Flow

### How Operations Work with Permissions
1. Admin creates operations for modules
2. User profiles select allowed operations
3. System checks operation permission on access
4. Denied operations hide UI elements

### Example Permission Check
```php
// Check if user can edit in this module
if (temPermissao('modulo-id', 'editar')) {
    // Show edit button
}
```

## 🔗 Related Modules
- `modulos`: Parent modules
- `usuarios-perfis`: Permission assignment
- `usuarios`: User access control
