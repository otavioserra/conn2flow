---
title: "Biblioteca recursos.php"
label: "URLs de assets"
description: "URLs de assets estáticos: entrega direta de public_html/dist/ quando o arquivo foi publicado, com fallback para o controlador arquivo-estatico."
section: reference
order: 120
sources:
  - gestor/bibliotecas/recursos.php
  - gestor/config.php
verified_at: 8768245a
---

# Biblioteca `recursos.php`

Resolve a URL de um asset estático (JS, CSS, imagem, fonte) do Gestor ou de um módulo. Carregada sempre pelo `config.php`.

O código do Gestor fica fora da pasta pública. Há duas formas de um asset chegar ao navegador:

1. **Publicado em `public_html/dist/`**: o `c2f assets:publish` (chamado pelo `resources:sync` e pelo deploy) copia os assets para lá e grava o inventário `dist/.manifest.json`. O Nginx ou o Apache servem o arquivo direto do disco, sem PHP.
2. **Pelo controlador `arquivo-estatico`**: sem publicação, a URL histórica (`<url-raiz>interface/interface.js`) passa pelo PHP, que lê o arquivo de `gestor/assets/` ou do módulo.

`recursos_url()` escolhe o `dist/` **só quando o arquivo consta no manifesto**. Em desenvolvimento, numa instalação nova ou com um asset ainda não publicado, a URL continua sendo a antiga, e nada quebra.

## Uso

```php
echo recursos_tag_js('interface/interface.js', $versao, 'defer');
echo recursos_tag_css('admin-arquivos/css.css');
$url = recursos_url_versionada('images/logo.png');
```

| Função | Faz |
|---|---|
| `recursos_url($caminho, $base = null)` | URL em `dist/` se publicado; senão `<url-raiz ou $base><caminho>` |
| `recursos_versao($caminho, $padrao = null)` | O hash do conteúdo, do manifesto; senão `$padrao`; senão `gestor_asset_version()` |
| `recursos_url_versionada()` | `recursos_url()` + `?v=<versão>` |
| `recursos_tag_js()` / `recursos_tag_css()` | A tag `<script>` / `<link rel="stylesheet">` pronta |
| `recursos_publicado($caminho)` / `recursos_dist_ativo()` | Consultas ao manifesto carregado em `$_GESTOR['dist-manifest']` |
| `recursos_caminho_normalizar($caminho)` | Tira `?`/`#`, barras extras e iniciais; recusa `..` e byte nulo (`''`) |
| `recursos_dist_mapear_fonte($relativo)` | O contrato fonte → `dist/`, compartilhado com o `assets:publish` |

## O contrato de caminhos

O caminho dentro de `dist/` é o mesmo da URL pública:

| Fonte no Gestor | Em `dist/` e na URL |
|---|---|
| `assets/<caminho>` | `<caminho>` |
| `modulos/<m>/<m>.js` / `<m>.css` | `<m>/js.js` / `<m>/css.css` |
| `modulos/<m>/<m>.<opcao>.js` | `<m>/<opcao>.js` |

O `.min.js` não tem caminho próprio: é a variante preferida do arquivo de autoria, escolhida na publicação e, em runtime, pelo `arquivo-estatico`.

A pasta `dist/` usa `url-raiz-sem-lang`: um mesmo arquivo não ganha uma URL por idioma. `dist-ativo` liga sozinho em produção e pode ser forçado com `ASSETS_DIST` no `.env`; `PUBLIC_PATH` diz onde está a pasta pública quando o Gestor roda pelo CLI ([variáveis globais](../../concepts/global-variables.md)).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/recursos.php` por `c2f docs:extract` — 9 funções. Não edite dentro deste bloco.

- `recursos_caminho_normalizar(string $caminho): string` — [linha 43](../../../../../gestor/bibliotecas/recursos.php#L43)
  Normaliza um caminho de asset para a forma usada como chave do manifesto.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset, como aparece na URL.
  Retorno: Caminho normalizado, ou '' quando o caminho é inválido.
- `recursos_dist_ativo()` — [linha 61](../../../../../gestor/bibliotecas/recursos.php#L61)
  Indica se a instalação tem assets publicados e habilitados para entrega direta.
- `recursos_publicado(string $caminho): bool` — [linha 73](../../../../../gestor/bibliotecas/recursos.php#L73)
  Informa se um asset consta no manifesto publicado em `dist/`.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset (com ou sem query string).
- `recursos_url(string $caminho, string|null $base = null): string` — [linha 102](../../../../../gestor/bibliotecas/recursos.php#L102)
  URL pública de um asset.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset, opcionalmente com query string.
  - `$base`: Raiz pública usada no fallback; null usa `url-raiz`.
  Retorno: URL pronta para o atributo `src`/`href`.
- `recursos_versao(string $caminho, string|null $versaoPadrao = null): string` — [linha 128](../../../../../gestor/bibliotecas/recursos.php#L128)
  Token de cache busting de um asset publicado.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset.
  - `$versaoPadrao`: Versão usada quando o asset não está publicado.
- `recursos_url_versionada(string $caminho, string|null $versao = null, string|null $base = null): string` — [linha 149](../../../../../gestor/bibliotecas/recursos.php#L149)
  URL de um asset já com o parâmetro de cache busting.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão a usar quando o asset não está publicado.
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_tag_js(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [linha 162](../../../../../gestor/bibliotecas/recursos.php#L162)
  Tag `<script>` de um asset do gestor.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão usada quando o asset não está publicado.
  - `$extra`: Atributos adicionais já formatados (ex.: `defer`).
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_tag_css(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [linha 177](../../../../../gestor/bibliotecas/recursos.php#L177)
  Tag `<link rel="stylesheet">` de um asset do gestor.
  Parâmetros:
  - `$caminho`: Caminho relativo do asset.
  - `$versao`: Versão usada quando o asset não está publicado.
  - `$extra`: Atributos adicionais já formatados (ex.: `data-c2f-css-role="quill"`).
  - `$base`: Raiz pública do fallback; null usa `url-raiz`.
- `recursos_dist_mapear_fonte(string $relativo): string` — [linha 204](../../../../../gestor/bibliotecas/recursos.php#L204)
  Contrato de mapeamento entre o arquivo FONTE no gestor e o caminho publicado em `dist/`.
  Parâmetros:
  - `$relativo`: Caminho do arquivo relativo à raiz do gestor, com `/` como separador.
  Retorno: Caminho dentro de `dist/`, ou '' quando o arquivo não é publicável.

<!-- c2f:extract:end -->
