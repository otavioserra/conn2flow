---
title: "admin-templates module"
description: "Creation and maintenance of HTML templates for Gestor resources."
section: reference
module: admin-templates
sources:
  - gestor/modulos/admin-templates/admin-templates.php
  - gestor/modulos/admin-templates/admin-templates.js
  - gestor/modulos/admin-templates/admin-templates.json
  - gestor/modulos/admin-templates/resources
  - gestor/db/migrations/20251030160430_create_templates_table.php
verified_at: a9a226e0
---

# admin-templates module

This module manages reusable content templates consumed by other resources, especially pages and publishers. A template combines a target, HTML, extra head content, CSS, framework, and thumbnail; it is not a published page on its own.

## How to use

Open admin-templates/ to list records. Create at admin-templates/adicionar/, edit at admin-templates/editar/?id=<slug>, or duplicate at admin-templates/clonar/?id=<slug>. Set the target, name, slug, thumbnail, and framework, then edit HTML/CSS. A consuming resource must select an active template in the matching language. All four routes exist in pt-br and en.

## Technical reference

The controller handles adicionar, editar, and clonar; the shared interface supplies listing, status, and deletion. The AJAX switch has no active case. Saving converts text markers to internal references, computes css_source_hash, records history and content backups on edit, and increments the version. Cloning copies content and metadata into a new record. admin_templates_alvo_ia() looks up or checks a target for the AI editor integration.

The templates table has numeric id_templates, nome, id, target, thumbnail, plugin, language, html, html_extra_head, css, css_compiled, framework_css, status, versao, dates, update flags, and file/checksum metadata. The migration's unique key is (id, language), without target. Later migrations add precompiled CSS, project, and source-hash fields. The module JSON defines no widget, hook, or hooks.api; consumers query templates by language, target, and status.

## Confirmed limitations

> [!WARNING]
> The edit form visually disables changing the target, but the POST handler accepts a submitted target. Slugs must also be unique across targets within one language because the SQL index does not include target. The CRUD stores supplied compiled CSS and a hash but does not run compilation.

> [!CAUTION]
> The admin_templates_alvo_ia() branch that fetches a template by id concatenates the identifier into its SQL condition. Do not treat this helper as validation of untrusted input.

## See also

- [Pages](admin-paginas.md)
- [Publisher](publisher.md)
