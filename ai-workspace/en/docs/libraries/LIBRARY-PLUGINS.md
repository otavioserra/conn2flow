# Library: plugins.php

> 🔌 Base template for plugin functions

## Overview

The `plugins.php` library serves as a **template and example** for creating new plugin-related functions in the Conn2Flow system. It contains an example function demonstrating the standard structure to be followed.

**Location**: `gestor/bibliotecas/plugins.php`  
**Version**: 1.0.0  
**Total Functions**: 1 (template)  
**Type**: Template/Example

## Dependencies

- **Global Variables**: `$_GESTOR`

## Global Variables

```php
$_GESTOR['biblioteca-template'] = Array(
    'versao' => '1.0.0',
);
```

---

## Template Functions

### template_opcao()

Template function serving as a structure example.

**Signature:**
```php
function template_opcao($params = false)
```

**Parameters (Associative Array):**
- `variavel` (type) - Obligatoriness - Variable description

**Return:**
- (void)

**Usage Example:**
```php
template_opcao(Array(
    'variavel' => 'valor'
));
```

**Notes:**
- This is an example function
- Should be renamed as needed
- Follows system naming pattern

---

## Standard for New Plugin Functions

### Recommended Structure

```php
/**
 * Brief description of the function.
 *
 * Detailed description of what the function does,
 * including special behaviors and use cases.
 *
 * @param array|false $params Array of named parameters or false.
 * @return type Description of return.
 */
function function_name($params = false){
    global $_GESTOR;
    
    // Extracts variables from parameter array
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Expected parameters:
    // 
    // parameter1 - String - Required - Description of parameter 1.
    // parameter2 - Int - Optional - Description of parameter 2.
    // parameter3 - Bool - Optional - Description of parameter 3.
    // 
    // ===== 
    
    // Validation of required parameters
    if(!isset($parameter1)){
        return false;
    }
    
    // Logic implementation
    // ...
    
    return $result;
}
```

### Naming Conventions

1. **Function Name**:
   - Use descriptive prefix of module/context
   - Example: `plugin_install`, `plugin_validate`, `plugin_activate`

2. **Parameters**:
   - Always use associative array
   - Clearly document obligatoriness
   - Use standard PHP types

3. **Documentation**:
   - PHPDoc at top of function
   - Commented parameters section
   - Usage examples when relevant

---

## Implementation Examples

### Simple Plugin Function

```php
function plugin_validate($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parameters:
    // plugin_id - Int - Required - Plugin ID.
    // ===== 
    
    if(!isset($plugin_id)){
        return false;
    }
    
    // Fetch plugin
    $plugin = banco_select(Array(
        'campos' => Array('nome', 'versao', 'ativo'),
        'tabela' => 'plugins',
        'extra' => "WHERE id='$plugin_id'",
        'unico' => true
    ));
    
    if(!$plugin){
        return false;
    }
    
    // Validate structure
    $path = $_GESTOR['plugins-path'] . $plugin['nome'];
    if(!file_exists($path . '/plugin.json')){
        return false;
    }
    
    return true;
}
```

### Function with Multiple Options

```php
function plugin_manage($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // ===== Parameters:
    // action - String - Required - 'activate', 'deactivate', 'reinstall'.
    // plugin_id - Int - Required - Plugin ID.
    // ===== 
    
    if(!isset($action) || !isset($plugin_id)){
        return false;
    }
    
    switch($action){
        case 'activate':
            banco_update(
                "ativo=1",
                'plugins',
                "WHERE id='$plugin_id'"
            );
            return true;
        
        case 'deactivate':
            banco_update(
                "ativo=0",
                'plugins',
                "WHERE id='$plugin_id'"
            );
            return true;
        
        case 'reinstall':
            // Reinstallation logic
            return plugin_reinstall($plugin_id);
        
        default:
            return false;
    }
}
```

---

## See Also

- [LIBRARY-PLUGINS-INSTALLER.md](./LIBRARY-PLUGINS-INSTALLER.md) - Installation system
- [LIBRARY-PLUGINS-CONSTS.md](./LIBRARY-PLUGINS-CONSTS.md) - Plugin constants
- [Plugin Architecture](../../CONN2FLOW-PLUGIN-ARCHITECTURE.md) - Complete documentation

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
