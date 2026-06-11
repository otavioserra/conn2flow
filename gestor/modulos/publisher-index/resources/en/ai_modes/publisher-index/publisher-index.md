Generate an HTML page (only the inner part of the <body>) containing a PUBLICATION INDEX (Publisher Index). This page will be used as a TEMPLATE in which the engine dynamically renders a PAGINATED LIST of items (publications) coming from an associated publisher (news, articles, lives, notes, etc.), with text search, sorting and a "Load more" button at runtime.

REQUIRED ROOT CONTAINER:
The whole index MUST be wrapped in a root element with the class `conn2flow-publisher-index` and the data attributes below (filled automatically at runtime by global variables). The public widget script uses this class and these attributes for the dynamic interaction:

```
<section class="conn2flow-publisher-index" data-grupo-slug="@[[grupo_slug]]@" data-publisher-id="@[[publisher_id]]@" data-items-per-page="@[[items_per_page]]@" data-ordenacao="@[[ordenacao]]@" data-page="1">
   ...controls + list of items...
</section>
```

REQUIRED ITEM REPETITION RULE:
Every HTML snippet representing ONE item (e.g. a card, a list row) MUST be wrapped between the loop markers below, inside a container with the class `publisher-index-items`:

```
<div class="publisher-index-items">
   <!-- item < -->
   ...HTML of ONE item, containing the placeholders @[[item#variable_name]]@...
   <!-- item > -->
   <!-- no-item < -->
   ...HTML shown when there are no publications (empty state)...
   <!-- no-item > -->
</div>
```

At runtime, the engine repeats the content between `<!-- item < -->` and `<!-- item > -->` once for each publication of the current page, replacing each `@[[item#variable_name]]@` with the real field value. The `<!-- no-item < --> ... <!-- no-item > -->` block is shown when there are no publications (including searches with no results).

OPTIONAL CONTROL BLOCKS (recommended):
The blocks below are shown or removed according to the panel settings (search bar, sorting and pagination). Include them for a complete index:

- Search bar — the input MUST have the class `publisher-index-search`:
```
<!-- search-input < -->
<input type="search" class="publisher-index-search" placeholder="Search publications...">
<!-- search-input > -->
```

- Sorting — the select MUST have the class `publisher-index-sort` and options with the values `date_desc`, `date_asc`, `title_asc`, `title_desc`:
```
<!-- sort-select < -->
<select class="publisher-index-sort">
   <option value="date_desc">Newest first</option>
   <option value="date_asc">Oldest first</option>
   <option value="title_asc">Title (A-Z)</option>
   <option value="title_desc">Title (Z-A)</option>
</select>
<!-- sort-select > -->
```

- "Load more" button — MUST have the class `publisher-index-load-more`:
```
<!-- load-more < -->
<button type="button" class="publisher-index-load-more">Load more</button>
<!-- load-more > -->
```

PLACEHOLDER FORMATTING RULE:
- Always use the format `[[item#variable_name]]` (item is literal/static; variable_name is dynamic).
- Use the version without `@` for your generated output — the internal pipeline converts it to `@[[item#variable_name]]@` automatically when saving.
- Do NOT insert data types in the variable name. The type comes from the associated publisher and the mapping is resolved at runtime.

COMMON VARIABLE EXAMPLES:
- `[[item#titulo]]` — publication title
- `[[item#resumo]]` — subtitle / summary
- `[[item#imagem]]` — cover image URL
- `[[item#url]]` — final page URL (use it in the `href` of the item's main link)
- `[[item#data]]` — formatted date
- `[[item#categoria]]`, `[[item#autor]]` — optional fields depending on the publisher

The user may also request and provide additional custom variables. In those cases, generate them following the same `[[item#additional_variable_name]]` pattern.

You may create fictitious texts and images beyond these markers ("Lorem ipsum" style) as guided by the user, but if they ask for something more direct, keep only the placeholders. Return the HTML code using markdown ```html ``` and, if you need extra CSS, return it with markdown ```css ```.

This page will use the CSS framework `{{framework_css}}`. Use the `<section></section>` tag as the index root container (the root `<section>` is the one receiving the `conn2flow-publisher-index` class).

You do not need to explain how to build the page since your output will only use the generated HTML and CSS, processed by a technical routine transparent to the end user.

Every generated section that did not exist before must have an incremental marker <NUMBER> of the current section. If it is a modification, keep the incremental value and modify the content. Create a simple title <TITLE> for each section and update it according to the context of each section. Put this title in the `data-title` attribute (also keeping the `conn2flow-publisher-index` class and the root container data attributes):
Example of creating a section:
<section class="conn2flow-publisher-index" data-id="<NUMBER>" data-title="<TITLE>" data-grupo-slug="@[[grupo_slug]]@" data-publisher-id="@[[publisher_id]]@" data-items-per-page="@[[items_per_page]]@" data-ordenacao="@[[ordenacao]]@" data-page="1">
HTML generated by you (conditional controls + the <!-- item < --> ... <!-- item > --> repetition block)
</section>
HTML generated in a previous interaction: 
```html
{{html}}
```
CSS generated in a previous interaction: 
```css
{{css}}
```
Variables available to include in the template formatting (each line is an item field available in the associated publisher):
[variables] // List of variables below, one per line (1-n)
variable_name

Variables available for inclusion:
```variables
{{variables}}
```
The variables sent above must be used inside the repetition block `<!-- item < --> ... <!-- item > -->` in the template, in the order they appear. The first variable should appear in the item's most prominent visual element (usually the title); the second in a secondary highlight (subtitle/summary); images should use the `<img>` element; URLs should be used in the `href` of the item's main link. If you receive existing HTML in this interaction, keep the order of the variables as explained, or follow a new order if the user explicitly asks. If the HTML already has variables defined outside the list above, simply ignore them and do not duplicate any variable. Try to create an organized and visually pleasant template according to the type of index requested (list, card grid, etc.) and the HTML sent. On the other hand, if the user asks for something contradictory to these instructions, always prioritize the user's request.

Below, a user with or without HTML knowledge described the following need:
