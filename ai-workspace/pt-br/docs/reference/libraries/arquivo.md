---
title: "Biblioteca arquivo.php"
label: "Arquivos"
description: "Funções puras para upload e arquivos: sanitizar nomes, barrar extensões executáveis, resolver caminhos sem path traversal e deduzir tipo e MIME."
section: reference
order: 190
sources:
  - gestor/bibliotecas/arquivo.php
  - gestor/modulos/admin-arquivos/admin-arquivos.php
verified_at: 4c6d01f0
---

# Biblioteca `arquivo.php`

Funções **puras** (sem banco nem `$_GESTOR`) para quem recebe ou serve arquivos. O gerenciador `admin-arquivos` e o controlador `arquivo-estatico` as usam. Cada função só é declarada se ainda não existir.

## Nomes

| Função | |
|---|---|
| `arquivo_nome_sanitizar($nome)` | Fica só com o nome (descarta pastas), troca `: * ? " < > \|` por `-`, remove controles e byte nulo, troca espaços por `-`, junta hífens repetidos e apara `.` e `-` das pontas. Acentos são mantidos. Pode devolver `''` |
| `arquivo_nome_colisao($base, $ext, $n)` | `foto-(2).jpg`, já sanitizado |

## Segurança

- `arquivo_extensao_perigosa($nome)` é `true` para `.htaccess`, `.htpasswd`, `.user.ini` e para qualquer **segmento** de extensão executável ou de configuração (`php`, `phtml`, `phar`, `cgi`, `py`, `sh`, `exe`, `jsp`, `asp`, `shtml`, `ini`, `conf`…). Por olhar todos os segmentos, pega `foto.php.jpg`.
- `arquivo_caminho_relativo_seguro($rel)` devolve o caminho relativo limpo ou `false` para byte nulo, caminho absoluto (`/…`, `C:`), qualquer `..` ou segmento que fique vazio.
- `arquivo_caminho_resolver($base, $rel)` junta os dois e, se o alvo já existe, confere com `realpath()` que ele continua dentro da base (barra link simbólico para fora). Devolve o caminho com o separador do sistema ou `false`.

> [!WARNING]
> A lista de extensões perigosas não inclui `html`, `htm`, `svg` nem `xml`. Enviados pelo `admin-arquivos`, eles são servidos pelo `arquivo-estatico` **inline, no domínio do site**, e podem executar JavaScript (req-181, item A6).

## Tipo e MIME

- `arquivo_tipo_por_extensao($nome)`: `image`, `video`, `audio` ou `file`.
- `arquivo_mime_por_extensao($nome)`: o MIME de uma tabela fixa, ou `application/octet-stream`. Não lê o conteúdo do arquivo: um `.png` que na verdade é outra coisa recebe `image/png`.
- `arquivo_mini_caminho_relativo($rel)`: onde fica a miniatura, `<pasta>/mini/<arquivo>`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/arquivo.php` por `c2f docs:extract` — 8 funções. Não edite dentro deste bloco.

- `arquivo_nome_sanitizar(string $nome): string` — [linha 39](../../../../../gestor/bibliotecas/arquivo.php#L39)
- `arquivo_nome_colisao(string $nomeBase, string $ext, int $indice): string` — [linha 88](../../../../../gestor/bibliotecas/arquivo.php#L88)
- `arquivo_extensao_perigosa(string $nome): bool` — [linha 106](../../../../../gestor/bibliotecas/arquivo.php#L106)
- `arquivo_caminho_relativo_seguro(string $rel): string|false` — [linha 147](../../../../../gestor/bibliotecas/arquivo.php#L147)
- `arquivo_caminho_resolver(string $base, string $rel): string|false` — [linha 191](../../../../../gestor/bibliotecas/arquivo.php#L191)
- `arquivo_mini_caminho_relativo(string $rel): string` — [linha 225](../../../../../gestor/bibliotecas/arquivo.php#L225)
- `arquivo_tipo_por_extensao(string $nome): string` — [linha 244](../../../../../gestor/bibliotecas/arquivo.php#L244)
- `arquivo_mime_por_extensao(string $nome): string` — [linha 275](../../../../../gestor/bibliotecas/arquivo.php#L275)

<!-- c2f:extract:end -->
