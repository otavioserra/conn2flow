# Conn2Flow - Global Variables Glossary

## 📋 Table of Contents
- [🎯 Introduction](#🎯-introduction)
- [⚡ Quick Reference](#⚡-quick-reference)
- [📝 Syntax and Format](#📝-syntax-and-format)
- [📚 Variable Categories](#📚-variable-categories)
  - [Page Variables (pagina#)](#page-variables-pagina)
  - [User Variables (usuario#)](#user-variables-usuario)
  - [System Variables (gestor#)](#system-variables-gestor)
  - [Widget Variables (widgets#)](#widget-variables-widgets)
- [🔍 Technical Reference](#🔍-technical-reference)
- [💡 Usage Examples](#💡-usage-examples)

---

## 🎯 Introduction

This document is a **complete glossary** of the Conn2Flow system's **Global Variables**. These variables are dynamically processed by the system core (`gestor.php`) and allow dynamic content injection in layouts, pages, and components.

### What are Global Variables?

**Global Variables** are special markers in the format `@[[FUNCTION#VARIABLE]]@` that are **replaced at runtime** with dynamic system values. They allow HTML templates to be reusable and adaptive without the need for embedded PHP code.

### Processing Architecture

1. **HTTP Request** → `gestor.php` receives the request
2. **Loading** → Layout and page are loaded from the database
3. **Detection** → System scans HTML for `@[[...]]@` patterns
4. **Substitution** → Each variable is replaced by its real value
5. **Rendering** → Final HTML is sent to the browser

---

## ⚡ Quick Reference

Quick reference of all global variables available in the system:

### Page Variables
1. `@[[pagina#corpo]]@` - Marks where page content should be inserted in the layout
2. `@[[pagina#titulo]]@` - Page title (used in `<title>` and breadcrumbs)
3. `@[[pagina#menu]]@` - Dynamically generated system main menu
4. `@[[pagina#url-raiz]]@` - System base URL (application root)
5. `@[[pagina#url-full-http]]@` - Full URL including protocol and domain
6. `@[[pagina#url-caminho]]@` - Current page relative path (without domain)
7. `@[[pagina#contato-url]]@` - System contact page URL
8. `@[[pagina#modulo-id]]@` - ID of the module associated with the current page
9. `@[[pagina#registro-id]]@` - ID of the record being viewed/edited

### User Variables
10. `@[[usuario#nome]]@` - Full name of the authenticated user

### System Variables
11. `@[[gestor#versao]]@` - Current installed Conn2Flow version

### Widget Variables
12. `@[[widgets#WIDGET_ID]]@` - Includes a specific widget on the page (replace WIDGET_ID with actual identifier)

---

## 📝 Syntax and Format

### Standard Format
```
@[[CATEGORY#IDENTIFIER]]@
```

### Syntax Components

| Element | Description | Example |
|---------|-------------|---------|
| `@[[` | Opening delimiter (security) | `@[[` |
| `CATEGORY` | Variable type/function | `pagina`, `usuario`, `gestor` |
| `#` | Category/identifier separator | `#` |
| `IDENTIFIER` | Specific variable name | `titulo`, `nome`, `versao` |
| `]]@` | Closing delimiter (security) | `]]@` |

### Processing Rules

1. **Case-Sensitive**: `pagina#titulo` ≠ `pagina#Titulo`
2. **Processing Order**: Global variables → Module variables → Custom variables
3. **Security Protection**: The `@` delimiters are mandatory in the backend (database)
4. **Clean Interface**: In the frontend (visual editor), users see only `[[...]]` (without `@`)

### 🔄 Storage Format vs. Editing Format

The Conn2Flow system uses **two different formats** for variables depending on the context:

#### 📦 **Storage Format (Backend/Database)**
- **Format**: `@[[CATEGORY#IDENTIFIER]]@`
- **Context**: Database, resource files, internal processing
- **Example**: `@[[pagina#titulo]]@`, `@[[usuario#nome]]@`
- **Function**: Safe format for storage and system processing

#### ✏️ **Editing Format (Frontend/User)**
- **Format**: `[[CATEGORY#IDENTIFIER]]` (without the `@`)
- **Context**: Editing interface, forms, visual editors
- **Example**: `[[pagina#titulo]]`, `[[usuario#nome]]`
- **Function**: Clean and user-friendly interface

#### 🔄 **Conversion Flow**

```
┌─────────────────────────────────────────────────────────┐
│  DATABASE → FRONTEND (Load for Editing)               │
├─────────────────────────────────────────────────────────┤
│  @[[pagina#titulo]]@  →  [[pagina#titulo]]              │
│  (Remove @ delimiters)                                   │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  FRONTEND → DATABASE (Save Changes)                    │
├─────────────────────────────────────────────────────────┤
│  [[pagina#titulo]]  →  @[[pagina#titulo]]@              │
│  (Add @ delimiters)                                      │
└─────────────────────────────────────────────────────────┘
```

#### 🛠️ **Technical Implementation**

The conversion middleware is implemented in modules through functions like `admin_templates_editar()` in file `gestor/modulos/admin-templates/admin-templates.php`:

```php
// === LOADING DATA FROM DATABASE (Backend → Frontend) ===
// Remove @ for user editing
$html_clean = str_replace('@[[', '[[', $html_database);
$html_clean = str_replace(']]@', ']]', $html_clean);

// === SAVING TO DATABASE (Frontend → Backend) ===
// Add @ before persisting
$open = $_GESTOR['variavel-global']['open'];      // '@[['
$close = $_GESTOR['variavel-global']['close'];    // ']]@'
$openText = $_GESTOR['variavel-global']['openText'];  // '[['
$closeText = $_GESTOR['variavel-global']['closeText']; // ']]'

$_REQUEST['html'] = preg_replace(
    "/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/", 
    strtolower($open."$1".$close), 
    $_REQUEST['html']
);
```

#### ⚠️ **Important Rules**

1. **Backend (Database/Resources)**: ALWAYS use `@[[...]]@`
2. **Frontend (User Interface)**: ALWAYS use `[[...]]` (without `@`)
3. **Automatic Conversion**: Modules must implement conversion middleware
4. **System Processing**: `gestor.php` only processes variables with `@[[...]]@`
5. **Physical Resources**: `.html` and `.css` files in `gestor/resources/` use `@[[...]]@`

---

## 📚 Variable Categories

### Page Variables (pagina#)

Variables related to the current page context and navigation.

#### `@[[pagina#corpo]]@`
- **Type**: Structural
- **Description**: Marks where page content should be inserted in the layout
- **Context**: Mandatory in all layouts
- **Processing**: Function `gestor_pagina_variaveis()`
- **Code line**: `gestor.php:451`
- **Example**:
  ```html
  <div class="main-content">
      @[[pagina#corpo]]@
  </div>
  ```

#### `@[[pagina#titulo]]@`
- **Type**: Metadata
- **Description**: Page title (used in `<title>` and breadcrumbs)
- **Source**: `titulo` field from `paginas` table
- **Processing**: Function `gestor_pagina_variaveis()`
- **Code line**: `gestor.php:447, 487`
- **Example**:
  ```html
  <title>@[[pagina#titulo]]@ - Conn2Flow</title>
  <h1>@[[pagina#titulo]]@</h1>
  ```

#### `@[[pagina#menu]]@`
- **Type**: Dynamic Component
- **Description**: System main menu (dynamically generated)
- **Source**: Function `gestor_pagina_menu()` based on permissions
- **Processing**: Loads modules, groups, and user permissions
- **Code line**: `gestor.php:483`
- **Example**:
  ```html
  <nav class="sidebar">
      @[[pagina#menu]]@
  </nav>
  ```

#### `@[[pagina#url-raiz]]@`
- **Type**: URL
- **Description**: System base URL (application root)
- **Source**: Global variable `$_GESTOR['url-raiz']`
- **Processing**: Configured in `config.php`
- **Code line**: `gestor.php:484`
- **Example**:
  ```html
  <link rel="stylesheet" href="@[[pagina#url-raiz]]@assets/style.css">
  <a href="@[[pagina#url-raiz]]@dashboard/">Dashboard</a>
  ```

#### `@[[pagina#url-full-http]]@`
- **Type**: URL
- **Description**: Full URL including protocol and domain
- **Source**: Global variable `$_GESTOR['url-full-http']`
- **Usage**: Absolute links, sharing, APIs
- **Code line**: `gestor.php:485`
- **Example**:
  ```html
  <meta property="og:url" content="@[[pagina#url-full-http]]@">
  ```

#### `@[[pagina#url-caminho]]@`
- **Type**: URL
- **Description**: Current page relative path (without domain)
- **Source**: Variable `$_GESTOR['caminho-total']`
- **Processing**: Normalized with `/` at the end
- **Code line**: `gestor.php:486`
- **Example**:
  ```html
  <span class="breadcrumb">You are at: @[[pagina#url-caminho]]@</span>
  ```

#### `@[[pagina#contato-url]]@`
- **Type**: URL
- **Description**: System contact page URL
- **Source**: Variable `$_GESTOR['pagina#contato-url']`
- **Usage**: Support and contact links
- **Code line**: `gestor.php:488`
- **Example**:
  ```html
  <a href="@[[pagina#contato-url]]@">Contact us</a>
  ```

#### `@[[pagina#modulo-id]]@`
- **Type**: Identifier
- **Description**: ID of the module associated with the current page
- **Source**: Variable `$_GESTOR['modulo-id']`
- **Condition**: Only if page has linked module
- **Code line**: `gestor.php:497`
- **Example**:
  ```html
  <div data-modulo="@[[pagina#modulo-id]]@">
      <!-- Module content -->
  </div>
  ```

#### `@[[pagina#registro-id]]@`
- **Type**: Identifier
- **Description**: ID of the record being viewed/edited
- **Source**: Variable `$_GESTOR['modulo-registro-id']`
- **Condition**: Only on edit/view pages
- **Code line**: `gestor.php:498`
- **Example**:
  ```html
  <form action="save/@[[pagina#registro-id]]@/" method="post">
      <!-- Form fields -->
  </form>
  ```

---

### User Variables (usuario#)

Variables related to the authenticated user in the system.

#### `@[[usuario#nome]]@`
- **Type**: User Data
- **Description**: Full name of the authenticated user
- **Source**: Function `gestor_usuario()` → `nome` field from `usuarios` table
- **Processing**: Loaded from active session
- **Code line**: `gestor.php:495`
- **Example**:
  ```html
  <div class="user-profile">
      Welcome, <strong>@[[usuario#nome]]@</strong>
  </div>
  ```

---

### System Variables (gestor#)

Variables related to the Conn2Flow system as a whole.

#### `@[[gestor#versao]]@`
- **Type**: System Information
- **Description**: Current installed Conn2Flow version
- **Source**: Global variable `$_GESTOR['versao']`
- **Format**: Semantic Versioning (e.g., `1.2.3`)
- **Code line**: `gestor.php:489`
- **Example**:
  ```html
  <footer>
      Conn2Flow v@[[gestor#versao]]@ - © 2026
  </footer>
  ```

---

### Widget Variables (widgets#)

Variables for including dynamic system widgets.

#### `@[[widgets#WIDGET_ID]]@`
- **Type**: Dynamic Component
- **Description**: Includes a specific widget on the page
- **Source**: Function `widgets_get()` from `widgets.php` library
- **Processing**: System detects pattern and fetches widget from database
- **Code line**: `gestor.php:460-476`
- **Dynamic ID**: Replace `WIDGET_ID` with the actual widget identifier
- **Example**:
  ```html
  <div class="dashboard-stats">
      @[[widgets#sales-statistics]]@
  </div>
  ```

#### Widget Processing Flow

1. **Detection**: Regex searches for pattern `@[[widgets#(.+?)]]@`
2. **Library**: System loads `gestor/bibliotecas/widgets.php`
3. **Search**: Function `widgets_get(Array('id' => $match))` fetches widget
4. **Substitution**: If widget exists, replaces marker with widget HTML
5. **Rendering**: Widget is rendered on the page

---

## 🔍 Technical Reference

### Code Location

#### Main Function: `gestor_pagina_variaveis()`
- **File**: `gestor/gestor.php`
- **Line**: 432-560
- **Responsibility**: Process and replace all global variables

#### Processing Order

```php
// 1. Structural variables (title, body)
$layout = modelo_var_troca($layout, '<!-- pagina#titulo -->', ...);
$_GESTOR['pagina'] = modelo_var_troca($layout, '@[[pagina#corpo]]@', ...);

// 2. Dynamic widgets
preg_match_all("/\@\[\[widgets#(.+?)\]\]@/i", $_GESTOR['pagina'], $matchesWidgets);

// 3. Page and system variables
$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '@[[pagina#menu]]@', ...);
$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'], '@[[usuario#nome]]@', ...);

// 4. Custom global variables
$valor = gestor_variaveis_globais(Array('id' => $match));

// 5. Specific module variables
$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => $match));
```

### Helper Functions

| Function | Description |
|----------|-------------|
| `modelo_var_troca()` | Replaces first occurrence of variable |
| `modelo_var_troca_tudo()` | Replaces all occurrences of variable |
| `gestor_variaveis_globais()` | Fetches global variable from database |
| `gestor_variaveis()` | Fetches module-specific variable |
| `gestor_usuario()` | Returns authenticated user data |
| `gestor_pagina_menu()` | Generates dynamic menu based on permissions |

### Database Tables

| Table | Variable Relationship |
|-------|----------------------|
| `paginas` | Provides `titulo`, `caminho`, HTML content |
| `layouts` | Provides base structure with `@[[pagina#corpo]]@` |
| `usuarios` | Provides user data (`nome`, etc) |
| `variaveis` | Stores custom global and module variables |
| `modulos` | Defines modules and their permissions |

---

## 💡 Usage Examples

### Example 1: Base Layout with Variables

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@[[pagina#titulo]]@ - Conn2Flow System</title>
    <meta property="og:url" content="@[[pagina#url-full-http]]@">
    <link rel="stylesheet" href="@[[pagina#url-raiz]]@assets/css/main.css">
</head>
<body>
    <header>
        <nav>@[[pagina#menu]]@</nav>
        <div class="user-info">
            Hello, <strong>@[[usuario#nome]]@</strong>
        </div>
    </header>
    
    <main class="container">
        <h1>@[[pagina#titulo]]@</h1>
        @[[pagina#corpo]]@
    </main>
    
    <footer>
        <p>Conn2Flow v@[[gestor#versao]]@ - All rights reserved</p>
        <a href="@[[pagina#contato-url]]@">Contact</a>
    </footer>
</body>
</html>
```

### Example 2: Page with Widgets

```html
<div class="dashboard">
    <h2>Main Dashboard</h2>
    
    <div class="widgets-row">
        @[[widgets#total-sales]]@
        @[[widgets#active-users]]@
        @[[widgets#pending-tasks]]@
    </div>
    
    <div class="content-area">
        <p>You are at: @[[pagina#url-caminho]]@</p>
        <!-- Page content -->
    </div>
</div>
```

### Example 3: Edit Form

```html
<form action="@[[pagina#url-raiz]]@module/save/@[[pagina#registro-id]]@/" method="post">
    <input type="hidden" name="modulo-id" value="@[[pagina#modulo-id]]@">
    
    <div class="form-group">
        <label>Name:</label>
        <input type="text" name="nome" required>
    </div>
    
    <button type="submit">Save Changes</button>
    <a href="@[[pagina#url-raiz]]@module/list/">Cancel</a>
</form>
```

### Example 4: Dynamic Breadcrumb

```html
<nav class="breadcrumb">
    <a href="@[[pagina#url-raiz]]@">Home</a>
    <span class="separator">/</span>
    <span class="current">@[[pagina#url-caminho]]@</span>
</nav>
```

### Example 5: Variable Conversion (Backend ↔ Frontend)

#### Scenario: Template Editing in admin-templates Module

```php
// STEP 1: Load from Database (Backend)
$template_db = banco_select([
    'tabela' => 'templates',
    'campos' => ['html', 'css'],
    'extra' => "WHERE id='my-template'"
]);

// Database content: @[[pagina#titulo]]@ and @[[usuario#nome]]@
echo $template_db['html']; 
// Output: <h1>@[[pagina#titulo]]@</h1><p>Hello @[[usuario#nome]]@</p>

// STEP 2: Convert to Frontend (Remove @)
$html_frontend = str_replace('@[[', '[[', $template_db['html']);
$html_frontend = str_replace(']]@', ']]', $html_frontend);

echo $html_frontend;
// Output: <h1>[[pagina#titulo]]</h1><p>Hello [[usuario#nome]]</p>
// ↑ User edits in this format

// STEP 3: User Edits and Saves
$_POST['html'] = '<h1>[[pagina#titulo]]</h1><p>Welcome [[usuario#nome]]</p>';

// STEP 4: Convert to Backend (Add @)
$open = '@[[';
$close = ']]@';
$openText = '[[';
$closeText = ']]';

$html_backend = preg_replace(
    "/".preg_quote($openText)."(.+?)".preg_quote($closeText)."/",
    strtolower($open."$1".$close),
    $_POST['html']
);

echo $html_backend;
// Output: <h1>@[[pagina#titulo]]@</h1><p>Welcome @[[usuario#nome]]@</p>
// ↑ Saved to database in this format

// STEP 5: System Automatically Processes
// gestor.php detects @[[...]]@ and replaces with actual values:
// <h1>Main Dashboard</h1><p>Welcome John Smith</p>
```

---

## 🔐 Security and Best Practices

### Security Delimiters

The `@` delimiters serve to:
1. **Unique Identification**: Avoid conflicts with regular text
2. **Safe Processing**: Ensure only valid variables are processed
3. **XSS Protection**: System validates and sanitizes values before substitution
4. **Context Separation**: Differentiates secure storage (`@[[...]]@`) from user-friendly editing (`[[...]]`)

### Security Architecture

#### 🔒 **Backend (Secure Storage)**
- Variables protected with `@[[...]]@`
- Processing restricted by the system
- Runtime validation
- Protection against code injection

#### ✏️ **Frontend (User Interface)**
- Clean variables `[[...]]` for better UX
- Automatic conversion via middleware
- Validation before persistence
- Input sanitization

### Best Practices

✅ **DO:**
- Use variables for dynamic content
- Maintain exact syntax (case-sensitive)
- Document custom variables created in modules
- Test variables after creation/modification

❌ **DON'T:**
- Create variables with generic names that conflict with globals
- Include PHP code inside variables
- Modify `@[[` and `]]@` delimiters in frontend
- Manually process variables without using system functions

---

## 📖 References

- **General Documentation**: `ai-workspace/en/docs/CONN2FLOW-MANAGER-DETAILS.md`
- **Template System**: `ai-workspace/en/docs/CONN2FLOW-LAYOUTS-PAGES-COMPONENTS.md`
- **Source Code**: `gestor/gestor.php` (function `gestor_pagina_variaveis()`)
- **Model Library**: `gestor/bibliotecas/modelo.php`
- **Widgets Library**: `gestor/bibliotecas/widgets.php`

---

**Last updated:** January 26, 2026  
**System Version:** Conn2Flow 2.5.x  
**Author:** Conn2Flow Technical Documentation
