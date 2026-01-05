````markdown
# Prompt Interactive Programming - Inclusion of framework_css Field (TailwindCSS)

## 🎯 Initial Context
- Expand support for CSS frameworks in the system, allowing each resource (page, layout, component) to be marked with the framework used.
- Supported frameworks: 'fomantic-ui' (default), 'tailwindcss' (new), others in the future.
- Data source: resource JSON files, update scripts, database tables.

## 📝 Guidelines for the Agent
1. Design all necessary changes to allow multiple CSS frameworks per resource.
2. Document examples, fallback, and update flow.
3. Register doubts and suggestions before implementation.

## 🤔 Doubts and 📝 Suggestions
- The `framework_css` field will always be a string, initially allowed values: 'fomantic-ui', 'tailwindcss'.
Yes, always string.
- Can a resource use multiple frameworks simultaneously? (Ex: array or comma-separated string)
At first, only one resource at a time.
- Will the field be mandatory or optional? (If absent, always use 'fomantic-ui')
If absent, always use 'fomantic-ui'.
- Any impact on module rendering/routes? (Ex: assets, helpers, JS classes)
The rendering part will be in phase 2 of the modification. For now, just focus on updating the JSON files and the logic of `gestor\controladores\agents\arquitetura\atualizacao-dados-recursos.php`.
- Any rules for framework inheritance between layouts/pages/components?
No, just reference for now.

## ✅ Implementation Progress
- [x] 1. Specification of the `framework_css` field for layouts, pages, and components
- [x] 2. Update of resource source files (JSON) to accept the field (optional field, automatic fallback)
- [x] 3. Creation of 3 migrations to add the field in the tables `paginas`, `layouts`, `componentes`
- [x] 4. Update of resource generation/update scripts to respect the field and use default if absent
- [x] 5. Documentation of usage examples and fallback
- [x] 6. Registration of doubts/suggestions for validation

### Examples (phase 1)

Resource page in source (pages.json or module):
```json
{
	"id": "dashboard",
	"name": "Dashboard",
	"framework_css": "tailwindcss",
	"status": "A"
}
```

If absent:
```json
{
	"id": "relatorios",
	"name": "Reports"
}
```
In collection, it will result in `framework_css: "fomantic-ui"`.

Layout example:
```json
{ "id": "principal", "framework_css": "tailwindcss" }
```

Component example without field (fallback):
```json
{ "id": "save-button" }
```

### Notes
1. Field propagated to Data JSON (`LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`).
2. Migrations add nullable column; application treats null as `fomantic-ui`.
3. Next step: mark final documentation and validate remaining doubts (items 5 and 6).

### Final Doubts / Validation
- No pending doubts. Scope confirmed: only persistence and fallback in phase 1.

### Future Suggestions (Phase 2 – Rendering / Assets)
1. Conditional loading of bundles (CSS/JS) according to predominant `framework_css` of the page (prioritize page > layout > components).
2. Pre-processing strategy for Tailwind (build on demand vs. single build).
3. Global configuration flag to define default framework (today constant DEFAULT_FRAMEWORK_CSS).
4. Optional validation of acceptable values (whitelist) to avoid propagation of incorrect values.
5. Usage metric per framework to monitor adoption.

### How-To Add New Framework (Future)
1. Add new value to source files (JSON) in `framework_css`.
2. (Optional) Update whitelist if implemented.
3. Implement build/asset pipeline (ex: generate minified CSS).
4. Adjust dynamic asset injection routing.

### Confirmation
Phase 1 implementation completed without impact on existing resource versioning (except inclusion of the new field in Data JSON).

---
---
**Date:** 08/27/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.15.0
````