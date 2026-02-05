# Conn2Flow Plugin Architecture

## Overview

The Conn2Flow plugin system allows extending CMS functionalities in a modular way, without modifying the core code. Plugins are independent extensions that can add new modules, resources, layouts, pages, and components.

## 📁 Plugin Structure

### Directory Structure

```
gestor/plugins/{plugin-id}/
├── manifest.json               # Plugin metadata and configuration
├── {plugin-id}.php             # Main controller (optional)
├── {plugin-id}.js              # Client-side JavaScript (optional)
├── assets/                     # Static files (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
├── db/
│   ├── migrations/             # Phinx migrations for tables
│   └── data/                   # JSON data files
│       ├── ModulosData.json
│       ├── PaginasData.json
│       ├── LayoutsData.json
│       ├── ComponentesData.json
│       └── VariaveisData.json
├── modulos/                    # Plugin modules
│   └── {module-id}/
│       ├── {module-id}.php
│       ├── {module-id}.js
│       ├── {module-id}.json
│       └── resources/
└── resources/                  # Layouts, pages, components
    ├── pt-br/
    │   ├── layouts/
    │   ├── pages/
    │   └── components/
    └── en/
        ├── layouts/
        ├── pages/
        └── components/
```

### manifest.json File

The `manifest.json` is the heart of the plugin, containing all metadata and configurations:

```json
{
    "id": "my-plugin",
    "nome": "My Amazing Plugin",
    "versao": "1.0.0",
    "descricao": "Adds amazing features to Conn2Flow",
    "autor": "Developer Name",
    "repositorio": "https://github.com/dev/my-plugin",
    "dependencias": {
        "gestor": ">=1.5.0",
        "php": ">=7.4"
    },
    "modulos": [
        "plugin-module"
    ],
    "migracoes": true,
    "recursos": true
}
```

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique plugin identifier (slug) |
| `nome` | string | Display name |
| `versao` | string | Semantic version (SemVer) |
| `descricao` | string | Brief description |
| `autor` | string | Author/company name |
| `repositorio` | string | GitHub repository URL |
| `dependencias` | object | Version dependencies |
| `modulos` | array | List of included modules |
| `migracoes` | boolean | Whether it has database migrations |
| `recursos` | boolean | Whether it has resources (layouts, pages, etc.) |

---

## 🗄️ Database

### Main Table: `plugins`

```sql
CREATE TABLE plugins (
    id_plugins INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,     -- Plugin slug
    nome VARCHAR(255) NOT NULL,          -- Display name
    descricao TEXT,                      -- Description
    versao VARCHAR(50),                  -- Current installed version
    autor VARCHAR(255),                  -- Author
    repositorio VARCHAR(255),            -- Repository URL
    ativo CHAR(1) DEFAULT 'N',           -- S = Active, N = Inactive
    status CHAR(1) DEFAULT 'A',          -- General status
    data_instalacao DATETIME,            -- Installation date
    data_atualizacao DATETIME,           -- Last update
    versao_reg INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Data.json Files

Plugins use `*Data.json` files to synchronize data with the database:

| File | Table | Description |
|------|-------|-------------|
| `ModulosData.json` | `modulos` | Module configuration |
| `PaginasData.json` | `paginas` | Page definitions |
| `LayoutsData.json` | `layouts` | Layout definitions |
| `ComponentesData.json` | `componentes` | Component definitions |
| `VariaveisData.json` | `variaveis` | System variables |

---

## 🔧 Plugin Lifecycle

### Plugin States

| Status | Constant | Description |
|--------|----------|-------------|
| Idle | `PLG_STATUS_IDLE` | System at rest |
| Installing | `PLG_STATUS_INSTALANDO` | Plugin being installed |
| Updating | `PLG_STATUS_ATUALIZANDO` | Plugin being updated |
| Error | `PLG_STATUS_ERRO` | Operation failed |
| OK | `PLG_STATUS_OK` | Operation completed |

### Exit Codes

| Code | Constant | Description |
|------|----------|-------------|
| 0 | `PLG_EXIT_OK` | Success |
| 10 | `PLG_EXIT_PARAMS_OR_FILE` | Parameter/file error |
| 11 | `PLG_EXIT_VALIDATE` | Validation failed |
| 12 | `PLG_EXIT_MOVE` | Failed to move files |
| 20 | `PLG_EXIT_DOWNLOAD` | Download failed |
| 21 | `PLG_EXIT_ZIP_INVALID` | Invalid ZIP |
| 22 | `PLG_EXIT_CHECKSUM` | Checksum failed |

---

## 📦 Installation Flow

### Complete Pipeline

1. **Validation** - Parameter and origin verification
2. **Download/Copy** - Get package to staging (`temp/plugins/<slug>/`)
3. **Extraction** - Extract ZIP to staging
4. **Manifest Validation** - Verify `manifest.json` and structure
5. **Backup** - Backup previous installation (if exists)
6. **Movement** - Move files to `plugins/<slug>/`
7. **Migrations** - Execute database migrations (if enabled)
8. **Data.json Detection** - Auto-detect all `*Data.json` files
9. **Resource Synchronization** - Sync data for each file
10. **Module Synchronization** - Process `modules/*/module-id.json`
11. **Cleanup** - Remove `db/` folder from installed plugin
12. **Permissions** - Permission correction (recursive chown)
13. **Persistence** - Update metadata in `plugins` table
14. **Logging** - Final log and exit code

### Supported Origins

| Origin | Description |
|--------|-------------|
| `upload` | Local ZIP via upload |
| `github_publico` | Public GitHub repository |
| `github_privado` | Private GitHub repository (with token) |
| `local_path` | Local server path |

### GitHub Download

#### Public Repositories
```
https://github.com/{owner}/{repo}/releases/download/{tag}/gestor-plugin.zip
```

#### Private Repositories
Uses REST API for assets with authentication:
```http
Authorization: token YOUR_TOKEN
Accept: application/octet-stream
User-Agent: Conn2Flow-Plugin-Manager/1.0
```

**SHA256 Integrity Verification:**
- Download `gestor-plugin.zip.sha256` file
- Calculate hash of downloaded ZIP
- Compare and validate before proceeding

---

## 🔌 System Libraries

### plugins.php

Base template for plugin functions.

**Location**: `gestor/bibliotecas/plugins.php`

```php
function plugin_validar($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parameters:
    // plugin_id - Int - Required - Plugin ID.
    // ===== 
    
    if(!isset($plugin_id)){
        return false;
    }
    
    // Plugin validation...
    return true;
}
```

### plugins-installer.php

Complete installation and management system.

**Location**: `gestor/bibliotecas/plugins-installer.php`  
**Total Functions**: 43

#### Main Functions

| Category | Functions |
|----------|-----------|
| **Installation** | `plugins_installer_install()`, `plugins_installer_download()`, `plugins_installer_extract()` |
| **Update** | `plugins_installer_update()`, `plugins_installer_verificar_versao()`, `plugins_installer_backup_antes_update()` |
| **Uninstall** | `plugins_installer_uninstall()`, `plugins_installer_remover_arquivos()`, `plugins_installer_remover_tabelas()` |
| **Activation** | `plugins_installer_activate()`, `plugins_installer_deactivate()`, `plugins_installer_verificar_ativo()` |
| **Dependencies** | `plugins_installer_verificar_dependencias()`, `plugins_installer_resolver_conflitos()` |

### plugins-consts.php

Constants and status codes.

**Location**: `gestor/bibliotecas/plugins-consts.php`

```php
// Exit Codes
define('PLG_EXIT_OK', 0);
define('PLG_EXIT_PARAMS_OR_FILE', 10);
define('PLG_EXIT_VALIDATE', 11);
define('PLG_EXIT_MOVE', 12);
define('PLG_EXIT_DOWNLOAD', 20);
define('PLG_EXIT_ZIP_INVALID', 21);
define('PLG_EXIT_CHECKSUM', 22);

// Execution Status
define('PLG_STATUS_IDLE', 'idle');
define('PLG_STATUS_INSTALANDO', 'instalando');
define('PLG_STATUS_ATUALIZANDO', 'atualizando');
define('PLG_STATUS_ERRO', 'erro');
define('PLG_STATUS_OK', 'ok');
```

---

## 🎨 Administrative Module

### admin-plugins

The `admin-plugins` module manages the plugin administration interface.

**Location**: `gestor/modulos/admin-plugins/`

#### Features

| Feature | Description |
|---------|-------------|
| **Discovery** | Browse available plugins in marketplace |
| **Download** | Fetch packages from repositories |
| **Installation** | Execute migrations and setup |
| **Activation** | Enable/disable functionality |
| **Update** | Apply new versions |
| **Removal** | Completely uninstall |
| **Monitoring** | Check version and health |

#### User Interface

- **Plugin List**: Card grid with info, status, and actions
- **Plugin Detail**: Description, versions, configurations

---

## ⚠️ Security

### Installation Validations

- ✅ Verify package signature
- ✅ Validate `manifest.json` structure
- ✅ Verify SHA256 checksum
- ✅ Scan for malicious code
- ✅ Execute in sandbox first

### Permissions

- Only administrators can install plugins
- Audit logs for all changes
- Automatic backup before update/removal

---

## 📍 Important Locations

| File/Directory | Description |
|----------------|-------------|
| `gestor/plugins/` | Installed plugins directory |
| `gestor/bibliotecas/plugins-installer.php` | Main installer code |
| `gestor/controladores/plugins/atualizacao-plugin.php` | CLI orchestration |
| `gestor/logs/plugins/installer.log` | Installation logs |
| `gestor/plugins/_backups/` | Previous version backups |
| `gestor/temp/plugins/` | Installation staging |

---

## 🔗 Related Documentation

- [Plugin Installer Flow](./CONN2FLOW-PLUGIN-INSTALLER-FLOW.md) - Installation pipeline details
- [plugins.php Library](./libraries/LIBRARY-PLUGINS.md) - Function template
- [plugins-installer.php Library](./libraries/LIBRARY-PLUGINS-INSTALLER.md) - Installation system
- [plugins-consts.php Library](./libraries/LIBRARY-PLUGINS-CONSTS.md) - Constants and codes
- [admin-plugins Module](./modulos/admin-plugins.md) - Administrative interface

---

## 🚀 Quick Development Guide

### 1. Create Basic Structure

```bash
mkdir -p gestor/plugins/my-plugin/{assets,db/{migrations,data},modulos,resources/{pt-br,en}}
```

### 2. Create manifest.json

```json
{
    "id": "my-plugin",
    "nome": "My Plugin",
    "versao": "1.0.0",
    "descricao": "Plugin description",
    "autor": "Your Name",
    "repositorio": "https://github.com/your-user/my-plugin",
    "dependencias": {
        "gestor": ">=1.5.0"
    },
    "modulos": [],
    "migracoes": false,
    "recursos": true
}
```

### 3. Add Resources

Create `*Data.json` files in `db/data/` to synchronize resources.

### 4. Package

```bash
cd gestor/plugins/my-plugin
zip -r gestor-plugin.zip .
sha256sum gestor-plugin.zip > gestor-plugin.zip.sha256
```

### 5. Distribute

Upload as a GitHub release or distribute directly.

---

**Last Updated**: February 2026  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
