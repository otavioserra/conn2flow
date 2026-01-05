````markdown
# Prompt Interactive Programming - NAME

## 🎯 Initial Context
- FUNDAMENTAL: Analyze the previous context before following the instructions below, which was recorded in the file: `ai-workspace\prompts\architecture\update-resource-data-v2.md`.

## 📖 Libraries

## 📝 Instructions for the Agent
1. I found a problem in generating versions and checksums for resources: `pages`, `layouts`, and `components`.
2. I verified that when I change the HTML value of a resource (probably the problem also exists in CSS), then execute the script `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`, the checksum in the `TabelaData.json` file changes correctly, but the `file_version` field is not being incremented in this file. The `checksum` values are changing correctly. On the other hand, the `file_version` field is not being updated in the resource source file, the same occurs with the `checksum`. Not keeping the history as expected. Example:
- I changed the file `gestor\resources\pt-br\components\botao-superior-interface\botao-superior-interface.html`, and included this in it (but any change causes the problem):
```html
<p class="ui #cor# text">
    #texto# as
</p>
```
- I executed the script `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`.
- The record in the source file `gestor\resources\pt-br\components.json` was not modified:
```json
    {
        "name": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "version": "1.2",
        "checksum": {
            "html": "17898f8cf079921914072cfa54971eb7",
            "css": "",
            "combined": "17898f8cf079921914072cfa54971eb7"
        }
    },
```
- The record in the destination file `gestor\db\data\ComponentesData.json` modified only the `checksum` (the other fields apparently are changing correctly):
```json
    {
        "nome": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "language": "pt-br",
        "modulo": null,
        "html": "<a class=\"ui button #cor#\" href=\"#url#\" data-content=\"#tooltip#\" data-id=\"adicionar\">\n    <i class=\"#icon# icon\"><\/i>\n    #label#\n<\/a>\n<p class=\"ui #cor# text\">\n    #texto# as\n<\/p>",
        "css": null,
        "status": "A",
        "versao": 6,
        "file_version": "1.2",
        "checksum": "{\"html\":\"f4aa7f9cb54d699431bf771a5f12f442\",\"css\":\"\",\"combined\":\"f4aa7f9cb54d699431bf771a5f12f442\"}"
    },
```
- What was expected in the record in the source file `gestor\resources\pt-br\components.json`:
```json
    {
        "name": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "version": "1.3",
        "checksum": {
            "html": "f4aa7f9cb54d699431bf771a5f12f442",
            "css": "",
            "combined": "f4aa7f9cb54d699431bf771a5f12f442"
        }
    },
```
- What was expected in the record in the destination file `gestor\db\data\ComponentesData.json`:
```json
    {
        "nome": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "language": "pt-br",
        "modulo": null,
        "html": "<a class=\"ui button #cor#\" href=\"#url#\" data-content=\"#tooltip#\" data-id=\"adicionar\">\n    <i class=\"#icon# icon\"><\/i>\n    #label#\n<\/a>\n<p class=\"ui #cor# text\">\n    #texto# as\n<\/p>",
        "css": null,
        "status": "A",
        "versao": 6,
        "file_version": "1.3",
        "checksum": "{\"html\":\"f4aa7f9cb54d699431bf771a5f12f442\",\"css\":\"\",\"combined\":\"f4aa7f9cb54d699431bf771a5f12f442\"}"
    },
```


## 🧭 Source Code Structure
```
main():
    // Main script logic
    

main()
```

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [] progress item

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly. (Executed on 2025-08-18: versions and checksums updated and idempotency validated.)
- [x] Generate detailed message and execute commit:

```bash
./ai-workspace/git/scripts/commit.sh "Resources: automatic update of version/checksum in source (layouts, pages, components)

- Adds 'updateSourceFiles' phase to the update-resource-data script.
- Recalculates checksums (html, css, combined) by reading physical files before collection.
- Increments 'version' in source only when checksum changes (keeping history coherent).
- Synchronizes 'file_version' in *Data.json with the updated source version.
- Maintains internal 'versao' increment (content change counter) without altering previous logic.
- Adds --no-origin-update flag to skip source update when necessary.
- Ensures idempotency: second execution without modifications does not alter versions.
- Manual validation performed for layouts, pages, and components (example: botao-superior-interface 1.2 -> 1.3).
"
```

## ♻️ Changes and Fixes 1.0

## ✅ Progress of Implementation of Changes and Fixes

## ☑️ Post Changes and Fixes Process
- [] Execute the generated script to see if it works correctly.
- [] Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
**Date:** currentDate()
**Developer:** Otavio Serra
**Project:** Conn2Flow version()
````