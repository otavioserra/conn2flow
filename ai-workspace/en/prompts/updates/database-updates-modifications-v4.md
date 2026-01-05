````markdown
# Prompt Interactive Programming - Project Updates Do Not Update Database Marked as Project

## 🎯 Initial Context

**Identified Problem:**
During normal system update, all records updated by project deploy via OAuth API (implemented in v2.4.0), the database updater is overwriting records in the database that were modified by the project deploy. This occurs because normal system updates (version updates) do not distinguish between modified records updated via project deploy.

**Proposed Solution:**
Implement a marking system similar to `user_modified`, but for projects. When a record is updated via project deploy, it will be marked with the project ID, preventing normal system updates from overwriting it.

**Affected Tables:**
- `componentes` ✅ (has user_modified)
- `layouts` ✅ (has user_modified)
- `paginas` ✅ (has user_modified)
- `variaveis` ✅ (has user_modified)
- `templates` ✅ (has user_modified - migration 20251030160430_create_templates_table.php)

**Field to be Added:**
- `project` (VARCHAR(255) NULL) - Stores the ID of the project that made the last update.

**PROJECT_TARGET Format:**
- Project identifier string (ex: "digitalfluxus", "my-project")
- Obtained from `devEnvironment.projectTarget` in environment.json
- Used as key to access project-specific configurations

### 🏗️ Proposed Architecture

**Project Deploy Flow (WITH --project):**
1. Script `deploy-projeto.sh` identifies `PROJECT_TARGET`
2. Sends ZIP + header `X-Project-ID: $PROJECT_TARGET` to API
3. API executes update with `--project=$PROJECT_TARGET`
4. Update overwrites data normally and marks `project = PROJECT_TARGET`
5. Records become protected against future normal updates

**Normal Update Flow (WITHOUT --project):**
1. Normal system update is executed
2. Records with `project IS NOT NULL` are skipped (not updated)
3. Records with `user_modified = 1` are preserved (existing logic)
4. Only unmarked records are updated normally

**Protection Logic:**
- **Deploy with --project**: Always overwrites and marks with project ID
- **Normal Update**: Skips records with `project IS NOT NULL` (except if `user_modified = 1`)
- **user_modified = 1**: Always prioritized (user has full control, overwrites any protection)

**Usage Scenarios:**
- Project deploy: Updates everything and marks with project ID
- Normal update: Respects project markings (does not overwrite)
- User modifies: `user_modified=1` allows overwriting project protection

## 📝 Guidelines for the Agent

### 🎯 Project Objectives:
1. **Create Database Migration**: Add `project` field in specified tables
2. **Update Deploy API**: Modify endpoint `/_api/project/update` to mark records with project ID
3. **Update Deploy Script**: Modify `deploy-projeto.sh` to pass project target
4. **Test Integration**: Verify that normal updates respect project marking

### 📋 Implementation Steps:

#### Step 1: Create Database Migration
- Create new migration in `gestor/db/migrations/`
- Add field `project` VARCHAR(255) NULL in tables:
  - componentes ✅ (already has user_modified)
  - layouts ✅ (already has user_modified)
  - paginas ✅ (already has user_modified)
  - variaveis ✅ (already has user_modified)
  - templates ✅ (already has user_modified - migration 20251030160430_create_templates_table.php)
- Execute migration and verify table structure

#### Step 2: Update Project Deploy API
- Modify `gestor/controladores/api/api.php` endpoint `/_api/project/update`
- During ZIP processing and database update, mark records with project ID
- Use `PROJECT_TARGET` passed via HTTP header `X-Project-ID` or request body parameter
- Implement logic to define `project = ?` during INSERT/UPDATE of records

#### Step 3: Update Deploy Script
- Modify `ai-workspace/scripts/projects/deploy-projeto.sh`
- Add header `X-Project-ID: $PROJECT_TARGET` in curl request to API
- Verify if API receives and processes project ID correctly

#### Step 4: Update Normal Update Logic
- Modify `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
- **When WITHOUT --project**: Add condition to skip records with `project IS NOT NULL`
- **When WITH --project**: Overwrite normally and define `project = PROJECT_ID` in all updated/inserted records
- Maintain priority of `user_modified = 1` (user always has full control)
- Add logging for skipped records due to project markings
- Implement logic:
  - If `--project` defined: update everything and mark with project ID
  - If `--project` not defined: skip records with `project IS NOT NULL` (except if `user_modified = 1`)

#### Step 5: Tests and Validation
- Test project deploy marking records correctly
- Test normal update respecting markings
- Verify rollback and recovery in case of failures

### 🔧 Technical Requirements:
- **PHP**: 8.1+
- **MySQL/MariaDB**: 5.7+
- **Migrations**: Use Phinx for migrations
- **API**: OAuth 2.0 authentication
- **Scripts**: Bash for deploy automation

### 🔧 Technical Implementation Details

**In `sincronizarTabela()` - `atualizacoes-banco-de-dados.php`:**
```php
// Verify if it is project execution
$project = !empty($GLOBALS['CLI_OPTS']['project']) ?? null;

// During updates/inserts:
if ($project) {
    // Project deploy: always overwrite and mark
    $row['project'] = $project;
} else {
    // Normal update: skip records marked with project
    if (!empty($exist['project'])) {
        // Skip this record if not user_modified
        if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
            log_disco("SKIP_PROJECT_PROTECTED table=$tabela key=$k project={$exist['project']}");
            continue;
        }
    }
}
```

**In `api_executar_atualizacao_banco()` - `api.php`:**
```php
// Receive PROJECT_TARGET via header or parameter
$projectId = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;

// Pass to update script
$cli['project'] = $projectId;
```

**In `deploy-projeto.sh`:**
```bash
# Add header in curl request
curl -H "X-Project-ID: $PROJECT_TARGET" ...
```

## 🤔 Doubts and 📝 Suggestions

**Pending Doubts:**
- ✅ Table `templates` exists and has `user_modified` (migration verified)
- ✅ PROJECT_TARGET is a string from environment.json (ex: "digitalfluxus")
- ✅ Logic defined: --project = overwrite and mark; without --project = respect markings
- How to pass PROJECT_TARGET to API? Via HTTP header `X-Project-ID` or CLI parameter?
- How to implement `project = PROJECT_ID` marking during updates/inserts in script?
- Need migration rollback to remove field if necessary?
- How to clear project markings when necessary (ex: revert deploy)?

**Implementation Suggestions:**
- Use transactions in database to ensure atomicity of operations
- Add detailed logging for conflict debug
- Implement CLI command to clear project markings if necessary
- Consider adding field `project_updated_at` TIMESTAMP for audit
- Test thoroughly: deploy marks, normal update respects, user_modified overwrites

## ✅ Implementation Progress
- [x] Complete analysis of current code (API, scripts, migrations) - Tables verified, PROJECT_TARGET identified
- [x] Protection logic defined: --project = overwrite/mark; without --project = respect markings
- [x] Parameter --project already implemented in script atualizacoes-banco-de-dados.php
- [x] Creation of migration to add `project` field
- [x] Migration test in development environment
- [x] API modification to pass --project to update script
- [x] Update of script deploy-projeto.sh to send PROJECT_TARGET
- [x] Implementation of conditional logic in sincronizarTabela() - Applied only in 5 tables of $preserveMap
- [x] Complete integration tests
- [x] Documentation of changes
- [ ] Validation in production (staging first)

---
**Date:** 11/13/2025 (Updated with corrections and defined logic)
**Developer:** Otavio Serra
**Project:** Conn2Flow v2.5.1 - Project-Based Database Update Protection

**Status:** Project updated with 11 corrections applied. Logic defined: deploy with --project overwrites and marks; normal update respects markings. Ready for analysis and implementation.
````