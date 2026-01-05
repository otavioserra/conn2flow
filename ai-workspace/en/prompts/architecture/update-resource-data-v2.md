````markdown
# Prompt Interactive Programming - Update Resource Data V 2.0.

## 🎯 Initial Context
- FUNDAMENTAL: Analyze the previous context before following the instructions below, which was recorded in the file: `ai-workspace\prompts\architecture\update-resource-data.md`.

## 📖 Libraries

## 📝 Instructions for the Agent

## 🧭 Source Code Structure
```
main():
    // Main script logic
    

main()
```

## 🤔 Doubts and 📝 Suggestions

---
## 🚀 COLLABORATIVE PLANNING - VERSION 2.0

### Context
The process of updating resource data will be simplified: there will no longer be manual control of numeric identifiers for the `pages`, `layouts`, `variables`, and `components` resources. The responsibility for the identifiers will lie with the database itself, using auto-increment on the primary keys (this is already implemented in the migrations). This eliminates the complexity and duplication problems that occurred in the previous flow.

Files involved:
- Main script to be changed: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- Original context and rules: `ai-workspace/prompts/architecture/update-resource-data.md`
- Planning and decision log: `ai-workspace/prompts/architecture/update-resource-data-v2.md` (this file)

### Requirements

#### Resource reference and uniqueness rules
1. **Layout reference in pages**:
    - The `layout_id` field of the destination (`PaginasData.json`) will be filled with the value of the `layout` field from the source (`pages.json`).
    - The `id_layouts` field no longer exists in the `pages` table and will not be generated.

2. **Resource uniqueness rules**:
    - All resources (`layouts`, `pages`, `components`, `variables`):
      - The `id` field must be unique within the same `language`. It can be repeated in different `languages`.
    - `pages` resource:
      - Allows the same `id` in the same `language` if it is in different `modules`.
      - The `path` field must be unique within the same `language` (can be repeated in different `languages`).
    - `variables` resource:
      - Allows the same `id` in the same `language` and `module` if the `group` field is different.

3. **Page migration**:
    - Analyze whether the migration `gestor/db/migrations/20250723165530_create_paginas_table.php` is compatible with the rules above, especially regarding the uniqueness of `id` and `path`.

4. **Orphans rule**:
  - Any record that does not meet the uniqueness criteria, data type, or specific rules will be moved to the orphans folder: `gestor/db/orphans`.
  - For each type of resource (`pages`, `layouts`, `components`, `variables`), a JSON file will be created containing only the problematic records. Use the same naming pattern used to save the resources in the folder: `gestor\db\data`
  - Valid data goes to the final files in the correct folder; orphans are available for future consultation and analysis.


4. **Rules checklist**:
  - [x] Layout reference in pages adjusted (`layout_id` field coming from the `layout` field of the source)
  - [x] `id_layouts` field removed from the flow
  - [x] Uniqueness rules for `id` and `path` fields implemented as specified
  - [x] Page migration revised to ensure compatibility (schema already uses string `layout_id`)
  - [x] Orphan separation flow implemented (files in `gestor/db/orphans`)

### Action Plan
1. Map all points in the script and flows where there is manipulation of manual numeric identifiers.
2. Document the necessary changes to eliminate this logic.
3. Ensure that the structure of the Data.json files and seeders no longer depends on manual IDs.
4. Record all decisions and alternatives considered in this file.

### Checklist
- [x] Mapping of manual ID manipulation points performed (replaced by natural keys)
- [x] Necessary changes documented
- [x] Structure of Data.json files and seeders revised (removal of numeric ids, maintenance of version/checksum)
- [x] Decisions and alternatives recorded
- [x] Planning approved for implementation

### Decisions
- The control of numeric identifiers will be exclusively by the database (auto-increment).
- The flow of generating and updating resources will be simplified, focusing only on relevant data and IDs.
- The entire process will be documented and collaboratively validated before any changes to the code.

## ✅ Implementation Progress
- [x] Refactoring of the `update-resource-data.php` script to V2 (removed manual numeric ID control)
- [x] Implementation of uniqueness rules and orphan segregation
- [x] Generation of updated files without uniqueness errors (0 orphans in the current execution)
- [x] Seeders verified/created when absent

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate a detailed message, replace the string in the command, and execute: `./ai-workspace/git/scripts/commit.sh "feat(resources): refactoring V2 update resource data (natural IDs, orphans, layout_id, uniqueness, seeders)"`

### Technical Summary of V2 Implementation
| Aspect | Status |
|---------|--------|
| Numeric ID control | Removed from Data.json (autoincrement PK only in the database) |
| layout_id in pages | Derived directly from source `layout` |
| Page uniqueness (id + module) | Implemented; allows the same id in different modules |
| Page path uniqueness (language) | Enforced; duplicates become orphans |
| Variable uniqueness | id+mod+lang allows multiple distinct groups; violations become orphans |
| Orphans | Saved in `gestor/db/orphans/*Data.json` |
| Version/Checksum | Increment only when html/css change; checksum stored as JSON string |
| Seeders | Generated if non-existent (does not overwrite existing ones) |

### Suggested Next Steps
1. Execute automated commit with a detailed message.
2. Validate seeders in a test environment (phinx migrate/seed).
3. Adjust any consumers that still expect numeric ids in Data.json.

## ♻️ Changes and Fixes 1.0

## ✅ Progress of Implementation of Changes and Fixes

## ☑️ Post Changes and Fixes Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate a detailed message, replace the string, and execute the automated commit.

### Commit Message Used
```
feat(resources): refactoring V2 update resource data (natural IDs, orphans, layout_id, uniqueness, seeders)

Implements version 2 of the resource generation process:
- Completely removes manual control of numeric IDs (layouts, pages, components, variables) from Data.json
- Adopts only natural keys for versioning and reuse of version/checksum
- Introduces a flow for segregating orphans (duplicates, inconsistencies) in gestor/db/orphans
- Converts layout reference in pages to a direct string `layout_id` field from the source
- Updates the admin-pages module to persist textual layout_id (mapping numeric selection)
- Adjusts gestor/gestor.php to consume layout_id
- Maintains version increments only on html/css changes (consolidated JSON string checksum)
- Generates seeders only when absent (without overwriting existing ones)
- Ensures uniqueness:
  * layouts/components: id+language
  * pages: id+language+module and unique path per language
  * variables: id+language+module (+group allows multiples) 
- Execution resulted in 0 orphans (sanity validated)

Key files: update-resource-data.php, PaginasData.json, LayoutsData.json, ComponentesData.json, VariaveisData.json, admin-paginas.php, v2 planning.

Suggested next steps (non-blocking):
1. Run phinx migrate/seed in a test environment
2. Audit other consumers still using id_layouts for reading
3. Internationalize new log messages
```

---
**Date:** 08/15/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.17
````