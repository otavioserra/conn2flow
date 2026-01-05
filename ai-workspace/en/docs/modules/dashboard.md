````markdown
# Module: dashboard

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `dashboard` |
| **Name** | Control Panel |
| **Version** | `1.0.0` |
| **Category** | Core Functional Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **dashboard** module is the **main control center** of the Conn2Flow CMS system. It serves as the entry point for administrators after login, offering an overview of the system, informational widgets, notifications, and quick access to the most used functionalities.

## 🏗️ Main Features

### 🏠 **Main Panel**
- **Administrative home page**: Post-login landing page
- **Summary widgets**: Real-time statistics
- **Quick access**: Links to the most used modules
- **Centralized notifications**: Toast and alert system
- **System status**: Health and performance information

### 🔔 **Notification System (Toasts)**
- **Smart toasts**: Contextual notifications
- **Configurable time**: Customizable duration
- **Action buttons**: Direct interactions in notifications
- **Specific rules**: Custom logic by type
- **Preference persistence**: Remember user choices

### 🔄 **Update System**
- **Automatic check**: Check for available versions
- **Update notification**: Non-intrusive alerts
- **Permission management**: Only admins see updates
- **Version comparison**: Smart versioning control
- **Automated redirection**: Guided update flow

### 🧪 **Test Environment**
- **Test dashboard**: Isolated environment for development
- **Pre-publication**: Staging area
- **Resource validation**: Tests before deployment

## 📊 User Interface

### 🏠 **Main Layout**
```html
<div class="dashboard-container">
    <!-- Header with user information -->
    <div class="dashboard-header">
        <h1>Welcome to Conn2Flow</h1>
        <div class="user-info">
            <span class="username">{{user_name}}</span>
            <a href="#" class="logout">Logout</a>
        </div>
    </div>
    
    <!-- Widget grid -->
    <div class="dashboard-widgets">
        <div class="widget-row">
            <div class="widget statistics">
                <h3>General Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="number">{{total_pages}}</span>
                        <span class="label">Pages</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{total_posts}}</span>
                        <span class="label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{total_files}}</span>
                        <span class="label">Files</span>
                    </div>
                </div>
            </div>
            
            <div class="widget quick-actions">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <a href="admin-pages/add/" class="action-button">
                        <i class="plus icon"></i>
                        New Page
                    </a>
                    <a href="posts/add/" class="action-button">
                        <i class="edit icon"></i>
                        New Post
                    </a>
                    <a href="admin-files/" class="action-button">
                        <i class="upload icon"></i>
                        Upload File
                    </a>
                </div>
            </div>
        </div>
        
        <div class="widget-row">
            <div class="widget recent-activity">
                <h3>Recent Activity</h3>
                <div class="activity-list">
                    <!-- List of recent activities -->
                </div>
            </div>
            
            <div class="widget system-status">
                <h3>System Status</h3>
                <div class="status-indicators">
                    <div class="status-item">
                        <i class="green circle icon"></i>
                        System Online
                    </div>
                    <div class="status-item">
                        <i class="blue circle icon"></i>
                        Version v1.16.0
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 🔔 **Toast System**
```html
<div class="ui toast-container">
    <div class="ui toast update-toast" data-toast-id="update">
        <div class="content">
            <div class="header">
                <i class="download icon"></i>
                Update Available
            </div>
            <div class="description">
                An update is available. Do you want to update now?
            </div>
        </div>
        <div class="actions">
            <button class="ui mini positive button update-now">
                Update Now
            </button>
            <button class="ui mini button update-later">
                Do Not Update
            </button>
        </div>
    </div>
</div>
```

## 🔧 Technical Features

### 📡 **Toast System**

#### Function: `dashboard_toast($params)`
Central manager for toast notifications.

**Parameters:**
- `id` (string): Unique identifier for the toast
- `options` (array): Display settings
- `buttons` (array): Action buttons
- `rule` (string): Specific behavior rule

**Available settings:**
```php
$toast_config = [
    'change_time' => 5000,                    // Display time (ms)
    'updateNotShowToastTime' => 10080,       // Time to not show again (min)
    'default_options' => [
        'displayTime' => 10000,              // Default display time
        'class' => 'black'                   // Default CSS class
    ]
];
```

#### Example of use:
```php
dashboard_toast([
    'id' => 'welcome',
    'options' => [
        'title' => 'Welcome!',
        'message' => 'System loaded successfully.',
        'class' => 'success',
        'displayTime' => 5000
    ],
    'buttons' => [
        'positive' => [
            'text' => 'Got it',
            'action' => 'dismiss'
        ]
    ]
]);
```

### 🔄 **Update Check System**

#### Function: `dashboard_toast_updates()`
Checks and notifies about available updates.

**Operating logic:**
1. **Privilege check**: Only admins see notifications
2. **Version comparison**: Local version vs. available version
3. **Conditional display**: Toast only if a new version is available
4. **Preference management**: Remembers the user's choice

```php
function dashboard_toast_updates() {
    global $_MANAGER;
    
    // Check if user is admin
    $host_check = manager_session_variable('host-check-'.$_MANAGER['user-id']);
    
    if(isset($host_check['admin_privileges'])) {
        // Get current system version
        $current_version = $hosts[0]['manager_client_version_num'];
        $available_version = $_MANAGER['manager-client']['version_num'];
        
        // Compare versions
        if($available_version > (int)$current_version) {
            // Display update toast
            dashboard_toast([
                'id' => 'update',
                'rule' => 'update',
                'options' => [
                    'title' => 'Update Available',
                    'message' => 'An update is available. Do you want to update now?'
                ],
                'buttons' => [
                    'positive' => [
                        'text' => 'Update Now',
                        'action' => 'redirect',
                        'url' => 'admin-updates/'
                    ],
                    'negative' => [
                        'text' => 'Do Not Update',
                        'action' => 'dismiss_with_delay'
                    ]
                ]
            ]);
        }
    }
}
```

## 📱 Core JavaScript

### 🔔 **Toast Manager**
```javascript
// Global toast configuration
var toastConfig = {
    change_time: manager.toasts_options.change_time,
    updateNotShowToastTime: manager.toasts_options.updateNotShowToastTime,
    default_options: manager.toasts_options.default_options
};

// Function to display toast
function showToast(toastId, options) {
    var toast = manager.toasts[toastId];
    
    if (toast && toast.rule !== 'dismissed') {
        $('body').toast({
            title: toast.options.title,
            message: toast.options.message,
            displayTime: toast.options.displayTime || toastConfig.default_options.displayTime,
            class: toast.options.class || toastConfig.default_options.class,
            
            // Configure buttons if they exist
            actions: toast.buttons ? formatToastButtons(toast.buttons) : undefined,
            
            // Close callback
            onHide: function() {
                if (toast.rule === 'update') {
                    // Specific logic for update toast
                    handleUpdateToastClose(toastId);
                }
            }
        });
    }
}

// Formatting buttons for toasts
function formatToastButtons(buttons) {
    var actions = [];
    
    if (buttons.positive) {
        actions.push({
            text: buttons.positive.text,
            class: 'positive',
            click: function() {
                handleToastAction(buttons.positive.action, buttons.positive);
            }
        });
    }
    
    if (buttons.negative) {
        actions.push({
            text: buttons.negative.text,
            class: 'negative',
            click: function() {
                handleToastAction(buttons.negative.action, buttons.negative);
            }
        });
    }
    
    return actions;
}

// Toast action handler
function handleToastAction(action, buttonConfig) {
    switch(action) {
        case 'redirect':
            if (buttonConfig.url) {
                window.location.href = buttonConfig.url;
            }
            break;
            
        case 'dismiss':
            // Just close
            break;
            
        case 'dismiss_with_delay':
            // Close and remember for X time
            localStorage.setItem('toast_dismissed_' + Date.now(), 
                JSON.stringify({
                    timestamp: Date.now(),
                    duration: toastConfig.updateNotShowToastTime * 60 * 1000
                })
            );
            break;
    }
}
```

### 📊 **Dynamic Widgets**
```javascript
// Loading data for widgets
function loadDashboardWidgets() {
    // Statistics widget
    $.get('dashboard/?ajax=get_statistics', function(data) {
        $('.widget.statistics .stats-grid').html(data.html);
    });
    
    // Recent activity widget
    $.get('dashboard/?ajax=get_recent_activity', function(data) {
        $('.widget.recent-activity .activity-list').html(data.html);
    });
    
    // System status widget
    $.get('dashboard/?ajax=get_system_status', function(data) {
        $('.widget.system-status .status-indicators').html(data.html);
    });
}

// Auto-refresh widgets
setInterval(function() {
    loadDashboardWidgets();
}, 30000); // Update every 30 seconds
```

## 🗺️ Routing and Pages

### 📄 **Available Pages**
| Route | Option | Function | Access |
|------|-------|--------|--------|
| `dashboard/` | `start` | Main panel | Admin/Manager |
| `dashboard-tests/` | `list` | Test environment | Development |
| `octavio-page/` | `dashboard-test` | Pre-publication | Development |

### 🔀 **Routing System**
```php
function dashboard_start() {
    global $_MANAGER;
    
    manager_include_libraries();
    
    if ($_MANAGER['ajax']) {
        switch ($_MANAGER['ajax-option']) {
            case 'get_statistics': dashboard_ajax_statistics(); break;
            case 'get_recent_activity': dashboard_ajax_activity(); break;
            case 'get_system_status': dashboard_ajax_status(); break;
        }
    } else {
        switch ($_MANAGER['option']) {
            case 'start': dashboard_main(); break;
            case 'list': dashboard_tests(); break;
            case 'dashboard-test': dashboard_preview(); break;
            default: dashboard_main();
        }
    }
}
```

## 📊 Available Widgets

### 📈 **Statistics Widget**
```php
function dashboard_widget_statistics() {
    // Count pages
    $total_pages = database_select_count('pages', "WHERE status='A'");
    
    // Count posts
    $total_posts = database_select_count('posts', "WHERE status='A'");
    
    // Count files
    $total_files = database_select_count('files', "WHERE status='A'");
    
    // Count users
    $total_users = database_select_count('users', "WHERE status='A'");
    
    return [
        'pages' => $total_pages,
        'posts' => $total_posts,
        'files' => $total_files,
        'users' => $total_users
    ];
}
```

### 📋 **Recent Activity Widget**
```php
function dashboard_widget_recent_activity() {
    $activities = database_select_name(
        "type, description, creation_date, id_users",
        "activities",
        "WHERE creation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
         ORDER BY creation_date DESC 
         LIMIT 10"
    );
    
    $html = '';
    foreach ($activities as $activity) {
        $html .= '<div class="activity-item">';
        $html .= '<i class="icon ' . $activity['type'] . '"></i>';
        $html .= '<span class="description">' . $activity['description'] . '</span>';
        $html .= '<span class="time">' . format_time_ago($activity['creation_date']) . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}
```

### 🔧 **System Status Widget**
```php
function dashboard_widget_system_status() {
    $status = [
        'server' => check_server_health(),
        'database' => check_database_health(),
        'storage' => check_storage_health(),
        'version' => $_MANAGER['version'],
        'updates' => check_available_updates()
    ];
    
    return $status;
}
```

## ⚙️ JSON Settings

### 📋 **dashboard.json Structure**
```json
{
    "version": "1.0.0",
    "libraries": ["interface", "html"],
    "toasts": {
        "change_time": 5000,
        "updateNotShowToastTime": 10080,
        "default_options": {
            "displayTime": 10000,
            "class": "black"
        }
    },
    "resources": {
        "en": {
            "pages": [
                {
                    "name": "Dashboard",
                    "id": "dashboard",
                    "layout": "manager-administrative-layout",
                    "path": "dashboard/",
                    "type": "system",
                    "option": "start",
                    "root": true
                }
            ],
            "variables": [
                {
                    "id": "logout-grup",
                    "value": "Logout",
                    "type": "string"
                },
                {
                    "id": "toast-update-title",
                    "value": "Update Available",
                    "type": "string"
                }
            ]
        }
    }
}
```

## 🛡️ Security and Permissions

### 🔐 **Access Control**
- **Mandatory authentication**: Only logged-in users
- **Profile verification**: Admin/Manager/Host
- **Validated session**: Continuous authenticity check
- **Automatic timeout**: Logout due to inactivity

### 🛡️ **Security Validations**
```php
// Check if user is authenticated
if (!manager_user_logged_in()) {
    manager_redirect('login/');
    exit;
}

// Check specific permissions
if (!manager_user_permission('dashboard', 'view')) {
    manager_error('Access denied to dashboard');
    exit;
}

// Validate active session
if (!manager_session_is_valid()) {
    manager_logout();
    manager_redirect('login/?error=session_expired');
    exit;
}
```

## 📈 Performance and Optimization

### ⚡ **Performance Strategies**
- **Widget caching**: Results cached for 5 minutes
- **Lazy loading**: Asynchronous loading of non-critical data
- **Optimized queries**: Indexes on all queries
- **Compression**: Gzip for static resources
- **CDN ready**: Prepared for external CDN

### 🗃️ **Caching System**
```php
// Dashboard statistics cache
function dashboard_get_cached_stats() {
    $cache_key = 'dashboard_stats_' . $_MANAGER['user-id'];
    $cache_time = 300; // 5 minutes
    
    $cached = manager_cache_get($cache_key);
    if ($cached && (time() - $cached['timestamp']) < $cache_time) {
        return $cached['data'];
    }
    
    $stats = dashboard_widget_statistics();
    manager_cache_set($cache_key, [
        'timestamp' => time(),
        'data' => $stats
    ]);
    
    return $stats;
}
```

## 🔗 Integration with Other Modules

### 🔄 **Update System**
Direct integration with `admin-updates`:
```php
// Check for available updates
if (module_exists('admin-updates')) {
    $updates = admin_updates_check_available();
    if ($updates) {
        dashboard_toast_updates();
    }
}
```

### 👥 **User System**
Integration with user modules:
```php
// Logged-in user information
$user = manager_user();
$permissions = manager_user_permissions();
$preferences = user_get_preferences($user['id_users']);
```

### 📊 **Content Modules**
Statistics from content modules:
```php
// Integration with various modules for statistics
$stats = [
    'pages' => pages_count_all(),
    'posts' => posts_count_all(),
    'files' => admin_files_count_all(),
    'users' => users_count_all()
];
```

## 🧪 Tests and Development

### ✅ **Test Environment**
- **Test dashboard**: Isolated area for development
- **Data simulation**: Mock data for tests
- **Integrated debug**: Detailed logs of operations
- **Performance monitoring**: Real-time metrics

### 🔍 **Debugging**
```php
// Toast debug
if ($_MANAGER['debug']) {
    error_log('Dashboard toast created: ' . json_encode($toast));
}

// Widget debug
if ($_MANAGER['debug']) {
    $render_time = microtime(true) - $start_time;
    error_log("Widget rendered in {$render_time}ms");
}
```

## 🚀 Roadmap

### ✅ **Implemented (v1.0.0)**
- Basic functional dashboard
- Toast system
- Update check
- Statistics widgets
- Responsive interface

### 🚧 **In Development (v1.1.0)**
- Customizable dashboard
- Drag & drop widgets
- Interactive charts
- Push notifications
- Dark mode theme

### 🔮 **Planned (v2.0.0)**
- AI-powered insights
- Dashboards by profile
- Third-party widgets
- Widget API
- Mobile app integration

## 📖 Conclusion

The **dashboard** module serves as the heart of the Conn2Flow administrative system, offering a centralized and intuitive experience for managing the CMS. With its robust notification system, informational widgets, and deep integration with other modules, it represents an essential control point for administrators.

**Main features:**
- ✅ **Centralized interface** for administration
- ✅ **Smart notification system**
- ✅ **Real-time informational widgets**
- ✅ **Automatic update check**
- ✅ **Optimized performance** with caching

**Status**: ✅ **Production - Stable**  
**Maintainers**: Conn2Flow Core Team  
**Last update**: August 31, 2025

````