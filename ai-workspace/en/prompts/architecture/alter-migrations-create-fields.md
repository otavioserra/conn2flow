````markdown
# Prompt Interactive Programming - Alter Migrations Create Fields

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application's source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

## 🎯 Initial Context
- Definitions of the entire programming infrastructure that will be used by the AI agent to interact with the user and dynamically generate code are defined below.
- The agent will use this file to be able to create and change guidelines dynamically, based on interactions with the user. It can change any part of this file at any time. The user will pay attention to this file and modify it to ensure that the changes are understood and implemented correctly.
- Both the user and the AI agent can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be aware of the changes and adapt its behavior as necessary.
- Below, commands will be defined by the agent and/or user using pseudo-code where the syntax definition is in the following file: `ai-workspace\templates\pseudo-language-programming.md`.

## 🧪 Test Environment
- Tests will be done locally in the development environment.

## 🗃️ GIT Repository
- There is a script made with all the necessary internal operations to manage the repository: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Within this script, automatic project versioning, commit, and push are performed. Therefore, do not perform the commands manually. Only run the script when you are going to change the repository.

## ⚙️ Implementation Settings
- Base path: $base = `ai-workspace\scripts\architecture`.
- Implementation file name: $implementationFileName = $base + `alter-migrations-create-fields.php`.
- Backup folder path if necessary: $backupPath = `backups\architecture`.
- Log folder path: $logsPath = `logs\architecture`.
- The source code must **be well commented (DocBlock standard), follow the defined design patterns, and be modular.** All guidelines must be included in the code comments.

## 📖 Libraries
- Log generation: `gestor\libraries\log.php`: `log_disk($msg, $logFilename = "manager")` > Can be changed if necessary.
- Migration generation: Phinx. Already fully configured in the repository: `gestor\phinx.php`

## 📝 Instructions for the Agent
1. We will create new migrations to alter database fields. The folder with all migrations is: `gestor\db\migrations`.
2. We will alter the fields of the tables: `pages`, `layouts`, and `components`. We will include the following new fields after the `user_modified` field: `system_updated`, `html_updated`, and `css_updated`. Use the following data type:
```php
    ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('html_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
    ->addColumn('css_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
```
3. We will alter the fields of the `variables` table. We will include the following new fields after the `description` field: `user_modified`, `system_updated`, and `value_updated`. Use the following data type:

```php
    ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('value_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
```
4. Execute the generated script and check for errors in the migration files. IMPORTANT: Do not run the migrations, I will do that in another context.
5. If everything is resolved, we will generate the version and GIT operations by running the commit script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## 🧭 Source Code Structure
```
generate_migrations():
    > Logic to generate migrations

generate_report():
    > Logic to generate the report

main():
    generate_migrations()
    generate_report()

main()
```

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Create migration to add fields to pages
- [x] Create migration to add fields to layouts
- [x] Create migration to add fields to components
- [x] Create migration to add fields to variables
- [x] Validate syntax/phinx (do not run up) 
- [x] Generate commit and version

## ☑️ Post-Implementation Process
- [x] Execute the generated script to see if it works correctly.
- [x] Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Fixes 1.0

## ✅ Progress of Implementation of Changes and Fixes

## ☑️ Post Changes and Fixes Process
- [] Execute the generated script to see if it works correctly.
- [] Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
**Date:** 08/14/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.14
````