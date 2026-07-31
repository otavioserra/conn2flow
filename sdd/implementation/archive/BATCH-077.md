# BATCH-077 — Desacoplamento de Scripts do Iframe e Mapeamento Inteligente de Variáveis no Live Editor

- **Intake**: [req-077](../human-requests/req-077.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-077](../validation/VALIDATION-CHECKLIST.md#batch-077)
- **Decisão**: [DEC-079](../decisions/DECISION-LOG.md#dec-079---2026-07-10---accepted)
- **Base**: correções pós-teste visual do BATCH-075 (Dashboard Site Toolbar / edição in-place).

## Escopo (2 slices)

### Slice 1 — Desacoplamento do script da toolbar do iframe
Extrair a lógica JavaScript embutida na tag `<script>` do template `dashboard-site-toolbar.html`
(pt-br/en) para um arquivo estático próprio e injetá-lo dinamicamente.

- **Novo arquivo**: `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` (IIFE da toolbar:
  toggle de edição, resize do iframe, ponte de postMessage com a hospedeira).
- **HTML** (`dashboard-site-toolbar.html` pt-br/en): remoção da tag `<script>…</script>` final.
- **Injeção**: `gestor_pagina_javascript_incluir(Array('tipo'=>'iframe-toolbar','modulo_id'=>'dashboard'))`
  em `dashboard_site_toolbar()`. O hífen em `iframe-toolbar` casa a regex `/^[A-Za-z0-9-]+$/` do
  roteador `arquivo-estatico.php` (URL `dashboard/iframe-toolbar.js` → físico `dashboard.iframe-toolbar.js`).
- **Limpeza**: removido o órfão vazio `dashboard.iframe.toolbar.js` (0 bytes, sem referências).

### Slice 2 — Mapeamento inteligente de variáveis/atributos no Live Editor
Trocar a substituição **destrutiva** de `root.innerHTML` (que quebrava scripts/CSS/estado do site
vivo) por **preservação do DOM vivo + anotação por mapeamento** contra o HTML original.

- **Backend** (`dashboard_ajax_site_toolbar_render`): passa a devolver o HTML **CRU** original
  (`content_raw` = `paginas.html`; `layout_raw` = body-inner do layout com o slot `@[[pagina#corpo]]@`
  preenchido por `#c2f-raw-content`). Campos legados (`html`/`layout_html` com caixas) mantidos p/
  retrocompat (painel de backups).
- **Frontend** (`dashboard.toolbar.js`):
  - `startEdit`: guarda o cru num container oculto `#paginaHTMLAntesEdicao` e **NÃO** substitui o
    `root.innerHTML`. Chama `mapTree(root, backup)` (varredura paralela viva × original):
    - variável em **atributo** → marca a tag viva com `data-c2f-variable="ID_VAR_N"` + `varMap[ID]
      = {param, variable, valor}`; o valor resolvido continua visível no editor;
    - variável em **texto** → `annotateTextVars` envolve o trecho num `span.c2f-var-box` protegido;
    - **widget** → `mapTree` envolve a expansão viva num `div.c2f-widget-box` atômico (âncora por
      assinatura estrutural do próximo nó após o bloco de widget no cru).
  - `saveEdit`/`reconstructOriginal`: restaura `data-c2f-variable`→notação `@[[…]]@` (só quando o
    valor não foi alterado pelo usuário) e caixas→marcador original; **separa** `#c2f-page-content`
    (→ `paginas`) do resto do layout com `#c2f-page-content`→`@[[pagina#corpo]]@` (→ `layouts`),
    conforme orientação do Engenheiro Chefe (o body vivo mistura HTML de `layouts` e de `paginas`).

## Validação

- Estática: `node --check` (2 JS) OK; `php -l` (`dashboard.php`) OK; `<div>` 5/5 balanceado nos 2 HTMLs.
- Lógica do motor (happy-dom, arquivo real via message-bus): **16/16** — atributos (aceite),
  variável de texto e widget mapeados e reconstruídos; DOM vivo preservado.
- Runtime (deploy `Update => Core` + edição visual real com `html-editor.js`): **pendente com o operador**.
