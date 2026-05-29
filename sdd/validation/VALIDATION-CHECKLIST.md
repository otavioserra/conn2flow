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

## BATCH-005 - Correções Visuais, Simulação, Mapeamento e Fallback (req-006)

- [x] **Item 1** — Dropdown de itens manuais vazio corrigido
  - [x] `publisher_highlights_ajax_publisher_pages_search`: INNER JOIN com `publisher_pages`, filtro por `pp.publisher_id`
  - [x] `publisher_highlights_ajax_publisher_pages_fetch`: mesmo fix
- [x] **Item 2** — Iframe atualiza automaticamente ao selecionar template
  - [x] `window.html_editor_refresh_preview` exposto no html-editor-interface.js
  - [x] `publisher-highlights.js` chama `html_editor_refresh_preview` após `set_html`/`set_css`
- [x] **Item 3** — Simulação de variáveis `@[[item#...]]@` no html-editor
  - [x] Bloco `publisher-highlights` adicionado ao início de `publisherVariablesOrSimulation`
  - [x] Replica o bloco `<!-- item < -->..<!-- item > -->` N vezes (count do schema)
  - [x] Mapeia `@[[item#VAR]]@` → `variable_mapping` → tipo → `.hep-simulation-${tipo} .item`
- [x] **Item 4** — Segmento Fomantic com borda/fundo branco (`basic fitted` removidos)
  - [x] Todas as 6 páginas HTML (pt-br e en): `ui segment template-options-wrapper`
- [x] **Item 5** — Margens nos labels descritivos e botões de mapeamento
  - [x] `style="margin: 8px 0; display: block;"` nos `ui label` informativos (6 páginas)
  - [x] `style="margin-bottom:6px;margin-right:6px;"` nos botões `.item-var` e `.publisher-field`
- [x] **Item 6** — Campos do publisher filtrados por `linked_template: true`
  - [x] `publisher_highlights_ajax_publisher_load`: lê `template_map`, monta `linked_ids`, filtra `fields`
  - [x] Fallback: se nenhum campo tiver `linked_template`, inclui todos (retrocompatível)
- [x] **Items 7+9** — Suporte ao bloco `no-item` no widget renderer
  - [x] `publisher-highlights.widget.php`: detecta `<!-- no-item < -->..<!-- no-item > -->`
  - [x] Sem publicações: usa conteúdo do `no-item` (ou retorna `''` se não existir)
  - [x] Com publicações: remove bloco `no-item` antes de processar o loop de itens
- [x] **Item 8** — Bloco `no-item` inserido nos 12 templates físicos
  - [x] 6 pt-br com `<h3>Nenhuma publicação encontrada</h3>`
  - [x] 6 en com `<h3>No publications found</h3>`
  - [x] Checksums dos templates no JSON já estavam vazios (prontos para recálculo)

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.php`
  - `gestor/modulos/publisher-highlights/publisher-highlights.js`
  - `gestor/modulos/publisher-highlights/publisher-highlights.widget.php`
  - `gestor/assets/interface/html-editor-interface.js`
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/publisher-highlights-{adicionar,editar,clonar}/*.html`
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/templates/**/*.html` (12 templates)
- Pendência: testes manuais no ambiente local (Docker) para confirmar:
  - dropdown de itens manuais populado com páginas do publisher
  - iframe atualiza ao trocar template
  - simulação preenche variáveis `@[[item#...]]@` com dados fictícios
  - segment com fundo branco e botões com margem
  - campos do publisher filtrados por `linked_template`
  - bloco `no-item` exibido quando não há publicações

## BATCH-006 - Diagnóstico de Select, Mapeamento e Preview Real (req-007)

- [x] **Item 1** — Diagnóstico do select vazio
  - [x] JS: `beforeSend` envia `publisher_id`/`q` na raiz E em `params` (fallback duplo)
  - [x] PHP: `publisher-pages-search` e `publisher-pages-fetch` lêem `$_REQUEST['params'][X] ?? $_REQUEST[X]`
  - [x] PHP: chave `debug` na resposta com SQL, valores, contagem e idioma
- [x] **Item 2** — Ocultar itens já mapeados nas colunas
  - [x] `renderItemVars`: variáveis presentes em `variable_mapping` são filtradas
  - [x] `renderPublisherFields`: campos já usados como valor no mapeamento são filtrados
  - [x] Ao remover vínculo, colunas são re-renderizadas automaticamente
- [x] **Item 3** — Separar campos padrões (grey) e dinâmicos (teal)
  - [x] Padrões: `titulo`, `url`, `data` em botões `ui basic small button grey`
  - [x] Dinâmicos: demais campos em botões `ui basic small button teal`
  - [x] Subtítulos `Campos Padrões` e `Campos Dinâmicos` (i18n via skeleton)
- [x] **Item 4** — Editor/visualizador em abas + AJAX widget-preview
  - [x] Páginas (6×) com header renomeado `Conteúdo do Destaque`
  - [x] `html-editor-interface.js` oculta abas `modelos`, `assistente-ia`, `publisher-variables` para alvo highlights
  - [x] APIs públicas `html_editor_get_html/get_css/set_iframe_html/on_editor_change`
  - [x] PHP: endpoint `widget-preview` chama `publisher_highlights_widget_render_inline`
  - [x] JS: `scheduleWidgetPreview` com debounce 400ms + snapshot-diff para evitar chamadas redundantes
  - [x] Refresh dispara em: troca de template, troca de publisher, troca de rule/count/order_by, mapeamento link/unlink, seleção manual, edição de HTML/CSS, clique na aba do visualizador
- [x] **Item 5** — Componente de simulação dedicado para highlights
  - [x] `gestor/resources/{pt-br,en}/components/html-editor-publisher-highlights-simulation/html-editor-publisher-highlights-simulation.html` criados
  - [x] Tipos: `text`, `textarea`, `image`, `url`, `date` (sem variantes sofisticadas)
  - [x] `html-editor.php` carrega o componente específico quando `alvo === 'publisher-highlights'`
  - [x] `.publisher-design-mode-simulation` ocultado para alvo highlights
  - [x] Detecção de tipo em `publisherVariablesOrSimulation` mapeia `data/date → date` e `resumo/descri → textarea`

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.php` (AJAXes com debug + endpoint widget-preview)
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (render colunas, scheduleWidgetPreview, hooks)
  - `gestor/modulos/publisher-highlights/publisher-highlights.widget.php` (refatoração render_inline)
  - `gestor/assets/interface/html-editor-interface.js` (APIs públicas, oculta abas, detecção tipo)
  - `gestor/bibliotecas/html-editor.php` (carregamento do novo componente de simulação)
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/**/*.html` (header + skeletons)
- Arquivos criados:
  - `gestor/resources/pt-br/components/html-editor-publisher-highlights-simulation/html-editor-publisher-highlights-simulation.html`
  - `gestor/resources/en/components/html-editor-publisher-highlights-simulation/html-editor-publisher-highlights-simulation.html`
- Pendência: rodar `🗃️ Projects - Update => Core` para registrar o novo componente em `ComponentesData.json` e calcular checksums; depois validar manualmente:
  - dropdown manual populado (inspecionar `debug.sql` no Network)
  - colunas de variáveis/campos ocultam itens mapeados
  - botões padrões em cinza, dinâmicos em teal
  - abas extras escondidas, header `Conteúdo do Destaque` visível
  - prévia ao vivo no iframe ao mudar regras/seleção/editor
  - simulação usa massa simplificada (sem quebras de layout)

## BATCH-007 - Busca Manual, Abas Externas e Fallback de Simulação (req-008)

- [x] **Item 1** — Busca manual no dropdown de Itens selecionados
  - [x] `apiSettings` removido em `publisher-highlights.js`
  - [x] Dropdown inicializado localmente; listener `input/keyup` debounced (250ms) no `input.search` interno
  - [x] AJAX manual para `publisher-pages-search` com `publisher_id` e `q` na raiz e em `params`
  - [x] `<select>` atualizado preservando opções selecionadas + `dropdown('refresh')` + `set selected`
  - [x] Disparo inicial e em troca de publisher (`resetManualItemsDropdown` chama `manualItemsSearch('')`)
- [x] **Item 2** — Abas externas "Pré-Visualização" / "Editor HTML" e sincronização
  - [x] Ocultamento de sub-abas internas do html-editor revertido (5 sub-abas originais intactas)
  - [x] 6 páginas (pt-br + en × adicionar/editar/clonar) com `.menuConteudoDestaque` envolvendo iframe externo + `#html-editor#`
  - [x] JS: `$('.menuConteudoDestaque .item').tab()` inicializa as abas
  - [x] Novo iframe `#iframe-publisher-highlights-preview` recebe o HTML do AJAX `widget-preview`
  - [x] `window.updatedCodeMirrorHtml` definido em `publisher-highlights.js` → dispara `scheduleWidgetPreview(false)`
  - [x] Listener antigo `html_editor_on_editor_change` removido (substituído pelo hook global existente)
- [x] **Item 3** — Fallbacks na simulação de destaques
  - [x] `url` retorna `'#'` quando `.hep-simulation-url .item` vazio
  - [x] `date` retorna `'27/05/2026'` quando `.hep-simulation-date .item` vazio
  - [x] Demais tipos caem em `.hep-simulation-text .item` automaticamente
- [x] **Item 4** — Componente `html-editor-publisher-highlights-simulation` (recap req-007)
  - [x] Arquivos pt-br e en presentes em `resources/components/`
  - [x] `html-editor.php` carrega o componente específico para alvo highlights
  - [x] `.publisher-design-mode-simulation` permanece oculto para alvo highlights

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js` (reverteu ocultamento de abas internas; adicionou fallbacks url/date/text)
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (busca manual, hook `updatedCodeMirrorHtml`, refresh do iframe externo)
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/**/*.html` (6 páginas com menu de abas externas)
- Componentes inalterados desde BATCH-006:
  - `gestor/resources/{pt-br,en}/components/html-editor-publisher-highlights-simulation/html-editor-publisher-highlights-simulation.html`
- Pendência: rodar `🗃️ Projects - Update => Core` para recalcular checksums das páginas alteradas; validar manualmente:
  - dropdown manual lista páginas ao digitar (Network mostra `publisher-pages-search`)
  - 5 sub-abas internas do html-editor visíveis dentro da aba "Editor HTML"
  - aba "Pré-Visualização" renderiza widget com dados reais
  - alterações no CodeMirror disparam refresh automático debounced
  - variáveis `url` e `data` aparecem substituídas (não literais) na simulação

## BATCH-008 - Variáveis sem Arrobas, Regex de Simulação e Debounce Global (req-009)

- [x] **Item 1a** — Remover arrobas dos rótulos dinâmicos no JS
  - [x] `renderItemVars`: botão exibe `[[item#X]]`
  - [x] `renderLinkedVars`: rótulo da variável exibe `[[item#X]]`
- [x] **Item 1b** — HTMLs de `pages/` permanecem com `@[[item#...]]@` (cópia direta do banco)
  - [x] Pulado por orientação do Engenheiro Chefe — ver memória `feedback-conn2flow-variaveis-html-paginas`
- [x] **Item 2** — Regex de simulação dos destaques sem arrobas
  - [x] `publisherVariablesOrSimulation` (alvo `publisher-highlights`) usa `/\[\[item#([a-zA-Z0-9_\-]+)\]\]/g`
- [x] **Item 3** — Sincronização do mapeamento com a aba do editor
  - [x] `syncEditorVariables()` chama `window.publisher_highlights_update_target_variables(availableItemVars)`
  - [x] Disparada em: link (`.publisher-field` click), unlink (`[data-unlink]` click), troca de template
- [x] **Item 4** — Debounce global em todos os controles relevantes
  - [x] `#template_id` (com fallback para template vazio) → schedule
  - [x] `#rule`, `#count`, `#order_by` → schedule
  - [x] `#selected_items` (Fomantic onChange) → schedule
  - [x] Mapeamento link/unlink → schedule
  - [x] CodeMirror HTML → schedule via `window.updatedCodeMirrorHtml`
  - [x] `scheduleWidgetPreview` usa debounce 400ms + snapshot-diff (mantido do BATCH-006)

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (rótulos sem arrobas, `syncEditorVariables`, template empty → schedule)
  - `gestor/assets/interface/html-editor-interface.js` (regex de simulação sem arrobas)
- Pendência: rodar `🗃️ Projects - Update => Core` (apenas JS, não há mudança em páginas HTML); validar manualmente:
  - botões de variáveis aparecem como `[[item#X]]` sem `@@`
  - simulação substitui variáveis no preview interno (não aparecem literais)
  - vínculo/desvínculo de variáveis atualiza imediatamente a aba lateral do editor
  - todas as alterações nos selects/inputs disparam o iframe externo após ~400ms

## BATCH-009 - Persistência template_id, Preview Mapeado, Simulação Completa e Limpeza Final (req-010)

- [x] **Item 1** — `template_id` persistido em `fields_schema` (sem coluna nova)
  - [x] JS: hidratação no init via `$('#template_id').dropdown('set selected', ...)`
  - [x] JS: serialização no submit (`schema.template_id = $('#template_id').val()`)
  - [x] PHP `adicionar`: default `'template_id' => ''` em `$schema_inicial`
  - [x] PHP `editar`: `$fields_schema_decoded += [...'template_id' => '']` + `publisher_highlights_template_options($fields_schema_decoded['template_id'])`
  - [x] PHP `clonar`: idem ao editar
- [x] **Item 2** — Normalização de `fields_values` no widget
  - [x] `publisher_highlights_widget_buscar_publicacoes` converte `[{id, value}, ...]` → `{id: value, ...}` antes do `array_merge`
  - [x] Decodificação aceita `'[]'` como default (em vez de `'{}'`)
- [x] **Item 3** — Fallbacks robustos na simulação JS
  - [x] `image` → `https://picsum.photos/seed/highlights/800/450`
  - [x] `url` → `'#'`
  - [x] `date` → `'27/05/2026'`
  - [x] Demais buckets vazios caem em `.hep-simulation-text`
  - [x] Último recurso: `textarea` → resumo simulado; outros → "Título Simulado de Destaque"
- [x] **Item 4** — Refresh do preview interno ao trocar `template_id`
  - [x] `$template.on('change')` chama `window.html_editor_refresh_preview()` (delay 150ms)
- [x] **Item 5** — Arrobas removidos dos textos decorativos
  - [x] `<kbd>@[[item#...]]@</kbd>` → `<kbd>[[item#...]]</kbd>` em 6 páginas
  - [x] `<code>@[[item#nome_da_variavel]]@</code>` → `<code>[[item#nome_da_variavel]]</code>` em editar/adicionar (pt-br + en)

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (hidratação + submit do template_id; refresh do preview)
  - `gestor/modulos/publisher-highlights/publisher-highlights.php` (defaults com template_id; chamada de template_options com valor restaurado)
  - `gestor/modulos/publisher-highlights/publisher-highlights.widget.php` (normalização fields_values)
  - `gestor/assets/interface/html-editor-interface.js` (fallbacks robustos por tipo)
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/**/*.html` (6 páginas com `[[item#...]]`)
- Pendência: rodar `🗃️ Projects - Update => Core` para recompilar páginas; validar manualmente que:
  - template_id selecionado é restaurado ao reabrir o registro em editar/clonar
  - campos customizados (subtitulo, conteudo, etc.) renderizam no preview do widget
  - simulação substitui todas as variáveis (titulo, resumo, url, imagem, data) mesmo antes do deploy do componente
  - troca de modelo recarrega o preview interno do editor
  - títulos das colunas de mapeamento aparecem como `[[item#...]]` sem `@@`

## BATCH-010 - Ordem Manual, Hidratação, Vínculo no Inserir e Simulação Diversificada (req-011)

- [x] **Item 1** — Preservação da ordem cronológica na curadoria manual
  - [x] `jquery-custom-dropdown.js`: array interno `this.selectedIds` mantido em ordem de cliques
  - [x] `settings()` retorna callbacks `onAdd` (remove e re-empurra) e `onRemove`
  - [x] `setValues(values, selectedIds)` sincroniza `this.selectedIds` antes do `set exactly`
  - [x] `syncSelection` usa `this.selectedIds.slice()` (não o `<select>.val()` estrutural)
  - [x] `publisher-highlights.js` (submit e `refreshWidgetPreview`): só sobrescreve `schema.selected_items` do `<select>` quando `rule !== 'manual'`
- [x] **Item 2** — Hidratação visual dos selects `#rule` e `#order_by`
  - [x] `setTimeout(50)` chama `.dropdown('set selected', ...)` em ambos após a inicialização
- [x] **Item 3** — Inserção mostra a aba de variáveis para destaques
  - [x] `html-editor.php`: para `$alvo_atual === 'publisher-highlights'`, força `$tem_vinculo = true` independente de `$target_variables`
- [x] **Item 4** — Simulação dinâmica sem duplicidades
  - [x] `.publisher-design-mode-simulation` removido do DOM ao simular destaques
  - [x] Offsets rastreados por `varName` (uma chave por variável), não por `fieldType`
  - [x] Índice final: `(i + offsets[varName]) % simulItems.length` — cards e variáveis vizinhas diferem
- [x] **Item 5** — Enriquecimento da massa mock
  - [x] `text`: 20 títulos (pt-br) / 20 títulos (en)
  - [x] `textarea`: 12 resumos (pt-br) / 12 resumos (en)
  - [x] `image`: 15 URLs picsum com seeds variadas
  - [x] `url`: 12 caminhos fictícios
  - [x] `date`: 12 datas escalonadas em 2026

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/assets/interface/jquery-custom-dropdown.js` (selectedIds + onAdd/onRemove + setValues + syncSelection)
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (preservação da ordem manual + hidratação rule/order_by)
  - `gestor/bibliotecas/html-editor.php` (tem_vinculo sempre verdadeiro para highlights)
  - `gestor/assets/interface/html-editor-interface.js` (offsets por varName + remoção do design-mode)
- Arquivos sobrescritos:
  - `gestor/resources/{pt-br,en}/components/html-editor-publisher-highlights-simulation/html-editor-publisher-highlights-simulation.html` (massa mock enriquecida)
- Pendência: rodar `🗃️ Projects - Update => Core` para deployar componente mock + JS atualizado; validar manualmente:
  - clicar Nota 2 → Nota 1 preserva essa ordem no preview e no banco
  - selects de Regra e Ordenação refletem o valor salvo ao reabrir um registro
  - na tela "Adicionar", após escolher o modelo, a aba de variáveis aparece e permite mapeamento
  - simulação com 6+ cards mostra dados distintos para títulos e resumos vizinhos

## BATCH-011 - Nova Aba "Código do Widget" no Editor de Destaques (req-012)

- [x] **Item 1** — Aba e contêiner adicionados nas 6 páginas
  - [x] Menu `.menuConteudoDestaque` ganhou `<a data-tab="hep-widget">` (pt-br: "Código do Widget", en: "Widget Code")
  - [x] Cada página inclui `<div data-tab="hep-widget">` com `.ui icon message info` explicando o uso + `<textarea id="hep-widget-code">`
- [x] **Item 2** — Inicialização e atualização do CodeMirror
  - [x] `contentHighlightsTabHandler('hep-widget')` chama `updateWidgetCodeTab()`
  - [x] `updateWidgetCodeTab` re-tenta com `setTimeout(100)` se `CodeMirror` ainda não disponível
  - [x] Instância única (`widgetCodeMirror`) com `mode:'text/html'`, `readOnly:true`, `lineNumbers:true`, `lineWrapping:true`
  - [x] Slug derivado de `gestor.moduloRegistroId` ou placeholder localizado (`[slug-do-destaque]` / `[highlight-slug]`)
  - [x] HTML interno obtido via `window.html_editor_get_html()` (API exposta no BATCH-006)
  - [x] Saída no formato `<!-- widgets#publisher-highlights->render({"grupo_slug":"SLUG"}) < -->\nHTML\n<!-- ... > -->`
  - [x] `getDoc().setValue(...)` + `.refresh()` aplicados ao final

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (case `'hep-widget'` no handler + `updateWidgetCodeTab`)
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/publisher-highlights-{adicionar,editar,clonar}/*.html` (6 páginas)
- Pendência: rodar `🗃️ Projects - Update => Core` para recompilar páginas; validar manualmente:
  - clicar na aba "Código do Widget" abre CodeMirror com `<!-- widgets#publisher-highlights->render({"grupo_slug":"<slug>"}) < --> ... > -->`
  - editar HTML no editor e voltar para a aba reflete o novo conteúdo
  - na tela "Adicionar", slug aparece como `[slug-do-destaque]` / `[highlight-slug]` antes de salvar
  - editor é read-only (cursor visível mas teclas não modificam)

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
