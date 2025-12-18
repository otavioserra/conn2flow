# Conn2Flow - Detailed Routing

## 📋 Index
- [Overview](#overview)
- [How Routing Works](#how-routing-works)
- [Routing Fields](#routing-fields)
- [Module Linking](#module-linking)
- [Practical Examples](#practical-examples)
- [Decision History](#decision-history)

---

## 🎯 Overview

Conn2Flow routing is centralized in the `gestor.php` file, which resolves URLs, fetches pages, layouts, and modules, and renders the final HTML.

---

## 🔄 How Routing Works
1. Request arrives at `gestor.php`.
2. The path is analyzed and the corresponding page is searched in the `paginas` table.
3. The linked layout is loaded.
4. If the page has a module, the module file is included.
5. Dynamic variables are processed.
6. Components are included as needed.
7. Final HTML is sent to the browser.

---

## 🏷️ Routing Fields
- `caminho`: field in `paginas` table that defines the URL.
- `id_layouts`: layout linked to the page.
- `id_modulos`: linked module (optional).

---

## 🔗 Module Linking
- If `id_modulos` is defined, the router includes `{module}.php`.
- Allows specific logic per page.
- Example: dashboard, host-configuracao.

---

## 💡 Practical Examples
- `/dashboard` → dashboard page, layout 1, dashboard module.
- `/instalacao-sucesso` → success page, layout 23, no module.

---

## 📜 Decision History
- Centralized routing structure since initial version.
- Use of `caminho` field for friendly URLs.
- Standardized dynamic variable processing.
