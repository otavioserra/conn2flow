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

## BATCH-013 - Correção de Sincronização, CodeMirror e Renderização do Widget (req-013)

- [x] **Item 1** — Flag `ignoreCallbacks` previne loops em mutações programáticas
  - [x] Construtor inicializa `this.ignoreCallbacks = false`
  - [x] `setValues` envolve `change values` / `clear` / `set exactly` em `try/finally` setando a flag
  - [x] `onAdd` e `onRemove` retornam imediatamente quando a flag está ativa
- [x] **Item 2** — `syncSelection` simplificada
  - [x] Retorna imediatamente se `ignoreCallbacks` ativa
  - [x] Confia sempre em `this.selectedIds.slice()`; removida a reidratação por `readSelection()`
- [x] **Item 3** — Acoplamento `setSelectedIds → scheduleWidgetPreview` validado
  - [x] `schema.selected_items = (selectedIds || []).slice()` (cópia defensiva)
  - [x] `scheduleWidgetPreview(false)` chamado sem condicional
- [x] **Item 4** — CodeMirror do widget consistente e sem duplicação
  - [x] Tema `tomorrow-night-bright`, modo `htmlmixed` com `htmlMode: true`
  - [x] `lineNumbers`, `lineWrapping`, `styleActiveLine`, `matchBrackets`, `indentUnit: 4`, `readOnly: true`
  - [x] `setSize('100%', 800)` aplicado após instanciação
  - [x] Antes de instanciar, recupera referência existente em `$textarea.next('.CodeMirror')[0].CodeMirror`
- [x] **Item 5** — Regex backend sem arrobas
  - [x] `publisher-highlights.widget.php`: `preg_replace_callback('/\[\[item#X\]\]/', ...)`
  - [x] `publisher-highlights.php` (`extract_item_variables`): `'/\[\[item#X\]\]/'`
  - [x] `publisher-highlights.php` (`ajax_template_load`): `'/\[\[item#X\]\]/'`
- [x] **Item 6** — Restrição de versionamento respeitada
  - [x] Nenhum `git commit` ou `git push` executado

### Evidência registrada em 2026-05-27

- Arquivos alterados:
  - `gestor/assets/interface/jquery-custom-dropdown.js` (ignoreCallbacks + try/finally + syncSelection simplificada)
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (slice defensivo em setSelectedIds + CodeMirror estilizado e dedup)
  - `gestor/modulos/publisher-highlights/publisher-highlights.widget.php` (regex sem arrobas no loop de itens)
  - `gestor/modulos/publisher-highlights/publisher-highlights.php` (regex sem arrobas em extract_item_variables e ajax_template_load)
- Pendência: rodar `🗃️ Projects - Update => Core` para deploy JS+PHP atualizados; validar manualmente:
  - Adicionar 2 itens manuais (Nota 2 → Nota 1): preview atualiza em cada clique mantendo ordem
  - Remover tag clicando no "x": preview atualiza removendo o item correto
  - Alternar template com itens selecionados: renderiza todos, não só o primeiro
  - Trocar de aba Editor HTML → Widget Code e voltar: não duplica CodeMirror; tema dark e altura 800px aplicados
  - Site público renderiza valores reais dos campos (titulo/url/data/customs) em vez de `[[item#X]]` literais

## BATCH-014 - Refatoração de Curadoria Manual, Autocomplete Ajax e Reordenação Drag and Drop (req-014)

- [x] **Item 1** — Remoção completa do componente antigo
  - [x] Arquivo `gestor/assets/interface/jquery-custom-dropdown.js` deletado
  - [x] Inclusões `gestor_pagina_javascript_incluir('biblioteca', [...'jquery-custom-dropdown'...])` removidas das 3 funções (`adicionar`/`editar`/`clonar`)
  - [x] JS sem referências a `manualItemsDropdown`, `ensureManualItemsDropdown`, `PublisherHighlightsCustomDropdown` ou `#selected_items`
- [x] **Item 2** — Dependência Sortable.js incluída
  - [x] `gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>')` em `adicionar`/`editar`/`clonar`
- [x] **Item 3** — Estrutura HTML do autocomplete nas 6 páginas
  - [x] `<select id="selected_items">` substituído por `#manual_search_input` + `#search-suggestions-dropdown` + `#selected-labels-container`
  - [x] pt-br com placeholders/avisos em português; en com placeholders/avisos em inglês
- [x] **Item 4** — Lógica JS do autocomplete (`publisher-highlights.js`)
  - [x] Hidratação inicial via `publisher-pages-fetch` respeitando a ordem de `schema.selected_items`
  - [x] Busca incremental via `publisher-pages-search` com debounce de 300ms
  - [x] Tags Fomantic UI (label teal) com handle de arraste (`grip vertical`) e botão remover (`remove-tag-btn`)
  - [x] Itens já selecionados aparecem desabilitados nas sugestões
  - [x] Fechamento do dropdown ao clicar fora ou pressionar `Esc`
  - [x] Seleção/remoção/reordenação atualizam `schema.selected_items` e disparam `scheduleWidgetPreview(false)`
- [x] **Item 5** — Integração Sortable.js
  - [x] `new Sortable(#selected-labels-container, { handle: '.grip.vertical.icon', onEnd })` relê a ordem física do DOM
- [x] **Item 6** — Serialização do formulário
  - [x] Submit e widget-preview enviam `fields_schema` por cópia (`out`), com `selected_items` vazio para regra não-manual e preservando o estado em memória
- [x] **Item 7** — CodeMirror da aba "Código do Widget" (`#hep-widget-code`)
  - [x] Case `'hep-widget'` reintroduzido em `contentHighlightsTabHandler`
  - [x] `updateWidgetCodeTab` com retry quando `CodeMirror` indisponível e dedup via `$textarea.next('.CodeMirror')`
  - [x] Instância única read-only, tema `tomorrow-night-bright`, modo `htmlmixed`, `setSize('100%', 800)`
  - [x] Envelopamento `<!-- widgets#publisher-highlights->render({"grupo_slug":"SLUG"}) < --> ... > -->` com slug de `gestor.moduloRegistroId` ou placeholder localizado
- [x] **Item 8** — Restrição de versionamento respeitada (nenhum `git commit`/`git push`)

### Evidência registrada em 2026-06-04

- Validação executável (estática, sem ambiente Docker nesta rodada):
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` → `JS_OK` (sem erros de sintaxe)
  - `php -l gestor/modulos/publisher-highlights/publisher-highlights.php` → `No syntax errors detected`
  - Grep em `gestor/` por `jquery-custom-dropdown` e `id="selected_items"` → nenhum resultado (remoção completa confirmada)
  - Grep no JS por `manualItemsDropdown|PublisherHighlightsCustomDropdown|ensureManualItemsDropdown` → apenas o comentário de contexto remanescente; nenhuma chamada viva
- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.php` (Sortable.js CDN no lugar do include antigo, 3 funções)
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (autocomplete + tags + Sortable + `updateWidgetCodeTab`)
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/publisher-highlights-{adicionar,editar,clonar}/*.html` (6 páginas)
- Arquivo removido:
  - `gestor/assets/interface/jquery-custom-dropdown.js`
- Decisão registrada: [DEC-021](../decisions/DECISION-LOG.md) (supera DEC-018/019/020)
- Pendência: rodar `🗃️ Projects - Update => Core` para recompilar páginas/JS e validar manualmente no Docker:
  - digitar no campo de busca lista sugestões (Network mostra `publisher-pages-search`); item já selecionado fica desabilitado
  - clicar numa sugestão adiciona a tag ao final e atualiza o preview ao vivo
  - arrastar tags (handle) reordena e o preview reflete a nova ordem; abrir o registro novamente preserva a ordem salva
  - remover tag pelo "x" tira do preview o item correto
  - `Esc`/clique fora fecham a lista de sugestões
  - aba "Código do Widget" exibe o CodeMirror dark read-only (800px) com o wrapper `widgets#publisher-highlights->render(...)`, sem duplicar instância ao alternar abas

## BATCH-015 - Correções Residuais de Destaques e Inicialização do Módulo de Menus (req-015)

### Parte 1 — Correções residuais do `publisher-highlights`

- [x] **Item 1.1** — Contagem da simulação segue o campo `#count`
  - [x] `html-editor-interface.js` (`publisherVariablesOrSimulation`, alvo `publisher-highlights`) lê `#count` do DOM
  - [x] `count = Math.max(1, parseInt(countVal || schema.count || 4, 10))` (reflete o input antes de salvar)
- [x] **Item 1.2** — Alinhamento do dropdown de autocomplete
  - [x] `margin: 0 !important;` adicionado ao `#search-suggestions-dropdown` nas 6 páginas (adicionar/editar/clonar × pt-br/en)
- [x] **Item 1.3** — Visibilidade dinâmica do `#selected-labels-container`
  - [x] `toggleSelectedLabelsVisibility()` criada em `publisher-highlights.js`
  - [x] Invocada ao final de `renderSelectedLabels()`, após adicionar tag e após remover tag
- [x] **Item 1.4** — Drag handle livre + filtro + cursores grab/grabbing
  - [x] `handle: '.grip.vertical.icon'` removido; toda a tag é arrastável
  - [x] `filter: '.remove-tag-btn'` adicionado (clique no "x" não inicia arraste)
  - [x] CSS grab/grabbing injetado no head via JS (`.drag-label`, `:active`, `.sortable-drag`, `.remove-tag-btn`)

### Parte 2 — Inicialização do módulo `menus` (ver DEC-022)

- [x] **2.1/2.2** — Clonagem física `publisher-highlights/` → `menus/` com renomeação de arquivos (`menus.php`, `menus.json`, `menus.js`, `menus.widget.php`) e diretórios/arquivos de páginas (`menus-adicionar/editar/clonar`); identificadores internos substituídos para `menus`/`id_menus`
- [x] **2.3** — Registro em `gestor/db/data/ModulosData.json` (pt-br + en, grupo `administracao-gestor`, ícone `sitemap`)
- [x] **2.4** — Migração Phinx `20260701110000_create_menus_table.php` (tabela `menus` **sem** `publisher_id`, demais colunas análogas a `publisher_highlights`)
- [x] **2.5** — Limpeza preliminar
  - [x] HTML das 6 páginas: removidos publicador de origem (`#publisher_id`), regra (`#rule`), quantidade (`#count`), ordenação (`#order_by`) e painel de mapeamento de variáveis; mantido o wrapper de autocomplete (agora buscando páginas do site)
  - [x] `menus.widget.php`: removidas rotinas de paginação dinâmica e herança de publisher; renderizador processa injeção simples a partir de `selected_items` (slugs de `paginas`) com `[[item#label]]`/`[[item#url]]`/`[[item#slug]]`
  - [x] `menus.php` desacoplado do publisher (sem validação/insert/update/select de `publisher_id`; AJAX `pages-search`/`pages-fetch` varrem `paginas`)
- [x] 6 templates próprios de menu criados (`pt-br` + `en`); 6 templates de destaques removidos do clone

### Evidência registrada em 2026-06-04

- Validação executável (estática, sem ambiente Docker nesta rodada):
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` → `PH_JS_OK`
  - `node --check gestor/assets/interface/html-editor-interface.js` → `HEI_JS_OK`
  - `node --check gestor/modulos/menus/menus.js` → `MENUS_JS_OK`
  - `php -l gestor/modulos/menus/menus.php` → `No syntax errors detected`
  - `php -l gestor/modulos/menus/menus.widget.php` → `No syntax errors detected`
  - `php -l gestor/db/migrations/20260701110000_create_menus_table.php` → `No syntax errors detected`
  - `JSON.parse` de `menus.json` e `ModulosData.json` → `OK`
  - Grep por `publisher` na pasta `gestor/modulos/menus/` → apenas os `.md` de ai_modes (reescritos para menus); sem acoplamento residual em PHP/JS/JSON/HTML
- Arquivos alterados (Parte 1):
  - `gestor/assets/interface/html-editor-interface.js`
  - `gestor/modulos/publisher-highlights/publisher-highlights.js`
  - `gestor/modulos/publisher-highlights/resources/{pt-br,en}/pages/publisher-highlights-{adicionar,editar,clonar}/*.html` (6 páginas)
- Arquivos criados/alterados (Parte 2):
  - `gestor/modulos/menus/**` (módulo completo: php/json/js/widget + 6 páginas + 12 templates + 2 ai_modes)
  - `gestor/db/migrations/20260701110000_create_menus_table.php`
  - `gestor/db/data/ModulosData.json`
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `🗃️ Projects - Update => Core` para compilar recursos do `menus`, calcular checksums (atualmente vazios), aplicar a migração no runtime de testes e registrar páginas/templates/módulo no banco. Depois, validar manualmente:
  - menu administrativo "Menus do Site" aparece no grupo Administração do Gestor
  - adicionar/editar/clonar salvam `name` + `fields_schema` + `html`/`css` sem erro de `publisher_id`
  - autocomplete lista páginas do site; tags adicionam/removem/reordenam e o contêiner só aparece com ≥1 item
  - aba "Pré-Visualização" renderiza o menu com os itens reais; aba "Código do Widget" mostra o wrapper `widgets#menus->render(...)`
  - Parte 1: simulação dos destaques respeita o `#count`; dropdown de autocomplete alinhado; grab no label inteiro

## BATCH-016 - Hierarquia Multi-nível de Menus e Drag-and-Drop Estilo WordPress (req-016)

### Contrato e decisão

- [x] Contrato de `fields_schema.selected_items` migrado para árvore tipada (ver [DEC-023]); sem migração de banco (`fields_schema` já é `json`)
- [x] Retrocompatibilidade: lista de slugs (BATCH-015) é interpretada como itens `pagina` de nível raiz

### Itens tipados e filtro de página

- [x] **5 tipos de item** disponíveis no construtor: `pagina`, `link-custom`, `cabecalho`, `link-action`, `separador`
- [x] Campos condicionais por tipo (rótulo/URL/classes CSS) mostrados/ocultados conforme o tipo
- [x] **Filtro de tipo de página** (rádios `pagina`/`sistema`/`ambos`, default `pagina`) exibido apenas para o tipo `pagina`
- [x] AJAX `pages-search` aplica `AND p.tipo='<valor>'` para `pagina`/`sistema` e ignora o filtro em `ambos` (default `pagina`)
- [x] `pages-search`/`pages-fetch` retornam `url` (caminho canônico) para hidratar nós de página

### Editor de árvore (componente próprio)

- [x] Componente de drag-and-drop bidimensional próprio: Pointer Events (JS vanilla) + visual Fomantic-UI, **sem** jQuery UI/nestedSortable/Sortable.js
- [x] Dependência CDN do `Sortable.js` removida das 3 funções de `menus.php`
- [x] Modelo interno flat-com-`depth`; arraste vertical reordena e arraste horizontal indenta/desindenta (clamp em `depth(anterior)+1`); placeholder mostra posição+recuo
- [x] Mover um item move também seus descendentes (preservando recuo relativo)
- [x] Edição inline (rótulo/URL/classes CSS conforme tipo) e exclusão recursiva (item + descendentes)
- [x] Inserção do novo item logo abaixo do item selecionado (como irmão) ou no fim da raiz
- [x] Serialização flat→árvore no submit e no preview; hidratação árvore→flat na carga

### Renderização recursiva (widget)

- [x] Três delimitadores suportados: `no-item`, `item` (folha) e `item-parent` (com `[[item#children]]`)
- [x] `menus_render_level()` recursiva; tipo `pagina` resolve `label`/`url` do banco pelo `page_id` (links canônicos)
- [x] Variáveis expostas: `[[item#label]]`, `[[item#url]]`, `[[item#slug]]`, `[[item#css_classes]]`, `[[item#children]]`
- [x] Substituição tolerante às arrobas do banco (`@[[item#X]]@` → valor, sem sobra de `@valor@`)
- [x] Fallback DFS quando o template não tem `item-parent` (nenhum item é perdido)
- [x] 12 templates (6 pt-br + 6 en) atualizados com bloco `item-parent`

### Evidência registrada em 2026-06-04

- Validação executável (estática + teste de unidade do renderizador, sem ambiente Docker nesta rodada):
  - `php -l gestor/modulos/menus/menus.php` → `No syntax errors detected`
  - `php -l gestor/modulos/menus/menus.widget.php` → `No syntax errors detected`
  - `node --check gestor/modulos/menus/menus.js` → `menus.js OK`
  - `JSON.parse` de `gestor/modulos/menus/menus.json` → `menus.json OK`
  - Teste PHP do renderizador recursivo (stubs de `banco_select`/`banco_escape_field`, arquivo temporário já removido) cobrindo 4 cenários:
    - **árvore**: `cabecalho` com filhos usou `item-parent`; filhos `pagina` resolvidos do banco (`Economia`, link canônico) e label customizado preservado (`Esportes (custom)`); `link-custom` folha via `item`; arrobas consumidas corretamente
    - **vazio**: usou bloco `no-item`
    - **legado** (lista de slugs): convertido em nós `pagina` raiz e resolvido
    - **fallback** (template sem `item-parent`): árvore achatada via DFS, sem perder itens
- Arquivos alterados:
  - `gestor/modulos/menus/menus.php` (filtro `tipo` em `pages-search`, `url` em `pages-search`/`pages-fetch`, remoção do Sortable.js CDN)
  - `gestor/modulos/menus/menus.widget.php` (renderização recursiva completa: `menus_render_level`, normalização da árvore, resolução por tipo, fallback DFS)
  - `gestor/modulos/menus/menus.js` (componente de árvore: flat-com-depth, DnD vanilla por Pointer Events, edição inline, add por tipo, autocomplete com filtro, serialização)
  - `gestor/modulos/menus/resources/{pt-br,en}/pages/menus-{adicionar,editar,clonar}/*.html` (6 páginas: construtor de itens + filtro de página + contêiner da árvore; info de "Conteúdo do Menu" atualizada em adicionar/editar)
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-*/*.html` (12 templates: bloco `item-parent`)
  - `gestor/modulos/menus/menus.json` (version dos recursos alterados 1.1 → 1.2)
- Decisão registrada: [DEC-023](../decisions/DECISION-LOG.md) (estende DEC-022; mantém DEC-014)
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `🗃️ Projects - Update => Core` para compilar recursos do `menus`, recalcular checksums (o pipeline UPSERT recalcula) e aplicar no ambiente de testes. Depois, validar manualmente:
  - adicionar itens de cada tipo (página com filtro pagina/sistema/ambos; link-custom; cabeçalho; link-action; separador)
  - arrastar para ordenar e para indentar/desindentar (estilo WordPress); mover pai move os filhos
  - editar rótulo/URL/classes inline; excluir nó com filhos remove a subárvore
  - reabrir o registro preserva a árvore salva (hidratação)
  - aba "Pré-Visualização" renderiza a árvore com submenus; aba "Código do Widget" mostra o wrapper `widgets#menus->render(...)`

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

## BATCH-017 - Ajustes e Correções no Módulo de Menus (req-017)

- [x] **Item 1.1** — Variáveis e Simulação no Editor HTML para o alvo `menus`
  - [x] `menus.php`: nova `menus_variaveis_template()` (variáveis fixas `label`/`url`/`slug`/`css_classes`/`children`) passada como `target_variables` nas 3 chamadas (adicionar/editar/clonar)
  - [x] `html-editor.php`: `menus` adicionado ao `$backupCallbackMap`; `case 'menus'` no switch carrega o componente de simulação e os controles de variáveis; `html_editor_publisher_controls` trata `menus` (força vínculo + monta `template_map` de `target_variables`)
  - [x] `html-editor-interface.js`: helper `alvoUsaItemVars()` (highlights + menus) usado em `regexVariaveisGlobal`, `publisherVariablesSearch`, `publisherTableVariables`, `addVariableSkeleton`, `remove-variable-skeleton`; design-mode-simulation oculto para menus
- [x] **Item 1.2** — Componente de Simulação de Menus
  - [x] `gestor/resources/{pt-br,en}/components/html-editor-menus-simulation/*.html` com árvore mockada JSON (página, cabeçalho com filhos, link-custom, link-action, separador; 2 níveis)
  - [x] Simulação recursiva no `publisherVariablesOrSimulation` (branch `menus`) espelhando `menus.widget.php` (blocos item/item-parent/no-item + `[[item#children]]`); fallback embutido (`MENUS_SIM_FALLBACK`) caso o componente não esteja deployado
- [x] **Item 1.3** — Correção do alternador de tipo de item (`menus.js`)
  - [x] `currentItemType()` lê `dropdown('get value')` (com fallback `.val()` → `'pagina'`); `onChange` propaga o `value` para `toggleItemTypeFields`
- [x] **Item 1.4** — Melhorias no Placeholder do Drag-and-Drop (`menus.js`)
  - [x] `.menu-tree-placeholder` com `min-height:38px` (altura de item real) + flex centralizado
  - [x] Texto "Solte o item aqui"/"Drop item here" entre setas ← / →
- [x] **Item 1.5** — Correção de Hover nos Submenus (templates)
  - [x] `menus-dropdown` (pt-br+en): removidos `mt-1` (dropdown principal) e `ml-1` (submenu) — colam no gatilho mantendo a ponte de `:hover`
  - [x] `menus-horizontal-navbar` (pt-br+en): removido `mt-1` (submenu cola no `<li>.group` via `top-full`)
- [x] **Versionamento** — `menus.json`: `version` 1.3→1.4 nos 4 templates alterados (`menus-dropdown` e `menus-horizontal-navbar`, pt-br+en); checksums **mantidos intactos** (recálculo automático pelo pipeline UPSERT — ver `MEMORIA-ENGENHARIA-EXECUCAO.md`, não calcular/alterar checksums manualmente)

### Evidência registrada em 2026-06-05

- Validação executável (estática + teste de unidade da simulação, sem ambiente Docker nesta rodada):
  - `php -l gestor/bibliotecas/html-editor.php` → `No syntax errors detected`
  - `php -l gestor/modulos/menus/menus.php` → `No syntax errors detected`
  - `node --check gestor/assets/interface/html-editor-interface.js` → OK
  - `node --check gestor/modulos/menus/menus.js` → OK
  - `JSON.parse` da árvore mockada (`hep-menus-simulation-tree`) em pt-br e en → OK (5 itens raiz cada)
  - `JSON.parse` de `menus.json` → OK (4 templates em version 1.4)
  - Teste da simulação recursiva (réplica das funções JS) contra o template `menus-dropdown` real: sem variáveis `[[item#X]]` literais; sem sobra de `@valor@`; submenu (`Sobre Nós` em `Institucional`) renderizado via `item-parent`; bloco `no-item` removido com itens; delimitadores consumidos; itens raiz folha presentes — 7/7 asserts OK
- Arquivos alterados:
  - `gestor/modulos/menus/menus.php` (variáveis fixas + target_variables)
  - `gestor/modulos/menus/menus.js` (alternador de tipo + placeholder do DnD)
  - `gestor/modulos/menus/menus.json` (version/checksum dos templates `menus-dropdown` e `menus-horizontal-navbar`)
  - `gestor/bibliotecas/html-editor.php` (case `menus` + controls + backupCallbackMap)
  - `gestor/assets/interface/html-editor-interface.js` (helper `alvoUsaItemVars` + simulação recursiva de menus)
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-dropdown/*.html` (hover)
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-horizontal-navbar/*.html` (hover)
- Arquivos criados:
  - `gestor/resources/{pt-br,en}/components/html-editor-menus-simulation/html-editor-menus-simulation.html`
- Decisão registrada: [DEC-024](../decisions/DECISION-LOG.md) (estende DEC-022/DEC-023; reutiliza a infraestrutura de variáveis `[[item#X]]` do html-editor)
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para registrar o novo componente em `ComponentesData.json`, recalcular checksums dos templates alterados e aplicar no ambiente de testes. Depois, validar manualmente:
  - editar um menu → aba "Editor HTML" → botões "Variáveis"/"Simular": "Simular" renderiza o template com a árvore mockada (com submenus); "Variáveis" lista `[[item#label/url/slug/css_classes/children]]`
  - construtor de itens: trocar o "Tipo de Item" para link-custom/cabeçalho/link-action/separador exibe os inputs corretos e oculta a busca de páginas
  - arrastar item: a caixa de drop tem altura de item e mostra "Solte o item aqui" com setas ← →
  - preview/site com template `menus-dropdown` ou `menus-horizontal-navbar`: o submenu permanece aberto ao mover o mouse do item pai para a lista flutuante

## BATCH-018 - Tipo Publicador, Correções no Menus e Módulo de Galerias (req-018)

### Parte 1 — Tipo Publicador e correções no Menus (DEC-025)

- [x] Interface de formulário de Menus (adicionar/editar/clonar × pt-br/en):
  - [x] Opção "Publicador" / "Publisher" adicionada ao dropdown `#item_type`.
  - [x] Exibição condicional de inputs: dropdown de publicadores (`#item_publisher_id`), limite (`#item_publisher_count`), e ordenação (`#item_publisher_order_by`) sob `#field-publisher-wrapper`.
- [x] Árvore visual e persistência de Menus (menus.js):
  - [x] Salvar `publisher_id`, `count` e `order_by` (+`publisher_name`) no schema do nó `publicador` (flatten/buildTree).
  - [x] Renderizar nó visual `Publicador: <nome> (limite: N)` na árvore.
  - [x] Impedir aninhamento manual de sub-itens sob o item publicador (clamp de `maxD` no DnD; inserção como irmão).
  - [x] Edição inline de Rótulo/Publicador/Limite/Ordenação/Classes CSS no painel do nó.
- [x] Backend CRUD de Menus (menus.php):
  - [x] `menus_publisher_options()` busca publicadores ativos e substitui `#publisher_id_options#`; chamada em adicionar/editar/clonar.
- [x] Renderização dinâmica de Menus (menus.widget.php):
  - [x] `menus_widget_buscar_publicacoes_publicador()` busca publicações por `paginas.publisher_id` com `count`/`order_by`.
  - [x] `menus_widget_expandir_publicadores()` injeta as publicações como filhos `pagina` antes da renderização recursiva (normalização preserva os campos do publicador).
- [x] Simulação do HTML Editor de Menus:
  - [x] `html-editor-interface.js`: `menusExpandirPublicadores()` gera `count` sub-itens `pagina` mock sob cada nó `publicador`.
  - [x] Componente `html-editor-menus-simulation` (pt-br/en) e fallback `MENUS_SIM_FALLBACK` com exemplo de nó `publicador`.
- [x] Correções adicionais no Menus (req-018):
  - [x] Alteração de `template_id` atualiza o CodeMirror mesmo focado/oculto (refresh agendado em `html_editor_set_html`/`set_css`).
  - [x] Alternador de `item_type` exibe/oculta os campos corretos (toggle estende `#field-publisher-wrapper`).
  - [x] Variáveis `[[item#slug]]` (como `data-slug`) e `[[item#css_classes]]` (anexada às classes) incluídas nos 12 templates.

### Parte 2 — Módulo de Galerias de Imagens (DEC-026)
- [x] Criação do Módulo de Galerias (req-018):
  - [x] Estrutura clonada de `publisher-highlights`/`menus` desacoplada de publisher (tabela `galleries` **sem** `publisher_id`).
  - [x] Migração Phinx `20260701120000_create_galleries_table.php` (tabela `galleries` com colunas pedidas + campos do sistema).
  - [x] Módulo `galleries` registrado em `ModulosData.json` (pt-br/en, grupo `administracao-gestor`, ícone `images`) e em `UsuariosPerfisModulosData.json`.
  - [x] Botão "Selecionar Imagens do Servidor" (`#btn-select-images`) abre o modal `iframePagina` apontando para `admin-arquivos/?paginaIframe=sim` (setup via `galleries_imagepick_setup`).
  - [x] Listener de `postMessage` em `galleries.js` valida `tipo` de imagem e adiciona à lista **sem fechar** o modal (seleção em lote).
  - [x] Cada imagem na curadoria exibe thumbnail, nome, input de legenda e botão remover.
  - [x] Reordenação drag-and-drop com `Sortable.js` (CDN), relendo a ordem física do DOM no `onEnd`.
  - [x] Serialização de `fields_schema.selected_items` com `id`, `caminho`, `imgSrc`, `nome` e `legenda`.
  - [x] CRUD backend (`galleries.php`: adicionar/editar/clonar) integrado ao ImagePick e ao html-editor (alvo `galleries`).
  - [x] Renderizador (`galleries.widget.php`) decodifica o JSON e renderiza item/no-item com `[[item#img-src]]`/`[[item#caminho]]`/`[[item#nome]]`/`[[item#legenda]]`.
  - [x] 4 templates padrões (`galleries-grid`, `galleries-carousel`, `galleries-masonry`, `galleries-slider`) em pt-br/en.
  - [x] Aba de simulação/variáveis para `galleries` no HTML Editor (`case 'galleries'` + `alvoUsaItemVars`) com componente mockado Picsum e fallback `GALLERIES_SIM_FALLBACK`.
- [ ] Ações pós-implementação (com o operador):
  - [ ] Executar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para registrar módulo/páginas/templates/componente, calcular checksums e aplicar a migração `galleries` no runtime.

### Evidência registrada em 2026-06-05 (BATCH-018)

- Validação executável (estática + testes de unidade, sem ambiente Docker nesta rodada):
  - `php -l` OK em `menus.php`, `menus.widget.php`, `galleries.php`, `galleries.widget.php`, `html-editor.php` e na migração `20260701120000_create_galleries_table.php`
  - `node --check` OK em `menus.js`, `galleries.js`, `html-editor-interface.js`
  - `JSON.parse` OK em `menus.json`, `galleries.json`, `ModulosData.json`, `UsuariosPerfisModulosData.json` e nos componentes de simulação (menus c/ nó publicador; galleries c/ 6 imagens)
  - Teste do widget `menus` (publicador) com stubs de banco — 9/9 asserts OK: expansão do publicador, limite `count`, ordenação, injeção como `item-parent`, sem `[[item#X]]`/arrobas residuais
  - Teste da simulação JS de `menus` (réplica real do arquivo) — 7/7 asserts: 4 sub-itens mock sob o publicador, submenu, sem variáveis literais
  - Teste do widget `galleries` com stubs — 11/11 asserts: img-src absoluta preservada, relativa prefixada com url-raiz, legendas/nome, 2 blocos item, CSS injetado, no-item exibido só quando vazio
  - Teste da simulação JS de `galleries` (réplica real) — 6/6 asserts: 6 imagens Picsum, sem variáveis literais
- Decisões registradas: [DEC-025](../decisions/DECISION-LOG.md) (tipo publicador + correções no menus) e [DEC-026](../decisions/DECISION-LOG.md) (módulo galleries)
- Bug corrigido durante a implementação: `menus_widget_normalizar_itens` descartava `publisher_id`/`count`/`order_by`, impedindo a expansão do publicador (corrigido).
- Harmonização com edição do Engenheiro Chefe: o `#item_type` do menus voltou a `<select>` nativo (correção do alternador, req-018 §1.2) — `currentItemType()` e o construtor do publicador passaram a ler valores via `.val()`/`option:selected`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `🗃️ Projects - Update => Core` (registra o módulo `galleries`, novos componentes `html-editor-galleries-simulation`/atualização do `html-editor-menus-simulation`, recalcula checksums e aplica a migração). Depois, validar manualmente:
  - **Menus / publicador**: adicionar item "Publicador", escolher publicador/limite/ordenação; a árvore mostra `Publicador: <nome> (limite: N)`; não permite aninhar sob ele; preview/site geram os N sub-itens com as publicações reais; aba "Simular" mostra sub-itens mock.
  - **Menus / correções**: trocar `template_id` com a aba "Editor HTML" aberta atualiza o CodeMirror; alternar tipo mostra os campos certos; `[[item#slug]]`/`[[item#css_classes]]` saem no HTML final.
  - **Galleries**: menu "Galerias de Imagens" aparece; "Selecionar Imagens do Servidor" abre o gerenciador e permite escolher várias seguidas sem fechar; legenda editável; arrastar reordena; salvar/reabrir preserva a ordem; aba "Pré-Visualização" e o widget `widgets#galleries->render(...)` renderizam as imagens; aba "Simular" usa imagens Picsum.

## BATCH-019 - Correções no Menus e Lógica do Módulo de Galerias (req-019)

- [x] Módulo de Menus (req-019):
  - [x] Margem superior de 1rem inserida no contêiner `#btn-add-item-wrapper` (nos 3 HTMLs pt-br/en).
  - [x] Campo de target `#custom-target` inserido nos formulários e controlado condicionalmente no tipo `link-custom`.
  - [x] Edição inline de target funcional no painel da árvore visual e persistência no schema JSON.
  - [x] Campo de rótulo disponível no tipo `separador` na interface e na edição inline.
  - [x] Suporte ao bloco `item-separator` no backend (`menus.widget.php`) e simulação em JS (`html-editor-interface.js`).
  - [x] Atributo `target="[[item#target]]"` e divisores visuais `item-separator` incluídos em todos os 12 templates.
  - [x] Spacing horizontal aumentado de `gap-6` para `gap-8` no template `menus-horizontal-navbar`.
  - [x] Hamburguer mobile alterado para clique via botão no HTML e manipulado por `menus.widget.js`.
  - [x] Links pais clicáveis e tags `<a>` presentes no template `menus-footer-colunas`.
  - [x] Hover do dropdown em múltiplos subníveis funcionando via fallback em JS no `menus.widget.js`.
- [x] Módulo de Galerias (req-019):
  - [x] Campos de controles (`show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`) inseridos nas 3 páginas HTML pt-br/en.
  - [x] Hidratação, persistência e serialização dos controles configurada em `galleries.js`.
  - [x] Resolução de imagem no widget público (`galleries.widget.php`) prioriza `caminho` original em vez de `imgSrc` miniatura.
  - [x] Atributos de dados `data-*` correspondentes às configurações de controle gerados no DOM do widget.
  - [x] Renderizador trata blocos condicionais de controles (`controls-arrows`, `controls-dots`, `dot-item` interno) no backend e na simulação.
  - [x] Marcação de setas, dots e dot-items incluída nos templates `galleries-carousel.html` e `galleries-slider.html`.
  - [x] JavaScript do widget (`galleries.widget.js`) gerencia a rolagem horizontal suave, navegação por setas, dot pagination e temporizador de autoplay.
- [x] Registro e Prompts de IA (req-019):
  - [x] Bloco `ai_prompts_targets` e `ai_modes` configurados e presentes em `galleries.json` (pt-br/en).
  - [x] Arquivo `galleries.md` de prompt de IA criado e validado em `pt-br/ai_modes/galleries/` e `en/ai_modes/galleries/`.
  - [x] Arquivo `menus.md` de prompt de IA atualizado em `pt-br/ai_modes/menus/` e `en/ai_modes/menus/` com as novas variáveis (`target`, `css_classes`, `children`) e divisor de separador.
  - [x] Variáveis globais (`show_arrows`, etc.) expostas em `galleries_variaveis_template()` com `'global' => true`.
  - [x] `html-editor.php` gerando placeholders `[[show_arrows]]` (sem `item#`) na interface do editor e populo correto do placeholder `{{variables}}` em `html_editor_ajax_ia_requests()`.
  - [x] `galleries.widget.php` resolvendo globalmente as variáveis no HTML final (ex: `[[autoplay]]` -> `true`/`false`).
- [ ] Ações pós-implementação:
  - [ ] Executar `atualizacao-dados-recursos.php` para sincronizar e registrar os novos templates, componentes de simulação, alvos/modos de IA e variáveis no banco.

### Evidência registrada em 2026-06-05 (BATCH-019)

- Validação executável (estática + teste de unidade dos renderers, sem ambiente Docker nesta rodada):
  - `php -l` OK em `menus.php`, `menus.widget.php`, `galleries.php`, `galleries.widget.php` e `html-editor.php`.
  - `node --check` OK em `menus.js`, `menus.widget.js` (novo), `galleries.js`, `galleries.widget.js` (novo) e `html-editor-interface.js`.
  - `JSON.parse` OK em `menus.json`, `galleries.json` e nas árvores mockadas dos componentes `html-editor-menus-simulation` (pt-br/en, com link `_blank` e separador rotulado).
  - Teste de unidade dos renderers (`menus.widget.php` + `galleries.widget.php` com stubs de banco) — **27/27 asserts OK**: target `_self`/`_blank` (link-custom), bloco `item-separator` com/sem rótulo, fallback do separador sem o bloco, submenu recursivo, resolução de páginas; galerias com `img-src` priorizando `caminho` (relativo prefixado / absoluto preservado), 3 slides, `controls-arrows`/`controls-dots` condicionais, 3 dots com índice e classe ativa só no primeiro, variáveis globais `data-autoplay`/`data-speed`/`data-loop` resolvidas, galeria vazia (no-item + controles removidos).
- Bug corrigido durante a implementação: `menus_widget_normalizar_itens` descartava o campo `target`, fazendo o link-custom cair sempre em `_self` (corrigido — preserva `target`).
- Arquivos alterados (menus): `menus.php`, `menus.js`, `menus.widget.php`, `menus.json`, 6 páginas (`menus-{adicionar,editar,clonar}` pt-br/en), 12 templates, `menus.md` (pt-br/en), `html-editor-menus-simulation` (pt-br/en).
- Arquivos criados (menus): `menus.widget.js`.
- Arquivos alterados (galleries): `galleries.php`, `galleries.js`, `galleries.widget.php`, `galleries.json`, 6 páginas, `galleries-carousel`/`galleries-slider` (pt-br/en).
- Arquivos criados (galleries): `galleries.widget.js`, `ai_modes/galleries/galleries.md` (pt-br/en).
- Arquivos alterados (core compartilhado): `gestor/bibliotecas/html-editor.php` (flag `global` no `template_map` + casos `menus`/`galleries` no AJAX de IA) e `gestor/assets/interface/html-editor-interface.js` (simulação de target/separador no menus e de setas/dots/dot-item/globais nas galerias).
- Decisões registradas: [DEC-027](../decisions/DECISION-LOG.md) a [DEC-031](../decisions/DECISION-LOG.md).
- Versionamento: versões incrementadas nos recursos alterados (templates/páginas de menus e galerias, ai_mode de menus, `versao` dos módulos `menus` 1.0.1→1.0.2 e `galleries` 1.0.0→1.0.1); checksums mantidos intactos (recálculo automático pelo pipeline UPSERT).
- Restrição respeitada: nenhum `git commit`/`git push` executado.
- Pendência (com o operador): rodar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para registrar os novos templates, o componente atualizado, o alvo/modo de IA `galleries`, as variáveis e recalcular checksums; aplicar no ambiente de testes. Depois, validar manualmente:
  - **Menus / target**: criar um item `link-custom` com "Nova aba", salvar/reabrir e confirmar `target="_blank"` no preview e no site.
  - **Menus / separador**: adicionar separador com e sem rótulo; confirmar o bloco `item-separator` renderizando o divisor (com/sem texto) nos templates.
  - **Menus / widget JS**: no template mobile, o botão hambúrguer abre/fecha a lista; no dropdown, o submenu abre por hover mesmo se os named groups do Tailwind falharem.
  - **Galerias / controles**: marcar/desmarcar setas, pontinhos, autoplay, loop e ajustar o tempo; confirmar que o preview e o site refletem (carrossel/slider deslizam, dots sincronizam, autoplay respeita o tempo e pausa no hover).
  - **Galerias / imagem**: confirmar que o site usa o `caminho` original (não a miniatura) na tag `<img>`.
  - **IA**: na aba do Modo de IA do alvo `galleries`, confirmar que o prompt recebe as variáveis de item e as globais; no menus, as novas variáveis (`target`, `css_classes`, `children`).

## BATCH-020 - Integração do Tailwind CSS CLI no Core do Sistema e Pipeline de Release (req-020)

- [x] Estrutura de Estilo do Core:
  - [x] Arquivo `gestor/assets/tailwindcss/input.css` criado contendo `@import "tailwindcss";` e a diretiva `@config "../../../tailwind.config.js";`.
- [x] Configurações de Ambiente Local e Templates:
  - [x] Variável `"tailwindcss/cli"` adicionada no bloco `"devEnvironment"` em `environment.json`.
  - [x] Variável `"tailwindcss/cli"` adicionada no bloco `"devEnvironment"` do template `templates/environment/environment.json`.
  - [x] Auditoria comparativa do template com o arquivo ativo concluída e quaisquer variáveis estruturais ausentes normalizadas no template.
- [x] Sincronização e Compilação Local:
  - [x] `synchronize-manager.sh` lê a chave do environment (via `jq` e fallback regex), executa o build na pasta `gestor/` se configurado, e aborta a sincronização em caso de falha.
  - [x] `sync-core-to-project.sh` lê a chave do environment, executa o build do Tailwind na pasta `gestor/` se configurado, e aborta em caso de falha.
- [x] Pipeline de Release (GitHub Actions):
  - [x] `release-gestor.yml` inclui a etapa de configuração do Node.js v20.
  - [x] `release-gestor.yml` executa a compilação do Tailwind CSS CLI (`npx @tailwindcss/cli -i ./assets/tailwindcss/input.css -o ./assets/tailwindcss/output.css --minify`) antes de empacotar.
  - [x] `release-gestor.yml` inclui a linha `git add gestor/assets/tailwindcss/*.css` na etapa de commit das atualizações.

### Evidência registrada em 2026-06-08 (BATCH-020)

- Validação executável:
  - `bash -n` verificado em `synchronize-manager.sh` e `sync-core-to-project.sh` -> OK.
  - Leitura de JSON via `jq` da chave `tailwindcss/cli` em ambos JSONs (`environment.json` ativo e template) -> JSON válido.
  - Fallback regex verificado para extrair o comando correto de `devEnvironment`.
  - Integridade estrutural do `release-gestor.yml` validada por inspeção visual.
- Arquivos alterados/criados:
  - Criado estilo core `gestor/assets/tailwindcss/input.css`
  - Adicionado bloco `devEnvironment` com a nova chave em `dev-environment/data/environment.json` e no template `dev-environment/templates/environment/environment.json`.
  - Scripts de build e sync atualizados: `synchronize-manager.sh` e `sync-core-to-project.sh`.
  - Workflow `.github/workflows/release-gestor.yml` atualizado para configurar Node.js v20, compilar Tailwind CSS v4 CLI e comitar os arquivos gerados.
- Correção pós-execução do operador (2026-06-08): na primeira execução real de `sync-core-to-project.sh`, o passo de compilação Tailwind não disparou (só o `input.css` sincronizou, sem `output.css`). Causa raiz: no **jq 1.8.1**, o filtro `jq -r '.devEnvironment."tailwindcss/cli" // empty'` retorna vazio — a **barra `/` na chave em notação de ponto** interage incorretamente com o operador `//` (confirmado: `.devEnvironment.source // empty` funciona; `.devEnvironment."tailwindcss/cli" // empty` retorna vazio; `.devEnvironment["tailwindcss/cli"] // empty` funciona). Correção aplicada nos dois scripts: **notação de colchetes** `.devEnvironment["tailwindcss/cli"]`. Após o fix, a leitura via jq retorna o comando e o bloco de compilação dispara (o fallback regex de `synchronize-manager.sh` já estava correto). Requer Node.js disponível no host para o `npx`; em falha de compilação o sync aborta with `exit 1`.

## BATCH-021 - Lançamento v2.8.0, Correção HTML e Automação de Campos (req-021)

- [x] Módulo `publisher-pages`:
  - [x] Hidden input com `name="field_<id>"` para campos HTML inicializado em `publisher-pages.js` com o valor atual do editor Quill no carregamento da página.
- [x] Módulo `publisher`:
  - [x] Botão `#add-all-fields-btn` incluído nas 6 páginas HTML (adicionar/editar/clonar em pt-br/en).
  - [x] Visibilidade do botão `#add-all-fields-btn` controlada dinamicamente via `templateWrapper` em `publisher.js`.
  - [x] Clique do botão `#add-all-fields-btn` instanciando apenas os campos do modelo não vinculados.
  - [x] Rótulo (label) formatado via `generateLabelFromId`.
  - [x] Preenchimento inicial do input `.field-template` em `addFieldRow` se `template_field_id` for fornecido.
  - [x] Preenchimento de prompt em `updateFieldTemplateSearches` contendo fallback para campos adicionados dinamicamente em lote.
- [x] Lançamento v2.8.0:
  - [x] `CHANGELOG.md` e `CHANGELOG-PT-BR.md` atualizados com as notas da versão 2.8.0.
  - [x] `README.md` e `README-PT-BR.md` com a versão e destaques atualizados.
  - [x] Workflow `release-gestor.yml` atualizado na ação `Create Release` para referenciar as novidades da 2.8.0.

### Evidência registrada em 2026-06-08 (BATCH-021)

- Validação executável (estática, sem ambiente Docker nesta rodada):
  - `node --check gestor/modulos/publisher/publisher.js` → `publisher.js OK`
  - `node --check gestor/modulos/publisher-pages/publisher-pages.js` → `publisher-pages.js OK`
  - `release-gestor.yml`: integridade do block scalar `body: |` validada por inspeção (conteúdo a 10 espaços; `draft`/`prerelease`/`files` preservados a 8 espaços). PyYAML indisponível no host nesta rodada.
- Arquivos alterados:
  - `gestor/modulos/publisher-pages/publisher-pages.js` (inicializa o hidden input do campo HTML com o valor corrente do Quill dentro da iteração `.quill-editor`)
  - `gestor/modulos/publisher/publisher.js` (`templateWrapper` controla `#add-all-fields-btn`; helper `generateLabelFromId`; handler de clique em lote sobre `fieldSets.available`; pré-preenchimento do `.field-template` em `addFieldRow`; fallback de prompt `[[publisher#tipo#id]]` em `updateFieldTemplateSearches`)
  - `gestor/modulos/publisher/resources/{pt-br,en}/pages/publisher-{adicionar,editar,clonar}/*.html` (6 páginas com `#add-all-fields-btn` ao lado de `#add-field-btn`)
  - `CHANGELOG.md`, `CHANGELOG-PT-BR.md` (seção `[2.8.0] - 2026-06-08`)
  - `README.md`, `README-PT-BR.md` (versão e destaques v2.8.0)
  - `.github/workflows/release-gestor.yml` (corpo da ação `Create Release` reescrito para v2.8.0)
- Decisão registrada: [DEC-034](../decisions/DECISION-LOG.md#dec-034---2026-06-08---accepted)
- Pendência: rodar `🗃️ Projects - Update => Core` para recompilar as 6 páginas HTML alteradas e validar manualmente no Docker:
  - editar uma publicação com campo HTML sem tocar no editor e salvar → o conteúdo HTML é preservado (não esvazia)
  - selecionar um modelo no publisher exibe `#add-all-fields-btn`; clicar adiciona apenas os campos ainda não vinculados, com labels capitalizados (ex: `lista_signatarios` → `Lista Signatarios`)
  - cada campo em lote já vem com `.field-template` e prompt preenchidos como `[[publisher#tipo#id]]`
  - trocar para um modelo vazio/none oculta novamente o botão

## BATCH-022 - Pré-visualizador de HTML Externo Unificado (req-022)

- [x] Módulo Destaques (`publisher-highlights.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.
- [x] Módulo Menus (`menus.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.
- [x] Módulo Galerias (`galleries.js`):
  - [x] Função `scheduleWidgetPreview` chama `window.previewExternalHtmlConteudo` para gerar o HTML do iframe de pré-visualização.
  - [x] Passa `dados.html`, `css` e `gestor.html_editor.framework_css` de forma correta.
  - [x] Mantém fallback de segurança caso a função não exista no escopo.

### Evidência registrada em 2026-06-09 (BATCH-022)

- Validação executável (estática):
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` -> OK
  - `node --check gestor/modulos/menus/menus.js` -> OK
  - `node --check gestor/modulos/galleries/galleries.js` -> OK
- Arquivos alterados:
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (refatorado callback AJAX success do `widget-preview`)
  - `gestor/modulos/menus/menus.js` (refatorado callback AJAX success do `widget-preview`)
  - `gestor/modulos/galleries/galleries.js` (refatorado callback AJAX success do `widget-preview`)
- Decisão registrada: [DEC-035](../decisions/DECISION-LOG.md#dec-035---2026-06-09---accepted)
- Pendência:
  - Validar manualmente no ambiente local (Docker):
    - Pré-visualização do módulo Destaques carrega perfeitamente e aplica o CSS customizado e framework adequados.
    - Pré-visualização do módulo Menus carrega recursivamente e renderiza a árvore simulada.
    - Pré-visualização do módulo Galerias renderiza as imagens e controles.


## BATCH-023 - Otimização de CSS Automático com Filtragem de Redundâncias (req-023)

- [x] JavaScript do Editor HTML (`html-editor-interface.js`):
  - [x] Função `updateCSSCompiled` varre os stylesheets do iframe do previewer para extrair seletores de `system-output.css` e `output.css`.
  - [x] O conjunto `systemSelectors` (`Set`) é populado com as regras de classe simples (`selectorText`).
  - [x] A folha de estilos do Tailwind CDN (`styleSheet.cssRules`) é percorrida e as regras são filtradas para remover duplicidades.
  - [x] Regras `@media` têm suas sub-regras limpas e re-montadas de forma não redundante.
  - [x] A instância CodeMirror `CodeMirrorCssCompiled` recebe o valor do CSS limpo sem travar o editor.
  - [x] Compatibilidade de leitura estruturada via `sheet.cssRules` assegurada para Tailwind v3 (innerHTML) e v4 (insertRule).
- [x] Limpeza de Templates de Layout:
  - [x] CDNs redundantes de Tailwind CSS v3 e Fomantic UI removidos dos 9 templates de layout em `gestor/resources/pt-br/templates/`.
  - [x] Placeholders `<!-- pagina#titulo -->` e `<!-- pagina#js -->` padronizados nos layouts.

### Evidência registrada em 2026-06-09 (BATCH-023)

- Validação executável:
  - `node --check gestor/assets/interface/html-editor-interface.js` -> OK
  - `git diff gestor/resources/pt-br/templates` inspecionado → 9 arquivos de templates limpos e com placeholders normalizados.
- Arquivos alterados:
  - `gestor/assets/interface/html-editor-interface.js` (refatorada a rotina de extração e filtragem da função `updateCSSCompiled`)
  - 9 arquivos de layout HTML em `gestor/resources/pt-br/templates/` (limpeza de CDNs de frameworks e placeholders)

- Decisão registrada: [DEC-036](../decisions/DECISION-LOG.md#dec-036---2026-06-09---accepted)
- Pendência:
  - Testar manualmente no navegador (painel do Gestor):
    - Inserir tags HTML usando classes comuns (ex: `flex`, `hidden`). O painel CodeMirror "CSS Compilado" deve permanecer vazio.
    - Inserir uma classe exclusiva nova (ex: `bg-emerald-950`). O painel CodeMirror deve ser preenchido apenas com a regra específica para esta classe.
    - Confirmar que o salvamento no banco grava a string contendo apenas o CSS reduzido.


## BATCH-024 - Links Dinâmicos em Galerias, Controles de Exibição e Correções de Layout (req-024)

- [x] Galerias: Links Individuais por Imagem (Painel e Widget):
  - [x] Cada item na curadoria do painel tem o botão retrátil "Configurar Link" com ícone `linkify`.
  - [x] Exibe inputs dinâmicos conforme o tipo de link selecionado: Página (com dropdown carregado via global `galleries_pages`), Link Customizado (URL input + target), Link com Classe CSS (URL input + target + CSS class), Última Publicação (dropdown de publicadores via global `galleries_publishers` + dropdown ordenação).
  - [x] Serialização correta dos novos atributos no array `selected_items` de `fields_schema` (itens normalizados com `link_type`/`link_page_id`/`link_url`/`link_target`/`link_css_classes`/`link_publisher_id`/`link_order_by`).
  - [x] Widget renderiza os links nas imagens substituindo `[[item#link-url]]`, `[[item#link-target]]` e `[[item#link-css-classes]]` (páginas resolvidas em lote; publicador resolvido via publicação mais recente com cache).
  - [x] Caso `link_type` seja `'nenhum'`, retorna `'javascript:void(0);'` no link-url.
- [x] Galerias: Controles de Exibição Globais (Altura e Margem Lateral):
  - [x] Novos inputs de Altura (padrão 300) e Margem Lateral (padrão 0) na aba "Controles de Exibição" do painel de Galerias (6 páginas pt-br/en).
  - [x] Serialização correta e instantânea ao digitar, disparando o preview da aba ao vivo (listener `input change` em `#gallery-height`/`#gallery-margin-lateral`).
  - [x] Templates visuais atualizados: margem lateral (`[[margin_lateral]]`) na `<section>` raiz de **todos** (carousel, grid, slider, masonry); altura (`[[height]]`) em carousel, grid e slider. O masonry **não** recebe altura fixa para preservar o fluxo natural de colunas (alvenaria), conforme req §2.3.
- [x] Destaques do Publicador: Mapeamento de Variáveis (`linked-fields-list`):
  - [x] Tags renderizadas no container de vinculação de campos têm espaçamento horizontal (`margin: 2px 4px`).
- [x] Menus: Submenus no Menu Horizontal (`menus-horizontal-navbar`):
  - [x] Submenus de nível 2, 3 e subsequentes no menu horizontal têm o recuo/padding horizontal interno correto (`0.5rem 1rem`) e hover apropriado, sem que o texto dos links encoste nas bordas (bloco `<style>` na `<section>` raiz, pt-br/en).
- [x] Galerias: Legenda do Layout Masonry (`galleries-masonry`):
  - [x] Legendas das imagens no masonry não ficam coladas no canto esquerdo ou inferior (`mt-1 px-1` → `mt-2 px-3 pb-2`, pt-br/en).

### Evidência de Validação (BATCH-024)

- Validação estática executada em 2026-06-10:
  - `node --check gestor/modulos/galleries/galleries.js` → OK (sem erros de sintaxe).
  - `node --check gestor/modulos/publisher-highlights/publisher-highlights.js` → OK.
  - `php -l gestor/modulos/galleries/galleries.php` → `No syntax errors detected`.
  - `php -l gestor/modulos/galleries/galleries.widget.php` → `No syntax errors detected`.
- Arquivos alterados:
  - `gestor/modulos/galleries/galleries.js` (contrato de item com `link_*`, painel "Configurar Link" com visibilidade dinâmica, controles globais altura/margem).
  - `gestor/modulos/galleries/galleries.php` (`galleries_link_listas_setup()` injeta `galleries_pages`/`galleries_publishers`; `galleries_variaveis_template()` registra `link-url`/`link-target`/`link-css-classes` e globais `height`/`margin_lateral`).
  - `gestor/modulos/galleries/galleries.widget.php` (`galleries_widget_resolver_link()`, `galleries_widget_resolver_publicacao_recente()` com cache, `galleries_widget_carregar_paginas()` em lote; `height`/`margin_lateral` em `galleries_widget_resolver_globais()`).
  - `gestor/modulos/galleries/resources/{pt-br,en}/pages/galleries-{adicionar,editar,clonar}/*.html` (6 páginas: inputs Altura/Margem Lateral).
  - `gestor/modulos/galleries/resources/{pt-br,en}/templates/galleries-{carousel,slider,grid,masonry}/*.html` (8 templates: âncora de link, margem lateral, altura, legenda do masonry).
  - `gestor/modulos/publisher-highlights/publisher-highlights.js` (margem horizontal das tags em `renderLinkedVars`).
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-horizontal-navbar/*.html` (bloco `<style>` com padding nos submenus).
- Observações de contrato real (divergência do pseudocódigo do intake): publicadores vêm da tabela `publisher` (coluna `name`) e páginas da tabela `paginas` (slug em `id`, rótulo em `nome`, URL em `caminho`), espelhando `menus_publisher_options`/`menus_widget_carregar_paginas`. Os tipos de link seguem DEC-037: `nenhum`, `pagina`, `link-custom`, `link-css-classes`, `publicador`.
- Testes manuais/runtime pendentes com o operador (após `🗃️ Projects - Update => Core`):
  - Adição de link de Página, Customizado (`_blank`) e Última Publicação em imagens da galeria; conferir HTML renderizado no preview.
  - Ajuste de altura para 450px e margem lateral para 20px; conferir reflexo instantâneo no preview.
  - Verificação visual dos submenus do menu horizontal e das legendas no masonry.
- Decisão registrada: [DEC-037](../decisions/DECISION-LOG.md#dec-037---2026-06-10---accepted)


## BATCH-025 - Autocomplete de Páginas em Galerias, Ajuste do Menu Horizontal e Preparação Final de Release (req-025)

- [x] Galerias: Autocomplete AJAX de Páginas:
  - [x] Dropdown estático simples de páginas na curadoria removido e substituído pelo autocomplete AJAX do Menus (`buildPageAutocompleteField`).
  - [x] Filtro de tipo de página (Página, Sistema, Ambos) e input de busca implementados com identificadores e classes isolados por ID de item curado (`name="gallery_page_search_type_${it.id}"` nos rádios; `data-id="${it.id}"` + classes locais no input/sugestões/hidden) para evitar colisões entre linhas.
  - [x] AJAX de busca (`pages-search`) e de carregamento inicial (`pages-fetch`) roteados no `galleries.php` (`galleries_ajax_pages_search`/`galleries_ajax_pages_fetch`, clonados do `menus.php`) consultando a tabela `paginas`.
  - [x] Hidratação automática na edição varrendo o array global `galleries_pages` (`resolvePageNameLocal`); fallback via `pages-fetch` (`fetchPageName`) quando o nome não está em memória.
- [x] Galerias: Inativação de Links no Widget:
  - [x] Tags `<a>` sem link configurado (`link_type === 'nenhum'`) recebem a classe `pointer-events-none cursor-default` (anexada a `link-css-classes` em `galleries_widget_resolver_link`), desabilitando o clique e mantendo o cursor padrão.
- [x] Menus: Alinhamento Horizontal do Submenu no Navbar:
  - [x] Regra `[data-title="menu-horizontal-navbar"] ul.absolute a` alterada de `display: block !important` para `display: flex !important; align-items: center; justify-content: space-between; gap: 0.25rem !important` (pt-br + en), alinhando a setinha SVG na mesma linha do rótulo.
- [x] Galerias: Proporção das Miniaturas no Painel:
  - [x] Estilo `.gallery-item-thumb` alterado de `64px×48px` para `width: 200px; height: 140px;` em `injectGalleryStyles`.
- [x] Documentação e CI/CD:
  - [x] Data de lançamento da v2.8.0 atualizada para `2026-06-10` em `CHANGELOG.md`, `CHANGELOG-PT-BR.md`, `README.md` e `README-PT-BR.md`.
  - [x] Otimizações do BATCH-023 (CSS inline + previewer unificado) e correções do BATCH-024/025 (links em galerias, busca de páginas, miniaturas, imagens sem link, submenus) documentadas nos changelogs e nos READMEs (incl. aba "Código do Widget" e Tailwind CSS CLI v4).
  - [x] Workflow GitHub Actions (`release-gestor.yml`) com o `body` do release atualizado descrevendo novidades e correções.

### Evidência de Validação (BATCH-025)

- Validação estática executada em 2026-06-10:
  - `node --check gestor/modulos/galleries/galleries.js` → `JS_OK` (sem erros de sintaxe).
  - `php -l gestor/modulos/galleries/galleries.php` → `No syntax errors detected`.
  - `php -l gestor/modulos/galleries/galleries.widget.php` → `No syntax errors detected`.
  - YAML de `release-gestor.yml`: alteração restrita ao `body:` (block scalar literal), indentação de 10 espaços preservada; validação por parser não executada (js-yaml/PyYAML indisponíveis no ambiente).
- Arquivos alterados:
  - `gestor/modulos/galleries/galleries.js` (miniatura 200×140; autocomplete AJAX de páginas por imagem: `buildPageAutocompleteField`, `runGalleryPageSearch`, `renderGalleryPageSuggestions`, `resolvePageNameLocal`, `fetchPageName` + listeners isolados por `data-id`).
  - `gestor/modulos/galleries/galleries.php` (endpoints `pages-search`/`pages-fetch` no switch AJAX + funções `galleries_ajax_pages_search`/`galleries_ajax_pages_fetch`).
  - `gestor/modulos/galleries/galleries.widget.php` (classe `pointer-events-none cursor-default` para `link_type === 'nenhum'`).
  - `gestor/modulos/menus/resources/{pt-br,en}/templates/menus-horizontal-navbar/*.html` (regra do submenu para flex/space-between).
  - `CHANGELOG.md`, `CHANGELOG-PT-BR.md`, `README.md`, `README-PT-BR.md` (data v2.8.0 e descritivos).
  - `.github/workflows/release-gestor.yml` (`body` do release).
- Testes manuais/runtime pendentes com o operador (após `🗃️ Projects - Update => Core`):
  - Verificar autocomplete AJAX de páginas ao escolher Tipo Página na imagem curada da galeria (Network mostra `pages-search`; filtro Página/Sistema/Ambos recarrega a busca; clique na sugestão preenche o nome amigável e salva slug/URL).
  - Verificar se imagens sem link não exibem cursor de mão/ponteiro no widget ou preview.
  - Verificar setas dos submenus nos níveis filhos do navbar horizontal (mesma linha do rótulo).
  - Confirmar que as miniaturas no painel aparecem maiores (200×140px).
- Decisão registrada: [DEC-038](../decisions/DECISION-LOG.md#dec-038---2026-06-10---accepted)


