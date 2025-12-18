# CONN2FLOW - Multiple CSS Framework Support (Phase 1)

## Objective
Persist and propagate the `framework_css` field for layouts, pages, and components, enabling future evolution of conditional rendering (TailwindCSS vs FomanticUI).

## Phase 1 Scope
- Inclusion of the field in: Data JSON (`LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`) and tables `layouts`, `paginas`, `componentes`.
- Automatic fallback to `fomantic-ui` when absent.
- No runtime changes in asset loading yet.

## Migrations
Created files:
```
20250827110000_alter_layouts_add_framework_css.php
20250827110010_alter_paginas_add_framework_css.php
20250827110020_alter_componentes_add_framework_css.php
```
Each adds `framework_css VARCHAR(50) NULL` (commented, reversible in down).

## Update Script
File: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- Added constant `DEFAULT_FRAMEWORK_CSS = 'fomantic-ui'`.
- Utility function `getFrameworkCss($src)` returns value or fallback.
- Field incorporated into collections of layouts, pages, and components (global, modules, plugins).

## Origin Examples
Page (Tailwind):
```json
{ "id": "dashboard", "name": "Dashboard", "framework_css": "tailwindcss" }
```
Component (fallback Fomantic):
```json
{ "id": "save-button" }
```

## Data JSON (Simplified Example)
```json
{
  "id": "dashboard",
  "language": "pt-br",
  "framework_css": "tailwindcss",
  "html": "...",
  "css": null,
  "version": 1
}
```

## Current Rules
| Item | Rule |
|------|-------|
| Supported values | `fomantic-ui`, `tailwindcss` (others pass without validation for now) |
| Fallback | Absent or empty => `fomantic-ui` |
| Persistence | Always written to Data JSON after update |
| Database | Nullable column; null treated as fallback at application level |

## Next Steps (Phase 2)
1. Conditional asset injection (initially per page). 
2. Integrated Tailwind build (watch + purge). 
3. Payload optimization (load only necessary framework in context). 
4. Segmented cache by framework for renderings.
5. Whitelist/validation of frameworks and adoption metrics.

## Compatibility Considerations
- Inclusion of the field does not alter uniqueness logic, versioning, or checksums.
- Old deploys ignore the field without breaking; new versions enrich Data JSON.

## Rollback
Run `phinx rollback` to remove columns (migrations have down). Data JSON will keep field; old consumers simply ignore extra keys.

## Suggested Commit
```
feat(css): adds initial support for framework_css (TailwindCSS phase 1)

- Migrations add framework_css column in layouts/pages/components
- Resource collection update propagates field with 'fomantic-ui' fallback
- Updated planning + docs CONN2FLOW-CSS-FRAMEWORK
- No runtime asset changes (planned phase 2)
```

## Suggested Tag
`v1.15.0-frameworkcss-phase1` (or include in next accumulated release).

---
Date: 2025-08-27
Author: Otavio Serra
