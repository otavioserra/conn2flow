---
title: "Biblioteca sitemap.php"
label: "Sitemap e robots.txt"
description: "Geração e atualização incremental do sitemap.xml e do robots.txt: que páginas entram, onde os arquivos ficam e quando são atualizados."
section: reference
order: 270
sources:
  - gestor/bibliotecas/sitemap.php
  - gestor/modulos/admin-paginas/admin-paginas.php
  - gestor/modulos/publisher-pages/publisher-pages.php
verified_at: c8c99168
---

# Biblioteca `sitemap.php`

Mantém o `sitemap.xml` e o `robots.txt` do site. Os dois ficam em `gestor/assets/` e são servidos pelo controlador `arquivo-estatico` em `https://<dominio>/sitemap.xml` e `/robots.txt`, sem depender de regra de reescrita do servidor. Um `sitemap.xml` antigo na raiz do Gestor, se foi gerado pelo core, é apagado na próxima gravação; um feito à mão é preservado.

## Que páginas entram

`sitemap_pagina_elegivel($pagina)` exige:
- `status='A'` e **`sem_permissao`** (página pública; o painel inteiro fica de fora);
- caminho que não seja rota de sistema do Gestor nem rota de fluxo (`sitemap_caminho_nao_indexavel()`): callbacks de OAuth, `signin-2fa`, `validate-user`, processadores de formulário, `pagina-de-impressao`, qualquer caminho começando por `admin-`, e páginas de desfecho cujo último segmento é ou termina em `confirmation`, `success`, `error`, `failure`, `cancel`, `cancelled` ou `canceled` (`contato-success`, `checkout/error`), além de `…/payment`, `…/checkout`, `…/processing` e `…/download` em caminhos compostos, e do fluxo de compra: último segmento `cart` ou `checkout`, ou terminado em `-cart`/`-checkout` (`cart/`, `en/checkout/`, `subscription-checkout/`, req-191);
- estar dentro da janela `data_publicacao_inicio`/`fim`.

`/signin/`, `/signup/` e `/forgot-password/` entram. A URL leva o prefixo do idioma quando ele não é o padrão (`/en/docs/`), e `lastmod` é a `data_modificacao` da página.

## Quando é atualizado

- **Incremental**, a cada salvar, clonar, excluir ou trocar status no `admin-paginas`, no `publisher-pages` e na edição pelo Live Editor (`sitemap_sincronizar_por_id()`, que chama `sitemap_sincronizar_pagina()`): só a URL daquela página entra, muda ou sai. Se o caminho mudou, a URL antiga sai antes de a nova entrar.
- **Completo** (`sitemap_gerar_completo()`), só quando o arquivo não existe, está corrompido ou a página editada sumiu do banco. A geração completa também regrava o `robots.txt`.

> [!WARNING]
> **O deploy pela API regenera o sitemap.** Depois do banco e dos hooks, `api_project_update()` chama `sitemap_gerar_completo()` no contexto HTTP do site, que conhece o domínio, e devolve `"sitemap": "updated"` na resposta. A sincronização por SSH (`project:update-all` com `deploy_mode: ssh`) roda o atualizador em CLI, sem o domínio, e não regenera: nesse caso, páginas novas só entram quando alguém as edita no painel ou no próximo deploy pela API.

## robots.txt

`sitemap_robots_montar()` barra as rotas utilitárias (`/_gestor-cookie-verify`, `/oauth-callback`, `/signin-2fa`, `/forms-submissions-process`…) e declara `Sitemap: <url>/sitemap.xml`. Aceita `disallow` extras.

> [!NOTE]
> Os `Disallow` são absolutos a partir de `/`: numa instalação em subpasta (`URL_RAIZ=/site/`) ou nas versões em outro idioma (`/en/oauth-callback`) eles não casam. As páginas continuam fora do sitemap, mas o rastreador não é impedido de visitá-las.

## Funções do XML

`sitemap_xml_montar($urls)`, `sitemap_xml_upsert($xml, $loc, $lastmod)` e `sitemap_xml_remover($xml, $loc)` são puras (texto → texto); `sitemap_data_w3c()` formata o `lastmod`. `sitemap_url_da_pagina()`, `sitemap_caminho_arquivo()`, `sitemap_robots_caminho_arquivo()`, `sitemap_gravar()`, `sitemap_robots_gravar()`, `sitemap_conteudo_proprio()` e `sitemap_legado_remover()` cuidam da URL e do disco.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/sitemap.php` por `c2f docs:extract` — 17 funções. Não edite dentro deste bloco.

- `sitemap_pagina_elegivel(array $pagina, int|null $agora = null): bool` — [linha 41](../../../../../gestor/bibliotecas/sitemap.php#L41)
  Decide se uma página entra no sitemap.
  Parâmetros:
  - `$pagina`: Linha da tabela `paginas`.
  - `$agora`: Timestamp de referência (padrão: `time()`), usado nos testes.
- `sitemap_caminho_nao_indexavel(string $caminho): bool` — [linha 99](../../../../../gestor/bibliotecas/sitemap.php#L99)
  Rotas públicas que NÃO são conteúdo indexável (req-112).
  Parâmetros:
  - `$caminho`: Caminho da página, em minúsculas.
- `sitemap_robots_montar(array $params = Array()): string` — [linha 177](../../../../../gestor/bibliotecas/sitemap.php#L177)
  Monta o conteúdo do `robots.txt`.
  Parâmetros:
  - `$params['sitemap']`: URL absoluta do sitemap (omitida quando vazia).
  - `$params['disallow']`: Prefixos adicionais a barrar.
- `sitemap_robots_caminho_arquivo(): string` — [linha 225](../../../../../gestor/bibliotecas/sitemap.php#L225)
  Caminho físico do `robots.txt`.
- `sitemap_robots_gravar(): bool` — [linha 235](../../../../../gestor/bibliotecas/sitemap.php#L235)
  Grava o `robots.txt` apontando para o sitemap público.
- `sitemap_xml_montar(array $urls = Array()): string` — [linha 265](../../../../../gestor/bibliotecas/sitemap.php#L265)
  Monta o documento completo do sitemap a partir de uma lista de URLs.
  Parâmetros:
  - `$urls`: Lista de `['loc' => string, 'lastmod' => string|null]`.
  Retorno: XML pronto para gravação.
- `sitemap_data_w3c(string|null $data = null): string|null` — [linha 294](../../../../../gestor/bibliotecas/sitemap.php#L294)
  Converte uma data do banco para o formato W3C exigido pelo protocolo de sitemap.
  Retorno: `null` quando a data é inválida ou ausente (a tag é então omitida).
- `sitemap_xml_upsert(string $xml, string $loc, string|null $lastmod = null): string` — [linha 314](../../../../../gestor/bibliotecas/sitemap.php#L314)
  Insere ou atualiza a entrada de uma URL num sitemap existente (upsert incremental).
  Parâmetros:
  - `$xml`: XML atual.
  - `$loc`: URL absoluta da página.
  - `$lastmod`: Data de modificação.
  Retorno: XML atualizado.
- `sitemap_xml_remover(string $xml, string $loc): string` — [linha 345](../../../../../gestor/bibliotecas/sitemap.php#L345)
  Remove a entrada de uma URL do sitemap (página excluída, despublicada ou que virou privada).
  Parâmetros:
  - `$xml`: XML atual.
  - `$loc`: URL absoluta a remover.
  Retorno: XML sem a entrada.
- `sitemap_caminho_arquivo(): string` — [linha 377](../../../../../gestor/bibliotecas/sitemap.php#L377)
  Caminho absoluto do arquivo `sitemap.xml` na raiz pública.
- `sitemap_url_da_pagina(array $pagina): string` — [linha 406](../../../../../gestor/bibliotecas/sitemap.php#L406)
  URL pública absoluta de uma página, respeitando o prefixo de idioma quando não for o padrão.
  Parâmetros:
  - `$pagina`: Linha da tabela `paginas`.
- `sitemap_gravar(string $xml): bool` — [linha 430](../../../../../gestor/bibliotecas/sitemap.php#L430)
  Grava o conteúdo do sitemap em disco.
- `sitemap_conteudo_proprio(string $conteudo): bool` — [linha 455](../../../../../gestor/bibliotecas/sitemap.php#L455)
  Reconhece um `sitemap.xml` gerado por ESTA biblioteca (F8 do review de 2026-08-15).
- `sitemap_legado_remover(): bool` — [linha 481](../../../../../gestor/bibliotecas/sitemap.php#L481)
  Apaga o `sitemap.xml` que versões anteriores gravavam na RAIZ pública (F8).
  Retorno: True quando algo foi removido.
- `sitemap_gerar_completo(): bool` — [linha 513](../../../../../gestor/bibliotecas/sitemap.php#L513)
  Regenera o `sitemap.xml` inteiro a partir das páginas públicas ativas.
- `sitemap_sincronizar_pagina(array $pagina, bool $remover = false, $caminhoAntigo = null): bool` — [linha 566](../../../../../gestor/bibliotecas/sitemap.php#L566)
  Sincroniza UMA página no sitemap, criando o arquivo do zero quando ele ainda não existe.
  Parâmetros:
  - `$pagina`: Linha (ou dados equivalentes) da página alterada.
  - `$remover`: Força a remoção da entrada (página excluída).
- `sitemap_sincronizar_por_id(string $id, bool $remover = false, string|null $caminhoAntigo = null): bool` — [linha 606](../../../../../gestor/bibliotecas/sitemap.php#L606)
  Recarrega os dados de uma página pelo identificador e sincroniza o sitemap.
  Parâmetros:
  - `$id`: Identificador textual da página.
  - `$caminhoAntigo`: Caminho anterior, quando o slug mudou (req-112).

<!-- c2f:extract:end -->
