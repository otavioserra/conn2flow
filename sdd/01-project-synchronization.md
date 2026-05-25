# 01 Especificação Normativa: Sincronização de Projetos Específicos

## Objetivo e Contexto

Permitir que desenvolvedores trabalhem no core do Conn2Flow (`conn2flow/gestor/`) e sincronizem suas modificações (arquivos, atualizações de banco de dados) diretamente para o **ambiente de testes (Docker)** do projeto específico cadastrado no `environment.json` (como o projeto `transformamp-local` em `dev-environment/data/sites/localhost/transformamp/`). 

Isso une no mesmo diretório de testes do Docker:
1. Os arquivos base do motor/core do Conn2Flow (sincronizados da pasta `conn2flow/gestor/`).
2. Os arquivos específicos do projeto (sincronizados da pasta do repositório do projeto, ex: `transformamp/gestor/`, através do deploy do projeto).

Desta forma, os repositórios permanecem limpos e isolados no disco local do desenvolvedor, mas se integram no runtime do Docker para os testes locais.

## Requisitos de Implementação

### 1. Novo Script: Sincronização do Core para a pasta de Teste do Projeto
- **Caminho**: [sync-core-to-project.sh](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/ai-workspace/en/scripts/projects/sync-core-to-project.sh)
- **Comportamento**:
  - Aceitar parâmetro `--project <PROJECT_ID>` (ou `-p`).
  - Ler `devProjects.<PROJECT_ID>.target` no `environment.json` (com fallback para `devProjects.<PROJECT_ID>.path_tests` se `target` não estiver definido).
  - Validar a existência do diretório de testes do projeto.
  - Sincronizar o conteúdo de `./gestor/` (core da sessão de desenvolvimento ativa) para o **caminho de testes** do projeto usando `rsync` de forma incremental.
  - **Exclusões obrigatórias**: `.git/`, `logs/`, `temp/`, `resources.map.php`.
  - **Inclusão obrigatória**: `db/data/` deve seguir no sync do core, porque os JSONs compilados do gestor fazem parte do pipeline de atualização do ambiente de testes do projeto.

### 2. Sincronização de Arquivos do Projeto para Área de Testes (Fallback `path_tests`)
- **Caminho**: [synchronize-project.sh](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/ai-workspace/en/scripts/projects/synchronize-project.sh)
- **Modificação**:
  - Ajustar o parser do JSON do projeto em `devProjects.<PROJECT_ID>`.
  - Se a chave `target` não estiver definida no JSON, usar a chave `path_tests` como fallback automático para determinar a pasta de destino dos testes locais no Docker (onde tanto o core quanto o projeto serão executados).
  - Se ambas estiverem vazias ou ausentes, lançar erro de configuração amigável.

### 3. Atualização de Banco de Dados de Projetos
- **Caminho**: [updates-manager-database.sh](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh)
- **Modificação**:
  - Adicionar suporte ao parâmetro `--project <PROJECT_ID>` (ou `-p`).
  - Se o projeto for especificado, ler a chave `devProjects.<PROJECT_ID>.dockerPath` no `environment.json`.
  - **Fallback de dockerPath**: Se `dockerPath` não estiver explícito no bloco do projeto, derivar o caminho dentro do Docker a partir da pasta de testes local (`path_tests` ou `target`) do projeto:
    Substituir a raiz `/c/Users/otavi/OneDrive/Documentos/GIT/conn2flow/dev-environment/data/sites/` por `/var/www/sites/`.
  - Executar a migração de banco de dados (`atualizacoes-banco-de-dados.php`) usando a variável `PATH_DOCKER` resultante dentro do Docker Container `conn2flow-app`.

### 4. Configuração das Tasks no VS Code
- **Caminho**: [tasks.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/.vscode/tasks.json)
- **Novas Tasks**:
  - `🗃️ Projects - Resources Core -> ID`: Executa a compilação de recursos locais no repositório core (`atualizacao-dados-recursos.php`).
  - `🗃️ Projects - Sync Core -> ID`: Executa `sync-core-to-project.sh --project ${input:projectID}`.
  - `🗃️ Projects - Synchronize => Resources -> ID`: Executa `update-resource-data.sh --project ${input:projectID}`.
  - `🗃️ Projects - Synchronize => Database -> ID`: Executa `updates-manager-database.sh --project ${input:projectID}`.
  - `🗃️ Projects - Update => All - Core & Project`: Composta. Sincroniza e atualiza tanto o core Conn2Flow quanto o projeto. Agrupa sequencialmente em `dependsOn` (ordem de execução em `sequence`):
    1. `🗃️ Projects - Resources Core -> ID`
    2. `🗃️ Projects - Sync Core -> ID`
    3. `🗃️ Projects - Synchronize => Database -> ID`
    4. `🗃️ Projects - Synchronize => Resources -> ID`
    5. `🗃️ Projects - Synchronize => Files -> ID`
    6. `🗃️ Projects - Synchronize => Database -> ID`
  - `🗃️ Projects - Update => Project`: Composta. Sincroniza e atualiza apenas os arquivos e recursos do projeto (caso o core não tenha mudado). Agrupa sequencialmente:
    1. `🗃️ Projects - Synchronize => Resources -> ID`
    2. `🗃️ Projects - Synchronize => Files -> ID`
    3. `🗃️ Projects - Synchronize => Database -> ID`
  - `🗃️ Projects - Update => Core`: Composta. Sincroniza e atualiza apenas as mudanças do core Conn2Flow na pasta do projeto. Agrupa sequencialmente:
    1. `🗃️ Projects - Resources Core -> ID`
    2. `🗃️ Projects - Sync Core -> ID`
    3. `🗃️ Projects - Synchronize => Database -> ID`

## Critérios de Aceitação e Validação
Definidos em [VALIDATION-CHECKLIST.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md) sob o lote `BATCH-001`.
