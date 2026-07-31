# BATCH-079 — Mapeamento no Pai de Widgets Múltiplos, Image Picker, Filtro/Agrupamento de Módulos e Backups Página×Layout

- **Intake**: [req-079](../human-requests/req-079.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-079](../validation/VALIDATION-CHECKLIST.md#batch-079)
- **Decisão**: [DEC-081](../decisions/DECISION-LOG.md#dec-081---2026-07-10---accepted)
- **Base**: refinamentos pós-teste do BATCH-077/BATCH-078 (Live Editor / Dashboard Site Toolbar).

## Escopo (8 itens)

O Live Editor reutiliza o motor visual admin (`html-editor.js`) escopado a `contentRoot` na
própria página hospedeira. O mapeamento cru×vivo (`mapTree`/`reconstructOriginal`) vive em
`dashboard.toolbar.js`. A toolbar (iframe) usa `dashboard.iframe-toolbar.js` + os 2 HTMLs.

### 1+7 — Marcadores preservados no modo edição → fronteira exata (abordagem final)
> **Rev. 2 (proposta do Chefe)**: a heurística de alinhamento DOM-vivo × cru (tail alignment /
> divisão de grupo) foi substituída por preservação explícita da fronteira.
- **`gestor_pagina_widgets` (gestor.php)**: no modo edição (`gestor_dashboard_toolbar_ativo()`),
  troca o bloco de widget por `<!-- widgets#SIG < -->render<!-- widgets#SIG > -->` (mantém os
  comentários, só troca o mockup pelo render). O DOM vivo do editor passa a ter a fronteira EXATA.
- **`mapTree` (dashboard.toolbar.js)**: casa o comentário de abertura vivo pela assinatura a partir
  de `li` (separa duplicados/consecutivos naturalmente), marca os nós entre open/close e remove os
  comentários. **Modo-pai**: se nenhum elemento/texto/outro-widget existe fora da faixa `[open,close]`
  do contêiner (e o pai não é a raiz editável) → marca o **pai** (`data-c2f-widget-parent="1"`);
  senão os elementos-raiz. `reconstructOriginal`: modo-pai substitui o **`innerHTML`** do pai pelo
  marcador cru. Removidos `collectWidgetGroup`/`structSig`/tail-align.

### 2 — Ícones Fomantic restantes → SVG inline
Substituição das `<i class="… icon">` remanescentes em `html-editor.js` (accordion do styler,
bgimage folder/trash e os botões `kind:'icon'` do styler) por SVG inline `stroke="currentColor"`
via mapa nome-Fomantic→path (`svgIcon()`), independentes da fonte Fomantic (ausente no site).

### 3 — Image picker funcional no modal do live editor
`ensureFallbackModal`: input `#element-src` ganha o botão `_html-editor-imagepick-btn` ao lado.
Novo picker **autônomo** (`openLiveImagePicker`) monta um overlay com iframe →
`raiz + admin-arquivos/?paginaIframe=sim`; a seleção (`window.parent` → mesma janela) preenche
`#element-src`. `raiz` chega pela opção `raiz` do construtor (passada por `dashboard.toolbar.js`).

### 4 — Modal de edição redimensionável
Caixa interna do modal fallback recebe `resize:both;overflow:auto;min-*` (arrasto pelo canto).

### 5+6 — Filtro autocomplete + agrupamento por categoria no menu de módulos
`dashboard_site_toolbar_menu` (dashboard.php): módulos agrupados por `modulos_grupos`
(cabeçalhos `.c2f-group-header` não-clicáveis, caixa-alta atenuada) + input
`#c2f-modules-filter`. `dashboard.iframe-toolbar.js`: listener `input` filtra `.c2f-menu-item`
(case-insensitive) e oculta `.c2f-group-header` sem itens visíveis.

### 7 — Widgets consecutivos/duplicados
Resolvido junto do item 1 pela abordagem marker-based (ver acima): cada widget tem seu par de
comentários no DOM vivo, então dois `menu-header-padrao` idênticos consecutivos num `<nav>` viram
widgets separados e independentes, sem fusão.

### 8 — Backups divididos Página × Layout
`dashboard.php`: `…_backups` retorna `page_backups` + `layout_backups` (via `layout_id` da
página); `…_backup_get` aceita `type=page|layout` (layout → renderiza body-inner do backup com
caixas + slot). `dashboard.toolbar.js`: painel em 2 colunas; clique em backup de página restaura
só `#c2f-page-content`; clique em backup de layout restaura o layout preservando o
`#c2f-page-content` vivo.

## Arquivos alterados
- `gestor/gestor.php` (`gestor_pagina_widgets`: mantém marcadores no modo edição — itens 1, 7).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (itens 1, 3-config, 7, 8).
- `gestor/modulos/dashboard/dashboard.php` (itens 6, 8).
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` (itens 5, 6).
- `gestor/assets/interface/html-editor.js` (itens 2, 3, 4).
- `gestor/bibliotecas/html-editor.php` (cache-bust da biblioteca).
- `tests/Unit/JS/dashboard.toolbar.test.js` (testes integrados vitest: widget no pai + duplicados separados + estático preservado).

## Validação
- Estática: `node --check` (2 JS) + `php -l` (dashboard.php) + JSON/HTML balanceado.
- Vitest (`npm run test`): Executa 20 checks em `dashboard.toolbar.test.js` cobrindo o widget de menu mapeado no pai, round-trip de reconstrução por innerHTML, e 2 widgets idênticos consecutivos separados.
- `composer test` sem regressão.
- Runtime (deploy `Update => Core` + homologação visual): **pendente com o operador**.
