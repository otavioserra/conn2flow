````markdown
# Module: admin-pages

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-pages` |
| **Name** | Page Administration |
| **Version** | `1.1.0` |
| **Category** | Administrative Module |
| **Complexity** | 🔴 High |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-pages** module is the **central system for creating and managing pages** in the Conn2Flow CMS. It allows creating, editing, and managing static and dynamic pages with an integrated visual editor, real-time preview, and support for multiple CSS frameworks.

## 🏗️ Main Features

### 📝 **Complete Page Editor**
- **Visual HTML editor**: Integrated WYSIWYG interface
- **Code editor**: CodeMirror with syntax highlighting
- **Real-time preview**: Instant visualization of changes
- **Multi-framework support**: TailwindCSS and FomanticUI
- **Dynamic templates**: System of variables and placeholders
- **Automatic validation**: HTML/CSS syntax checking

### 🎨 **Responsive Preview System**
- **Desktop preview**: Visualization for large screens
- **Tablet preview**: Simulation for tablets
- **Mobile preview**: Simulation for smartphones
- **Responsive modal**: Adaptable interface
- **Hot reload**: Automatic preview update

### 🔧 **Advanced Management**
- **Layout system**: Integration with the layouts module
- **Dynamic routing**: Configuration of custom URLs
- **Permission control**: Granular access system
- **Versioning**: Control of page versions
- **Integrated SEO**: Meta tags and automatic optimization

### 🌐 **Multi-framework CSS**
- **TailwindCSS**: Utility-first CSS framework
- **FomanticUI**: Component-based CSS framework
- **Per-page selection**: Specific CSS framework per page
- **Automatic compilation**: Automatic build of styles
- **Automatic purge**: Removal of unused CSS

## 🗄️ Database Structure

### Main Table: `pages`
```sql
CREATE TABLE pages (
    id_pages INT AUTO_INCREMENT PRIMARY KEY,
    id_users INT NOT NULL,                 -- Page creator
    name VARCHAR(255) NOT NULL,               -- Page name
    id VARCHAR(255) UNIQUE NOT NULL,          -- Unique identifier
    layout_id VARCHAR(255),                   -- Associated layout (string)
    type ENUM('system','page') DEFAULT 'page', -- Page type
    framework_css ENUM('tailwindcss','fomantic-ui') DEFAULT 'fomantic-ui',
    module VARCHAR(100),                      -- Associated module (if type=system)
    option VARCHAR(100),                       -- Module option
    path VARCHAR(500) NOT NULL,            -- Page URL
    html LONGTEXT,                            -- HTML content
    css LONGTEXT,                             -- Custom CSS
    root BOOLEAN DEFAULT FALSE,               -- Site root page
    no_permission BOOLEAN DEFAULT FALSE,      -- Page without permission check
    status CHAR(1) DEFAULT 'A',               -- Status (A=Active, D=Deleted)
    version INT DEFAULT 1,                     -- Version control
    creation_date DATETIME DEFAULT NOW(),      -- Creation date
    modification_date DATETIME DEFAULT NOW(),   -- Last modification
    
    INDEX idx_status (status),
    INDEX idx_path (path),
    INDEX idx_layout (layout_id),
    INDEX idx_type (type),
    INDEX idx_framework (framework_css),
    FOREIGN KEY (id_users) REFERENCES users(id_users)
);
```

### Relationships
```sql
-- Relationship with layouts (string-based)
SELECT p.*, l.name as layout_name 
FROM pages p 
LEFT JOIN layouts l ON p.layout_id = l.id 
WHERE p.status = 'A';

-- Relationship with modules (for pages of type=system)
SELECT p.*, m.name as module_name 
FROM pages p 
LEFT JOIN modules m ON p.module = m.identifier 
WHERE p.type = 'system';
```

## 📁 File and Resource Structure

### Resource Organization
```
admin-pages/
├── admin-pages.php           # Main controller
├── admin-pages.js            # Frontend JavaScript
├── admin-pages.json          # Settings and metadata
└── resources/                  # Resources by language
    └── en/
        ├── components/         # Reusable components
        │   └── modal-page.html
        ├── pages/              # Page templates
        │   ├── admin-pages.html        # Listing
        │   ├── admin-pages-edit.html # Editing
        │   └── admin-pages-add.html # Creation
        └── assets/             # Specific CSS/JS
```

## 🔧 Core Technical Features

### 📝 **Function: `admin_pages_add()`**
Main controller for creating new pages.

**Functionalities:**
- Validation of required fields
- Verification of unique URLs
- Processing of global variables
- Insertion into the database
- Redirection to editing

```php
function admin_pages_add() {
    global $_MANAGER;
    
    if (isset($_MANAGER['add-database'])) {
        // Validation of required fields
        interface_validate_required_fields([
            'fields' => [
                [
                    'rule' => 'required-text',
                    'field' => 'page-name',
                    'label' => 'Page Name'
                ],
                [
                    'rule' => 'required-text',
                    'field' => 'pagePath',
                    'label' => 'Page Path',
                    'min' => 1
                ]
            ]
        ]);
        
        // Check if path already exists
        $exists = interface_check_fields([
            'field' => 'path',
            'value' => database_escape_field($_REQUEST['pagePath'])
        ]);
        
        if ($exists) {
            interface_alert([
                'redirect' => true,
                'msg' => 'This path is already in use'
            ]);
            return;
        }
        
        // Process global variables
        $html = $_REQUEST['html'];
        $css = $_REQUEST['css'];
        
        // Convert template variables
        $html = preg_replace("/{{(.+?)}}/", "{{$1}}", $html);
        $css = preg_replace("/{{(.+?)}}/", "{{$1}}", $css);
        
        // Insert into database
        $fields = [
            ['id_users', manager_user()['id_users']],
            ['name', database_escape_field($_REQUEST['page-name'])],
            ['id', database_identifier([...])],
            ['layout_id', get_layout_id($_REQUEST['layout'])],
            ['type', database_escape_field($_REQUEST['type'])],
            ['framework_css', database_escape_field($_REQUEST['framework_css'])],
            ['module', database_escape_field($_REQUEST['module'])],
            ['option', database_escape_field($_REQUEST['page-option'])],
            ['path', database_escape_field($_REQUEST['pagePath'])],
            ['html', database_escape_field($html)],
            ['css', database_escape_field($css)],
            ['root', isset($_REQUEST['root']) ? '1' : '0'],
            ['no_permission', isset($_REQUEST['no_permission']) ? '1' : '0']
        ];
        
        database_insert_name($fields, 'pages');
        
        manager_redirect("admin-pages/edit/?id={$id}");
    }
}
```

### ✏️ **Function: `admin_pages_edit()`**
Controller for editing existing pages.

**Functionalities:**
- Loading of existing data
- Field updates
- Automatic versioning
- Automatic backup before editing
- Real-time preview

```php
function admin_pages_edit() {
    global $_MANAGER;
    
    $id = database_escape_field($_REQUEST['id']);
    
    if (isset($_MANAGER['edit-database'])) {
        // Load current data
        $current_page = database_select_name(
            "html, css, version",
            "pages",
            "WHERE id = '{$id}' AND status = 'A'"
        );
        
        if (!$current_page) {
            manager_error('Page not found');
            return;
        }
        
        // Create backup if there were significant changes
        $current_html = $current_page[0]['html'];
        $new_html = database_escape_field($_REQUEST['html']);
        
        if (strlen($new_html) != strlen($current_html)) {
            create_page_backup($id, $current_page[0]);
        }
        
        // Update data
        $update_fields = [
            ['name', database_escape_field($_REQUEST['page-name'])],
            ['layout_id', get_layout_id($_REQUEST['layout'])],
            ['framework_css', database_escape_field($_REQUEST['framework_css'])],
            ['html', $new_html],
            ['css', database_escape_field($_REQUEST['css'])],
            ['version', (int)$current_page[0]['version'] + 1],
            ['modification_date', 'NOW()']
        ];
        
        database_update_name($update_fields, 'pages', "WHERE id = '{$id}'");
        
        manager_redirect("admin-pages/edit/?id={$id}&success=1");
    }
    
    // Load data for form
    load_page_data($id);
}
```

### 📋 **Function: `admin_pages_list()`**
Listing interface with filters and search.

**Functionalities:**
- Paginated listing
- Filters by type, framework, status
- Search by name and content
- Multiple sorting
- Batch actions

```php
function admin_pages_list() {
    global $_MANAGER;
    
    // Filter parameters
    $filters = get_listing_filters();
    $sort_order = $_REQUEST['order'] ?? 'modification_date DESC';
    $page = (int)($_REQUEST['page'] ?? 1);
    $per_page = 20;
    
    // Build WHERE clause
    $where = "WHERE status = 'A'";
    
    if ($filters['type']) {
        $where .= " AND type = '{$filters['type']}'";
    }
    
    if ($filters['framework']) {
        $where .= " AND framework_css = '{$filters['framework']}'";
    }
    
    if ($filters['search']) {
        $search = database_escape_field($filters['search']);
        $where .= " AND (name LIKE '%{$search}%' OR html LIKE '%{$search}%')";
    }
    
    // Count total
    $total = database_select_count('pages', $where);
    
    // Fetch data
    $pages = database_select_name(
        "id_pages, name, id, path, type, framework_css, modification_date",
        "pages",
        "{$where} ORDER BY {$sort_order} LIMIT " . 
        (($page - 1) * $per_page) . ", {$per_page}"
    );
    
    // Render page listing
    render_pages_listing($pages, $total, $page, $per_page);
}
```

## 🎨 User Interface

### 📝 **Page Editor**
```html
<div class="page-editor">
    <!-- Navigation tabs -->
    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="visual">Visual Editor</a>
        <a class="item" data-tab="html-code">HTML Code</a>
        <a class="item" data-tab="css">CSS</a>
        <a class="item" data-tab="settings">Settings</a>
    </div>
    
    <!-- Tab: Visual Editor -->
    <div class="ui bottom attached tab segment active" data-tab="visual">
        <div class="ui form">
            <div class="field">
                <label>Page Content</label>
                <div id="visual-editor" class="html-editor">
                    <!-- CKEditor will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tab: HTML Code -->
    <div class="ui bottom attached tab segment" data-tab="html-code">
        <div class="field">
            <label>HTML Code</label>
            <textarea class="codemirror-html" name="html">
                {{html_content}}
            </textarea>
        </div>
    </div>
    
    <!-- Tab: CSS -->
    <div class="ui bottom attached tab segment" data-tab="css">
        <div class="field">
            <label>Custom CSS</label>
            <textarea class="codemirror-css" name="css">
                {{custom_css}}
            </textarea>
        </div>
    </div>
    
    <!-- Tab: Settings -->
    <div class="ui bottom attached tab segment" data-tab="settings">
        <div class="ui form">
            <div class="three fields">
                <div class="field">
                    <label>Page Name</label>
                    <input type="text" name="page-name" value="{{name}}" required>
                </div>
                <div class="field">
                    <label>Path (URL)</label>
                    <input type="text" name="pagePath" value="{{path}}" required>
                </div>
                <div class="field">
                    <label>Layout</label>
                    <select name="layout" class="ui dropdown">
                        <option value="">Select a layout</option>
                        {{layout_options}}
                    </select>
                </div>
            </div>
            
            <div class="three fields">
                <div class="field">
                    <label>Type</label>
                    <select name="type" class="ui dropdown">
                        <option value="page">Page</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="field">
                    <label>CSS Framework</label>
                    <select name="framework_css" class="ui dropdown">
                        <option value="fomantic-ui">Fomantic-UI</option>
                        <option value="tailwindcss">TailwindCSS</option>
                    </select>
                </div>
                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="root">
                        <label>Root Page</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action buttons -->
    <div class="ui actions">
        <button type="button" class="ui button preview-button">
            <i class="eye icon"></i>
            Preview
        </button>
        <button type="submit" class="ui primary button">
            <i class="save icon"></i>
            Save Page
        </button>
        <a href="admin-pages/" class="ui button">
            <i class="arrow left icon"></i>
            Back
        </a>
    </div>
</div>
```

### 🔍 **Responsive Preview Modal**
```html
<div class="ui modal" id="preview-modal">
    <div class="header">
        <i class="eye icon"></i>
        Page Preview
    </div>
    <div class="content">
        <!-- Device selectors -->
        <div class="ui secondary menu">
            <a class="item active" data-device="desktop">
                <i class="desktop icon"></i>
                Desktop
            </a>
            <a class="item" data-device="tablet">
                <i class="tablet icon"></i>
                Tablet
            </a>
            <a class="item" data-device="mobile">
                <i class="mobile icon"></i>
                Mobile
            </a>
        </div>
        
        <!-- Preview container -->
        <div class="preview-container">
            <iframe id="preview-frame" 
                    src="about:blank" 
                    width="100%" 
                    height="600px"
                    frameborder="0">
            </iframe>
        </div>
    </div>
    <div class="actions">
        <button class="ui button" onclick="$('#preview-modal').modal('hide')">
            Close
        </button>
        <button class="ui primary button" onclick="savePage()">
            <i class="save icon"></i>
            Save
        </button>
    </div>
</div>
```

## 🖥️ Core JavaScript

### 🔧 **CodeMirror Initialization**
```javascript
$(document).ready(function() {
    var codemirrors_instances = [];
    
    // Configure CSS editor
    var css_editors = document.getElementsByClassName("codemirror-css");
    for (var i = 0; i < css_editors.length; i++) {
        var cssEditor = CodeMirror.fromTextArea(css_editors[i], {
            lineNumbers: true,
            lineWrapping: true,
            styleActiveLine: true,
            matchBrackets: true,
            mode: "css",
            theme: "tomorrow-night-bright",
            indentUnit: 4,
            extraKeys: {
                "F11": function(cm) {
                    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                },
                "Esc": function(cm) {
                    if (cm.getOption("fullScreen")) {
                        cm.setOption("fullScreen", false);
                    }
                }
            }
        });
        
        cssEditor.setSize('100%', 500);
        codemirrors_instances.push(cssEditor);
    }
    
    // Configure HTML editor
    var html_editors = document.getElementsByClassName("codemirror-html");
    for (var i = 0; i < html_editors.length; i++) {
        var htmlEditor = CodeMirror.fromTextArea(html_editors[i], {
            lineNumbers: true,
            lineWrapping: true,
            styleActiveLine: true,
            matchBrackets: true,
            mode: "htmlmixed",
            theme: "tomorrow-night-bright",
            indentUnit: 4,
            autoCloseTags: true,
            extraKeys: {
                "F11": function(cm) {
                    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                },
                "Esc": function(cm) {
                    if (cm.getOption("fullScreen")) {
                        cm.setOption("fullScreen", false);
                    }
                }
            }
        });
        
        htmlEditor.setSize('100%', 500);
        codemirrors_instances.push(htmlEditor);
    }
});
```

### 📱 **Responsive Preview System**
```javascript
// Responsive preview control
function initPreviewSystem() {
    $('.preview-button').click(function() {
        var html = getEditorContent('html');
        var css = getEditorContent('css');
        var framework = $('select[name="framework_css"]').val();
        
        generatePreview(html, css, framework);
        $('#preview-modal').modal('show');
    });
    
    // Switch between devices
    $('.preview-device-selector .item').click(function() {
        var device = $(this).data('device');
        
        $('.preview-device-selector .item').removeClass('active');
        $(this).addClass('active');
        
        updatePreviewDevice(device);
    });
}

function generatePreview(html, css, framework) {
    var previewHtml = buildPreviewHTML(html, css, framework);
    
    // Create blob URL for preview
    var blob = new Blob([previewHtml], {type: 'text/html'});
    var url = URL.createObjectURL(blob);
    
    $('#preview-frame').attr('src', url);
    
    // Clean up URL after loading
    $('#preview-frame').on('load', function() {
        URL.revokeObjectURL(url);
    });
}

function buildPreviewHTML(content, css, framework) {
    var framework_css = '';
    
    if (framework === 'tailwindcss') {
        framework_css = '<link href="https://cdn.tailwindcss.com" rel="stylesheet">';
    } else {
        framework_css = '<link href="/fomantic-ui/semantic.min.css" rel="stylesheet">';
    }
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Preview</title>
            ${framework_css}
            <style>${css}</style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `;
}

function updatePreviewDevice(device) {
    var iframe = $('#preview-frame');
    
    switch(device) {
        case 'desktop':
            iframe.css({
                'width': '100%',
                'max-width': 'none'
            });
            break;
        case 'tablet':
            iframe.css({
                'width': '768px',
                'max-width': '100%'
            });
            break;
        case 'mobile':
            iframe.css({
                'width': '375px',
                'max-width': '100%'
            });
            break;
    }
}
```

### 🔄 **Automatic Backup System**
```javascript
// Automatic backup system
function initAutoBackup() {
    var backupInterval = 30000; // 30 seconds
    var lastBackup = Date.now();
    
    setInterval(function() {
        if (hasContentChanged() && (Date.now() - lastBackup > backupInterval)) {
            createAutoBackup();
            lastBackup = Date.now();
        }
    }, 5000); // Check every 5 seconds
}

function hasContentChanged() {
    var currentHtml = getEditorContent('html');
    var currentCss = getEditorContent('css');
    
    var lastHtml = localStorage.getItem('page_backup_html');
    var lastCss = localStorage.getItem('page_backup_css');
    
    return (currentHtml !== lastHtml) || (currentCss !== lastCss);
}

function createAutoBackup() {
    var html = getEditorContent('html');
    var css = getEditorContent('css');
    var timestamp = Date.now();
    
    localStorage.setItem('page_backup_html', html);
    localStorage.setItem('page_backup_css', css);
    localStorage.setItem('page_backup_timestamp', timestamp);
    
    // Discreetly notify user
    showBackupNotification();
}

function restoreFromBackup() {
    var backupHtml = localStorage.getItem('page_backup_html');
    var backupCss = localStorage.getItem('page_backup_css');
    var timestamp = localStorage.getItem('page_backup_timestamp');
    
    if (backupHtml && backupCss && timestamp) {
        if (confirm('Do you want to restore the automatic backup?')) {
            setEditorContent('html', backupHtml);
            setEditorContent('css', backupCss);
            
            clearBackup();
        }
    }
}
```

## ⚙️ Settings and Parameters

### 📋 **Module Settings (JSON)**
```json
{
    "version": "1.1.0",
    "libraries": ["interface", "html"],
    "table": {
        "name": "pages",
        "id": "id",
        "numeric_id": "id_pages",
        "status": "status",
        "version": "version",
        "creation_date": "creation_date",
        "modification_date": "modification_date"
    },
    "selectDataType": [
        {"text": "System", "value": "system"},
        {"text": "Page", "value": "page"}
    ],
    "selectDataFrameworkCSS": [
        {"text": "Fomantic-UI", "value": "fomantic-ui"},
        {"text": "TailwindCSS", "value": "tailwindcss"}
    ],
    "preview": {
        "devices": {
            "desktop": {"width": "100%", "height": "600px"},
            "tablet": {"width": "768px", "height": "600px"},
            "mobile": {"width": "375px", "height": "600px"}
        }
    }
}
```

### 🎛️ **Editor Settings**
```php
// CodeMirror settings
$codemirror_config = [
    'theme' => 'tomorrow-night-bright',
    'lineNumbers' => true,
    'lineWrapping' => true,
    'styleActiveLine' => true,
    'matchBrackets' => true,
    'autoCloseTags' => true,
    'indentUnit' => 4,
    'height' => 500
];

// Validation settings
$validation_rules = [
    'name_min' => 3,
    'name_max' => 255,
    'path_min' => 1,
    'path_max' => 500,
    'html_max' => 1000000, // 1MB
    'css_max' => 100000    // 100KB
];
```

## 🔌 Integration with Other Modules

### 🎨 **Layouts Module**
Bidirectional integration for layout management:

```php
// Get available layouts
function get_available_layouts() {
    return database_select_name(
        "id, id_layouts, name",
        "layouts",
        "WHERE status = 'A' ORDER BY name ASC"
    );
}

// Associate page with layout
function associate_page_layout($page_id, $layout_id) {
    database_update_name(
        [['layout_id', $layout_id]],
        'pages',
        "WHERE id = '{$page_id}'"
    );
}
```

### 🧩 **Components Module**
Integration for inserting components into pages:

```php
// Render components on the page
function process_page_components($html) {
    // Search for component patterns: {{component:name}}
    return preg_replace_callback(
        '/{{component:([^}]+)}}/',
        function($matches) {
            return manager_component(['id' => $matches[1]]);
        },
        $html
    );
}
```

### 🔀 **Routing System**
Integration with the URL system:

```php
// Register page routes
function register_page_routes() {
    $pages = database_select_name(
        "id, path, type, module, option",
        "pages",
        "WHERE status = 'A' AND root = 1"
    );
    
    foreach ($pages as $page) {
        manager_route_register([
            'path' => $page['path'],
            'module' => $page['type'] === 'system' ? $page['module'] : 'pages',
            'option' => $page['option'] ?: 'view',
            'parameters' => ['id' => $page['id']]
        ]);
    }
}
```

## 🛡️ Security and Validation

### 🔒 **Input Validations**
```php
// Sanitize page HTML
function sanitize_page_html($html) {
    // Allow only safe tags
    $allowed_tags = '<p><div><span><h1><h2><h3><h4><h5><h6><strong><em><u><a><img><ul><ol><li><table><tr><td><th>';
    $html = strip_tags($html, $allowed_tags);
    
    // Remove scripts and JavaScript events
    $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
    $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
    
    return $html;
}

// Validate CSS
function validate_css($css) {
    // Remove insecure @import and @font-face
    $css = preg_replace('/@import\s+url\([^)]*\);?/i', '', $css);
    $css = preg_replace('/expression\s*\([^)]*\)/i', '', $css);
    $css = preg_replace('/javascript\s*:/i', '', $css);
    
    return $css;
}

// Check page permissions
function check_page_permission($action, $page_id = null) {
    if (!manager_user_logged_in()) {
        return false;
    }
    
    $user = manager_user();
    
    // Check general permission
    if (!manager_user_permission('admin-pages', $action)) {
        return false;
    }
    
    // Check page ownership (if editing)
    if ($page_id && $action === 'edit') {
        $page = database_select_name(
            "id_users",
            "pages",
            "WHERE id = '{$page_id}' AND status = 'A'"
        );
        
        if ($page && $page[0]['id_users'] != $user['id_users']) {
            // Check if admin
            if (!manager_user_is_admin()) {
                return false;
            }
        }
    }
    
    return true;
}
```

### 🛡️ **Attack Prevention**
```php
// Protect against XSS
function protect_page_xss($html) {
    // Escape template variables
    $html = preg_replace('/{{([^}]+)}}/', '{{htmlspecialchars($1)}}', $html);
    
    // Validate URLs in links and images
    $html = preg_replace_callback(
        '/(href|src)=["\']([^"\']+)["\']/i',
        function($matches) {
            $url = $matches[2];
            if (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0) {
                return $matches[0];
            }
            return $matches[1] . '="#"';
        },
        $html
    );
    
    return $html;
}

// Validate URL paths
function validate_url_path($path) {
    // Must start and end with /
    if (!preg_match('/^\/.*\/$/', $path)) {
        return false;
    }
    
    // Cannot contain dangerous characters
    if (preg_match('/[<>"]/', $path)) {
        return false;
    }
    
    // Cannot contain .. (path traversal)
    if (strpos($path, '..') !== false) {
        return false;
    }
    
    return true;
}
```

## 📈 Performance and Optimization

### ⚡ **Performance Strategies**
- **Page caching**: Smart caching system
- **Automatic minification**: Optimized HTML and CSS
- **Lazy loading**: On-demand loading of the editor
- **Gzip compression**: Reduction of transfer size
- **CDN ready**: Prepared for use with a CDN

### 🗃️ **Caching System**
```php
// Cache rendered pages
function cache_rendered_page($page_id, $rendered_html) {
    $cache_key = "page_{$page_id}";
    $cache_time = 3600; // 1 hour
    
    manager_cache_set($cache_key, [
        'html' => $rendered_html,
        'timestamp' => time(),
        'checksum' => md5($rendered_html)
    ], $cache_time);
}

function get_cached_page($page_id) {
    $cache_key = "page_{$page_id}";
    $cached = manager_cache_get($cache_key);
    
    if ($cached && is_valid_cache($cached)) {
        return $cached['html'];
    }
    
    return false;
}

// Smart cache invalidation
function invalidate_page_cache($page_id) {
    $cache_key = "page_{$page_id}";
    manager_cache_delete($cache_key);
    
    // Also invalidate cache of related pages
    $related_pages = get_related_pages($page_id);
    foreach ($related_pages as $related) {
        manager_cache_delete("page_{$related['id']}");
    }
}
```

## 🧪 Tests and Validation

### ✅ **Test Cases**
- **Page creation**: New page with all fields
- **Content editing**: Modification of HTML and CSS
- **Responsive preview**: Test in different resolutions
- **Form validation**: Required fields and format
- **Layout system**: Association and change of layouts
- **CSS Framework**: Switching between TailwindCSS and FomanticUI
- **Permissions**: Access control by profile

### 🐛 **Known Issues**
- **CodeMirror refresh**: Manual refresh required in tabs
- **Preview iframe**: Cross-origin security limitations
- **Automatic backup**: May consume localStorage
- **CKEditor editor**: Occasional conflicts with CodeMirror

## 📊 Metrics and Analytics

### 📈 **Module KPIs**
- **Total pages**: Number of pages in the system
- **Pages per framework**: Distribution of TailwindCSS vs FomanticUI
- **Edit rate**: Frequency of modifications
- **Loading performance**: Editor response time
- **Feature usage**: Which features are most used

### 📋 **Audit Logs**
```php
// Log critical operations
function log_page_operation($action, $page_id, $details = []) {
    $user = manager_user();
    
    database_insert_name([
        ['module', 'admin-pages'],
        ['action', $action],
        ['page_id', $page_id],
        ['user_id', $user['id_users']],
        ['details', json_encode($details)],
        ['ip', $_SERVER['REMOTE_ADDR']],
        ['user_agent', $_SERVER['HTTP_USER_AGENT']],
        ['timestamp', 'NOW()']
    ], 'audit_log');
}
```

## 🚀 Roadmap and Improvements

### ✅ **Implemented (v1.1.0)**
- Integrated visual and code editors
- Responsive preview in modal
- Support for TailwindCSS and FomanticUI
- Layout system
- Permission control
- Automatic backup

````