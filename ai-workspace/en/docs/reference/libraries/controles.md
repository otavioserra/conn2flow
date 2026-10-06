---
title: "controles.php library"
description: "Shared administrative controls, native form elements and input masks."
section: reference
order: 100
sources:
  - gestor/bibliotecas/controles.php
  - gestor/assets/interface/controles.js
  - gestor/assets/interface/campo-moeda.js
verified_at: 914c7b10
---

# controles.php library


Helpers return HTML; JavaScript enhances behavior while retaining native form submission. [Panel conventions](../../concepts/admin-interface.md) describe classes, masks, floating selects and formatted messages.

- `controles_textos`: Reads global controles-* variables for JS.
- `controles_incluir`: Queues CSS, runtime, masks and texts once per page.
- `controles_esc`: Escapes values using ENT_QUOTES and UTF-8.
- `controles_select`: Builds a native select, options/groups, search, multiple selection and AJAX configuration.
- `controles_chave`: Builds a checkbox; optionally includes a hidden zero value when disabled.
- `controles_abas`: Escapes labels; prepared content is unescaped HTML.
- `controles_atributos`: Validates names; true becomes a valueless attribute, false/null are omitted.
- `controles_campo`: Groups label, help, error and aria-describedby; types include text, area, select and switch.
- `controles_botao`: Builds a button with variant and Lucide icon; escapes the label.

## Control runtime

The internal observarSelects observer initializes selects inserted after page load and rebuilds cloned control wrappers, whose events are not copied by cloneNode. Selects inside template are excluded until inserted into the document. Selection retains the native select for form submission.

c2fControles.formatado allows only b, strong, i, em, u, br and code, without attributes. Percentage and currency masks are loaded by controles_incluir: they display pt-BR numbers and submit decimals with a dot during formdata; percentages are limited to 0–100 with two decimal places.

## Generated function reference

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/controles.php` by `c2f docs:extract` — 9 functions. Do not edit inside this block.

- `controles_textos()` — [line 14](../../../../../gestor/bibliotecas/controles.php#L14)
  Textos dos controles para o JavaScript (variáveis globais `controles-*`).
- `controles_incluir()` — [line 25](../../../../../gestor/bibliotecas/controles.php#L25)
  Enfileira o runtime e os textos uma vez por página.
- `controles_esc($valor)` — [line 41](../../../../../gestor/bibliotecas/controles.php#L41)
- `controles_select(array $params)` — [line 52](../../../../../gestor/bibliotecas/controles.php#L52)
  Select com busca (e AJAX, opcional) sobre um <select> nativo.
  Parameters:
  - `$params`: name, id, opcoes ([valor => rótulo] ou [['valor','rotulo','grupo']]), valor (string ou lista),
- `controles_chave(array $params)` — [line 96](../../../../../gestor/bibliotecas/controles.php#L96)
  Chave liga/desliga sobre um checkbox nativo (envia `1` quando ligada).
- `controles_abas(array $abas, $ativa = null)` — [line 112](../../../../../gestor/bibliotecas/controles.php#L112)
  Abas: [['id' => 'dados', 'rotulo' => '...', 'conteudo' => '<html já pronto>'], ...]. O conteúdo é HTML do próprio sistema (não escapado); os rótulos são escapados.
- `controles_atributos(array $atributos)` — [line 134](../../../../../gestor/bibliotecas/controles.php#L134)
  Atributos HTML escapados; `true` vira atributo sem valor e `false`/`null` some.
- `controles_campo(array $p)` — [line 150](../../../../../gestor/bibliotecas/controles.php#L150)
  Campo completo do formulário.
  Parameters:
  - `$p`: tipo (texto|email|senha|numero|data|data-hora|url|area|select|chave|oculto), name, id, rotulo, ajuda,
- `controles_botao(array $p)` — [line 197](../../../../../gestor/bibliotecas/controles.php#L197)
  Botão padrão. variante: primario (padrão) | secundario | perigo | fantasma; tipo: button|submit; url vira <a>. `icone` é o nome de um ícone Lucide (`data-lucide`), desenhado pelo layout.

<!-- c2f:extract:end -->
