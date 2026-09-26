---
title: "assets-externos.php library"
description: "Single registry of third-party libraries (jQuery, Fomantic, CodeMirror, Quill, in-browser Tailwind…) with pinned versions, served from disk with a CDN only as a fallback."
section: reference
order: 250
sources:
  - gestor/bibliotecas/assets-externos.php
  - cli/src/Commands/AssetsVendorCommand.php
verified_at: 883d3243
---

# `assets-externos.php` library

Every third-party JavaScript and CSS file the Gestor uses goes through a **single registry**, with a pinned version. A module does not write the tag: it calls `assets_externos_incluir('codemirror')`.

The file is served from `gestor/assets/vendor/<library>/<version>/` when it exists, and from the registry's CDN when it does not. That way the visitor's IP does not go to third-party CDNs, and a new npm release does not reach production without review.

## Registered libraries

| Name | Version | Files |
|---|---|---|
| `jquery` | 3.7.1 | `jquery.min.js` |
| `fomantic-ui` | 2.9.4 | `semantic.min.css`/`.js` + theme fonts and icons |
| `fomantic-icon` | 2.9.4 | Icons only, for Tailwind layouts |
| `codemirror` | 5.65.20 | Core, theme, addons and HTML/CSS/JS/Markdown modes |
| `quill` | 2.0.3 | `quill.snow.css`, `quill.min.js` |
| `sortablejs` | 1.15.6 | `Sortable.min.js` |
| `lucide` | 0.544.0 | `lucide.min.js` |
| `qrcodejs` | 1.0.0 | `qrcode.min.js` |
| `fingerprintjs` | 4 | `fp.min.js` |
| `tailwindcss-browser` | 4.3.0 | Tailwind compiler used in the visual editor; follows the offline build version |

New installations already ship the files in `gestor/assets/vendor/`. To download what is missing (or a new version after changing the registry):

```bash
c2f assets:vendor [--lib=codemirror] [--listar] [--forcar]
```

A library's `arquivos` entry lists what is downloaded but does not become a tag (the fonts the CSS requests by relative path). Without them, served from disk, Fomantic loses its icons.

## Usage

```php
gestor_incluir_biblioteca('assets-externos');
assets_externos_incluir('sortablejs');                  // false if the name is not registered

$tags = assets_externos_tags('quill', $vendorPath, $vendorUrl);   // ['css' => [...], 'js' => [...]]
$map  = assets_externos_urls_js(['codemirror']);        // library => file => URL, for JavaScript
```

`assets_externos_urls_js()` exists for code that builds tags in the browser (the preview `iframe`s and the edit bar): it gets the same URLs, without knowing versions or hosts.

## Pitfalls

- The fallback to the CDN is **silent**: a file missing from `vendor/` is served from the CDN without warning. After changing a version, run `c2f assets:vendor`.
- `assets_externos_incluir()` adds the CSS tags through `gestor_pagina_css_incluir($tag)`, which pushes them onto the end-of-page **JavaScript** queue ([gestor.php](gestor.md)). The CSS works, but loads after the content.
- The local URL uses `url-raiz`, which includes the language prefix when it is in the URL.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/assets-externos.php` by `c2f docs:extract` — 6 functions. Do not edit inside this block.

- `assets_externos_registro(): array<string,` — [line 47](../../../../../gestor/bibliotecas/assets-externos.php#L47)
- `assets_externos_url(array $lib, string $nome, string $arquivo, string $vendorFisico, string $vendorPublico): string` — [line 217](../../../../../gestor/bibliotecas/assets-externos.php#L217)
- `assets_externos_tags(string $nome, string $vendorFisico = '', string $vendorPublico = ''): array{css:` — [line 238](../../../../../gestor/bibliotecas/assets-externos.php#L238)
- `assets_externos_urls_map(list<string> $nomes = Array(), string $vendorFisico = '', string $vendorPublico = ''): array<string,` — [line 279](../../../../../gestor/bibliotecas/assets-externos.php#L279)
- `assets_externos_urls_js(list<string> $nomes = Array()): array<string,` — [line 308](../../../../../gestor/bibliotecas/assets-externos.php#L308)
- `assets_externos_incluir(string $nome): bool` — [line 329](../../../../../gestor/bibliotecas/assets-externos.php#L329)

<!-- c2f:extract:end -->
