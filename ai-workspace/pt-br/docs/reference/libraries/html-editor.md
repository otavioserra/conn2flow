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
verified_at: c665e2e5
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

Referência gerada a partir de `gestor/bibliotecas/html-editor.php` por `c2f docs:extract` — 35 funções. Não edite dentro deste bloco.

- `html_editor_publisher_controls(array $params = false)` — [linha 26](../../../../../gestor/bibliotecas/html-editor.php#L26)
  Publisher controles.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['publisher']`: variáveis do publisher.
- `html_editor_tailwind_browser_version(): string` — [linha 119](../../../../../gestor/bibliotecas/html-editor.php#L119)
  Versão do `@tailwindcss/browser` usada pelos editores (req-117).
- `html_editor_assets_registro(): array` — [linha 134](../../../../../gestor/bibliotecas/html-editor.php#L134)
  Registro de assets externos, carregado sob demanda (req-156).
- `html_editor_tailwind_browser_contract(): string` — [linha 154](../../../../../gestor/bibliotecas/html-editor.php#L154)
  Contrato de tema entregue ao Tailwind Browser (req-114, extraído em req-117).
  Retorno: CSS do contrato, ou string vazia quando não houver arquivo.
- `html_editor_ia_tokens_tema_limite(): int` — [linha 179](../../../../../gestor/bibliotecas/html-editor.php#L179)
  Orçamento de bytes do bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_namespaces(): array` — [linha 202](../../../../../gestor/bibliotecas/html-editor.php#L202)
  Namespaces de tema do Tailwind v4 aceitos no bloco `{{theme_tokens}}` (req-127).
- `html_editor_ia_tokens_tema_valor_util(string $valor): bool` — [linha 231](../../../../../gestor/bibliotecas/html-editor.php#L231)
  Decide se o VALOR de uma declaração de tema cabe no prompt (req-127).
  Parâmetros:
  - `$valor`: valor da declaração, já sem o `;`.
- `html_editor_ia_tokens_tema_bloco(string $css): string` — [linha 258](../../../../../gestor/bibliotecas/html-editor.php#L258)
  Recorta o conteúdo do primeiro bloco `@theme` / `@theme static` do contrato (req-127).
  Parâmetros:
  - `$css`: CSS do contrato, já sem comentários.
  Retorno: conteúdo interno do bloco, ou string vazia quando não houver `@theme`.
- `html_editor_ia_tokens_tema_componentes(string $css): array` — [linha 292](../../../../../gestor/bibliotecas/html-editor.php#L292)
  Lista os nomes de classe declarados nos blocos `@layer components` do contrato (req-127).
  Parâmetros:
  - `$css`: CSS do contrato, já sem comentários.
  Retorno: lista de classes com o ponto, na ordem de aparição.
- `html_editor_ia_css_classes_resumir(string $css, int|null $limiteBytes = null): string` — [linha 343](../../../../../gestor/bibliotecas/html-editor.php#L343)
  Resume um CSS qualquer na lista de nomes de classe que ele define (req-127).
  Parâmetros:
  - `$css`: CSS a resumir.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: lista de classes separadas por espaço, ou string vazia.
- `html_editor_ia_tokens_tema_compilar(string $css, int|null $limiteBytes = null): string` — [linha 385](../../../../../gestor/bibliotecas/html-editor.php#L385)
  Monta o bloco `{{theme_tokens}}` a partir do CSS do contrato (req-127).
  Parâmetros:
  - `$css`: conteúdo do `browser-contract.css`.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: bloco CSS compacto, ou string vazia quando não houver nada aproveitável.
- `html_editor_ia_theme_tokens_marcador(): string` — [linha 521](../../../../../gestor/bibliotecas/html-editor.php#L521)
  Marcador do bloco condicional de tokens de tema nos `.md` dos modos de IA (req-127).
- `html_editor_ia_modo_theme_tokens_aplicar(string $modo, string $theme_tokens): string` — [linha 541](../../../../../gestor/bibliotecas/html-editor.php#L541)
  Aplica (ou remove) a seção de tokens de tema no texto do modo de IA (req-127).
  Parâmetros:
  - `$modo`: texto do modo de IA.
  - `$theme_tokens`: saída de `html_editor_ia_extrair_tokens_tema()`.
- `html_editor_ia_extrair_tokens_tema(string|null $caminhoContrato = null, int|null $limiteBytes = null): string` — [linha 593](../../../../../gestor/bibliotecas/html-editor.php#L593)
  Extrator semântico leve de tokens de tema para o Assistente de IA (req-127).
  Parâmetros:
  - `$caminhoContrato`: caminho explícito do contrato; `null` resolve pelo projeto ativo.
  - `$limiteBytes`: orçamento total; `null` usa `html_editor_ia_tokens_tema_limite()`.
  Retorno: bloco CSS compacto, ou string vazia quando não houver contrato aproveitável.
- `html_editor_css_precompiled_baseline(array $params = false): string` — [linha 630](../../../../../gestor/bibliotecas/html-editor.php#L630)
  Baseline de CSS pré-compilado do editor (req-117).
  Parâmetros:
  - `$params['css_precompiled']`: pré-compilado do próprio recurso.
  - `$params['layout_id']`: layout da página, quando houver.
  - `$params['alvo']`: alvo do editor (`paginas`, `layouts`, `componentes`…).
  Retorno: CSS concatenado na ordem da cascata.
- `html_editor_layout_css_autoral(string $layout_id): string` — [linha 679](../../../../../gestor/bibliotecas/html-editor.php#L679)
  CSS AUTORAL do layout — a folha que o runtime serve e o editor não recebia (req-160).
  Parâmetros:
  - `$layout_id`: Layout da página.
- `html_editor_css_precompiled_concatenar(string $layout, string $recurso): string` — [linha 712](../../../../../gestor/bibliotecas/html-editor.php#L712)
  Concatena as camadas do baseline na ordem da cascata do runtime (req-117).
  Parâmetros:
  - `$layout`: CSS pré-compilado do layout.
  - `$recurso`: CSS pré-compilado do próprio recurso.
- `html_editor_componente($params = false)` — [linha 742](../../../../../gestor/bibliotecas/html-editor.php#L742)
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
- `html_editor_include($params = false)` — [linha 1109](../../../../../gestor/bibliotecas/html-editor.php#L1109)
  Incluir o editor HTML visual.
  Parâmetros:
  - `$params['js_vars']`: variáveis JS a serem incluídas.
- `html_editor_ajax_interface(array $params = false)` — [linha 1198](../../../../../gestor/bibliotecas/html-editor.php#L1198)
  AJAX Interface.
  Parâmetros:
  - `$params`: Parâmetros da função.
- `html_editor_ajax_layout_css()` — [linha 1229](../../../../../gestor/bibliotecas/html-editor.php#L1229)
  CSS do layout escolhido no formulário (req-160).
- `html_editor_var_pattern()` — [linha 1282](../../../../../gestor/bibliotecas/html-editor.php#L1282)
  ============================================================================ req-093 (BATCH-093) — Renderização de variáveis/widgets como caixas no Editor HTML Visual CLÁSSICO e no preview, espelhando a Editbar (Dashboard Site Toolbar).
- `html_editor_var_box($marker, $rendered, $tipo)` — [linha 1288](../../../../../gestor/bibliotecas/html-editor.php#L1288)
- `html_editor_render_widget_signature($signature)` — [linha 1295](../../../../../gestor/bibliotecas/html-editor.php#L1295)
- `html_editor_resolver_var($id)` — [linha 1313](../../../../../gestor/bibliotecas/html-editor.php#L1313)
- `html_editor_boxes_widgets($html)` — [linha 1339](../../../../../gestor/bibliotecas/html-editor.php#L1339)
- `html_editor_resolver_variaveis($html)` — [linha 1355](../../../../../gestor/bibliotecas/html-editor.php#L1355)
- `html_editor_boxes_variaveis($html)` — [linha 1366](../../../../../gestor/bibliotecas/html-editor.php#L1366)
- `html_editor_ajax_render_vars()` — [linha 1411](../../../../../gestor/bibliotecas/html-editor.php#L1411)
  AJAX — Recebe o HTML do editor (CodeMirror) e devolve duas versões renderizadas (req-093): - `boxes`:  variáveis globais em caixas (`.c2f-var-box` + `data-c2f-marker`) + widgets renderizados entre comentários — para carregar no EDITOR VISUAL (átomos reversíveis no save). - `values`: variáveis globais resolvidas para valor puro (sem caixas) — para o PREVIEW iframe. As variáveis LOCAIS/de simulação (desconhecidas do backend) são preservadas para o frontend resolver.
- `html_editor_ajax_widget_render()` — [linha 1435](../../../../../gestor/bibliotecas/html-editor.php#L1435)
  AJAX Widget Render.
- `html_editor_ajax_widget_types()` — [linha 1475](../../../../../gestor/bibliotecas/html-editor.php#L1475)
  AJAX Widget Types.
- `html_editor_ajax_widgets_list()` — [linha 1511](../../../../../gestor/bibliotecas/html-editor.php#L1511)
  AJAX Widgets List.
- `html_editor_widgets_buscar(array $params = array()): array` — [linha 1593](../../../../../gestor/bibliotecas/html-editor.php#L1593)
  Busca paginada de itens de widget para o painel "+" do Live Editor (BATCH-081 §6).
  Parâmetros:
  - `$params`: { module?:string, busca?:string, pagina?:int, limite?:int }
  Retorno: Estrutura de resposta pronta para `$_GESTOR['ajax-json']`.
- `html_editor_ajax_templates_load()` — [linha 1666](../../../../../gestor/bibliotecas/html-editor.php#L1666)
  AJAX Templates.
- `html_editor_ajax_ia_requests()` — [linha 1785](../../../../../gestor/bibliotecas/html-editor.php#L1785)
  AJAX IA Requests.

<!-- c2f:extract:end -->
