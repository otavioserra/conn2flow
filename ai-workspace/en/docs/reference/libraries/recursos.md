---
title: "recursos.php library"
label: "Asset URLs"
description: "Static asset URLs: direct delivery from public_html/dist/ when the file was published, with a fallback to the arquivo-estatico controller."
section: reference
order: 120
sources:
  - gestor/bibliotecas/recursos.php
  - gestor/config.php
verified_at: 8768245a
---

# `recursos.php` library

Resolves the URL of a static asset (JS, CSS, image, font) of the Gestor or of a module. Always loaded by `config.php`.

The Gestor code lives outside the public folder. An asset can reach the browser in two ways:

1. **Published to `public_html/dist/`**: `c2f assets:publish` (called by `resources:sync` and by the deploy) copies the assets there and writes the `dist/.manifest.json` inventory. Nginx or Apache serve the file straight from disk, without PHP.
2. **Through the `arquivo-estatico` controller**: without publication, the historical URL (`<url-raiz>interface/interface.js`) goes through PHP, which reads the file from `gestor/assets/` or from the module.

`recursos_url()` picks `dist/` **only when the file is in the manifest**. In development, on a fresh installation or with an asset not published yet, the URL stays the old one, and nothing breaks.

## Usage

```php
echo recursos_tag_js('interface/interface.js', $version, 'defer');
echo recursos_tag_css('admin-arquivos/css.css');
$url = recursos_url_versionada('images/logo.png');
```

| Function | Does |
|---|---|
| `recursos_url($caminho, $base = null)` | URL in `dist/` if published; otherwise `<url-raiz or $base><path>` |
| `recursos_versao($caminho, $padrao = null)` | The content hash, from the manifest; otherwise `$padrao`; otherwise `gestor_asset_version()` |
| `recursos_url_versionada()` | `recursos_url()` + `?v=<version>` |
| `recursos_tag_js()` / `recursos_tag_css()` | The ready `<script>` / `<link rel="stylesheet">` tag |
| `recursos_publicado($caminho)` / `recursos_dist_ativo()` | Lookups in the manifest loaded into `$_GESTOR['dist-manifest']` |
| `recursos_caminho_normalizar($caminho)` | Strips `?`/`#`, extra and leading slashes; rejects `..` and null bytes (`''`) |
| `recursos_dist_mapear_fonte($relativo)` | The source → `dist/` contract, shared with `assets:publish` |

## The path contract

The path inside `dist/` is the same as the public URL:

| Source in the Gestor | In `dist/` and in the URL |
|---|---|
| `assets/<path>` | `<path>` |
| `modulos/<m>/<m>.js` / `<m>.css` | `<m>/js.js` / `<m>/css.css` |
| `modulos/<m>/<m>.<option>.js` | `<m>/<option>.js` |

`.min.js` has no path of its own: it is the preferred variant of the authored file, chosen at publication and, at runtime, by `arquivo-estatico`.

The `dist/` folder uses `url-raiz-sem-lang`: the same file does not get one URL per language. `dist-ativo` turns on by itself in production and can be forced with `ASSETS_DIST` in the `.env`; `PUBLIC_PATH` tells where the public folder is when the Gestor runs from the CLI ([global variables](../../concepts/global-variables.md)).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/recursos.php` by `c2f docs:extract` — 9 functions. Do not edit inside this block.

- `recursos_caminho_normalizar(string $caminho): string` — [line 43](../../../../../gestor/bibliotecas/recursos.php#L43)
  Normaliza um caminho de asset para a forma usada como chave do manifesto.
  Parameters:
  - `$caminho`: Caminho relativo do asset, como aparece na URL.
  Returns: Caminho normalizado, ou '' quando o caminho é inválido.
- `recursos_dist_ativo()` — [line 61](../../../../../gestor/bibliotecas/recursos.php#L61)
  Indica se a instalação tem assets publicados e habilitados para entrega direta.
- `recursos_publicado(string $caminho): bool` — [line 73](../../../../../gestor/bibliotecas/recursos.php#L73)
  Informa se um asset consta no manifesto publicado em `dist/`.
  Parameters:
  - `$caminho`: Caminho relativo do asset (com ou sem query string).
- `recursos_url(string $caminho, string|null $base = null): string` — [line 102](../../../../../gestor/bibliotecas/recursos.php#L102)
  URL pública de um asset.
  Parameters:
  - `$caminho`: Caminho relativo do asset, opcionalmente com query string.
  - `$base`: Raiz pública usada no fallback; null usa `url-raiz`.
  Returns: URL pronta para o atributo `src`/`href`.
- `recursos_versao(string $caminho, string|null $versaoPadrao = null): string` — [line 128](../../../../../gestor/bibliotecas/recursos.php#L128)
  Token de cache busting de um asset publicado.
  Parameters:
  - `$caminho`: Caminho relativo do asset.
  - `$versaoPadrao`: Versão usada quando o asset não está publicado.
- `recursos_url_versionada(string $caminho, string|null $versao = null, string|null $base = null): string` — [line 149](../../../../../gestor/bibliotecas/recursos.php#L149)
  URL de um asset já com o parâmetro de cache busting.
  Parameters:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão a usar quando o asset não está publicado.
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_tag_js(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [line 162](../../../../../gestor/bibliotecas/recursos.php#L162)
  Tag `<script>` de um asset do gestor.
  Parameters:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão usada quando o asset não está publicado.
  - `$extra`: Atributos adicionais já formatados (ex.: `defer`).
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_tag_css(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [line 177](../../../../../gestor/bibliotecas/recursos.php#L177)
  Tag `<link rel="stylesheet">` de um asset do gestor.
  Parameters:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão usada quando o asset não está publicado.
  - `$extra`: Atributos adicionais já formatados (ex.: `data-c2f-css-role="quill"`).
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_dist_mapear_fonte(string $relativo): string` — [line 204](../../../../../gestor/bibliotecas/recursos.php#L204)
  Contrato de mapeamento entre o arquivo FONTE no gestor e o caminho publicado em `dist/`.
  Parameters:
  - `$relativo`: Caminho do arquivo relativo à raiz do gestor, com `/` como separador.
  Returns: Caminho dentro de `dist/`, ou '' quando o arquivo não é publicável.

<!-- c2f:extract:end -->
