Generate an HTML page with only the inner part of the <body> containing an IMAGE GALLERY of the site (carousel, slider or grid). This page will be used as a TEMPLATE in which an engine dynamically renders a LIST of images curated by the user.

MANDATORY IMAGE REPETITION RULE:
The whole HTML snippet that represents ONE gallery image (e.g. a `<figure>`, a slide, a card) MUST be wrapped between the loop markers below:

```
<!-- item < -->
...HTML of ONE image, containing the @[[item#variable_name]]@ placeholders...
<!-- item > -->
```

At runtime the engine repeats the content between `<!-- item < -->` and `<!-- item > -->` once per curated image, replacing each placeholder with the real value. Variables outside this block are NOT repeated (they belong to the gallery container, arrows, pagination, etc).

If the user wants to show a message when the gallery has no images, wrap that snippet in the optional block:

```
<!-- no-item < -->
...HTML shown when the gallery is empty...
<!-- no-item > -->
```

MANDATORY IMAGE LINKS RULE:
Each image (or slide/card) inside the `<!-- item < -->` loop must contain an anchor `<a>` tag wrapping the `<img>` element. Use the placeholders `href="[[item#link-url]]"`, `target="[[item#link-target]]"`, and `class="[[item#link-css-classes]]"` in this anchor to ensure individual click functionality. Example:

```
<!-- item < -->
<figure>
    <a href="[[item#link-url]]" target="[[item#link-target]]" class="[[item#link-css-classes]]">
        <img src="[[item#img-src]]" alt="[[item#nome]]">
    </a>
    <figcaption>[[item#legenda]]</figcaption>
</figure>
<!-- item > -->
```

OPTIONAL CONTROLS (CAROUSEL/SLIDER):
The main gallery container must have the `conn2flow-gallery` class and expose the behavior attributes using the global variables (replaced by `true`/`false`/number at runtime):

```
<section class="conn2flow-gallery" data-autoplay="[[autoplay]]" data-speed="[[autoplay_speed]]" data-loop="[[loop]]">
```

The slides must live inside a container with the `gallery-slides-wrapper` class, and each slide must have the `gallery-slide` class.

Navigation arrows (shown only when the user enables `show_arrows`): wrap them in the conditional block, using the `gallery-prev` and `gallery-next` classes:

```
<!-- controls-arrows < -->
<button type="button" class="gallery-prev">‹</button>
<button type="button" class="gallery-next">›</button>
<!-- controls-arrows > -->
```

Pagination dots (shown only when the user enables `show_dots`): wrap the `gallery-dots` container in the conditional block and, INSIDE it, use the `dot-item` repetition block (one dot per image). Each dot must have the `gallery-dot` class, the `data-index="[[dot#index]]"` attribute and the active class `[[dot#active-class]]` (auto-filled on the first dot):

```
<!-- controls-dots < -->
<div class="gallery-dots">
    <!-- dot-item < -->
    <button type="button" class="gallery-dot [[dot#active-class]]" data-index="[[dot#index]]"></button>
    <!-- dot-item > -->
</div>
<!-- controls-dots > -->
```

PLACEHOLDER FORMATTING RULE:
- Always use the `[[item#variable_name]]` format (item is literal/static; variable_name is dynamic).
- Use the version without `@` in your output — the internal pipeline converts it to `@[[item#variable_name]]@` automatically when saving.

VARIABLES AVAILABLE FOR EACH IMAGE:
- `[[item#img-src]]` — public image URL (use in the `src` attribute of the `<img>` tag)
- `[[item#caminho]]` — relative path of the original file
- `[[item#nome]]` — file name (use in `alt`/`title`)
- `[[item#legenda]]` — custom image caption
- `[[item#link-url]]` — destination URL of the image link
- `[[item#link-target]]` — link target (`_self` or `_blank`)
- `[[item#link-css-classes]]` — extra CSS classes of the image link

GLOBAL VARIABLES (CONTROLS, outside the `item` block):
- `[[show_arrows]]` — show arrows (`true`/`false`)
- `[[show_dots]]` — show dots (`true`/`false`)
- `[[autoplay]]` — autoplay (`true`/`false`)
- `[[autoplay_speed]]` — transition time in milliseconds (number)
- `[[loop]]` — infinite loop (`true`/`false`)

You can create dummy text beyond these markers as instructed by the user, but if they ask for something more direct, keep only the placeholders. Return the HTML code using markdown ```html ``` and, if you need extra CSS, return it with markdown ```css ```.

This page will use the CSS framework `{{framework_css}}`. Use the `<section class="conn2flow-gallery"></section>` tag for the main gallery container according to the request context.

You do not need to explain how to build the page since only the generated HTML and CSS will be used, processed by a technical routine transparent to the end user.

Every newly generated session must have an incremental marker <NUMBER> for the current session. If it is a modification, keep the increment value and modify the content. Create a simple <TITLE> for each session and update it according to each session's context. Put this title in the `data-title` attribute:
Example of creating a session:
<section class="conn2flow-gallery" data-id="<NUMBER>" data-title="<TITLE>">
HTML generated by you (including the <!-- item < --> ... <!-- item > --> repetition block)
</section>
HTML generated in a previous interaction: 
```html
{{html}}
```
CSS generated in a previous interaction: 
```css
{{css}}
```
Variables available to include in the template formatting (each line is an available item field):
[variables] // List of variables below, one per line (1-n)
variable_name

Variables available for inclusion:
```variables
{{variables}}
```
The variables sent above must be used inside the repetition block `<!-- item < --> ... <!-- item > -->` (image variables) or in the container/controls (global variables). The `[[item#img-src]]` must go in the image `src` attribute; the `[[item#legenda]]` in the descriptive text. If you receive existing HTML in this interaction, keep the variable order as explained, or follow a new order if the user explicitly asks. Try to create an organized and visually pleasant template according to the requested gallery type (grid, carousel, slider, masonry, etc.) and the provided HTML. On the other hand, if the user asks for something contradictory to these instructions, always prioritize the user's request.

Below a user with or without HTML knowledge described their need:
