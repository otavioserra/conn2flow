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
verified_at: c665e2e5
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

Reference generated from `gestor/bibliotecas/html-editor.php` by `c2f docs:extract` — 35 functions. Do not edit inside this block.

- `html_editor_publisher_controls(array $params = false)` — [line 26](../../../../../gestor/bibliotecas/html-editor.php#L26)
  Publisher controles.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['publisher']`: variáveis do publisher.
- `html_editor_tailwind_browser_version(): string` — [line 119](../../../../../gestor/bibliotecas/html-editor.php#L119)
  Versão do `@tailwindcss/browser` usada pelos editores (req-117).
- `html_editor_assets_registro(): array` — [line 134](../../../../../gestor/bibliotecas/html-editor.php#L134)
  Registro de assets externos, carregado sob demanda (req-156).
- `html_editor_tailwind_browser_contract(): string` — [line 154](../../../../../gestor/bibliotecas/html-editor.php#L154)
  Contrato de tema entregue ao Tailwind Browser (req-114, extraído em req-117).
  Returns: CSS do contrato, ou string vazia quando não houver arquivo.
- `html_editor_ia_tokens_tema_limite(): int` — [line 179](../../../../../gestor/bibliotecas/html-editor.php#L179)
  Orçamento de bytes do bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_namespaces(): array` — [line 202](../../../../../gestor/bibliotecas/html-editor.php#L202)
  Namespaces de tema do Tailwind v4 aceitos no bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_valor_util(string $valor): bool` — [line 231](../../../../../gestor/bibliotecas/html-editor.php#L231)
  Decide se o VALOR de uma declaração de tema cabe no prompt (req-127).
  Parameters:
  - `$valor`: valor da declaração, já sem o `;`.
- `html_editor_ia_tokens_tema_bloco(string $css): string` — [line 258](../../../../../gestor/bibliotecas/html-editor.php#L258)
  Recorta o conteúdo do primeiro bloco `@theme` / `@theme static` do contrato (req-127).
  Parameters:
  - `$css`: CSS do contrato, já sem comentários.
  Returns: conteúdo interno do bloco, ou string vazia quando não houver `@theme`.
- `html_editor_ia_tokens_tema_componentes(string $css): array` — [line 292](../../../../../gestor/bibliotecas/html-editor.php#L292)
  Lista os nomes de classe declarados nos blocos `@layer components` do contrato (req-127).
  Parameters:
  - `$css`: CSS do contrato, já sem comentários.
  Returns: lista de classes com o ponto, na ordem de aparição.
- `html_editor_ia_css_classes_resumir(string $css, int|null $limiteBytes = null): string` — [line 343](../../../../../gestor/bibliotecas/html-editor.php#L343)
  Resume um CSS qualquer na lista de nomes de classe que ele define (req-127).
  Parameters:
  - `$css`: CSS a resumir.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: lista de classes separadas por espaço, ou string vazia.
- `html_editor_ia_tokens_tema_compilar(string $css, int|null $limiteBytes = null): string` — [line 385](../../../../../gestor/bibliotecas/html-editor.php#L385)
  Monta o bloco `{{theme_tokens}}` a partir do CSS do contrato (req-127).
  Parameters:
  - `$css`: conteúdo do `browser-contract.css`.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: bloco CSS compacto, ou string vazia quando não houver nada aproveitável.
- `html_editor_ia_theme_tokens_marcador(): string` — [line 521](../../../../../gestor/bibliotecas/html-editor.php#L521)
  Marcador do bloco condicional de tokens de tema nos `.md` dos modos de IA (req-127).
- `html_editor_ia_modo_theme_tokens_aplicar(string $modo, string $theme_tokens): string` — [line 541](../../../../../gestor/bibliotecas/html-editor.php#L541)
  Aplica (ou remove) a seção de tokens de tema no texto do modo de IA (req-127).
  Parameters:
  - `$modo`: texto do modo de IA.
  - `$theme_tokens`: saída de `html_editor_ia_extrair_tokens_tema()`.
- `html_editor_ia_extrair_tokens_tema(string|null $caminhoContrato = null, int|null $limiteBytes = null): string` — [line 593](../../../../../gestor/bibliotecas/html-editor.php#L593)
  Extrator semântico leve de tokens de tema para o Assistente de IA (req-127).
  Parameters:
  - `$caminhoContrato`: caminho explícito do contrato; `null` resolve pelo projeto ativo.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Returns: bloco CSS compacto, ou string vazia quando não houver contrato aproveitável.
- `html_editor_css_precompiled_baseline(array $params = false): string` — [line 630](../../../../../gestor/bibliotecas/html-editor.php#L630)
  Baseline de CSS pré-compilado do editor (req-117).
  Parameters:
  - `$params['css_precompiled']`: pré-compilado do próprio recurso.
  - `$params['layout_id']`: layout da página, quando houver.
  - `$params['alvo']`: alvo do editor (`paginas`, `layouts`, `componentes`…).
  Returns: CSS concatenado na ordem da cascata.
- `html_editor_layout_css_autoral(string $layout_id): string` — [line 679](../../../../../gestor/bibliotecas/html-editor.php#L679)
  CSS AUTORAL do layout — a folha que o runtime serve e o editor não recebia (req-160).
  Parameters:
  - `$layout_id`: Layout da página.
- `html_editor_css_precompiled_concatenar(string $layout, string $recurso): string` — [line 712](../../../../../gestor/bibliotecas/html-editor.php#L712)
  Concatena as camadas do baseline na ordem da cascata do runtime (req-117).
  Parameters:
  - `$layout`: CSS pré-compilado do layout.
  - `$recurso`: CSS pré-compilado do próprio recurso.
- `html_editor_componente($params = false)` — [line 742](../../../../../gestor/bibliotecas/html-editor.php#L742)
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
- `html_editor_include($params = false)` — [line 1109](../../../../../gestor/bibliotecas/html-editor.php#L1109)
  Incluir o editor HTML visual.
  Parameters:
  - `$params['js_vars']`: variáveis JS a serem incluídas.
- `html_editor_ajax_interface(array $params = false)` — [line 1198](../../../../../gestor/bibliotecas/html-editor.php#L1198)
  AJAX Interface.
  Parameters:
  - `$params`: Parâmetros da função.
- `html_editor_ajax_layout_css()` — [line 1229](../../../../../gestor/bibliotecas/html-editor.php#L1229)
  CSS do layout escolhido no formulário (req-160).
- `html_editor_var_pattern()` — [line 1282](../../../../../gestor/bibliotecas/html-editor.php#L1282)
  ============================================================================ req-093 (BATCH-093) — Renderização de variáveis/widgets como caixas no Editor HTML Visual CLÁSSICO e no preview, espelhando a Editbar (Dashboard Site Toolbar).
- `html_editor_var_box($marker, $rendered, $tipo)` — [line 1288](../../../../../gestor/bibliotecas/html-editor.php#L1288)
- `html_editor_render_widget_signature($signature)` — [line 1295](../../../../../gestor/bibliotecas/html-editor.php#L1295)
- `html_editor_resolver_var($id)` — [line 1313](../../../../../gestor/bibliotecas/html-editor.php#L1313)
- `html_editor_boxes_widgets($html)` — [line 1339](../../../../../gestor/bibliotecas/html-editor.php#L1339)
- `html_editor_resolver_variaveis($html)` — [line 1355](../../../../../gestor/bibliotecas/html-editor.php#L1355)
- `html_editor_boxes_variaveis($html)` — [line 1366](../../../../../gestor/bibliotecas/html-editor.php#L1366)
- `html_editor_ajax_render_vars()` — [line 1411](../../../../../gestor/bibliotecas/html-editor.php#L1411)
  AJAX — Recebe o HTML do editor (CodeMirror) e devolve duas versões renderizadas (req-093): - `boxes`:  variáveis globais em caixas (`.c2f-var-box` + `data-c2f-marker`) + widgets renderizados entre comentários — para carregar no EDITOR VISUAL (átomos reversíveis no save). - `values`: variáveis globais resolvidas para valor puro (sem caixas) — para o PREVIEW iframe. As variáveis LOCAIS/de simulação (desconhecidas do backend) são preservadas para o frontend resolver.
- `html_editor_ajax_widget_render()` — [line 1435](../../../../../gestor/bibliotecas/html-editor.php#L1435)
  AJAX Widget Render.
- `html_editor_ajax_widget_types()` — [line 1475](../../../../../gestor/bibliotecas/html-editor.php#L1475)
  AJAX Widget Types.
- `html_editor_ajax_widgets_list()` — [line 1511](../../../../../gestor/bibliotecas/html-editor.php#L1511)
  AJAX Widgets List.
- `html_editor_widgets_buscar(array $params = array()): array` — [line 1593](../../../../../gestor/bibliotecas/html-editor.php#L1593)
  Busca paginada de itens de widget para o painel "+" do Live Editor (BATCH-081 §6).
  Parameters:
  - `$params`: { module?:string, busca?:string, pagina?:int, limite?:int }
  Returns: Estrutura de resposta pronta para `$_GESTOR['ajax-json']`.
- `html_editor_ajax_templates_load()` — [line 1666](../../../../../gestor/bibliotecas/html-editor.php#L1666)
  AJAX Templates.
- `html_editor_ajax_ia_requests()` — [line 1785](../../../../../gestor/bibliotecas/html-editor.php#L1785)
  AJAX IA Requests.

<!-- c2f:extract:end -->
