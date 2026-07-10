# BATCH-075 — Dashboard Site Toolbar, Agendamento de Páginas e Extensões do Editor HTML

> [!IMPORTANT]
> **Atenção (Engenheiro de Execução - BATCH-075)**: Lote em andamento. Há outro lote paralelo pendente de início (BATCH-076). **Foque exclusivamente nos slices descritos neste arquivo e ignore o BATCH-076.**

- **Intake**: [req-075.md](../human-requests/req-075.md)
- **Status**: in-progress
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-075

## Contexto

Lote consolidado que evolui a navegação administrativa, o agendamento de publicações e a edição em runtime do ecossistema `conn2flow`. O intake reúne 6 metas grandes e parcialmente interdependentes (botão de acesso ao site, toolbar flutuante em iframe estilo WordPress admin bar, edição visual live, redirecionamento inteligente de edição avançada, agendamento/datas retroativas de páginas e atalhos de gestão de widgets no editor HTML).

Por ser um lote amplo, é implementado em **slices sequenciais pequenos**, um por meta (as metas 2+3+4 formam o épico da toolbar e ganham sub-slices próprios). Nenhum slice é aberto antes de estabilizar o anterior. O baseline (`sdd/00-baseline-architecture.md`) é preservado — as mudanças adicionam comportamento explícito, sem reescrever o legado.

## Slices

| # | Meta | Escopo | Arquivos-alvo | Validação mínima | Status |
| --- | --- | --- | --- | --- | --- |
| 1 | Meta 1 | Botão "Acessar Site" na `.menu-controls` do layout administrativo | `gestor/resources/{pt-br,en}/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.html` | Balanceamento de tags HTML + inspeção do marcador `@[[pagina#url-raiz]]@` | **done** (validação estática OK; runtime pendente) |
| 3a | Meta 2 | Dashboard Site Toolbar: bootstrap/injeção do iframe (`gestor_dashboard_toolbar()` em `gestor.php`), rota `dashboard-site-toolbar/`, controlador `dashboard_site_toolbar()`/`dashboard_site_toolbar_menu()`, páginas HTML do iframe (pt-br/en) + `dashboard.toolbar.js` (offset `margin-top`) | `gestor/gestor.php`, `gestor/modulos/dashboard/dashboard.php`, `gestor/modulos/dashboard/dashboard.json`, `gestor/modulos/dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/*`, `gestor/modulos/dashboard/dashboard.toolbar.js` | `php -l` + `composer test` + carga do iframe logado | **done** (php -l 2/2, node --check, composer test 76/76; runtime pendente) — *reordenada à frente da Meta 5 porque o operador scaffoldou a estrutura* |
| 2 | Meta 5 | Agendamento de páginas (janela publicar-a-partir/expirar-em) + datas retroativas (`data_criacao`/`data_modificacao`) + regra 404 fora da janela no roteador | `admin-paginas` e `publisher-pages` (CRUD `adicionar`/`editar`/`clonar` × pt-br/en), controladores dos módulos, roteamento público, migração Phinx (colunas de janela de publicação, se ausentes) | `php -l` + `composer test` + validação de janela 404 | **done** (migração `20260712100000`, 404 no roteador, 2 controladores, 12 forms; runtime pendente) |
| 3b | Meta 3 | Edição visual live via toolbar — **Approach B / Path Y** (editar o HTML ORIGINAL da tabela com `@[[var]]@`/widgets preservados; preview fiel à página live; sem reconciliação). Fundação: botão "Editar Página" → postMessage → overlay full-screen com o editor da página → "Concluir e recarregar". **Refino pendente**: fidelidade visual do preview do editor à página live. | `gestor/modulos/dashboard/dashboard.toolbar.js`, `dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/*`, `gestor/modulos/dashboard/dashboard.php`, `gestor/gestor.php`; (refino) `gestor/bibliotecas/html-editor.php`, `gestor/assets/interface/html-editor*.js` | `node --check` + `composer test` + edição/save/reload live | **done** (motor marker-based: render-com-caixas + editável in-place + reconstrução + save + reload) |
| 3c | Meta 4 | Atalho "Editar Página Avançado" com redirecionamento inteligente (`publisher-pages` se `publisher_id`, senão `admin-paginas`) via `target="_parent"` | Controlador/HTML da toolbar (dashboard), consulta à tabela `paginas` | `php -l` + teste dos dois ramos de redirecionamento | **done** (coberto na Meta 2 + `publisher_id`) |
| 4 | Meta 6 | Atalho para edição administrativa de widgets no `html-editor-floating-toolbar` (mapear tipo → URL de gestão, ex.: `menus/editar/?id={id}`) | `gestor/assets/interface/html-editor.js`, `gestor/assets/interface/html-editor-interface.js` (toolbar flutuante de seleção de widget) | `node --check` + abertura da URL do módulo do widget | **done** (`he-tb-widget-admin` no floating toolbar) |

## Notas de execução

- **Meta 1 (Slice 1)**: adicionar um `<a class="ui button">` (âncora estilizada como botão, semântica de navegação) irmão dos botões `#menu-dashboard3d-btn`/`#menu-close-btn` dentro do grupo `ui icon buttons`, apontando para `@[[pagina#url-raiz]]@` (raiz pública). Ícone `external alternate` (sair do admin para o site público). Tooltip localizado: "Acessar site" (pt-br) / "Visit site" (en). Preservar a notação `@[[...]]@` literal (cópia do banco — ver memória `feedback-conn2flow-variaveis-html-paginas`).
- **Meta 5**: verificar se `paginas` já tem colunas de janela de publicação; se não, criar migração Phinx idempotente (`hasColumn`) com timestamp > o maior existente. `data_criacao`/`data_modificacao` já existem como DATETIME — o CRUD passa a permitir edição retroativa validada. Formato amigável `dd/mm/yyyy hh:mm` no front, DATETIME no banco. Regra 404: a query pública de página ativa filtra `NOW()` dentro da janela quando configurada.
- **Toolbar (3a-3c)**: já existe um `gestor/modulos/dashboard/dashboard.toolbar.js` — investigar se é trabalho parcial anterior antes de recriar. Garantir `target="_parent"`/`target="_top"` em todas as `<a>` do iframe. Injeção só para `id_usuarios > 0` com permissão `gestor_acesso('editar','admin-paginas')`.
- **Meta 3 — estratégia FINAL (aval do Chefe — marker-based in-place)**:
  - **UX**: "Editar Página" revela uma barra de controles ABAIXO da toolbar (sem overlay que substitui a página); o `margin-top` da página cresce dinamicamente (30→74px, editbar aberta) e volta ao fechar. O menu de módulos e a editbar redimensionam o iframe via `c2f-toolbar:resize` (padrão do seletor de linguagens em `global.js`), com o iframe/página transparentes para a área expandida mostrar a página atrás. Distinção `height` (iframe, inclui dropdown que só sobrepõe) vs `offset` (persistente = 30 + editbar, que empurra).
  - **Motor (marker-based, determinístico)**: o editor mostra a página **RENDERIZADA** (totalmente funcional). Os conteúdos vindos de `@[[var]]@` e de widgets são envolvidos numa **CAIXA DE DESTAQUE protegida** (como a caixa de widget), guardando o marcador original num `data-*`. O usuário edita tudo, **menos** as caixas. No salvar, a reconstrução é **determinística**: cada caixa → seu marcador original (`@[[var]]@` / `<!-- widgets#... -->`); o resto → HTML editado. **Sem diff difuso** (as caixas delimitam explicitamente as regiões dinâmicas).
  - **Motivação normativa**: a página live é RENDERIZADA; salvar o rendered direto queimaria vars/widgets. As caixas com o marcador guardado permitem edição visual com fidelidade total **e** preservação do dinamismo.
  - **Backend**: endpoint AJAX `dashboard` `site-toolbar-save` (grava `html`/`css`/`css_compiled`/`html_extra_head` em `paginas` por id+idioma; permissão `gestor_acesso('editar','admin-paginas')`; `banco_update_campo` escapa) — **FEITO**. Pendente: endpoint `site-toolbar-render` (renderiza o `paginas.html` original com as caixas de destaque em cada `@[[var]]@`/widget, resolvendo os valores).
  - **Frontend (pendente)**: ao "Editar Página", buscar o render-com-caixas → trocar na região de conteúdo da página → habilitar edição in-place (reuso do `html-editor`); ao "Salvar", reconstruir o original (caixas→marcadores) → `site-toolbar-save` → reload. Ganchos já postados pela editbar: `c2f-toolbar:edit-start`/`edit-save`/`edit-cancel`.
- **Meta 6**: mapear o tipo de widget selecionado na toolbar flutuante (`html-editor-floating-toolbar`) para a URL administrativa do módulo correspondente, reaproveitando o `widgetsMap`/assinatura já mantidos em memória pelo editor (ver memórias BATCH-044 sobre assinatura de widget).

## Regras respeitadas

- Sem `git commit`/`git push` autônomo (memória de chefia).
- Sem edição manual de `version`/`checksum` nos `.json` (pipeline recalcula).
- Escopo congelado do `req-075.md` durante a execução (intake não é reescrito pelo executor).
