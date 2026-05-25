# Validation Checklist

Use este checklist para validar batches no `conn2flow` sem perder de vista o baseline operacional do repositório.

## Onboarding SDD repo-wide

- [x] `CLAUDE.md` instalado na raiz do repositório
- [x] `.claude/` instalado com agents, rules, skills e settings do Claude Code
- [x] `.github/copilot-instructions.md` instalado
- [x] `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/` com artefatos SDD do Copilot
- [x] `sdd/scripts/hooks/` criado com hooks de sessão SDD
- [x] `sdd/human-requests/` ativo
- [x] `sdd/README.md`, `process/`, `implementation/`, `validation/` e `decisions/` criados
- [x] `sdd/00-baseline-architecture.md` criado com preservação do legado

## Checklist mínimo por batch

- [ ] O batch está registrado em `sdd/implementation/BATCH-INDEX.md`
- [ ] O impacto foi comparado contra `sdd/00-baseline-architecture.md`
- [ ] A menor validação executável do slice foi definida antes de editar mais do que o necessário
- [ ] Scripts, tasks ou paths alterados continuam coerentes com `dev-environment/data/environment.json`
- [ ] Não houve reescrita ampla do legado sem mudança normativa aprovada
- [ ] O review findings-first foi feito quando a mudança ficou pronta para avaliação

## Quando o batch tocar operação local

- [ ] Validar a task do VS Code mais próxima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execução correspondente
- [ ] Se tocar sincronização de projeto, validar source/target/path no `environment.json`
- [ ] Se tocar plugins, validar o fluxo na árvore `dev-plugins/`

## Evidência mínima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendências ou riscos restantes

## Regra final

Se não houver validação executável no slice atual, o batch deve registrar explicitamente por que a validação ficou documental ou manual.

## BATCH-001 - Sincronização de Projetos Específicos

- [x] Novo script `sync-core-to-project.sh` criado e funcional
  - [x] Recebe `--project` ou `-p`
  - [x] Lê `devProjects.<ID>.target` com fallback para `devProjects.<ID>.path_tests`
  - [x] Executa rsync com exclusões adequadas (`.git/`, `logs/`, `temp/`, `resources.map.php`) e mantém `db/data/` no sync do core
- [x] Script `synchronize-project.sh` modificado
  - [x] Faz fallback automático de `target` para `path_tests`
- [x] Script `updates-manager-database.sh` modificado
  - [x] Recebe `--project` ou `-p`
  - [x] Resolve `dockerPath` específico ou deriva a partir de `path_tests`/`target`
- [x] Tasks registradas no `.vscode/tasks.json`
  - [x] `🗃️ Projects - Resources Core -> ID`
  - [x] `🗃️ Projects - Sync Core -> ID`
  - [x] `🗃️ Projects - Synchronize => Resources -> ID`
  - [x] `🗃️ Projects - Synchronize => Files -> ID`
  - [x] `🗃️ Projects - Synchronize => Database -> ID`
  - [x] `🗃️ Projects - Update => All - Core & Project`
  - [x] `🗃️ Projects - Update => Project`
  - [x] `🗃️ Projects - Update => Core`
- [x] Teste end-to-end de execução das tarefas compostas de atualização para `transformamp-local`

### Evidência registrada em 2026-05-25 (Fase 1)

- Comandos executados:
  - `bash ./ai-workspace/en/scripts/projects/synchronize-project.sh --project transformamp-local checksum`
  - `bash ./ai-workspace/en/scripts/projects/sync-core-to-project.sh --project transformamp-local`
  - `bash ./ai-workspace/en/scripts/projects/sync-core-to-project.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/update-resource-data.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/synchronize-project.sh --project transformamp-local checksum && bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project transformamp-local`
- Resultado observado:
  - fallback `target -> path_tests` confirmado para `transformamp-local`
  - `sync-core-to-project.sh` executado com rsync e sem transferir `db/data`, `logs`, `temp` ou `resources.map.php`
  - atualização de recursos concluída com relatório final sem problemas
  - `dockerPath` derivado para `/var/www/sites/localhost/transformamp/` e migrações concluídas via Docker
- Pendências ou riscos restantes:
  - Falta rodar a geração de recursos no core antes de fazer a sincronização com o projeto (identificado no log req-002.md).

### Evidência registrada em 2026-05-25 (Fase 2 - ajuste req-002)

- Comandos executados:
  - `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php && bash ./ai-workspace/en/scripts/projects/sync-core-to-project.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/update-resource-data.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/synchronize-project.sh --project transformamp-local checksum && bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project transformamp-local`
  - task VS Code `🗃️ Projects - Resources Core -> ID`
  - task VS Code `🗃️ Projects - Update => All - Test Environment`
- Resultado observado:
  - recursos do core compilados antes da sincronização do core, com relatório final de `2014` itens e nenhum problema detectado
  - `sync-core-to-project.sh` executado com rsync após a compilação do core e mantendo a exclusão de `db/data`, `logs`, `temp` e `resources.map.php`
  - a task composta do VS Code percorreu a cadeia até `🗃️ Projects - Synchronize => Database -> ID`, encerrando com `Database updates completed successfully!`
- Pendências ou riscos restantes:
  - sem pendências funcionais abertas para o ajuste do req-002 no BATCH-001

### Evidência registrada em 2026-05-25 (Fase 3 - ajuste db/data no sync do core)

- Comandos executados:
  - `bash -n ./ai-workspace/en/scripts/projects/sync-core-to-project.sh`
  - `bash ./ai-workspace/en/scripts/projects/sync-core-to-project.sh --project transformamp-local`
  - `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php && bash ./ai-workspace/en/scripts/projects/sync-core-to-project.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/update-resource-data.sh --project transformamp-local && bash ./ai-workspace/en/scripts/projects/synchronize-project.sh --project transformamp-local checksum && bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project transformamp-local`
- Resultado observado:
  - `sync-core-to-project.sh` passou na validação sintática Bash sem erros
  - o comando rsync passou a executar sem `--exclude /db/data/`
  - a execução real do sync listou `db/data/` e os arquivos `*Data.json` do core na transferência para `transformamp-local`
  - o pipeline completo voltou a concluir com compilação de recursos do core, sync do core, compilação de recursos do projeto, sync do projeto e atualização de banco com sucesso
- Pendências ou riscos restantes:
  - sem pendências funcionais abertas para o ajuste de `db/data` no BATCH-001

### Evidência registrada em 2026-05-25 (Fase 4 - Nova divisão de tasks em 3 fluxos)

- Comandos/Tarefas executados:
  - task VS Code `🗃️ Projects - Update => All - Core & Project`
  - task VS Code `🗃️ Projects - Update => Project`
  - task VS Code `🗃️ Projects - Update => Core`
- Resultado observado:
  - As três novas tarefas compostas foram adicionadas a `.vscode/tasks.json` com sucesso.
  - `🗃️ Projects - Update => All - Core & Project` executa sequencialmente o pipeline completo (recursos do core, sync do core, banco intermediário, recursos do projeto, arquivos do projeto e banco final).
  - `🗃️ Projects - Update => Project` atualiza apenas os recursos, arquivos e banco do projeto.
  - `🗃️ Projects - Update => Core` atualiza apenas os recursos, arquivos (sync) e banco do core no projeto.
  - A sincronização de core respeita a inclusão da pasta `db/data` para garantir a cópia dos JSONs de dados compilados do gestor.
- Pendências ou riscos restantes:
  - sem pendências funcionais ou operacionais abertas para o BATCH-001.

## BATCH-DATA-001 - Reestruturação e Otimização de Dados e Sincronização

- [ ] Migrações Phinx alteradas de `linguagem_codigo` para `language`
  - [ ] Executar migração limpa em banco de dados vazio e verificar o schema resultante.
- [ ] Geração do arquivo descritor unificado:
  - [ ] Executar o script `atualizacao-dados-recursos.php` localmente.
  - [ ] Confirmar a criação de `db/data/schema-metadata.json`.
  - [ ] Verificar se as chaves de mapeamento (`strategy`, `natural_key_columns`, `preserve_on_user_modified`, `insert_only`) dos módulos e recursos globais estão consolidadas corretamente.
- [ ] Execução de Ganchos Locais:
  - [ ] Validar a leitura e processamento sequencial de múltiplos arquivos `data-hooks.php`.
- [ ] Validação do Loteador Dinâmico (Packet Safety):
  - [ ] Simular um limite `max_allowed_packet` muito baixo (ex: 200KB) e rodar o atualizador.
  - [ ] Confirmar se o motor fragmenta dados grandes de HTML/CSS em sub-lotes e se as inserções completam sem erros.
- [ ] Deleção Imperativa de Dados:
  - [ ] Declarar uma chave de deleção no JSON e verificar se a remoção correspondente ocorre no banco durante o deploy.
- [ ] Validação de Transações:
  - [ ] Simular um erro de banco de dados no meio da sincronização de uma tabela.
  - [ ] Confirmar se o `rollBack()` restaura o estado anterior da tabela sem alterações parciais.
