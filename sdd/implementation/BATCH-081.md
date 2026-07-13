# BATCH-081 — CodeMirror no Assistente IA, Correção no Save, Dropdowns da Toolbar e Painel "+" em Duas Colunas

- **Intake**: [req-081](../human-requests/req-081.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-081](../validation/VALIDATION-CHECKLIST.md#batch-081)
- **Decisão**: [DEC-083](../decisions/DECISION-LOG.md#dec-083---2026-07-11---accepted)
- **Base**: extensão do Live Editor (barra flutuante + editbar do Dashboard Site Toolbar) pós BATCH-080.

## Escopo

Seis metas de refinamento do Live Editor / Dashboard Site Toolbar. Todos os recursos novos do
motor (`html-editor.js`) permanecem **gated ao Live Editor** (`this.raiz`), sem alterar o editor
visual clássico do admin. As chamadas AJAX continuam nas rotas `site-toolbar-*` do `dashboard.php`,
que **reusam** funções das bibliotecas `ia.php`/`html-editor.php`.

### 1 — CodeMirror + resize no Assistente IA
- `buildAiPanel`/`openAiPanel` (`html-editor.js`): instancia CodeMirror (modo `markdown`, tema
  `tomorrow-night-bright`) sobre `#c2f-ai-instruction` e `#c2f-ai-mode-text` quando `window.CodeMirror`
  está presente (dedup por `.CodeMirror` irmão). `submitAi` e os handlers de troca de prompt/modo
  passam a ler/escrever via `getValue()`/`setValue()` com fallback ao `.value`.
- CSS `.c2f-he-live-box{resize:both;overflow:auto;}` (torna a caixa de Modelos e IA redimensionável
  pelo canto inferior direito).
- `dashboard.toolbar.js` carrega também `mode/markdown/markdown.js` no `ensureCodeMirror`.

### 2 — Deseleção determinística ao salvar
- `html-editor.js`: novo `deselectAll()` (sai do modo de inserção, limpa seleção/hover/contornos,
  fecha wrap-menu).
- `dashboard.toolbar.js` `saveEdit`: chama `c2fEditor.deselectAll()` **antes** de `getCleanHtml()`,
  evitando o erro "Erro ao salvar a página." com elemento selecionado/hover ativo.

### 3 — Dropdowns de Usuário e Página na toolbar
- `dashboard-site-toolbar.html` (pt-br/en): substitui os dois botões expostos por dois dropdowns.
  - **Usuário**: ícone + `@[[usuario#nome]]@`; menu com ícone maior, nome, Perfil
    (`@[[pagina#url-raiz]]@perfil-usuario/`) e Sair (`@[[pagina#url-raiz]]@signout/`).
  - **Página**: ícone + "Página"; menu com título (`#page_title#`), Editar Página (`c2f-toolbar-edit`
    preservado), Editar Avançado (`c2f-toolbar-edit-advanced` preservado), Criar Nova Página
    (`#new_page_url#`) e Clonar Página (`#clone_page_url#`).
- `dashboard.php` `dashboard_site_toolbar()`: computa `#page_title#`/`#new_page_url#`/`#clone_page_url#`
  (publisher-pages vs admin-paginas) e troca os placeholders.
- `dashboard.iframe-toolbar.js`: toggle por clique dos dropdowns (fecha ao clicar fora / ao abrir
  outro), integrando à altura do iframe (`pushHeight`).

### 4 — CRUD de prompts no Assistente IA
- `html-editor.js`: botões `ai-prompt-clear`/`ai-prompt-edit`/`ai-prompt-del`/`ai-prompt-new` no painel;
  handlers AJAX reusando as ações do backend; atualização do `<select>` de prompts após cada operação.
- `dashboard.php`: 3 rotas novas `site-toolbar-ia-prompt-new`/`-edit`/`-del` reusando
  `ia_ajax_prompt_novo`/`_edit`/`_del`.

### 5 — Código Customizado no painel "+"
- `dashboard.toolbar.js`: item "Código Customizado" no painel "+"; ao clicar chama
  `c2fEditor.openCustomCodePanel()`.
- `html-editor.js`: `openCustomCodePanel()` (modal `.c2f-he-live-box` com CodeMirror `htmlmixed`) e
  `insertCustomHtml(html)` (insere o bloco no DOM vivo relativo à seleção, estilo `c2f-add-el`).
  `#c2f-custom-panel` reconhecido em `isEditorOwned`.

### 6 — Painel "+" em duas colunas, grupos, paginação e autocomplete
- `dashboard.toolbar.js`: painel "+" reestruturado — coluna Elementos (esq.) × coluna Widgets (dir.);
  Widgets em subcolunas (`c2f-add-widget-groups` × `c2f-add-widget-item`); clique no grupo carrega os
  itens do grupo; input de busca autocomplete no topo (debounce → AJAX); botão "Carregar mais".
- `dashboard.php` `dashboard_ajax_site_toolbar_widgets_list`: paginação (`pagina`/`limite`) + busca
  (`busca`, `name LIKE`) via novo helper `html_editor_widgets_buscar()`, com busca cross-grupo quando
  `module` vazio. Resposta `{status, data:{items:[{id,nome,module}], tem_mais}}` (o editor clássico
  segue usando `html_editor_ajax_widgets_list`, inalterado).
- `dashboard.json`: opção `site_toolbar.widgets_por_pagina` (padrão 10).

## Arquivos alterados
- `gestor/assets/interface/html-editor.js` (CodeMirror IA, deselectAll, CRUD prompts, custom code, resize).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (deselect no save, custom code, painel "+" 2 colunas, markdown mode).
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` (dropdowns Usuário/Página).
- `gestor/modulos/dashboard/dashboard.php` (placeholders dos dropdowns + 3 rotas prompt CRUD + widgets paginado/busca).
- `gestor/modulos/dashboard/dashboard.json` (opção `site_toolbar.widgets_por_pagina` + bump versão).
- `gestor/modulos/dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/dashboard-site-toolbar.html` (dropdowns).
- `gestor/bibliotecas/html-editor.php` (`html_editor_widgets_buscar` + cache-bust).
- `tests/Unit/JS/html-editor.live.test.js` (novos casos BATCH-081).

## Validação
- Estática: `node --check` (html-editor.js/dashboard.toolbar.js/dashboard.iframe-toolbar.js),
  `php -l` (dashboard.php/ia.php/html-editor.php), JSON válido (dashboard.json).
- Vitest: `npm run test` (casos novos de CodeMirror IA, deselectAll, custom code, CRUD de prompts).
- `composer test` sem regressão.
- Runtime (deploy `Update => Core` + homologação visual): **pendente com o operador**.
