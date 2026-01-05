````markdown
# Prompt Interactive Programming - Resource Variables Create Origin

- Definitions of all programming infrastructure that will be used by AI agents to interact with the user and generate code dynamically are defined below.
- Agents will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part at any time. The user will be attentive and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and AI agents can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by agents and users using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 AI Agent
- **Development Agent**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT Agent**: Responsible for managing the source code repository and project versions. To do this, create/modify the file inside the folder with all modifications for message creation: `ai-workspace\git\arquitetura\`. When you need to generate the commit, use this script: `ai-workspace\git\scripts\commit.sh`
- **Docker Agent**: Responsible for managing Docker containers and related infrastructure.

## ⚙️ Implementation Settings
- Name of this implementation: $implementationName = `Resource Variables Create Origin`
- Base path: $base = `gestor\controladores\agents\arquitetura`.
- Implementation file name: $implementationFileName = $base + `recurso-variaveis-criar-origem.php`.
- Backup folder path if necessary: $backupPath = `backups\arquitetura`.
- Logs folder path: $logsPath = `gestor\logs\arquitetura`.
- Languages folder path: $languagesPath = `gestor\controladores\agents\arquitetura\lang`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow defined design patterns and be modular.** All guidelines must be in the code comments.

## 🧪 Testing Environment
- There is a ready and functional testing infrastructure. The environment settings are in the file `docker\dados\docker-compose.yml`
- The testing environment is in the folder `docker\dados\sites\localhost\conn2flow-gestor`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect repository changes, follow the synchronization file: `docker\utils\sincroniza-gestor.sh checksum`

## 📖 Libraries
- Log generation: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Can change if necessary.
- Lang functions: `gestor\bibliotecas\lang.php`: `_()` > Necessary to define.

## 🎯 Initial Context
1. We will create a new variable resource for the data origin. Currently, the data origin is in the `variaveis` table seed: `gestor\db\data\VariaveisData.json`.
2. The destination of each variable will be the respective resource using the following locations:
- Globals: gestor/resources/{lang}/variables.json
- Modules: gestor/modulos/{module}/{module}.json -> resources.{lang}.{variables}
- Plugins: gestor-plugins/{plugin}/local/modulos/{module}/{module}.json -> resources.{lang}.{variables}
3. The reverse process has already been done for `pages`, `layouts` and `components` in another routine. And to create the seeds use this script `ai-workspace\scripts\arquitetura\generate_seeds_data.php`. In the future we will include `variables` in this process. So use this script as a reference for the reverse process.

## 📝 Guidelines for the Agent
1.

## 🧭 Source Code Structure
```
readVariables():
    > Make logic to read all records from `gestor\db\data\VariaveisData.json` and put in variable $records passing to array format.
    <$records

modulesIDs():
    > Make logic to read all records from `gestor\modulos` and put in variable $modulesIDs passing to array format.
    <$modulesIDs

pluginsModulesIDs():
    > Make logic to read all records from `gestor-plugins` and put in variable $pluginsModulesIDs passing to array format.
    <$pluginsModulesIDs

formatVars($variables, $modulesIDs, $pluginsModulesIDs):
    > Format variables to the format:
    /*
        Origin Example: 

        {
            "id_variaveis": "1647", // Ignore, as it will be controlled by the reverse process of creating Seeds.
            "linguagem_codigo": "pt-br",
            "modulo": "host-configuracao", // This value will define the module and needs to search to place in one of the 3 types. If no value, it is global. Otherwise need to search in modules and plugins.
            "id": "recaptcha-v3-tooltip",
            "valor": "Click to switch to Google reCAPTCHA v3",
            "tipo": "string",
            "grupo": null, // If null ignore
            "descricao": null // If null ignore
        }

        New Format:

        {
            "id": "recaptcha-v3-tooltip",
            "value": "Click to switch to Google reCAPTCHA v3",
            "type": "string"
        }
    */
    > Link the 3 types to return variable $varsFormattedByType based on module and plugin IDs as follows:
    /*
        $varsFormattedByType = [
            'modulos' => [],
            'plugins' => [],
            'globais' => []
        ];

        foreach ($variables as $var) {
            if (in_array($var['modulo'], $modulesIDs)) {
                $varsFormattedByType['modulos'][] = $var;
            } elseif (in_array($var['modulo'], $pluginsModulesIDs)) {
                $varsFormattedByType['plugins'][] = $var;
            } else {
                $varsFormattedByType['globais'][] = $var;
            }
        }
    */
    <$varsFormattedByType

saveVarsInResources($varsFormattedByType):
    > Make logic to save formatted data in file `gestor/resources/{lang}/variables.json` and in files of each module `gestor/modulos/{module}/{module}.json -> resources.{lang}.{variables}` and plugin module `gestor-plugins/{plugin}/local/modulos/{module}/{module}.json -> resources.{lang}.{variables}`.

main():
    $variables = readVariables()
    $modulesIDs = modulesIDs()
    $pluginsModulesIDs = pluginsModulesIDs()
    $varsFormattedByType = formatVars($variables, $modulesIDs, $pluginsModulesIDs)
    saveVarsInResources($varsFormattedByType)
    reportChanges($varsFormattedByType)

main()
```

## 🤔 Doubts and 📝 Suggestions

# ✅ Implementation Progress
- [x] Create the main implementation file in `gestor\controladores\agents\arquitetura\recurso-variaveis-criar-origem.php`.
- [x] Implement function `readVariables()` to read data from `gestor\db\data\VariaveisData.json`.
- [x] Implement function `modulesIDs()` to list modules from `gestor\modulos`.
- [x] Implement function `pluginsModulesIDs()` to list modules from `gestor-plugins`.
- [x] Implement function `formatVars()` to transform and categorize variables.
- [x] Implement function `saveVarsInResources()` to save variables in correct resource files.
- [x] Implement function `reportChanges()` to log changes.
- [x] Implement function `main()` to orchestrate the whole process.
- [x] Add internationalization logic for log/report messages.
- [x] Review and refactor code to ensure quality, comments and adherence to standards.

## 🐛 Identified Problems
1. A file was created outside the correct folders: `gestor\resources\variables.json`
2. Non-existent modules inside folder `gestor\modulos` were not treated, for example `"modulo": "_sistema",`. When this occurs place the variable as global, but put the module in the variable definition:
/* Example:
    {
        "id": "ID",
        "value": "VALUE",
        "type": "TYPE",
        "modulo": "_sistema" // Example "_sistema", but there are several other cases as I could see.
    }
*/

---
**Date:** 08/12/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow Gestor v1.10.5
````