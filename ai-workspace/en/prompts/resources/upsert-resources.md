```markdown
# Prompt Interactive Programming - Resource Manager (Upsert/Delete)

## 🎯 Initial Context
This document defines the technical specification for the `upsert-resources.php` script located at `ai-workspace\scripts\resources\upsert-resources.php`. The goal is to create a robust Command Line Interface (CLI) tool to create, update (upsert), and delete resources (pages, layouts, components, templates, variables) within the Conn2Flow ecosystem.

The script acts as the **Source of Truth** manager for the system. It handles physical files and JSON metadata located in `resources/` and `modulos/`. These files are subsequently consumed by the `atualizacao-dados-recursos.php` script, which consolidates them into `*Data.json` files to be finally applied to the database via `atualizacoes-banco-de-dados.php`.

The script must be capable of handling resources in three main contexts (Manager, Plugins, Projects) and two scopes (Global, Module), dealing with the complexity of different paths and file structures for each combination.

## 📝 Technical Specification

### 1. CLI Parameters
The script must accept the following arguments:

| Argument | Description | Default | Mandatory |
| :--- | :--- | :--- | :--- |
| `--target` | Operation target: `gestor`, `plugin`, `project`. | `gestor` | No |
| `--plugin-type` | If target is plugin: `public` or `private`. | - | Yes (if target=plugin) |
| `--scope` | Resource scope: `global` or `module`. | `global` | No |
| `--module-id` | Module ID (if scope is module). | - | Yes (if scope=module) |
| `--lang` | Language code (e.g., `pt-br`, `en`). | `pt-br` | No |
| `--type` | Resource type: `page`, `layout`, `component`, `template`, `variable`, `prompt_ia`, `modo_ia`, `alvo_ia`. | - | Yes |
| `--id` | Resource ID or comma-separated list (e.g., `home,contact`). Replaces `--data` for quick operations. | - | No (but mandatory if `--data` is not provided) |
| `--action` | Action to execute: `upsert` or `delete`. | `upsert` | No |
| `--open` | If present, opens created/updated files (physical and JSON metadata) in the default editor (VS Code). | - | No |
| `--interactive` | Activates interactive mode (CLI menu) to fill parameters. | - | No |
| `--data` | JSON string with resource data (metadata + content). | - | Yes (if `--id` is not provided) |

### 2. Interactive Mode
If the script is executed without arguments (or with `--interactive`), it will enter interactive mode, guiding the user step-by-step with colored menus to select:
1. Target (Manager/Plugin/Project)
2. Scope and Module
3. Language and Resource Type
4. Action (Upsert/Delete)
5. Option to open files
6. Data Input:
   - **ID List:** For quick creation or navigation.
   - **Full JSON:** To paste a JSON with all resource data.

> **Note:** Interactive mode and script outputs use ANSI colors to facilitate visualization (Green for success, Cyan for information, Yellow for warnings, Red for errors).

### 3. Path Resolution Logic (Root)

The script must determine the root (`{root}`) based on `--target`:

#### 2.1. Manager (Default)
- **Path:** `gestor/` (relative to repository root).

#### 2.2. Project
1. Read `dev-environment/data/environment.json`.
2. Get active project ID in `devEnvironment.projectTarget`.
3. Get path in `devProjects[{projectTarget}].path`.
4. **Root:** The resolved path.

#### 2.3. Plugins
1. Read `dev-environment/data/environment.json`.
2. Identify plugin environment file based on `--plugin-type` (`public` or `private`) via `devPluginEnvironmentConfig.{type}.path`.
3. Read the specific plugin environment file.
4. Get active plugin ID in `activePlugin.id`.
5. Get `source` (base path) in `devEnvironment.source`.
6. Search in `plugins` array for item where `id` == `activePlugin.id` and get `path`.
7. **Root:** Concatenation of `{source}` + `{path}`.

### 3. Data Structure and Metadata

#### 3.1. Resource Classification
Resources are divided into three categories based on their physical structure:

1.  **HTML/CSS Resources:** `page`, `layout`, `component`, `template`.
    *   Have physical `.html` and `.css` files.
    *   Metadata in mapping JSON.

2.  **Markdown Resources (AI):** `prompt_ia`, `modo_ia`, `alvo_ia`.
    *   Have physical `.md` file.
    *   Metadata in mapping JSON.

3.  **Data Resources:** `variable`.
    *   Do not have separate physical files.
    *   Data and metadata reside exclusively in JSON (`variables.json`).

#### 3.2. Global Scope (`--scope global`)
- **Mapping:** Read `{root}/resources/resources.map.php`.
- **Metadata Location:** Defined in array `languages[{lang}][data][{type}s]`.
  - Ex: `pages` -> `pages.json`.
  - Full JSON path: `{root}/resources/{lang}/{json_file}`.
- **Physical Files Location:**
  - HTML/CSS: `{root}/resources/{lang}/{type}s/{id}/{id}.html` and `{id}.css`.
  - Markdown: `{root}/resources/{lang}/{type}s/{id}/{id}.md`.

#### 3.3. Module Scope (`--scope module`)
- **Configuration File:** `{root}/modulos/{module_id}/{module_id}.json`.
- **Metadata Location:** Inside this JSON, at key `resources.{lang}.{type}s`.
- **Physical Files Location:**
  - HTML/CSS: `{root}/modulos/{module_id}/resources/{lang}/{type}s/{id}/{id}.html` and `{id}.css`.
  - Markdown: `{root}/modulos/{module_id}/resources/{lang}/{type}s/{id}/{id}.md`.

#### 3.4. Data Schema (Input JSON)
The `--data` parameter must respect the fields below for each resource type. Fields marked with `*` are mandatory (or have logical fallback).

**1. Layouts (`layout`)**
```json
{
  "id": "string*",
  "name": "string",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (content)",
  "css": "string (content)"
}
```

**2. Components (`component`)**
```json
{
  "id": "string*",
  "name": "string",
  "module": "string",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (content)",
  "css": "string (content)"
}
```

**3. Pages (`page`)**
```json
{
  "id": "string*",
  "name": "string",
  "layout": "string (Default: layout-pagina-sem-permissao)",
  "path": "string (Default: {id}/)",
  "type": "string (Default: page)",
  "module": "string",
  "option": "string",
  "root": "boolean",
  "without_permission": "boolean",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (content)",
  "css": "string (content)"
}
```

**4. Templates (`template`)**
```json
{
  "id": "string*",
  "name": "string",
  "target": "string",
  "thumbnail": "string (url/path)",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (content)",
  "css": "string (content)"
}
```

**5. Variables (`variable`)**
```json
{
  "id": "string*",
  "value": "string",
  "type": "string",
  "group": "string",
  "module": "string",
  "description": "string"
}
```

**6. AI Prompts (`prompt_ia`) & AI Modes (`modo_ia`)**
```json
{
  "id": "string*",
  "name": "string",
  "target": "string",
  "default": "boolean",
  "status": "string (A/I)",
  "version": "string",
  "md": "string (content)"
}
```

**7. AI Targets (`alvo_ia`)**
```json
{
  "id": "string*",
  "name": "string",
  "status": "string (A/I)"
}
```

### 4. Execution Flow (Upsert)

1. **Initialization:** Parse CLI arguments.
2. **Root Definition:** Execute path resolution logic (Manager/Plugin/Project).
3. **Metadata Loading:**
   - If Global: Load `resources.map.php` and open specific language/type JSON.
   - If Module: Open `{module_id}.json`.
4. **Processing:**
   - Check if resource already exists (by ID).
   - **Content Handling (Input JSON):**
     - Input JSON (`--data`) must contain content fields (`html`, `css`, `md`) if applicable.
     - **HTML/CSS:** Extract content from `html` and `css`. Save/Overwrite physical `.html` and `.css` files. Remove `html` and `css` fields from metadata object.
     - **Markdown:** Extract content from `md`. Save/Overwrite physical `.md` file. Remove `md` field from metadata object.
     - **Variables:** Keep value in metadata object.
   - **Update Metadata:** Insert or update object in JSON array (merging new data with existing).
5. **Persistence:** Save updated metadata JSON file.

### 5. Execution Flow (Delete)

1. **Initialization & Root:** Same as Upsert.
2. **Loading:** Same as Upsert.
3. **Removal:**
   - Remove object from metadata JSON array.
   - **Physical Files:** Delete corresponding physical folder/files (if they exist).
4. **Persistence:** Save JSON.

## 🤔 Doubts and 📝 Suggestions

1. **Data Input:** The script will assume that input JSON (`--data`) contains raw content fields `html`, `css` or `md` to be saved in physical files. These fields will be removed from the object before saving to metadata JSON.

2. **Versioning:** Should the script implement the same version increment logic (`X.Y`) as `atualizacao-dados-recursos.php`?
   - *Decision:* **No.** The upsert script focuses only on data persistence. Checksum calculation and versioning is the responsibility of `atualizacao-dados-recursos.php` which prepares data for the database.

## ✅ Implementation Progress
- [x] Project Definition and Requirements (MD).
- [x] Path Resolution Logic Implementation (PHP).
- [x] Metadata Read/Write Implementation (Global/Module).
- [x] Physical File Manipulation Implementation.
- [x] Upsert Tests (Manager/Global).
- [x] Upsert Tests (Module).
- [x] Delete Tests.

---
**Date:** 11/25/2025
**Developer:** GitHub Copilot
**Project:** Conn2Flow v1.0
```