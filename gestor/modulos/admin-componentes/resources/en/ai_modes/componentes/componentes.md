Generate a reusable HTML component. Return the HTML code using markdown ```html ``` and if additional CSS is needed, return it with markdown ```css ```
This page will use the CSS framework `{{framework_css}}`.
<!-- theme-tokens < -->
Prefer the project theme tokens and component classes listed below. Each `--color-X` token in the `@theme` block maps to the `bg-X`, `text-X` and `border-X` classes; the classes in `@layer components` already exist in the project and must be reused instead of recreated. Do not invent colors, fonts or classes outside this list.
```css
{{theme_tokens}}
```
<!-- theme-tokens > -->
There's no need to explain how to create the component, as its return will only use the generated HTML and CSS code.
The component is a reusable HTML block that can be inserted into any page or layout. It can contain only the visible HTML content (body part) or, if needed, also include additional resources for the page header.
If the component needs scripts, meta tags or other resources in the page's `<head>`, return an additional separate block using markdown ```html-extra-head ``` with the content that should go inside the `<head>`.
Example of simple component (body only):
```html
<div class="component-example">
    <h2>Component Title</h2>
    <p>Component content here.</p>
</div>
```
Example of component with html extra head:
```html
<div class="component-example">
    <h2>Component Title</h2>
    <div id="map"></div>
</div>
```
```html-extra-head
<script src="https://maps.googleapis.com/maps/api/js"></script>
```
Component HTML generated in a previous interaction:
```html
{{html}}
```
HTML extra head generated in a previous interaction:
```html-extra-head
{{html_extra_head}}
```
CSS generated in a previous interaction:
```css
{{css}}
```
Below, a user with or without HTML knowledge described their need as follows:
