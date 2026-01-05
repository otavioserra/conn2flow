````markdown
# Prompt Interactive Programming - NAME

## 🤖 AI Agent - Responsibilities
- **Development**: Responsible for creating and modifying these guidelines and the application source code.
- **GIT**: Responsible for managing the source code repository and project versions.
- **Docker**: Responsible for managing Docker containers and related infrastructure.

## 🎯 Initial Context
- Definitions of the entire programming infrastructure that will be used by the AI agent to interact with the user and generate code dynamically are defined below.
- The agent will use this file to be able to create and change guidelines dynamically, based on interactions with the user. Being able to change any part of this file at any time. The user will pay attention to this file and modify this file to ensure that changes are understood and implemented correctly.
- Both the user and the AI agent can modify the guidelines and programming elements defined in this file at any time. Therefore, the agent must always be attentive to changes and adapt its behavior as necessary.
- Below, commands will be defined by the agent and/or user using pseudo-code where the syntax definition is in the following file: `ai-workspace\en\templates\pseudo-language-programming.md`.

## 🧪 Test Environment
- There is a ready and functional test infrastructure. The environment configurations are in the file `docker\dados\docker-compose.yml`
- The test environment is in the local folder `docker\dados\sites\localhost\conn2flow-gestor`, which is in the test environment folder: `/var/www/sites/localhost/conn2flow-gestor/`. Which is executed by the manager via browser like this: `http://localhost/instalador/` . It is in the folder: `docker\dados\sites\localhost\public_html\instalador`
- To update the environment and reflect the repository changes, follow the synchronization file: `docker\utils\sincroniza-gestor.sh checksum`
- All commands to execute in the test environment are in the file: `docker\utils\comandos-docker.md`
- If you need to execute PHP there, example: `docker exec conn2flow-app bash -c "php -v"`

## 🗃️ GIT Repository
- There is a script made with all necessary internal operations to manage the repository: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`
- Inside this script, automatic project versioning, commit, and push are done. Therefore, do not do the commands manually. Only execute the script when changing the repository.

## ⚙️ Implementation Settings
- Base path: $base = `PATH`.
- Implementation file name: $implementationFileName = $base + `FILE_NAME`.
- Backup folder path if necessary: $backupPath = `PATH_BACKUP`.
- Logs folder path: $logsPath = `PATH_LOGS`.
- Languages folder path: $languagesPath = `PATH_LANGUAGES`.
- Supported languages: $supportedLanguages = [`pt-br`, `en`].
- Dictionary languages will be stored in a .JSON file.
- All information/log texts must be multilingual. Escaped using helper function `_()`;
- The source code must **be well commented (DocBlock standard), follow the defined design patterns and be modular.** All guidelines must be included in the code comments.

## 📖 Libraries

## 📝 Guidelines for the Agent

## 🧭 Source Code Structure
```
main():
    // Main logic of the script
    

main()
```

## 🤔 Questions and 📝 Suggestions

## ✅ Implementation Progress
- [] progress item

## ☑️ Post-Implementation Process
- [] Execute the generated script to see if it works correctly.
- [] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## ♻️ Changes and Fixes 1.0

## ✅ Changes and Fixes Implementation Progress

## ☑️ Post Changes and Fixes Process
- [] Execute the generated script to see if it works correctly.
- [] Generate detailed message, replace "DetailedMessageHere" in the script and execute the GIT script below: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

---
**Date:** currentDate()
**Developer:** Otavio Serra
**Project:** Conn2Flow version()
````