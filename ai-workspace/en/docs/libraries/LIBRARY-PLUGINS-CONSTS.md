# Library: plugins-consts.php

> 🔧 Constants and status codes for the plugin system

## Overview

The `plugins-consts.php` library defines essential constants for the Conn2Flow plugin installation and management system. It establishes standardized exit codes and execution statuses that allow consistent tracking and debugging during plugin operations.

**Location**: `gestor/bibliotecas/plugins-consts.php`  
**Version**: Phase 1  
**Total Functions**: 1 (helper)  
**Total Constants**: 12

## Dependencies

- No dependencies on other libraries
- Aligned with documentation in `ai-workspace/prompts/plugins/modificar-plugins.md`

## Defined Constants

### Exit Codes

Constants indicating the result of plugin operations:

#### PLG_EXIT_OK
```php
define('PLG_EXIT_OK', 0);
```
**Description**: Operation completed successfully  
**Usage**: Returned when plugin is installed/updated without errors

#### PLG_EXIT_PARAMS_OR_FILE
```php
define('PLG_EXIT_PARAMS_OR_FILE', 10);
```
**Description**: Parameter error or file not found  
**Common Causes**:
- Required parameters not provided
- Plugin file not found
- Invalid path

#### PLG_EXIT_VALIDATE
```php
define('PLG_EXIT_VALIDATE', 11);
```
**Description**: Plugin validation failure  
**Common Causes**:
- Invalid directory structure
- Missing required files
- Invalid metadata (metadata.json)
- Incompatible version

#### PLG_EXIT_MOVE
```php
define('PLG_EXIT_MOVE', 12);
```
**Description**: Failure moving plugin files  
**Common Causes**:
- Insufficient permissions in destination directory
- Insufficient disk space
- Destination directory does not exist

#### PLG_EXIT_DOWNLOAD
```php
define('PLG_EXIT_DOWNLOAD', 20);
```
**Description**: Plugin download failure  
**Common Causes**:
- Invalid or inaccessible URL
- Connectivity problem
- Request timeout
- Remote server unavailable

#### PLG_EXIT_ZIP_INVALID
```php
define('PLG_EXIT_ZIP_INVALID', 21);
```
**Description**: Invalid or corrupted ZIP file  
**Common Causes**:
- Corrupted ZIP file
- Not a valid ZIP file
- Failure extracting content

#### PLG_EXIT_CHECKSUM
```php
define('PLG_EXIT_CHECKSUM', 22);
```
**Description**: Checksum/integrity verification failure  
**Common Causes**:
- SHA256 hash does not match
- File modified after creation
- File corrupted during download

---

### Execution Status

Constants indicating the current state of a plugin operation:

#### PLG_STATUS_IDLE
```php
define('PLG_STATUS_IDLE', 'idle');
```
**Description**: System idle, no operation in progress  
**Transitions**: `idle` → `instalando` or `atualizando`

#### PLG_STATUS_INSTALANDO
```php
define('PLG_STATUS_INSTALANDO', 'instalando');
```
**Description**: Plugin being installed  
**Transitions**: `instalando` → `ok` or `erro`

#### PLG_STATUS_ATUALIZANDO
```php
define('PLG_STATUS_ATUALIZANDO', 'atualizando');
```
**Description**: Plugin being updated  
**Transitions**: `atualizando` → `ok` or `erro`

#### PLG_STATUS_ERRO
```php
define('PLG_STATUS_ERRO', 'erro');
```
**Description**: Operation failed  
**Transitions**: `erro` → `idle` (after handling)

#### PLG_STATUS_OK
```php
define('PLG_STATUS_OK', 'ok');
```
**Description**: Operation completed successfully  
**Transitions**: `ok` → `idle`

---

## Helper Function

### plg_exit_code_label()

Converts numeric exit code to descriptive label for debugging.

**Signature:**
```php
function plg_exit_code_label(int $code): string
```

**Parameters:**
- `$code` (int) - **Required** - Numeric exit code

**Return:**
- (string) - Descriptive label of the code or 'UNKNOWN' if not recognized

**Supported Codes:**

| Code | Label | Description |
|--------|-------|-----------|
| 0 | `OK` | Success |
| 10 | `PARAMS_OR_FILE` | Parameter/file error |
| 11 | `VALIDATE` | Validation failure |
| 12 | `MOVE` | Failure moving files |
| 20 | `DOWNLOAD` | Download failure |
| 21 | `ZIP_INVALID` | Invalid ZIP |
| 22 | `CHECKSUM` | Checksum failure |
| Other | `UNKNOWN` | Unknown code |

**Usage Example:**
```php
$result_code = install_plugin($plugin_data);

if($result_code !== PLG_EXIT_OK) {
    $label = plg_exit_code_label($result_code);
    error_log("Installation failed: " . $label . " (code: " . $result_code . ")");
}

// Example log output:
// "Installation failed: CHECKSUM (code: 22)"
```

---

## Use Cases

### 1. Plugin Installation

```php
function install_plugin_wrapper($plugin_url, $plugin_name) {
    // Update status
    update_plugin_status($plugin_name, PLG_STATUS_INSTALANDO);
    
    // Attempt installation
    $result = install_plugin(Array(
        'url' => $plugin_url,
        'nome' => $plugin_name
    ));
    
    // Process result
    switch($result) {
        case PLG_EXIT_OK:
            update_plugin_status($plugin_name, PLG_STATUS_OK);
            log_plugin("Plugin $plugin_name installed successfully");
            break;
            
        case PLG_EXIT_DOWNLOAD:
            update_plugin_status($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Download failed for plugin $plugin_name", 'error');
            break;
            
        case PLG_EXIT_VALIDATE:
            update_plugin_status($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Plugin $plugin_name failed validation", 'error');
            break;
            
        case PLG_EXIT_CHECKSUM:
            update_plugin_status($plugin_name, PLG_STATUS_ERRO);
            log_plugin("Invalid checksum for plugin $plugin_name", 'error');
            break;
            
        default:
            update_plugin_status($plugin_name, PLG_STATUS_ERRO);
            $label = plg_exit_code_label($result);
            log_plugin("Unknown error: $label (code: $result)", 'error');
    }
    
    return $result;
}
```

### 2. Detailed Logging System

```php
function log_plugin_operation($operation, $exit_code, $details = '') {
    global $_BANCO;
    
    $status_label = plg_exit_code_label($exit_code);
    $success = ($exit_code === PLG_EXIT_OK) ? 1 : 0;
    
    banco_query(
        "INSERT INTO plugins_log 
         (operation, exit_code, status_label, success, details, datetime) 
         VALUES 
         ('" . banco_escape_field($operation) . "',
          " . $exit_code . ",
          '" . banco_escape_field($status_label) . "',
          " . $success . ",
          '" . banco_escape_field($details) . "',
          NOW())"
    );
}

// Usage:
$result = install_plugin($data);
log_plugin_operation('installation', $result, "Plugin: $plugin_name");
```

### 3. Progress Interface

```php
function get_installation_status($plugin_id) {
    global $_BANCO;
    
    $status = banco_select_one(
        "SELECT execution_status, last_error_code 
         FROM plugins_installation 
         WHERE plugin_id = '" . banco_escape_field($plugin_id) . "'"
    );
    
    $response = Array(
        'status' => $status['execution_status'],
        'in_progress' => in_array($status['execution_status'], [
            PLG_STATUS_INSTALANDO,
            PLG_STATUS_ATUALIZANDO
        ]),
        'finished' => in_array($status['execution_status'], [
            PLG_STATUS_OK,
            PLG_STATUS_ERRO,
            PLG_STATUS_IDLE
        ]),
        'success' => ($status['execution_status'] === PLG_STATUS_OK)
    );
    
    if($status['last_error_code']) {
        $response['error_label'] = plg_exit_code_label($status['last_error_code']);
        $response['error_code'] = $status['last_error_code'];
    }
    
    return $response;
}

// Usage in AJAX:
// GET /api/plugin/status/123
$status = get_installation_status($_GET['plugin_id']);
echo json_encode($status);

// Response:
// {
//   "status": "instalando",
//   "in_progress": true,
//   "finished": false,
//   "success": false
// }
```

### 4. Validation and Error Handling

```php
function process_plugin_result($code) {
    $messages = Array(
        PLG_EXIT_OK => Array(
            'type' => 'success',
            'title' => 'Success',
            'message' => 'Plugin installed successfully!'
        ),
        PLG_EXIT_PARAMS_OR_FILE => Array(
            'type' => 'error',
            'title' => 'Parameter Error',
            'message' => 'Invalid parameters or file not found.'
        ),
        PLG_EXIT_VALIDATE => Array(
            'type' => 'error',
            'title' => 'Validation Failed',
            'message' => 'Plugin failed validation. Check structure.'
        ),
        PLG_EXIT_DOWNLOAD => Array(
            'type' => 'error',
            'title' => 'Download Error',
            'message' => 'Could not download plugin. Check connection.'
        ),
        PLG_EXIT_ZIP_INVALID => Array(
            'type' => 'error',
            'title' => 'Invalid ZIP',
            'message' => 'ZIP file is corrupted or invalid.'
        ),
        PLG_EXIT_CHECKSUM => Array(
            'type' => 'error',
            'title' => 'Integrity Check',
            'message' => 'Checksum does not match. File may be corrupted.'
        )
    );
    
    return $messages[$code] ?? Array(
        'type' => 'error',
        'title' => 'Unknown Error',
        'message' => 'Error: ' . plg_exit_code_label($code)
    );
}

// Usage:
$installation_result = install_plugin($data);
$feedback = process_plugin_result($installation_result);

// Display to user:
echo '<div class="alert alert-' . $feedback['type'] . '">';
echo '<strong>' . $feedback['title'] . '</strong>: ';
echo $feedback['message'];
echo '</div>';
```

### 5. State Machine

```php
class PluginStateMachine {
    private $plugin_id;
    private $current_status;
    
    public function __construct($plugin_id) {
        $this->plugin_id = $plugin_id;
        $this->current_status = $this->get_status();
    }
    
    public function can_start_installation() {
        return $this->current_status === PLG_STATUS_IDLE;
    }
    
    public function can_update() {
        return in_array($this->current_status, [
            PLG_STATUS_IDLE,
            PLG_STATUS_OK
        ]);
    }
    
    public function transition_to($new_status) {
        $valid_transitions = Array(
            PLG_STATUS_IDLE => [PLG_STATUS_INSTALANDO, PLG_STATUS_ATUALIZANDO],
            PLG_STATUS_INSTALANDO => [PLG_STATUS_OK, PLG_STATUS_ERRO],
            PLG_STATUS_ATUALIZANDO => [PLG_STATUS_OK, PLG_STATUS_ERRO],
            PLG_STATUS_OK => [PLG_STATUS_ATUALIZANDO, PLG_STATUS_IDLE],
            PLG_STATUS_ERRO => [PLG_STATUS_IDLE, PLG_STATUS_INSTALANDO]
        );
        
        if(in_array($new_status, $valid_transitions[$this->current_status] ?? [])) {
            $this->update_status($new_status);
            $this->current_status = $new_status;
            return true;
        }
        
        return false;
    }
    
    private function get_status() {
        // Query database
    }
    
    private function update_status($status) {
        // Update database
    }
}

// Usage:
$state = new PluginStateMachine($plugin_id);

if($state->can_start_installation()) {
    $state->transition_to(PLG_STATUS_INSTALANDO);
    // ... perform installation ...
    $state->transition_to(PLG_STATUS_OK);
}
```

## State Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    PLG_STATUS_IDLE                       │
│                  (Initial State)                         │
└──────────────────┬──────────────────────────────────────┘
                   │
     ┌─────────────┴──────────────┐
     │                            │
     ▼                            ▼
┌─────────────┐          ┌──────────────┐
│ INSTALANDO  │          │ ATUALIZANDO  │
└──────┬──────┘          └──────┬───────┘
       │                        │
       │    ┌──────────────────┘
       │    │
       ▼    ▼
    ┌────────┐
    │  OK    │────────┐
    └────────┘        │
                      │
       ┌──────────────┘
       │
       ▼
    ┌────────┐
    │ ERRO   │
    └───┬────┘
        │
        ▼
    (back to IDLE after handling)
```

## Exit Code Mapping

| Category | Codes | Recoverable? | Suggested Action |
|-----------|---------|--------------|---------------|
| **Success** | 0 | N/A | None |
| **Parameters** | 10-12 | Yes | Fix input/permissions |
| **Download/ZIP** | 20-22 | Partial | Retry or check file |

## Documentation References

This library is aligned with:
- `ai-workspace/prompts/plugins/modificar-plugins.md` - Main plugin system documentation
- `gestor/bibliotecas/plugins-installer.php` - Installer implementation
- `gestor/bibliotecas/plugins.php` - Plugin utilities

## Future Extensions

Possible additions to this library:

```php
// Additional codes for specific operations
define('PLG_EXIT_DEPENDENCY', 30);      // Dependency not met
define('PLG_EXIT_INCOMPATIBLE', 31);    // Incompatible version
define('PLG_EXIT_ALREADY_INSTALLED', 32); // Already installed

// Additional statuses
define('PLG_STATUS_DESINSTALANDO', 'desinstalando');
define('PLG_STATUS_PAUSADO', 'pausado');
define('PLG_STATUS_AGUARDANDO', 'aguardando_dependencia');
```

## See Also

- [LIBRARY-PLUGINS-INSTALLER.md](./LIBRARY-PLUGINS-INSTALLER.md) - Installation system
- [LIBRARY-PLUGINS.md](./LIBRARY-PLUGINS.md) - Plugin utilities
- [Plugin Architecture](../../CONN2FLOW-PLUGIN-ARCHITECTURE.md) - General architecture

---

**Last Update**: October 2025  
**Documented by**: Conn2Flow Team
