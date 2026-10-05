# BATCH-244 — req-235 (Estabilização Visual do Dashboard V3.0)

- **Branch:** `feat/req-235` (worktree `conn2flow-req235`, derivado de `main` @ 3e2ad2e9)
- **Status:** `in-review`

## Live Todo List
- [x] Worktree isolado `feat/req-235` a partir de `main`.
- [x] Logomarca da topbar blindada (`.admin-topbar-logo` + inline style) em pt-br/en; regra no `<head>` dos layouts.
- [x] Cabeçalho dos cards sem flexbox; camadas absolutas (cover wrapper e SVG) com inline style.
- [x] Alça de arrasto: `position:absolute !important`, `z-index:20`, `data-c2f-dica-pos="bottom right"`.
- [x] `<img>` de capa em `dashboard.php` com inline style (100% / object-fit: cover).
- [x] CSS: alturas M 9rem / G 12rem com `!important`; alça oculta em P; cover 100%.
- [x] Resize 12 colunas (`.dashboard-widget-resize-handle`, pointer events) e seletor `.dashboard-widget-switch-btn` — já presentes (req-233), verificados sem alteração.
- [x] `npm.cmd test` — 38 arquivos / 501 testes passaram.
- [x] `php cli/c2f.php assets:minify` — 1 gerado, 69 coerentes, 0 falhas.
- [x] PHPUnit (`*Req233Test`): executado com sucesso — `AdminTopbarReq233Test`, `DashboardCoversReq233Test` e `DashboardWidgetsReq233Test` 100% aprovados.
- [ ] Pipeline `project:update-all` e validação no navegador: pendentes de execução pelo operador.

## Arquivos alterados
- `gestor/resources/{pt-br,en}/components/admin-topbar-tailwind/admin-topbar-tailwind.html`
- `gestor/resources/{pt-br,en}/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.{html,css}`
- `gestor/modulos/dashboard/dashboard.php`
- `gestor/assets/minify-manifest.json`

## Observações
- Edições feitas preservando a codificação original (Latin-1) dos arquivos.
- Como só houve CSS/HTML de recurso, o bump de versão do `<id>.json` e o `css:rebuild` ficam para a execução do pipeline.
