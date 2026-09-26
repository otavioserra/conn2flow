---
title: "Biblioteca sitemap.php"
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
- caminho que não seja rota de sistema do Gestor nem rota de fluxo (`sitemap_caminho_nao_indexavel()`): callbacks de OAuth, `signin-2fa`, `validate-user`, processadores de formulário, `pagina-de-impressao`, qualquer caminho começando por `admin-`, e páginas de desfecho cujo último segmento é ou termina em `confirmation`, `success`, `error`, `failure` ou `cancel` (`contato-success`, `checkout/error`), além de `…/payment`, `…/checkout` e `…/processing` em caminhos compostos;
- estar dentro da janela `data_publicacao_inicio`/`fim`.

`/signin/`, `/signup/` e `/forgot-password/` entram. A URL leva o prefixo do idioma quando ele não é o padrão (`/en/docs/`), e `lastmod` é a `data_modificacao` da página.

## Quando é atualizado

- **Incremental**, a cada salvar, clonar, excluir ou trocar status no `admin-paginas`, no `publisher-pages` e na edição pelo Live Editor (`sitemap_sincronizar_por_id()`, que chama `sitemap_sincronizar_pagina()`): só a URL daquela página entra, muda ou sai. Se o caminho mudou, a URL antiga sai antes de a nova entrar.
- **Completo** (`sitemap_gerar_completo()`), só quando o arquivo não existe, está corrompido ou a página editada sumiu do banco. A geração completa também regrava o `robots.txt`.

> [!WARNING]
> **O deploy não atualiza o sitemap.** Páginas criadas ou alteradas pelo pipeline (`project:update-all`, recursos de módulo, publicações geradas pelo `docs:build`) só entram quando alguém as edita no painel ou quando o arquivo é regenerado por inteiro. Para forçar, apague `gestor/assets/sitemap.xml` no servidor: a próxima edição de qualquer página o gera de novo. Nenhum comando do CLI faz isso hoje.

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
- `sitemap_caminho_nao_indexavel(string $caminho): bool` — [linha 99](../../../../../gestor/bibliotecas/sitemap.php#L99)
- `sitemap_robots_montar(array $params = Array()): string` — [linha 168](../../../../../gestor/bibliotecas/sitemap.php#L168)
- `sitemap_robots_caminho_arquivo(): string` — [linha 216](../../../../../gestor/bibliotecas/sitemap.php#L216)
- `sitemap_robots_gravar(): bool` — [linha 226](../../../../../gestor/bibliotecas/sitemap.php#L226)
- `sitemap_xml_montar(array $urls = Array()): string` — [linha 256](../../../../../gestor/bibliotecas/sitemap.php#L256)
- `sitemap_data_w3c(string|null $data = null): string|null` — [linha 285](../../../../../gestor/bibliotecas/sitemap.php#L285)
- `sitemap_xml_upsert(string $xml, string $loc, string|null $lastmod = null): string` — [linha 305](../../../../../gestor/bibliotecas/sitemap.php#L305)
- `sitemap_xml_remover(string $xml, string $loc): string` — [linha 336](../../../../../gestor/bibliotecas/sitemap.php#L336)
- `sitemap_caminho_arquivo(): string` — [linha 368](../../../../../gestor/bibliotecas/sitemap.php#L368)
- `sitemap_url_da_pagina(array $pagina): string` — [linha 397](../../../../../gestor/bibliotecas/sitemap.php#L397)
- `sitemap_gravar(string $xml): bool` — [linha 421](../../../../../gestor/bibliotecas/sitemap.php#L421)
- `sitemap_conteudo_proprio(string $conteudo): bool` — [linha 446](../../../../../gestor/bibliotecas/sitemap.php#L446)
- `sitemap_legado_remover(): bool` — [linha 472](../../../../../gestor/bibliotecas/sitemap.php#L472)
- `sitemap_gerar_completo(): bool` — [linha 504](../../../../../gestor/bibliotecas/sitemap.php#L504)
- `sitemap_sincronizar_pagina(array $pagina, bool $remover = false, $caminhoAntigo = null): bool` — [linha 557](../../../../../gestor/bibliotecas/sitemap.php#L557)
- `sitemap_sincronizar_por_id(string $id, bool $remover = false, string|null $caminhoAntigo = null): bool` — [linha 597](../../../../../gestor/bibliotecas/sitemap.php#L597)

<!-- c2f:extract:end -->
