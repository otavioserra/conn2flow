```markdown
# Project: Plugin Installation / Update System (Phase 1)

## 🎯 Initial Context
This document consolidates the planning to evolve the `admin-plugins` module (currently a simple name CRUD) into a **complete plugin installation and update manager**.

### Branches
1. `main`: System Development. Current existing environment.
2. `plugin-development`: Plugin Development. Environment we will create which will have the plugin development skeleton. Every new plugin will clone this environment to create a new plugin.
**Important Information**: there will be two different environments, as the first is focused on the main system, while the second is dedicated to plugin development.

### Script Architecture
1. Use the existing system update architecture as a basis for creating the plugin flow:
**System Development**:
- `atualizacao-dados-recursos.php` (generates data sources / *Data.json* of layouts, pages, components, variables) - Path: `gestor/controladores/agents/arquitetura/`. This script is consumed by GitHub Actions when releasing a version: `.github\workflows\release-gestor.yml`:
```yml
- name: Generate Resources Updates
      run: |
        cd gestor/controladores/agents/arquitetura
        php atualizacao-dados-recursos.php
        echo "Resource updates generated successfully"
```
**Test Environment**:
- Has a complete test environment made in docker. Paths: `docker`, `dev-environment\data\docker-compose.yml` and `dev-environment\data\Dockerfile`.
- `ai-workspace\scripts\atualizacoes\build-local-gestor.sh` - release simulation script similar to `release-gestor.yml`, used to generate the zip and hash and send to the local docker test environment. That is, it creates the artifact for this environment.
**System Update**:
- `atualizacoes-banco-de-dados.php` (synchronizes *Data.json* → database) - Path: `gestor/controladores/atualizacoes/`
- `atualizacoes-sistema.php` (orchestrates core update flow) - Path: `gestor/controladores/atualizacoes/`
- Both scripts above are consumed by the system update module: `gestor\modulos\admin-atualizacoes\admin-atualizacoes.php`, as well as can be run via CLI.
**GIT**
- The system has ready scripts for automating common tasks, such as commit and release. For release it uses `ai-workspace\git\scripts\release.sh` and for commit `ai-workspace\git\scripts\commit.sh`. In the case of release, the sh script still internally executes `ai-workspace\scripts\version.php` which automatically increases the system version.

2. Create specific scripts for the plugin flow:
**Plugin Development** - located in the `plugin-development` branch:
- `update-data-resources-plugin.php` (generates data sources / *Data.json* of layouts, pages, components, variables of a plugin) - Path: `utils/controllers/agents/`. This script will be consumed by GitHub Actions when releasing a version of any plugin: `.github\workflows\release-gestor-plugin.yml` (should be created based on `release-gestor.yml`):
```yml
- name: Generate Plugin Resources Updates
      run: |
        cd utils/controllers/agents/
        php update-data-resources-plugin.php
        echo "Plugin resource updates generated successfully"
```
**Test Environment**:
- Has a complete test environment made in docker. Therefore, to perform tests, it is implied that docker with `docker-compose.yml` and `Dockerfile` are correctly configured previously by the main system test environment. No need to do anything here, just ensure the environment is running.
- `ai-workspace\scripts\updates\build-local-gestor-plugin.sh` - create a release simulation script similar to `release-gestor-plugin.yml`, used to generate the zip and hash and send to the local docker test environment. That is, it creates the artifact for this environment. Use the same folder as the manager.
**Plugin Update** - located in the `main` branch along with the main system:
- `atualizacao-plugin.php` (manages the installation/update of a plugin for files and database) - Path: `gestor/controladores/plugins/`
- The script above will be consumed by the plugin update module: `gestor\modulos\admin-plugins\admin-plugins.php`, as well as can be run via CLI.
**GIT**
- The plugin manager should have ready scripts for automating common tasks, such as commit and release. For release it will use `ai-workspace\git\scripts\release.sh` and for commit `ai-workspace\git\scripts\commit.sh`. In the case of release, the sh script should execute a similar script `ai-workspace\git\scripts\version.php` which automatically increases the plugin version. That is, it will change the manifest version in this case

### General Objective (MVP Phase 1)
Implement minimum plugin installation/update flow:
1. Skeleton in `plugin-development`
2. Release workflow (generates ZIP + sha256 + Plugin Data.json)
3. Installation (upload / public GitHub / local dev path)
4. Incremental update (same pipeline)
5. Registration + versioning (manifest + checksum)
6. Reuse of existing routines without deep refactoring
7. Persistence of minimum metadata

Advanced items moved to document `modify-plugins-v2.md`.

## 🧩 Phase 1 Scope (Closed)
- Add minimum metadata in `plugins` (defined model) including private GitHub support.
- Origin pipeline (Phase 1):
    1. Manual upload `.zip`
    2. Public GitHub (release/tag or branch ZIP)
    3. Private GitHub (PAT via secure reference)
    4. Local path (dev) optional
- Extraction, validation and package registration.
- Minimum structure standardization (manifest + expected folders).
- **Automatic detection of all `*Data.json` files** in the `db/data/` directory.
- **Support for any table via `*Data.json` files** (not limited to hardcoded list).
- Selective synchronization with database (plugin resources).
- **Automatic cleanup of `db/` folder after processing**.
- **Automatic file permission correction**.
- Basic logging per file + execution status.
- Interface: list / install / update / reprocess / details (removal only Phase 2 as soft delete).
- Git version control in plugin skeleton (commit/release/version scripts).

## 🚫 Out of Scope (Phase 1)
- Dependencies between plugins (graph resolution) - Approach for the future.
- Automatic plugin rollback - Approach for the future - To simplify due to process complexity, we will only unzip data and copy them to the system plugins folder, update the database and keep a log of changes.
- Cryptographic signature / GPG verification - Use the same strategy as `atualizacoes-sistema.php` which uses a HASH file. In the case of the main manager uses: `gestor.zip` and `gestor.zip.sha256`.
- Sandbox / plugin PHP code execution isolation - Approach for the future.

## 🗃️ Data Model (Phase 1 – Minimum)
Add in `plugins` (or change if already exists):
- origin_type (upload|github_public|github_private|local_path)
- origin_reference (e.g. owner/repo, local path, internal identifier)
- origin_branch_tag (nullable)
- origin_credential_ref (nullable) (alias to fetch token outside database – replaces previous origin_token_ref)
- installed_version (nullable)
- package_checksum (nullable)
- manifest_json (text nullable)
- execution_status (idle|installing|updating|error|ok) default idle
- installation_date (datetime nullable)
- last_update_date (datetime nullable)

Postponed (V2+): last_execution_log, last_verification.

Slug = existing id field.

Tokens: will not be persisted; resolution via environment variable (e.g. PLUGIN_TOKEN_<REF>) or secure PHP config.

## 📦 Expected Plugin Package Structure with structure as well as comments on what each part of the skeleton is.
Note: Local release artifacts will be in the `ai-workspace/scripts/build/` folder. Core file name: `gestor.zip`; plugin file name: `gestor-plugin.zip` + `gestor-plugin.zip.sha256`.
```
.github/                                            (GitHub Actions configurations)
    workflows/                                      (GitHub Actions workflows)
        release-gestor-plugin.yml                   (workflow to release the manager plugin)
ai-workspace/                                       (AI workspace environment)
    git/                                            (Git data folder generated by AI)
        scripts/                                    (automation scripts for Git generated by AI)
            release.sh                              (manager plugin release script)
            commit.sh                               (manager plugin commit script)
            version.php                             (script that updates the manager plugin version)
    scripts/                                        (automation scripts generated by AI)
        build/                                      (folder storing local builds)
        updates/                                    (update scripts generated by AI)
            build-local-gestor-plugin.sh            (release simulation script generated by AI)
utils/                                              (plugin creation utilities)
    controllers/                                    (plugin controllers)
        agents/                                     (plugin agent controllers)
            update-data-resources-plugin.php        (generates data sources / *Data.json* of layouts, pages, components, variables of a plugin)
plugin/                                             (specific plugin root)
	manifest.json                                   (mandatory)
    controllers/                                    (plugin controllers)
        controller-id/                              (specific controller. The plugin can have 0-n controllers)
            controller-id.php                       (specific controller php)
	modules/                                        (folder with all plugin modules)
        module-id/                                  (specific module always follows this pattern for automatic connection with the system. The plugin can have 0-n modules)
            resources/                              (specific module resources - structure similar to main system)
                pt-br/                              (specific module resources in pt-br language[can have en,es,etc. in same structure])
                    pages/                          (specific module pages)
                        page-id/                    (directory storing specific HTML and CSS of page id: `page-id`. 0-n pages)
                            page-id.css             (specific page CSS - Optional)
                            page-id.html            (specific page HTML - Optional)
                    layouts/                        (specific module layouts)
                        layout-id/                  (directory storing specific HTML and CSS of layout id: `layout-id`. 0-n layouts)
                            layout-id.css           (specific layout CSS - Optional)
                            layout-id.html          (specific layout HTML - Optional)
                    components/                     (specific module components)
                        component-id/               (directory storing specific HTML and CSS of component id: `component-id`. 0-n components)
                            component-id.css        (specific component CSS - Optional)
                            component-id.html       (specific component HTML - Optional)
            modulo-id.json                          (mapping of pages, layouts and components of specific module, as well as variables and other variables that will be consumed in the module)
            modulo-id.js                            (specific module javascript)
            modulo-id.php                           (specific module php, it is referenced in gestor.php to be executed)
    resources/                                      (global plugin resources - structure similar to main system)
        pt-br/                                      (global specific resources in pt-br language[can have en,es,etc. in same structure])
            pages/                                  (global pages follows same pattern as modules)
            layouts/                                (global layouts follows same pattern as modules)
            components/                             (global components follows same pattern as modules)
            components.json                         (global plugin components)
            layouts.json                            (global plugin layouts)
            pages.json                              (global plugin pages)
            variables.json                          (global plugin variables)
        resources.map.php                           (global resources mapping)
    db/                                             (plugin database)
        data/                                       (specific plugin data in *Data.json* format generated by `atualizacao-dados-recursos-plugin.php` in plugin development and stored here)
	    migrations/                                 (specific plugin migrations)
	assets/                                         (css/js/images)
	vendor/                                         (if isolated – evaluate policy)
```

### modulo-id.json - metadata of resources of each module, as well as variables inside the module that will be consumed by `modulo-id.php`:
```json
{
    "versao": "1.0.0",
    "bibliotecas": [
        "biblioteca-id"
    ],
    "tabela": {
        "nome": "tabela",
        "id": "id",
        "id_numerico": "id_tabela",
        "status": "status",
        "versao": "versao",
        "data_criacao": "data_criacao",
        "data_modificacao": "data_modificacao"
    },
    "resources": {
        "pt-br": {
            "layouts": [
                {
                    "name": "Layout Name",
                    "id": "layout-id",
                    "version": "1.0",
                    "checksum": {
                        "html": "474e067290ce9318c978ab463c3ed895",
                        "css": "c3fd0dfa321e5a4f032ff574cc07a4fb",
                        "combined": "1406ab702ddefb4fd6ae89fbaabdbf18"
                    }
                }
            ],
            "pages": [
                {
                    "name": "Page Name",
                    "id": "page-id",
                    "layout": "layout-id",
                    "path": "path-page-id\/",
                    "type": "system",
                    "option": "option",
                    "root": true,
                    "version": "1.0",
                    "checksum": {
                        "html": "8f33d8113e655162a32f7a7213409e19",
                        "css": "da65a7d1abba118408353e14d6102779",
                        "combined": "ddb032331dd7e8da25416f3ac40a104a"
                    }
                }
            ],
            "components": [
                {
                    "name": "Component Name",
                    "id": "component-id",
                    "version": "1.0",
                    "checksum": {
                        "html": "7fb861d588aebb98b48ff04511e06943",
                        "css": "",
                        "combined": "7fb861d588aebb98b48ff04511e06943"
                    }
                }
            ],
            "variables": [
                {
                    "id": "variable-id",
                    "value": "Value",
                    "type": "type"
                }
            ]
        }
    }
}
```

### resources.map.php - Map with paths to resource metadata:
```php
<?php

/**********
	Description: resources mapping.
**********/

// ===== Variable definition.

$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Portuguese (Brazil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
                'variables' => 'variables.json',
            ],
            'version' => '1',
        ],
    ],
];

// ===== Return the variable.

return $resources;
```

### components.json - metadata of each component (HTML and CSS directly in folder as per previous guidance):
```json
[
    {
        "name": "Component Name",
        "id": "component-id",
        "version": "1.0",
        "checksum": {
            "html": "7fb861d588aebb98b48ff04511e06943",
            "css": "",
            "combined": "7fb861d588aebb98b48ff04511e06943"
        }
    }
]
```

### layouts.json - metadata of each layout (HTML and CSS directly in folder as per previous guidance):
```json
[
    {
        "name": "Layout Name",
        "id": "layout-id",
        "version": "1.0",
        "checksum": {
            "html": "474e067290ce9318c978ab463c3ed895",
            "css": "c3fd0dfa321e5a4f032ff574cc07a4fb",
            "combined": "1406ab702ddefb4fd6ae89fbaabdbf18"
        }
    }
]
```

### pages.json - metadata of each page (HTML and CSS directly in folder as per previous guidance):
```json
[
    {
        "name": "Page Name",
        "id": "page-id",
        "layout": "layout-id",
        "path": "path-page-id/",
        "type": "system",
        "option": "option",
        "root": true,
        "version": "1.0",
        "checksum": {
            "html": "8f33d8113e655162a32f7a7213409e19",
            "css": "da65a7d1abba118408353e14d6102779",
            "combined": "ddb032331dd7e8da25416f3ac40a104a"
        }
    }
]
```

### variables.json - metadata of each variable, as well as its values:
```json
[
    {
        "id": "variable-id",
        "value": "Value",
        "type": "type"
    }
]
```

### Manifest (Fields – Phase 1)
```
{
	"id": "my-plugin-x",           // unique slug
	"nome": "My Plugin X",
	"versao": "1.2.0",
	"descricao": "Advanced functions ...",
	"compatibilidade": { "min": "1.0.0", "max": "2.x" },
	"autor": "Org / Dev",
	"license": "MIT",
	"recursos": { "layouts": true, "pages": true, "components": true, "variables": true },
	"scripts_pos_instalacao": ["php scripts/setup.php"],
	"checksum_override": null
}
```

Validations:
- `id` mandatory (slug-safe)
- `versao` semantic
- `compatibilidade` used for alert (not blocking in phase 1)

## 🔄 Pipeline (Installation / Update)
1. Select plugin (new / existing)
2. Define origin (upload / github_public / github_private / local_path)
3. Get package (upload → temp, github → download ZIP, local → copy/zip)
4. Calculate checksum + validate with .sha256 if exists
5. Extract to staging: `gestor/temp/plugins/<slug>/`
6. Validate manifest + minimum structure
7. Copy final directory (safe overwrite) to plugins destination.
    - Use new path directory `gestor/plugins/<slug>/`.
8. **Execute migrations (if enabled)**
9. **Automatically detect all `*Data.json`** in `db/data/` directory
10. **Synchronize resources for each detected file (layouts, pages, components, variables, modules, custom tables)**
11. **Cleanup of installed plugin `db/` folder**
12. **Permission correction (recursive chown)**
13. Persist metadata (version, checksum, dates)
14. Clear staging (except debug mode)
15. Register final log

### Flows by Origin
| Origin | Download Action | Observations |
|--------|-----------------|--------------|
| Upload | Receives ZIP | Size / extension validation |
| Public GitHub | GET https://codeload.github.com/{owner}/{repo}/zip/{ref} | Ref = branch or tag |
| Private GitHub | Authenticated GET (Authorization: token <PAT>) | Token via `origin_credential_ref` (external lookup) |
| Local Path | Internal Copy/Zip | Dev use / disableable in production |

## ♻️ Component Reuse (Status)
Data.json generation done in plugin release pipeline. Installer only consumes provisioned files.

## 🔐 Security (Minimum Scope)
- `.zip` extension + size limit
- Path normalization (traversal block)
- Slug/ID sanitization
- SHA256 Checksum
- Private tokens: do not persist in database; only reference (`origin_credential_ref`).
- Logs do not display token (only symbolic reference).

## 🧪 Tests (Initial Plan)
- Simple valid upload.
- Repeated upload without change (same checksum → skip data synchronization).
- Public GitHub with different branch.
- Private GitHub with valid credential.
- Private GitHub missing credential → controlled error.
- Invalid manifest (missing `id`).
- Structure missing `manifest.json`.
- Slug conflict already installed with another different `id` → error.

## 🗂️ Logs & Exit Codes (Proposal)
Prefix: `PLG_`
- `PLG_EXIT_OK = 0`
- `PLG_EXIT_PARAMS_OR_FILE = 10`
- `PLG_EXIT_VALIDATE = 11`
- `PLG_EXIT_MOVE = 12`
- `PLG_EXIT_DOWNLOAD = 20`
- `PLG_EXIT_ZIP_INVALID = 21`

Current (implemented in code): 0,10,11,12,20,21.

Implementation Status:
- Public/private GitHub download: Implemented with cURL/stream fallback.
- Granular Data.json synchronization: phase 1 registers statistics and copies file (granular upsert pending Phase 1.1).
- UI admin-plugins: install/update/reprocess actions + manifest and metadata display implemented (baseline, without advanced validations).

Log format: `[DATE] [LEVEL] [PLUGIN:slug] Message`

## 🧱 Code Structure (Phase 1)
- gestor/controladores/plugins/atualizacao-plugin.php (CLI / orchestrator)
- gestor/bibliotecas/plugins-installer.php (helpers)
- gestor/temp/plugins/ (staging)
- gestor/db/data/plugins/<slug>/ (Data.json generated in plugin release)

## 🔌 Interface Integration (`admin-plugins`)
New actions:
- `add` → form configures origin + optional upload.
- `install` (POST) → triggers pipeline.
- `update` → re-executes pipeline if remote checksum changed.
- `reprocess` → forces resource regeneration even without checksum change.
- `details` → shows manifest + history.

Form fields by origin:
- Common: name, slug (auto), description (optional)
- GitHub: owner, repo, branch/tag
- Private: same + credential reference
- Upload: input file
- Local: validated absolute/relative path

## 🔄 Versioning / Checksum
- Compare manifest `versao` + `package_checksum`.
- If checksum equal → mark as "no change" and do not regenerate resources (except if `reprocess`).

## 🧷 *Data.json* Generation Strategy
Generated in plugin release workflow (segregated by plugin). Installer does not generate, only reads and synchronizes.

## 🧩 Database Synchronization (Phase 1)
- Infer scope via names/prefixes
- Insert if not exists; update if different checksum
- No automatic physical removal

## 🚀 Roadmap (Summary)
P1: MVP (this document)
P2: Private GitHub + credentials + plugin in resources + re-check
P3: Dependencies + partial rollback + uninstallation
P4: Metrics / telemetry / signatures

## ✅ Consolidated Decisions Phase 1
1. Mandatory prefix in resource IDs: `plg_<slug>_`.
2. Downgrade blocked (flag `--force` postponed P2).
3. Do not store installed ZIP (backup Phase 2).
4. Complete overwrite of plugin directory (removal + new copy).
5. Staging always clean (debug mode preserve postponed P2).
6. Description only in `manifest_json` (no column, reevaluate later).
7. Index (origin_type, origin_reference) postponed (possible future optimization).
8. Final directory defined: `gestor/plugins/<slug>` (path abstraction for future change if necessary).
9. Plugin artifact: `gestor-plugin.zip` + `gestor-plugin.zip.sha256` in same core folder.
10. Private GitHub included already in Phase 1 via `origin_credential_ref`.
11. Tokens not persisted; resolution only environment/config.
12. Git versioning scripts present in plugin skeleton.

## ❓ Residual Pending Items to Confirm Before Implementation
- Final credential field name: keep `origin_credential_ref` (proposed) ✅?
- Default credential source: `.env` (PLUGIN_TOKEN_<REF>) + fallback PHP config ✅?
- Logs: mask origin (e.g. `cred=github_private:MYREF`) without token ✅?

Confirmation of these 3 points releases implementation start.

## ✅ Implementation Progress (Checklist P1)
 - [x] Migration new `plugins` fields
 - [x] Branch orphan `plugin-development` (postponed to end as per strategy) - Strategy abandoned, using `dev-plugins` folder inside same repository.
 - [x] Plugin base skeleton (initial structure + manifest)
 - [x] Plugin release workflow and `build-local-gestor-plugin.sh`
 - [x] Example plugin (basic test-plugin)
 - [x] Script update-data-resources-plugin.php (stub)
 - [x] Script atualizacao-plugin.php (complete orchestrator)
 - [x] Upload ZIP (pipeline + UI fields)
 - [x] Public/Private GitHub Download
 - [x] Secure extraction (implemented)
 - [x] Manifest validation (with basic errors)
 - [x] Checksum calculation/compare
 - [x] Copy assets/modules/resources (overwrite final directory)
 - [x] **Automatic detection of `*Data.json`** with `glob()`
 - [x] **Function `tabelaFromDataFile()`** for dynamic conversion
 - [x] **Support for any table** via `*Data.json`
 - [x] Granular database synchronization (layouts/pages/components/variables/modules + custom tables)
 - [x] **Automatic cleanup of `db/` folder**
 - [x] **Automatic permission correction** with `chown -R`
 - [x] Persist metadata (installed version, checksum, dates)
 - [x] Logs & exit codes (centralized constants)
 - [x] Install/update/details interface
 - [x] Complete manual tests
 - [x] Updated documentation
 - [ ] Additional manual tests

**Implementations Performed:**
- Dynamic detection system: `glob('*Data.json')` replaces hardcoded list
- Function `tabelaFromDataFile()`: Converts `ExampleTableData.json` → `example_table`
- Post-installation cleanup: Removes `db/` folder automatically
- Permission correction: `chown -R` using parent folder owner/group
- Unlimited support: Any plugin can update any table via `*Data.json`
- Enhanced logs: `[ok] db/ folder removed`, `[ok] permissions corrected`

Note: exit constants centralized in `gestor/bibliotecas/plugins-consts.php` and used in orchestrator/installer.

## 🛠️ Immediate Actions (Awaiting GO)
1. Answer Open Doubts
2. Close migration fields
3. Confirm overwrite + backup ZIP
4. Create orphan branch
5. Implement migration + skeleton

---
**Date:** 09/02/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow (Plugins Manager Phase 1)
```