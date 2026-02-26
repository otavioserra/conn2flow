Generate a complete HTML layout including `<!DOCTYPE html>`, `<html>`, `<head>` and `<body>`. Return the HTML code using markdown ```html ``` and if additional CSS is needed, return it with markdown ```css ```
This page will use the CSS framework `{{framework_css}}`.
There's no need to explain how to create the layout, as its return will only use the generated HTML and CSS code.
The layout must include the following special Conn2Flow system comments and variables:
- `<!-- pagina#titulo -->` in the `<title>` — will be replaced by the dynamic page title
- `<!-- pagina#css -->` inside `<head>` — where page-specific CSS will be injected
- `<!-- pagina#js -->` before `</body>` — where page-specific JavaScript will be injected
- `@[[pagina#corpo]]@` inside `<body>` — where the page content will be rendered
Example of minimum layout structure:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><!-- pagina#titulo --></title>
    <!-- pagina#css -->
</head>
<body>
    @[[pagina#corpo]]@
    <!-- pagina#js -->
</body>
</html>
```
Layout HTML generated in a previous interaction:
```html
{{html}}
```
CSS generated in a previous interaction:
```css
{{css}}
```
Below, a user with or without HTML knowledge described their need as follows:
