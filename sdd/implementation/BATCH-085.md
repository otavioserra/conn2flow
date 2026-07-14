# BATCH-085 — Restauração de Backup Server-Side e Preview de Dispositivo com JS do Site (Live Editor, rodada 3)

- **Intake**: demandas diretas do Engenheiro Chefe durante a homologação (sem req-XXX formal).
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-085](../validation/VALIDATION-CHECKLIST.md#batch-085)
- **Decisão**: [DEC-086](../decisions/DECISION-LOG.md#dec-086---2026-07-13---accepted)
- **Base**: correções de homologação do Live Editor pós BATCH-082/083 (BATCH-084 é a demanda paralela de datas, fora deste escopo).

## Escopo

### 1 — Restauração de backup SERVER-SIDE (substitui a injeção client-side do BATCH-082 §3)

Sintoma: injetar o HTML do backup no cliente (`restorePageBackup`/`restoreLayoutBackup` + `mapTree`)
quebrava o DOM/scripts da página ao trocar de backup.

Novo fluxo (proposto pelo Chefe):
- **Frontend (`dashboard.toolbar.js`)**: `restoreBackup` deixa de injetar HTML; chama a rota
  `site-toolbar-backup-restore` (sinaliza o backup) e dá `window.location.reload()`. Removidas
  `restorePageBackup`/`restoreLayoutBackup`/`remapAfterRestore`.
- **Backend (`dashboard.php`)**: nova rota `site-toolbar-backup-restore` →
  `dashboard_ajax_site_toolbar_backup_restore()` valida permissão/propriedade e grava a variável de
  sessão `site-toolbar-backup-restore` (`{id, type, caminho}`) via `gestor_sessao_variavel`.
- **Roteador (`gestor.php` core)**: nova `gestor_site_toolbar_backup_aplicar(&$paginas)` chamada logo
  após o hook `roteador.paginas`. Para usuário logado em page load normal, lê a sessão; se o `caminho`
  casa com a página roteada, **consome** a sinalização (uma vez), busca o `backup_campos.valor` e
  substitui o HTML da página (`$paginas[0]['html']` + override `$_GESTOR['site-toolbar-backup-page-html']`
  para cobrir dev-env) ou do layout (`$_GESTOR['site-toolbar-backup-layout-html']`, aplicado onde o
  layout é montado). Seta o flag JS `gestor.siteToolbarBackupRestaurado`. O conteúdo do backup é então
  renderizado pelo **pipeline normal** do gestor (robusto, sem injeção no cliente).
- **Reentrada automática (`dashboard.iframe-toolbar.js`)**: no init, lê
  `window.parent.gestor.siteToolbarBackupRestaurado` (same-origin) e, se presente, chama `setEdit(true)`
  → o editbar reentra no modo de edição já com o backup carregado. Ao salvar, persiste o valor do backup.

### 2 — Preview de dispositivo com JS do site (corrige o BATCH-083 §2)

Sintoma: no preview Tablet/Mobile, interações JS do site (menu hambúrguer) não funcionavam — o
`srcdoc` do BATCH-083 §2 incluía só o CSS do `<head>`, sem os `<script>` do site.

- **`dashboard.toolbar.js` (`enterDevicePreview`)**: o iframe passa a carregar a PRÓPRIA página
  (`iframe.src = location + '?c2f-device-preview=1'`) — layout + CSS + JS reais → media queries, vw/vh e
  interações JS funcionam de verdade. Removidos `collectSiteCss`/`buildPreviewDoc` (srcdoc). Reflete a
  versão SALVA; as edições não salvas ficam preservadas no DOM real ao voltar ao Desktop.
- **`gestor.php` (`gestor_dashboard_toolbar_ativo`)**: o parâmetro `c2f-device-preview` desativa a
  injeção da toolbar/editor (evita a recursão do iframe da barra), mantendo o layout/CSS/JS reais.

## Arquivos alterados
- `gestor/gestor.php` (core: `gestor_site_toolbar_backup_aplicar` + chamada no roteador + overrides de html/layout; param `c2f-device-preview` em `gestor_dashboard_toolbar_ativo`).
- `gestor/modulos/dashboard/dashboard.php` (rota `site-toolbar-backup-restore`).
- `gestor/modulos/dashboard/dashboard.toolbar.js` (restoreBackup sinaliza+reload; preview device via URL real; remoção do restore client-side).
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` (reentrada automática em edição pós-restauração).
- `gestor/modulos/dashboard/dashboard.json` (versao `1.0.10`→`1.0.11`, cache-bust dos JS do módulo).
- `tests/Unit/JS/dashboard.toolbar.test.js` (remoção do teste/hook de `restorePageBackup`).

## Validação
- Estática: `php -l` (gestor.php/dashboard.php), `node --check` (toolbar.js/iframe-toolbar.js), `dashboard.json` JSON válido.
- Vitest: `npx vitest run` **19/19**; `composer test` **76/76** (287 assertions, 4 skipped) sem regressão.
- Runtime (deploy `Update => Core` + homologação): **pendente com o operador**.
  - §1: clicar num backup de página e de layout, confirmar reload + reentrada em edição já com o backup, e que o save persiste o backup. Testar isolamento multi-usuário (backup de outro dono → bloqueio).
  - §2: no preview Tablet/Mobile, confirmar que o menu hambúrguer e demais interações JS do site funcionam e o layout responsivo é fiel.
