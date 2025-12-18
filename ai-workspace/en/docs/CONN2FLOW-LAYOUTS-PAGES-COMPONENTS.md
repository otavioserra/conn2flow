# Conn2Flow - Layouts, Pages and Components

## 📋 Index
- [Overview](#overview)
- [Data Structure](#data-structure)
- [Layouts](#layouts)
- [Pages](#pages)
- [Components](#components)
- [Export and Versioning](#export-and-versioning)
- [Best Practices](#best-practices)
- [Practical Examples](#practical-examples)
- [Decision History](#decision-history)

---

## 🎯 Overview

The Conn2Flow system uses a centralized model of layouts, pages, and components to ensure flexibility, reuse, and interface standardization. All visual content is stored in the database but can be exported to versionable files to facilitate maintenance and deployment.

---

## 🏗️ Data Structure

- **Layouts**: Page structures (header, footer, dynamic slots)
- **Pages**: Specific content, linked to a layout
- **Components**: Reusable blocks (alerts, forms, etc.)

Main tables: `layouts`, `paginas`, `componentes`.

---

## 🖼️ Layouts
- Define the base structure of pages.
- Have dynamic variables, mainly `@[[page#body]]@`.
- Example: Administrative layout (ID 1), external layout (ID 23).

---

## 📄 Pages
- Specific content displayed to the user.
- Always associated with a layout.
- Has `path` field for routing.

---

## 🧩 Components
- Reusable interface blocks.
- Included in layouts or pages.
- Example: modals, buttons, alerts.

---

## 🚀 Export and Versioning

- Automated export of resources to file structure:
  - `gestor/resources/layouts/{id}/`
  - `gestor/resources/paginas/{id}/`
  - `gestor/resources/componentes/{id}/`
- Layouts and components always global.
- Pages exported to real modules or global.
- Versioning via Git.

---

## ✅ Best Practices
- Always use dynamic variables in layouts.
- Keep components generic and reusable.
- Validate existence of modules before exporting pages.
- Document decisions and adopted standards.

---

## 💡 Practical Examples
- Administrative layout: sidebar, top, content slot.
- Dashboard page: linked to layout 1, path `/dashboard`.
- Alert component: included in several pages.

---

## 📜 Decision History
- Automated export implemented in August/2025.
- Clear separation between global and module resources.
- Mirrored file structure from manager to client-manager.
- Mandatory versioning for all visual resources.
