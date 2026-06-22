Generate an HTML page (only the inner part of the <body>) containing a site FORM. This page will be used as a TEMPLATE in which an engine dynamically renders a LIST of form fields defined by the user in the Forms module.

MANDATORY FIELD REPETITION RULE:
Every HTML snippet that represents ONE form field (e.g. a group with `<label>` + `<input>`, `<textarea>`, `<select>`, radio options, or checkbox options) MUST be wrapped between the loop markers below:

```
<!-- item < -->
...HTML of ONE form field, containing the placeholders @[[item#variable_name]]@...
<!-- item > -->
```

At runtime, the engine repeats the content between `<!-- item < -->` and `<!-- item > -->` once for each configured form field, replacing each placeholder `@[[item#variable_name]]@` with the real value of the matching field. Variables outside this block are NOT repeated (they serve the form container, header, introductory text, submit button, messages, etc).

PLACEHOLDER FORMATTING RULE:
- Always use the format `[[item#variable_name]]` for field variables (item is literal/static; variable_name is dynamic).
- Always use the format `[[variable_name]]` for global form variables.
- Use the version without `@` for your output — the internal pipeline converts it to `@[[item#variable_name]]@` automatically on save.
- Do not invent placeholders outside the list below. Decorative text may be created normally, but dynamic data must use only the available variables.

AVAILABLE GLOBAL VARIABLES:
- `[[form_id]]` — textual form identifier; use in `data-form-id`, the hidden `form_id`, and as a prefix for HTML ids.
- `[[form_action]]` — optional form action; use in the `action` attribute when appropriate.
- `[[force_recaptcha]]` — indicates whether the form requires recaptcha (`true`/`false`); usually only use it if the user explicitly asks for it.

AVAILABLE FORM FIELD VARIABLES:
- `[[item#label]]` — visible field label; use inside `<label>`.
- `[[item#name]]` — technical field name; use in `name` attributes and as part of the `id`.
- `[[item#placeholder]]` — field placeholder; use in `placeholder` when the type supports it.
- `[[item#type]]` — HTML input type for simple fields (`text`, `email`, `tel`, `number`, etc.); use in the `type` attribute inside the `type-input` block.
- `[[item#required]]` — `required` attribute when the field is mandatory; use directly on the input element.
- `[[item#options]]` — options already rendered by the engine; use only inside the `type-select`, `type-radio`, and `type-checkbox` blocks.

FIELD TYPE CONDITIONAL BLOCKS:
Inside the `item` block, define the conditional blocks below. The engine will keep only the block compatible with the current field type and remove the others.

Simple field (`text`, `email`, `tel`, `number`, etc.):
```
<!-- type-input < -->
<input type="[[item#type]]" id="[[form_id]]-[[item#name]]" name="[[item#name]]" placeholder="[[item#placeholder]]" [[item#required]]>
<!-- type-input > -->
```

Long text field:
```
<!-- type-textarea < -->
<textarea id="[[form_id]]-[[item#name]]" name="[[item#name]]" placeholder="[[item#placeholder]]" [[item#required]]></textarea>
<!-- type-textarea > -->
```

Select field:
```
<!-- type-select < -->
<select id="[[form_id]]-[[item#name]]" name="[[item#name]]" [[item#required]]>
    [[item#options]]
</select>
<!-- type-select > -->
```

Single-choice field (radio):
```
<!-- type-radio < -->
<div>
    [[item#options]]
</div>
<!-- type-radio > -->
```

Multiple-choice field (checkbox):
```
<!-- type-checkbox < -->
<div>
    [[item#options]]
</div>
<!-- type-checkbox > -->
```

FORM CONTRACT:
- The main container must be a `<form class="conn2flow-form ...">` tag.
- Include `data-form-id="[[form_id]]"` on the `<form>`.
- Use `method="post"`.
- Always include `<input type="hidden" name="form_id" value="[[form_id]]">` inside the form.
- Include a message element with the class `conn2flow-form-message`.
- The submit button must have the class `conn2flow-form-submit`.
- Use stable ids in the pattern `[[form_id]]-[[item#name]]` to associate `<label for="...">`.
- Keep all dynamic fields inside the `<!-- item < --> ... <!-- item > -->` block.
- Do not place the submit button inside the `item` block, because it must not be repeated.

SELECT/RADIO/CHECKBOX OPTIONS:
The engine renders `[[item#options]]` automatically. For `select`, it produces `<option>` tags. For `radio` and `checkbox`, it produces inputs inside labels. Therefore, you should only place `[[item#options]]` inside the correct conditional block and style the wrapper.

You may create placeholder text, titles, and descriptions as instructed by the user, but if a more direct output is requested, keep only the placeholders and the required technical structure. Return the HTML code using markdown ```html ``` and, if extra CSS is needed, return it using markdown ```css ```.

This page will use the CSS framework `{{framework_css}}`. Use classes compatible with that framework and avoid external dependencies unless explicitly requested.

You don't need to explain how to build the page since only the generated HTML and CSS will be used and processed by a technical routine transparent to the end user.

All newly generated sections must have an incremental marker <NUMBER> of the current section. If it is a modification, keep the incremental value and modify the content. Create a simple title <TITLE> for each section and set it in the `data-title` attribute:
Example of creating a section:
<section data-id="<NUMBER>" data-title="<TITLE>">
HTML generated by you (including the `<form>` and the `<!-- item < --> ... <!-- item > -->` repetition block)
</section>
HTML generated in a previous interaction:
```html
{{html}}
```
CSS generated in a previous interaction:
```css
{{css}}
```
Variables available to include in the template (each line is a field of the available item):
[variables] // List of variables below, one per line (1-n)
variable_name

Variables available for inclusion:
```variables
{{variables}}
```
The variables above must be used inside the repetition block `<!-- item < --> ... <!-- item > -->` in the template when they are field variables. The `[[item#label]]` must be the visible label text for each field; the `[[item#name]]` must go in the `name` attribute; the `[[item#options]]` must appear only in select, radio, and checkbox. If you receive existing HTML in this interaction, preserve the technical block structure and keep the field order unless the user explicitly requests a different order. Try to create a well-organized and visually pleasant template according to the requested form type (contact, newsletter, registration, survey, support, quote request, signup, etc.) and the provided HTML. However, if the user requests something contradicting these instructions, always prioritize the technical integrity of the markers and placeholders required by the rendering engine.

Below, a user with or without HTML understanding described the following need:
