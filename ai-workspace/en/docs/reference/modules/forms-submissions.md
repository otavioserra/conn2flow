---
title: "Forms submissions module"
description: "Reviewing, tracking, and replying to form submissions."
section: reference
module: forms-submissions
sources:
  - gestor/modulos/forms-submissions/forms-submissions.php
  - gestor/modulos/forms-submissions/forms-submissions.ajax.public.php
  - gestor/modulos/forms-submissions/forms-submissions.js
  - gestor/modulos/forms-submissions/forms-submissions.json
  - gestor/modulos/forms-submissions/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091400_create_forms_submissions_table.php
  - gestor/db/migrations/20260213100000_add_form_status_to_forms_submissions.php
  - gestor/db/migrations/20260211140359_add_forms_security_tables.php
verified_at: 7bf08fe1
---

# `forms-submissions` module

Collects public form submissions, lets operators inspect submitted fields, track handling status, and reply by email. The public processing route delegates to the `formulario` library.

## How to use

Open `forms-submissions/` to list records. At `forms-submissions/view/?id=<slug>`, inspect field values, submission state, and the email detected among form fields. Select `new` or `responded` to update handling status, or enter an address and message to send a reply. The public `forms-submissions-process/` page receives form POST requests; it is not an operator screen. Routes are the same in both languages.

## Technical reference

The admin controller exposes `listar` and `visualizar`. Generic status and deletion use the shared interface; `form_status` is separate and has localized `new` and `responded` options in JSON. AJAX `update-status` checks membership in those options. `reply` requires an id, valid email address, and message, reads the submission, builds the `prepared-response-email` component, embeds images, and attempts delivery through the communication service. Admin JS calls these operations at the current URL.

The public endpoint uses `ajaxOpcao=forms-process`; `forms_submissions_ajax_forms_process()` includes `formulario` and calls `formulario_processador()`. That library finds the form by id and language, checks the honeypot, timestamp age, access, CAPTCHA, and fields, then saves the submission. Limits, recipients, and redirects come from the `forms` `fields_schema`, with central defaults. `forms-submissions` has no public widget or template; it supplies an email component.

`forms_submissions` contains numeric `id_forms_submissions`, `form_id` and `id` up to 100 characters, `name` up to 255, JSON `fields_values`, `form_status` up to 100 defaulting to `new`, `language`, `status`, `version`, `created_at`, and `updated_at`. `(id, language)` is unique, with indexes on `form_id`, `language`, and `form_status`. `fields_values` contains `fields` with `name`/`value` pairs, email status, and later `responses` with `message`, `date`, `email`, and `status`. The module JSON declares no hooks or `hooks.api`.

## Confirmed limitations

> [!WARNING]
> `reply` appends a response and sets `form_status=responded` even when email delivery fails; it then returns `warning` and stores `status=failed` for that response. Handling status does not prove message delivery.

> [!CAUTION]
> The form definition lookup in `reply` concatenates `form_id` and language into SQL without `banco_escape_field`. Submissions normally come from the processor, but this path deserves scrutiny with untrusted data. The status endpoint escapes the numeric id and validates the new value.

## See also

- [Forms](forms.md)
- [Search forms](forms-search.md)
