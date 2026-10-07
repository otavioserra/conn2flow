Generate the ARRANGEMENT OF A DASHBOARD BOARD in JSON. The result is NOT HTML: it is a single JSON object, with no text before or after it, describing the board items. The system creates the board from it.

FORMAT:

```
{
  "modo": "grade",
  "widgets": [ ...items... ]
}
```

- `modo`: `"grade"` (12-column grid, items in order; recommended, because it adapts to any width) or `"lousa"` (free position in cells).
- `widgets`: a list of up to 40 items, in the order they appear.

AN ITEM THAT IS A WIDGET:

```
{"id": "menus", "name": "Menus", "registro_id": "", "width": 4, "height_px": 320, "options": {"title": "Navigation"}}
```

- `id`: the widget type, exactly as in the widget registry (for example `menus`, `pages-index`, `publisher-index`, `publisher-highlights`, `forms`, `forms-search`, `galleries`). Do not invent types.
- `registro_id`: leave it empty. When the board is created, the system uses the first active record of that type in the installation; a type with no record is left out.
- `width`: width in columns, 2 to 12 in the grid (up to 24 on the board). `height_px`: height in pixels, 120 to 960.

AN ITEM THAT IS A FREE OBJECT (text, shape, image, icon or button):

```
{"id": "objeto", "name": "Objeto", "width": 12, "height_px": 120, "options": {"header": false, "frame": false},
 "object": {"type": "text", "text": "Title", "size": 44, "weight": 700, "align": "center", "color": "#0f172a"}}
```

- `id` is always `"objeto"`.
- `object.type`: `text`, `shape`, `image`, `icon` or `button`.
- Text and button: `text` (up to 2,000 characters), `size` (10 to 160), `weight` (400 or 700), `align` (`left`, `center`, `right`), `color` (six-digit hexadecimal).
- Button: `href` (an http(s) address or a site path starting with `/`), `fill` (button color), `newTab` (true or false).
- Shape: `shape` (`rect`, `rounded`, `circle`, `line`) and `fill`. Icon: `icon` (a Lucide icon name) and `color`. Image: `src` (path of a panel file, starting with `/`), `fit` (`cover` or `contain`) and `alt`.

OPTIONS OF ANY ITEM (`options`, all optional):
`header` (show the header), `frame` (show the frame), `title` (custom title, up to 80 characters), `background` (hexadecimal background color), `padding` (`none`, `small`, `medium`, `large`), `hide` (hide below a width: `sm`, `md` or `lg`).

POSITION IN BOARD MODE (optional): `x` (column, 0 to 23) and `y` (20 px row, from 0). Do not use them in the grid.

RULES:
- Valid JSON only: double quotes, no comments, no trailing commas.
- In the grid, think in rows that add up to 12 columns (12; 8 + 4; 6 + 6; 4 + 4 + 4).
- Start with a title text and, when it makes sense, end with a call-to-action button.
- Do not include HTML, scripts or addresses of other sites in images.
