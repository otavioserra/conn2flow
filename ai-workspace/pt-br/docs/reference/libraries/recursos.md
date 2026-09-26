---
title: "Biblioteca recursos.php"
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
- `recursos_dist_ativo()` — [linha 61](../../../../../gestor/bibliotecas/recursos.php#L61)
- `recursos_publicado(string $caminho): bool` — [linha 73](../../../../../gestor/bibliotecas/recursos.php#L73)
- `recursos_url(string $caminho, string|null $base = null): string` — [linha 102](../../../../../gestor/bibliotecas/recursos.php#L102)
- `recursos_versao(string $caminho, string|null $versaoPadrao = null): string` — [linha 128](../../../../../gestor/bibliotecas/recursos.php#L128)
- `recursos_url_versionada(string $caminho, string|null $versao = null, string|null $base = null): string` — [linha 149](../../../../../gestor/bibliotecas/recursos.php#L149)
- `recursos_tag_js(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [linha 162](../../../../../gestor/bibliotecas/recursos.php#L162)
- `recursos_tag_css(string $caminho, string|null $versao = null, string $extra = '', string|null $base = null): string` — [linha 177](../../../../../gestor/bibliotecas/recursos.php#L177)
- `recursos_dist_mapear_fonte(string $relativo): string` — [linha 204](../../../../../gestor/bibliotecas/recursos.php#L204)

<!-- c2f:extract:end -->
