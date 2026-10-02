---
title: "Presentations module"
description: "Slide presentations: the author writes the sections and the widget builds the navigation."
section: reference
module: presentations
sources:
  - gestor/modulos/presentations/presentations.php
  - gestor/modulos/presentations/presentations.js
  - gestor/modulos/presentations/presentations.widget.php
  - gestor/modulos/presentations/presentations.widget.js
  - gestor/modulos/presentations/presentations.json
  - gestor/modulos/presentations/resources
  - gestor/db/migrations/20261002110000_create_presentations_and_cookie_consent_tables.php
verified_at: 268f7e75
---

# `presentations` module

Turns HTML made of sections into a navigable presentation. Each `<section data-slide>` is a slide. Dots, arrows, counter, progress bar, keyboard, touch, fullscreen, scaling on small screens and printing come from the widget.

## How to use

Open `presentations/adicionar/`, give it a name, pick the `presentations-deck` model and write the slides in the HTML editor. To create a slide, copy a section:

```html
<section data-slide class="flex-col items-center justify-center p-6 md:p-12">
    <div class="w-full max-w-5xl mx-auto">
        <h2 class="text-5xl font-black">Slide title</h2>
    </div>
</section>
```

Do not set `display` on the section (nor the `flex` class): the model CSS shows only the active slide, already as flex.

For a button to lead to another slide, use `data-c2f-deck-goto="N"`, with N starting at zero.

Insert the widget into the page:

```html
<!-- widgets#presentations->render({"grupo_slug":"my-presentation"}) < -->
<div>Mockup</div>
<!-- widgets#presentations->render({"grupo_slug":"my-presentation"}) > -->
```

### Options

| Option | Default | What it does |
|---|---|---|
| `mode` | `fullscreen` | `fullscreen` covers the window; `embedded` takes the `height` inside the page |
| `height` | `600` | Height in pixels in embedded mode |
| `transition` | `random` | `random`, `fade`, `zoom`, `slide-up` or `slide-horizontal` |
| `loop` | `false` | After the last slide, goes back to the first |
| `keyboard` | `true` | Arrows, space, PageUp/PageDown, Home, End and F (fullscreen) |
| `touch` | `true` | Swipe to navigate |
| `hash` | `true` | Keeps the slide in the address (`#slide-3`) and opens on it |
| `show_arrows`, `show_dots`, `show_counter`, `show_progress`, `show_fullscreen` | `true` | Show each control |
| `autoplay`, `autoplay_speed` | `false`, `8000` | Automatic advance and time per slide, in milliseconds |

`?transition=zoom&loop=true` in the address overrides both options for that visit.

## Technical reference

The controller dispatches `listar`, `adicionar`, `editar` and `clonar`. Admin AJAX: `template-load` and `widget-preview`. The preview renders on the server and runs the same public controller as the site.

The `presentations` table has the structure of `galleries`: `id_presentations`, `id` up to 100, `name`, JSON `fields_schema`, `html`, `css`, `css_compiled`, `html_extra_head`, `plugin`, `project`, `language`, `status`, `versao`, dates and update flags. `(id, language)` is unique.

The widget counts the sections with `data-slide`, ignoring comments, and resolves the model blocks:

| Block | When it stays |
|---|---|
| `controls-arrows` | `show_arrows` and more than one slide. May appear more than once |
| `controls-dots` with `dot-item` | `show_dots` and more than one slide. `dot-item` is repeated per slide, with `[[dot#index]]` and `[[dot#number]]` |
| `controls-counter` | `show_counter` and more than one slide |
| `controls-progress` | `show_progress` and more than one slide |
| `controls-fullscreen` | `show_fullscreen` |

Global variables: `[[total]]`, `[[mode]]`, `[[height]]`, `[[transition]]`, `[[loop]]`, `[[keyboard]]`, `[[touch]]`, `[[hash]]`, `[[autoplay]]`, `[[autoplay_speed]]` and `[[preview]]`.

The public controller reads the options from the `data-*` attributes of the `[data-c2f-deck]` container. It exposes `element.c2fDeck` (`next`, `prev`, `goTo`, `current`, `total`, `setTransition`) and fires `c2f:deck:change` with `{ index, total }`. On small screens the `[data-c2f-deck-stage]` stage is drawn larger and scaled down; in fullscreen, pinch zooms and drag pans.

When printing, the model CSS shows one slide per page and hides the controls.

## Confirmed limitations

> [!WARNING]
> The slide classes only have CSS if the page showing the widget compiles them. A record edited in the panel keeps the CSS in `css_compiled`. A record declared as a project resource does not go through the editor: carry the deck HTML as the mockup inside the widget marker, so the page compiler sees the classes.

> [!CAUTION]
> With more than one presentation on the page, the keyboard goes to the whole-window one or to the last one the visitor interacted with. An embedded presentation only answers the keyboard with the pointer over it, with focus inside it or in fullscreen.

## See also

- [Galleries](galleries.md)
- [Models](admin-templates.md)
