# BATCH-080 — Integração de Modelos de Sessão e Assistente IA no Live Editor

- **Intake**: [req-080](../human-requests/req-080.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-080](../validation/VALIDATION-CHECKLIST.md#batch-080)
- **Decisão**: [DEC-082](../decisions/DECISION-LOG.md#dec-082---2026-07-10---accepted)
- **Base**: extensão do Live Editor (barra flutuante `#html-editor-floating-toolbar`) pós BATCH-079.

## Escopo

Os novos recursos são **gated ao Live Editor** (`this.raiz` presente no construtor de
`HtmlEditorClass`, passado por `dashboard.toolbar.js`), para não alterar o editor visual do admin.
As chamadas AJAX vão para as rotas `site-toolbar-*` do `dashboard.php`, que **reusam** as funções
das bibliotecas `html-editor.php`/`ia.php` sem violar o painel clássico.

### 0 — Fix BATCH-079: widget de múltiplos elementos como bloco atômico
Após teste do Chefe, um `<nav>` com 2 widgets idênticos marcava cada `<a>` como selecionável
isolado. Correções no `html-editor.js`:
- `resolveEditable`: qualquer elemento com `data-c2f-widget-id` resolve para a **raiz do grupo**
  (`[data-c2f-widget-root]`) — o widget inteiro é UM átomo.
- `positionOverlay`/`updateSelectionUI` usam `elementRect` = **união** dos bounding boxes do grupo.
- CSS: outline em `[data-c2f-widget-id]` (todos os elementos do grupo) + label na raiz.

### 1 — Botão "Modelos" + painel vanilla (item 1/2)
`createToolbar` ganha `.he-tb-templates` (SVG grid, só no live). `openTemplatesPanel` monta
`#c2f-tpl-panel` (blindado): busca `#modelos-search-input` (debounce 300ms), select de framework,
relação inserir (Substituir/Antes/Depois), grid `#modelos-cards` + "Carregar mais". AJAX
`site-toolbar-templates-load` (paginado, com `busca`). `insertTemplate` insere o HTML no DOM vivo
na relação escolhida e injeta o CSS numa `<style id="c2f-templates-css">`.

### 2/3 — Botão "Assistente IA" + painel vanilla
`.he-tb-ai` (SVG faísca). `openAiPanel` monta `#c2f-ai-panel` com abas Prompt/Modo/Config:
selects de prompts/modos/conexões/modelos populados por `site-toolbar-ia-init` (`ia_editor_dados`),
textarea de instrução, texto do modo (buscado em `site-toolbar-ia-mode`), prompt salvo
(`site-toolbar-ia-prompt`). `submitAi` → `site-toolbar-ia-request` (reusa
`html_editor_ajax_ia_requests` + `ia_enviar_prompt`) com o `outerHTML` do elemento; `applyAiResult`
substitui o elemento pelo HTML gerado e injeta o CSS.

### 4 — Background image picker autônomo
`requestBackgroundImage`: no live (`this.raiz`) usa `openLiveImagePicker` com
`imagePickerTarget='background'`; `bindLiveImagePicker` roteia a seleção → `applyBackgroundImage`
(fundo) ou `#element-src` (modal). `.he-bgimage-clear` → `clearBackgroundImage` (já existente).

### 5 — Backend (reuso)
`dashboard.php`: 5 rotas `site-toolbar-templates-load`/`-ia-init`/`-ia-prompt`/`-ia-mode`/`-ia-request`
(todas com `gestor_acesso('editar','admin-paginas')` + `gestor_incluir_biblioteca`). `ia.php`: novo
`ia_editor_dados($alvo)` (prompts/modos/conexões/modelos como arrays JSON). `html-editor.php`:
`html_editor_ajax_templates_load` aceita `params[busca]` (retrocompatível).

## Arquivos alterados
- `gestor/assets/interface/html-editor.js` (botões, painéis, picker de fundo, atomicidade de grupo).
- `gestor/modulos/dashboard/dashboard.php` (5 rotas AJAX + handlers).
- `gestor/bibliotecas/ia.php` (`ia_editor_dados`).
- `gestor/bibliotecas/html-editor.php` (busca em templates + cache-bust 1.4.3).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (cache-bust `?v=c2f5`).
- `tests/Unit/JS/html-editor.live.test.js` (novo: botões, painéis, atomicidade, bg picker).

## Validação
- Estática: `node --check` (html-editor.js/dashboard.toolbar.js), `php -l` (dashboard/ia/html-editor).
- Vitest: `npm run test` **11/11** (novo `html-editor.live.test.js` **5/5**).
- `composer test` **76/76** sem regressão.
- Runtime (deploy `Update => Core` + homologação visual dos painéis/IA/picker): **pendente com o operador**.
