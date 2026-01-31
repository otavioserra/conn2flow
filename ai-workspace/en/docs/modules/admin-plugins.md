# Module: admin-plugins

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-plugins` |
| **Name** | Plugin Administration |
| **Version** | `1.0.0` |
| **Category** | Administrative Module |
| **Complexity** | 🔴 High |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-plugins** module manages the **plugin system** in Conn2Flow. Plugins are modular extensions that add new functionality to the CMS without modifying core code. They can include new modules, components, pages, and database tables.

## 🏗️ Main Features

### 🔌 **Plugin Management**
- **Install plugins**: Upload and install plugin packages
- **Activate/Deactivate**: Toggle plugin status
- **Update plugins**: Apply plugin updates
- **Uninstall**: Remove plugins completely

### 📦 **Plugin Structure**
- **Manifest**: `manifest.json` defines plugin metadata
- **Modules**: Plugin-specific modules
- **Resources**: Layouts, pages, components
- **Database**: Plugin migrations and data
- **Assets**: CSS, JS, images

### 🔄 **Installation Process**
1. Upload plugin package (.zip)
2. Extract to plugins directory
3. Run database migrations
4. Import seed data
5. Activate plugin

## 🗄️ Database Structure

### Main Table: `plugins`
```sql
CREATE TABLE plugins (
    id_plugins INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    versao VARCHAR(50),
    autor VARCHAR(255),
    descricao TEXT,
    status CHAR(1) DEFAULT 'A',          -- A=Active, I=Inactive
    versao_registro INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

## 📁 Plugin Structure

```
plugins/{plugin-id}/
├── manifest.json                # Plugin metadata
├── controllers/                 # Plugin controllers
├── db/
│   ├── migrations/             # Database migrations
│   └── data/                   # Seed data (JSON)
├── modules/
│   └── {module-id}/            # Plugin modules
├── resources/
│   ├── pt-br/
│   │   ├── layouts/
│   │   ├── pages/
│   │   └── components/
│   └── en/
└── assets/
    ├── css/
    ├── js/
    └── images/
```

### manifest.json Example
```json
{
    "id": "my-plugin",
    "name": "My Plugin",
    "version": "1.0.0",
    "author": "Developer Name",
    "description": "Plugin description",
    "dependencies": {
        "gestor": ">=1.0.0"
    },
    "modules": [
        "my-module"
    ]
}
```

## 🔧 Core Operations

### Plugin Installation
```php
// 1. Extract package
// 2. Validate manifest
// 3. Run migrations
// 4. Import resources
// 5. Register plugin
```

### Plugin Activation
```php
// 1. Load plugin modules
// 2. Register routes
// 3. Update plugin status
```

## 🎨 User Interface

### Plugin List
- Installed plugins with status
- Version information
- Activate/Deactivate toggle
- Execute/Test actions
- Delete option

### Add Plugin Page
- File upload area
- Installation progress
- Error handling

### Plugin Execution Page
- Plugin-specific operations
- Configuration options
- Test functionality

## ⚠️ Security Considerations

- Only install plugins from trusted sources
- Review plugin code before installation
- Backup database before installing
- Test in development environment first
- Monitor plugin permissions

## 🔗 Related Modules
- `modulos`: Plugin modules appear here
- `admin-atualizacoes`: Plugin updates
