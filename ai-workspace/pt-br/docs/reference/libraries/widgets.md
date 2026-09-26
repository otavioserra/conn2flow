---
title: "Biblioteca widgets.php"
label: "Widgets"
description: "widgets_get(): como um marcador widgets# numa página vira o HTML devolvido por uma função de módulo, e como o mesmo widget responde por AJAX."
section: reference
order: 110
sources:
  - gestor/bibliotecas/widgets.php
  - gestor/gestor.php
verified_at: a5ae8605
---

# Biblioteca `widgets.php`

Um **widget** é um trecho dinâmico que qualquer página, layout ou template pode embutir por marcador: um menu, um índice de publicações, uma galeria, um formulário. A biblioteca tem uma função, `widgets_get()`, que resolve o marcador e chama a função PHP do módulo dono do widget. O roteador a carrega sozinho quando a página tem algum marcador.

## Marcadores

A forma atual é um par de comentários com um *mockup* no meio:

```html
<!-- widgets#menus->render({"id":"principal"}) < -->
  <nav>… HTML estático, só para visualizar a página no editor …</nav>
<!-- widgets#menus->render({"id":"principal"}) > -->
```

A forma antiga, sem mockup, continua aceita: `@[[widgets#menus->render({"id":"principal"})]]@`.

A assinatura é `módulo->função(JSON)`: o módulo só tem letras, dígitos, `_` e `-`; o JSON é opcional e, se inválido, vira `[]`.

## O que `widgets_get(['id' => …, 'html' => …])` faz

1. Inclui `gestor/modulos/<modulo>/<modulo>.widget.php`, se existir.
2. Resolve a função: primeiro `<modulo_com_underscore>_<funcao>` (`publisher-highlights->render` → `publisher_highlights_render`); se não existir, `<funcao>` puro.
3. Chama a função com o array de parâmetros do JSON. O mockup chega em `$params['html']`.
4. Registra a assinatura em `$_GESTOR['widgetsToAjax']`, que vai para o JavaScript como `gestor.widgetsToAjax`.
5. Devolve o HTML retornado pela função, ou `''`.

Na página final, o roteador troca o bloco inteiro (comentários e mockup) pelo HTML devolvido. Para editores logados no Live Editor, os comentários são mantidos em volta do HTML renderizado, para o editor saber onde cada widget começa e termina.

> [!WARNING]
> Se a função do widget não existe ou devolve vazio, o bloco **não é trocado**: o mockup e os comentários ficam na página publicada. Um widget que "sumiu" costuma aparecer como o HTML de exemplo.

## AJAX

O JavaScript do widget envia `ajax=sim` e `ajaxWidgets=<assinatura>` (várias separadas por `<#;>`). O roteador chama `widgets_get()` para cada uma, agora procurando `<funcao>_ajax` (`publisher_index_render_ajax`). A função responde preenchendo `$_GESTOR['ajax-json']` e **devolvendo vazio**; qualquer string devolvida vira erro 500 `Widget AJAX error`.

Com `ajaxRegistroId` na requisição e `grupo_slug` nos parâmetros, só o widget cujo `grupo_slug` bate com o registro responde, para que vários widgets do mesmo tipo na página não respondam todos.

Widgets com AJAX no core: `forms-search`, `pages-index`, `publisher-index`.

## Escrever um widget

```php
// gestor/modulos/meu-modulo/meu-modulo.widget.php
function meu_modulo_render($params) {
    $html = $params['html'] ?? '';          // o mockup, se quiser reaproveitá-lo como modelo
    // ... montar o HTML a partir do banco ...
    return $html;
}

function meu_modulo_render_ajax($params) {
    global $_GESTOR;
    $_GESTOR['ajax-json'] = ['status' => 'Ok', 'itens' => []];
    return '';
}
```

O comportamento no navegador vai num `meu-modulo.widget.js`. Quem o inclui na página publicada é a própria função do widget, com `gestor_pagina_javascript_incluir()` (é o que fazem `publisher-index` e `pages-index`); o editor visual o carrega na pré-visualização.

## Limites

- Só módulos em `gestor/modulos/` são procurados. Um widget dentro de um plugin (`plugins/<plugin>/modules/…`) não é encontrado.
- Blocos iguais (mesma assinatura) são renderizados uma vez e o resultado vale para todos.
- Não há cache: cada visita executa a função do widget.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/widgets.php` por `c2f docs:extract` — 1 funções. Não edite dentro deste bloco.

- `widgets_get(array|false $params = false): string` — [linha 43](../../../../../gestor/bibliotecas/widgets.php#L43)

<!-- c2f:extract:end -->
