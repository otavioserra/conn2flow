---
title: "Galleries module"
description: "Curating and rendering image galleries."
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

# `galleries` module

Maintains an ordered image list for a public widget. Each item can have a caption and link; the record keeps an editable copy of template HTML and display options.

## How to use

Open `galleries/` and `galleries/adicionar/`, enter a name and template, select images, and set their order. Each image can link to a page, a manual URL, or the latest publication from a publisher. Configure height, image position, arrows, dots, autoplay, and loop. Edit at `galleries/editar/` or duplicate at `galleries/clonar/`. Insert the widget:

```html
<!-- widgets#galleries->render({"grupo_slug":"home"}) < -->
<div>Mockup</div>
<!-- widgets#galleries->render({"grupo_slug":"home"}) > -->
```

Routes are identical in both languages; the current language selects the record.

## Technical reference

The controller dispatches `listar`, `adicionar`, `editar`, and `clonar`; the shared interface handles status and deletion. Admin AJAX: `template-load`, `widget-preview`, `pages-search`, and `pages-fetch`. Admin JS maintains selection, order, and options; public JS implements arrows, dots, autoplay, and loop. The JSON declares no hooks or `hooks.api`.

`galleries` contains numeric `id_galleries`, `id` up to 100, `name` up to 255, JSON `fields_schema`, MEDIUMTEXT `html` and `css_compiled`, TEXT `css`, `html_extra_head`, `plugin`, `language`, `status`, `versao`, timestamps, and update flags. `(id, language)` is unique. There is no `publisher_id` column: publisher links are per-item options.

`fields_schema.selected_items` is an ordered array of objects with `id`, `caminho`, `imgSrc`, `nome`, `legenda`, and optionally `link_type`, `link_page_id`, `link_publisher_id`, `link_order_by`, `link_url`, `link_target`, and `link_css_classes`. `link_type` accepts `nenhum`, `pagina`, `publicador`, `link-custom`, `link-css-classes`, and `link-action`. Global settings include `show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`, `height`, `margin_lateral`, and `image_position`.

The widget requires an active record and nonempty HTML in the database. It repeats `<!-- item < -->` with `item#img-src`, `item#caminho`, `item#nome`, `item#legenda`, `item#link-url`, `item#link-target`, and `item#link-css-classes`; it uses `no-item` when the list is empty. `controls-arrows`, `controls-dots`, and `dot-item` control navigation. The image source prefers `caminho` over `imgSrc`; relative paths get the site root. Page links are resolved in one query; publisher links look up the latest active page according to `link_order_by`. Templates: `galleries-grid`, `galleries-carousel`, `galleries-masonry`, `galleries-slider`, and `galleries-estados`.

## Confirmed limitations

> [!WARNING]
> `galleries.html` stores a template copy made at selection time. Changing the original template does not update existing galleries. The renderer resolves classes for unlinked items from this copy, then from the source template and `galleries-estados`, but that does not refresh the rest of the HTML.

> [!CAUTION]
> Page and publisher link resolution filters `status='A'` and language, but does not check `sem_permissao` or the publication window. Manual `link_url` becomes the link destination; treat it as operator-controlled content. A record without HTML returns empty output without displaying marker mockup.

## See also

- [Admin pages](admin-paginas.md)
- [Publications](publisher-pages.md)
