# Module: forms

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `forms` |
| **Name** | Dynamic Forms |
| **Version** | `1.0.4` |
| **Category** | Communication Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html`, `banco`, `comunicacao`, `formulario` (library) |

---

## 🎯 Purpose

The **forms** module manages the creation and processing of **dynamic forms** in Conn2Flow. It allows administrators to configure forms with customizable fields (name, type, required, limits) via a JSON schema. Forms are rendered on the front-end, validated on both client and server sides, and stored in the database. E-mail notifications are sent automatically for every valid submission.

---

## 🏗️ Main Features

### 📋 **Form Management (Admin)**
- **Create / Edit / Clone**: Configurable forms via JSON schema.
- **Preview**: Field preview, behavior and configuration review.
- **Enable / Disable**: Form status control.
- **Multilingual**: Each form is created per language (`language`).

### 📤 **Submission & Processing**
- **AJAX Submission**: Asynchronous submissions with loading/error feedback.
- **Anti-spam**: Honeypot, IP-based rate limiting, reCAPTCHA v3 and v2 fallback.
- **Dual Validation**: Client (JS) and Server (PHP) — server is authoritative.
- **Storage**: All fields and values saved in the `forms_submissions` table.
- **E-mail Notification**: Sent automatically after every valid submission.

### 🛡️ **Validation and Character Limits**
- Per-type character limits with solid defaults and per-field override support.
- Truncated e-mail preview vs. full value preserved in the database.
- Real-time character counter on the client side.

### 📧 **Notification E-mail**
- Configurable template per form (`message_component` field in schema).
- Support for processed fields as repeating cells (`<!-- cel < --> ... <!-- cel > -->`).
- Automatic local image embedding (via CID/PHPMailer).
- `#valor#` = preview value; `#valor_full#` = full content.

---

## 🛡️ Character Limits by Type

This feature was added in version **1.0.4** and ensures protection against abusive submissions while preserving the readability of notification e-mails.

### Default Limits by Type

| Type | Minimum (required fields) | Default Maximum | Notes |
|------|--------------------------|-----------------|-------|
| `text` | 3 characters | **254** | Equivalent to SQL `VARCHAR(255)` limit |
| `email` | — | **254** | RFC 5321 standard for e-mail addresses |
| `textarea` | 3 characters | **10,000** | Long text; multi-line support in DB |
| others | — | **1,000** | Fallback for unmapped types |

> Limits can be **overridden per field** via `max_length` in the JSON schema.

### Per-field Override in Schema

```json
{
  "fields": [
    {
      "name": "message",
      "type": "textarea",
      "label": "Message",
      "required": true,
      "max_length": 500
    }
  ]
}
```

### Server Validation Flow (`formulario_processador`)

Validation happens in **two distinct passes** to ensure robustness:

**1st Pass — Required fields:**
```php
// Minimum 3 characters for required text/textarea fields
if(in_array($field['type'], ['text', 'textarea']) && mb_strlen($fieldValue, 'UTF-8') < 3){
    return false; // message: ajax-message-min-length
}

// Maximum per type (or per-field override)
$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : null;
if(!$maxLength) {
    if(in_array($field['type'], ['text','email'])) $maxLength = 254;
    elseif($field['type'] === 'textarea')           $maxLength = 10000;
}
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    return false; // message: ajax-message-max-length
}
```

**2nd Pass — All fields (including optional):**
```php
$maxLength = isset($field['max_length']) ? (int)$field['max_length'] : (
    in_array($field['type'], ['text','email']) ? 254 : (
        ($field['type'] === 'textarea') ? 10000 : 1000
    )
);
if($maxLength && mb_strlen($fieldValue, 'UTF-8') > $maxLength){
    return false; // message: ajax-message-max-length
}
```

> Both passes use `mb_strlen(..., 'UTF-8')` for proper support of accented characters and emojis.

---

## 📱 Character Counter (Client Side)

The JavaScript file (`gestor/assets/interface/formulario.js`) dynamically applies `maxlength` and displays a live counter below each field.

### `maxlength` Assignment Logic

```javascript
// Detects maxLength: schema > defaults (text/email=254, textarea=10000)
var maxLength = field.max_length
    ? parseInt(field.max_length, 10)
    : (['text', 'email'].indexOf(field.type) !== -1 ? 254
        : (field.type === 'textarea' ? 10000 : null));

if (maxLength) {
    input.attr('maxlength', maxLength);
    // Inject counter below the field
    input.after('<div class="field-counter"><small class="char-counter">0 / ' + maxLength + '</small></div>');
    updateCharCounter(input, maxLength);
    input.on('input', function () { updateCharCounter($(this), maxLength); });
}
```

### `updateCharCounter` Function

Resilient element lookup — supports different DOM structures (Fomantic UI / Tailwind):

```javascript
function updateCharCounter(input, maxLength) {
    var $input = (input instanceof jQuery) ? input : $(input);
    var val = $input.val() || '';
    var length = val.length;

    // Cascading lookup: .field > .char-counter → siblings → nextAll → parent
    var counter = $input.closest('.field').find('.char-counter');
    if (!counter.length) counter = $input.siblings('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.nextAll('.field-counter').find('.char-counter');
    if (!counter.length) counter = $input.parent().find('.char-counter');

    if (counter.length) {
        counter.text(length + ' / ' + maxLength);
        // Red when exceeds limit (browser maxlength also prevents this)
        counter.css('color', length > maxLength ? '#dc2626' : '');
    }
}
```

---

## 📧 E-mail Preview vs. Full Value in DB

To avoid excessively long notification e-mails (especially for `textarea` fields), the system generates two distinct values per field:

| Template Variable | Content | Where to Use |
|------------------|---------|-------------|
| `#valor#` | Formatted preview (truncated for `textarea`, line breaks as `<br>`) | Notification e-mail template |
| `#valor_full#` | Full, formatted value (no truncation) | Admin templates, detail view pages |

### Preview Configuration

The maximum preview length is configurable via `config.php` or `.env`:

```php
$_CONFIG['formularios-email-preview-length'] // default: 800 characters
```

### PHP Processing by Type

```php
// For textarea: preserve line breaks in preview
if($field['type'] === 'textarea'){
    $plainForPreview = preg_replace("/\r\n|\r/", "\n", $fieldValue);
    $preview = nl2br($plainForPreview); // <br> for HTML e-mail
} else {
    $plainForPreview = strip_tags($fieldValueFormatted);
    $preview = $plainForPreview;
}

$camposProcessados[] = [
    '#label#'       => $fieldLabel,
    '#valor#'       => $preview,            // used in e-mail template
    '#valor_full#'  => $fieldValueFormatted // full, for future use
];
```

---

## 🗄️ Database Structure

### Table `forms`

Stores the definition and configuration of each form.

```sql
CREATE TABLE forms (
    id_forms INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'pt-br',
    name VARCHAR(255) NOT NULL,
    fields_schema TEXT NOT NULL,   -- JSON schema with fields, redirects and e-mail configuration
    status CHAR(1) DEFAULT 'A',    -- A = Active, I = Inactive
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### Table `forms_submissions`

Stores each received submission.

```sql
CREATE TABLE forms_submissions (
    id_forms_submissions INT AUTO_INCREMENT PRIMARY KEY,
    id VARCHAR(255) UNIQUE NOT NULL,
    form_id VARCHAR(255) NOT NULL,   -- FK to forms.id
    name VARCHAR(255) NOT NULL,      -- Value of the field configured as field_name
    language VARCHAR(10) NOT NULL DEFAULT 'pt-br',
    fields_values JSON,              -- Array of {name, value} + email_status
    status CHAR(1) DEFAULT 'A',
    versao INT DEFAULT 1,
    data_criacao DATETIME DEFAULT NOW(),
    data_modificacao DATETIME DEFAULT NOW()
);
```

### `fields_values` JSON Structure

```json
{
  "fields": [
    { "name": "name",    "value": "John Doe" },
    { "name": "email",   "value": "john@example.com" },
    { "name": "message", "value": "Hello, I would like to know..." }
  ],
  "email_status": "email-sent"
}
```

---

## 📁 File Structure

```
gestor/
├── bibliotecas/
│   └── formulario.php                         # Main library (controller, processor, validation)
├── assets/
│   └── interface/
│       └── formulario.js                      # Client-side JS (validation, counter, AJAX)
├── modulos/
│   └── forms/
│       ├── forms.php                          # Admin module controller
│       ├── forms.js                           # Admin-specific JS
│       ├── forms.json                         # Module configuration
│       └── resources/
│           ├── pt-br/
│           │   ├── components/
│           │   │   ├── forms-info-definition/ # 📌 Inline docs (reusable component)
│           │   │   └── forms-prepared-email/  # HTML template for notification e-mail
│           │   └── pages/
│           │       ├── forms-adicionar/       # Page: new form
│           │       ├── forms-editar/          # Page: edit form
│           │       ├── forms-visualizar/      # Page: view form
│           │       └── forms-clonar/          # Page: clone form
│           └── en/
│               ├── components/
│               │   ├── forms-info-definition/ # 📌 EN version of the docs component
│               │   └── forms-prepared-email/
│               └── pages/
│                   └── ... (same structure)
└── resources/
    ├── pt-br/
    │   └── components/
    │       └── form-ui/                       # Public form UI component
    └── en/
        └── components/
            └── form-ui/                       # EN version of form-ui
```

---

## 🧩 `form-ui` Component

The `form-ui` component holds all strings used by both JavaScript and PHP for validation and visual feedback.

### Component Cells

| Cell | Type | Description |
|------|------|-------------|
| `<!-- prompts < --> ... <!-- prompts > -->` | HTML | Inline validation messages (empty, email, maxLength) |
| `<!-- ui-texts < --> ... <!-- ui-texts > -->` | HTML | UI texts (loading, errors, timeout) |
| `<!-- ui-components < --> ... <!-- ui-components > -->` | HTML | Reusable HTML components (dimmer, error element, reCAPTCHA v2) |
| `<!-- block-wrapper < --> ... <!-- block-wrapper > -->` | HTML | IP/rate-limit block wrapper |
| `<!-- ajax-messages < --> ... <!-- ajax-messages > -->` | HTML | JSON messages returned via AJAX |

### `maxLength` Messages (added in v1.0.4)

**Prompt (inline, for the client):**
```html
<!-- prompt-max-length < -->
The field #label# must have at most #max# characters.
<!-- prompt-max-length > -->
```

**AJAX message (server → client):**
```html
<!-- ajax-message-max-length < -->
The field #fieldLabel# exceeded the maximum limit of #max# characters.
<!-- ajax-message-max-length > -->
```

---

## 🔧 Form JSON Schema

Each form is defined by a JSON schema stored in the `fields_schema` column of the `forms` table.

### Full Structure

```json
{
  "form_action": "/api/forms/",
  "force_recaptcha": false,
  "access_max": 10,
  "access_max_simple": 5,
  "field_name": "name",
  "field_email": "email",
  "fields": [
    {
      "name": "name",
      "type": "text",
      "label": "Name",
      "required": true,
      "max_length": 120
    },
    {
      "name": "email",
      "type": "email",
      "label": "E-mail",
      "required": true
    },
    {
      "name": "message",
      "type": "textarea",
      "label": "Message",
      "required": true,
      "max_length": 800
    }
  ],
  "redirects": {
    "success": {
      "path": "/thank-you/"
    }
  },
  "email": {
    "subject": "New message: #formName# — #code#",
    "recipients": "admin@mysite.com",
    "reply_to": "",
    "reply_to_name": "",
    "message_component": "forms-prepared-email"
  }
}
```

### Schema Field Reference

| Field | Type | Description |
|-------|------|-------------|
| `form_action` | string | AJAX endpoint for submission |
| `force_recaptcha` | boolean | Forces reCAPTCHA v3 even for "free" IPs |
| `access_max` | int | Maximum submissions before IP block |
| `access_max_simple` | int | Maximum simple submissions (without CAPTCHA) |
| `field_name` | string | Field used as submission identifier |
| `field_email` | string | E-mail field for automatic `reply-to` |
| `fields[].name` | string | POST field name |
| `fields[].type` | string | `text`, `email`, `textarea` |
| `fields[].label` | string | Label for error messages and e-mail |
| `fields[].required` | boolean | Whether the field is required |
| `fields[].max_length` | int | Maximum character limit (optional — overrides default) |
| `redirects.success.path` | string | Redirect URL after successful submission |
| `email.subject` | string | E-mail subject (supports `#formName#`, `#code#`) |
| `email.recipients` | string | Recipients separated by `;` |
| `email.message_component` | string | ID of the e-mail template component |

---

## 🔐 Security

### Protection Layers

| Level | Mechanism | Detail |
|-------|-----------|--------|
| **1. Honeypot** | Hidden `honeypot` field | Bots filling the field are silently rejected |
| **2. Timestamp** | `timestamp` field submitted with form | Submissions older than 2 days (172,800s) are rejected |
| **3. CAPTCHA v3** | Google reCAPTCHA v3 | Score < 0.5 activates CAPTCHA v2 as fallback |
| **4. Rate Limiting** | IP + `forms_access` table | Blocks IPs exceeding `access_max` submissions |
| **5. Validation** | Client + Server | JS validates, PHP **always** re-validates authoritatively |
| **6. SQL Escaping** | `banco_escape_field()` | IDs and values sanitized before queries |
| **7. HTML Escaping** | `htmlspecialchars()` | Field values sanitized before e-mail |
| **8. Size Limit** | `mb_strlen()` | Rejects fields exceeding configured maximum |

### Error Messages

All error messages are read from the `form-ui` component (HTML), never hardcoded in PHP, ensuring they are multilingual and customizable.

---

## 🌐 Integration with `formulario.php` Library

The `forms` module uses two main functions from the library:

### `formulario_controlador($params)`

Prepares the form state for front-end rendering. Includes:
- Loading schema from the database.
- IP block check.
- Preparing fields and JS variables (`formDefinition`, `fields`, `framework`, etc.).

### `formulario_processador($params)`

Processes submissions received via POST/AJAX. Executes:
1. Validation of `formId`, honeypot, timestamp.
2. reCAPTCHA v3 and v2 verification.
3. Required field and type validation.
4. **Maximum size validation** (2 passes + UTF-8 `mb_strlen`).
5. Insert into `forms_submissions`.
6. E-mail formatting with processed cells.
7. Sending via `comunicacao_email()` with image embedding.

---

## 🧩 `forms-info-definition` Component

Inline documentation component displayed on the admin pages of the module (add, edit, view, clone). Created to avoid duplicating documentation HTML across 4 pages and 2 languages.

```
gestor/modulos/forms/resources/
├── pt-br/components/forms-info-definition/forms-info-definition.html
└── en/components/forms-info-definition/forms-info-definition.html  ← (added in v1.0.4)
```

On the pages, it is included via a template variable:
```html
<!-- Placeholder in HTML pages -->
#forms-info-definition#
```

In the PHP controller (`forms.php`), the component is injected:
```php
$pagina = modelo_var_troca($pagina, '#forms-info-definition#', gestor_componente(['id' => 'forms-info-definition']));
```

---

## 📊 Full Submission Flow

```
[User fills the form]
         │
         ▼
[JS validates: required, email, maxlength]
    ↓ valid?
         │
         ▼
[AJAX: POST /api/forms/ with fingerprint + reCAPTCHA token]
         │
         ▼
[formulario_processador()]
    ├─ ✅ formId present?
    ├─ ✅ honeypot empty?
    ├─ ✅ timestamp < 2 days?
    ├─ ✅ reCAPTCHA v3 score >= 0.5? → or v2?
    ├─ ✅ required fields filled?
    ├─ ✅ minimum 3 chars (text/textarea)?
    ├─ ✅ maximum per type (mb_strlen UTF-8)?
    └─ ✅ email valid (FILTER_VALIDATE_EMAIL)?
         │
         ▼
[Insert into forms_submissions (database)]
         │
         ▼
[E-mail formatting]
    ├─ #valor# = formatted preview (textarea → nl2br)
    └─ #valor_full# = full content
         │
         ▼
[comunicacao_email() → PHPMailer → SMTP]
         │
         ▼
[AJAX response: { status: success, redirect: /thank-you/ }]
         │
         ▼
[JS redirects or shows success message]
```

---

## ⚙️ Configuration

### `$_CONFIG` / `.env` Variables

| Key | Default | Description |
|-----|---------|-------------|
| `formularios-maximo-cadastros` | — | Submission limit per IP before blocking |
| `formularios-maximo-cadastros-simples` | — | Simple (no CAPTCHA) submission limit per IP |
| `formularios-email-preview-length` | `800` | Maximum field preview length in e-mail |
| `usuario-recaptcha-active` | — | Enables reCAPTCHA v3 |
| `usuario-recaptcha-server` | — | reCAPTCHA v3 secret key |
| `usuario-recaptcha-v2-active` | — | Enables reCAPTCHA v2 fallback |
| `usuario-recaptcha-v2-server` | — | reCAPTCHA v2 secret key |

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| `1.0.0` | — | Initial release of forms module |
| `1.0.4` | 2026-03-31 | ➕ Per-type character limits (`text`=254, `textarea`=10,000, `email`=254) — server + client. ➕ Real-time character counter. ➕ Truncated e-mail preview + `#valor_full#`. ➕ `forms-info-definition` EN component. 🐛 Fix: UTF-8 safety via `mb_strlen`. 🐛 Fix: `textarea` line breaks preserved in e-mail. |
