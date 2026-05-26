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

## BATCH-002 - Motor de Widgets Envelopados e Módulo Publisher Highlights

- [x] Motor de Widgets Envelopados (Regex de parsing de comentários no core `gestor.php`)
- [x] Injeção de parâmetro `$paramsArray['html']` no callback de widgets (`widgets.php`)
- [x] Criação da tabela `publisher_highlights` no banco local via Phinx migration
- [x] Mapeamento e deploy do módulo `publisher-highlights`
- [x] Testes administrativos do CRUD (Adicionar, Editar e Clonar)
- [x] Integração com `html-editor.php` (edição de HTML/CSS do banco e salvamento)
- [x] Renderização do widget com isolamento do item template via `<!-- item < -->` e substituição de placeholders
- [x] Retrocompatibilidade confirmada com a sintaxe legada `@[[widgets#...]]@`

### Evidência registrada em 2026-05-25 (Fase 1)

- Comandos/Procedimentos executados:
  - Verificação de logs e código para a Regex `/<!--\s*widgets#(.+?)\s*<\s*-->([\s\S]*?)<!--\s*widgets#\s*\\1\s*>\s*-->/i` em `gestor.php`.
  - Execução de Phinx migrations locais para atualizar a tabela `publisher_highlights`.
  - Edição de layouts usando os marcadores de wrapper de widget e validação de injeção dinâmica no Docker.
- Resultado observado:
  - O motor do core interceptou com sucesso os comentários estáticos de preview e os substituiu pela renderização dinâmica a partir do template cadastrado no banco.
  - A interface de vinculação dinâmica de placeholders foi exibida e funcionou sem erros no CRUD de edição.
  - As tags legadas de widgets em linha continuam renderizando perfeitamente.
- Pendências ou riscos restantes:
  - Nenhuma pendência aberta para a integração de Core Widgets e Highlights.

## BATCH-003 - Correções e Melhorias do Módulo Publisher Highlights

- [ ] Formulário completo em adicionar/clonar/editar
  - [ ] Adicionar inclui regra, modelo, mapeamento e editor HTML/CSS (mesma estrutura do editar)
  - [ ] Clonar inclui regra, modelo e mapeamento, preservando o html/css do registro de origem
  - [ ] Backend grava `fields_schema`, `html` e `css` em todas as três rotas
- [ ] Substituição do placeholder `#template_placeholder_option#` em todas as rotas
- [ ] Dropdown `template_id`
  - [ ] Lista templates ativos com `target='publisher-highlights'` na linguagem corrente
  - [ ] Marca `selected` o template ativo no registro em edição
- [ ] Visibilidade dinâmica do `.template-options-wrapper` controlada pelo `template_id`
- [ ] Regra "Automática" com dropdown de ordenação (`order_by`) e renderizador respeitando a opção
- [ ] Regra "Manual" usando dropdown múltiplo Fomantic (`.ui.multiple.search.selection.dropdown`)
  - [ ] AJAX `publisher-pages-search` retornando páginas ativas filtradas por `publisher_id`
  - [ ] Limpeza de seleção quando `publisher_id` muda
  - [ ] Pré-hidratação na tela de edição/clonagem com nomes resolvidos
- [ ] Editor HTML/CSS exibindo variáveis `[[item#NOME]]` (não `[[publisher#TIPO#ID]]`) no alvo `publisher-highlights`

### Evidência registrada em 2026-05-26 (Fase 1)

- Implementação dos sete itens do req-004 nos arquivos:
  - `gestor/modulos/publisher-highlights/publisher-highlights.php`
  - `gestor/modulos/publisher-highlights/publisher-highlights.js`
  - `gestor/modulos/publisher-highlights/publisher-highlights.widget.php`
  - `gestor/modulos/publisher-highlights/publisher-highlights.json`
  - `gestor/modulos/publisher-highlights/resources/pt-br/pages/publisher-highlights-{adicionar,editar,clonar}/*.html`
  - `gestor/modulos/publisher-highlights/resources/en/pages/publisher-highlights-{adicionar,editar,clonar}/*.html`
  - `gestor/bibliotecas/html-editor.php`
  - `gestor/assets/interface/html-editor-interface.js`
- Pendência: testes manuais no ambiente local (Docker) para confirmar:
  - dropdowns populados (publisher, template, manual selection)
  - visibilidade dinâmica `template-options-wrapper`
  - ordenação `order_by` aplicada no widget
  - editor exibindo `[[item#NOME]]` corretamente

## BATCH-004 - Renomeação Física de Diretórios e Arquivos de Templates

- [x] Diretórios `resources/pt-br/templates/` renomeados com prefixo `publisher-highlights-`
  - [x] `noticias-lista-simples` → `publisher-highlights-noticias-lista-simples`
  - [x] `noticias-grid-cards` → `publisher-highlights-noticias-grid-cards`
  - [x] `artigos-editorial` → `publisher-highlights-artigos-editorial`
  - [x] `lives-video-destaque` → `publisher-highlights-lives-video-destaque`
  - [x] `notas-mosaico` → `publisher-highlights-notas-mosaico`
  - [x] `destaque-principal-carousel` → `publisher-highlights-principal-carousel`
- [x] Diretórios `resources/en/templates/` renomeados com prefixo `publisher-highlights-`
  - [x] `noticias-lista-simples` → `publisher-highlights-noticias-lista-simples`
  - [x] `noticias-grid-cards` → `publisher-highlights-noticias-grid-cards`
  - [x] `artigos-editorial` → `publisher-highlights-artigos-editorial`
  - [x] `lives-video-destaque` → `publisher-highlights-lives-video-destaque`
  - [x] `notas-mosaico` → `publisher-highlights-notas-mosaico`
  - [x] `destaque-principal-carousel` → `publisher-highlights-destaque-principal-carousel`
- [x] Arquivos `.html` internos renomeados para corresponder ao novo nome do diretório (pt-br e en)
- [x] Checksums dos templates no JSON já estavam como strings vazias (prontos para recálculo pelo pipeline)

### Evidência registrada em 2026-05-26

- Renomeação executada via PowerShell; 12 arquivos HTML confirmados com nomes alinhados ao `id` do JSON
- Próximo passo: rodar `🗃️ Projects - Update => Core` para recalcular checksums via pipeline UPSERT

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
