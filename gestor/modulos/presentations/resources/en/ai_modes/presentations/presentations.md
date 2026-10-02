Generate only the inner part of the <body> of a SLIDE PRESENTATION. The HTML is a model: an engine counts the slides and builds the navigation; a public script handles keyboard, touch, fullscreen, scaling and printing.

MANDATORY SLIDE RULE:
Each slide is a `<section data-slide>` child of the stage `[data-c2f-deck-stage]`. The `data-slide` attribute is what defines the slide: do not use any other markup for it. A slide fills the whole screen; keep the content inside a centered container with a maximum width.

```
<section data-slide class="flex-col items-center justify-center p-6 md:p-12">
    <div class="w-full max-w-5xl mx-auto">
        ...content of ONE slide...
    </div>
</section>
```

Do not set `display` on the slide (neither the `flex` class nor `hidden`): the model CSS shows only the active slide, already as flex. Use `flex-col`, `items-center` and `justify-center` to lay out the content.

CONTAINER STRUCTURE:
Keep the outer container with `data-c2f-deck` and all its `data-*` attributes, the stage `data-c2f-deck-stage` and the navigation bar. They already come in the model; change only the sections.

```
<div class="c2f-deck" data-c2f-deck data-mode="[[mode]]" data-transition="[[transition]]" data-loop="[[loop]]" data-keyboard="[[keyboard]]" data-touch="[[touch]]" data-hash="[[hash]]" data-autoplay="[[autoplay]]" data-speed="[[autoplay_speed]]" data-preview="[[preview]]" style="--c2f-deck-height: [[height]]px;">
    <div class="c2f-deck-stage" data-c2f-deck-stage>
        ...sections...
    </div>
    ...controls...
</div>
```

CONTROLS (conditional blocks; the engine keeps or removes them according to the options):

```
<!-- controls-progress < --><div class="c2f-deck-progress"><span data-c2f-deck-progress></span></div><!-- controls-progress > -->
<!-- controls-arrows < --><button type="button" data-c2f-deck-prev>‹</button><!-- controls-arrows > -->
<!-- controls-dots < -->
<div class="c2f-deck-dots">
    <!-- dot-item < --><button type="button" class="c2f-deck-dot" data-c2f-deck-dot="[[dot#index]]" aria-label="Go to slide [[dot#number]]"></button><!-- dot-item > -->
</div>
<!-- controls-dots > -->
<!-- controls-counter < --><span><span data-c2f-deck-current>1</span> / [[total]]</span><!-- controls-counter > -->
<!-- controls-arrows < --><button type="button" data-c2f-deck-next>›</button><!-- controls-arrows > -->
<!-- controls-fullscreen < --><button type="button" data-c2f-deck-fullscreen>⛶</button><!-- controls-fullscreen > -->
```

The `dot-item` block is repeated once per slide. Do not write the dots by hand or number the slides: counting is automatic.

IMAGE SLIDE:
A slide that is just an image uses `data-slide-type="image"` and an `<img class="c2f-slide-image" data-fit="contain">` (or `data-fit="cover"` to fill the slide, possibly cropping). The model CSS handles the size.

SLIDE TITLE:
`data-title="..."` on the section names the slide in the slide board of the panel. It does not show in the presentation.

JUMPING BETWEEN SLIDES:
For a button or link inside a slide to lead to another one, use `data-c2f-deck-goto="N"`, with N starting at zero. Do not use `onclick` or global functions.

PLACEHOLDER FORMAT RULE:
Always use `[[name]]`, without `@`. The pipeline converts it to `@[[name]]@` on save.

GLOBAL VARIABLES:
- `[[total]]` — number of slides
- `[[mode]]` — `fullscreen` or `embedded`
- `[[height]]` — height in pixels in embedded mode
- `[[transition]]`, `[[loop]]`, `[[keyboard]]`, `[[touch]]`, `[[hash]]`, `[[autoplay]]`, `[[autoplay_speed]]`, `[[preview]]` — options read by the script

DOT VARIABLES (only inside `dot-item`):
- `[[dot#index]]` — position starting at zero
- `[[dot#number]]` — position starting at one

STYLE:
Write the slide content with classes from the CSS framework of the page. Large text and little text per slide; high contrast; one idea per slide. Do not include `<script>`.
