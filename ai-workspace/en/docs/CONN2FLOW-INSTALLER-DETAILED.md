# Conn2Flow - Installation System (Manager-Installer)

## 📋 Index
- [Overview](#overview)
- [Installer Architecture](#installer-architecture)
- [Complete Installation Process](#complete-installation-process)
- [Installer.php Class](#installerphp-class)
- [Auto-Login System](#auto-login-system)
- [Environment Configuration](#environment-configuration)
- [Success Page System](#success-page-system)
- [Logging and Debugging](#logging-and-debugging)
- [Troubleshooting](#troubleshooting)
- [Development History](#development-history)

---

## 🎯 Overview

### What is the Manager-Installer
The **Manager-Installer** is an automated installation system for Conn2Flow that:
- **Verifies server environment** (PHP, extensions, permissions)
- **Configures database** automatically
- **Executes migrations and seeds** for initial structure
- **Configures authentication** with SSL keys
- **Creates administrator user** with auto-login
- **Generates success page** with automatic removal

### Location and Structure
```
gestor-instalador/
├── index.php              # Installation entry point
├── installer.log          # Current installation log
├── teste-seguranca.txt    # Security test file
├── src/
│   └── Installer.php      # ❤️ Main installer class
├── assets/                # CSS, JS, installer images
├── lang/                  # Language files
├── public-access/         # Public files
└── views/                 # Interface templates
```

---

## 🏗️ Installer Architecture

### Design Pattern
- **Single Class**: `Installer.php` centralizes all logic
- **Integrated Logging**: Own logging system with levels
- **Error Handling**: Try/catch in all critical operations
- **Web Interface**: HTML templates for visual feedback
- **Robust Validation**: Checks at each step

### Dependencies and Requirements
#### System Requirements
- **PHP**: 7.4+ (automatically verified)
- **PHP Extensions**: MySQLi/PDO, OpenSSL, JSON, mbstring
- **Permissions**: Write access in specific directories
- **MySQL Database**: Connection and creation permissions

#### Conn2Flow Dependencies
- **Manager Core**: Main structure in `/gestor`
- **Migrations**: Phinx system for DB structure
- **Seeds**: Initial data (users, layouts, pages)
- **Libraries**: Authentication and database system

---

## 🔄 Complete Installation Process

### 8-Step Flow

#### 1. **Environment Verification** (`checkSystemRequirements()`)
```php
- PHP Version >= 7.4
- Extensions: mysqli, pdo, openssl, json, mbstring
- Write permissions in critical directories
- Basic security verification
```

#### 2. **Database Configuration** (`setupDatabase()`)
```php
- Connection test with provided credentials
- Database creation if not exists
- User permissions verification
- UTF-8 charset configuration
```

#### 3. **File Extraction** (`extract_files()`)
```php
- Unzipping gestor.zip
- Directory structure creation
- Initial file configuration
- .env file creation with settings
```

#### 4. **Migrations Execution** (`runMigrations()`)
```php
- Phinx loading
- Sequential migrations execution
- Table structure creation
- Indexes and relationships
```

#### 5. **Seeds Execution** (`runSeeds()`)
```php
- Mandatory initial data
- Default layouts (ID 1, 23, etc.)
- System modules
- Basic pages
- Default configurations
```

#### 6. **Auto-Login and Configuration** (`createAdminAutoLogin()`) ⚠️ **CORRECTED ORDER**
```php
- ✅ Executed AFTER .env creation and users
- JWT token generation
- Persistent cookie definition
- Automatic authentication configuration
```

#### 7. **SSL Keys Generation** (`generateSSLKeys()`)
```php
- Private/public key creation
- Password protection via OPENSSL_PASSWORD
- Configuration for JWT
- Secure storage
```

#### 8. **Success Page and Cleanup** (`createSuccessPage()`, `cleanupInstaller()`)
```php
- Informative page creation
- Automatic removal configuration
- Temporary files cleanup
- Final redirection
```

---

## 🔧 Installer.php Class

### Main Properties
```php
class Installer {
    private $data = [];           // Installation configuration data
    private $logFile;            // Current log file
    private $gestorPath;         // Path to manager
    private $config = [];        // Loaded configurations
    
    // Public methods
    public function install($data)              // Main process
    public function getInstallationStatus()    // Current status
    public function getLogContents()          // Log contents
}
```

### Critical Methods

#### `install($data)` - Main Method
```php
public function install($data) {
    $this->data = $data;
    $this->log("Starting Conn2Flow installation v1.8.4+");
    
    try {
        $this->checkSystemRequirements();
        $this->setupDatabase();
        $this->runMigrations();
        $this->runSeeds();
        $this->setupGestorEnvironment();
        $this->generateSSLKeys();
        $this->createAdminUser();
        $this->createAdminAutoLogin();
        $this->createSuccessPage();
        $this->cleanupInstallerFiles();
        
        return ['success' => true, 'message' => 'Installation completed!'];
    } catch (Exception $e) {
        $this->log("ERROR: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

#### `setupGestorEnvironment()` - Native Configuration
```php
private function setupGestorEnvironment() {
    // Uses native system config.php instead of creating manual $_CONFIG
    require_once($this->gestorPath . '/config.php');
    
    // Installation specific configurations
    $_CONFIG['url-raiz'] = $this->data['url_raiz'];
    $_CONFIG['banco']['host'] = $this->data['db_host'];
    $_CONFIG['banco']['nome'] = $this->data['db_name'];
    // ... other configurations
}
```

#### `generateSSLKeys($senha)` - Keys with Protection
```php
private function generateSSLKeys($senha) {
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey, $senha); // ⚠️ MANDATORY PASSWORD
    
    $publicKey = openssl_pkey_get_details($res);
    $publicKey = $publicKey["key"];
    
    // Stores keys with protection
}
```

---

## 🔐 Auto-Login System

### Complete Implementation

#### `createAdminAutoLogin()` - Token Generation
```php
private function createAdminAutoLogin() {
    // 1. Loads authentication libraries
    require_once($this->gestorPath . '/bibliotecas/autenticacao.php');
    
    // 2. Prepares user data
    $userData = [
        'id_usuarios' => 1,
        'email' => $this->data['admin_email'],
        'nome' => $this->data['admin_nome'],
        'permanecer_logado' => true
    ];
    
    // 3. Generates JWT token using native system function
    $token = usuario_gerar_token_autorizacao($userData);
    
    // 4. Sets persistent cookie (30 days)
    setcookie('auth_token', $token, time() + (30 * 24 * 60 * 60), '/');
    
    $this->log("Auto-login configured successfully");
}
```

### Authentication Process
1. **JWT Token generated** using `usuario_gerar_token_autorizacao()`
2. **Persistent Cookie** set for 30 days
3. **Automatic Redirection** to dashboard after installation
4. **Native Validation** by manager authentication system

---

## ⚙️ Environment Configuration

### Native Configuration System

#### Before (Problematic)
```php
// ❌ WRONG - Manual $_CONFIG creation
$_CONFIG = array();
$_CONFIG['banco']['host'] = $host;
$_CONFIG['banco']['nome'] = $database;
// Hardcoded values, not integrated into system
```

#### After (Correct)
```php
// ✅ CORRECT - Uses native config.php
require_once($this->gestorPath . '/config.php');
// System automatically loads configurations
// Integrated with .env and native system
```

### Environment Variables (.env)
```env
# Automatically generated configurations
DB_HOST=localhost
DB_NAME=conn2flow
DB_USER=root
DB_PASS=db_password

# Security
OPENSSL_PASSWORD=ssl_keys_password
JWT_SECRET=generated_jwt_key

# System
APP_ENV=production
DEBUG=false
URL_RAIZ=https://example.com/

# Paths
GESTOR_PATH=/full/path/manager
```

---

## 📄 Success Page System

### Complete Implementation

#### `createSuccessPage()` - Page Creation
```php
private function createSuccessPage() {
    // ⚠️ IMPORTANT: Uses 'paginas' table (not 'hosts_paginas')
    // ⚠️ IMPORTANT: Uses layout ID 23 (external, no admin menu)
    
    $insertQuery = "
        INSERT INTO paginas (
            id_usuarios, id_layouts, nome, id, caminho, tipo, 
            html, css, status, versao, data_criacao, data_modificacao
        ) VALUES (
            1, 23, 'Installation Completed', 'instalacao-sucesso', 'instalacao-sucesso', 'pagina',
            :html, :css, 'A', 1, NOW(), NOW()
        )";
    
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        'html' => $this->getSuccessPageHtml(),
        'css' => $this->getSuccessPageCss()
    ]);
}
```

#### `getSuccessPageHtml()` - Page Content
```html
<div class="ui main container">
    <div class="ui centered grid">
        <div class="twelve wide column">
            <!-- Success Message -->
            <div class="ui positive message">
                <div class="header">
                    <i class="checkmark icon"></i>
                    Installation Completed Successfully!
                </div>
                <p>Conn2Flow has been installed and configured successfully on your server.</p>
            </div>
            
            <!-- Next Steps -->
            <div class="ui segment">
                <div class="ui header">Next Steps</div>
                <div class="ui ordered steps">
                    <div class="step">Access the Panel</div>
                    <div class="step">Configure the System</div>
                    <div class="step">Customize the Design</div>
                    <div class="step">Start Using</div>
                </div>
            </div>
            
            <!-- Access Button -->
            <div class="ui center aligned segment">
                <a href="@[[pagina#url-raiz]]@dashboard" class="ui huge primary button">
                    <i class="sign in icon"></i>
                    Access Administrative Panel
                </a>
            </div>
            
            <!-- Final Note -->
            <div class="ui info message">
                <div class="header">Note</div>
                <p>This page will be automatically removed when you access the administrative panel for the first time.</p>
            </div>
        </div>
    </div>
</div>
```

### Automatic Removal
- **Implemented in**: `gestor/modulos/dashboard/dashboard.php`
- **Function**: `dashboard_remover_pagina_instalacao_sucesso()`
- **Moment**: First access to dashboard after installation
- **Feedback**: Informative toast for the user

---

## 📝 Logging and Debugging

### Integrated Log System

#### `log($message, $level)` - Main Function
```php
private function log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Writes to file
    file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Can also display in real-time for web interface
    echo $logEntry;
    flush();
}
```

#### Log Levels
- **INFO**: General process information
- **WARNING**: Non-critical warnings
- **ERROR**: Errors preventing continuation
- **DEBUG**: Detailed information for development

#### Usage Examples
```php
$this->log("Starting system verification");
$this->log("OpenSSL extension not found", 'WARNING');
$this->log("Database connection failure: " . $e->getMessage(), 'ERROR');
$this->log("Query executed: " . $sql, 'DEBUG');
```

---

## 🚨 Troubleshooting

### Common Problems and Solutions

#### 1. Database Connection Error
**Symptoms**:
- "Connection refused" or "Access denied"
- Installation stops at step 2

**Solutions**:
```php
// Verify credentials
$this->data['db_host'] = 'localhost'; // or correct IP
$this->data['db_user'] = 'root';      // user with permissions
$this->data['db_pass'] = 'password';  // correct password

// Verify MySQL permissions
GRANT ALL PRIVILEGES ON *.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. "Configuration file (.env) not found" Error ⚠️ **PROBLEM SOLVED**
**Symptoms**:
- Error 503 with message about .env not found
- Failure during auto-login

**Cause**:
- Auto-login executing before complete creation of .env file
- Incorrect execution order in installer

**Implemented Solution**:
```php
// ✅ CORRECT ORDER (corrected in July 2025)
1. extract_files() → Creates structure and .env
2. run_migrations() → Executes migrations
3. runSeeds() → Creates users in database
4. createAdminAutoLogin() → NOW can use .env + existing users
```

#### 3. Missing PHP Extensions
**Symptoms**:
- "Extension not found" in logs
- System verification failure

**Solutions**:
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli php-pdo php-openssl php-json php-mbstring

# CentOS/RHEL
sudo yum install php-mysqli php-pdo php-openssl php-json php-mbstring
```

#### 3. Permission Problems
**Symptoms**:
- "Permission denied" when creating files
- Failure in SSL key generation

**Solutions**:
```bash
# Set correct permissions
chmod 755 gestor/
chmod 644 gestor/config.php
chmod 600 gestor/autenticacoes/*/chaves/*
```

#### 4. Incorrect Layout on Success Page
**Symptoms**:
- Success page with administrative menu
- Inconsistent layout

**Solution**:
```php
// Verify if using layout ID 23 (external)
$layoutId = 23; // Layout without administrative menu
```

#### 5. Auto-Login Not Working
**Symptoms**:
- Redirection to login after installation
- Cookie not defined

**Verifications**:
```php
// Verify if function exists
if (function_exists('usuario_gerar_token_autorizacao')) {
    // Verify if libraries were loaded
    require_once($this->gestorPath . '/bibliotecas/autenticacao.php');
    
    // Verify if cookie is being set
    setcookie('auth_token', $token, time() + (30 * 24 * 60 * 60), '/');
}
```

---

## 📚 Development History

### Version 1.0 (Original Base)
- **Features**: Basic installation with migrations and seeds
- **Configuration**: Manual via web form
- **Authentication**: Manual login after installation
- **Problems**: Hardcoded values, no integration with .env

### Version 1.1 (Auto-Login)
**Date**: July 2025
**Improvements**:
- ✅ Auto-login implementation with JWT
- ✅ Integration with `usuario_gerar_token_autorizacao()`
- ✅ Persistent cookie for 30 days
- ✅ Automatic redirection to dashboard

### Version 1.2 (Native Configuration)
**Date**: July 2025
**Improvements**:
- ✅ Use of native system `config.php`
- ✅ Removal of hardcoded values
- ✅ Integration with existing .env system
- ✅ SSL password via `OPENSSL_PASSWORD`

### Version 1.3 (Success Page)
**Date**: July 2025
**Improvements**:
- ✅ Creation of post-installation informative page
- ✅ Layout ID 23 (external, no administrative menu)
- ✅ Automatic removal on first dashboard access
- ✅ Informative toast for user
- ✅ Responsive interface with Semantic UI

### Version 1.4 (Execution Order Correction)
**Date**: July 2025
**Problem**: Error 503 - "Configuration file (.env) not found for domain: localhost"
**Cause**: Auto-login executing before .env file is completely configured
**Improvements**:
- ✅ Correction of execution order in installer
- ✅ Auto-login moved to after .env creation AND user insertion
- ✅ Correct order: extract_files → run_migrations → seeds → auto-login
- ✅ Updated documentation with specific troubleshooting
- ✅ Prevention of configuration not found errors

### Future Planned Improvements
- **Multi-Language Installation**: Support for Portuguese, English, Spanish
- **Automatic Backup**: Backup before critical modifications
- **Rollback**: Ability to undo installation
- **Integrity Verification**: Hash check of core files
- **Silent Installation**: Via command line
- **Progress Bar**: More detailed visual indicator

---

## 🔧 Advanced Configurations

### Installation Customization

#### Optional Configurations
```php
// Configurations that can be customized
$customConfig = [
    'auto_login_duration' => 30,        // days of persistent cookie
    'success_page_layout' => 23,        // layout ID for success page
    'ssl_key_bits' => 4096,            // SSL key size
    'log_level' => 'INFO',             // logging level
    'cleanup_installer' => true,        // cleanup installer files
];
```

#### Available Hooks
```php
// Points where custom code can be added
private function beforeInstall() { /* Hook before installation */ }
private function afterDatabase() { /* Hook after DB configuration */ }
private function afterMigrations() { /* Hook after migrations */ }
private function afterSeeds() { /* Hook after seeds */ }
private function beforeCleanup() { /* Hook before cleanup */ }
private function afterInstall() { /* Hook after complete installation */ }
```

### Integration with External Systems

#### cPanel/WHM
```php
// Automatic integration with cPanel if available
if ($this->isCpanelEnvironment()) {
    $this->configureCpanelIntegration();
    $this->createCpanelAccount();
}
```

#### Docker
```php
// Specific configurations for Docker environment
if ($this->isDockerEnvironment()) {
    $this->configureDockerPaths();
    $this->setDockerPermissions();
}
```

---

**Complete Technical Document of Manager-Installer**
**Version**: 1.3.0
**Last Update**: July 2025
**Maintained by**: GitHub Copilot AI

> This document contains all technical details, implementations, and history of the Conn2Flow installation system. For quick reference, consult the main knowledge document.
