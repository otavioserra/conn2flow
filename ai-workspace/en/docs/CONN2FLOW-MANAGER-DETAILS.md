# Conn2Flow - Manager Detailed Technical Documentation

## 📋 Index
- [🏗️ General Architecture](#🏗️-general-architecture)
  - [Directory Structure](#directory-structure)
  - [System Heart (gestor.php)](#system-heart-gestorphp)
  - [Layer System](#layer-system)
  - [Processing Flow](#processing-flow)
- [📚 Resource System](#📚-resource-system)
  - [Resource Structure](#resource-structure)
  - [Global Resources](#global-resources)
  - [Resources by Module](#resources-by-module)
  - [Resource Formatting](#resource-formatting)
  - [Physical Files](#physical-files)
  - [Creation/Consumption Dynamics](#creationconsumption-dynamics)
- [💾 Database](#💾-database)
  - [Data Structure](#data-structure)
  - [Migration System](#migration-system)
  - [Main Tables](#main-tables)
- [🔧 Configuration System](#🔧-configuration-system)
  - [config.php](#configphp)
  - [Environment Variables](#environment-variables)
  - [Multi-tenant](#multi-tenant)
- [📦 Plugins System](#📦-plugins-system)
  - [Plugins Architecture](#plugins-architecture)
  - [Installation Process](#installation-process)
- [🔐 Security](#🔐-security)
  - [Authentication](#authentication)
  - [Authorization](#authorization)
- [🌐 Web System](#🌐-web-system)
  - [Routing](#routing)
  - [Cache and Performance](#cache-and-performance)
- [📝 Template System](#📝-template-system)
  - [Dynamic Variables](#dynamic-variables)
  - [Processing](#processing)
- [🎮 Controllers](#🎮-controllers)
  - [System Controllers](#system-controllers)
  - [Module Controllers](#module-controllers)
- [📚 Libraries](#📚-libraries)
  - [Core Libraries](#core-libraries)
  - [Specialized Libraries](#specialized-libraries)
- [🔍 Development](#🔍-development)
  - [Dev Environment](#dev-environment)
  - [Debugging](#debugging)
  - [Tools](#tools)
- [📖 Quick References](#📖-quick-references)
  - [Important Functions](#important-functions)
  - [Global Variables](#global-variables)
  - [Data Structures](#data-structures)

---

## 🏗️ General Architecture

### Directory Structure
```
conn2flow/
	├── gestor/                         # 🏠 Main system core
	│   ├── config.php                  # ⚙️ Central configurations and .env
	│   ├── gestor.php                  # ❤️ SYSTEM HEART - Main router
	│   ├── modulos/                    # 📦 System modules
	│   ├── bibliotecas/                # 📚 30+ system libraries
	│   ├── controladores/              # 🎮 Specific controllers
	│   ├── db/                         # 💾 Database
	│   │   ├── data/                   # 📄 Initial data (JSON)
	│   │   └── migrations/             # 🔄 Phinx Migrations
	│   ├── assets/                     # 🎨 Static files
	│   ├── contents/                   # 📝 Managed content
	│   ├── logs/                       # 📋 System logs
	│   ├── resources/                  # 📚 Global resources
	│   └── vendor/                     # 📦 Composer dependencies
	├── gestor-instalador/              # 📦 System installer
	├── dev-environment/                # 🐳 Docker Environment
	└── ai-workspace/                   # 🤖 Development tools
```

### System Heart (gestor.php)

The **`gestor.php`** is the absolute **HEART** of the Conn2Flow system:

#### 🎯 Main Functionalities:
- **🛣️ Main Router**: Processes ALL HTTP requests
- **📁 Static File Manager**: CSS, JS, images with optimized cache
- **🚀 Process Initiator**: Web application entry point
- **🔗 Component Connector**: Links layouts, pages, modules, and components
- **🔐 Session System**: Manages authentication and user state
- **🔄 Variable Processor**: Replaces `@[[variable-id]]@` dynamically

#### ⚡ Initialization Process:
1. **Loads configurations** (`config.php`)
2. **Processes URL** and identifies route
3. **Verifies authentication** and permissions
4. **Loads layout** of the requested page
5. **Processes variables** dynamically
6. **Includes components** needed
7. **Renders HTML** final

### Layer System

The system uses an intelligent **4-layer** architecture:

#### 1. 🏗️ **LAYOUTS** (Table: `layouts`)
- **Function**: Structure that repeats (header/footer)
- **Content**: Complete HTML with dynamic variables
- **Critical Variable**: `@[[page#body]]@` - where content is inserted
- **Fields**: `id`, `html`, `css`, `framework_css`, `layout_id`
- **Inclusion**: Automatic on every page

#### 2. 📄 **PAGES** (Table: `pages`)
- **Function**: Specific content that goes into the "body" of the page
- **Linking**: Each page has an associated layout (`layout_id`)
- **Routing**: `path` field defines URL in browser
- **Content**: Specific HTML (goes into `@[[page#body]]@`)
- **Fields**: `id`, `html`, `css`, `path`, `layout_id`, `title`

#### 3. 🧩 **COMPONENTS** (Table: `components`)
- **Function**: Reusable interface elements
- **Examples**: Alerts, forms, modals, buttons, menus
- **Usage**: Included via `@[[component#name]]@`
- **Fields**: `id`, `html`, `css`, `module`, `component_id`
- **Inclusion**: Dynamic by variables or programmatic

#### 4. 📦 **MODULES** (Directory: `gestor/modulos/`)
- **Function**: Business logic and specific processing
- **Structure**: Own folder with PHP/JS files
- **Fields**: `id`, `name`, `title`, `icon`, `module_group_id`, `plugin`
- **Integration**: Connect layouts/pages via variables

### Processing Flow

```
🌐 HTTP Request
       ↓
🏠 gestor.php (HEART)
       ↓
🛣️ Routing → Identifies page by path
       ↓
📄 Search Page → Table `pages`
       ↓
🏗️ Search Layout → Table `layouts` (linked)
       ↓
📦 Search Module → `modulos/` (if linked)
       ↓
🔄 Process Variables → Replaces @[[variables]]@
       ↓
🧩 Include Components → Table `components`
       ↓
🎨 Render → Final HTML for browser
```

---

## 📚 Resource System

### Resource Structure

The system has **2 types of resources**:

#### 🌍 **Global Resources** (`gestor/resources/`)
```
gestor/resources/
├── lang/                      # Lang folder, for Brazilian Portuguese use `pt-br`
│   ├── components/            # Global components
│   ├── layouts/               # Global layouts
│   ├── pages/                 # Global pages
│   ├── components.json        # Components mapping
│   ├── layouts.json           # Layouts mapping
│   ├── pages.json             # Pages mapping
│   └── variables.json         # Global variables
└── resources.map.php          # General mapping of each language
```
- resources.map.php:
```php
$resources = [
	'languages' => [
        'lang-slug' => [ // ex: 'pt-br', 'en-us', etc.
            'name' => 'Language Name',
            'data' => [ // Location of JSON files relative to each `lang-slug` folder
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'variables' => 'variables.json',
            ],
            'version' => '1',
        ],
    ],
];
```

#### 📦 **Resources by Module** (`modulos/{module-id}/resources/`)
```
modulos/{module-id}/resources/
├── {module-id}.json               # Module configurations
├── resources/                     # Specific resources
│   └── lang/
│       ├── components/
│       ├── layouts/
│       └── pages/
```

### Resource Formatting

#### 📋 Base JSON Structure:
```json
{
    "name": "name",           // SQL table 'name' field
    "id": "id",              // SQL table 'id' field
    "version": "1.0",        // Automatically generated
    "checksum": {            // Automatically generated
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🏗️ Specific Layout:
```json
{
    "name": "name",
    "id": "id",
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 📄 Specific Page:
```json
{
    "name": "name",
    "id": "id",
    "layout": "layout-id",
    "path": "path/",
    "type": "system",        // "sistema" → "system", "pagina" → "page"
    "option": "option",      // OPTIONAL
    "root": true,            // If "root" = '1', i.e., in a redirect to root, this page will be the root.
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🧩 Specific Component:
```json
{
    "name": "name",
    "id": "id",
    "version": "1.0",
    "checksum": {
        "html": "",
        "css": "",
        "combined": ""
    }
}
```

#### 🔧 Specific Variable:
```json
{
    "id": "id",
    "value": "value",
    "type": "string"         // string, text, bool, number, etc.
}
```

### Physical Files

#### 📁 Storage Structure:
```
resource_folder/                    # layouts, pages, components
├── {resource-id}/                  # Folder with resource ID
│   ├── {resource-id}.html          # Resource HTML (optional)
│   └── {resource-id}.css           # Resource CSS (optional)
```

#### ⚠️ Important Rules:
- **Mandatory ID**: Same as JSON `id` field
- **Optional files**: HTML and CSS can exist separately
- **Processing**: System searches for physical file based on ID

### Creation/Consumption Dynamics

#### 🔄 Resource Process:

1. **📝 Creation/Modification**:
   - **Physical files**: HTML/CSS saved in files
   - **Metadata**: Stored in JSON files
   - **Variables**: Complete content in JSON

2. **⚙️ Processing**:
   - **Script**: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
   - **GitHub Action**: Called automatically on releases
   - **Development**: Can be run manually

3. **💾 Consumption**:
   - **Not direct**: JSONs and physical files are not consumed directly
   - **Database**: Processed and stored in specific tables and therefore, consumed via SQL
   - **Debug mode**: Exception for development

4. **📊 Destination Tables**:
   - `layouts`: Reusable page structures
   - `pages`: Specific page content
   - `components`: Reusable interface elements
   - `variables`: Dynamic system variables

---

## 💾 Database

### Data Structure

#### 📂 Organization:
```
gestor/db/
├── data/                          # 📄 Initial data/updates (JSON)
│   ├── ModulosData.json           # Modules data
│   ├── PaginasData.json           # Pages data
│   └── ...
└── migrations/                    # 🔄 Phinx Migrations
    ├── 001_create_modulos_table.php
    └── ...
```

### Migration System

#### 🛠️ Phinx Framework:
```php
final class CreateModulosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('modulos', ['id' => 'id_modulos']);
        $table->addColumn('id_modulos_grupos', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['limit' => 255])
              ->addColumn('id', 'string', ['limit' => 255])
              ->create();
    }
}
```

#### ✨ Functionalities:
- **📈 Versioning**: Complete schema control
- **🔙 Rollback**: Reversion of changes
- **🌱 Seeds**: Initial data via JSON. IMPORTANT: updates also use the same format.
- **⚡ Migrations**: Programmatic table structure

### Main Tables

#### 🎨 **Presentation System**:
- **`layouts`**: Main templates (header/footer)
- **`pages`**: Specific page content
- **`components`**: Reusable elements used as blocks within pages and layouts.

#### 👥 **Users and Permissions**:
- **`users`**: User data
- **`users_profiles`**: Access profiles
- **`users_profiles_modules`**: Permissions per module
- **`sessions`**: Active sessions
- **`tokens`**: API tokens

#### 📦 **Modules and System**:
- **`modules`**: Available modules
- **`modules_groups`**: Module grouping
- **`plugins`**: Installed plugins

#### 🔧 **Others**:
- **`variables`**: System variables
- **`history`**: Action log
- **`files`**: File management

---

## 🔧 Configuration System

### config.php

#### ⚙️ Intelligent Loading:
```php
// Loads .env based on domain
$dotenv = Dotenv\Dotenv::createImmutable($_GESTOR['AUTH_PATH_SERVER']);
$dotenv->load();

// Database configurations via .env
$_BANCO = [
    'tipo'    => $_ENV['DB_CONNECTION'] ?? 'mysqli',
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'nome'    => $_ENV['DB_DATABASE'] ?? '',
    'usuario' => $_ENV['DB_USERNAME'] ?? '',
    'senha'   => $_ENV['DB_PASSWORD'] ?? '',
];
```

#### 🎯 Functionalities:
- **🌐 Domain detection**: Configurations per environment
- **🔐 Security**: Secure credential loading
- **📦 Dependencies**: Automatic library inclusion
- **⚡ Performance**: Intelligent configuration cache

### Environment Variables

#### 📄 .env Structure:
```env
# 🗄️ Database
DB_CONNECTION=mysqli
DB_HOST=localhost
DB_DATABASE=conn2flow
DB_USERNAME=root
DB_PASSWORD=password

# 🔐 Security
OPENSSL_PASSWORD=ssl_password
JWT_SECRET=jwt_key

# ⚙️ System
APP_ENV=local
DEBUG=true
```

#### 📂 Location:
- Automatic detection based on access domain:
```
gestor/autenticacoes/
├── domain1.com/
│   └── .env
├── domain2.com/
│   └── .env
└── localhost/
    └── .env
```

### Multi-tenant

#### 🏢 Complete Isolation:
- **🌐 Per domain**: `$_SERVER['SERVER_NAME']`
- **📁 .env Files**: Specific per environment
- **🗄️ Database**: Complete isolation between instances
- **🔧 Configurations**: Independent per tenant

---

## 📦 Plugins System

### Plugins Architecture

#### 📂 Structure:
```
plugins/
├── plugin-id/
│   ├── manifest.json          # 📋 Plugin metadata
│   ├── controllers/           # 🎮 Specific controllers
│   ├── db/                    # 💾 Database
│   │   ├── migrations/        # 🔄 Plugin migrations
│   │   └── data/              # 📄 Initial data
│   ├── modules/               # 📦 Plugin modules
│   ├── resources/             # 📚 Visual resources
│   └── assets/                # 🎨 Static files
```

#### 🔗 Integration:
- **📦 Isolation**: Plugins completely isolated
- **🔄 Migrations**: Automatic on installation/updates
- **📚 Resources**: Own layouts, pages, components
- **🎯 API**: Integration with main system

### Installation Process

#### 📋 Steps:
1. **📥 Download**: Plugin ZIP
2. **📦 Extraction**: To staging directory
3. **🔄 Migrations**: Automatic execution
4. **📊 Data**: Data synchronization
5. **✅ Activation**: Plugin operational

---

## 🔐 Security

### Authentication

#### 🛡️ Mechanisms:
- **🔑 JWT**: Secure tokens with expiration
- **🍪 Sessions**: Complete management with garbage collector
- **🔒 Cookies**: HTTPOnly, Secure, SameSite
- **🔐 OpenSSL**: Private key encryption

### Authorization

#### 👥 Access Control:
- **👤 Profiles**: Granular control per user
- **📦 Modules**: Specific permissions
- **🌐 Hosts**: Multi-tenant isolation
- **🔧 Functions**: Fine control of functionalities

---

## 🌐 Web System

### Routing

#### 🛣️ Functionalities:
- **🔗 Clean URLs**: No query strings
- **📄 Page-based**: `path` field of `pages` table
- **📁 Static files**: Complete support
- **🔄 Redirects**: Automatic 301

### Cache and Performance

#### ⚡ Optimizations:
- **🏷️ ETags**: Intelligent static file cache
- **🗜️ Compression**: Automatic content compression
- **🎨 Assets**: Automatic optimization
- **📈 Performance**: Frequent query cache

---

## 📝 Template System

### Dynamic Variables

#### 🔄 Storage Format (Backend):
```html
@[[variable-id]]@
```

**Important**: This format `@[[...]]@` is used internally by the system:
- ✅ Database
- ✅ Resource files (`.html`, `.css`)
- ✅ Processing by `gestor.php`

#### ✏️ Editing Format (Frontend):
```html
[[variable-id]]
```

**Important**: This format `[[...]]` (without `@`) is used for editing:
- ✅ User interface
- ✅ Edit forms
- ✅ Visual HTML editor

#### 🔄 Automatic Conversion:
- **Load**: `@[[variable]]@` → `[[variable]]` (Backend → Frontend)
- **Save**: `[[variable]]` → `@[[variable]]@` (Frontend → Backend)
- **Implementation**: Middleware in modules (e.g., `admin-templates.php`)

#### 📋 Main Global Examples:
```html
@[[page#root-url]]@          <!-- System base URL -->
@[[page#body]]@              <!-- PAGE CONTENT (CRITICAL!) -->
@[[user#name]]@              <!-- Logged user name -->
@[[page#title]]@             <!-- Page title -->
@[[component#menu]]@         <!-- System menu -->
```

**Note**: Complete documentation in [`CONN2FLOW-GLOBAL-VARIABLES.md`](CONN2FLOW-GLOBAL-VARIABLES.md)

#### ⚠️ CRITICAL Variable:
**`@[[page#body]]@`** - This is the most important one!
- **📍 Location**: Where page content is inserted into the layout
- **🔧 Usage**: Must be present in ALL layouts
- **⚙️ Process**: gestor.php replaces with content from `pages` table

### Processing

#### 🔄 Functionalities:
- **⚡ Real-time**: Dynamic replacement
- **🔀 Conditionals**: Conditional logic support
- **📦 Per module**: Specific variables
- **💾 Cache**: Intelligent for performance

---

## 🎮 Controllers

### System Controllers

#### 📂 Location: `gestor/controladores/`

#### 🔧 Main:
- **`static-file.php`**: Serves static files with cache
- **`plugin-update.php`**: Plugin installation/update
- **`database-updates.php`**: System migrations and updates
- **`gateways-platform.php`**: Payment processing

#### 🎯 Functionalities:
- **🔗 Special URLs**: `_gateways`, webhooks, etc.
- **🌐 REST APIs**: Endpoints for integrations
- **📨 Webhooks**: External notification receipt
- **⏰ CRON jobs**: Scheduled tasks

### Module Controllers

#### 📂 Location: `modulos/{module-id}/`

#### 📋 Typical Structure:
```
module-id/
├── module-id.php           # 🔧 Backend logic (PHP)
├── module-id.js            # 🎨 Frontend logic (JavaScript)
├── module-id.json          # ⚙️ Configurations and metadata
└── resources/              # 📚 Visual resources
```

#### 🔄 Process:
1. **🔗 Linking**: Page references module
2. **📦 Inclusion**: gestor.php automatically includes
3. **⚙️ Initialization**: `start()` function executed
4. **🔧 Processing**: Module specific logic

---

## 📚 Libraries

### Core Libraries

#### 💾 **banco.php** - Data Layer:
```php
// Automatic connection and reconnection
// Complete CRUD (select, insert, update, delete)
// Error handling and debug
// Utility functions (escape, stripslashes, etc.)
```

#### 🏠 **gestor.php** - Main System:
```php
gestor_componente()           // Loads components
gestor_layout()              // Loads layouts
gestor_variaveis()           // Variable system
// Session and authentication system
```

#### 📝 **modelo.php** - Templates:
```php
// Variable replacement
// HTML tag manipulation
// Text processing functions
```

#### 👤 **usuario.php** - Authentication:
```php
usuario_gerar_token_autorizacao()  // JWT
// OpenSSL encryption
// Authentication and authorization
```

### Specialized Libraries

#### 🛠️ Utilities:
- **`html.php`**: DOM manipulation with XPath
- **`comunicacao.php`**: APIs and external communication
- **`formulario.php`**: Form processing
- **`log.php`**: Logging system

#### 📊 Specialized:
- **`pdf.php`**: PDF generation (FPDF)
- **`ftp.php`**: File transfer
- **`paypal.php`**: PayPal integration

---

## 🔍 Development

### Dev Environment

#### 🐳 Docker Environment:
- **📂 Location**: `dev-environment/docker/`
- **🔄 Synchronization**: Automated scripts
- **📊 Logs**: Integrated Apache and PHP
- **💾 Database**: Containerized MySQL

#### 🔧 Configurations:
- **📁 Loading**: Files instead of database
- **🔄 Hot reload**: Automatic
- **🐛 Debug**: Detailed
- **📋 Logs**: Structured

### Debugging

#### 🛠️ Tools:
- **📊 Structured logs**: Integrated system
- **🐛 Debug mode**: File loading
- **📈 Profiling**: Performance analysis
- **🔍 Inspection**: Variable state

### Tools

#### 🤖 AI Workspace:
- **📂 Location**: `ai-workspace/`
- **📚 Documentation**: Guides and references
- **🔧 Scripts**: Task automation
- **📋 Templates**: Standard structures

#### 🔄 Synchronization:
- **🐳 Docker**: Containerized environment
- **📦 Scripts**: Synchronization automation
- **🧪 Tests**: Test structure
- **📊 Monitoring**: Performance and logs

---

## 📖 Quick References

### AJAX Requests and JavaScript

#### 🎯 **Global Variable `gestor`**
The `gestor` object is dynamically created by `gestor.php` and contains essential information:

```javascript
// Automatically created by the system:
var gestor = {
    raiz: '/instalador/',           // System root URL ($_GESTOR['url-raiz'])
    moduloId: 'admin-arquivos',     // Current module ID ($_GESTOR['modulo-id'])
    moduloOpcao: 'listar-arquivos', // Current option ($_GESTOR['opcao'])
    moduloCaminho: 'admin-arquivos/' // Module path
};
```

#### 📡 **AJAX Request Structure**
**MANDATORY STANDARD** for all AJAX requests in the Manager:

```javascript
$.ajax({
    type: 'POST',
    url: gestor.raiz + gestor.moduloId + '/',  // Dynamic URL
    data: {
        ajax: 'sim',                           // Always 'sim' for AJAX
        ajaxOpcao: 'function-name',            // ⚠️ DO NOT use 'ajax-opcao'
        // ... other specific parameters
    },
    dataType: 'json',
    beforeSend: function(){
        $('#gestor-listener').trigger('carregar_abrir');  // Loading
    },
    success: function(dados){
        switch(dados.status){
            case 'Ok':
                // Success
                break;
            case 'success':
                // Alternative success
                break;
            case 'error':
                // Specific error
                break;
            default:
                console.log('ERROR - ajaxOpcao - '+dados.status);
        }
        $('#gestor-listener').trigger('carregar_fechar');  // Close loading
    },
    error: function(txt){
        switch(txt.status){
            case 401: 
                // Unauthorized - redirect to login
                window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); 
                break;
            default:
                console.log('ERROR AJAX - ajaxOpcao - Data:');
                console.log(txt);
                $('#gestor-listener').trigger('carregar_fechar');
        }
    }
});
```

#### ⚠️ **COMMON ERRORS to AVOID**:
```javascript
// ❌ WRONG - Do not use FormData for simple data
var formData = new FormData();
formData.append('ajax', 'true');  // ❌ 'true' instead of 'sim'
formData.append('ajax-opcao', 'function');  // ❌ 'ajax-opcao' instead of 'ajaxOpcao'

// ❌ WRONG - Do not use window.location.href
url: window.location.href,  // ❌ Incorrect URL

// ❌ WRONG - Do not handle errors adequately
error: function() {
    showMessage('error', 'Error');  // ❌ Generic handling
}
```

#### 🎨 **Response Handling**
```javascript
// ✅ CORRECT - Complete handling
success: function(dados){
    switch(dados.status){
        case 'Ok':      // Standard for success operations
        case 'success': // Alternative for specific operations
            // Process data
            break;
        case 'error':
            // Show specific error
            break;
        default:
            // Log for debug
            console.log('Unknown status:', dados.status);
    }
}
```

#### 🌐 **URL Mapping**
- **Physical URL**: `http://localhost/instalador/admin-environment/`
- **Logical URL**: `/instalador/` (defined in `.env` as `URL_ROOT`)
- **Module**: `admin-environment`
- **PHP File**: `gestor/modulos/admin-environment/admin-environment.php`

#### 📂 **Complete Module Structure**
```
gestor/modulos/{module-id}/
├── {module-id}.php              # 🔧 Backend logic (PHP)
├── {module-id}.js               # 🎨 Frontend logic (JavaScript)
├── {module-id}.json             # ⚙️ Configurations and metadata
└── resources/                   # 📚 Visual resources
    ├── {module-id}.html         # 📄 Page template
    └── lang/
        └── pt-br/
            └── pages/
                └── {module-id}/
                    └── {module-id}.html
```

#### 🔧 **Module Start Function**
```php
function {module-id}_start(){
    global $_GESTOR;
    
    gestor_incluir_bibliotecas();  // ⚠️ ALWAYS include first
    
    if($_GESTOR['ajax']){
        interface_ajax_iniciar();
        
        switch($_GESTOR['ajax-opcao']){  // ⚠️ 'ajax-opcao' (with hyphen)
            case 'salvar': {module-id}_ajax_salvar(); break;
            case 'testar': {module-id}_ajax_testar(); break;
        }
        
        interface_ajax_finalizar();
    } else {
        {module-id}_interfaces_padroes();
        
        interface_iniciar();
        
        switch($_GESTOR['opcao']){
            case 'raiz': {module-id}_raiz(); break;
        }
        
        interface_finalizar();
    }
}
```

### JavaScript Variable System

#### 🌐 **How It Works**
The system dynamically creates a global `gestor` object with all necessary variables:

```php
// In gestor.php, automatic creation:
$variaveis_js = Array(
    'raiz' => $_GESTOR['url-raiz'],           // '/instalador/'
    'moduloId' => $_GESTOR['modulo-id'],      // 'admin-environment'
    'moduloOpcao' => $_GESTOR['opcao'],       // 'raiz'
    'moduloCaminho' => $caminho,              // 'admin-environment/'
    // + custom module variables
);

$js_global_vars = '<script>
    var gestor = '.json_encode($variaveis_js, JSON_UNESCAPED_UNICODE).';
</script>';
```

#### 🎯 **Essential Variables**
```javascript
gestor.raiz           // Root URL: '/instalador/'
gestor.moduloId       // Module ID: 'admin-arquivos'
gestor.moduloOpcao    // Current option: 'upload'
gestor.moduloCaminho  // Path: 'admin-arquivos/'
```

#### 📦 **Custom Variables per Module**
```php
// In module, add specific variables:
$_GESTOR['javascript-vars']['arquivosCel'] = gestor_pagina_variaveis_globais(Array('html'=>$filesCel));
$_GESTOR['javascript-vars']['totalPaginas'] = 5;
$_GESTOR['javascript-vars']['config'] = Array(
    'maxSize' => '10MB',
    'allowedTypes' => ['jpg', 'png', 'pdf']
);
```

### Important Functions

#### 👤 Authentication:
```php
usuario_gerar_token_autorizacao($dados)  // JWT
gestor_usuario()                        // Logged user data
```

#### 💾 Database:
```php
banco_select(Array(...))                // SELECT
banco_insert(Array(...))                // INSERT
banco_update(Array(...))                // UPDATE
banco_delete(Array(...))                // DELETE
```

#### 🎨 Interface:
```php
gestor_componente(Array(...))           // Include component
interface_toast(Array(...))             // Notifications
```

#### ⚙️ System:
```php
gestor_incluir_bibliotecas()            // Load libraries
gestor_pagina_javascript_incluir()      // Include JS
```

### Global Variables

#### 🌐 System:
```php
$_GESTOR['url-raiz']                    // System base URL
$_GESTOR['usuario-id']                  // Logged user ID
$_GESTOR['modulo-id']                   // Current module ID
```

#### 📄 Page:
```php
$_GESTOR['pagina']                      // Page content
$_GESTOR['layout']                      // Current layout
```

### Data Structures

#### 📊 Configuration Arrays:
```php
$_BANCO = [...]                         // Database configurations
$_CONFIG = [...]                        // System configurations
$_GESTOR = [...]                        // Global variables
```

#### 📋 Module Structure:
```php
$_GESTOR['modulo-id'] = 'dashboard';
function dashboard_start() { ... }       // Initialization
function dashboard_pagina_inicial() { ... } // Specific logic
```

#### 📋 Read Module Component:
```php
$componenteHTML = gestor_componente(Array(
  'id' => 'id',
  'modulo' => $_GESTOR['modulo-id'],
));
```

#### 📋 Swap Variable in HTML:
```php
// Example of variable swap in HTML
$html = modelo_var_troca_tudo($html,'#variable#',$value);
// Example of variable swap in current page:
$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#variable#',$value);
```
- Practical example:
  - Before:
```html
<p>Hello, #name#!</p>
```
```php
$html = modelo_var_troca_tudo($html,'#name#','John');
```
  - After:
```html
<p>Hello, John!</p>
```

#### 📋 Repetition cells in HTML:
```html
<div class="ui celled list">
    <!-- cel-id < -->
    <div class="item">
        <div class="content">
            <div class="header">#name#</div>
            <div class="description">#type# - #url#</div>
        </div>
    </div>
    <!-- cel-id > -->
</div>
```
```php
// Usage example
$cel_name = 'cel-id'; $cel[$cel_name] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_name.' < -->','<!-- '.$cel_name.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_name.' < -->','<!-- '.$cel_name.' > -->','<!-- '.$cel_name.' -->');

$result = [
    ['name'=>'Google','type'=>'Search Engine','url'=>'https://www.google.com'],
    ['name'=>'Facebook','type'=>'Social Media','url'=>'https://www.facebook.com'],
    ['name'=>'Twitter','type'=>'Social Media','url'=>'https://www.twitter.com'],
];

foreach($result as $res){
    $cel_aux = $cel[$cel_name];

    $html = modelo_var_troca_tudo($html,'#name#',$res['name']);
    $html = modelo_var_troca_tudo($html,'#type#',$res['type']);
    $html = modelo_var_troca_tudo($html,'#url#',$res['url']);

    $_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_name.' -->',$cel_aux);
}

$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_name.' -->','');
```

```js
var data = { 
					opcao : opcao,
					ajax : 'sim',
