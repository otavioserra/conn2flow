# BATCH-082 — Carregamento de Widgets, Seleção de Modelos, Restauração de Backups e Isolamento Multi-usuário no Live Editor

- **Intake**: [req-082](../human-requests/req-082.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-082](../validation/VALIDATION-CHECKLIST.md#batch-082)
- **Decisão**: [DEC-084](../decisions/DECISION-LOG.md#dec-084---2026-07-13---accepted)
- **Base**: correções de homologação do Live Editor (barra flutuante + editbar do Dashboard Site Toolbar) pós BATCH-080/081.

## Escopo

Quatro correções pontuais no Live Editor. Todo recurso novo do motor (`html-editor.js`)
permanece **gated ao Live Editor** (`this.raiz`), sem alterar o editor visual clássico do admin.
As chamadas AJAX seguem nas rotas `site-toolbar-*` do `dashboard.php`, que **reusam** funções da
biblioteca `html-editor.php`.

### 1 — Carregamento de widgets dinâmicos (postMessage → AJAX → postMessage)

Sintoma: ao inserir um widget pelo painel "+", o placeholder `Carregando widget…` fica preso porque
o `postMessage` `c2f-he:widget-render` do motor não tinha receptor no Live Editor.

- **Backend (`dashboard.php`)**: nova rota `site-toolbar-widget-render` → `dashboard_ajax_site_toolbar_widget_render()`
  (checa `gestor_acesso('editar','admin-paginas')`, inclui a lib `html-editor` e delega a
  `html_editor_ajax_widget_render()`, que já existe e resolve a assinatura `modulo->func({...})`).
- **Frontend (`dashboard.toolbar.js`)**: listener dedicado de `message` que intercepta a **string JSON**
  `c2f-he:widget-render` (o motor posta `JSON.stringify(...)` para `window.parent`, que na página
  hospedeira top é a própria `window`). Handler `handleEngineWidgetRender(signature, wrapperId)`:
  faz POST na rota, e ao receber o HTML posta de volta `c2f-he:widget-rendered` (string JSON) para a
  própria `window` → o motor chama `applyWidgetRender`.

### 2 — Seleção automática do modelo inserido (`insertTemplate`)

- **Motor (`html-editor.js`)**: `insertTemplate` passa a rastrear o **primeiro nó de elemento**
  (`nodeType === 1`) inserido nas três relações (`replace`/`before`/`after`) e, ao final, o seleciona
  (`this.selectElement(firstInserted)`). Antes só `replace` selecionava.

### 3 — Restauração de backups com re-mapeamento (marker-based)

Sintoma: ao restaurar backup de página/layout os widgets vinham como `.c2f-widget-box` (caixa morta,
não interativa) e as anotações (`data-c2f-widget-id`/`data-c2f-variable`) se perdiam.

- **Backend (`dashboard.php`)**:
  - `dashboard_site_toolbar_boxes_widgets`: widgets passam a ser embrulhados nos **marcadores de
    comentário** `<!-- widgets#SIG < -->{render}<!-- widgets#SIG > -->` (formato vivo que o `mapTree`
    entende), em vez da div `.c2f-widget-box`.
  - Novo helper `dashboard_site_toolbar_resolver_variaveis($html)`: resolve `@[[var]]@` para o **valor
    puro** (texto e atributo), espelhando a página viva — sem `.c2f-var-box` (o `mapTree` re-anota).
  - `dashboard_ajax_site_toolbar_backup_get`: retorna também `raw` (o HTML cru salvo, com marcadores
    intactos) além do `html` renderizado. Para layout, `raw`/`html` alinham o slot vazio
    `#c2f-page-content`.
- **Frontend (`dashboard.toolbar.js`)**: `restorePageBackup(html, raw)` / `restoreLayoutBackup(html, raw)`
  chamam `deselectAll()`, injetam o `html` no DOM vivo e o `raw` no container oculto de mapeamento,
  re-rodam `mapTree()` para re-anotar widgets/variáveis e chamam `restoreChrome()`. O `varMap` **não**
  é zerado (preserva o mapeamento cruzado layout×conteúdo — ver DEC-084 §3).

### 4 — Isolamento multi-usuário na Editbar (via hook core↔projeto)

A regra de isolamento é específica de projeto e NÃO vive no core. Segue o sistema de hooks do
Conn2Flow (`hook_apply_filters`).

- **Core (`dashboard.php`)**: helper `dashboard_site_toolbar_verificar_permissao_pagina($pagina)` só
  EXPÕE o filtro `dashboard` / `site-toolbar.permissao-pagina` (default `true`, guard
  `function_exists('hook_apply_filters')`) — o core não conhece `multiusuario`. Aplicado em
  `site-toolbar-render`, `site-toolbar-save`, `site-toolbar-backups` (selecionam `id_usuarios`) e
  `site-toolbar-backup-get` (recebe `page_id` do contexto e valida a página dona).
- **Projeto (`conn2flow-site`)**: handler `multiusuario_dashboard_site_toolbar_permissao_pagina_filter`
  em `multiusuario.hooks.php`, registrado no `hooks.json` (`controllers.dashboard` +
  `filters.dashboard["site-toolbar.permissao-pagina"]`), exige acesso completo
  (`acesso-completo-a-paginas`/`admin-paginas` ou `acesso-completo-a-paginas-publisher`/`publisher-pages`,
  conforme `publisher_id`) OU que o `id_usuarios` da página seja o do usuário logado.

## Arquivos alterados
### Core (`conn2flow`)
- `gestor/modulos/dashboard/dashboard.php` (rota widget-render + helper que emite o filtro de permissão + backup_get `raw` + widgets marker-based + resolver variáveis).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (listener widget-render, restore com raw+remap, page_id no backup_get, cache-bust).
- `gestor/assets/interface/html-editor.js` (seleção automática em `insertTemplate`).
- `gestor/bibliotecas/html-editor.php` (cache-bust da biblioteca).
- `tests/Unit/JS/html-editor.live.test.js` (seleção de modelo before/after).
- `tests/Unit/JS/dashboard.toolbar.test.js` (widget-render → widget-rendered; restore re-mapeia).

### Projeto (`conn2flow-site`, repo privado)
- `gestor/project/hooks/controllers/multiusuario.hooks.php` (handler do filtro `dashboard`/`site-toolbar.permissao-pagina`).
- `gestor/project/hooks/hooks.json` (registro do controller `dashboard` + filtro).

## Validação
- Estática: `node --check` (html-editor.js/dashboard.toolbar.js), `php -l` (dashboard.php/html-editor.php).
- Vitest: `npm run test` (seleção de modelo; ponte widget-render/rendered; re-mapeamento no restore).
- `composer test` sem regressão.
- Runtime (deploy `Update => Core` + homologação visual + teste de bloqueio multi-usuário): **pendente com o operador**.
