````markdown
# Prompt Interactive Programming - Database Updates Modifications

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

## 🎯 Initial Context
- Definitions of all programming infrastructure that will be used by the AI agent to interact with the user and generate code dynamically are defined below.
- The agent will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part of this file at any time. The user will be attentive to this file and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agent can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by the agent and/or user using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🧪 Testing Environment
- There is a ready and functional testing infrastructure. The environment settings are in the file `docker\dados\docker-compose.yml`
- The testing environment is in the folder `docker\dados\sites\localhost\conn2flow-gestor`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect repository changes, follow the synchronization file: `docker\utils\sincroniza-gestor.sh checksum`
- All commands to execute in the testing environment are in the file: `docker\utils\comandos-docker.md`
- If you need to execute PHP there, example: `docker exec conn2flow-app bash -c "php -v"`

## 🗃️ GIT Repository
- There is a script made with all necessary internal operations to manage the repository: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Inside this script, automatic project versioning, commit and push are done. Therefore, do not do the commands manually. Only execute the script when changing the repository.

## ⚙️ Implementation Settings
- Base path: $base = `gestor\controladores\atualizacoes\`.
- Implementation file name: $implementationFileName = $base + `atualizacoes-banco-de-dados.php`.
- Backup folder path if necessary: $backupPath = `backups\atualizacoes`.
- Logs folder path: $logsPath = `gestor\logs\atualizacoes`.
- Languages folder path: $languagesPath = `gestor\controladores\atualizacoes\lang\`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow defined design patterns and be modular.** All guidelines must be in the code comments.

## 📖 Libraries
- Log generation: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Can change if necessary.
- Lang functions: `gestor\bibliotecas\lang.php`: `_()` > Can change if necessary.

## 📝 Guidelines for the Agent
1. We will change `gestor\controladores\atualizacoes\atualizacoes-banco-de-dados.php` to remove the `seeders()` function. Since seeders will no longer be executed in updates, they will only be executed in an installation, which is done in another context.
2. After changing everything, let's do the tests in the testing environment. For this first synchronize the data. `docker\utils\sincroniza-gestor.sh checksum`.
3. Then, execute the tests in the testing environment to ensure everything is working correctly. Example: `docker exec conn2flow-app bash -c "php -v"`
4. If everything is resolved, let's generate the version and GIT operations by executing the commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## 🧭 Source Code Structure
```
migrations():
    > Logic to run migrations

seeders(): // Remove
    > Logic to run seeders

dataComparison():
    > Logic to compare data

finalReport():
    > Logic to generate final report

main():
    migrations()
    seeders() // Remove
    dataComparison()
    finalReport()

main()
```

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Remove seeders() function and call in main of file `gestor\\controladores\\atualizacoes\\atualizacoes-banco-de-dados.php`
- [x] Test synchronization and execution in testing environment (dry-run executed without seeders)
- [x] Generate commit and version

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Corrections v1.10.15
1. There was a problem in the `paginas` table in the `tipo` field. The original resources have the .JSON file field called `type`. This field either has the value `page` or `system`. The problem is that the manager uses the Portuguese value `tipo` with Portuguese values: `pagina` and `sistema`. That's why we need to update the script to be able to do this conversion correctly.
2. The following tables have the `user_modified` field: `paginas`, `layouts`, `componentes` and `variaveis`. When this value is defined in the record inside the database, i.e. `1`, we cannot update the `html` and `css` fields of the tables: `paginas`, `layouts` and `componentes`. And we cannot update the `valor` field of the `variaveis` table. Remembering that these values come from the resources of the `TableData.json` files in the folder: `gestor/db/data`. Example: `gestor\db\data\PaginasData.json`
3. When this occurs, i.e., when we run the script to update the data in the database, we will keep the data of the `html` and `css` columns of the `paginas`, `layouts` and `componentes` tables, and the `valor` field of the `variaveis` table. But, in return we will mark each record that this occurs the `system_updated` field as `1`. And include the value of `html` and `css` of the updated resources in the `html_updated` and `css_updated` fields of the tables: `paginas`, `layouts` and `componentes`. And the value of `valor` of the `variaveis` table in the `value_updated` field.
4. Then, execute the tests in the testing environment to ensure everything is working correctly. Example: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
5. If everything is resolved, let's generate the version and GIT operations by executing the commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## 🧭 Source Code Structure
```
migrations():
    > Logic to run migrations

dataComparison():
    > Logic to compare data
    > Fix comparison between `tipo` and `type` fields.
    > Keep data of `html` and `css` columns of `paginas`, `layouts` and `componentes` tables, and `valor` field of `variaveis` table.
    > Mark `system_updated` field as `1` when data is kept.
    > Include updated values in `html_updated`, `css_updated` and `value_updated` fields.

finalReport():
    > Logic to generate final report

main():
    migrations()
    dataComparison()
    finalReport()

main()
```

## ✅ Changes and Corrections Implementation Progress
- [x] Implement type->tipo conversion (page/system => pagina/sistema)
- [x] Implement html/css/value preservation when user_modified=1
- [x] Fill *_updated and system_updated according to rules
- [x] Test script (dry-run) in docker environment
- [x] Commit version v1.10.15

## ☑️ Post Changes and Corrections Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Corrections v1.10.16
1. I made the following change in the `paginas` table by myself using PHPMyAdmin. I accessed the record of `id`=`teste-variavel-global`, modified the `html` field to a new value. And changed the `user_modified` field to `1`. Record SQL for checking:
```sql
(3, 1, 2, 'Teste Variável Global 2', 'teste-variavel-global', 'pt-br', 'teste-variavel-global/', 'page', NULL, NULL, NULL, NULL, '<p>Teste novo porra @[[variavel-global]]@ que deve ser @[[variavel-novo]]@ como deve ser.</p>\n<p>Mas será que dá certo @[[variavel-nova]]@ , sei lá</p>\n<p>Dinovo!!!</p>\nTeste', 'p{\n    width:@[[variavel-nova]]@;\n}', 'A', 1, '2025-08-13 19:30:53', '2025-08-14 13:34:04', 1, 0, NULL, NULL, '1.2', '{\"html\":\"541933857364212ec6a4925c86d75feb\",\"css\":\"635794792273cbaa101a544f44d917e0\",\"combined\":\"79695fd8d837dda4d0306ecc03953f05\"}');

```
2. I ran by myself in `$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --dry-run 2>&1 | tail -n 60"`
3. I went to look at the `paginas` table and confirmed that the changes were NOT applied correctly. Besides not modifying the `system_updated` field. It also did not change the `html_updated` and `css_updated` fields as expected.
4. I could also see that in all records of the `paginas` table the `tipo` fields continue with their English values `system` and `page`.
5. Then, execute the tests in the testing environment to ensure everything is working correctly. Example: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
6. If everything is resolved, let's generate the version and GIT operations by executing the commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ✅ Changes and Corrections Implementation Progress

## ☑️ Post Changes and Corrections Process
- [] Execute the generated script to see if it works correctly.
- [] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
**Date:** 08/14/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.16
````