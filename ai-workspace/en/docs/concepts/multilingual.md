---
title: "Multilingual system"
description: "Language selection, paths, resources, and content isolation."
section: concepts
order: 60
sources:
  - gestor/config.php
  - gestor/gestor.php
  - gestor/bibliotecas/gestor.php
  - gestor/bibliotecas/lang.php
verified_at: 3b099ff0
---

# Multilingual system

`LANGUAGES` configures accepted languages. `gestor_config()` removes a valid language prefix from the path (`/en/...`) and adds it to root URLs; without a prefix, a valid language cookie can select the language. The router queries pages whose `language` matches the active language. Available translations of a page are determined by querying matching pages.

Resources for different languages are separate files under `resources/<language>/` and separate database records. A missing resource is not translated automatically. System variables are queried for the active language; interface text should have corresponding resources in each language. See [resources](resources.md) and [global variables](global-variables.md).

`gestor/bibliotecas/lang.php` implements a separate JSON dictionary with `__t()`, which returns the key itself when a translation is missing. This dictionary does not replace compiled `variables` resources; its expected default dictionary file is absent from the current core. Use per-language resources for site and admin content.

> [!WARNING]
> Adding a URL prefix without creating the page and its resources in that language leaves the route without a page; it does not translate existing HTML.
