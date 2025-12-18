# Conn2Flow - Knowledge System and Technical Documentation

## 📋 Index
- [Detailed Knowledge Files](#detailed-knowledge-files)
- [System Overview](#system-overview)
- [Architecture and Structure](#architecture-and-structure)
- [Installation System (Summary)](#installation-system-summary)
- [Authentication System](#authentication-system)
- [Layout System](#layout-system)
- [Database](#database)
- [Modules and Features](#modules-and-features)
- [Configuration and Environment](#configuration-and-environment)
- [Development and Debugging](#development-and-debugging)
- [Implementation History](#implementation-history)

---

## 📚 Detailed Knowledge Files

### Modular Documentation by Area

#### 🛠️ CONN2FLOW-INSTALLER-DETAILED.md
**Area**: Installation System (Manager-Installer)
**Content**: 
- Complete installer architecture with `Installer.php` class
- Detailed 8-step process (verification → database → migrations → seeds → SSL → auto-login)
- Auto-login system with JWT and persistent cookies
- Native configuration via `config.php` and .env integration
- Success pages with automatic removal from dashboard
- Advanced logging and specific troubleshooting

**When to use**: Development, maintenance, or debugging of the installation system

#### 📋 Future Planned Files
- `CONN2FLOW-AUTHENTICATION-DETAILED.md` - Complete authentication system
- `CONN2FLOW-MODULES-DETAILED.md` - Module development and structure
- `CONN2FLOW-DATABASE-DETAILED.md` - Complete database structure
- `CONN2FLOW-CPANEL-DETAILED.md` - Complete cPanel/WHM integration
- `CONN2FLOW-API-DETAILED.md` - APIs and external integrations

### How to Use This Documentation
1. **This file**: General summary and quick reference
2. **Detailed files**: Specific technical information by area
3. **Future context**: Specialized agents will be able to consult specific files
4. **Modular structure**: Avoids context overload in conversations

---

## 🎯 System Overview

### What is Conn2Flow
**Conn2Flow** is a complex and robust CMS (Content Management System) developed in PHP that functions as:
- **Central Core**: A central server that manages multiple distributed hosts
- **Multi-Host System**: Each host has its own instance of the manager-client
- **Modular Platform**: System based on extensible modules and plugins

### System Versions
- **Conn2Flow Core**: v1.8.4+ (main system)
- **Manager-Client**: Distributed system running on hosts
- **Manager-Installer**: Automated installation system

---

## 🏗️ Architecture and Structure

### Main Directory Structure
```
conn2flow/
├── gestor/                     # Main system core
│   ├── config.php             # Central configurations
│   ├── gestor.php            # ❤️ SYSTEM HEART - Main router
│   ├── modulos/              # System modules
│   │   └── dashboard/        # Main module (always loaded on login)
│   ├── bibliotecas/          # System libraries
│   ├── db/seeds/            # Database seeds
│   └── vendor/              # Composer dependencies
├── gestor-cliente/           # Distributed system for hosts
├── gestor-instalador/        # Installation system
│   └── src/Installer.php    # Main installation class
├── cpanel/                   # cPanel integrations
├── docker/                   # Docker configurations
└── utilitarios/             # Documentation and utility files
```

### 🎯 Central Architecture: The Heart of the System (gestor.php)

#### What is gestor.php
**`gestor.php`** is the **HEART** of the entire Conn2Flow system:
- **Main Router**: Processes all requests
- **Static File Manager**: Handles CSS, JS, images
- **Process Initiator**: Entry point for the entire application
- **Component Connector**: Links layouts, pages, modules, and components

#### Structuring Philosophy: HTML in Database
The system was designed to store **EVERYTHING related to content in the database**:
- Complete layouts
- Specific pages
- Reusable components
- Specific programming (modules)

### 🧩 Layer System: The Fundamental Structure

#### 1. **LAYOUTS** (Table: `layouts`)
- **Function**: Structure that repeats (like WordPress header/footer)
- **Content**: Complete HTML with dynamic variables
- **Variables**: `@[[pagina#url-raiz]]@`, `@[[usuario#nome]]@`, etc.
- **Critical Variable**: `@[[pagina#corpo]]@` - where page content is inserted

#### 2. **PAGES** (Table: `paginas`)
- **Function**: Specific content that goes in the "middle" of the page
- **Linking**: Each page has an associated layout
- **Routing**: `caminho` field defines the URL in the browser
- **Content**: Specific HTML of the page (goes into `@[[pagina#corpo]]@`)

#### 3. **COMPONENTS** (Table: `componentes`)
- **Function**: Reusable interface pieces
- **Examples**: Alerts, forms, modals, buttons
- **Usage**: Included within pages or layouts
- **Advantage**: Reuse and standardization

#### 4. **MODULES** (Directory: `gestor/modulos/`)
- **Function**: Specific programming for pages
- **Linking**: Pages may or may not have an associated module
- **Process**: gestor.php checks linking and automatically includes module
- **Example**: `dashboard` has layout + page + components + module

### 🔄 Complete Processing Flow

```
1. Request → gestor.php (HEART)
       ↓
2. Routing → Identifies page by path
       ↓
3. Fetch Page → Table `paginas` 
       ↓
4. Fetch Layout → Table `layouts` (linked to page)
       ↓
5. Fetch Module → Directory `modulos/` (if linked)
       ↓
6. Process Variables → Replaces @[[variables]]@ 
       ↓
7. Include Components → Table `componentes` (if necessary)
       ↓
8. Render → Final HTML to browser
```

### Critical Differentiation: Core vs Host Tables

#### ⚠️ IMPORTANT: Table Naming
- **Core Tables** (no prefix): `paginas`, `usuarios`, `modulos`, `layouts`
  - Belong to the main/central manager
  - Manage the system core
  
- **Host Tables** (`hosts_` prefix): `hosts_paginas`, `hosts_usuarios`, `hosts_layouts`
  - Are for external distributed hosts
  - Each host has its own data copies

**This differentiation is FUNDAMENTAL to avoid confusion about where to create/fetch data!**

---

## ️ Installation System (Summary)

### Manager-Installer
**📖 Detailed documentation**: `CONN2FLOW-INSTALLER-DETAILED.md`

#### Automated Process (8 Steps)
1. **Environment Verification** - PHP 7.4+, extensions, permissions
2. **Database Configuration** - Connection and automatic creation  
3. **Migrations Execution** - Complete table structure
4. **Seeds Execution** - Mandatory initial data
5. **Native Configuration** - Integration with `config.php` and `.env`
6. **SSL Key Generation** - Protection by `OPENSSL_PASSWORD`
7. **Admin User Creation** - With JWT auto-login
8. **Success Page** - External layout + automatic removal on dashboard

#### Main Features
- **Auto-Login**: JWT token + persistent cookie (30 days)
- **Native Configuration**: Uses system `config.php` (not hardcoded)
- **Success Page**: Layout ID 23 (external), automatically removed on dashboard
- **Advanced Logging**: Complete log system with levels
- **Error Handling**: Try/catch in all critical operations

#### Main Files
- `gestor-instalador/src/Installer.php` - Main class
- `gestor-instalador/index.php` - Web interface
- `gestor/modulos/dashboard/dashboard.php` - Automatic page removal

---

## 🔐 Authentication System

### Authentication Configuration
- **File**: `gestor/autenticacoes/localhost/autenticacao.php`
- **System**: JWT with persistent cookies
- **Main Function**: `usuario_gerar_token_autorizacao()`

### Login Process
1. Credential validation
2. JWT token generation
3. Persistent cookie definition
4. Redirection to dashboard

### SSL Keys
- **Generation**: Automated during installation
- **Protection**: Password via `OPENSSL_PASSWORD` variable
- **Location**: Defined in environment settings

---

## 🎨 Layout System

### Main Identified Layouts

#### Layout ID 1 - Administrative
- **Name**: "Manager Administrative Layout"
- **Usage**: Internal pages with sidebar and full menu
- **Features**: Sidebar menu, top profile, full navigation

#### Layout ID 23 - External Page
- **Name**: "No Permission Page Layout"
- **Usage**: External pages without administrative interface
- **Features**: Only logo and content, no administrative menu
- **Ideal for**: Installation, error, informational pages

### When to Use Each Layout
- **Layout 1**: Dashboard, administrative modules, internal pages
- **Layout 23**: Success, error, installation, informational pages

---

## 💾 Database

### Migrations and Seeds System
- **Location**: `gestor/db/`
- **Tool**: Phinx (PHP migrations)
- **Seeds**: Automatic initial data

### Main Identified Tables

#### 🎨 Presentation System
- **`layouts`**: Main templates with complete structure (header/footer)
- **`paginas`**: Specific content of each page (goes into `@[[pagina#corpo]]@`)
- **`componentes`**: Reusable pieces (alerts, forms, modals)

#### 👥 Users and Permissions
- `usuarios`: System users
- `usuarios_perfis`: Access profiles
- `usuarios_perfis_modulos`: Permissions per module

#### 🔧 Modules and System
- `modulos`: Available modules (specific programming)
- `modulos_grupos`: Module grouping
- `hosts`: Managed distributed hosts

#### 🌐 Host Versions (hosts_*)
- `hosts_paginas`: Pages of distributed hosts
- `hosts_layouts`: Layouts of distributed hosts
- `hosts_usuarios`: Users of external hosts

### 🔧 Dynamic Variable System

#### Variable Format
```php
@[[category#variable]]@
```

#### Common Variable Examples
```php
@[[pagina#url-raiz]]@       // System base URL
@[[pagina#corpo]]@          // Page content (CRITICAL in layouts)
@[[pagina#titulo]]@         // Page title
@[[usuario#nome]]@          // Logged user name
@[[pagina#css]]@            // Page specific CSS
@[[pagina#js]]@             // Page specific JavaScript
@[[pagina#menu]]@           // System menu
```

#### ⚠️ CRITICAL Variable in Layouts
**`@[[pagina#corpo]]@`** - This is the most important variable!
- **Function**: Place where page content is inserted into the layout
- **Usage**: Must be present in ALL layouts
- **Process**: gestor.php replaces with content from `paginas` table

---

## 📦 Modules and Features

### Dashboard (Main Module)
- **File**: `gestor/modulos/dashboard/dashboard.php`
- **Function**: Always loaded on login, main entry point
- **Features**:
  - Dynamic module menu
  - Toast system (notifications)
  - Update verification
  - **Automatic installation page removal**

#### Critical Function: dashboard_pagina_inicial()
```php
function dashboard_pagina_inicial(){
    // 1. Removes installation page if exists
    dashboard_remover_pagina_instalacao_sucesso();
    
    // 2. Loads interface components
    // 3. Includes system JavaScript
    // 4. Generates dynamic menu
    // 5. Displays system toasts
}
```

### 🔗 Page-Module Linking System

#### How Linking Works
1. **Page** has a field referencing a **Module** (optional)
2. **gestor.php** checks if page has linked module
3. If yes, **automatically includes** the module file
4. Module executes **specific programming** for the page

#### Practical Example: Dashboard
```
Page "dashboard" →  Linked to module "dashboard"
       ↓
gestor.php detects linking
       ↓
Automatically includes: gestor/modulos/dashboard/dashboard.php
       ↓
Executes: dashboard_pagina_inicial() and other functions
```

#### Modules Using cPanel
- **`host-configuracao`**: Main host configuration module
  - Uses cPanel libraries to create/manage accounts
  - FTP user creation
  - Domain configuration
  - Database management
- **Other modules** can use cPanel functions as needed

#### Module Structure
```php
// Example: gestor/modulos/dashboard/dashboard.php

// 1. Module configuration
$_GESTOR['modulo-id'] = 'dashboard';

// 2. Module specific functions
function dashboard_pagina_inicial() { ... }
function dashboard_menu() { ... }
function dashboard_toast() { ... }

// 3. Entry point/initialization
function dashboard_start() { ... }
dashboard_start(); // Automatically executed
```

### Toast System
- **Function**: Interface notifications
- **Types**: Success, error, information, update
- **Configuration**: Time, CSS class, progress
- **Usage**: Feedback to user about system actions

---

## ⚙️ Configuration and Environment

### config.php File
- **Location**: `gestor/config.php`
- **Function**: Central configuration loading
- **Format**: Uses native $_CONFIG system
- **Important**: DO NOT use hardcoded values, always via .env

### Environment Variables (.env)
```env
# Database
DB_HOST=localhost
DB_NAME=conn2flow
DB_USER=root
DB_PASS=password

# Security
OPENSSL_PASSWORD=ssl_keys_password
JWT_SECRET=jwt_key

# System
APP_ENV=local
DEBUG=true
```

### ⚠️ Important Rule: Configurations
**ALWAYS use configurations via .env/config.php, NEVER hardcoded values!**

---

## 🔧 Development and Debugging

### Development Environment
- **Docker**: Configuration available in `/docker`
- **cPanel**: Integrations in `/cpanel`
- **Logs**: Integrated logging system

### Routing Structure
- **Main File**: `gestor/gestor.php`
- **System**: Based on paths and modules
- **Controllers**: Per module in `gestor/modulos/`

### System Libraries
- **Location**: `gestor/bibliotecas/`
- **Main**: banco.php, autenticacao.php, formulario.php, **cpanel/**
- **Loading**: Automatic via `gestor_incluir_bibliotecas()`

### 🔧 cPanel Integration
- **Location**: `gestor/bibliotecas/cpanel/` (moved from root)
- **Function**: Connection between Conn2Flow and cPanel/WHM servers
- **Main Features**:
  - User account creation
  - Database creation  
  - FTP management
  - Domain configuration
  - Git creation and updates
  - Account suspension/reactivation

#### cPanel Integration Files
- **`cpanel-functions.php`**: Main functions (whm_query, cpanel_query, logs)
- **`cpanel-config.php`**: Connection settings
- **`cpanel-createacct.php`**: Account creation
- **`cpanel-createdb.php`**: Database creation
- **`cpanel-ftp-*.php`**: FTP management
- **`cpanel-git-*.php`**: Git operations
- **`logs/`**: cPanel operation logs

---

## 📚 Implementation History

### Installer Execution Order Fix (Completed)
**Date**: July 2025
**Problem**: Error 503 - "Configuration file (.env) not found for domain: localhost"
**Cause**: Auto-login executing before .env file is fully configured
**Solution**:
- Execution reordering: extract_files → migrations → seeds → auto-login
- Auto-login moved to after complete creation of .env and users in database
- Updated documentation with specific troubleshooting
- Prevention of configuration not found errors

### Modular Documentation System Creation (Completed)
**Date**: July 2025
**Objective**: Structure knowledge in specific files by area
**Implementation**:
- Creation of `CONN2FLOW-INSTALLER-DETAILED.md` with complete installer documentation
- Restructuring of main file as index and summaries
- Planning of future files by area (authentication, modules, database, cPanel, API)
- Modular system to avoid context overload
- Cross-references between documents

### cPanel Library Reorganization (Completed)
**Date**: July 2025
**Objective**: Move cPanel integration to organized structure
**Implementation**:
- Moving `/cpanel` to `gestor/bibliotecas/cpanel/`
- Documentation update with integration details
- Registration of main functions (whm_query, cpanel_query, cpanel_log)
- Documentation of modules using cPanel (host-configuracao)

### Auto-Login Implementation (Completed)
**Date**: July 2025
**Objective**: Automatic login after installation
**Implementation**:
- Added `createAdminAutoLogin()` function in installer
- Integration with `usuario_gerar_token_autorizacao()`
- Persistent cookie to maintain session
- Configuration via native environment

### Configuration System Fix (Completed)
**Problem**: Hardcoded values in installer
**Solution**:
- Migration to use native `config.php`
- Removal of manual $_CONFIG creation
- Integration with existing .env system
- SSL password correction via `OPENSSL_PASSWORD`

### Success Page Implementation (Completed)
**Objective**: Post-installation informational page with automatic removal
**Implementation**:
- Creation in `paginas` table (not `hosts_paginas`)
- Use of Layout ID 23 (without administrative menu)
- Function `dashboard_remover_pagina_instalacao_sucesso()`
- Informational toast on removal
- Integration in `dashboard_pagina_inicial()`

### Layout ID Fix (Completed)
**Problem**: Incorrect use of Layout ID 1 (administrative)
**Solution**: Change to Layout ID 23 (external without menu)
**Justification**: Success page is external, should not have administrative interface

### cPanel Library Reorganization (Completed)
**Date**: July 2025
**Objective**: Better organization and integration of cPanel features
**Implementation**:
- Moved `cpanel/` folder from root to `gestor/bibliotecas/cpanel/`
- Integration with manager library system
- Maintenance of all existing features
- Logs preserved in `gestor/bibliotecas/cpanel/logs/`

**Organized cPanel Features**:
- Account creation and management
- Database creation
- FTP operations (create user, change password)
- Git operations (create repository, updates)
- Account suspension/reactivation
- Plan/package change

---

## 🎯 Development Attention Points

### 1. Table Differentiation
- **Core**: `paginas`, `usuarios`, `modulos` (no prefix)
- **Host**: `hosts_paginas`, `hosts_usuarios` (with `hosts_` prefix)
- **Always check which table to use according to context!**

### 2. Layout Selection
- **ID 1**: Complete administrative interface
- **ID 23**: External pages without menu
- **Check page purpose before choosing layout**

### 3. Configuration System
- **Use**: `config.php` and .env variables
- **Avoid**: Hardcoded values anywhere
- **Check**: If configuration exists before using

### 4. Dashboard as Central Point
- **Always loaded**: On user login
- **Ideal place**: For automatic system operations
- **Main function**: `dashboard_pagina_inicial()`

### 5. Toasts for Feedback
- **Always use**: To inform actions to the user
- **Configure properly**: Time, class, message
- **Do not fail**: In case of error, capture exception

### 6. ⚠️ Layer Structure (CRITICAL)
- **Layout** contains `@[[pagina#corpo]]@` (mandatory)
- **Page** has content that goes into the body
- **Module** is optional, but executes specific programming
- **Components** are reusable and included when necessary

### 7. Table Relationships
```
layouts (1) ←→ (N) paginas ←→ (0..1) modulos
                ↓
            componentes (reusable)
```

### 8. ⚠️ Variable @[[pagina#corpo]]@ 
- **MANDATORY** in all layouts
- Place where page content is inserted
- Without it, the page does not render content

---

## 📝 Development Notes

### Identified Code Patterns
- **Naming**: Snake_case for functions, camelCase for variables
- **Structure**: Comments with separators `// =====`
- **Arrays**: Format `Array()` (old PHP)
- **Database**: Use of proprietary libraries (`banco_select`, `banco_delete`)

### Logging System
```php
$this->log("Message", 'LEVEL'); // WARNING, ERROR, INFO
```

### Redirection System
- **Dashboard**: Always `dashboard/` (with trailing slash)
- **Modules**: Based on module path
- **Pages**: Using `caminho` field from table

---

## 🚨 Common Troubleshooting

### Problem: Page not found
- **Check**: If it is in the correct table (`paginas` vs `hosts_paginas`)
- **Verify**: Field `status = 'A'` (active)
- **Validate**: Correct path in URL

### Problem: Incorrect Layout
- **ID 1**: For internal administrative pages
- **ID 23**: For external/informational pages
- **Check**: If layout exists in `layouts` table

### Problem: Configuration not working
- **Check**: If .env is being loaded
- **Verify**: If config.php is included
- **Validate**: If variable exists in $_CONFIG

### Problem: Toast does not appear
- **Check**: If dashboard_toast was called correctly
- **Verify**: If 'opcoes' is defined
- **Validate**: If JavaScript is included

---

## 📖 Quick References

### Important System Functions
```php
// Authentication
usuario_gerar_token_autorizacao($dados)
gestor_usuario() // Returns logged user data

// Database
banco_select(Array(...))
banco_delete(Array(...))
banco_campos_virgulas(Array(...))

// Interface
dashboard_toast(Array(...))
interface_componentes_incluir(Array(...))

// System
gestor_incluir_bibliotecas()
gestor_pagina_javascript_incluir($script)

// cPanel/WHM Integration
whm_query($params) // Executes WHM commands via API
cpanel_query($params) // Executes cPanel commands via API
cpanel_log($txt) // Specific cPanel log system
cpanel_find_error($xml) // cPanel error handling
```

### Specific cPanel Functions
```php
// Main available operations:
// - cpanel-createacct.php: Account creation
// - cpanel-createdb.php: Database creation
// - cpanel-ftp-add.php: FTP user addition
// - cpanel-ftp-passwd.php: FTP password change
// - cpanel-changepackage.php: Plan change
// - cpanel-suspendacct.php: Account suspension
// - cpanel-unsuspendacct.php: Account reactivation
// - cpanel-removeacct.php: Account removal
// - cpanel-git-create.php: Git repository creation
// - cpanel-git-updates.php: Git updates
```

### Important Global Variables
```php
$_GESTOR['url-raiz']          // System base URL
$_GESTOR['usuario-id']        // Logged user ID
$_GESTOR['host-id']           // Current host ID
$_GESTOR['modulo-id']         // Current module ID
$_GESTOR['pagina']            // Current page content
```

---

**Document maintained by**: GitHub Copilot AI
**Last update**: July 2025
**Version**: 1.0.0

> This document must be constantly updated as new knowledge about the Conn2Flow system is acquired. It is the knowledge base for continuity between different development sessions.
