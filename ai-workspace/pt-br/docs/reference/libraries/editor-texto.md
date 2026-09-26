---
title: "Biblioteca editor-texto.php"
label: "Editor de texto"
description: "O editor de texto rico (Quill) no painel e o CSS que faz o conteúdo publicado ficar igual ao que o autor viu ao escrever."
section: reference
order: 260
sources:
  - gestor/bibliotecas/editor-texto.php
  - gestor/gestor.php
verified_at: 883d3243
---

# Biblioteca `editor-texto.php`

Tudo sobre o **Quill**, o editor de texto rico dos formulários do painel, fica aqui: a versão, as tags do editor, o CSS do conteúdo publicado e a paridade visual entre os dois. Não confundir com o editor de HTML (`html-editor.php`).

## No painel: abrir o editor

```php
gestor_incluir_biblioteca('editor-texto');
editor_texto_incluir();                     // ['paridade' => false] para não aplicar os tokens do projeto
```

`editor_texto_incluir()` acrescenta à página:
- o CSS e o JavaScript do Quill, pelo [registro de assets externos](assets-externos.md) (disco primeiro, CDN como reserva);
- `interface/quill-content.css`, o **mesmo** CSS de conteúdo que o site recebe;
- `interface/editor-texto.js`, que cria os editores sobre os `<textarea>` e mantém o campo do formulário sincronizado;
- a **paridade**: se o projeto tem `contents/tailwindcss/browser-contract.css`, as variáveis CSS (`--…`) dele são copiadas para dentro de `.ql-editor`, para o autor escrever com a fonte e as cores do site. Valores com `url()`/`data:` ou maiores que 160 caracteres ficam de fora.

A versão vem de `$_GESTOR['editor-texto-versao']` ou do registro de assets externos (`quill` 2.0.3). O `quill-content.css` foi gerado para essa versão: trocar uma sem a outra faz o editor e o site desenharem diferente.

## No site: o conteúdo publicado

O Quill grava a formatação em **classes** (`ql-align-right`, `ql-indent-2`), não em estilo inline. Sem o `quill-content.css`, a página publicada perde alinhamentos e recuos.

O roteador resolve isso sozinho (`gestor_pagina_quill()`): depois de montar a página e os widgets, `editor_texto_conteudo_detectar($html)` procura classes do Quill (comparando classe por classe, não por substring) e, se achar, inclui o CSS de `editor_texto_assets_publicacao()`. Páginas sem conteúdo do editor não carregam nada.

## Detalhe

> [!NOTE]
> A paridade define `color: var(--color-mp-ink, inherit)` dentro de `.ql-editor`. `--color-mp-ink` é um token de um projeto específico; nos demais, o valor cai no `inherit`. Para ajustar a cor do texto no editor, defina esse token no contrato do projeto ou use `--font-sans` e as variáveis próprias.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `editor_texto_assets_externos_carregar()`, `editor_texto_versao_cdn()`, `editor_texto_assets_editor()`, `editor_texto_paridade_css()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/editor-texto.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `editor_texto_assets_externos_carregar()` — [linha 38](../../../../../gestor/bibliotecas/editor-texto.php#L38)
  Garante que o registro de assets externos esteja carregado.
- `editor_texto_versao_cdn(): string` — [linha 59](../../../../../gestor/bibliotecas/editor-texto.php#L59)
  Versão do Quill servida pelo CDN.
- `editor_texto_assets_editor(string $urlRaiz = '', string $versaoAsset = '', $vendorFisico = '', $vendorPublico = ''): array{css: list<string>, javascript: list<string>}` — [linha 93](../../../../../gestor/bibliotecas/editor-texto.php#L93)
  Tags de CSS e JavaScript do editor de texto.
  Parâmetros:
  - `$urlRaiz`: Raiz pública do projeto (`$_GESTOR['url-raiz']`).
  - `$versaoAsset`: Versão dos assets do core, para cache-bust.
- `editor_texto_paridade_css(string $contrato): string` — [linha 153](../../../../../gestor/bibliotecas/editor-texto.php#L153)
  CSS que dá à área de edição a aparência da página publicada.
  Parâmetros:
  - `$contrato`: Conteúdo do `browser-contract.css` do projeto (pode ser vazio).
  Retorno: Bloco `<style>` escopado, ou string vazia quando não há o que injetar.
- `editor_texto_incluir(array $params = false): void` — [linha 213](../../../../../gestor/bibliotecas/editor-texto.php#L213)
  Inclui no pipeline da página tudo que o editor de texto precisa.
  Parâmetros:
  - `$params['paridade']`: Injeta os tokens do projeto na área de edição (padrão: true).
- `editor_texto_conteudo_detectar(string $html): bool` — [linha 271](../../../../../gestor/bibliotecas/editor-texto.php#L271)
  Detecta conteúdo formatado pelo editor de texto no HTML final.
  Parâmetros:
  - `$html`: HTML final da página, já com widgets incluídos.
- `editor_texto_assets_publicacao(string $urlRaiz = '', string $versao = ''): array` — [linha 333](../../../../../gestor/bibliotecas/editor-texto.php#L333)
  Tags do CSS de conteúdo entregue ao visitante.
  Parâmetros:
  - `$urlRaiz`: Raiz pública do projeto.
  - `$versao`: Versão do asset, para cache-bust.
  Retorno: Lista de tags `<link>`.

<!-- c2f:extract:end -->
