# Conn2Flow - Modules Detailed

## 📋 Index
- [Overview](#overview)
- [Module Structure](#module-structure)
- [Page-Module Linking](#page-module-linking)
- [Real vs Invalid Modules](#real-vs-invalid-modules)
- [Best Practices](#best-practices)
- [Examples](#examples)
- [Decision History](#decision-history)

---

## 🎯 Overview

Modules are responsible for specific page logic in Conn2Flow. Each module can contain PHP and JS files, as well as its own assets.

---

## 🏗️ Module Structure
- Location: `gestor/modulos/{module}/`
- Main file: `{module}.php` (and/or `{module}.js`)
- Assets: CSS, JS, images, etc.
- Specific functions: initialization, menu, toasts, etc.

```
module-name/
├── module-name.php       # Backend logic (PHP)
├── module-name.js        # Frontend logic (JavaScript)
├── module-name.json      # Configurations, metadata and resource mapping.
└── resources/            # Visual resources by language
    └── pt-br/
        ├── layouts/      # Specific layouts
        ├── pages/        # HTML pages
        ├── components/   # Reusable components
```

### 🎛️ **JSON Configuration System**
Each module has a JSON file with:
- **version**: Module versioning
- **libraries**: Library dependencies
- **table**: Database configuration
- **resources**: Resources by language (pages, components, variables)
- **Specific configurations**: Unique module parameters

---

## 🔗 Page-Module Linking
- Pages can be linked to a module.
- The router (`gestor.php`) automatically includes the module when rendering the page.
- Example: dashboard page linked to dashboard module.

---

## ✅ Real vs Invalid Modules
- Real module: has `{module}.php` or `{module}.js` in the folder.
- Invalid module: folder without main file, should not receive exported pages.
- Automated export only creates folders for real modules.

---

## 📝 Best Practices
- Always create `{module}.php` for new modules.
- Document functions and entry points.
- Keep assets organized in the module folder.
- Avoid logic duplication between modules.

---

## 💡 Examples
- Dashboard module: `gestor/modulos/dashboard/dashboard.php`
- Host-configuration module: cPanel integrations, own assets.

---

## 📜 Decision History
- Real module validation implemented in August/2025.
- Export of pages to invalid modules blocked.
- Standardized module structure to facilitate maintenance.
