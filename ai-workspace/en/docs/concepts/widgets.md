---
title: "Widgets"
description: "Module markers, callbacks, rendering, and widget limitations."
section: concepts
order: 50
sources:
  - gestor/bibliotecas/widgets.php
  - gestor/gestor.php
verified_at: 3b099ff0
---

# Widgets

A widget inserts HTML computed by a module into a page. The current form uses `<!-- widgets#MODULE->FUNCTION(JSON) < -->` and a matching closing marker with `>`; the enclosed HTML is passed to the callback as `html` for a visual mockup. The inline form `@[[widgets#MODULE->FUNCTION(JSON)]]@` is still accepted.

`gestor_pagina_widgets()` finds the markers and calls `widgets_get()`. It looks for `gestor/modulos/<module>/<module>.widget.php`, replaces hyphens in the module name with underscores, and tries `<module>_<function>` before the unprefixed name. In AJAX it looks for the corresponding `_ajax` functions. Invalid JSON becomes an empty array.

Use the modular identifier form. The compatibility path for a plain name returns an empty string: it does not query a widget database record. An empty callback result also leaves the original marker in the page because replacement only happens for a nonempty result. This path does not locate plugin widgets; it only checks `modulos/`.

The Live Editor retains opening and closing markers around rendered HTML to identify each widget. They are removed for visitors when replacement succeeds. See the [widgets library](../reference/libraries/widgets.md) for the function signature.
