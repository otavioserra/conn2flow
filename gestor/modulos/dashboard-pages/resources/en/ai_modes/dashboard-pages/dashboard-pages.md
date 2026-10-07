Generate the HTML of a BOARD PAGE: only the inner part of the <body>, which works as a frame for a Dashboard board (a set of widgets and objects assembled by the user). The system places the finished board inside this frame; you do NOT generate the widgets or the board content.

BOARD PLACE (REQUIRED, EXACTLY ONCE):
Write the marker below exactly once, on its own, at the point where the board should appear. On save, the system replaces this marker with the chosen board.

```
[[lousa#widget]]
```

- Do not put the marker inside an attribute, a link, a paragraph or a heading.
- Give the board a container with a width: it fills the whole width of its parent element. For centered content use something like `max-width: 1280px; margin: 0 auto`.
- Do not set a fixed height or `overflow: hidden` on the board container: the height comes from the items.

PAGE TITLE (OPTIONAL, RECOMMENDED):
Use the `[[lousa#titulo]]` marker where the title should appear. The text comes from the panel (a custom title or the page name). Wrap EVERYTHING that only makes sense with the title between the block markers below; the panel has a control that turns the title off and removes this whole block.

```
<!-- lousa-titulo < -->
<h1 class="my-page-title">[[lousa#titulo]]</h1>
<!-- lousa-titulo > -->
```

WHAT YOU MAY ADD AROUND THE BOARD:
An opening band with a label and a supporting sentence, simple navigation (anchor links or links to site pages), a call to action with a button, footer text, colored bands. All of it is ordinary HTML, editable later in the editor.

FORMATTING RULES:
- Use the markers without `@` (`[[lousa#widget]]`, `[[lousa#titulo]]`). System variables also go without `@`, for example `[[pagina#url-raiz]]` at the start of an internal link; the system converts them on save.
- Do not use `<html>`, `<head>`, `<body>`, `<script>` or `<iframe>`.
- Do not invent other `[[lousa#...]]` markers: only `widget` and `titulo` exist.
- The page is built inside the site layout (the site header and footer already exist): do not repeat the site header or footer.

STYLE:
- Prefer your own classes with a unique prefix (for example `my-page-`) and the matching CSS in the CSS field. Do not style classes starting with `c2f-lousa`, which belong to the board.
- The page must work from 360 px up to wide screens, with no horizontal scrolling. Below 640 px the board becomes a single column.
- Text with enough contrast over the chosen background; the site layout may be light or dark, so set background and text color together when you use a colored band.

MINIMAL EXAMPLE:

```
<section class="my-page">
<!-- lousa-titulo < -->
<h1 class="my-page-title">[[lousa#titulo]]</h1>
<!-- lousa-titulo > -->
[[lousa#widget]]
</section>
```
