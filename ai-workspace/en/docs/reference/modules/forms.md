---
title: "Forms module"
description: "Creating, rendering, and submitting public forms."
section: reference
module: forms
sources:
  - gestor/modulos/forms/forms.php
  - gestor/modulos/forms/forms.js
  - gestor/modulos/forms/forms.widget.php
  - gestor/modulos/forms/forms.widget.js
  - gestor/modulos/forms/forms.json
  - gestor/modulos/forms/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091358_create_forms_table.php
  - gestor/db/migrations/20260216100000_add_project_to_forms_table.php
  - gestor/db/migrations/20260706110000_add_html_and_css_to_forms_table.php
verified_at: 7bf08fe1
---

# `forms` module

Defines reusable site forms. The editor stores fields and settings in `fields_schema`; the widget builds public HTML and the `formulario` library validates and processes submissions.

## How to use

1. Open `forms/` and `forms/adicionar/`. Enter a name, template, and fields; configure description, target, access limits, CAPTCHA, email, and redirects as needed.
2. Check the preview and widget code. `forms/view/`, `forms/editar/`, and `forms/clonar/` display, edit, and duplicate records. `forms/test-email-page/` provides a test page.
3. Insert the marker in a page:

```html
<!-- widgets#forms->render({"form_id":"contact"}) < -->
<div>Mockup</div>
<!-- widgets#forms->render({"form_id":"contact"}) > -->
```

Routes are identical in both languages; the record and template are selected for the request language.

## Technical reference

The controller handles `listar`, `visualizar`, `adicionar`, `editar`, `clonar`, and `test-email-page`; status and deletion belong to the shared interface. Administrative AJAX exposes `template-load`, `widget-preview`, `buscar-componentes`, and `forms-render-editor-html`. The last operation supplies the JS configuration for the editor preview. The admin JS keeps fields sortable, synchronizes hidden JSON, and requests CSRF-protected previews.

`forms` has numeric `id_forms`, `id` up to 100 characters, `name` up to 255, `description`, `template_id`, JSON `fields_schema`, `module`, `plugin`, `project`, `language`, `status`, `version`, timestamps, and update flags. Later migrations add HTML, CSS, and compiled resources. `(id, language)` is unique. Fields are objects with `type`, `name`, `label`, `required`, `placeholder`, and `options`; the schema also accepts `field_name`, `field_email`, `form_action`, `access_max_simple`, `access_max`, `force_recaptcha`, `email`, and `redirects`.

The widget requires an active record. It uses its own HTML or an active `forms` target template, inheriting the template's precompiled CSS when the form lacks it. It repeats the `<!-- item < -->` block per field and replaces `item#label`, `item#name`, `item#type`, `item#value`, `item#options`, `form_id`, `form_action`, and `force_recaptcha`. Blocks `type-select`, `type-textarea`, `type-radio`, `type-checkbox`, and `type-input` select field markup. Included templates are `forms-contato-basico`, `forms-newsletter-newsletter`, `forms-registro-usuario`, `forms-pesquisa-satisfacao`, and `forms-suporte-tecnico`. The JSON declares no module hooks or `hooks.api`.

Submissions use `forms-submissions-process/` by default. The widget invokes `formulario_controlador`, which injects public configuration; widget JS requests it through `forms-render-editor-html` only when it is absent in the editor preview. `formulario_processador` checks `_formId`, honeypot, timestamp, access, CAPTCHA, fields, and limits; inserts into `forms_submissions`, records access, and attempts email as configured. Password field values are persisted as empty strings in submissions, although they remain in POST for the duration of the request.

## Confirmed limitations

> [!WARNING]
> A custom `form_action` changes the POST destination. It must point to a compatible processor; the default is `forms-submissions-process/`. The replay timestamp rejects forms submitted more than two days after generation.

> [!CAUTION]
> The template is a runtime dependency: without custom HTML or an active template in the language, the widget returns an empty string. Limits and CAPTCHA also depend on central configuration and access state; `force_recaptcha` does not describe the entire policy.

## See also

- [Form submissions](forms-submissions.md)
- [Search forms](forms-search.md)
