# Library: plugins-installer.php

> 🔌 Plugin installation and management system

## Overview

The `plugins-installer.php` library provides 43 functions to manage the entire plugin lifecycle: installation, update, uninstallation, activation/deactivation, and dependency management.

**Location**: `gestor/bibliotecas/plugins-installer.php`  
**Total Functions**: 43

## Main Features

### Installation
- `plugins_installer_install()` - Installs plugin
- `plugins_installer_download()` - Repository download
- `plugins_installer_extract()` - Extracts ZIP files
- `plugins_installer_verificar_requisitos()` - Checks dependencies
- `plugins_installer_copiar_arquivos()` - Copies files to destination
- `plugins_installer_criar_tabelas()` - Creates database tables

### Update
- `plugins_installer_update()` - Updates plugin
- `plugins_installer_verificar_versao()` - Compares versions
- `plugins_installer_backup_antes_update()` - Automatic backup
- `plugins_installer_migrar_dados()` - Data migration

### Uninstallation
- `plugins_installer_uninstall()` - Uninstalls plugin
- `plugins_installer_remover_arquivos()` - Removes files
- `plugins_installer_remover_tabelas()` - Removes tables
- `plugins_installer_limpar_cache()` - Clears cache

### Activation/Deactivation
- `plugins_installer_activate()` - Activates plugin
- `plugins_installer_deactivate()` - Deactivates plugin
- `plugins_installer_verificar_ativo()` - Checks if active

### Dependency Management
- `plugins_installer_verificar_dependencias()` - Checks deps
- `plugins_installer_instalar_dependencia()` - Installs dep
- `plugins_installer_resolver_conflitos()` - Resolves conflicts

### Listing and Search
- `plugins_installer_listar()` - Lists installed plugins
- `plugins_installer_buscar()` - Searches in repository
- `plugins_installer_detalhes()` - Plugin details

## Usage Examples

### Install Plugin

```php
$result = plugins_installer_install(Array(
    'plugin_id' => 'ecommerce-gateway',
    'versao' => '2.1.0',
    'source' => 'https://repo.conn2flow.com/plugins/ecommerce-gateway.zip'
));

if ($result['sucesso']) {
    echo "Plugin installed successfully!";
    
    // Automatically activate
    plugins_installer_activate(Array(
        'plugin_id' => 'ecommerce-gateway'
    ));
} else {
    echo "Error: " . $result['erro'];
}
```

### Update Plugin

```php
// Check if update is available
$update_available = plugins_installer_verificar_versao(Array(
    'plugin_id' => 'ecommerce-gateway',
    'versao_atual' => '2.0.0',
    'versao_nova' => '2.1.0'
));

if ($update_available) {
    // Backup before update
    plugins_installer_backup_antes_update(Array(
        'plugin_id' => 'ecommerce-gateway'
    ));
    
    // Update
    plugins_installer_update(Array(
        'plugin_id' => 'ecommerce-gateway',
        'versao' => '2.1.0'
    ));
}
```

### List Plugins

```php
$plugins = plugins_installer_listar(Array(
    'status' => 'ativo',  // or 'inativo', 'todos'
    'categoria' => 'ecommerce'
));

foreach ($plugins as $plugin) {
    echo "{$plugin['nome']} - v{$plugin['versao']} - {$plugin['status']}<br>";
}
```

### Check Dependencies

```php
$deps = plugins_installer_verificar_dependencias(Array(
    'plugin_id' => 'advanced-analytics',
    'dependencias' => Array(
        'php_version' => '7.4',
        'plugins' => Array('core-stats'),
        'extensoes_php' => Array('gd', 'curl')
    )
));

if (!$deps['ok']) {
    echo "Dependencies not met: ";
    print_r($deps['faltando']);
}
```

## Plugin Structure

```php
// plugin.json
{
    "id": "my-plugin",
    "nome": "My Plugin",
    "versao": "1.0.0",
    "autor": "Developer",
    "descricao": "Example plugin",
    "dependencias": {
        "php": ">=7.4",
        "conn2flow": ">=2.0",
        "plugins": ["plugin-base"]
    },
    "tabelas": [
        {
            "nome": "my_plugin_data",
            "sql": "CREATE TABLE ..."
        }
    ]
}
```

## Hooks and Events

Plugins can register hooks:

```php
// In plugin
plugins_installer_registrar_hook(Array(
    'evento' => 'product_created',
    'callback' => 'my_plugin_on_product_create'
));

function my_plugin_on_product_create($product) {
    // Execute custom action
}
```

## Security Patterns

### Validation
- Verify plugin signature
- Validate download origin
- Sanitize files

### Isolation
- Plugins run in isolated namespace
- Restricted file permissions
- Resource limits

---

## See Also

- [LIBRARY-PLUGINS.md](./LIBRARY-PLUGINS.md) - Plugin template
- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - Database operations

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
