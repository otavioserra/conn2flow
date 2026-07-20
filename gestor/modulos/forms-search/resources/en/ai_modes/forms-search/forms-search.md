Generate only the inner HTML of `<body>` for a SEARCH WIDGET. The component submits with GET, has one intrinsic text-search field, and may repeat optional extra filters configured by the user.

MANDATORY CONTRACT:
- Use `<form class="conn2flow-search-form ..." data-form-id="[[form_id]]" action="[[form_action]]" method="get">`.
- Do not create settings, fields, or copy related to email, form-submission AJAX, reCAPTCHA, or success/error redirects.
- The main search field is not part of the extra-fields loop. Wrap exactly one `<input type="search">` with:
```html
<!-- input-search < -->
<input type="search" placeholder="Search">
<!-- input-search > -->
```
- Do not assign another name to this input; the renderer always injects `name="search"`, `required`, ids, and ARIA attributes.
- Include an empty floating box inside a positioned ancestor:
```html
<!-- results-box < -->
<div class="forms-search-results ..."></div>
<!-- results-box > -->
```
- Keep the `type="submit"` button outside both cells. A click or Enter without a selected result navigates to `[[form_action]]`; an empty action falls back to `pages-index-search/`.

EXTRA FIELDS:
All HTML for ONE optional filter must be inside `<!-- item < -->` and `<!-- item > -->`. The engine repeats it for every configured extra field. Use only:
- `[[item#label]]`, `[[item#name]]`, `[[item#placeholder]]`, `[[item#type]]`, `[[item#required]]`, `[[item#options]]`.
- Allowed conditional blocks: `type-input`, `type-textarea`, `type-select`, `type-radio`, and `type-checkbox`, each delimited by `<!-- name < -->` and `<!-- name > -->`.
- `[[item#options]]` may appear only in select, radio, and checkbox blocks.

Autocomplete is wired automatically by the module: 300 ms debounce, 3-character minimum, batches of 30, a "Load more" button, arrow/Enter navigation, `<mark>` highlighting, and an in-memory cache. Build only the visual structure; do not add custom JavaScript or fake results inside `results-box`.

Use placeholders without `@`; the pipeline converts them to storage format. Do not invent variables. Use classes compatible with `{{framework_css}}` and avoid external dependencies.

Return only `html` and, when essential, `css` markdown blocks. Preserve every contract when editing existing content.

Previous HTML:
```html
{{html}}
```
Previous CSS:
```css
{{css}}
```
Available extra variables:
```variables
{{variables}}
```

User request:
