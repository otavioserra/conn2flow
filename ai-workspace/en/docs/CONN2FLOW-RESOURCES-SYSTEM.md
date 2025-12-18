# Resources System - Conn2Flow

## 📋 Index
- [Overview](#overview)
- [Fundamental Concepts](#fundamental-concepts)
- [Resource Types](#resource-types)
- [File Structure](#file-structure)
- [System Entities](#system-entities)
- [Workflow](#workflow)
- [Management Tools](#management-tools)
- [Multilanguage and Versioning](#multilanguage-and-versioning)

---

## 🎯 Overview and Philosophy

The **Resources System** of Conn2Flow was designed to solve a fundamental problem in software development: **the complexity of versioning Database data.**

### The Problem
Structural data (such as page layout, variable configurations, or AI prompts) usually reside in the database. This makes it difficult to:
1.  Track who changed what and when.
2.  Revert specific changes without restoring an entire backup.
3.  Merge work from different developers (Merge Conflicts).

### The Solution: Resource Compilation Architecture
Conn2Flow's architecture treats resources as source code that needs to be "compiled" before going to the database.

1.  **Source**: Physical files (`.html`, `.css`, `.json`, `.md`) editable by the developer.
2.  **Compilation (Build)**: A script processes these files and generates a "data package" in JSON format (`*Data.json`).
3.  **Transport**: Git versions both the sources and the generated JSONs.
4.  **Execution (Runtime)**: On installation/update, the system reads the JSONs and updates the Database.

The correct flow is: **Physical Editing -> Processing (Generates JSON) -> Commit/Release -> Consumption by Updater -> Database**.

---

## 🧠 Fundamental Concepts

### What is a Resource?
A resource is essentially composed of **Metadata** and **Content**.

- **Metadata**: Configurations, IDs, names, links, and properties. Usually stored in JSON files.
- **Content**: The main body of the resource. Can be one or more physical files (`.html`, `.css`, `.md`) or embedded in the JSON itself (as in the case of variables).

### Lifecycle
1.  **Creation/Editing**: Developer creates/edits files in the `resources/` folder.
2.  **Synchronization**: Scripts process the files and update the Database.
3.  **Consumption**: The system (`gestor.php`) reads from the Database to render the page.

---

## 🌍 Resource Types

### 1. Global Resources
Located in `gestor/resources/`. Are accessible throughout the system and do not depend on a specific module.
- **Examples**: Default Layout, Login Page, UI Components (Buttons, Modals).

### 2. Module Resources
Located in `modulos/{module-id}/resources/`. Are specific to a module and encapsulated within it.
- **Examples**: Module Dashboard Page, Specific Report Components.

---

## 📂 File Structure

The folder structure follows a strict pattern to ensure automatic detection by synchronization scripts.
**Note**: There is no intermediate `lang` folder. Languages are directly in the resources root.

### Global Structure (`gestor/resources/`)
```
gestor/resources/
├── pt-br/                     # Portuguese Language
│   ├── layouts/               # Layouts
│   ├── pages/                 # Pages
│   └── components/            # Components
├── en/                        # English Language
│   └── ...
├── components.json            # Global component metadata
├── layouts.json               # Global layout metadata
├── pages.json                 # Global page metadata
├── variables.json             # Global variables
└── resources.map.php          # Version and checksum map
```

### Module Structure (`modulos/{id}/resources/`)
```
modulos/{id}/resources/
├── {id}.json                  # Module configuration
└── resources/
    └── pt-br/
        ├── layouts/
        ├── pages/
        └── components/
```

### Resource Anatomy
Each resource has a folder with its ID containing the files.

**IMPORTANT**: The folder name (ID) is also the **Identifier (Natural Key)** of the record in the database table in the `id` field. This ensures the precise link between the physical file and the relational data.

```
pages/
└── my-example-page/               # Resource ID and NK in Database
    ├── my-example-page.html       # HTML Content
    └── my-example-page.css        # CSS Styles (Optional)
```

Metadata resides in the root JSON files (`pages.json`, etc.):
```json
{
    "id": "my-example-page",
    "name": "My Example Page",
    "caminho": "/example",
    "id_layouts": "default-layout"
}
```

---

## 🏗️ System Entities

The system divides resources into specific categories, each with a clear purpose:

### 1. Visual Resources

#### 📄 Pages (`paginas`)
Are the final elements published and accessible via URL.
- **Function**: Display specific content to the user.
- **Link**: Every page is mandatorily a "child" of a Layout.
- **Example**: "Home", "Contact Us", "Dashboard".

#### 🏗️ Layouts (`layouts`)
Function as the "shell" or structure of the page (similar to the union of Header + Footer in WordPress).
- **Function**: Define the common structure that repeats (header, footer, side menus).
- **Slot**: Has a placeholder where the Page will be inserted.
- **Example**: "Administrative Layout" (with sidebar), "Public Layout" (with top menu).

#### 🧩 Components (`componentes`)
Reusable HTML pieces that can appear in multiple places.
- **Function**: Avoid code repetition. Can be used within Pages, Layouts, or even within other Components.
- **Example**: "Action Button", "News Card", "Confirmation Modal".

#### 📋 Templates (`templates`)
Ready-made and pre-configured models of other resources.
- **Function**: Accelerate the creation of new Pages, Layouts, or Components by providing a standardized base.

### 2. Configuration

#### 🔧 Variables (`variaveis`)
Dynamic values that allow configuration via Administrative Panel.
- **Function**: Allow administrators to change behaviors or texts without touching the code.
- **Example**: "Site Title", "Primary Color", "API Key".

### 3. AI Ecosystem (`ai_*`)

The system has a robust structure to manage Artificial Intelligence instructions, stored in Markdown files (`.md`).

#### 🤖 AI Prompts (`ai_prompts`)
User-level instructions ("User Prompts").
- **Function**: Store specific creation requests. The user can create multiple prompts for different purposes.
- **Example**: "Create a landing page for a lawyer with 'About' and 'Contact' sections".

#### ⚙️ AI Modes (`ai_modes`)
System-level technical instructions ("System Prompts").
- **Function**: Guide the AI on **how** to format the response, not **what** to respond. It is the technical "instruction manual".
- **Flow**: The final prompt sent to the AI is usually the sum: `Mode + Prompt`.
- **Example**: "HTML/CSS Mode" (Instructs the AI to return code wrapped in specific markers and use TailwindCSS classes or another CSS framework).

#### 🎯 AI Targets (`ai_prompts_targets`)
Abstraction that defines the "Data Type" or destination of the generation.
- **Function**: Organize and categorize what is being generated. Serves for the system to understand where to save or how to treat the AI return.
- **Example**: "Pages", "Layouts", "Menus", "News". If the target is "Pages", the system knows it should treat the result with the page type.

---

## 🔄 Detailed Workflow

### Phase 1: Development (Local)
1.  **Editing**: The developer creates or edits resources in the `resources/` folder (manually or via CLI).
2.  **Resource Compilation**: Executes the script `atualizacao-dados-recursos.php`.
    - It reads all physical files.
    - Calculates checksums (HTML/CSS/MD).
    - Generates static data files in `gestor/db/data/` (e.g., `PaginasData.json`, `LayoutsData.json`).
3.  **Commit**: The physical files AND the generated JSON files are committed to Git.

### Phase 2: Deployment and Update (Server)
1.  **Release**: The system package (ZIP) contains the updated `*Data.json` files.
2.  **Installation/Update**: The script `atualizacoes-banco-de-dados.php` is executed.
    - It reads the `*Data.json` files.
    - Compares with the current Database.
    - Performs the **Upsert** (Insert or Update) respecting protection rules.

---

## 🛠️ Management Tools

### 1. Resources CLI (`upsert-resources.php`)
Powerful command-line tool (CLI) that acts as the **"Source of Truth"** for creating, editing, and removing resources. It manages both metadata (JSON) and physical files (HTML/CSS/MD).

- **Location**: `ai-workspace/scripts/resources/upsert-resources.php`
- **Usage Modes**:
    - **Interactive**: Guided menu with colors and options (just run without arguments or with `--interactive`).
    - **Arguments**: Direct execution for automation or quick use.

#### Main Parameters
| Parameter | Description | Options |
| :--- | :--- | :--- |
| `--action` | Action to be executed | `upsert` (create/update), `delete`, `copy` |
| `--lang` | Resource language | Ex: `pt-br`, `en`, `es` |
| `--target` | Operation target | `gestor` (default), `plugin`, `project` |
| `--scope` | Resource scope | `global` (default), `module` |
| `--type` | Resource type | `page`, `layout`, `component`, `variable`, `prompt_ia`, etc. |
| `--id` | Resource identifier | Ex: `my-page`, `my-component` |
| `--module-id` | Module ID (if scope=module) | Ex: `dashboard`, `admin-users` |
| `--plugin-type` | Plugin type (if target=plugin) | `public`, `private` |
| `--open` | Opens files in VS Code | `true` (flag) |
| `--new-id` | New ID (only for `copy` action) | Ex: `my-page-copy` |

#### Copy Functionality (`copy`)
Allows cloning resources between different contexts (e.g., copying a Global page into a Module, or from Manager to a Project).
- **Source Parameters**: `--source-target`, `--source-scope`, `--source-module-id`, `--source-lang` etc.
- **Renaming**: Use `--new-id` to save with a different name at the destination.

#### Usage Examples
```bash
# Interactive Mode (Recommended)
php ai-workspace/scripts/resources/upsert-resources.php

# Create a global page and open in editor
php ai-workspace/scripts/resources/upsert-resources.php --type=page --id=new-page --open

# Copy a global component to a module
php ai-workspace/scripts/resources/upsert-resources.php --action=copy --type=component --id=default-button --source-target=gestor --source-scope=global --source-module-id=my-module --source-lang=pt-br --target=gestor --scope=module --module-id=my-module --lang=en --new-id=default-button-copy

# Delete a resource
php ai-workspace/scripts/resources/upsert-resources.php --action=delete --type=layout --id=old-layout
```

### 2. The "Compiler": `atualizacao-dados-recursos.php`
**Responsibility**: Transform physical files into structured data for the database.
- **Location**: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- **Input**: Reads folders `gestor/resources/` and `modulos/*/resources/`.
- **Processing**:
    - Applies uniqueness rules (unique IDs per language/module).
    - Calculates Checksums: If HTML/CSS changed, increments version.
    - Detects Orphans: Invalid or duplicate resources are segregated.
- **Output**: Generates JSON files in `gestor/db/data/` folder:
    - `LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`, `VariaveisData.json`, etc.

### 3. The "Synchronizer": `atualizacoes-banco-de-dados.php`
**Responsibility**: Consume JSON data and apply to SQL Database.
- **Location**: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
- **Execution**: Runs during system installation or update via panel.
- **Upsert Logic**:
    - Reads `*Data.json` files generated by previous step.
    - Compares record by record with corresponding SQL table.
    - If record does not exist -> **INSERT**.
    - If record exists and is different -> **UPDATE**.
- **Data Protection (`user_modified` and `project`)**:
    The script has security mechanisms to not overwrite customizations:
    1.  **User Modification**: If the record in the database has `user_modified = 1` (indicating user edited via panel), the update is ignored.
    2.  **Project Protection**: If the record belongs to a specific project (`project` column filled) and the current update is system (not project), the update is also ignored.
    
    This ensures that manual customizations and specific project developments are not lost in general system updates.

---

## 🌐 Multilanguage and Versioning

### Hybrid Multilingual System
The system supports multiple languages through the `lang/{language}/` folder structure.
- The `resources.map.php` file maps which resources exist in which languages.
- The system loads the correct resource based on user preference or domain configuration.

### Automatic Versioning
Each change in physical files generates a new **Checksum**.
- If the checksum changes, the resource version is incremented (v1.0 -> v1.1).
- This ensures caches are invalidated and updates are applied correctly.

---

## 🎨 HTML Development Conventions

### Section Attributes in Pages
Whenever creating or editing HTML files for pages (in `resources/*/pages/`), add the following attributes to the main `<section>` tags:

- **`data-id`**: Incremental numeric value starting from 1, sequential per page (e.g., `data-id="1"`, `data-id="2"`).
- **`data-title`**: Semantic name of the section in plain text, without special formatting (e.g., `data-title="hero"`, `data-title="conn2flow-starter"`).

**Example**:
```html
<section class="text-center mb-16" data-id="1" data-title="hero">
    <!-- Section content -->
</section>
```

**Purpose**:
- Facilitates identification and manipulation of sections via JavaScript or CSS.
- Standardizes structure for AI agents and developers.
- Improves code semantics and accessibility.

This convention must be followed in all pages created in the resource system.
