---
title: "arquivo.php library"
label: "Files"
description: "Pure functions for uploads and files: sanitizing names, blocking executable extensions, resolving paths without path traversal and deducing type and MIME."
section: reference
order: 190
sources:
  - gestor/bibliotecas/arquivo.php
  - gestor/modulos/admin-arquivos/admin-arquivos.php
verified_at: 914c7b10
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

Reference generated from `gestor/bibliotecas/arquivo.php` by `c2f docs:extract` — 12 functions. Do not edit inside this block.

- `arquivo_nome_sanitizar(string $nome): string` — [line 39](../../../../../gestor/bibliotecas/arquivo.php#L39)
  Higieniza um nome de arquivo ou pasta para uso seguro em sistemas de arquivos heterogêneos (Windows/Linux/rede).
  Parameters:
  - `$nome`: Nome cru informado pelo usuário ou upload.
  Returns: Nome higienizado; string vazia quando nada sobra de válido.
- `arquivo_nome_colisao(string $nomeBase, string $ext, int $indice): string` — [line 88](../../../../../gestor/bibliotecas/arquivo.php#L88)
  Nome de desempate quando o arquivo enviado colide com um já existente na pasta.
  Parameters:
  - `$nomeBase`: Nome já higienizado, sem extensão.
  - `$ext`: Extensão sem o ponto (string vazia quando não há).
  - `$indice`: Contador de desempate (1, 2, 3...).
  Returns: Nome final higienizado.
- `arquivo_extensao_perigosa(string $nome): bool` — [line 106](../../../../../gestor/bibliotecas/arquivo.php#L106)
  Verifica se a extensão de um nome de arquivo é executável/perigosa e deve ser bloqueada no upload, mesmo que o usuário tente burlar a extensão.
  Parameters:
  - `$nome`: Nome do arquivo (com extensão).
  Returns: true quando a extensão é perigosa.
- `arquivo_caminho_relativo_seguro(string $rel): string|false` — [line 147](../../../../../gestor/bibliotecas/arquivo.php#L147)
  Normaliza um caminho relativo informado pelo cliente e garante que ele permanece dentro da árvore de conteúdos (previne path traversal).
  Parameters:
  - `$rel`: Caminho relativo cru (pode vir vazio = raiz).
  Returns: Caminho relativo canônico com `/` (sem barra inicial/final),
- `arquivo_caminho_resolver(string $base, string $rel): string|false` — [line 191](../../../../../gestor/bibliotecas/arquivo.php#L191)
  Resolve o caminho absoluto de um relativo seguro sob uma base e confirma, via realpath quando o alvo existe, que ele não escapa da base.
  Parameters:
  - `$base`: Raiz absoluta de conteúdos (`$_GESTOR['contents-path']`).
  - `$rel`: Caminho relativo (será validado por arquivo_caminho_relativo_seguro).
  Returns: Caminho absoluto (com separador nativo) ou false se inseguro.
- `arquivo_mini_caminho_relativo(string $rel): string` — [line 225](../../../../../gestor/bibliotecas/arquivo.php#L225)
  Dado o caminho relativo de um arquivo, retorna o caminho relativo da sua miniatura na subpasta física `mini/` da mesma pasta.
  Parameters:
  - `$rel`: Caminho relativo do arquivo original.
  Returns: Caminho relativo da miniatura (com `/`).
- `arquivo_tipo_por_extensao(string $nome): string` — [line 244](../../../../../gestor/bibliotecas/arquivo.php#L244)
  Classifica o "tipo" de um arquivo (image/video/audio/file) a partir da extensão, sem depender de `mime_content_type` (que exige o arquivo em disco).
  Parameters:
  - `$nome`: Nome do arquivo.
  Returns: Um de: image, video, audio, file.
- `arquivo_mime_por_extensao(string $nome): string` — [line 275](../../../../../gestor/bibliotecas/arquivo.php#L275)
  Resolve o MIME type real de um arquivo a partir da extensão, sem depender de `mime_content_type` (que exige o arquivo em disco e a extensão fileinfo ativa).
  Parameters:
  - `$nome`: Nome do arquivo (com ou sem caminho).
  Returns: MIME type; `application/octet-stream` para extensão desconhecida.
- `arquivo_assinatura_confere(string $caminho, string $nome): bool` — [line 334](../../../../../gestor/bibliotecas/arquivo.php#L334)
  Confere se o conteúdo de um arquivo bate com a extensão que ele declara (bytes mágicos).
  Parameters:
  - `$caminho`: Caminho absoluto do arquivo já gravado (ou temporário).
  - `$nome`: Nome do arquivo, de onde sai a extensão declarada.
  Returns: False quando o conteúdo contradiz a extensão.
- `arquivo_svg_sanitizar(string $conteudo): string` — [line 394](../../../../../gestor/bibliotecas/arquivo.php#L394)
  Remove de um SVG o que executa código quando o arquivo é aberto no navegador.
  Parameters:
  - `$conteudo`: Conteúdo do SVG.
  Returns: Conteúdo sem os vetores conhecidos.
- `arquivo_caminho_confinar(string $rel, string $escopo): string` — [line 427](../../../../../gestor/bibliotecas/arquivo.php#L427)
  Mantém um caminho relativo dentro de uma pasta de escopo.
  Parameters:
  - `$rel`: Caminho relativo pedido.
  - `$escopo`: Pasta de escopo, relativa aos conteúdos ('' = sem restrição).
  Returns: Caminho relativo seguro, dentro do escopo.
- `arquivo_dir_tamanho(string $abs): int` — [line 446](../../../../../gestor/bibliotecas/arquivo.php#L446)
  Soma o tamanho dos arquivos de uma pasta, recursivamente, sem as miniaturas (`mini/`).
  Parameters:
  - `$abs`: Caminho absoluto da pasta.
  Returns: Total em bytes (0 se a pasta não existe).

<!-- c2f:extract:end -->

## Upload and file-browser scope

arquivo_assinatura_confere compares extension and file signature; SVG looks for an svg tag and glTF requires JSON with asset, so these are not full format validators. arquivo_svg_sanitizar removes scripts, foreignObject, objects/iframes, on* handlers and executable references through regular expressions; it is not an XML parser or general sanitizer. arquivo_caminho_confinar keeps relative paths within a subtree and returns its root for invalid/outside paths. arquivo_dir_tamanho recursively totals bytes, ignoring symbolic links and mini/ thumbnails.
