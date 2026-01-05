````markdown
# Prompt Interactive Programming - Update Data Structure

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

## 🎯 Initial Context
- Definitions of the entire programming infrastructure that will be used by the AI agent to interact with the user and generate code dynamically are defined below.
- The agent will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part of this file at any time. The user will be attentive to this file and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agent can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by the agent and/or user using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🧪 Testing Environment
- There is a ready and functional testing infrastructure. The environment settings are in the file `docker\dados\docker-compose.yml`
- The testing environment is in the local folder `docker\dados\sites\localhost\conn2flow-gestor`, it is in the testing environment folder: `/var/www/sites/localhost/conn2flow-gestor/`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect the repository changes, follow the file for synchronization: `docker\utils\sincroniza-gestor.sh checksum`
- All commands to execute in the testing environment are in the file: `docker\utils\comandos-docker.md`
- If you need to execute PHP there, example: `docker exec conn2flow-app bash -c "php -v"`

## 🗃️ GIT Repository
- There is a script made with all the necessary internal operations to manage the repository: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Inside this script, automatic project versioning, commit, and push are done. Therefore, do not perform the commands manually. Only execute the script when changing the repository.

## ⚙️ Implementation Settings
- Base path: $base = `gestor/controladores/agents/arquitetura`.
- Implementation file name: $implementationFileName = $base + `atualização-estrutura-dados.php`.
- Backup folder path if necessary: $backupPath = `backups\arquitetura`.
- Logs folder path: $logsPath = `gestor\logs\arquitetura`.
- Languages folder path: $languagesPath = `gestor\controladores\agents\arquitetura\lang`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in a .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow the defined design patterns, and be modular.** All guidelines must be included in the code comments.

## 📖 Libraries

## 📝 Instructions for the Agent

## 🧭 Source Code Structure
```
main():
    // Main script logic
    

main()
```

## 🤔 Doubts and 📝 Suggestions

---
## 🚀 PLANNING AND WORK RULES

### How the collaborative flow will be:
- All ideas, decisions, requirements, and changes will be recorded in this file before any implementation.
- Each task will be documented with: objective, context, requirements, action plan, and checklist.
- The AI agent will only make changes to the code after the plan is approved/documented here.
- All history of decisions and changes will be recorded for traceability.

### Structure of each task:
1. **Context**: Description of the problem or objective.
2. **Requirements**: Points that must be met.
3. **Action Plan**: Steps to resolve.
4. **Checklist**: Items to mark progress.
5. **Decisions**: Justifications and alternatives considered.

---
## 📝 Task 1: Initialization of the Collaborative Process

### Context
Start recording all decisions and changes of the data structure update project, centralizing the flow in this markdown file.

### Requirements
- Define collaborative work rules.
- Create task template for future interactions.
- Ensure that every new plan/change is documented before being implemented.

### Action Plan
1. Add planning and rules section to the file.
2. Create task template for future use.
3. Register this first task as an example.

### Checklist
- [x] Planning section created
- [x] Collaborative work rules defined
- [x] Task template added
- [x] Task 1 registered

---

## ✅ Implementation Progress
- [] progress item

## ☑️ Post-Implementation Process
- [] Execute the generated script to see if it works correctly.
- [] Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Fixes 1.0

## ✅ Progress of Implementation of Changes and Fixes

## ☑️ Post Changes and Fixes Process
- [] Execute the generated script to see if it works correctly.
- [] Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
## 📝 Task 2: Standardization of Identifiers in Migrations and Pages Table

### Context
Necessary to standardize the user identifier in migrations to default value equal to 1. In the `pages` table, remove the `id_layouts` (integer) field and add the `layout_id` (string) field, referencing the `id` field of the `layouts` table.

### Requirements
- All migrations must define `id_usuarios` with default value 1.
- In the `pages` table, remove `id_layouts` (integer).
- Add `layout_id` (string, limit 255, not null) in the `pages` table, referencing the `id` field of the `layouts` table.
- Ensure that the relationship between pages and layouts is done by the alphanumeric identifier.

### Action Plan
1. Review all migrations and adjust the `id_usuarios` field to default 1.
2. Edit the `pages` table migration:
    - Remove the `id_layouts` (integer) field.
    - Add the `layout_id` (string, limit 255, not null) field, referencing the `id` field of the `layouts` table.
3. Validate if the relationship is correct and compatible with the new standard.
4. Test the migrations in development environment.
5. Document decisions and any problems found.

### Checklist
- [x] `pages` migration adjusted (`id_usuarios` default 1, replacement of `id_layouts` by `layout_id` string)
- [x] `layouts` migration adjusted (`id_usuarios` default 1)
- [x] `components` migration adjusted (`id_usuarios` default 1)
- [x] `files` migration adjusted (`id_usuarios` default 1)
- [x] `hosts_variables` migration adjusted (`id_usuarios` default 1)
- [x] Other migrations with `id_usuarios` reviewed and adjusted


### Decisions
- It was decided to use alphanumeric identifier for layouts, aiming for greater flexibility and future compatibility.
- The `id_usuarios` default 1 field facilitates the control of global records and avoids ownership inconsistencies.

---
**Date:** 08/15/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.16
````