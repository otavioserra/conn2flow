---
title: "geral.php library"
description: "A single function, geral_nl2br(), with no callers in the core."
section: reference
order: 220
sources:
  - gestor/bibliotecas/geral.php
verified_at: c267f123
---

# `geral.php` library

It contains only `geral_nl2br($string = '')`: it returns `nl2br($string)` when the value is not empty (according to `existe()`) and the value itself otherwise. It **does not escape HTML**: for user text, use `nl2br(htmlspecialchars($text))`.

No core module uses it. It exists for compatibility; in new code, call `nl2br()` directly.

Registered as `geral` in `bibliotecas-dados` (`gestor_incluir_biblioteca('geral')`).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/geral.php` by `c2f docs:extract` — 1 functions. Do not edit inside this block.

- `geral_nl2br(string $string = ''): string` — [line 22](../../../../../gestor/bibliotecas/geral.php#L22)

<!-- c2f:extract:end -->
