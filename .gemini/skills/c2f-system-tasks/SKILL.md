---
name: c2f-system-tasks
description: Use ao consultar ou executar tarefas de automação do sistema (definidas em .vscode/tasks.json): Docker, compilação do Manager, atualizações de banco de dados, deploys de projetos, plugins e releases Git.
user-invocable: false
---

# Tarefas de Automação do Sistema (`c2f-system-tasks`)

Consulte e utilize as **Tarefas de Automação do Sistema** definidas em `.vscode/tasks.json`. Elas encapsulam scripts Bash e PHP centrais do Conn2Flow para acelerar testes, compilação, sincronizações e deploys:

## 1. Grupos Principais de Tarefas do Sistema

* **📦 Docker (Gestão de Container)**:
  - `📦 Docker - Container Status`: `docker ps`
  - `📦 Docker - Apache Logs > Real Time`: `docker logs conn2flow-app --tail 50 --follow`
  - `📦 Docker - PHP Logs > Real Time`: `docker exec conn2flow-app bash -c "tail -f /var/log/php_errors.log"`
  - `📦 Docker - PHP Logs Truncate`: `docker exec conn2flow-app bash -c "truncate -s 0 /var/log/php_errors.log"`

* **🛠️ Manager (Automação do Núcleo)**:
  - `🛠️ Manager - Update => All - Test Environment`: Executa a sequência completa (recursos ➔ arquivos ➔ banco) no ambiente de testes.
  - `🛠️ Manager - Synchronize => Resources - Local`: Roda a compilação de recursos (`atualizacao-dados-recursos.php`).
  - `🛠️ Manager - Create Module`: Roda o scaffold de novo módulo (`create-new-module.sh`).
  - `🛠️ Manager - GIT Release` / `GIT Commit`: Executa os pipelines normativos de release e commit.

* **🗃️ Projects (Gestão e Deploy de Projetos)**:
  - `🗃️ Projects - Deploy Current Project`: Compacta e envia deploy via API (`deploy-project-v2.sh --contents`).
  - `🗃️ Projects - Deploy Project -> ID`: Deploy direcionado por ID de projeto.
  - `🗃️ Projects - Recover Current Project`: Recuperação/pull de dados do servidor (`recover-project.sh`).
  - `🗃️ Projects - Sync Core -> ID`: Sincroniza atualizações do núcleo para o repositório do projeto.
  - `🗃️ Projects - Update => All - Core & Project`: Pipeline completo de atualização do core e do projeto.

* **🧩 Plugins (Automação de Plugins Públicos/Privados)**:
  - Sincronização, build local, recursos e releases para plugins sob `dev-plugins/plugins/`.

---

## 2. Parâmetros de Entrada (`inputs`)
- `moduloID`, `projectID`, `contentsChoice` (Sim / Não), `commitMsg`, `tagMsg`, `releaseMode` (automatic / manual).
