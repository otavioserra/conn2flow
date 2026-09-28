---
title: "sitemap.php library"
label: "Sitemap and robots.txt"
description: "Generation and incremental updates of sitemap.xml and robots.txt: which pages go in, where the files live and when they are updated."
section: reference
order: 270
sources:
  - gestor/bibliotecas/sitemap.php
  - gestor/modulos/admin-paginas/admin-paginas.php
  - gestor/modulos/publisher-pages/publisher-pages.php
verified_at: 0ccf7099
---

# `sitemap.php` library

Maintains the site's `sitemap.xml` and `robots.txt`. Both live in `gestor/assets/` and are served by the `arquivo-estatico` controller at `https://<domain>/sitemap.xml` and `/robots.txt`, with no dependency on server rewrite rules. An old `sitemap.xml` at the Gestor root, if the core generated it, is deleted on the next write; a hand-made one is preserved.

## Which pages go in

`sitemap_pagina_elegivel($page)` requires:
- `status='A'` and **`sem_permissao`** (public page; the whole admin panel stays out);
- a path that is not a Gestor system route nor a flow route (`sitemap_caminho_nao_indexavel()`): OAuth callbacks, `signin-2fa`, `validate-user`, form processors, `pagina-de-impressao`, any path starting with `admin-`, and outcome pages whose last segment is or ends in `confirmation`, `success`, `error`, `failure`, `cancel`, `cancelled` or `canceled` (`contact-success`, `checkout/error`), plus `…/payment`, `…/checkout`, `…/processing` and `…/download` in compound paths, and the purchase flow: last segment `cart` or `checkout`, or ending in `-cart`/`-checkout` (`cart/`, `en/checkout/`, `subscription-checkout/`, req-191);
- being inside the `data_publicacao_inicio`/`fim` window.

`/signin/`, `/signup/` and `/forgot-password/` are included. The URL carries the language prefix when it is not the default (`/en/docs/`), and `lastmod` is the page's `data_modificacao`.

## When it is updated

- **Incrementally**, on every save, clone, delete or status change in `admin-paginas`, `publisher-pages` and Live Editor edits (`sitemap_sincronizar_por_id()`, which calls `sitemap_sincronizar_pagina()`): only that page's URL is added, changed or removed. If the path changed, the old URL goes out before the new one comes in.
- **Fully** (`sitemap_gerar_completo()`), only when the file does not exist, is corrupted or the edited page vanished from the database. A full generation also rewrites `robots.txt`.

> [!WARNING]
> **Deploying through the API regenerates the sitemap.** After the database and hooks, `api_project_update()` calls `sitemap_gerar_completo()` in the site's HTTP context, which knows the domain, and returns `"sitemap": "updated"` in the response. SSH synchronization (`project:update-all` with `deploy_mode: ssh`) runs the updater from the CLI, without the domain, and does not regenerate it: new pages then only get in when someone edits them in the panel or on the next API deploy.

## robots.txt

`sitemap_robots_montar()` blocks the utility routes (`/_gestor-cookie-verify`, `/oauth-callback`, `/signin-2fa`, `/forms-submissions-process`…) and declares `Sitemap: <url>/sitemap.xml`. It accepts extra `disallow` entries.

> [!NOTE]
> The `Disallow` lines are absolute from `/`: on an installation in a subfolder (`URL_RAIZ=/site/`) or for other-language versions (`/en/oauth-callback`) they do not match. The pages stay out of the sitemap, but crawlers are not prevented from visiting them.

## XML functions

`sitemap_xml_montar($urls)`, `sitemap_xml_upsert($xml, $loc, $lastmod)` and `sitemap_xml_remover($xml, $loc)` are pure (text → text); `sitemap_data_w3c()` formats `lastmod`. `sitemap_url_da_pagina()`, `sitemap_caminho_arquivo()`, `sitemap_robots_caminho_arquivo()`, `sitemap_gravar()`, `sitemap_robots_gravar()`, `sitemap_conteudo_proprio()` and `sitemap_legado_remover()` handle the URL and the disk.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/sitemap.php` by `c2f docs:extract` — 17 functions. Do not edit inside this block.

- `sitemap_pagina_elegivel(array $pagina, int|null $agora = null): bool` — [line 41](../../../../../gestor/bibliotecas/sitemap.php#L41)
  Decide se uma página entra no sitemap.
  Parameters:
  - `$pagina`: Linha da tabela `paginas`.
  - `$agora`: Timestamp de referência (padrão: `time()`), usado nos testes.
- `sitemap_caminho_nao_indexavel(string $caminho): bool` — [line 99](../../../../../gestor/bibliotecas/sitemap.php#L99)
  Rotas públicas que NÃO são conteúdo indexável (req-112).
  Parameters:
  - `$caminho`: Caminho da página, em minúsculas.
- `sitemap_robots_montar(array $params = Array()): string` — [line 177](../../../../../gestor/bibliotecas/sitemap.php#L177)
  Monta o conteúdo do `robots.txt`.
  Parameters:
  - `$params['sitemap']`: URL absoluta do sitemap (omitida quando vazia).
  - `$params['disallow']`: Prefixos adicionais a barrar.
- `sitemap_robots_caminho_arquivo(): string` — [line 225](../../../../../gestor/bibliotecas/sitemap.php#L225)
  Caminho físico do `robots.txt`.
- `sitemap_robots_gravar(): bool` — [line 235](../../../../../gestor/bibliotecas/sitemap.php#L235)
  Grava o `robots.txt` apontando para o sitemap público.
- `sitemap_xml_montar(array $urls = Array()): string` — [line 265](../../../../../gestor/bibliotecas/sitemap.php#L265)
  Monta o documento completo do sitemap a partir de uma lista de URLs.
  Parameters:
  - `$urls`: Lista de `['loc' => string, 'lastmod' => string|null]`.
  Returns: XML pronto para gravação.
- `sitemap_data_w3c(string|null $data = null): string|null` — [line 294](../../../../../gestor/bibliotecas/sitemap.php#L294)
  Converte uma data do banco para o formato W3C exigido pelo protocolo de sitemap.
  Returns: `null` quando a data é inválida ou ausente (a tag é então omitida).
- `sitemap_xml_upsert(string $xml, string $loc, string|null $lastmod = null): string` — [line 314](../../../../../gestor/bibliotecas/sitemap.php#L314)
  Insere ou atualiza a entrada de uma URL num sitemap existente (upsert incremental).
  Parameters:
  - `$xml`: XML atual.
  - `$loc`: URL absoluta da página.
  - `$lastmod`: Data de modificação.
  Returns: XML atualizado.
- `sitemap_xml_remover(string $xml, string $loc): string` — [line 345](../../../../../gestor/bibliotecas/sitemap.php#L345)
  Remove a entrada de uma URL do sitemap (página excluída, despublicada ou que virou privada).
  Parameters:
  - `$xml`: XML atual.
  - `$loc`: URL absoluta a remover.
  Returns: XML sem a entrada.
- `sitemap_caminho_arquivo(): string` — [line 377](../../../../../gestor/bibliotecas/sitemap.php#L377)
  Caminho absoluto do arquivo `sitemap.xml` na raiz pública.
- `sitemap_url_da_pagina(array $pagina): string` — [line 406](../../../../../gestor/bibliotecas/sitemap.php#L406)
  URL pública absoluta de uma página, respeitando o prefixo de idioma quando não for o padrão.
  Parameters:
  - `$pagina`: Linha da tabela `paginas`.
- `sitemap_gravar(string $xml): bool` — [line 430](../../../../../gestor/bibliotecas/sitemap.php#L430)
  Grava o conteúdo do sitemap em disco.
- `sitemap_conteudo_proprio(string $conteudo): bool` — [line 455](../../../../../gestor/bibliotecas/sitemap.php#L455)
  Reconhece um `sitemap.xml` gerado por ESTA biblioteca (F8 do review de 2026-08-15).
- `sitemap_legado_remover(): bool` — [line 481](../../../../../gestor/bibliotecas/sitemap.php#L481)
  Apaga o `sitemap.xml` que versões anteriores gravavam na RAIZ pública (F8).
  Returns: True quando algo foi removido.
- `sitemap_gerar_completo(): bool` — [line 513](../../../../../gestor/bibliotecas/sitemap.php#L513)
  Regenera o `sitemap.xml` inteiro a partir das páginas públicas ativas.
- `sitemap_sincronizar_pagina(array $pagina, bool $remover = false, $caminhoAntigo = null): bool` — [line 566](../../../../../gestor/bibliotecas/sitemap.php#L566)
  Sincroniza UMA página no sitemap, criando o arquivo do zero quando ele ainda não existe.
  Parameters:
  - `$pagina`: Linha (ou dados equivalentes) da página alterada.
  - `$remover`: Força a remoção da entrada (página excluída).
- `sitemap_sincronizar_por_id(string $id, bool $remover = false, string|null $caminhoAntigo = null): bool` — [line 606](../../../../../gestor/bibliotecas/sitemap.php#L606)
  Recarrega os dados de uma página pelo identificador e sincroniza o sitemap.
  Parameters:
  - `$id`: Identificador textual da página.
  - `$caminhoAntigo`: Caminho anterior, quando o slug mudou (req-112).

<!-- c2f:extract:end -->
