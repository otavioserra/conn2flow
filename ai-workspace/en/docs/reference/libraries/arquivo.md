---
title: "arquivo.php library"
description: "Pure functions for uploads and files: sanitizing names, blocking executable extensions, resolving paths without path traversal and deducing type and MIME."
section: reference
order: 190
sources:
  - gestor/bibliotecas/arquivo.php
  - gestor/modulos/admin-arquivos/admin-arquivos.php
verified_at: 4c6d01f0
---

# `arquivo.php` library

**Pure** functions (no database, no `$_GESTOR`) for code that receives or serves files. The `admin-arquivos` file manager and the `arquivo-estatico` controller use them. Each function is only declared if it does not exist yet.

## Names

| Function | |
|---|---|
| `arquivo_nome_sanitizar($name)` | Keeps only the name (drops folders), replaces `: * ? " < > \|` with `-`, removes control characters and null bytes, replaces spaces with `-`, collapses repeated hyphens and trims `.` and `-` from the ends. Accents are kept. It may return `''` |
| `arquivo_nome_colisao($base, $ext, $n)` | `photo-(2).jpg`, already sanitized |

## Security

- `arquivo_extensao_perigosa($name)` is `true` for `.htaccess`, `.htpasswd`, `.user.ini` and for any executable or configuration extension **segment** (`php`, `phtml`, `phar`, `cgi`, `py`, `sh`, `exe`, `jsp`, `asp`, `shtml`, `ini`, `conf`…). Because it checks every segment, it catches `photo.php.jpg`.
- `arquivo_caminho_relativo_seguro($rel)` returns the clean relative path, or `false` for a null byte, an absolute path (`/…`, `C:`), any `..` or a segment that ends up empty.
- `arquivo_caminho_resolver($base, $rel)` joins both and, if the target already exists, checks with `realpath()` that it stays inside the base (blocking symbolic links pointing outside). It returns the path with the system separator, or `false`.

> [!WARNING]
> The dangerous-extension list does not include `html`, `htm`, `svg` or `xml`. Uploaded through `admin-arquivos`, they are served by `arquivo-estatico` **inline, on the site's domain**, and can run JavaScript (req-181, item A6).

## Type and MIME

- `arquivo_tipo_por_extensao($name)`: `image`, `video`, `audio` or `file`.
- `arquivo_mime_por_extensao($name)`: the MIME type from a fixed table, or `application/octet-stream`. It does not read the file contents: a `.png` that is actually something else gets `image/png`.
- `arquivo_mini_caminho_relativo($rel)`: where the thumbnail lives, `<folder>/mini/<file>`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/arquivo.php` by `c2f docs:extract` — 8 functions. Do not edit inside this block.

- `arquivo_nome_sanitizar(string $nome): string` — [line 39](../../../../../gestor/bibliotecas/arquivo.php#L39)
- `arquivo_nome_colisao(string $nomeBase, string $ext, int $indice): string` — [line 88](../../../../../gestor/bibliotecas/arquivo.php#L88)
- `arquivo_extensao_perigosa(string $nome): bool` — [line 106](../../../../../gestor/bibliotecas/arquivo.php#L106)
- `arquivo_caminho_relativo_seguro(string $rel): string|false` — [line 147](../../../../../gestor/bibliotecas/arquivo.php#L147)
- `arquivo_caminho_resolver(string $base, string $rel): string|false` — [line 191](../../../../../gestor/bibliotecas/arquivo.php#L191)
- `arquivo_mini_caminho_relativo(string $rel): string` — [line 225](../../../../../gestor/bibliotecas/arquivo.php#L225)
- `arquivo_tipo_por_extensao(string $nome): string` — [line 244](../../../../../gestor/bibliotecas/arquivo.php#L244)
- `arquivo_mime_por_extensao(string $nome): string` — [line 275](../../../../../gestor/bibliotecas/arquivo.php#L275)

<!-- c2f:extract:end -->
