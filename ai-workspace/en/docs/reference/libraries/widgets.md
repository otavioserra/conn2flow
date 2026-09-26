---
title: "widgets.php library"
label: "Widgets"
description: "widgets_get(): how a widgets# marker on a page becomes the HTML returned by a module function, and how the same widget answers over AJAX."
section: reference
order: 110
sources:
  - gestor/bibliotecas/widgets.php
  - gestor/gestor.php
verified_at: a5ae8605
---

# `widgets.php` library

A **widget** is a dynamic snippet that any page, layout or template can embed through a marker: a menu, a publication index, a gallery, a form. The library has one function, `widgets_get()`, which resolves the marker and calls the PHP function of the module that owns the widget. The router loads it on its own when the page has a marker.

## Markers

The current form is a pair of comments with a *mockup* in between:

```html
<!-- widgets#menus->render({"id":"main"}) < -->
  <nav>… static HTML, only to preview the page in the editor …</nav>
<!-- widgets#menus->render({"id":"main"}) > -->
```

The old form, without a mockup, is still accepted: `@[[widgets#menus->render({"id":"main"})]]@`.

The signature is `module->function(JSON)`: the module has only letters, digits, `_` and `-`; the JSON is optional and, if invalid, becomes `[]`.

## What `widgets_get(['id' => …, 'html' => …])` does

1. Includes `gestor/modulos/<module>/<module>.widget.php`, if it exists.
2. Resolves the function: first `<module_with_underscores>_<function>` (`publisher-highlights->render` → `publisher_highlights_render`); if that does not exist, the bare `<function>`.
3. Calls the function with the JSON parameter array. The mockup arrives in `$params['html']`.
4. Registers the signature in `$_GESTOR['widgetsToAjax']`, which reaches JavaScript as `gestor.widgetsToAjax`.
5. Returns the HTML returned by the function, or `''`.

On the final page, the router replaces the whole block (comments and mockup) with the returned HTML. For editors logged into the Live Editor, the comments are kept around the rendered HTML, so the editor knows where each widget starts and ends.

> [!WARNING]
> If the widget function does not exist or returns empty, the block **is not replaced**: the mockup and the comments stay on the published page. A widget that "disappeared" usually shows up as the sample HTML.

## AJAX

The widget's JavaScript sends `ajax=sim` and `ajaxWidgets=<signature>` (several separated by `<#;>`). The router calls `widgets_get()` for each one, now looking for `<function>_ajax` (`publisher_index_render_ajax`). The function answers by filling `$_GESTOR['ajax-json']` and **returning empty**; any returned string becomes a 500 `Widget AJAX error`.

With `ajaxRegistroId` in the request and `grupo_slug` in the parameters, only the widget whose `grupo_slug` matches the record answers, so that several widgets of the same kind on the page do not all answer.

Core widgets with AJAX: `forms-search`, `pages-index`, `publisher-index`.

## Writing a widget

```php
// gestor/modulos/my-module/my-module.widget.php
function my_module_render($params) {
    $html = $params['html'] ?? '';          // the mockup, if you want to reuse it as a template
    // ... build the HTML from the database ...
    return $html;
}

function my_module_render_ajax($params) {
    global $_GESTOR;
    $_GESTOR['ajax-json'] = ['status' => 'Ok', 'items' => []];
    return '';
}
```

Browser behavior goes in a `my-module.widget.js`. The widget function itself includes it on the published page, with `gestor_pagina_javascript_incluir()` (as `publisher-index` and `pages-index` do); the visual editor loads it in the preview.

## Limits

- Only modules in `gestor/modulos/` are looked up. A widget inside a plugin (`plugins/<plugin>/modules/…`) is not found.
- Identical blocks (same signature) are rendered once and the result applies to all of them.
- There is no cache: every visit runs the widget function.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/widgets.php` by `c2f docs:extract` — 1 functions. Do not edit inside this block.

- `widgets_get(array|false $params = false): string` — [line 43](../../../../../gestor/bibliotecas/widgets.php#L43)

<!-- c2f:extract:end -->
