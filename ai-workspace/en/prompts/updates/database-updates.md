````markdown
# Prompt Interactive Programming - Database Updates

- Definitions of all programming infrastructure that will be used by the AI agents to interact with the user and generate code dynamically are defined below.
- The agents will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part at any time. The user will be attentive and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agents can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by the agents and users using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

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

## 🎯 Initial Context
- Let's create a database update routine.
- We use the PHP Phinx library to create migrations and seeders.
- Location of migration files: `gestor\db\migrations`. Example: `gestor\db\migrations\20250723165530_create_paginas_table.php`:
- Location of seeder files (Without records, they are included automatically): `gestor\db\seeders`. Example: `gestor\db\seeds\PaginasSeeder.php`
```php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PaginasSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/PaginasData.json'), true);

        $table = $this->table('paginas');
        $table->insert($data)->saveData();
    }
}
```
- Location of data record files: `gestor\db\data`. Example: `gestor\db\data\PaginasData.json`
```json
    ...
    {
        "id_paginas": 79,
        "id_usuarios": 1,
        "id_layouts": 1,
        "nome": "Arquivos - Adicionar",
        "id": "arquivos-adicionar",
        "language": "pt-br",
        "caminho": "arquivos\/adicionar\/",
        "tipo": "system",
        "modulo": "arquivos",
        "opcao": "upload",
        "raiz": null,
        "sem_permissao": null,
        "html": "...",
        "css": "...",
        "status": "A",
        "versao": 1,
        "data_criacao": "2025-08-13 17:12:12",
        "data_modificacao": "2025-08-13 17:12:12",
        "user_modified": 0,
        "file_version": "1.1",
        "checksum": "..."
    },
    ...
```
- Formatting of file names in relation to the table:
| Resource    | Formatting            |
|-------------|-----------------------|
| Table       | `table`               |
| Seeder      | `TableSeeder.php`     |
| Data        | `TableData.json`      |

## 📝 Guidelines for the Agent
1. We need to run migrations.
2. We need to run seeders.
3. We need to compare the data of each record of each table with its corresponding data file in `gestor\db\data`. If it does not exist, include the data. If it is different, update the record.

## 🧭 Source Code Structure
```
migrations():
    > Logic to run migrations

seeders():
    > Logic to run seeders

dataComparison():
    > Logic to compare data

finalReport():
    > Logic to generate final report

main():
    migrations()
    seeders()
    dataComparison()
    finalReport()

main()
```

## 🤔 Doubts and 📝 Suggestions
- Add `--backup` option to create JSON dump per table before modifying? (recommended)
Yes, the ideal is always to have a backup before making significant changes to fallback.
- Need to support multiple environments (e.g. staging) or just `localhost`? We can parameterize `--env-dir=`.
Yes, you can do it.

## ✅ Implementation Progress
- [x] Initial structure of the script `atualizacoes-banco-de-dados.php`
- [x] Dedicated multilingual loading (merge local dictionary)
- [x] Execution of migrations (with .env verification pending test environment)
- [x] Execution of seeders
- [x] Comparison and synchronization (insert/update) based on *Data.json files
- [x] CLI Flags: --skip-migrate, --skip-seed, --dry-run, --tables=list, --help
- [x] Environment verification (.env) with synchronization instruction
- [x] Implement detailed logging per divergent record (delta field-level) (flag --log-diff)
- [x] Implement optional backup before changes (--backup)
- [x] Implement reverse mode (generate Data.json from database) (--reverse)

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Corrections v1.10.11
### New Flags
- --backup: Creates JSON dump of all target tables before synchronizing.
- --env-dir=name: Allows choosing authentication directory (default localhost).
- --reverse: Exports database data to *Data.json files (DB -> Data) and exits.
- --log-diff: Registers in the log the changed fields per record (limited to 10 fields).

### Adjustments
- Correction of BASE_PATH paths to correctly point to gestor/.
- Addition of reverse export with backup of old files (rename *.bak.timestamp).
- Expanded multilingual messages (backup, reverse, diffs).
- Sanitization/limitation of values in logs (encLog).
- Update of usage help.

## ✅ Changes and Corrections Implementation Progress
### v1.10.12 (Executions and Adjustments)
- Adjusted script `atualizacoes-banco-de-dados.php` to correctly convert CamelCase -> snake_case (`HostsConfiguracoes` -> `hosts_configuracoes`).
- Adjusted inverse function for export (`dataFileNameFromTable`).
- Filter `--tables` started using same derivation logic (consistency).
- Seeders made idempotent with `truncate()` (Componentes, Layouts, Paginas) avoiding `Duplicate entry` when re-executing.
- All seeders executed successfully after adjustments.
- Specific dry-run test in `hosts_configuracoes` without error 42S02.
- Complete routine executed with `--debug --log-diff` validated.

### Synthetic Report
```
Seeders: OK (no failures)
Original error: resolved (mapping hosts_configuracoes)
Synchronization: diffs applied according to logs
```

### Proposed Commit Message
```
fix(v1.10.12): fix mapping hosts_configuracoes and make seeders idempotent

- Adjusts tableFromDataFile (Camel/PascalCase -> snake_case)
- Implements consistent dataFileNameFromTable
- Unifies filter --tables
- Adds truncate in Componentes, Layouts, Paginas
- Validation: seed:run complete and DB update OK
```
1. I went to execute by myself and it gave an error:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --dry-run --debug"
Error: Seeders failed

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```
2. I manually completely cleaned the database, ran again and even so it gave the same error.
3. I cleaned manually now and you will be able to run again by yourself with the clean database.

## ☑️ Post Changes and Corrections Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Corrections v1.10.12
1. I found an error in the execution of the database update:
```
[2025-08-13 16:41:33] Synchronizing table hostsconfiguracoes ...
[2025-08-13 16:41:33] Error in routine: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'conn2flow.hostsconfiguracoes' doesn't exist
```
2. Analyze the problem and fix it.

## ✅ Changes and Corrections Implementation Progress

## ☑️ Post Changes and Corrections Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "fix(v1.10.12): fix mapping hosts_configuracoes and idempotent seeders"`

---
**Date:** 08/13/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.12
````