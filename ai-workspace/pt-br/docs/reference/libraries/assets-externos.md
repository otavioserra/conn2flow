---
title: "Biblioteca assets-externos.php"
label: "Assets externos"
description: "Registro único das bibliotecas de terceiros (jQuery, Fomantic, CodeMirror, Quill, Tailwind no navegador…) com versão fixa, servidas do disco e com CDN só como fallback."
section: reference
order: 250
sources:
  - gestor/bibliotecas/assets-externos.php
  - cli/src/Commands/AssetsVendorCommand.php
verified_at: 883d3243
---

# Biblioteca `assets-externos.php`

Todo JavaScript e CSS de terceiros que o Gestor usa passa por um **registro único**, com a versão fixada. Um módulo não escreve a tag: pede `assets_externos_incluir('codemirror')`.

O arquivo é servido de `gestor/assets/vendor/<biblioteca>/<versão>/` quando existe, e do CDN do registro quando não existe. Assim o IP do visitante não vai para CDNs de terceiros, e uma publicação nova no npm não entra em produção sem revisão.

## Bibliotecas registradas

| Nome | Versão | Arquivos |
|---|---|---|
| `jquery` | 3.7.1 | `jquery.min.js` |
| `fomantic-ui` | 2.9.4 | `semantic.min.css`/`.js` + fontes e ícones do tema |
| `fomantic-icon` | 2.9.4 | Só os ícones, para layouts Tailwind |
| `codemirror` | 5.65.20 | Núcleo, tema, *addons* e modos HTML/CSS/JS/Markdown |
| `quill` | 2.0.3 | `quill.snow.css`, `quill.min.js` |
| `sortablejs` | 1.15.6 | `Sortable.min.js` |
| `lucide` | 0.544.0 | `lucide.min.js` |
| `qrcodejs` | 1.0.0 | `qrcode.min.js` |
| `fingerprintjs` | 4 | `fp.min.js` |
| `tailwindcss-browser` | 4.3.0 | Compilador do Tailwind usado no editor visual; acompanha a versão do build offline |

Os arquivos das instalações novas já vêm em `gestor/assets/vendor/`. Para baixar o que faltar (ou uma versão nova depois de mudar o registro):

```bash
c2f assets:vendor [--lib=codemirror] [--listar] [--forcar]
```

A entrada `arquivos` de uma biblioteca lista o que é baixado mas não vira tag (as fontes que o CSS pede por caminho relativo). Sem elas, servido do disco, o Fomantic fica sem ícones.

## Uso

```php
gestor_incluir_biblioteca('assets-externos');
assets_externos_incluir('sortablejs');                  // false se o nome não está registrado

$tags = assets_externos_tags('quill', $vendorFisico, $vendorPublico);   // ['css' => [...], 'js' => [...]]
$mapa = assets_externos_urls_js(['codemirror']);        // biblioteca => arquivo => URL, para o JavaScript
```

`assets_externos_urls_js()` existe para o que monta tags no navegador (os `iframe` de pré-visualização e a barra de edição): recebem as mesmas URLs, sem conhecer versão nem host.

## Armadilhas

- A troca para o CDN é **silenciosa**: um arquivo ausente em `vendor/` sai do CDN sem aviso. Depois de mudar uma versão, rode `c2f assets:vendor`.
- `assets_externos_incluir()` põe as tags de CSS pela `gestor_pagina_css_incluir($tag)`, que as empilha na fila de **JavaScript** do fim da página ([gestor.php](gestor.md)). O CSS funciona, mas carrega depois do conteúdo.
- A URL local usa `url-raiz`, que inclui o prefixo de idioma quando ele está na URL.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `assets_externos_registro()`, `assets_externos_url()`, `assets_externos_urls_map()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/assets-externos.php` por `c2f docs:extract` — 6 funções. Não edite dentro deste bloco.

- `assets_externos_registro(): array<string, array{versao: string, cdn: string, css: list<string>, js: list<string>}>` — [linha 47](../../../../../gestor/bibliotecas/assets-externos.php#L47)
  Registro central: nome => versão + arquivos.
- `assets_externos_url(array $lib, string $nome, string $arquivo, string $vendorFisico, string $vendorPublico): string` — [linha 217](../../../../../gestor/bibliotecas/assets-externos.php#L217)
  URL de um arquivo da biblioteca: local quando existe, CDN como fallback.
  Parâmetros:
  - `$lib`: Entrada do registro.
  - `$nome`: Identificador da biblioteca (subdiretório em `vendor/`).
  - `$arquivo`: Nome do arquivo dentro da biblioteca.
  - `$vendorFisico`: Caminho físico de `assets/vendor/` (com separador final).
  - `$vendorPublico`: URL pública de `vendor/` (com barra final).
- `assets_externos_tags(string $nome, string $vendorFisico = '', string $vendorPublico = ''): array{css: list<string>, js: list<string>}` — [linha 238](../../../../../gestor/bibliotecas/assets-externos.php#L238)
  Tags de uma biblioteca registrada.
  Parâmetros:
  - `$nome`: Identificador no registro.
  - `$vendorFisico`: Caminho físico de `assets/vendor/`.
  - `$vendorPublico`: URL pública de `vendor/`.
- `assets_externos_urls_map(list<string> $nomes = Array(), string $vendorFisico = '', string $vendorPublico = ''): array<string, array<string, string>>` — [linha 279](../../../../../gestor/bibliotecas/assets-externos.php#L279)
  Mapa `biblioteca => arquivo => URL` das bibliotecas pedidas (req-156).
  Parâmetros:
  - `$nomes`: Identificadores no registro; vazio devolve todas as bibliotecas.
  - `$vendorFisico`: Caminho físico de `assets/vendor/`.
  - `$vendorPublico`: URL pública de `vendor/`.
- `assets_externos_urls_js(list<string> $nomes = Array()): array<string, array<string, string>>` — [linha 308](../../../../../gestor/bibliotecas/assets-externos.php#L308)
  `assets_externos_urls_map()` resolvido com os caminhos do ambiente corrente (req-156).
  Parâmetros:
  - `$nomes`: Identificadores no registro.
- `assets_externos_incluir(string $nome): bool` — [linha 329](../../../../../gestor/bibliotecas/assets-externos.php#L329)
  Inclui uma biblioteca de terceiro no pipeline da página.
  Parâmetros:
  - `$nome`: Identificador no registro (ex.: `sortablejs`).
  Retorno: false quando a biblioteca não está registrada.

<!-- c2f:extract:end -->
