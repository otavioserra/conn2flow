---
title: "Biblioteca html-editor.php"
label: "Editor HTML"
description: "O editor de HTML e CSS dos CRUDs de páginas, layouts, componentes, templates, publicações e widgets: código, visual, pré-visualização com Tailwind, modelos, variáveis, widgets e assistente de IA."
section: reference
order: 70
sources:
  - gestor/bibliotecas/html-editor.php
  - gestor/assets/interface/html-editor-interface.js
  - gestor/modulos/admin-paginas/admin-paginas.php
verified_at: 914c7b10
---

# Biblioteca `html-editor.php`

O editor que aparece nos formulários de **páginas, layouts, componentes, templates, publicações, menus, galerias, formulários e índices** (cerca de 30 chamadas em 12 módulos). O servidor monta a tela e responde ao AJAX; a edição em si roda no navegador (`interface/html-editor.js` e `html-editor-interface.js`).

## Abrir o editor: `html_editor_componente()`

```php
gestor_incluir_biblioteca('html-editor');
$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#html-editor#', html_editor_componente([
    'editar' => true,
    'modulo' => $modulo,                    // o JSON do módulo
    'alvo' => 'paginas',                    // escolhe modelos e prompts de IA
    'css_precompiled' => $registro['css_precompiled'],
    'layout_id' => $registro['layout_id'],  // para pré-visualizar com a cascata do layout
]));
```

Parâmetros: `editar` / `adicionarEditar` (edição, ou adição que já edita HTML e CSS), `modulo`, `alvo`, `alvos_modelos` (alvos extras de modelos), `publisher` e `publisherPage` (controles de variáveis do publicador, `html_editor_publisher_controls()`), `css_precompiled` e `layout_id`.

A tela tem:
- **código** HTML e CSS em CodeMirror ([assets externos](assets-externos.md));
- **visual** e **pré-visualização** num `iframe` com o Tailwind rodando no navegador (versão do registro, `html_editor_tailwind_browser_version()`), nos tamanhos desktop, tablet e celular; o CSS de base é a mesma cascata do site: pré-compilado do layout, do recurso e o CSS autoral do layout (`html_editor_css_precompiled_baseline()`, `html_editor_layout_css_autoral()`, `html_editor_css_precompiled_concatenar()`). Trocar o layout no formulário recarrega essa cascata (`html-editor-layout-css`);
- **modelos** (templates por alvo) para começar uma página;
- **variáveis** `@[[…]]@` e **widgets** mostrados como caixas no visual, com o conteúdo real renderizado sob demanda;
- **assistente de IA** ([ia.php](ia.md)), aba **SEO & Compartilhamento** nas páginas, e o histórico de **backups** do HTML/CSS para restaurar.

O que o navegador compila vai para `css_compiled` ao salvar, com a assinatura de procedência ([recursos](../../concepts/resources.md)).

## AJAX: `html_editor_ajax_interface()`

Chamada pela [interface.php](interface.md) quando a biblioteca está entre as do módulo. Primeiro passa por `ia_ajax_interface()` e depois despacha pela `ajax-opcao`:

| Opção | Função | Faz |
|---|---|---|
| `html-editor-templates-load` | `html_editor_ajax_templates_load()` | Lista os modelos do alvo (filtro `html-editor.templates.load.where`) |
| `html-editor-widget-types`, `html-editor-widgets-list` | `html_editor_ajax_widget_types()`, `html_editor_ajax_widgets_list()` | Tipos de widget e instâncias para inserir (`html_editor_widgets_buscar()`) |
| `html-editor-widget-render` | `html_editor_ajax_widget_render()` | Renderiza um widget para o visual |
| `html-editor-render-vars` | `html_editor_ajax_render_vars()` | Troca variáveis e widgets do HTML por caixas visuais (`html_editor_boxes_variaveis()`, `html_editor_boxes_widgets()`, `html_editor_resolver_variaveis()`, `html_editor_resolver_var()`, `html_editor_var_box()`, `html_editor_var_pattern()`, `html_editor_render_widget_signature()`) |
| `html-editor-layout-css` | `html_editor_ajax_layout_css()` | CSS do layout escolhido, separado em pré-compilado e autoral |
| `html-editor-ia-requests` | `html_editor_ajax_ia_requests()` | Envia o pedido à IA com HTML, CSS, modo e tokens do tema |

## Tokens do tema para a IA

Para a IA usar as cores e fontes do site em vez de inventar, o editor resume o contrato Tailwind do projeto (`contents/tailwindcss/browser-contract.css`, `html_editor_tailwind_browser_contract()`) num bloco `{{theme_tokens}}` de até ~1,5 KB (teto 2 KB), priorizando cor, fonte e espaçamento: `html_editor_ia_tokens_tema_compilar()`, com `html_editor_ia_tokens_tema_limite()`, `html_editor_ia_tokens_tema_namespaces()`, `html_editor_ia_tokens_tema_valor_util()`, `html_editor_ia_tokens_tema_bloco()`, `html_editor_ia_tokens_tema_componentes()`, `html_editor_ia_css_classes_resumir()` e `html_editor_ia_extrair_tokens_tema()`. O modo de IA recebe o bloco no marcador próprio (`html_editor_ia_theme_tokens_marcador()`, `html_editor_ia_modo_theme_tokens_aplicar()`).

## Inclusões e filtros

- `html_editor_include(['js_vars' => …])` inclui o editor visual e suas dependências; os filtros `html-editor.html_editor_include.projectJS` e `html-editor.html_editor_include.imagepickJS` ([hooks](../../concepts/hooks.md)) deixam o projeto trocar o JS do projeto e o seletor de imagens.
- `html_editor_assets_registro()` carrega sob demanda o registro de [assets externos](assets-externos.md).
- `html_editor_tailwind_browser_contract()` lê o contrato de tema do projeto (`contents/tailwindcss/browser-contract.css`, que prevalece sobre o do core) entregue ao Tailwind do navegador.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/html-editor.php` por `c2f docs:extract` — 37 funções. Não edite dentro deste bloco.

- `html_editor_publisher_controls(array $params = false)` — [linha 26](../../../../../gestor/bibliotecas/html-editor.php#L26)
  Publisher controles.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['publisher']`: variáveis do publisher.
- `html_editor_tailwind_browser_version(): string` — [linha 121](../../../../../gestor/bibliotecas/html-editor.php#L121)
  Versão do `@tailwindcss/browser` usada pelos editores (req-117).
- `html_editor_assets_registro(): array` — [linha 136](../../../../../gestor/bibliotecas/html-editor.php#L136)
  Registro de assets externos, carregado sob demanda (req-156).
- `html_editor_tailwind_browser_contract(): string` — [linha 156](../../../../../gestor/bibliotecas/html-editor.php#L156)
  Contrato de tema entregue ao Tailwind Browser (req-114, extraído em req-117).
  Retorno: CSS do contrato, ou string vazia quando não houver arquivo.
- `html_editor_ia_tokens_tema_limite(): int` — [linha 181](../../../../../gestor/bibliotecas/html-editor.php#L181)
  Orçamento de bytes do bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_namespaces(): array` — [linha 204](../../../../../gestor/bibliotecas/html-editor.php#L204)
  Namespaces de tema do Tailwind v4 aceitos no bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_valor_util(string $valor): bool` — [linha 233](../../../../../gestor/bibliotecas/html-editor.php#L233)
  Decide se o VALOR de uma declaração de tema cabe no prompt (req-127).
  Parâmetros:
  - `$valor`: valor da declaração, já sem o `;`.
- `html_editor_ia_tokens_tema_bloco(string $css): string` — [linha 260](../../../../../gestor/bibliotecas/html-editor.php#L260)
  Recorta o conteúdo do primeiro bloco `@theme` / `@theme static` do contrato (req-127).
  Parâmetros:
  - `$css`: CSS do contrato, já sem comentários.
  Retorno: conteúdo interno do bloco, ou string vazia quando não houver `@theme`.
- `html_editor_ia_tokens_tema_componentes(string $css): array` — [linha 294](../../../../../gestor/bibliotecas/html-editor.php#L294)
  Lista os nomes de classe declarados nos blocos `@layer components` do contrato (req-127).
  Parâmetros:
  - `$css`: CSS do contrato, já sem comentários.
  Retorno: lista de classes com o ponto, na ordem de aparição.
- `html_editor_ia_css_classes_resumir(string $css, int|null $limiteBytes = null): string` — [linha 345](../../../../../gestor/bibliotecas/html-editor.php#L345)
  Resume um CSS qualquer na lista de nomes de classe que ele define (req-127).
  Parâmetros:
  - `$css`: CSS a resumir.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: lista de classes separadas por espaço, ou string vazia.
- `html_editor_ia_tokens_tema_compilar(string $css, int|null $limiteBytes = null): string` — [linha 387](../../../../../gestor/bibliotecas/html-editor.php#L387)
  Monta o bloco `{{theme_tokens}}` a partir do CSS do contrato (req-127).
  Parâmetros:
  - `$css`: conteúdo do `browser-contract.css`.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: bloco CSS compacto, ou string vazia quando não houver nada aproveitável.
- `html_editor_ia_theme_tokens_marcador(): string` — [linha 523](../../../../../gestor/bibliotecas/html-editor.php#L523)
  Marcador do bloco condicional de tokens de tema nos `.md` dos modos de IA (req-127).
- `html_editor_ia_modo_theme_tokens_aplicar(string $modo, string $theme_tokens): string` — [linha 543](../../../../../gestor/bibliotecas/html-editor.php#L543)
  Aplica (ou remove) a seção de tokens de tema no texto do modo de IA (req-127).
  Parâmetros:
  - `$modo`: texto do modo de IA.
  - `$theme_tokens`: saída de `html_editor_ia_extrair_tokens_tema()`.
- `html_editor_ia_extrair_tokens_tema(string|null $caminhoContrato = null, int|null $limiteBytes = null): string` — [linha 595](../../../../../gestor/bibliotecas/html-editor.php#L595)
  Extrator semântico leve de tokens de tema para o Assistente de IA (req-127).
  Parâmetros:
  - `$caminhoContrato`: caminho explícito do contrato; `null` resolve pelo projeto ativo.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: bloco CSS compacto, ou string vazia quando não houver contrato aproveitável.
- `html_editor_css_precompiled_baseline(array $params = false): string` — [linha 632](../../../../../gestor/bibliotecas/html-editor.php#L632)
  Baseline de CSS pré-compilado do editor (req-117).
  Parâmetros:
  - `$params['css_precompiled']`: pré-compilado do próprio recurso.
  - `$params['layout_id']`: layout da página, quando houver.
  - `$params['alvo']`: alvo do editor (`paginas`, `layouts`, `componentes`…).
  Retorno: CSS concatenado na ordem da cascata.
- `html_editor_layout_css_autoral(string $layout_id): string` — [linha 681](../../../../../gestor/bibliotecas/html-editor.php#L681)
  CSS AUTORAL do layout — a folha que o runtime serve e o editor não recebia (req-160).
  Parâmetros:
  - `$layout_id`: Layout da página.
- `html_editor_css_precompiled_concatenar(string $layout, string $recurso): string` — [linha 714](../../../../../gestor/bibliotecas/html-editor.php#L714)
  Concatena as camadas do baseline na ordem da cascata do runtime (req-117).
  Parâmetros:
  - `$layout`: CSS pré-compilado do layout.
  - `$recurso`: CSS pré-compilado do próprio recurso.
- `html_editor_componente($params = false)` — [linha 744](../../../../../gestor/bibliotecas/html-editor.php#L744)
  Componente Editor HTML.
  Parâmetros:
  - `$params['editar']`: caso seja edição.
  - `$params['adicionarEditar']`: caso seja adição mas queira modificar HTML e CSS.
  - `$params['modulo']`: dados do módulo atual.
  - `$params['alvo']`: alvo de modelos e ia.
  - `$params['alvos_modelos']`: alvos extras para modelos.
  - `$params['publisher']`: variáveis do publisher.
  - `$params['publisherPage']`: controles específicos do publisher page.
  - `$params['css_precompiled']`: CSS Tailwind offline do PRÓPRIO recurso.
  - `$params['layout_id']`: layout da página, quando houver (req-117). Serve para montar o
- `html_editor_include($params = false)` — [linha 1120](../../../../../gestor/bibliotecas/html-editor.php#L1120)
  Incluir o editor HTML visual.
  Parâmetros:
  - `$params['js_vars']`: variáveis JS a serem incluídas.
- `html_editor_ajax_interface(array $params = false)` — [linha 1209](../../../../../gestor/bibliotecas/html-editor.php#L1209)
  AJAX Interface.
  Parâmetros:
  - `$params`: Parâmetros da função.
- `html_editor_ajax_layout_css()` — [linha 1240](../../../../../gestor/bibliotecas/html-editor.php#L1240)
  CSS do layout escolhido no formulário (req-160).
- `html_editor_var_pattern()` — [linha 1293](../../../../../gestor/bibliotecas/html-editor.php#L1293)
  ============================================================================ req-093 (BATCH-093) — Renderização de variáveis/widgets como caixas no Editor HTML Visual CLÁSSICO e no preview, espelhando a Editbar (Dashboard Site Toolbar).
- `html_editor_var_box($marker, $rendered, $tipo)` — [linha 1299](../../../../../gestor/bibliotecas/html-editor.php#L1299)
- `html_editor_render_widget_signature($signature)` — [linha 1306](../../../../../gestor/bibliotecas/html-editor.php#L1306)
- `html_editor_widget_renderizar($sig): array` — [linha 1329](../../../../../gestor/bibliotecas/html-editor.php#L1329)
  Renderiza um widget como a página publicada o entrega, para as prévias do editor.
  Retorno: ['html' => string, 'css' => string] `css` são as tags <style> registradas na renderização.
- `html_editor_widget_js_modulos(): array` — [linha 1350](../../../../../gestor/bibliotecas/html-editor.php#L1350)
  Módulos que têm controlador público de widget (`<modulo>.widget.js`), pelo cadastro de widgets. A prévia do editor carrega o controlador dos widgets presentes na página; com uma lista fixa no JavaScript, widget de projeto ou de plugin ficava sem comportamento na prévia.
  Retorno: [modulo => true]
- `html_editor_resolver_var($id)` — [linha 1371](../../../../../gestor/bibliotecas/html-editor.php#L1371)
- `html_editor_boxes_widgets($html)` — [linha 1397](../../../../../gestor/bibliotecas/html-editor.php#L1397)
- `html_editor_resolver_variaveis($html)` — [linha 1413](../../../../../gestor/bibliotecas/html-editor.php#L1413)
- `html_editor_boxes_variaveis($html)` — [linha 1424](../../../../../gestor/bibliotecas/html-editor.php#L1424)
- `html_editor_ajax_render_vars()` — [linha 1469](../../../../../gestor/bibliotecas/html-editor.php#L1469)
  AJAX — Recebe o HTML do editor (CodeMirror) e devolve duas versões renderizadas (req-093): - `boxes`:  variáveis globais em caixas (`.c2f-var-box` + `data-c2f-marker`) + widgets renderizados entre comentários — para carregar no EDITOR VISUAL (átomos reversíveis no save). - `values`: variáveis globais resolvidas para valor puro (sem caixas) — para o PREVIEW iframe. As variáveis LOCAIS/de simulação (desconhecidas do backend) são preservadas para o frontend resolver.
- `html_editor_ajax_widget_render()` — [linha 1493](../../../../../gestor/bibliotecas/html-editor.php#L1493)
  AJAX Widget Render.
- `html_editor_ajax_widget_types()` — [linha 1534](../../../../../gestor/bibliotecas/html-editor.php#L1534)
  AJAX Widget Types.
- `html_editor_ajax_widgets_list()` — [linha 1570](../../../../../gestor/bibliotecas/html-editor.php#L1570)
  AJAX Widgets List.
- `html_editor_widgets_buscar(array $params = array()): array` — [linha 1652](../../../../../gestor/bibliotecas/html-editor.php#L1652)
  Busca paginada de itens de widget para o painel "+" do Live Editor (BATCH-081 §6).
  Parâmetros:
  - `$params`: { module?:string, busca?:string, pagina?:int, limite?:int }
  Retorno: Estrutura de resposta pronta para `$_GESTOR['ajax-json']`.
- `html_editor_ajax_templates_load()` — [linha 1725](../../../../../gestor/bibliotecas/html-editor.php#L1725)
  AJAX Templates.
- `html_editor_ajax_ia_requests()` — [linha 1844](../../../../../gestor/bibliotecas/html-editor.php#L1844)
  AJAX IA Requests.

<!-- c2f:extract:end -->

## Widgets e prévias responsivas

html_editor_widget_renderizar renderiza um widget declarado e retorna um array com html e css; html_editor_widget_js_modulos devolve um mapa módulo => true para widgets ativos com arquivo .widget.js. O Dashboard coleta CSS, head e scripts registrados durante a renderização e restaura o contexto temporário. O editor visual usa altura baseada no viewport, calc(100vh - 220px), nos modais Tailwind de edição/visualização. Widgets precisam dos estilos de template, registro e tema na prévia isolada. Veja [Dashboard](../modules/dashboard.md) para a ordem das folhas.
