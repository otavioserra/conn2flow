---
title: "Forms search module"
description: "GET search forms, page suggestions, and query logging."
section: reference
module: forms-search
sources:
  - gestor/modulos/forms-search/forms-search.php
  - gestor/modulos/forms-search/forms-search.js
  - gestor/modulos/forms-search/forms-search.widget.php
  - gestor/modulos/forms-search/forms-search.widget.js
  - gestor/modulos/forms-search/forms-search.json
  - gestor/modulos/forms-search/resources
  - gestor/db/migrations/20260715120000_create_forms_search_and_pages_index_tables.php
verified_at: 7bf08fe1
---

# `forms-search` module

Creates search forms for public pages. Submission navigates by GET to a results page; the widget also offers AJAX suggestions while a visitor types. Page index rendering records completed searches.

## How to use

Open `forms-search/` and `forms-search/adicionar/`; select a template, add fields, and set `form_action`, usually `pages-index-search/`. Inspect at `forms-search/view/`, edit at `forms-search/editar/`, or duplicate at `forms-search/clonar/`. Insert the widget on a page:

```html
<!-- widgets#forms-search->render({"form_id":"search"}) < -->
<div>Mockup</div>
<!-- widgets#forms-search->render({"form_id":"search"}) > -->
```

Routes are identical in both languages. The default target is a page containing a `pages-index` widget.

## Technical reference

The controller provides `listar`, `visualizar`, `adicionar`, `editar`, and `clonar`; admin AJAX has `template-load` and `widget-preview`. Status and deletion use the shared interface. `forms_search` has numeric `id_forms_search`, `id` up to 100, `name` up to 255, `description`, `template_id`, JSON `fields_schema`, `module`, `plugin`, `project`, `language`, `status`, `version`, HTML/CSS and compiled resources, timestamps, and update flags. `(id, language)` is unique. `forms_search_submissions` stores `form_id`, `name`, `id`, JSON `fields_values`, language, status, version, and timestamps; it does not hold POST submissions as `forms` does.

The schema accepts `form_action` and `fields` with `type`, `name`, `label`, `placeholder`, `options`, and `required`. The widget finds an active record in the current language and uses its HTML or an active `forms-search` target template; if its own precompiled CSS is absent, it inherits the template's. It repeats `<!-- item < -->`, selects `type-*` blocks, and replaces `item#*`, `form_id`, and `form_action`. It forces the first `<form>` to `method="get"` and ensures a `name="search"` input and `.forms-search-results` container. An empty target resolves to `pages-index-search/`; HTTP(S) and protocol-relative URLs pass through. Templates: `forms-search-contato-basico`, `forms-search-newsletter-newsletter`, `forms-search-registro-usuario`, `forms-search-pesquisa-satisfacao`, and `forms-search-suporte-tecnico`. The JSON declares no hooks or `hooks.api`.

Public AJAX `forms-search-autocomplete` reaches `forms_search_render_ajax()` through `ajaxWidgets`. It requires at least three characters and queries active `paginas` in the language with `tipo='pagina'`, `sem_permissao=1`, and `LIKE` on title or HTML; it orders by title and pages 30 results. It returns `title`, summary up to 180 characters, `url`, `tem_mais`, and `pagina`. The JS uses 300 ms debounce, cache, AbortController, keyboard selection, and load more. Form submission navigates to `?search=...`; it does not use the `forms` POST processor or CAPTCHA.

`forms_search_registrar_busca()` is called by `pages-index`: it writes the term to `forms_search_submissions`, with `form_id` set to the index slug and `fields_values` as an array containing `{ "id":"search", "value":"..." }`. AJAX suggestions alone do not call this logger.

## Confirmed limitations

> [!CAUTION]
> Suggestions ignore page publication windows. Typed `%` and `_` remain `LIKE` wildcards; searching HTML can match markup and attributes. The action parameter permits an absolute, including external, URL for the GET request.

> [!WARNING]
> The `forms_search` migration did not create `css_precompiled`; the widget checks whether the column exists and falls back to the template. The logger does not deduplicate by term or request; initial index rendering and AJAX may record the same search.

## See also

- [Pages index](pages-index.md)
- [Forms](forms.md)
