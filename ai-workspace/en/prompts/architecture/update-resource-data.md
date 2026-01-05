````markdown
# Prompt Interactive Programming - Update Resource Data.

- Definitions of the entire programming infrastructure that will be used by AI agents to interact with the user and generate code dynamically are defined below.
- Agents will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part at any time. The user will be attentive and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agents can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by agents and users using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

## 🧪 Testing Environment
- There is a ready and functional testing infrastructure. The environment settings are in the file `docker\dados\docker-compose.yml`
- The testing environment is in the folder `docker\dados\sites\localhost\conn2flow-gestor`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect the repository changes, follow the file for synchronization: `docker\utils\sincroniza-gestor.sh checksum`
- All commands to execute in the testing environment are in the file: `docker\utils\comandos-docker.md`

## 🗃️ GIT Repository
- There is a script made with all the necessary internal operations to manage the repository: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Inside this script, automatic project versioning, commit, and push are done. Therefore, do not perform the commands manually. Only execute the script when changing the repository.

## ⚙️ Implementation Settings
- Base path: $base = `gestor\controladores\agents\arquitetura`.
- Implementation file name: $implementationFileName = $base + `atualizacao-dados-recursos.php`.
- Backup folder path if necessary: $backupPath = `backups\arquitetura`.
- Logs folder path: $logsPath = `gestor\logs\arquitetura`.
- Languages folder path: $languagesPath = `gestor\controladores\agents\arquitetura\lang`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in a .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow the defined design patterns, and be modular.** All guidelines must be included in the code comments.

## 📖 Libraries
- Log generation: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Can change if necessary.
- Lang functions: `gestor\bibliotecas\lang.php`: `_()` > Necessary to define.

## 🎯 Initial Context
1. I created with another agent the following script to update resource data, this being the orchestrator: `gestor\resources\generate.multilingual.seeders.php` and this the data generator itself: `ai-workspace\scripts\arquitetura\generate_seeds_data.php`. Now let's integrate the two scripts into a single script in $implementationFileName .
2. In these 2 scripts, the logic was created to generate resources based on the data source for `pages`, `layouts`, and `components`, and all their peculiarities are fully implemented there. We need to adapt the new source code structure.
3. I created a new resource called `variables` and it is already mapped similarly to the other resources. It will be necessary to integrate this new resource into the data generation logic.

## 📝 Instructions for the Agent
1. The `variables` resource is already mapped and must be integrated into the data generation logic.
2. Read the original scripts and try to use the structure below to adapt the new form.

## 🧭 Source Code Structure
```
loadGlobalMapping():
    > Logic to load main language mapping, data files, etc. Store in variable $globalMappingData
    <$globalMappingData

loadExistingData():
    > Logic to load existing data (to keep IDs stable), store in variable $existingData
    /*
        $existingData = [
            'paginas' => [],
            'layouts' => [],
            'componentes' => [],
            'variaveis' => [],
        ];
    */
    <$existingData

collectResources():
    > Logic to collect resources of each type global, modules, and plugins. Store in variable $resources
    <$resources

updateData($existingData, $resources):
    > Logic to update existing data with the new collected resources

main():
    $globalMappingData = loadGlobalMapping()
    $existingData = loadExistingData()
    $resources = collectResources($existingData $globalMappingData)
    updateData($existingData, $resources)
    finalReport()

main()
```

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Integration of previous scripts into a single file `update-resource-data.php` with variables resource
- [x] Addition of language keys for logs and messages
- [x] Initial execution of the script without fatal errors (warnings eliminated)

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message and execute commit/push in the repository (commit.sh script not existing, used git manually)

## ♻️ Changes and Fixes v1.10.7
1. I saw that you created a new function on line #37 of the script inside it. But, as I told you, you could change the library. Therefore, put the new function in the corresponding library instead of leaving it in the code.
2. I didn't see a routine to compare `id`s to find duplicates. Create a routine for this. If there is any duplication, the system must mark the original resource with an `error` field equal to `true`, and also the `error_msg` field with an appropriate message.
- If there is duplicate `id` in different `languages`, there is no problem. Just ignore in this case.
- For the case of `pages` resource, the same logic must be implemented for the `path` field. That is, there can only be a single path, since the path is literally the page URI. And that's why it has to be unique.
3. For the case of `variables` resource, the `id` has to be unique within the same module. Being able to have equal `id`s in different modules.
4. I saw that you used on line #700 the function `nl2br()` to convert line breaks into <br> tags. But this is not necessary since this script is executed using CMD and is a routine not executed in the browser itself. Look how the terminal looked:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ php gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
Final Resource Update Report<br />
========================================<br />
Layouts: 14<br />
Pages: 188<br />
Components: 84<br />
Variables: 1308<br />
TOTAL: 1594<br />

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```
5. Change the report and include an error message if there is duplicate `id`. As well as which resources were updated referencing the type (global, module, plugin), and the `id` and more relevant information.
6. Change the report and include emojis like ✅, 📝 etc. to improve visualization.

## ✅ Progress of Implementation of Changes and Fixes
- [x] Remove internal log function and migrate to `log.php` library
- [x] Add defaults in `log.php` avoiding warnings
- [x] Implement duplication validation for pages (id, path) and variables (id per module+language)
- [x] Mark duplicate resources with `error` and `error_msg`
- [x] Remove use of `nl2br()` in CLI report
- [x] Improve report with emojis and summarization
- [ ] Adjust duplication messages for full internationalization (future)

## ☑️ Post Changes and Fixes Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message and use commit script (when existing) or temporary manual procedure

## ♻️ Changes and Fixes v1.10.8
INFO: I manually removed the duplicate entries. Only those of variables with `group` defined remained.
```bash
⚠️ Duplication errors:
  - pages (path): modulos/sincronizar-bancos/
```
1. Additional rule for variables implemented: `id` duplications within the same module and language are allowed IF all records have `group` defined and the groups are distinct (>1). Otherwise, mark error.
2. `error` and `error_msg` fields were removed from Data files (`gestor/db/data/*.json`) and are now added exclusively in the source files (globals/modules/plugins) as requested.
3. Duplication marking now does not persist in Data.json avoiding problems in seeders.
4. Origin `modulos/sincronizar-bancos/` remains flagged in `gestor/modulos/modulos/modulos.json`.

## ✅ Progress of Implementation of Changes and Fixes
- [x] Adjust variables rule (allow multiple ids with distinct groups)
- [x] Remove error/error_msg from Data.json
- [x] Add error/error_msg in the correct source files
- [x] Re-execute script and validate report

## ☑️ Post Changes and Fixes Process
- [x] Execute the script again to ensure consistency after any residual adjustment
- [x] Execute automated commit with detailed message

## ♻️ Changes and Fixes v1.10.11
1. I found a duplicate `id_variaveis`=1235 problem in the file: `gestor\db\data\VariaveisData.json`. I believe it is that case of equal `id`s in different `group`s. From what I understood it is being computed as 2 equal resources.
```json
{
        "id_variaveis": "1235",
        "linguagem_codigo": "pt-br",
        "modulo": "_sistema",
        "id": "novo",
        "valor": "<span class=\"ui grey label\">Novo<\/span>",
        "tipo": "string",
        "grupo": "pedidos-status", // Different group
        "descricao": null
    },
    {
        "id_variaveis": "1235",
        "linguagem_codigo": "pt-br",
        "modulo": "_sistema",
        "id": "novo",
        "valor": "<span class=\"ui grey label\">Novo<\/span>",
        "tipo": "string",
        "grupo": "pedidos-voucher-status", // Different group
        "descricao": null
    },
```
2. Fix this problem in the script.
3. Execute the same to see if there are problems.
Execute the script and fix errors: `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`


## ✅ Progress of Implementation of Changes and Fixes
Correction applied: adjustment of `VariaveisData.json` generation to assign new `id_variaveis` when the same (language, module, id) exists with distinct groups, avoiding reuse of the same numeric identifier (case of `id_variaveis=1235`). The second occurrence received a new identifier and the report does not present duplications.

## ☑️ Post Changes and Fixes Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" and execute (when existing) commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"` (script not yet present; use manual flow or create script in the future)

---
**Date:** 08/12/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow Gestor v1.10.11
````