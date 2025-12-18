# Conn2Flow Libraries Documentation

> 📚 Complete documentation of the 26 PHP libraries of the Conn2Flow system

## Overview

This directory contains detailed documentation for all Conn2Flow system libraries located in `gestor/bibliotecas/`. The libraries provide essential functionalities for the CMS operation, from database operations to user management and AI integrations.

## Available Libraries

### 📊 Core System Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **banco.php** | 45 | MySQL/MySQLi database operations | [📖 Docs](./LIBRARY-DATABASE.md) |
| **gestor.php** | 24 | Main CMS functions | [📖 Docs](./LIBRARY-MANAGER.md) |
| **autenticacao.php** | 18 | Authentication and security | [📖 Docs](./LIBRARY-AUTHENTICATION.md) |
| **configuracao.php** | 4 | Configuration management | [📖 Docs](./LIBRARY-CONFIGURATION.md) |

### 🎨 Interface and Presentation Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **interface.php** | 52 | User interface components | [📖 Docs](./LIBRARY-INTERFACE.md) |
| **html.php** | 8 | HTML generation | [📖 Docs](./LIBRARY-HTML.md) |
| **widgets.php** | 4 | Widget components | [📖 Docs](./LIBRARY-WIDGETS.md) |
| **formulario.php** | 5 | Form generation and validation | [📖 Docs](./LIBRARY-FORM.md) |

### 📄 Content and Data Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **pagina.php** | 7 | Page management | [📖 Docs](./LIBRARY-PAGE.md) |
| **modelo.php** | 10 | Templates and models | [📖 Docs](./LIBRARY-MODEL.md) |
| **formato.php** | 12 | Data formatting | [📖 Docs](./LIBRARY-FORMAT.md) |
| **variaveis.php** | 3 | Variable management | [📖 Docs](./LIBRARY-VARIABLES.md) |

### 👤 User and Communication Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **usuario.php** | 6 | User management | [📖 Docs](./LIBRARY-USER.md) |
| **comunicacao.php** | 2 | Communication and messaging | [📖 Docs](./LIBRARY-COMMUNICATION.md) |
| **log.php** | 5 | Log system | [📖 Docs](./LIBRARY-LOG.md) |

### 🔌 Plugin and Extension Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **plugins-installer.php** | 43 | Plugin installation system | [📖 Docs](./LIBRARY-PLUGINS-INSTALLER.md) |
| **plugins.php** | 1 | Plugin utilities | [📖 Docs](./LIBRARY-PLUGINS.md) |
| **plugins-consts.php** | 0 | Plugin constants | [📖 Docs](./LIBRARY-PLUGINS-CONSTS.md) |

### 🤖 Integration Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **ia.php** | 9 | AI Integration (Gemini API) | [📖 Docs](./LIBRARY-AI.md) |
| **pdf.php** | 1 | PDF Generation | [📖 Docs](./LIBRARY-PDF.md) |
| **ftp.php** | 4 | FTP Operations | [📖 Docs](./LIBRARY-FTP.md) |

### 🛠️ Utility Libraries

| Library | Functions | Description | Documentation |
|-----------|---------|-----------|--------------|
| **geral.php** | 1 | General functions | [📖 Docs](./LIBRARY-GENERAL.md) |
| **arquivo.php** | 0 | File operations | [📖 Docs](./LIBRARY-FILE.md) |
| **host.php** | 3 | Host utilities | [📖 Docs](./LIBRARY-HOST.md) |
| **ip.php** | 2 | IP utilities | [📖 Docs](./LIBRARY-IP.md) |
| **lang.php** | 0 | Language utilities | [📖 Docs](./LIBRARY-LANG.md) |

## Statistics

- **Total Libraries**: 26
- **Documented Libraries**: 26 (100%) ✅
- **Total Functions**: 269
- **Documented Functions**: 269 (100%) ✅
- **Documentation**: ~330 pages
- **Examples**: 90+ practical examples
- **Use Cases**: 60+ real scenarios
- **System Version**: v2.3.0
- **STATUS**: COMPLETE 🎉

## Naming Conventions

Functions in libraries follow a consistent naming pattern:

```php
[library]_[operation]_[context]($params)
```

### Examples:
- `banco_select()` - Database select operation
- `formato_data_hora()` - Date and time formatting
- `usuario_autenticar()` - User authentication
- `interface_modal_abrir()` - Opening a modal in the interface

## Parameter Patterns

### Parameter Array
Many functions accept an associative array of parameters:

```php
function example_function($params = false){
    if($params) foreach($params as $var => $val) $$var = $val;
    
    // Available parameters:
    // - parameter1 (type) - Required/Optional - Description
    // - parameter2 (type) - Required/Optional - Description
}
```

### Global Variables
Libraries use global variables for state and configuration:

```php
global $_GESTOR;  // System settings
global $_BANCO;   // Database settings
global $_USUARIO; // Authenticated user data
```

## How to Use This Documentation

1. **Find the Library**: Use the table above to locate the library containing the desired functionality
2. **Consult Documentation**: Click the documentation link to see full details
3. **See Examples**: Each documented function includes practical usage examples
4. **Understand Dependencies**: Check dependencies between libraries in specific documentation

## Library Documentation Structure

Each documentation file follows this structure:

1. **Overview**: Purpose and scope of the library
2. **Dependencies**: Other required libraries
3. **Global Variables**: Global variables used
4. **Helper Functions**: Internal functions (prefix without library)
5. **Main Functions**: Public API of the library
6. **Usage Examples**: Practical use cases
7. **Release Notes**: Change history

## Contributing

To add or improve documentation:

1. Analyze the source code in `gestor/bibliotecas/[name].php`
2. Document public functions with:
   - Full signature
   - Parameters (name, type, required/optional, description)
   - Return value
   - Usage example
   - Relevant notes and observations
3. Maintain consistency with the existing format
4. Test the provided examples

## Related Resources

- [📚 Knowledge System](../CONN2FLOW-KNOWLEDGE-SYSTEM.md)
- [🔧 Module Development](../CONN2FLOW-MODULES-DETAILED.md)
- [🎨 Layouts and Components](../CONN2FLOW-LAYOUTS-PAGES-COMPONENTS.md)
- [🔌 Plugin Architecture](../CONN2FLOW-PLUGIN-ARCHITECTURE.md)

## License

This documentation is part of the Conn2Flow project and is available under the same open-source license as the main system.

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
