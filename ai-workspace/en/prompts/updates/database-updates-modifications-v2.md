````markdown
# Prompt Interactive Programming - Database Updates - Modifications V 2.0

## 🎯 Initial Context
- FUNDAMENTAL: Analyze the previous context before proceeding with the guidelines below which was registered in the file: `ai-workspace\prompts\updates\database-updates-modifications.md`.

## 📝 Guidelines for the Agent

### Update only updated data source
1. I created a `manager_updates` table to record metadata of manager update executions. The reason is that the database data update is taking too long, about 14 seconds. This table will serve to keep history so we can see what really needs to update and which tables actually need to be updated. And not update all tables as is currently being done unnecessarily.
2. Inside this table there are the following fields: `db_checksum`, `backup_path`, `version`, `date`.
3. The `db_checksum` field will store the set of all checksums of the data files of each table, which are stored in the following folder: `gestor\db\data`.
4. Each data set inside this folder with the name `TableData.json` will have its checksum calculation individually. The set of all checksums will be stored as a single JSON value. Following the following format:
```json
{
    "TableData.json": "checksum1",
    "TableData2.json": "checksum2",
    ...
}
```
For example, for the two data types: `PaginasData.json` and `LayoutsData.json`
```json
{
    ...
    "PaginasData.json": "c4ca4238a0b923820dcc509a6f75849b",
    "LayoutsData.json": "098f6bcd4621d373cade4e832627b4f6"
    ...
}
```
This JSON will be stored in the `manager_updates` table in the `db_checksum` field.
5. The algorithm needs to analyze this field before starting any update. If there is no previous update, it must start a new complete update with all data. If there is a record of a previous update, it must compare the checksums and determine which tables need to be updated.
6. The `backup_path` field will be used to store the backup directory path associated with the current update.
7. The `version` field will store the manager version at the time of the update.
8. Every update must be registered in the `manager_updates` table with the appropriate metadata.
9. The `date` field will store the date and time of the update execution.

### Update only modified records.
1. Analyzing the final report log generated after executing the update, I could see that even if a record has no modified value, it is being updated anyway. That is, I didn't change anything in the `TableData.json` files, but all their records are being updated. We need to change the logic that checks if there was really a change in the data before performing the update in the database. As you can see from the generated report. + is record inclusion and ~ is update. So, it is clear that it is updating everything unnecessarily:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"
📝 Final DB Update Report
══════════════════════════════════════════════════
📦 categorias => +0 ~41 =0
📦 componentes => +0 ~0 =84
📦 hosts_configuracoes => +0 ~2 =0
📦 layouts => +0 ~0 =14
📦 modulos => +0 ~74 =0
📦 modulos_grupos => +0 ~12 =0
📦 modulos_operacoes => +0 ~1 =0
📦 paginas => +0 ~5 =179
📦 plugins => +0 ~3 =0
📦 templates => +0 ~42 =0
📦 usuarios => +0 ~1 =0
📦 usuarios_perfis => +0 ~1 =0
📦 usuarios_perfis_modulos => +0 ~0 =18
📦 variaveis => +0 ~718 =590
Σ TOTAL => +0 ~900 =885

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```

### Execute the script for necessary operations tests:
1. Synchronize data in the test environment: `bash docker/utils/sincroniza-gestor.sh checksum`
2. Execute the script in the test environment: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"`

### Update the repository
1. If everything is resolved, let's generate the version and GIT operations by executing the commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`. Remembering that you need to execute this and at the same time create the detailed message.

## 🧭 Source Code Structure
```
...Other Functions...

main():
    ... Other Logics ...
    
    ... Other Logics ...

main()
```

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Analyze logs and identify main problem
- [x] Fix comparison logic to detect real changes
- [x] Implement stricter verification of modified records
- [x] Implement automatic space cleaning in JSON data
- [ ] Optimize update performance by checksum
- [ ] Test script with --dry-run to verify improvements in all tables
- [ ] Execute final tests without --dry-run
- [ ] Commit changes

## ☑️ Post-Implementation Process
- [] Execute the generated script to see if it works correctly.
- [] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Corrections 1.0

## ✅ Changes and Corrections Implementation Progress

## ☑️ Post Changes and Corrections Process
- [] Execute the generated script to see if it works correctly.
- [] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
**Date:** currentDate()
**Developer:** Otavio Serra
**Project:** Conn2Flow version()
````