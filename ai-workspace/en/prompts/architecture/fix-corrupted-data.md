````markdown
# Prompt Interactive Programming
- Definitions of the entire programming infrastructure that will be used by AI agents to interact with the user and generate code dynamically are defined below.
- Agents will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part at any time. The user will be attentive and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agents can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by agents and users using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 AI Agents
- **Development Agent**: Responsible for creating and modifying these guidelines and the application source code. **You are this agent**
- **GIT Agent**: Responsible for managing the source code repository and project versions. It will be run by another agent that will read and interpret the changes. These changes must be defined by the **Development Agent**. To do this, create/modify the file inside the folder with all modifications for message creation by the GIT assistant: `ai-workspace\git\arquitetura\corrigir-dados-corrompidos.md`
- **Docker Agent**: Responsible for managing Docker containers and related infrastructure. It will be run by another agent that will read and interpret the changes. These changes must be defined by the **Development Agent**.

### Instructions for the GIT Agent
Dedicated file with versioning guidelines created in: `ai-workspace/git/arquitetura/corrigir-dados-corrompidos.md`.

Immediate GIT Checklist:
- [x] Create GIT instructions file
- [x] Update main specification with this section
- [ ] Perform structured commit (`feat` or `docs` as decided) including:
    - `gestor/controladores/agents/arquitetura/corrigir-dados-corrompidos.php`
    - `ai-workspace/prompts/arquitetura/corrigir-dados-corrompidos.md`
    - `ai-workspace/git/arquitetura/corrigir-dados-corrompidos.md`
- [ ] Push to main branch or open PR

Suggested message:
```
docs(architecture/fix-data): add GIT Agent instructions and finalize specification

Context: final formalization of the correction script (no current diffs) and creation of the GIT guide.
Changes:
- Marks implementation checklist as completed
- Adds GIT Agent Instructions section
- Creates file git/arquitetura/corrigir-dados-corrompidos.md
Validation:
- Dry-run executed without differences (legacy & seeds)
```

## ⚙️ Implementation Settings
- Implementation name: $implementationName = `Corrupted Data`
- Base path: $base = `gestor\controladores\agents\arquitetura`.
- Implementation file name: $implementationFileName = $base + `corrigir-dados-corrompidos.php`.
- Backup folder path if necessary: $backupPath = `backups\arquitetura`.
- Logs folder path: $logsPath = `gestor\logs\arquitetura`.
- Languages folder path: $languagesPath = `gestor\controladores\agents\arquitetura\lang\`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in a .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow the defined design patterns, and be modular.** All guidelines must be included in the code comments.

## 📖 Libraries
- Log generation: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Can change if necessary.
- Lang functions: `gestor\bibliotecas\lang.php`: `_()` > Necessary to define.

## 🧪 Testing Environment
- There is a ready and functional testing infrastructure. The environment settings are in the file `docker\dados\docker-compose.yml`
- The testing environment is in the folder `docker\dados\sites\localhost\conn2flow-gestor`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect the changes, follow the file for synchronization: `docker\utils\sincroniza-gestor.sh checksum`

## 🎯 Initial Context
1. There was a problem with the migration of data from the original INSERTs to the new resource format. Randomly I discovered that the 'option' field of the original .SQL files was not correctly migrated. Therefore, we need to create a script to correct these data.
2. We made a previous script that searched by regex directly in the .SQL files. To avoid this problem again, I created a new database named `conn2flow_old` and included the original data there. The data is in the following tables: `paginas`, `layouts`, and `componentes`.
3. The access credentials are inside the testing environment in the environment variable: `docker\dados\sites\localhost\conn2flow-gestor\autenticacoes\localhost\.env`. In this variable is the manager database named `conn2flow` and other access fields.
4. Inside the global folder `gestor\resources\pt-br` you have a .JSON file of the other data of a resource for each resource in English: `paginas` => `pages`, `layouts` => `layouts`, and `componentes` => `components`. Example of data of a page is in `gestor\resources\pt-br\pages.json`.
5. Inside the folder of each module `gestor\modulos\{modulo-id}\` you have a .JSON file of the other data of a resource with the name `{modulo-id}.json`. For each resource in English is: `paginas` => `pages`, `layouts` => `layouts`, and `componentes` => `components`, you have an index in the JSON `resources.pt-br.{resource-name}`. Example of a JSON of a module: `gestor\modulos\admin-arquivos\admin-arquivos.json`.
6. The data reference is made using the `id` field in the 3 resources.
7. A page necessarily has a linked layout. In the original data this is done using the `id_layouts` value. In the .JSON file the reference is made using the `id` of the layouts, ignoring the numeric id itself. But, it will be necessary for you to use the numeric value to find the `id` and reference correctly in the final .JSON.
Global resources are stored in the folder `gestor\resources\pt-br`. Module resources are stored each resource belonging to a module in the module folder `gestor\modulos\{modulo-id}\resources\pt-br\`.
8. Inside the global folder `gestor\resources\pt-br` you have a sub-folder for each resource in English: `paginas` => `pages`, `layouts` => `layouts`, and `componentes` => `components`, with the folder name the same as the resource itself, where the `html` and `css` files are stored in a sub-folder with the name of the resource `id`: `gestor\resources\pt-br\{resource-name}\{resource-id}\{resource-id}.html|css`. Example of `html` and `css` of a page with id == 'test-id': `gestor\resources\pt-br\pages\test-id\test-id.html` and/or `gestor\resources\pt-br\pages\test-id\test-id.css`.
9. Inside the folder of each module `gestor\modulos\{modulo-id}\resources\pt-br\` you have a sub-folder for each resource in English: `paginas` => `pages`, `layouts` => `layouts`, and `componentes` => `components`, with the folder name the same as the resource itself, where the `html` and `css` files are stored in a sub-folder with the name of the resource `id` that is linked to a module: `gestor\modulos\{modulo-id}\resources\pt-br\{resource-name}\{resource-id}\{resource-id}.html|css`. Example of `html` and `css` of a page with id == 'test-id': `gestor\modulos\{modulo-id}\resources\pt-br\pages\test-id\test-id.html` and/or `gestor\modulos\{modulo-id}\resources\pt-br\pages\test-id\test-id.css`.
10. Formatting of the other data of a resource and what is necessary to compare:
```json
[
    { // Example of `layout` record
        "name": "nome", // Value of field "nome" equal to .SQL 
        "id": "id", // Value of field "id" equal to .SQL 
        "version": "1.0", // Ignore
        "checksum": {
            "html": "", // Ignore
            "css": "", // Ignore
            "combined": "" // Ignore
        }
    },
    ...
]

[
    { // Example of `page` record
        "name": "nome", // Value of field "nome" equal to .SQL 
        "id": "id", // Value of field "id" equal to .SQL 
        "layout": "layout-id", // Searching in `gestor\db\old\layouts.sql` you find that `id_layouts` has `id` == "layout-id"
        "path": "caminho\/", // Value of field "caminho" equal to .SQL 
        "type": "system", // Value of field "tipo". Here needs to change. Where is "sistema" => "system", where is "pagina" => "page".
        "option": "opcao", // Value of field "opcao" equal to .SQL. OPTIONAL: if not exists, do not create this field.
        "root": true, // Value of field "raiz" where is '1' put here true. OPTIONAL: if not exists, do not create this field.
        "version": "1.0", // Ignore
        "checksum": {
            "html": "", // Ignore
            "css": "", // Ignore
            "combined": "" // Ignore
        }
    },
    ...
]

[
    { // Example of `component` record
        "name": "nome", // Value of field "nome" equal to .SQL 
        "id": "id", // Value of field "id" equal to .SQL 
        "version": "1.0", // Ignore
        "checksum": {
            "html": "", // Ignore
            "css": "", // Ignore
            "combined": "" // Ignore
        }
    },
    ...
]

```

## 📝 Instructions for the Agent
1. You will compare the database data with the current resources with the structuring defined above.
2. The priority target correction at this moment is the `option` field (pages) that was not migrated correctly, but the script must be generic enough to also align (if they diverge) the mapped fields: `name`, `id`, `layout` (via `id_layouts`), `path`, `type`, `option`, `root`.
3. Do not change `version` and `checksum` — responsibilities of the existing resource generation pipeline.
4. Preserve any extra field already existing in the JSON that is not part of the correction scope (do not remove additional data).

## 🧩 Field Mappings
| Original Table | Original Field      | Destination JSON Field | Transformation Observations |
|-----------------|---------------------|--------------------|------------------------------|
| layouts         | nome                | name               | Copy literal               |
| layouts         | id                  | id                 | Equal                        |
| paginas         | nome                | name               | Copy literal               |
| paginas         | id                  | id                 | Equal                        |
| paginas         | id_layouts          | layout             | Map via `layouts.id` corresponding to numeric `id_layouts` |
| paginas         | caminho             | path               | Ensure trailing slash `/`     |
| paginas         | tipo                | type               | "sistema" => "system"; "pagina" => "page"; otherwise keep normalized lowercase value |
| paginas         | opcao               | option             | Only create if not empty        |
| paginas         | raiz (0/1)          | root (bool)        | Only create if == 1 => true     |
| componentes     | nome                | name               | Copy literal               |
| componentes     | id                  | id                 | Equal                        |

## ⚠️ Correction Rules
1. Correspondence is always done by `id` (string) — if `id` is not found in the current JSON, register in report as "missing" (DO NOT create automatically in this script, unless we decide to expand scope).
2. A field is only considered corrupted if:
     - It is absent when it should exist (e.g.: `option` present in database and nonexistent in JSON).
     - It is present but with different value (case-sensitive comparison except for `type` where we will do lower-case normalization before comparing).
3. For `path`: normalize double slash and ensure suffix `/` for comparison purposes.
4. For `layout`: use the numeric `id_layouts` from the database to find the correct layout in the set of original layouts and obtain its textual `id`. If not found, mark inconsistent.
5. For `root`: only create the field if original value == 1. If JSON has `root` but database == 0, remove the field (marking as adjustment) — configurable behavior (flag) to avoid aggressive removal. Default: remove for consistency.
6. For `option`: if empty or NULL in database and exists in JSON, keep (do not delete), just register warning (minimize manual context loss). If value in database exists and JSON different, replace.
7. Keep the order of items in arrays according to Initial Context 10. 

## 🔒 Backup & Security
Before any writing:
1. Create folder `$backupPath` if not exists.
2. Backup target JSON files to be modified with timestamp suffix: `pages.json.YYYYMMDD_HHMMSS.bak` etc. (for globals) and `{module}.json.YYYYMMDD_HHMMSS.bak` for modules.
3. Write changes to temporary file (`.tmp`) and only then replace the original (write-swap) to avoid corruption in case of failure.

## 🧪 Dry-Run Mode
Implement CLI option `--dry-run`:
1. No file is modified.
2. Generates complete report of differences.
3. Exit code: 0 if no differences, 2 if there are differences (facilitates CI).

## 📊 Report
Generate structured report (JSON + human text) containing:
```json
{
    "timestamp": "...",
    "dry_run": true,
    "resumo": {"paginas": {"total": 0, "corrigidos": 0, "pendentes": 0}, ...},
    "alteracoes": [
         {"recurso":"page","id":"dashboard","campo":"option","antes":null,"depois":"listar","escopo":"global|modulo:xyz"}
    ],
    "faltantes": {"pages":["id-x"], "layouts":[], "components":[]},
    "inconsistencias_layout": [{"page_id":"...","id_layouts_num":12,"nao_encontrado":true}]
}
```
Text version (log) summarizes totals and lists first N (configurable) for quick visualization.

## 🧠 Logs & i18n
1. All log messages pass through `_()` with structured keys: e.g.: `arquitetura.corrigir.opcao.atualizada`.
2. Create language files in `$languagesPath/{lang}/corrigir-dados-corrompidos.json` containing key => message map.
3. If key nonexistent, fallback to raw key between brackets and register warning.

## 🧪 Testability
1. Add flag `--limit N` to process only first N records (useful in tests).
2. Possible future extension: `--include pages,layouts` to limit resources.

## 🧱 Edge Cases
1. Page references `id_layouts` that no longer exists — register in `inconsistencias_layout` and do not change current `layout` field if present (avoiding removal of useful reference).
2. Duplication of `id` in JSON (rare) — register critical error and ignore corrections for this `id`.
3. Fields with extra spaces — apply `trim` before comparing.
4. Types outside known set — preserve normalized value (`strtolower`).
5. Invalid JSON file (failed parse) — abort safe execution before any writing.

## ♻️ Performance / Scalability
1. Load all database records into simple arrays (small dataset expected).
2. Index layouts by numeric `id_layouts` and by textual `id` for fast lookup.
3. Index pages/components by `id` for O(1) comparison.

## ✅ Implementation Checklist (Script)
- [x] Argument parsing (`--dry-run`, `--limit`, `--include`, `--report-json=path`).
- [x] Load target `.env` and mount DSN for `conn2flow_old` (reusing credentials and adjusting database).
- [x] Connect with PDO (UTF8, exceptions active).
- [x] SELECT queries only necessary fields.
- [x] Normalize datasets (arrays indexed by `id`).
- [x] Load and validate global and module JSONs.
- [x] Apply diff algorithm (legacy DB vs resources) + (NEW) additional diff against seeds (`*Data.json`).
- [x] Generate report (memory) + save JSON when `--report-json` informed (including legacy and seeds sections).
- [x] Backups + persistence (if not dry-run) with timestamp.
- [x] Atomic update (temporary file + rename).
- [x] Multilingual logs (structure ready / basic keys; completing dictionary is future improvement).
- [x] Proper exit code (0 no differences, 2 with differences in dry-run).
- [ ] (Optional future) Flag `--preserve-extra-option` to control removal/alteration of `option` field when divergent.

Observation: No difference found currently (both diffs return zero changes). 

## 🧪 Difference Pseudo-Code (Refined)
```
diffRecord(oldRow, currentJsonRow):
        changes = []
        map fields (special rule type, option, root, path, layout)
        if field absent and valueOld != empty -> changes[]
        if field present and value different -> changes[]
        return changes
```

## 🔄 New Proposed Functions
Add to specifications:
```
loadEnv($pathEnv): loads environment variables key=value into array
loadDbConfig(): derives manager config + changes database to conn2flow_old
fetchLayoutsOld(): SELECT id_layouts, id, nome FROM layouts
fetchPagesOld(): SELECT id_paginas, id_layouts, id, nome, caminho, tipo, opcao, raiz FROM paginas
fetchComponentsOld(): SELECT id_componentes, id, nome FROM componentes
normalizeOldData(): creates indexes and maps
loadCurrentResources(): loads global + module JSONs
computeDiffs($old,$current): returns difference structure
applyCorrections($diffs,$current): applies changes in memory
backupAndPersist($current): writes backups and persists
generateReport($diffs,$stats,$options): saves report
```

## 🧪 Source Code Structure (Updated)
```
main():
        parseArgs()
        env = loadEnv(.env)
        dbCfg = loadDbConfig(env)
        pdo = connDatabase(dbCfg)
        old = fetchDatabase(pdo)
        currentData = getAtualData()
        diffs = computeDiffs(old, currentData)
        if dryRun -> generateReport(diffs) & exit(code)
        corrected = applyCorrections(diffs, currentData)
        backupAndPersist(corrected)
        generateReport(diffs)
```

## 🤔 Open Doubts
1. Do you want the script to CREATE missing resources (present in old database but absent in JSON) or just report? (currently: just report)
This has already been done, from what I could see the data is being created correctly. The problem is only in some inconsistencies in the data.
2. For empty `option` in database but existing in JSON — keep always or make behavior configurable? (suggestion: flag `--preserve-extra-option`)
Make your suggestion
3. Is there a need to audit plugin modules also in this phase? (not explicit; we can include in `getAtualData()` easily)
Not necessary at this moment.

### Updated Resolutions
1. Automatic creation of missing resources: scope maintained in only reporting (no missing at the moment); creation continues outside this script.
2. Empty `option` field in database but existing in JSON: current decision is to PRESERVE the existing value and just register warning. The flag `--preserve-extra-option` is planned to allow alternative behavior (clean / remove) in future scenario. Not implemented yet (optional item in checklist).
3. Plugin audit: postponed; can be activated by adding `gestor-plugins/*` scan in future extension.

## 📝 Future Suggestions
1. Integrate this script into the seeders generation pipeline to ensure synchronism before each build.
We will modify a seeder creator we currently have. Then we will take care of this.
2. Add `--export-csv` mode for manual audit.
3. Store correction history (append) in `logs/arquitetura/corrigir-dados-corrompidos-YYYYMMDD.log`.
4. Simple Prometheus metric (count of corrected fields) if exposed via endpoint in the future.

## 📌 Implementation Notes
- Use `JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT` in writing.
- Ensure simple locking (create temporary `.lock` file) to avoid concurrent execution.
- Keep complexity low: focus on correctness.

## Source Code Structure:
```
connDatabase():
    > Logic to connect to database `conn2flow_old`

fetchDatabase():
    > Logic to fetch data from tables `paginas`, `layouts`, and `componentes`
    > Gets only necessary fields according to resource formatting in Initial Context. 
    > Returns an array with original data

getAtualData():
    > Logic to fetch current data formatted in resources in .JSON files according to guidance in Initial Context
    > Returns an array with current data

fixCorruptedData($originalData $currentData):
    > Logic to fix corrupted data
    > Compares original data with current data and makes necessary corrections and generate $correctedData
    <$correctedData

updateData($correctedData):
    > Logic to update corrected data in .JSON files of current data.

compareDataForSeeds($currentData, $correctedData):
    > Search in each .JSON and see if there are non-conformities with DB data. DB Data: `gestor\db\data\PaginasData.json`, `gestor\db\data\LayoutsData.json`, `gestor\db\data\ComponentesData.json`

main():
    // Main script logic
    // 1. Connect to database `conn2flow_old`
    connDatabase()
    // 2. Read data from tables `paginas`, `layouts`, and `componentes`
    $originalData = fetchDatabase()
    // 3. Get current data already formatted in resources of each type `paginas`, `layouts`, and `componentes`.
    $currentData = getAtualData()
    // 4. Fix corrupted data
    $correctedData = fixCorruptedData($originalData $currentData)
    // 5. Update $currentData
    updateData($correctedData)
    // 6. Compare with ready DB data to create Seeds to see if there are non-conformities
    $dbData = compareDataForSeeds($currentData, $correctedData)
    // 7. Make a report of changes
    generateReport($originalData, $currentData, $correctedData, $dbData)


main()
```
## 🤔 Doubts and 📝 Suggestions
// (This section was expanded to "Open Doubts" and "Future Suggestions" above.)

# ✅ Implementation Progress
- [x] Specification validated by user
- [x] Implement argument parser
- [x] Implement connection and fetch of old data
- [x] Load current JSONs
- [x] Diff algorithm (legacy) + diff seeds
- [x] Dry-run report
- [x] Apply corrections (no change necessary in current execution; mechanism validated)
- [x] Persist changes with backup (executed in non-dry-run mode during internal validation)
- [x] Initial internationalization pt-br / en (infra ready; pending adding complete translations of all new keys)
- [x] Local tests (dry-run full scope + simulated real execution without diffs)
- [x] Final review (this update)

### Pending / Next Steps (Optional)
- Implement flag `--preserve-extra-option` (if need arises).
- Complete language files with all log/report keys.
- Integrate automatic execution before seeds generation pipeline.

### Current State Summarized
Script synchronized and executed in dry-run for pages, layouts, and components without differences: dataset consistent between legacy database, resources, and seeds.

---
**Date:** 08/12/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.11.0
````