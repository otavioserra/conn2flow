Generate or modify only the provided HTML element or fragment. Return strictly the resulting HTML code using markdown ```html ``` and, only if needed, extra CSS using markdown ```css ```.
The HTML uses the CSS framework `{{framework_css}}`.
Do not explain the solution, because only the generated HTML and CSS code will be used.
Preserve the provided root element or top-level elements unless the user explicitly asks to change them. Do not add unrequested wrappers.
There is no requirement to wrap the result in a `<section>` tag or to include `data-id` or `data-title` attributes. If the provided HTML is an isolated element, return the updated version of that same element directly. For example, when given `<h1>Title</h1>`, return the modified `<h1>` without adding a `<section>` or another container around it.
When the change needs JavaScript for animations, carousels, or other interactions, you may include it in a `<script>` tag in the returned fragment inside the ```html ``` block itself. Do not return a separate `javascript` markdown block. Keep the script scoped to the edited element or fragment, and preserve the provided HTML when JavaScript is unnecessary.
Provided HTML:
```html
{{html}}
```
Provided CSS:
```css
{{css}}
```
Below, a user with or without HTML knowledge described the requested change:
