---
title: "html-editor.php library"
label: "HTML editor"
description: "The HTML and CSS editor of the page, layout, component, template, publication and widget CRUDs: code, visual, Tailwind preview, templates, variables, widgets and AI assistant."
section: reference
order: 70
sources:
  - gestor/bibliotecas/html-editor.php
  - gestor/assets/interface/html-editor-interface.js
  - gestor/modulos/admin-paginas/admin-paginas.php
verified_at: 914c7b10
---

# `html-editor.php` library

The editor that shows up in the forms of **pages, layouts, components, templates, publications, menus, galleries, forms and indexes** (about 30 calls in 12 modules). The server builds the screen and answers AJAX; the editing itself runs in the browser (`interface/html-editor.js` and `html-editor-interface.js`).

## Opening the editor: `html_editor_componente()`

```php
gestor_incluir_biblioteca('html-editor');
$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#html-editor#', html_editor_componente([
    'editar' => true,
    'modulo' => $modulo,                    // the module JSON
    'alvo' => 'paginas',                    // picks templates and AI prompts
    'css_precompiled' => $record['css_precompiled'],
    'layout_id' => $record['layout_id'],    // to preview with the layout cascade
]));
```

Parameters: `editar` / `adicionarEditar` (editing, or adding while already editing HTML and CSS), `modulo`, `alvo`, `alvos_modelos` (extra template targets), `publisher` and `publisherPage` (publisher variable controls, `html_editor_publisher_controls()`), `css_precompiled` and `layout_id`.

The screen has:
- HTML and CSS **code** in CodeMirror ([external assets](assets-externos.md));
- **visual** mode and **preview** in an `iframe` with Tailwind running in the browser (registry version, `html_editor_tailwind_browser_version()`), in desktop, tablet and phone sizes; the base CSS is the same cascade as the site: precompiled CSS of the layout and of the resource, plus the layout's authored CSS (`html_editor_css_precompiled_baseline()`, `html_editor_layout_css_autoral()`, `html_editor_css_precompiled_concatenar()`). Changing the layout in the form reloads that cascade (`html-editor-layout-css`);
- **templates** (per target) to start a page;
- `@[[…]]@` **variables** and **widgets** shown as boxes in the visual mode, with the real content rendered on demand;
- an **AI assistant** ([ia.php](ia.md)), a **SEO & Sharing** tab for pages, and the HTML/CSS **backup** history to restore.

What the browser compiles goes to `css_compiled` on save, with the provenance signature ([resources](../../concepts/resources.md)).

## AJAX: `html_editor_ajax_interface()`

Called by [interface.php](interface.md) when the library is among the module's. It first goes through `ia_ajax_interface()` and then dispatches by `ajax-opcao`:

| Option | Function | Does |
|---|---|---|
| `html-editor-templates-load` | `html_editor_ajax_templates_load()` | Lists the target's templates (`html-editor.templates.load.where` filter) |
| `html-editor-widget-types`, `html-editor-widgets-list` | `html_editor_ajax_widget_types()`, `html_editor_ajax_widgets_list()` | Widget types and instances to insert (`html_editor_widgets_buscar()`) |
| `html-editor-widget-render` | `html_editor_ajax_widget_render()` | Renders a widget for the visual mode |
| `html-editor-render-vars` | `html_editor_ajax_render_vars()` | Replaces the HTML's variables and widgets with visual boxes (`html_editor_boxes_variaveis()`, `html_editor_boxes_widgets()`, `html_editor_resolver_variaveis()`, `html_editor_resolver_var()`, `html_editor_var_box()`, `html_editor_var_pattern()`, `html_editor_render_widget_signature()`) |
| `html-editor-layout-css` | `html_editor_ajax_layout_css()` | CSS of the chosen layout, split into precompiled and authored |
| `html-editor-ia-requests` | `html_editor_ajax_ia_requests()` | Sends the request to the AI with HTML, CSS, mode and theme tokens |

## Theme tokens for the AI

So the AI uses the site's colors and fonts instead of inventing them, the editor summarizes the project's Tailwind contract into a `{{theme_tokens}}` block of about 1.5 KB (2 KB hard cap), prioritizing color, font and spacing: `html_editor_ia_tokens_tema_compilar()`, with `html_editor_ia_tokens_tema_limite()`, `html_editor_ia_tokens_tema_namespaces()`, `html_editor_ia_tokens_tema_valor_util()`, `html_editor_ia_tokens_tema_bloco()`, `html_editor_ia_tokens_tema_componentes()`, `html_editor_ia_css_classes_resumir()` and `html_editor_ia_extrair_tokens_tema()`. The AI mode receives the block in its own marker (`html_editor_ia_theme_tokens_marcador()`, `html_editor_ia_modo_theme_tokens_aplicar()`).

## Includes and filters

- `html_editor_include(['js_vars' => …])` includes the visual editor and its dependencies; the `html-editor.html_editor_include.projectJS` and `html-editor.html_editor_include.imagepickJS` filters ([hooks](../../concepts/hooks.md)) let the project replace the project JS and the image picker.
- `html_editor_assets_registro()` loads the [external assets](assets-externos.md) registry on demand.
- `html_editor_tailwind_browser_contract()` reads the project theme contract (`contents/tailwindcss/browser-contract.css`, which overrides the core one) handed to the in-browser Tailwind.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/html-editor.php` by `c2f docs:extract` — 37 functions. Do not edit inside this block.

- `html_editor_publisher_controls(array $params = false)` — [line 26](../../../../../gestor/bibliotecas/html-editor.php#L26)
  Publisher controles.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['publisher']`: variáveis do publisher.
- `html_editor_tailwind_browser_version(): string` — [line 121](../../../../../gestor/bibliotecas/html-editor.php#L121)
  Versão do `@tailwindcss/browser` usada pelos editores (req-117).
- `html_editor_assets_registro(): array` — [line 136](../../../../../gestor/bibliotecas/html-editor.php#L136)
  Registro de assets externos, carregado sob demanda (req-156).
- `html_editor_tailwind_browser_contract(): string` — [line 156](../../../../../gestor/bibliotecas/html-editor.php#L156)
  Contrato de tema entregue ao Tailwind Browser (req-114, extraído em req-117).
  Returns: CSS do contrato, ou string vazia quando não houver arquivo.
- `html_editor_ia_tokens_tema_limite(): int` — [line 181](../../../../../gestor/bibliotecas/html-editor.php#L181)
  Orçamento de bytes do bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_namespaces(): array` — [line 204](../../../../../gestor/bibliotecas/html-editor.php#L204)
  Namespaces de tema do Tailwind v4 aceitos no bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_valor_util(string $valor): bool` — [line 233](../../../../../gestor/bibliotecas/html-editor.php#L233)
  Decide se o VALOR de uma declaração de tema cabe no prompt (req-127).
  Parameters:
  - `$valor`: valor da declaração, já sem o `;`.
- `html_editor_ia_tokens_tema_bloco(string $css): string` — [line 260](../../../../../gestor/bibliotecas/html-editor.php#L260)
  Recorta o conteúdo do primeiro bloco `@theme` / `@theme static` do contrato (req-127).
  Parameters:
  - `$css`: CSS do contrato, já sem comentários.
  Returns: conteúdo interno do bloco, ou string vazia quando não houver `@theme`.
- `html_editor_ia_tokens_tema_componentes(string $css): array` — [line 294](../../../../../gestor/bibliotecas/html-editor.php#L294)
  Lista os nomes de classe declarados nos blocos `@layer components` do contrato (req-127).
  Parameters:
  - `$css`: CSS do contrato, já sem comentários.
  Returns: lista de classes com o ponto, na ordem de aparição.
- `html_editor_ia_css_classes_resumir(string $css, int|null $limiteBytes = null): string` — [line 345](../../../../../gestor/bibliotecas/html-editor.php#L345)
  Resume um CSS qualquer na lista de nomes de classe que ele define (req-127).
  Parameters:
  - `$css`: CSS a resumir.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: lista de classes separadas por espaço, ou string vazia.
- `html_editor_ia_tokens_tema_compilar(string $css, int|null $limiteBytes = null): string` — [line 387](../../../../../gestor/bibliotecas/html-editor.php#L387)
  Monta o bloco `{{theme_tokens}}` a partir do CSS do contrato (req-127).
  Parameters:
  - `$css`: conteúdo do `browser-contract.css`.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: bloco CSS compacto, ou string vazia quando não houver nada aproveitável.
- `html_editor_ia_theme_tokens_marcador(): string` — [line 523](../../../../../gestor/bibliotecas/html-editor.php#L523)
  Marcador do bloco condicional de tokens de tema nos `.md` dos modos de IA (req-127).
- `html_editor_ia_modo_theme_tokens_aplicar(string $modo, string $theme_tokens): string` — [line 543](../../../../../gestor/bibliotecas/html-editor.php#L543)
  Aplica (ou remove) a seção de tokens de tema no texto do modo de IA (req-127).
  Parameters:
  - `$modo`: texto do modo de IA.
  - `$theme_tokens`: saída de `html_editor_ia_extrair_tokens_tema()`.
- `html_editor_ia_extrair_tokens_tema(string|null $caminhoContrato = null, int|null $limiteBytes = null): string` — [line 595](../../../../../gestor/bibliotecas/html-editor.php#L595)
  Extrator semântico leve de tokens de tema para o Assistente de IA (req-127).
  Parameters:
  - `$caminhoContrato`: caminho explícito do contrato; `null` resolve pelo projeto ativo.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: bloco CSS compacto, ou string vazia quando não houver contrato aproveitável.
- `html_editor_css_precompiled_baseline(array $params = false): string` — [line 632](../../../../../gestor/bibliotecas/html-editor.php#L632)
  Baseline de CSS pré-compilado do editor (req-117).
  Parameters:
  - `$params['css_precompiled']`: pré-compilado do próprio recurso.
  - `$params['layout_id']`: layout da página, quando houver.
  - `$params['alvo']`: alvo do editor (`paginas`, `layouts`, `componentes`…).
  Returns: CSS concatenado na ordem da cascata.
- `html_editor_layout_css_autoral(string $layout_id): string` — [line 681](../../../../../gestor/bibliotecas/html-editor.php#L681)
  CSS AUTORAL do layout — a folha que o runtime serve e o editor não recebia (req-160).
  Parameters:
  - `$layout_id`: Layout da página.
- `html_editor_css_precompiled_concatenar(string $layout, string $recurso): string` — [line 714](../../../../../gestor/bibliotecas/html-editor.php#L714)
  Concatena as camadas do baseline na ordem da cascata do runtime (req-117).
  Parameters:
  - `$layout`: CSS pré-compilado do layout.
  - `$recurso`: CSS pré-compilado do próprio recurso.
- `html_editor_componente($params = false)` — [line 744](../../../../../gestor/bibliotecas/html-editor.php#L744)
  Componente Editor HTML.
  Parameters:
  - `$params['editar']`: caso seja edição.
  - `$params['adicionarEditar']`: caso seja adição mas queira modificar HTML e CSS.
  - `$params['modulo']`: dados do módulo atual.
  - `$params['alvo']`: alvo de modelos e ia.
  - `$params['alvos_modelos']`: alvos extras para modelos.
  - `$params['publisher']`: variáveis do publisher.
  - `$params['publisherPage']`: controles específicos do publisher page.
  - `$params['css_precompiled']`: CSS Tailwind offline do PRÓPRIO recurso.
  - `$params['layout_id']`: layout da página, quando houver (req-117). Serve para montar o
- `html_editor_include($params = false)` — [line 1120](../../../../../gestor/bibliotecas/html-editor.php#L1120)
  Incluir o editor HTML visual.
  Parameters:
  - `$params['js_vars']`: variáveis JS a serem incluídas.
- `html_editor_ajax_interface(array $params = false)` — [line 1209](../../../../../gestor/bibliotecas/html-editor.php#L1209)
  AJAX Interface.
  Parameters:
  - `$params`: Parâmetros da função.
- `html_editor_ajax_layout_css()` — [line 1240](../../../../../gestor/bibliotecas/html-editor.php#L1240)
  CSS do layout escolhido no formulário (req-160).
- `html_editor_var_pattern()` — [line 1293](../../../../../gestor/bibliotecas/html-editor.php#L1293)
  ============================================================================ req-093 (BATCH-093) — Renderização de variáveis/widgets como caixas no Editor HTML Visual CLÁSSICO e no preview, espelhando a Editbar (Dashboard Site Toolbar).
- `html_editor_var_box($marker, $rendered, $tipo)` — [line 1299](../../../../../gestor/bibliotecas/html-editor.php#L1299)
- `html_editor_render_widget_signature($signature)` — [line 1306](../../../../../gestor/bibliotecas/html-editor.php#L1306)
- `html_editor_widget_renderizar($sig): array` — [line 1329](../../../../../gestor/bibliotecas/html-editor.php#L1329)
  Renderiza um widget como a página publicada o entrega, para as prévias do editor.
  Returns: ['html' => string, 'css' => string] `css` são as tags <style> registradas na renderização.
- `html_editor_widget_js_modulos(): array` — [line 1350](../../../../../gestor/bibliotecas/html-editor.php#L1350)
  Módulos que têm controlador público de widget (`<modulo>.widget.js`), pelo cadastro de widgets. A prévia do editor carrega o controlador dos widgets presentes na página; com uma lista fixa no JavaScript, widget de projeto ou de plugin ficava sem comportamento na prévia.
  Returns: [modulo => true]
- `html_editor_resolver_var($id)` — [line 1371](../../../../../gestor/bibliotecas/html-editor.php#L1371)
- `html_editor_boxes_widgets($html)` — [line 1397](../../../../../gestor/bibliotecas/html-editor.php#L1397)
- `html_editor_resolver_variaveis($html)` — [line 1413](../../../../../gestor/bibliotecas/html-editor.php#L1413)
- `html_editor_boxes_variaveis($html)` — [line 1424](../../../../../gestor/bibliotecas/html-editor.php#L1424)
- `html_editor_ajax_render_vars()` — [line 1469](../../../../../gestor/bibliotecas/html-editor.php#L1469)
  AJAX — Recebe o HTML do editor (CodeMirror) e devolve duas versões renderizadas (req-093): - `boxes`:  variáveis globais em caixas (`.c2f-var-box` + `data-c2f-marker`) + widgets renderizados entre comentários — para carregar no EDITOR VISUAL (átomos reversíveis no save). - `values`: variáveis globais resolvidas para valor puro (sem caixas) — para o PREVIEW iframe. As variáveis LOCAIS/de simulação (desconhecidas do backend) são preservadas para o frontend resolver.
- `html_editor_ajax_widget_render()` — [line 1493](../../../../../gestor/bibliotecas/html-editor.php#L1493)
  AJAX Widget Render.
- `html_editor_ajax_widget_types()` — [line 1534](../../../../../gestor/bibliotecas/html-editor.php#L1534)
  AJAX Widget Types.
- `html_editor_ajax_widgets_list()` — [line 1570](../../../../../gestor/bibliotecas/html-editor.php#L1570)
  AJAX Widgets List.
- `html_editor_widgets_buscar(array $params = array()): array` — [line 1652](../../../../../gestor/bibliotecas/html-editor.php#L1652)
  Busca paginada de itens de widget para o painel "+" do Live Editor (BATCH-081 §6).
  Parameters:
  - `$params`: { module?:string, busca?:string, pagina?:int, limite?:int }
  Returns: Estrutura de resposta pronta para `$_GESTOR['ajax-json']`.
- `html_editor_ajax_templates_load()` — [line 1725](../../../../../gestor/bibliotecas/html-editor.php#L1725)
  AJAX Templates.
- `html_editor_ajax_ia_requests()` — [line 1844](../../../../../gestor/bibliotecas/html-editor.php#L1844)
  AJAX IA Requests.

<!-- c2f:extract:end -->

## Widgets and responsive previews

html_editor_widget_renderizar renders a declared widget and returns an array with html and css; html_editor_widget_js_modulos returns a module => true map for active widgets with a .widget.js file. Dashboard callers collect the CSS, head and scripts registered during rendering, then restore temporary context. The visual editor uses viewport-based preview height, calc(100vh - 220px), in Tailwind edit/view modals. Widgets require their template, record and theme styles in the isolated preview. See [Dashboard](../modules/dashboard.md) for stylesheet order.
