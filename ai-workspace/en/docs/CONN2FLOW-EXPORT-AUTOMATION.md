# Conn2Flow - Export Automation

## 📋 Index
- [Overview](#overview)
- [Motivation](#motivation)
- [Export Flow](#export-flow)
- [Module Validation](#module-validation)
- [Exported File Structure](#exported-file-structure)
- [Best Practices](#best-practices)
- [Usage Examples](#usage-examples)
- [Decision History](#decision-history)

---

## 🎯 Overview

Export automation allows transforming data from seeders (layouts, pages, components) into versionable files, mirroring the manager structure for the client-manager.

---

## 💡 Motivation
- Facilitate versioning and maintenance.
- Ensure consistency between environments.
- Avoid manual errors in exporting visual resources.

---

## 🔄 Export Flow
1. Listing of real modules.
2. Reading of seeders.
3. Export of layouts/components to global resources.
4. Export of pages to valid modules or global.
5. Cleanup of invalid modules.
6. Validation of final structure.

---

## ✅ Module Validation
- Only modules with `{module}.php` or `{module}.js` are considered real.
- Export of pages to invalid modules is blocked.

---

## 🗂️ Exported File Structure
- `gestor-cliente/resources/layouts/{id}/`
- `gestor-cliente/resources/paginas/{id}/`
- `gestor-cliente/resources/componentes/{id}/`
- `gestor-cliente/modulos/{module}/{id}/` (only real modules)

---

## 📝 Best Practices
- Always run the export script after changes in seeders.
- Validate the generated structure before deploy.
- Keep export logs for auditing.

---

## 💡 Usage Examples
- Complete export after layout update.
- Cleanup of invalid modules before deploy.

---

## 📜 Decision History
- Export automation implemented in August/2025.
- Mirrored structure from manager to client-manager.
- Mandatory validation of real modules.
