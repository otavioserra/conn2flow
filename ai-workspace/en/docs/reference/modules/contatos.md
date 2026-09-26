---
title: "contatos module"
description: "Public contact page backed by the forms system."
section: reference
module: contatos
sources:
  - gestor/modulos/contatos/contatos.php
  - gestor/modulos/contatos/contatos.js
  - gestor/modulos/contatos/contatos.json
  - gestor/modulos/contatos/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091358_create_forms_table.php
  - gestor/db/migrations/20260210091400_create_forms_submissions_table.php
verified_at: a9a226e0
---

# contatos module

This module serves a public contact page using the forms system's form-contact definition. The formulario library processes submissions and stores them in forms and forms_submissions; there is no separate contacts table.

## How to use

A visitor opens contact/, fills in name, email, and message, and submits. The schema marks all three fields required and redirects success to contact/success/ and errors to contact/. Both pages carry without_permission in JSON and exist in pt-br and en. The success page includes a noindex robots meta tag. Operators inspect entries in forms-submissions.

## Technical reference

The controller handles only the contact option, calling formulario_controlador() with formId form-contact. Its AJAX switch has no active case; submission behavior comes from the forms library and the form class in the page. contact-success is a static page resource with no PHP switch branch. The JSON seeds forms.id=form-contact with fields_schema containing field_name=name, field_email=email, fields, and redirects. It also provides base-email and prepared-email components in pt-br and prepared-email in en; it defines no own widget, template, hook, or hooks.api.

forms stores id, name, description, template_id, JSON fields_schema, module, plugin, language, status, version, dates, and modification flags; (id, language) is unique. forms_submissions stores form_id, name, id, JSON fields_values, language, status, version, and dates; (id, language) is unique and form_id is indexed.

## Confirmed limitations

> [!CAUTION]
> contact/success/ does not run a contacts PHP handler; it depends on the seeded page HTML. Storage, validation, and any email delivery belong to the forms processor, not to a separate workflow in this module.

## See also

- [Forms](forms.md)
- [Form submissions](forms-submissions.md)
