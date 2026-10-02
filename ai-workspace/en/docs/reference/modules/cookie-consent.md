---
title: "Cookie-consent module"
description: "Cookie notice, preferences card by category and release of scripts according to the decision."
section: reference
module: cookie-consent
sources:
  - gestor/modulos/cookie-consent/cookie-consent.php
  - gestor/modulos/cookie-consent/cookie-consent.js
  - gestor/modulos/cookie-consent/cookie-consent.widget.php
  - gestor/modulos/cookie-consent/cookie-consent.widget.js
  - gestor/modulos/cookie-consent/cookie-consent.json
  - gestor/modulos/cookie-consent/resources
  - gestor/db/migrations/20261002110000_create_presentations_and_cookie_consent_tables.php
verified_at: ce9ba7ac
---

# `cookie-consent` module

Shows the cookie notice, stores the decision of the visitor by category and releases only what it allows. A record defines categories, texts and the addresses of the policy and the terms; the model draws the notice, the preferences card and the button that reopens the card.

## How to use

Open `cookie-consent/adicionar/`, give it a name, pick the model (`cookie-consent-card` or `cookie-consent-card-light`) and adjust the three tabs: general, categories and texts. Insert the widget into the site layout, once:

```html
<!-- widgets#cookie-consent->render({"grupo_slug":"site"}) < -->
<!-- widgets#cookie-consent->render({"grupo_slug":"site"}) > -->
```

For a script to run only with permission, change its type and name the category:

```html
<script type="text/plain" data-cookie-category="analytics" data-src="https://example.com/measure.js"></script>
<script type="text/plain" data-cookie-category="marketing">/* inline code */</script>
<iframe data-cookie-category="marketing" data-src="https://example.com/video"></iframe>
```

To reopen the card from anywhere (footer, policy), use a link to `#cookie-consent` or the `data-cookie-consent-open` attribute.

### Options

| Option | Default | What it does |
|---|---|---|
| `version` | `1` | Changing the version brings the notice back for those who had already decided |
| `expiry_days` | `180` | Validity of the decision, from 1 to 395 days |
| `position` | `bottom-left` | `bottom-left`, `bottom-right`, `bottom` or `center` |
| `show_floating_button`, `floating_position` | `true`, `left` | Button that reopens the card and its side |
| `policy_url`, `terms_url` | empty | Site path or `http(s)` address. Empty, the link does not appear |
| `consent_mode` | `true` | Sends the Google Consent Mode v2 signals to the `dataLayer` |
| `texts` | module variables | Thirteen texts; an empty field uses the language default |
| `categories` | four built in | List of `id`, `name`, `description` and `required` |

## Technical reference

The controller dispatches `listar`, `adicionar`, `editar` and `clonar`. Admin AJAX: `template-load` and `widget-preview`. In the preview the notice always appears and nothing is stored in the browser.

The `cookie_consent` table has the structure of `galleries`, with `id_cookie_consent` and the `project` column.

The widget repeats `category-item` per category (`[[category#id]]`, `[[category#name]]`, `[[category#description]]`, `[[category#checked]]`, `[[category#disabled]]`), with `category-required` and `category-optional` inside it; keeps `policy-link`, `terms-link` and `floating-button` according to the options; and resolves `[[text#key]]` and the globals `[[version]]`, `[[expiry_days]]`, `[[position]]`, `[[floating_position]]`, `[[consent_mode]]`, `[[policy_url]]`, `[[terms_url]]` and `[[preview]]`. Everything that comes from the panel is escaped on output.

The decision lives in the `c2f_consent` cookie of the site itself: `{ v, t, c }`, with the version, the instant and one boolean per category. Without a valid decision for the current version, only the required categories apply.

The public controller exposes `window.c2fConsent` (`get`, `has`, `open`, `reset`, `onChange`), fires `c2f:consent` on the `document` and marks `<html data-c2f-consent="pending|decided">`. With `consent_mode`, it sends `consent default` with everything denied on load and `consent update` with the decision: `analytics` controls `analytics_storage`; `marketing` controls `ad_storage`, `ad_user_data` and `ad_personalization`; `preferences` controls `functionality_storage` and `personalization_storage`.

The models ship their own CSS, with `c2f-cc-` classes and colours in variables: the notice looks the same on any page, with or without a CSS framework.

## Confirmed limitations

> [!WARNING]
> The widget only blocks what was marked with `type="text/plain"` and `data-cookie-category`. A third-party script written as a regular script runs before any decision. The `consent default` signal is sent when the controller loads, at the end of the page: a measurement tag placed in the `<head>` as a regular script fires before it.

> [!CAUTION]
> Withdrawing a category already granted reloads the page, because a script that already ran cannot be undone. Cookies the third party stored earlier are not deleted by the widget. The decision stays only in the browser: the module keeps no consent record on the server.

## See also

- [Menus](menus.md)
- [Models](admin-templates.md)
