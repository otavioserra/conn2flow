````markdown
# Module Template (`module_id`)

This directory is the OFFICIAL MODEL of how to structure a module within the Manager. It is NOT a functional business module; it serves as a reference for creation, reading, and standardization.

---
## Overview
A module is composed of:
1. **JSON Configuration File** (`module_id.json`): defines table, pages (HTML and CSS value of a page), components (short HTML and CSS that repeat like an alert, or pieces of a page), variables (dynamic values of information that change on a page or component), libraries, and optionally selection data (selectData*) for dropdowns and choice fields.
    - Page Record Example:
   ```json
    {
        "name": "Free Name", // Page name, can be anything, it's just referential
        "id": "module-id-option", // Unique identifier in relation to all pages.
        "layout": "layout-id", // Identifier of the main layout that will be used. Reference `gestor\resources\en\layouts.json`. For the administrative panel: `manager-administrative-layout` for a common page: `page-layout-without-permission`.
        "path": "module-id/option/", // Page routing in relation to the system root.
        "type": "system|page", // Can be system type (modules and some administrative operation) or page (common page without linking to modules).
        "option": "option", // Option that will be linked to the page. Used in the module to handle the logic of the specific module page.
        "root": true, // Whether or not it is the root page of the module.
        "version": "1.0"
    }
   ```
    - Component Record Example:
   ```json
    {
        "name": "Free Name", // Component name, can be anything, it's just referential
        "id": "unique-identifier-of-the-component-inside-the-module", // Identifier of the same to be able to access the component.
        "version": "1.0"
    }
   ```
    - Variable Record Example:
   ```json
    {
        "id": "unique-identifier-of-the-variable-inside-the-module", // Identifier of the same to be able to access the variable.
        "value": "variable value", // Value of the variable itself. It is of type MEDIUMTEXT, meaning it can use a lot of space.
        "type": "string" // Variable type.
    }
   ```
   Variable Types:
   ```php
    'fieldTypes' => Array(
		Array(	'text' => 'String',				'value' => 'string',			),
		Array(	'text' => 'Text',					'value' => 'text',				),
		Array(	'text' => 'Boolean',				'value' => 'bool',				),
		Array(	'text' => 'Number',				'value' => 'number',			),
		Array(	'text' => 'Quantity',			'value' => 'quantity',		),
		Array(	'text' => 'Money',				'value' => 'money',			),
		Array(	'text' => 'CSS',					'value' => 'css',				),
		Array(	'text' => 'JS',					'value' => 'js',				),
		Array(	'text' => 'HTML',					'value' => 'html',				),
		Array(	'text' => 'TinyMCE',				'value' => 'tinymce',			),
		Array(	'text' => 'Multiple Dates',		'value' => 'multiple-dates',	),
		Array(	'text' => 'Date',					'value' => 'date',				),
		Array(	'text' => 'Date and Time',			'value' => 'date-time',			),
	),
   ```
2. **PHP File** (`module_id.php`): controls routes (options), CRUD, validations, history, backup, interface, and AJAX.
3. **JS File** (`module_id.js`): dynamic interface logic, AJAX calls, frontend initialization, and specific functionalities like CodeMirror, modals, preview systems, etc.
4. **Directory `resources/<language>/`** with:
   - `pages/` → module HTML/CSS pages.
   - `components/` → reusable components.
   - `layouts/` → (optional) module-specific layouts.

Essential libraries: `database`, `manager`, `model` are loaded implicitly; additional ones must be listed in `module_id.json`.

---
## 1. Configuration File (`module_id.json`)
In this file, resource data mapping is done. But the physical .HTML and .CSS files are in the `resources/<language>/` folder.
### Main fields:
- `version`: semantic version of the module.
- `libraries`: list of additional libraries (aliases defined in `gestor/config.php`).
- `table`: primary table mapping:
  - `name`, `id` (textual identifier field), `numeric_id`, `status`, `version`, `creation_date`, `modification_date`.
- `selectData*` (optional): data arrays for dropdowns and selections, named according to module need (ex: `selectDataType`, `selectDataStatus`, `selectDataFrameworkCSS`, etc.).

#### Structure of selectData*
The `selectData*` arrays follow a specific pattern:
```json
{
  "selectDataFrameworkCSS": [
    {
      "text": "Fomantic UI",
      "value": "fomantic-ui"
    },
    {
      "text": "TailwindCSS", 
      "value": "tailwindcss"
    }
  ],
  "selectDataStatus": [
    {
      "text": "Active",
      "value": "A"
    },
    {
      "text": "Inactive",
      "value": "I"
    }
  ]
}
```

#### Usage in Forms
```php
// Select field using selectData*
Array(
    'type' => 'select',
    'id' => 'framework-css',
    'name' => 'framework_css',
    'selected_value' => $framework_css, // current value for editing
    'placeholder' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-framework-css-label')),
    'data' => $module['selectDataFrameworkCSS'], // reference to the array
),
```
- `resources`:
  - `<language>` → ex: `en`.
    - `pages`: list of module pages:
      - `name` (free), `id` (unique internal identifier), `layout` (existing global layout), `path` (public route with trailing `/`), `type` (`system` or `page`), `option` (value of `option` that PHP uses in the switch), `root` (boolean if default route), `version`.
    - `components`: collection of reusable components (each with `id` and `version`).
    - `variables`: variables consumed via `manager_variables`:
      - `id`, `value`, `type` (ex: `string`, `int`, `json`, `html`, `markdown`).

Rules:
- Page `id` becomes part of the file reference: `resources/en/pages/<id>/<id>.html` and optional `.css`.
- `components` follow structure: `resources/en/components/<id>/<id>.html|css`.
- `layout` points to an existing layout or customized in `layouts`.

---
## 2. PHP File (`module_id.php`)
Responsibilities:
- Load JSON config: `$_GESTOR['module#'.$_GESTOR['module-id']]`.
- Define `$_GESTOR['module-id']`.
- Include declared libraries (via `manager_include_libraries()`).
- Implement standard interfaces (list, add, edit) following patterns of:
  - Validation: `interface_validation_mandatory_fields`.
  - Unique identifier: `database_identifier`.
  - Additional uniqueness verification: `interface_verify_fields`.
  - Insert: `database_insert_name`.
  - Update + history: `database_select_fields_before_start`, `interface_history_include`.
  - Version increment: field `version` via `version = version + 1`.
  - Header buttons configured in `$_GESTOR['interface']['edit']['finish']['buttons']`.
  - List configured via `$_GESTOR['interface']['list']['finish']` with:
    - `database` (name, fields, id, status)
    - `table.columns` (fields, formatting, sorting, search)
    - `options` (edit, status toggle, delete)
    - `buttons` (add etc.)

Typical flow (non-AJAX):
1. `module_id_start()` → detects if it is AJAX or interface.
2. For interface: `interface_start()` → switch in `$_GESTOR['option']` → calls function (ex: `module_id_add`).
3. Function assembles `$_GESTOR['interface'][<action>]['finish']` and manipulates `$_GESTOR['page']` with substitutions (`model_var_replace_all`).
4. `interface_finish()` generates final HTML.

AJAX Flow:
1. `ajax=yes` + `ajaxOption` in POST.
2. `interface_ajax_start()`.
3. Switch in `$_GESTOR['ajax-option']`.
4. Function defines `$_GESTOR['ajax-json']`.
5. `interface_ajax_finish()` serializes JSON.

Key functions used (succinct reference):
- `manager_variables()` → gets module or global variables.
- `interface_format_data()` → field formatting (dateTime, otherTable, etc.).
- `manager_page_javascript_include()` → includes `<module>.js`.
- `manager_redirect()` / `_root()` → navigation.
- `interface_alert()` → user feedback.
- `interface_module_variable_value()` → access to table values after selection.

History & Backup:
- `interface_history_include()` records field by field before/after when there is a change.
- `interface_backup_field_include()` stores value of key fields (optional) before modification.

Status & Deletion:
- Standard: logical deletion (`status='D'`).
- Toggle status via buttons `status` / `activate` / `deactivate` configured.

Security & Best Practices:
- Always escape values that will be added to the database to avoid SQL injection: `database_escape_field($_REQUEST['field'])`.
- Validate mandatory fields also in PHP (do not trust only JS).
- Never trust `id` coming from the client without checking existence and status != 'D'.

---
## 3. JavaScript File (`module_id.js`)
Responsibilities:
- Initialization of components (dropdowns, forms, widgets).
- Configuration of external libraries (CodeMirror, modals, etc.).
- Complementary asynchronous validation (AJAX).
- Module-specific functionalities (preview systems, editors, etc.).
- AJAX requests following pattern:
```js
$.ajax({
  type: 'POST',
  url: gestor.root + gestor.modulePath + '/',
  data: { option: gestor.moduleOption, ajax: 'yes', ajaxOption: 'option', params: {} },
  dataType: 'json',
  beforeSend: function(){ $.load_open(); },
  success: function(data){ if(data.status==='ok'){ /* ... */ } $.load_close(); },
  error: function(err){ /* handle */ $.load_close(); }
});
```
- Must call internal initialization functions (ex: `exampleCalls();`).
- Avoid heavy inline logic in HTML (everything goes here).

### Inclusion of External Resources
When necessary to use libraries like CodeMirror, TinyMCE, etc., include in PHP:
```php
// CSS
$_GESTOR['css'][] = '<link rel="stylesheet" href="'.$_GESTOR['root-url'].'library/file.css" />';
// JavaScript  
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'library/file.js"></script>';
```

#### CodeMirror for Code Editors
For modules that edit HTML, CSS, or JavaScript:
```php
// ===== CodeMirror Inclusion
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['root-url'].'codemirror-5.59.1/lib/codemirror.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['root-url'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/lib/codemirror.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/search/search.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/mode/css/css.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['root-url'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';

// ===== Module JS Inclusion (always after external libraries)
manager_page_javascript_include();
```

### Support for Multiple CSS Frameworks

#### Field framework_css
Since version v1.15.0, the system supports multiple CSS frameworks. For modules that manage visual resources (layouts, pages, components):

```php
// In the add/edit form
Array(
    'type' => 'select',
    'id' => 'framework-css',
    'name' => 'framework_css',
    'selected_value' => 'fomantic-ui', // default
    'placeholder' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-framework-css-label')),
    'data' => $module['selectDataFrameworkCSS'],
),
```

#### Configuration in JSON
```json
{
  "selectDataFrameworkCSS": [
    {
      "text": "Fomantic UI",
      "value": "fomantic-ui"
    },
    {
      "text": "TailwindCSS",
      "value": "tailwindcss"
    }
  ]
}
```

#### Framework Validation
```php
// Mandatory validation for visual modules
Array(
    'rule' => 'selection-mandatory',
    'field' => 'framework_css',
    'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-framework-css-label')),
    'identifier' => 'framework_css',
)
```

#### Persistence in Database
```php
// Include the field in persistence
$field_name = "framework_css"; $post_name = $field_name; 
if($_REQUEST[$post_name]) $fields[] = Array($field_name,database_escape_field($_REQUEST[$post_name]));
```

---
## 4. Resources Structure
```
resources/
  en/
    pages/
      module-id-option/
        module-id-option.html
        module-id-option.css (optional)
    components/
      unique-identifier-of-the-component-inside-the-module/
        unique-identifier-of-the-component-inside-the-module.html
        unique-identifier-of-the-component-inside-the-module.css (optional)
    layouts/ (optional)
```
Observations:
- HTML content is never directly in PHP, only references/substitutions of placeholders via `model_var_replace_all`.
- Text variables are in JSON (`variables`).

### Usage of Advanced Components
For modules that use modals, editors, or preview systems:

#### Modal System (Correct Pattern)
```php
// ===== CRITICAL ORDER: Modal BEFORE CodeMirror
$_GESTOR['dependencies']['assets']['final'] = Array(
    // 1. Modal component (FIRST in order)
    'components/modal-preview-' . $_GESTOR['module-id'] . '.php',
    
    // 2. CodeMirror and external libraries (AFTER the modal)
    'assets/codemirror/lib/codemirror.js',
    'assets/codemirror/mode/xml/xml.js',
    'assets/codemirror/mode/css/css.js',
    'assets/codemirror/mode/javascript/javascript.js',
    'assets/codemirror/mode/htmlmixed/htmlmixed.js',
    
    // 3. Custom scripts (LAST)
    'assets/modules/' . $_GESTOR['module-id'] . '/script.js'
);

// ===== CORRECT CALL: WITHOUT 'variables' parameter
$modalComponent = manager_component('modal-preview');

// ===== VARIABLE SUBSTITUTION: Individual with model_var_replace
$modalComponent = model_var_replace($modalComponent,'#title#',manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'modal-title-preview')));
$modalComponent = model_var_replace($modalComponent,'#desktop#',manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'modal-desktop-preview')));
$modalComponent = model_var_replace($modalComponent,'#tablet#',manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'modal-tablet-preview')));
$modalComponent = model_var_replace($modalComponent,'#mobile#',manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'modal-mobile-preview')));
$modalComponent = model_var_replace($modalComponent,'#button-tooltip#',manager_variables(Array('module' => 'interface','id' => 'form-button-title')));
$modalComponent = model_var_replace($modalComponent,'#button-value#',manager_variables(Array('module' => 'interface','id' => 'form-button-value')));

// ===== COMPONENT DISPLAY
echo $modalComponent;
```

**⚠️ IMPORTANT - Inclusion Order:**
The modal must be included **BEFORE** CodeMirror or other external library inclusions to ensure elements are available when scripts are loaded.

#### ❌ Incorrect Pattern (DO NOT use)
```php
// ERROR 1: 'variables' parameter does not exist in manager_component
$modalComponent = manager_component(Array(
    'module' => 'module',
    'component' => 'modal',
    'variables' => Array(  // ❌ ERROR: this parameter does not exist
        'title' => 'Title'
    )
));

// ERROR 2: Modal included AFTER CodeMirror (incorrect order)
$_GESTOR['dependencies']['assets']['final'] = Array(
    'assets/codemirror/lib/codemirror.js',  // ❌ ERROR: CodeMirror first
    'components/modal-preview.php'          // ❌ ERROR: Modal after
);

// ERROR 3: attempt to use replace_variables instead of model_var_replace
$modalComponent = replace_variables($modalComponent, Array(  // ❌ ERROR: non-existent function
    '#title#' => 'value'
));
```

**Identified Problems:**
- ❌ `manager_component()` DOES NOT accept 'variables' parameter
- ❌ Modal included after CodeMirror breaks initialization
- ❌ Function `replace_variables()` does not exist - use `model_var_replace()`
    )
));
```

#### TailwindCSS Preview System
For modules that edit HTML/CSS (layouts, pages, components):
```php
// ===== IMPLEMENT IN BOTH FUNCTIONS: add AND edit

function module_add() {
    // ... add logic
    
    // ===== CRITICAL ORDER: Modal BEFORE CodeMirror
    $_GESTOR['dependencies']['assets']['final'] = Array(
        // 1. Modal FIRST
        'components/modal-preview-' . $_GESTOR['module-id'] . '.php',
        
        // 2. CodeMirror AFTER
        'assets/codemirror/lib/codemirror.js',
        'assets/codemirror/mode/xml/xml.js',
        'assets/codemirror/mode/css/css.js',
        'assets/codemirror/mode/javascript/javascript.js',
        'assets/codemirror/mode/htmlmixed/htmlmixed.js',
        
        // 3. Custom scripts LAST
        'assets/modules/' . $_GESTOR['module-id'] . '/script.js'
    );
    
    // ===== CORRECT MODAL CALL (without 'variables')
    $modalPreview = manager_component('modal-preview');
    
    // ===== INDIVIDUAL SUBSTITUTIONS
    $modalPreview = model_var_replace($modalPreview, '#modal-id#', 'modal-preview-' . $_GESTOR['module-id']);
    $modalPreview = model_var_replace($modalPreview, '#title#', manager_variables(Array('module' => $_GESTOR['module-id'], 'id' => 'modal-preview-title')));
    
    echo $modalPreview;
}

function module_edit($id) {
    // ... edit logic
    
    // ===== SAME PATTERN for edit function
    $_GESTOR['dependencies']['assets']['final'] = Array(
        'components/modal-preview-' . $_GESTOR['module-id'] . '.php',
        'assets/codemirror/lib/codemirror.js',
        // ... other assets in the same order
    );
    
    $modalPreview = manager_component('modal-preview');
    $modalPreview = model_var_replace($modalPreview, '#modal-id#', 'modal-preview-' . $_GESTOR['module-id']);
    echo $modalPreview;
}
```

**Custom Preview Button:**
```php
// In form configuration
'footer_buttons' => [
    'preview' => [
        'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-button-preview')),
        'tooltip' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'tooltip-button-preview')),
        'icon' => 'plus circle',
        'color' => 'positive',
        'callback' => 'preview', // JavaScript function in script.js
    ],
],
```

### Advanced Functionalities

#### Preview Endpoint System
For modules with preview, create file `preview.php`:
```php
// controllers/modules/admin-layouts/preview.php
<?php
require_once '../../../manager.php';

$name = $_POST['name'] ?? '';
$html = $_POST['html'] ?? '';
$css = $_POST['css'] ?? '';
$framework_css = $_POST['framework_css'] ?? 'fomantic';

// Configure CSS framework
$cssFramework = '';
if ($framework_css === 'tailwindcss') {
    $cssFramework = '<script src="https://cdn.tailwindcss.com"></script>';
} else {
    $cssFramework = '<link rel="stylesheet" href="' . $_GESTOR['root-url'] . 'assets/fomantic/semantic.min.css">';
}

// Preview structure
$preview = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Preview: $name</title>
    $cssFramework
    <style>$css</style>
</head>
<body>
    $html
</body>
</html>";

echo $preview;
?>
```

#### Preview JavaScript (script.js)
```javascript
function preview() {
    // Gets form data
    var formData = new FormData($('#form-' + currentModule)[0]);
    
    // Calls preview endpoint
    $.ajax({
        url: 'controllers/modules/' + currentModule + '/preview.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Displays in modal iframe
            $('#preview-frame').attr('src', 'data:text/html;charset=utf-8,' + encodeURIComponent(response));
            $('#modal-preview-' + currentModule).modal('show');
        },
        error: function() {
            alert('Error generating preview');
        }
    });
}
```

### Learned Form Patterns
```php
// Add interface with custom buttons (ex: preview system)
$_GESTOR['interface']['add']['finish'] = Array(
    'no_default_button' => true, // removes default save button
    'footer_buttons' => [
        'preview' => [
            'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-button-preview')),
            'tooltip' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'tooltip-button-preview')),
            'icon' => 'plus circle',
            'color' => 'positive',
            'callback' => 'preview', // JavaScript function
        ],
    ],
    'form' => Array(
        'validation' => Array(
            Array(
                'rule' => 'text-mandatory',
                'field' => 'name',
                'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-name-label')),
            ),
            Array(
                'rule' => 'selection-mandatory',
                'field' => 'framework_css',
                'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-framework-css-label')),
                'identifier' => 'framework_css',
            )
        ),
        'fields' => Array(
            // form fields definition
        ),
    )
);

// Edit interface (similar to add)
$_GESTOR['interface']['edit']['finish'] = Array(
    'no_default_button' => true,
    'footer_buttons' => [
        'preview' => [
            'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-button-preview')),
            'tooltip' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'tooltip-button-preview')),
            'icon' => 'plus circle',
            'color' => 'positive',
            'callback' => 'preview',
        ],
    ],
    'id' => $id,
    'metaData' => $metaData,
    'database' => Array(
        'name' => $module['table']['name'],
        'id' => $module['table']['id'],
        'status' => $module['table']['status'],
    ),
    'buttons' => Array(
        'add' => Array(...),
        'status' => Array(...),
        'delete' => Array(...),
    ),
    'form' => Array(
        // same pattern as add interface
    )
);
```

---
## 5. Identifier Conventions
- Module: use `kebab-case` (ex: `admin-updates`).
- Pages: `module-option` (ex: `admin-updates-detail`).
- Variables: `context-description` (ex: `updates-title`).
- Components: granular and self-explanatory (ex: `record-status-badge`).
- Table fields: follow snake_case if legacy already uses it; maintain consistency.

---
## 6. CRUD Lifecycle
Add:
1. User accesses `/module-id/add/`.
2. JS validates client-side (optional) + PHP validates server-side.
3. Calculates unique `id`.
4. Inserts record and redirects to `/edit/`.

Edit:
1. Loads existing record.
2. Saves previous state (`database_select_fields_before_start`).
3. Compares fields -> build `$changes`.
4. Updates record (increments version).
5. History + backup.
6. Redirects with new `id` if renamed.

List:
1. Defines table configuration.
2. System assembles HTML automatically.
3. Actions per record according to `options`.

---
## 7. Standard AJAX
Request sends: `option`, `ajax=yes`, `ajaxOption`, `params{}`.
Response: `$_GESTOR['ajax-json']` → serialized in JSON.

---
## 8. Field Formatting (`interface_format_data`)
Common formats:
- `dateTime`, `date`, `currency`, `otherTable`, `otherSet`.
- `otherTable` requires:
```php
'format' => [
  'id' => 'otherTable',
  'table' => [ 'name' => 'modules', 'field_swap' => 'name', 'field_reference' => 'id' ],
  'value_if_not_exists' => '<span class="ui info text">N/A</span>'
]
```

---
## 9. History
- Records field before/after when there is a change.
- Depends on previous call to `database_select_fields_before_start` + use of `database_select_fields_before` per field.
- Stores type (change, creation, logical deletion etc.).

---
## 10. Field Backup
- Used to record critical values (ex: templates, layouts) before destructive change.
- Functions: `interface_backup_field_include` and selection via `interface_backup_field_select`.

## 11. Validation and Security

### Field Validation
Always implement validation in two layers:

**In JavaScript (prior validation):**
```js
function validateForm(){
    if(!$('#mandatory-field').val()){
        alert('Mandatory field not filled');
        $('#mandatory-field').focus();
        return false;
    }
    return true;
}
```

**In PHP (definitive validation):**
```php
// Mandatory fields validation
interface_validation_mandatory_fields(Array(
    'name',
    'mandatory_field'
));

// Uniqueness verification
$exists = interface_verify_fields(Array(
    'table' => $module['table']['name'],
    'field' => 'name',
    'value' => database_escape_field($_REQUEST['name']),
    'except-id' => $id // For editing
));
```

### Security
- **Always escape values** for the database: `database_escape_field($_REQUEST['field'])`
- **Never trust only JavaScript validation** - always revalidate in PHP
- **Check existence and status** before editing: `status != 'D'`
- **Control permissions** of access to module options

---
## 12. Evolution Best Practices
- Increment `version` in JSON when changing structure (pages/variables/components) that impacts cache.
- Avoid duplicated logic between modules; extract to reusable library.
- Keep documentation (`module_id.md`) updated with each new pattern.

---
## 13. Routing: Page Path vs `?option=`

The router (`manager_router`) has important rules:

1. If the request contains `?option=something`, the core after including the module and executing the logic will call `manager_redirect_root()` and will NOT deliver the HTML page of that request. Use this format for actions that do not need to render immediately (side effects / processing / state change followed by redirect).
2. To display an HTML page directly, create a record in `resources.<language>.pages[]` in the module JSON with a `path` (ending in `/`). The router will locate the page by the path (without using `?option`) and load its HTML + module in the same response.
3. The `option` field of the page record is still passed to the module (`$_GESTOR['option']`), allowing internal switch, but without triggering automatic redirection because access was by path and not by `?option=`.
4. Internal links that should show content must point to the `path` (ex: `detail/`) and extra parameters (ex: `detail/?log=abc.log`). Avoid `?option=detail` in this case or you will lose the HTML.
5. AJAX (`ajax=yes`) never suffers this automatic redirect; the JSON response is returned according to `$_GESTOR['ajax-json']`.
6. Best practices: clearly separate action URLs (`?option=`) from navigational URLs (paths). Facilitates caching, logs, and SEO.

Example: In the `admin-updates` module, links were adjusted from `?option=update-detail&log=...` to `detail/?log=...` to allow display of the detail page HTML.

---
## 14. Steps to Create a New Module
1. Copy `module_id` folder with new name (ex: `admin-reports`).
2. Adjust `new_module.json` (table, pages, variables, specific libraries).
3. Rename functions in `new_module.php` (consistent prefix: `new_module_`).
4. Adjust JS (`new_module.js`) removing unused examples.
5. Create record in `ModulesData.json` + permission in `UsersProfilesModulesData.json`.
6. Implement pages and components in `resources/`.
7. Test basic CRUD + AJAX.
8. Register initial version.

---
## 14. Quality Checklist
- [ ] Valid JSON and aligned with real table.
- [ ] All PHP functions with correct prefix.
- [ ] `manager_include_libraries()` called in `start`.
- [ ] Placeholders replaced via `model_var_replace_all`.
- [ ] Server-side validation implemented.
- [ ] History activated (if relevant).
- [ ] Backup used (if necessary).
- [ ] JS without dead example code before production.
- [ ] Interface variables in JSON (nothing hardcoded in final HTML).

---
## 15. Quick Glossary
- `option`: logical route handled in PHP.
- `ajax-option`: subroute for asynchronous calls.
- `module-id`: global module identifier.
- `$_GESTOR['page']`: final HTML buffer before rendering.
- `manager_variables`: resolves variables by module/language.

---
## 16. Next Improvements (Template)
- Add examples of real `components`.
- Add example of customized `layout`.
- Create advanced section on pagination and dynamic filters.
- Include automated testing pattern (future).

---
## 17. Quick Reference of Important Functions
| Function | Usage |
| ------ | --- |
| manager_include_libraries | Loads libraries declared in JSON |
| manager_page_javascript_include | Includes module JS |
| interface_validation_mandatory_fields | Mandatory fields validation |
| database_identifier | Generates unique textual identifier |
| database_insert_name | Insert using fields vector | 
| database_update | Direct Update |
| interface_history_include | Change log |
| interface_backup_field_include | Backup of specific fields |
| interface_format_data | Presentation formatting |
| interface_alert | Message and redirect |
| model_var_replace_all | Placeholder substitution |

---
## 18. Final Observations
This template should be kept lean, didactic, and updated. Any new transversal functionality that repeats in multiple modules should become a reusable library and only referenced here.

> Update this documentation whenever changing the flow or adding a new pattern.

---
## 19. Updated Patterns (v1.15.0+)

### ✅ Correct Modal System
- **Modal BEFORE CodeMirror** in dependency order
- **manager_component() WITHOUT 'variables' parameter**
- **model_var_replace() individual** for each variable substitution
- Pattern implemented in admin-layouts and admin-components

### ✅ TailwindCSS Preview System
- Preview modal for visual modules
- Endpoint preview.php for HTML generation
- Multi-framework support (TailwindCSS/FomanticUI)
- Customized buttons with JavaScript callbacks

### ✅ Critical Inclusion Order
```
1. Modal Components (first)
2. CodeMirror Libraries (after) 
3. Custom Scripts (last)
```

### ✅ Field framework_css
- Native support since v1.15.0
- TailwindCSS vs FomanticUI Selectbox
- Mandatory validation for visual modules

**Knowledge Base:** admin-pages, admin-layouts, admin-components

````