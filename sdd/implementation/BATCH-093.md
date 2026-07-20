# BATCH-093 — Renderização de Variáveis/Widgets no Editor HTML Clássico e Preview (igual à Editbar)

- **Intake**: [req-093](../human-requests/req-093.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-093](../validation/VALIDATION-CHECKLIST.md#batch-093)
- **Decisão**: [DEC-091](../decisions/DECISION-LOG.md#dec-091---2026-07-20---accepted)
- **Base**: estende a infra de caixas/mapeamento da Editbar (BATCH-077/082) ao Editor HTML Clássico.
- **Status**: `complete` — itens 1 e 2 do req atendidos (editor visual + preview + publisher-pages via infra genérica). Refinamento opcional das variáveis LOCAIS em caixas no editor visual documentado como melhoria futura.

## Decisões de escopo (confirmadas com o Chefe)
- **Reuso**: DUPLICAR a lógica no editor clássico (Editbar intacta), não extrair para compartilhado.
- **Escopo**: itens 1 e 2 do req; executado de forma tecnicamente faseada (o item 2 depende da infra do item 1).

## Arquitetura descoberta (por que não é trivial)
O Editor Clássico difere da Editbar: usa iframes `srcdoc` (`#iframe-preview` = editor visual com o
motor dentro; `#iframe-visualizacao-pagina` = preview). O motor `html-editor.js` roda DENTRO do iframe
e expõe `htmlEditorGetCleanHtml` (já reverte widgets → comentários e trata `.c2f-dyn-box` como átomo),
mas **não** possui `mapTree`/`reconstructOriginal` de variáveis. A resolução de variáveis é **por alvo**
em `html-editor-modules.js` (`publisherVariablesOrSimulation`/`OrValues`: forms/menus/galleries/
publisher/highlights/index) — só LOCAIS; as GLOBAIS (`@[[pagina#url-raiz]]@`) apareciam cruas.

**Insight que simplifica**: como o BACKEND cria as caixas já com o marcador embutido
(`data-c2f-marker`), o frontend NÃO precisa do `mapTree` inteiro — basta reverter as caixas no save.

## Implementado nesta rodada (Fase base — editor visual do admin-paginas)

### Backend (`gestor/bibliotecas/html-editor.php`)
Funções duplicadas/adaptadas da Editbar (regex TOLERANTE a `@?[[...]]@?`, pois o CodeMirror usa
`[[var]]` sem cerco; o `data-c2f-marker` guarda o texto EXATO de entrada → round-trip perfeito):
- `html_editor_var_pattern`, `html_editor_var_box`, `html_editor_render_widget_signature`,
  `html_editor_resolver_var` (globais: `pagina#url-raiz`/`url-full-http`/`gestor#versao`/`pagina#menu`
  + `gestor_variaveis_globais`; retorna `null` p/ locais → preservadas),
  `html_editor_boxes_widgets`, `html_editor_resolver_variaveis`, `html_editor_boxes_variaveis`.
- Rota AJAX `html-editor-render-vars` → `html_editor_ajax_render_vars()`: devolve `{boxes, values}`
  (`boxes` = globais em `.c2f-var-box` + widgets renderizados p/ o editor visual; `values` = globais
  resolvidas p/ o preview). Alcançável via `html_editor_ajax_interface` (interface.php/interface-v2.php).

### Frontend (`gestor/assets/interface/html-editor-interface.js`)
- `htmlEditorRenderVars(html, cb)`: POST à rota; em falha `cb(null)` → usa o HTML cru (fluxo antigo).
- `htmlEditorReconstructVars(html)`: reverte `.c2f-dyn-box[data-c2f-marker]` ao marcador original
  (`[[var]]`) — early-return sem caixas (no-op seguro). Exposta em `window`.
- `editorHtmlVisual`: para páginas/componentes, agora renderiza as caixas (async) antes de abrir o
  editor visual (layouts seguem síncronos). Save (`previsualizarConfirmar`): aplica
  `htmlEditorReconstructVars(bodyContent)` antes de gravar no CodeMirror → o banco recebe as variáveis,
  não os valores.

## Implementado — Fase 2 (preview + publisher-pages)

### Preview iframe (`previewHtml`)
- `htmlEditorRenderVars` generalizado: o callback recebe `{boxes, values}` (o editor visual usa `boxes`,
  o preview usa `values`). `previewHtml` ficou assíncrono: aplica a simulação LOCAL (como antes) e então
  resolve as GLOBAIS para valor via `values` (fallback ao HTML local em falha — o preview nunca quebra).

### publisher-pages (item 2) — coberto pela infra genérica
- **Não** foi preciso tocar `publisher-pages.php`: a rota `html-editor-render-vars` resolve as GLOBAIS
  para QUALQUER módulo (roteada por `html_editor_ajax_interface`), preservando as LOCAIS (`resolver_var`
  retorna `null` → literal). Assim:
  - **Preview**: `publisherVariablesOrSimulation`/`OrValues` resolvem as LOCAIS (simulação replicada) e o
    backend resolve as GLOBAIS → ambas renderizadas em harmonia.
  - **Editor visual**: as GLOBAIS viram caixas (reversíveis); as LOCAIS permanecem como marcadores
    (`[[item#X]]`) e são preservadas no save. **Decisão**: no editor visual as locais NÃO recebem a
    simulação replicada do preview (que multiplica o item N vezes) — isso quebraria a reversão 1:1 e
    duplicaria o bloco `item` no banco. O usuário edita o TEMPLATE (um bloco), não a replicação.

## Pendente / refinamento futuro (opcional)
- **Variáveis LOCAIS em caixas protegidas no editor visual** (anotação/proteção 1:1, sem replicar) —
  melhoria de UX; o comportamento atual (locais como marcadores, preservadas no save) já é seguro.
- **Widgets no clássico**: validar render + reversão de widgets em runtime (backend já os renderiza).
- Homologação runtime no admin (deploy `Update => Core`).

## Validação (Fase base)
- `php -l` (html-editor.php) OK; smoke PHP das funções de caixas (globais→caixa c/ marcador; atributo→valor;
  local preservada; values resolve) → **OK**.
- `node --check` (html-editor-interface.js) OK.
- `npx vitest run tests/Unit/JS/html-editor-vars.test.js` → **4/4** (reversão: round-trip [[var]], no-op
  sem caixas, cerco @[[var]]@ exato, múltiplas caixas) extraindo a função REAL do arquivo.
- Suíte completa: `npx vitest run` **29/29**, `composer test` **110/110** (287→474 assertions; testes de
  outros agentes em paralelo) sem regressão.
- Cache-bust: `biblioteca-html-editor` `1.4.10`→`1.4.11`.
