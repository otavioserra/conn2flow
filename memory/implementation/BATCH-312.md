---
id: "BATCH-312"
title: "REQ-303 — Canonical, og:image:* e links privados nas docs"
status: "implemented-pending-review"
date: "2026-11-23"
author: "executor"
target_repo: "conn2flow"
request: "REQ-303"
topology: "dupla"
autonomy: "autonomo_monitorado"
summary_short: "Canonical no head, og:image:width/height/type do arquivo local, og:url = canonical e docs da memória sem link para caminho privado."
---

# BATCH-312 — REQ-303 (Categoria A no Core; o site leva as docs regeneradas, Categoria C)

- **Requisição:** [REQ-303](../human-requests/req-303.md); site: REQ-156 / BATCH-149.
- **Branch:** `feat/req-303-canonical-og-image`, montada sobre a `origin/main` (`4f2db9ce`) com índice separado. A árvore `main` tem WIP sem commit da REQ-301 (outro lote), que não entrou no commit.

## Alterações

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/gestor.php` | `gestor_open_graph_tags()` aceita `image_width`/`image_height`/`image_type` e as emite logo depois de `og:image` (só com imagem). Novas puras: `gestor_open_graph_imagem_arquivo()`, `gestor_open_graph_imagem_info()`, `gestor_canonical_url()`, `gestor_canonical_existe()` |
| `gestor/gestor.php` | `gestor_open_graph_dados()`: imagem relativa com a base sem idioma, dimensões do metadado ou do arquivo local, `og:url` = canonical. Novo `gestor_canonical_dados()`. Emissão única da canonical em `gestor_pagina_extra_head_e_javascript()` |
| `gestor/bibliotecas/sitemap.php` | `sitemap_caminho_nao_indexavel()`: etapa dentro de fluxo `cart`/`checkout` e páginas de token fora do sitemap, também com prefixo de idioma |
| `tests/Unit/PHP/SitemapTest.php` | Caso novo (8 caminhos fora, 5 dentro, como `checkout-rapido-guia/passo-1`) |
| `cli/src/Support/Docs/SddSource.php` | `unlinkPrivateTargets()` no fim de `filter()` |
| `tests/Unit/PHP/CanonicalOpenGraphImageReq303Test.php` | 14 testes / 50 asserções |

## Validação

- `php -l` nos três PHP: sem erro.
- `CanonicalOpenGraphImageReq303Test` 14/14; `SitemapTest` 40/40; `PaginasVisibilidadeReq275Test` 15/15; `CrawlersOpenGraphTest` 32/32; `DocsSddReq184Test` 6/6; `DocsReq188Test` 5/5.
- Suíte completa (PHP 8.5, Windows): 2.037 testes, 21 falhas + 1 erro. Rodada também sem a mudança, com o JUnit comparado: **o mesmo conjunto de 22 testes** falha nas duas, então nenhuma falha é nova.
- O WebP de teste é um cabeçalho VP8X montado em bytes, porque o PHP do Windows não tem GD com WebP.

## Pendências

- Evidência do Lab: registrada no BATCH-149 do site (publicação por `project:update-all conn2flow-site-local`).
- Revisão técnica; merge na `main`; homologação humana.
