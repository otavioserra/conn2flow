# BATCH-083 — Correções de Homologação do Live Editor: Hover dos Dropdowns, Preview de Dispositivo Fiel e Normalização de Variáveis no Save

- **Intake**: [req-083](../human-requests/req-083.md) (formalizado pelo Chefe a partir das demandas diretas de homologação do BATCH-082).
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-083](../validation/VALIDATION-CHECKLIST.md#batch-083)
- **Decisão**: [DEC-085](../decisions/DECISION-LOG.md#dec-085---2026-07-13---accepted)
- **Base**: correções de homologação do Live Editor (Dashboard Site Toolbar) pós BATCH-082.

## Escopo

Três correções pontuais, todas no Dashboard Site Toolbar / Live Editor.

### 1 — Hover dos dropdowns da toolbar (menu de módulos, Página, Usuário)

Sintoma: após redimensionar a janela, ao passar o mouse num dropdown (`c2f-toolbar-menu`/`c2f-page-dropdown`/
`c2f-user-dropdown`) a caixa abria, mas sumia ao mover o mouse do trigger para a caixa.

Causa: as caixas vivem DENTRO do iframe de 30px; ao abrir, o iframe precisa crescer (postMessage
**assíncrono** ao host) para não recortar a caixa. Como a visibilidade era 100% CSS (`group-hover:block`),
ao mover o mouse para a caixa ainda recortada o ponteiro "caía" fora do iframe e o `:hover` se perdia
(corrida agravada pós-resize).

- **`dashboard.iframe-toolbar.js`**: hover-intent unificado (`registerHoverDropdown`) para os 3 dropdowns:
  a caixa é forçada visível por JS (classe `c2f-dropdown-force-open`) **antes** de medir a altura
  (offsetHeight confiável); o fechamento é **adiado** (220ms, cancelável) — o mouse alcança a caixa
  enquanto o iframe cresce e, ao entrar nela, o timer é cancelado. Removidos os handlers antigos
  (`menuOpen`/mouseenter-mouseleave por dropdown). Novo `window.resize` → reajusta a altura se há
  dropdown aberto.
- **`dashboard-site-toolbar.html` (pt-br/en)**: regra CSS `.c2f-dropdown-force-open{display:block!important;}`
  (vence o `hidden` do Tailwind durante o atraso de fechamento).

### 2 — Preview de dispositivo fiel via iframe (`screenPagina`)

Sintoma: os botões Desktop/Tablet/Mobile mudavam só a largura do `#c2f-layout-root` (elemento), então
media queries (`@media`), unidades `vw/vh` e `window.resize` do site NÃO respondiam (elas usam a viewport,
não o elemento).

- **`dashboard.toolbar.js` (`setEditScreen`)**: Tablet/Mobile renderizam o conteúdo dentro de um IFRAME
  (`#c2f-device-preview`) com a largura exata do device → a viewport do iframe passa a ser a largura do
  device e as media queries/vw/vh/resize respondem de verdade. O documento do iframe (`srcdoc`) reúne o
  CSS do site (`<link rel=stylesheet>`/`<style>` do `<head>` + `<base href>`), sem `<script>`. Desktop
  (100%) volta à edição in-place. Nos modos de device o editor é **desabilitado** (preview fiel, não
  editável — decisão do Chefe); alternar Tablet↔Mobile só troca a largura do iframe (sem recarregar).

### 3 — Normalização de variáveis/widgets digitados no save

Sintoma: ao digitar uma variável no editor (ex.: `[[pagina#url-raiz]]`), ela era salva literal, sem o
cerco `@…@` do banco, e não renderizava. Idem para widgets inline.

- **`dashboard.toolbar.js` (`reconstructOriginal`)**: passe final que normaliza toda `[[x]]` para
  `@[[x]]@` (`openText/closeText` → `open/close`). Idempotente: `@[[x]]@`→`@[[x]]@` (não duplica o cerco
  das que vieram das caixas via `data-c2f-marker`). Cobre variáveis e widgets inline digitados.

## Arquivos alterados
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` (hover-intent dos dropdowns + resize handler).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (preview device via iframe + normalização de variáveis no save).
- `gestor/modulos/dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/dashboard-site-toolbar.html` (CSS `.c2f-dropdown-force-open`).
- `gestor/modulos/dashboard/dashboard.json` (versao `1.0.9`→`1.0.10`, cache-bust dos JS do módulo).
- `tests/Unit/JS/dashboard.toolbar.test.js` (teste da normalização de variáveis/widgets).

## Validação
- Estática: `node --check` (dashboard.iframe-toolbar.js/dashboard.toolbar.js), `dashboard.json` JSON válido.
- Vitest: `npx vitest run` **20/20** (novo caso: normaliza `[[x]]`→`@[[x]]@`, idempotente).
- Runtime (deploy `Update => Core` + homologação visual): **pendente com o operador**.
  - §1: redimensionar a janela e confirmar que os 3 dropdowns permanecem abertos ao mover o mouse do trigger para a caixa.
  - §2: alternar Desktop/Tablet/Mobile e confirmar que o layout responsivo (media queries) muda de verdade; voltar ao Desktop e confirmar edição in-place.
  - §3: digitar `[[pagina#url-raiz]]` (e um widget inline) no editor, salvar e confirmar que o banco grava `@[[…]]@` e a página renderiza o valor.
