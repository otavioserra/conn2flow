---
title: "plugins.php library"
description: "A template file with no use: it contains only the sample function template_opcao()."
section: reference
order: 300
sources:
  - gestor/bibliotecas/plugins.php
verified_at: a5ae8605
---

# `plugins.php` library

Despite the name, it **has nothing to do with the plugin system**. It is a library template file: it registers `$_GESTOR['biblioteca-template']` and defines `template_opcao($params)`, an empty function that shows the named-parameter convention (`foreach($params as $var => $val) $$var = $val;`).

It is not in the `bibliotecas-dados` registry and nothing includes it. Plugin installation and updates live in `plugins-installer.php`, with the constants of [plugins-consts.php](plugins-consts.md).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/plugins.php` by `c2f docs:extract` — 1 functions. Do not edit inside this block.

- `template_opcao(array|false $params = false): void` — [line 29](../../../../../gestor/bibliotecas/plugins.php#L29)

<!-- c2f:extract:end -->
