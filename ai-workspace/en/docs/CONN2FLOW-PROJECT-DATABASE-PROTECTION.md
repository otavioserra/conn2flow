# Conn2Flow - Project-Based Database Protection

## 📋 Index
- [🎯 Context and Problem](#🎯-context-and-problem)
- [🏗️ Solution Architecture](#🏗️-solution-architecture)
- [💾 Database Changes](#💾-database-changes)
- [🔧 Technical Implementation](#🔧-technical-implementation)
- [📦 API and Scripts](#📦-api-and-scripts)
- [🧪 Tests and Validation](#🧪-tests-and-validation)
- [📖 References](#📖-references)

---

## 🎯 Context and Problem

### Identified Problem
During normal Conn2Flow system updates, all records modified by project deployment via OAuth API were being overwritten. This occurred because normal system updates (version updates) did not distinguish between records modified by project deployment versus normal modifications.

### Implemented Solution
Marking system similar to `user_modified`, but specific for projects. When a record is updated via project deployment, it is marked with the project ID, preventing future normal system updates from overwriting it.

### Affected Tables
- `componentes` ✅ (has user_modified)
- `layouts` ✅ (has user_modified)
- `paginas` ✅ (has user_modified)
- `variaveis` ✅ (has user_modified)
- `templates` ✅ (has user_modified)

### Added Field
- `project` (VARCHAR(255) NULL) - Stores the ID of the project that made the last update

---

## 🏗️ Solution Architecture

### Project Deploy Flow
1. Script `deploy-projeto.sh` identifies `PROJECT_TARGET` from environment.json
2. Sends ZIP + header `X-Project-ID: $PROJECT_TARGET` to API
3. API executes update with `--project=$PROJECT_TARGET`
4. Update overwrites data and marks `project = PROJECT_TARGET`
5. Records become protected against future normal updates

### Normal Update Flow
1. Normal system update is executed
2. Records with `project IS NOT NULL` are skipped (not updated)
3. Records with `user_modified = 1` are preserved (existing logic)
4. Only records without marking are updated

### Protection Logic
- **Deploy with --project**: Always overwrites and marks with project ID
- **Normal Update**: Respects project markings
- **user_modified = 1**: Always prioritized (user has full control)

### Usage Scenarios
- **Project Deploy**: Updates everything and marks with project ID
- **Normal Update**: Respects project markings
- **User Modifies**: `user_modified=1` overwrites project protection

---

## 💾 Database Changes

### Created Migration
**File**: `gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php`

```php
final class AddProjectFieldToResourceTables extends AbstractMigration
{
    public function change(): void
    {
        $tables = ['componentes', 'layouts', 'paginas', 'variaveis', 'templates'];
        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('project', 'string', ['limit' => 255, 'null' => true])
                  ->update();
        }
    }
}
```

### Table Structure
All affected tables now have:
- `project` VARCHAR(255) NULL - ID of the project that made the last update

---

## 🔧 Technical Implementation

### Modifications in sincronizarTabela()
**File**: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`

```php
// Project verification
$project = $GLOBALS['CLI_OPTS']['project'] ?? null;

// Protection applied only in the 5 tables of $preserveMap
if (isset($preserveMap[$tabela])) {
    if (!$project && !empty($exist['project'])) {
        if (empty($exist['user_modified']) || (int)$exist['user_modified'] !== 1) {
            // Skip record protected by project
            continue;
        }
    }
}

// Marking during updates/inserts
if (isset($preserveMap[$tabela]) && $project) {
    $diff['project'] = $project; // For updates
    $row['project'] = $project;  // For inserts
}
```

### Variable $preserveMap
Defines tables that support protection:
```php
$preserveMap = [
    'paginas'      => ['nome','layout_id','caminho','framework_css','sem_permissao','html','css'],
    'layouts'      => ['nome','framework_css','html','css'],
    'componentes'  => ['nome','modulo','framework_css','html','css'],
    'templates'    => ['nome','target','framework_css','html','css'],
    'variaveis'    => ['valor']
];
```

---

## 📦 API and Scripts

### Project Deploy API
**File**: `gestor/controladores/api/api.php`

```php
function api_project_update() {
    // Receive project ID from header
    $project_id = $_SERVER['HTTP_X_PROJECT_ID'] ?? null;
    
    // Pass to update
    api_executar_atualizacao_banco($project_path, $project_id);
}
```

### Deploy Script
**File**: `ai-workspace/scripts/projects/deploy-projeto.sh`

```bash
# Send header with project target
curl -H "Authorization: Bearer $token" \
     -H "X-Project-ID: $project_target" \
     -F "project_zip=@$zip_file" \
     "$api_url"
```

### PROJECT_TARGET
- String from `devEnvironment.projectTarget` in environment.json
- Examples: "digitalfluxus", "project-test", "my-project"

---

## 🧪 Tests and Validation

### Tested Scenarios
1. **Project Deploy**: Marks records with correct project ID
2. **Normal Update**: Skips records marked by project
3. **user_modified**: Overwrites project protection
4. **Rollback**: Reversible migration

### Validation in Test Environment
- ✅ Deploy marks correctly with project string
- ✅ Normal updates respect markings
- ✅ user_modified priority maintained
- ✅ No impact on other tables

### Validation in Production
- Awaiting tests in production environment
- Verify behavior with real data

---

## 📖 References

### Modified Files
- `gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php`
- `gestor/controladores/api/api.php`
- `ai-workspace/scripts/projects/deploy-projeto.sh`
- `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`

### Related Documentation
- [Template System](CONN2FLOW-MANAGER-DETAILS.md#📝-template-system)
- [Database](CONN2FLOW-MANAGER-DETAILS.md#💾-database)
- [System API](CONN2FLOW-MANAGER-DETAILS.md#🌐-web-system)

### Version
- **Implemented in**: Conn2Flow v2.5.1
- **Date**: 13/11/2025
- **Developer**: Otavio Serra

---

## 🔁 Exception: Forced Update (`forcar_atualizacao`) (BATCH-056)

The `project` / `user_modified` protection can be **deliberately bypassed** for specific records declared in `forcar_atualizacao` (in the module's `tabela.config` or in the global `tables_config.json`; consolidated into `schema-metadata.json`).

For the matching records (by `pk` or `natural_key`), the updater:
- **ignores** the `project` check (project-deploy records are re-synchronized);
- **ignores** the `user_modified` preservation (applies the full deploy payload);
- **resets `user_modified = 0`** when it was `1`, realigning the record with the codebase;
- **preserves** the `project` value (neither changed nor cleared).

This is the recommended mechanism to fix, on a deploy, records that drifted due to manual editing or a prior project deploy.

---
*This documentation details the implementation of project-based database protection, ensuring that project deployments are not overwritten by normal system updates.*
