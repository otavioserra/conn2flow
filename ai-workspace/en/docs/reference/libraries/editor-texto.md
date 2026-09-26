---
title: "editor-texto.php library"
description: "The rich text editor (Quill) in the admin panel and the CSS that makes published content look the way the author saw it while writing."
section: reference
order: 260
sources:
  - gestor/bibliotecas/editor-texto.php
  - gestor/gestor.php
verified_at: 883d3243
---

# `editor-texto.php` library

Everything about **Quill**, the rich text editor of the admin panel forms, lives here: the version, the editor tags, the published content CSS and the visual parity between the two. Not to be confused with the HTML editor (`html-editor.php`).

## In the panel: opening the editor

```php
gestor_incluir_biblioteca('editor-texto');
editor_texto_incluir();                     // ['paridade' => false] to skip the project tokens
```

`editor_texto_incluir()` adds to the page:
- Quill's CSS and JavaScript, through the [external assets registry](assets-externos.md) (disk first, CDN as a fallback);
- `interface/quill-content.css`, the **same** content CSS the site receives;
- `interface/editor-texto.js`, which creates the editors over the `<textarea>`s and keeps the form field in sync;
- the **parity**: if the project has `contents/tailwindcss/browser-contract.css`, its CSS variables (`--…`) are copied into `.ql-editor`, so the author writes with the site's font and colors. Values with `url()`/`data:` or longer than 160 characters are left out.

The version comes from `$_GESTOR['editor-texto-versao']` or from the external assets registry (`quill` 2.0.3). `quill-content.css` was generated for that version: changing one without the other makes the editor and the site render differently.

## On the site: published content

Quill stores formatting as **classes** (`ql-align-right`, `ql-indent-2`), not inline styles. Without `quill-content.css`, the published page loses alignments and indents.

The router takes care of it on its own (`gestor_pagina_quill()`): after assembling the page and the widgets, `editor_texto_conteudo_detectar($html)` looks for Quill classes (class by class, not by substring) and, if it finds any, includes the CSS from `editor_texto_assets_publicacao()`. Pages without editor content load nothing.

## Detail

> [!NOTE]
> The parity sets `color: var(--color-mp-ink, inherit)` inside `.ql-editor`. `--color-mp-ink` is a token from one specific project; elsewhere, the value falls back to `inherit`. To adjust the text color in the editor, define that token in the project contract or use `--font-sans` and your own variables.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/editor-texto.php` by `c2f docs:extract` — 7 functions. Do not edit inside this block.

- `editor_texto_assets_externos_carregar()` — [line 38](../../../../../gestor/bibliotecas/editor-texto.php#L38)
- `editor_texto_versao_cdn(): string` — [line 59](../../../../../gestor/bibliotecas/editor-texto.php#L59)
- `editor_texto_assets_editor(string $urlRaiz = '', string $versaoAsset = '', $vendorFisico = '', $vendorPublico = ''): array{css: list<string>, javascript: list<string>}` — [line 93](../../../../../gestor/bibliotecas/editor-texto.php#L93)
- `editor_texto_paridade_css(string $contrato): string` — [line 153](../../../../../gestor/bibliotecas/editor-texto.php#L153)
- `editor_texto_incluir(array $params = false): void` — [line 213](../../../../../gestor/bibliotecas/editor-texto.php#L213)
- `editor_texto_conteudo_detectar(string $html): bool` — [line 271](../../../../../gestor/bibliotecas/editor-texto.php#L271)
- `editor_texto_assets_publicacao(string $urlRaiz = '', string $versao = ''): array` — [line 333](../../../../../gestor/bibliotecas/editor-texto.php#L333)

<!-- c2f:extract:end -->
