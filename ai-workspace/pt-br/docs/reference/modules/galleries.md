---
title: "Módulo galleries"
description: "Curadoria e renderização de galerias de imagens."
section: reference
module: galleries
sources:
  - gestor/modulos/galleries/galleries.php
  - gestor/modulos/galleries/galleries.js
  - gestor/modulos/galleries/galleries.widget.php
  - gestor/modulos/galleries/galleries.widget.js
  - gestor/modulos/galleries/galleries.json
  - gestor/modulos/galleries/resources
  - gestor/db/migrations/20260701120000_create_galleries_table.php
verified_at: 7bf08fe1
---

# Módulo `galleries`

Mantém uma lista ordenada de imagens para exibição por widget. Cada item pode ter legenda e link; o registro guarda uma cópia editável do HTML do template e as opções visuais.

## Como usar

Abra `galleries/` e `galleries/adicionar/`, informe nome e template, selecione imagens e ajuste a ordem. Cada imagem pode apontar para uma página, uma URL manual ou a publicação mais recente de um publicador. Configure altura, posição da imagem, setas, indicadores, autoplay e loop. Depois edite em `galleries/editar/` ou duplique em `galleries/clonar/`. Insira o widget:

```html
<!-- widgets#galleries->render({"grupo_slug":"home"}) < -->
<div>Mockup</div>
<!-- widgets#galleries->render({"grupo_slug":"home"}) > -->
```

As rotas são idênticas nos dois idiomas; o registro é selecionado pelo idioma atual.

## Referência técnica

O controlador despacha `listar`, `adicionar`, `editar`, `clonar`; a interface compartilhada cuida de status e exclusão. AJAX administrativo: `template-load`, `widget-preview`, `pages-search` e `pages-fetch`. O JS do painel mantém a seleção, ordenação e opções; o JS público implementa setas, indicadores, autoplay e loop. O JSON não declara hooks nem `hooks.api`.

`galleries` contém `id_galleries` numérico, `id` até 100, `name` até 255, JSON `fields_schema`, `html` e `css_compiled` MEDIUMTEXT, `css` TEXT, `html_extra_head`, `plugin`, `language`, `status`, `versao`, datas e flags de atualização. `(id, language)` é único. Não há `publisher_id` na tabela: vínculos com publicadores são opções dos itens.

`fields_schema.selected_items` é uma lista ordenada de objetos com `id`, `caminho`, `imgSrc`, `nome`, `legenda` e, opcionalmente, `link_type`, `link_page_id`, `link_publisher_id`, `link_order_by`, `link_url`, `link_target` e `link_css_classes`. `link_type` aceita `nenhum`, `pagina`, `publicador`, `link-custom`, `link-css-classes` e `link-action`. Opções globais: `show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`, `height`, `margin_lateral` e `image_position`.

O widget exige registro ativo e HTML não vazio no banco. Repete `<!-- item < -->` usando `item#img-src`, `item#caminho`, `item#nome`, `item#legenda`, `item#link-url`, `item#link-target` e `item#link-css-classes`; usa `no-item` se a lista estiver vazia. `controls-arrows`, `controls-dots` e `dot-item` controlam navegação. A origem da imagem prefere `caminho` a `imgSrc`; caminhos relativos recebem a raiz do site. Os links para páginas são resolvidos em lote; os de publicador procuram a página ativa mais recente segundo `link_order_by`. Templates: `galleries-grid`, `galleries-carousel`, `galleries-masonry`, `galleries-slider` e `galleries-estados`.

## Limitações confirmadas

> [!WARNING]
> `galleries.html` guarda uma cópia do template feita na seleção. Alterar o template original não atualiza galerias existentes. O renderizador busca classes para itens sem link primeiro nessa cópia, depois no template de origem e em `galleries-estados`, mas isso não substitui a atualização do restante do HTML.

> [!CAUTION]
> A resolução de links de página e publicador filtra `status='A'` e idioma, mas não testa `sem_permissao` nem a janela de publicação. `link_url` manual entra como destino do link; trate-o como conteúdo confiado ao operador. Um registro sem HTML retorna vazio, sem exibir o mockup do marcador.

## Veja também

- [Páginas administrativas](admin-paginas.md)
- [Publicações](publisher-pages.md)
