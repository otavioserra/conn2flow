---
name: c2f-vscode-tasks
description: Use ao consultar ou executar tarefas de automação do VS Code (tasks.json) no Conn2Flow: Docker, Manager, Projetos, Plugins e Releases.
user-invocable: false
---

# Tarefas de Automação do VS Code (`.vscode/tasks.json`)

Consulte e utilize as convenções das tarefas integradas do VS Code para acelerar testes, compilação e deploy no Conn2Flow:

## 1. Grupos Principais de Tarefas

* **📦 Docker**:
  - `📦 Docker - Container Status`: `docker ps`
  - `📦 Docker - Apache Logs > Real Time`: `docker logs conn2flow-app --tail 50 --follow`
  - `📦 Docker - PHP Logs > Real Time`: `docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log"`
  - `📦 Docker - PHP Logs Truncate`: `docker exec conn2flow-app bash -c "truncate -s 0 /var/log/php_errors.log"`

* **🛠️ Manager (Core)**:
  - `🛠️ Manager - Update => All - Test Environment`: Executa sequencialmente a compilação de recursos, sincronização de arquivos e banco no ambiente de testes.
  - `🛠️ Manager - Synchronize => Resources - Local`: Roda `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`.
  - `🛠️ Manager - Create Module`: Roda `create-new-module.sh "${input:moduloID}"`.
  - `🛠️ Manager - GIT Release` / `GIT Commit`: Executa scripts de release e commit normativos.

* **🗃️ Projects (Projetos)**:
  - `🗃️ Projects - Deploy Current Project`: Roda `deploy-project-v2.sh --contents ${input:contentsChoice}`.
  - `🗃️ Projects - Deploy Project -> ID`: Executa deploy direcionado por ID de projeto.
  - `🗃️ Projects - Recover Current Project`: Executa o pull de banco/dados do servidor via `recover-project.sh`.
  - `🗃️ Projects - Sync Core -> ID`: Sincroniza o núcleo atualizado para a pasta do projeto.
  - `🗃️ Projects - Update => All - Core & Project`: Sequência completa de atualização do core e do projeto.

* **🧩 Plugins (Públicos e Privados)**:
  - Synchronize, Build Local, Plugin Resources, GIT Commit e GIT Release para plugins ativos sob `dev-plugins/plugins/`.

---

## 2. Parâmetros de Entrada (`inputs`)
- `moduloID`, `projectID`, `contentsChoice` (Sim / Não), `commitMsg`, `tagMsg`, `releaseMode` (automatic / manual).
