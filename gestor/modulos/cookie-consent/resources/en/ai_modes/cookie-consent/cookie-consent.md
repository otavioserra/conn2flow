Generate only the inner part of the <body> of a COOKIE NOTICE. The HTML is a model with three pieces: the notice, the preferences card and the button that reopens the card. An engine fills in texts and categories; a public script stores the decision of the visitor and releases what it allows.

MANDATORY STRUCTURE:
The outer container has `data-c2f-cookie-consent`, `id="cookie-consent"` and the configuration attributes. Keep them.

```
<div id="cookie-consent" data-c2f-cookie-consent data-version="[[version]]" data-expiry-days="[[expiry_days]]" data-consent-mode="[[consent_mode]]" data-position="[[position]]" data-floating-position="[[floating_position]]" data-preview="[[preview]]">
    <section data-cc-banner hidden> ...notice... </section>
    <div data-cc-panel hidden> ...preferences card... </div>
    <!-- floating-button < --><button type="button" data-cc-open hidden>...</button><!-- floating-button > -->
</div>
```

The three pieces start with the `hidden` attribute: the script shows whichever applies. Do not use a class to hide them.

BUTTONS (the script recognises them by attribute):
- `data-cc-accept` — accepts every category
- `data-cc-reject` — rejects the optional ones
- `data-cc-customize` — opens the preferences card
- `data-cc-save` — stores the categories ticked in the card
- `data-cc-close` — closes the card without storing
- `data-cc-open` — reopens the card

EQUALITY RULE: rejecting must be as easy as accepting. The `data-cc-accept` and `data-cc-reject` buttons sit side by side in the notice, with the same size and the same emphasis. No optional category comes ticked.

CATEGORIES (block repeated once per category, inside the card):

```
<!-- category-item < -->
<li>
    <label for="c2f-cc-cat-[[category#id]]">[[category#name]]</label>
    <p>[[category#description]]</p>
    <!-- category-required < --><span>[[text#always_active]]</span><!-- category-required > -->
    <input type="checkbox" id="c2f-cc-cat-[[category#id]]" data-cc-category="[[category#id]]" [[category#checked]] [[category#disabled]]>
</li>
<!-- category-item > -->
```

The `input[data-cc-category]` checkbox is mandatory in each category: the script reads the choice from it. `[[category#checked]]` and `[[category#disabled]]` are filled only for the required category.

LINKS (conditional blocks; removed when the address was not provided):

```
<!-- policy-link < --><a href="[[policy_url]]">[[text#policy_label]]</a><!-- policy-link > -->
<!-- terms-link < --><a href="[[terms_url]]">[[text#terms_label]]</a><!-- terms-link > -->
```

PLACEHOLDER FORMAT RULE:
Always use `[[name]]`, without `@`. The pipeline converts it to `@[[name]]@` on save. Do not write fixed text: every visible text comes from `[[text#key]]`.

AVAILABLE TEXTS:
`[[text#title]]`, `[[text#message]]`, `[[text#accept_all]]`, `[[text#reject_all]]`, `[[text#customize]]`, `[[text#preferences_title]]`, `[[text#preferences_intro]]`, `[[text#save]]`, `[[text#always_active]]`, `[[text#policy_label]]`, `[[text#terms_label]]`, `[[text#floating_label]]`, `[[text#close]]`.

CATEGORY VARIABLES (only inside `category-item`):
`[[category#id]]`, `[[category#name]]`, `[[category#description]]`, `[[category#checked]]`, `[[category#disabled]]`.

ACCESSIBILITY:
The card has `role="dialog"`, `aria-modal="true"` and a title linked by `aria-labelledby`. Every icon-only button has an `aria-label`. The visible focus must not be removed.

STYLE:
Write its own CSS, with prefixed classes (`c2f-cc-`) and colours in variables, so the notice looks the same on any page. Do not include `<script>`.
