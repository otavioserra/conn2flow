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

## 🧱 Manifest `tabela` Block and Resource Synchronization (BATCH-056)

In the `<module>.json` manifest, the `"tabela"` key describes how the module's table(s) join the data pipeline. The `"config"` sub-key accepts an **object** (1 table) or an **array of objects** (N tables, each with `"tabela_nome"`), with the properties:

- `strategy` (`natural_key`/`pk`), `natural_key_columns`, `preserve_on_user_modified`, `insert_only`.
- `sync_resources`, `resources_dir`, `metadata_file`, `field_types` (`json` / `file:<ext>`) — automatically generate `<PascalCase>Data.json` from the physical/inline resources.
- `deletar` and `forcar_atualizacao` — lists of records (`pk` / `natural_key`) for deletion or forced overwrite on deploy.

The generator (`atualizacao-dados-recursos.php`) consolidates these blocks (modules + global) into the `schema-metadata.json` contract.

---

## 📜 Decision History
- Real module validation implemented in August/2025.
- Export of pages to invalid modules blocked.
- Standardized module structure to facilitate maintenance.
- Declarative synchronization of custom tables (`config` object/array, `sync_resources`, `field_types`, `forcar_atualizacao`) added in 2026-06 (BATCH-056).
