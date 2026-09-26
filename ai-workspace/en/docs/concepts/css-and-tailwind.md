---
title: "CSS and Tailwind"
description: "Authored CSS, derived CSS, and runtime layer order."
section: concepts
order: 90
sources:
  - gestor/bibliotecas/gestor.php
  - gestor/gestor.php
  - gestor/controladores/agents/arquitetura/tailwind-recursos.php
verified_at: 3b099ff0
---

# CSS and Tailwind

Layouts, pages, components, and templates have authored CSS (`css`) and two derived forms: `css_precompiled`, generated for the resource HTML, and `css_compiled`, generated as a delta by the visual editor. `css_source_hash` records compilation provenance. The [resources](resources.md) pipeline writes authored data to the database; `css:rebuild` regenerates derived CSS against the HTML in the database.

`gestor_css_incluir()` inserts styles marked by role (`authored`, `compiled`, and `data-tailwind-role` values), deduplicating them by hash. `gestor_pagina_css()` combines default, project, precompiled, editor, and authored CSS in a defined order. A dependency selected only at runtime, such as a template by `target`, must be listed in `tailwind_dependencies` so its classes are compiled.

> [!IMPORTANT]
> A page can look correct in the editor but lose classes when published if a dynamic dependency is absent from compilation. Inspect the final HTML and run `css:audit` when results differ.

Tailwind resources have per-resource precompiled CSS; an isolated global stylesheet does not replace the page CSS. The editor must accumulate the baseline for inserted sections so its delta retains the rules visitors see.
