# Library: lang.php

> 🌐 Internationalization and translation system

## Overview

The `lang.php` library provides a complete internationalization (i18n) system for Conn2Flow, allowing string translation through JSON dictionary files. The system uses custom functions instead of gettext, offering more flexibility and control.

**Location**: `gestor/bibliotecas/lang.php`  
**Version**: 1.0  
**Author**: Otavio Serra  
**Date**: 12/08/2025  
**Total Functions**: 3

## Dependencies

- JSON dictionary files (e.g., `pt-br.json`, `en.json`)
- Native PHP functions: `file_exists()`, `file_get_contents()`, `json_decode()`

## Global Variables

```php
$GLOBALS['lang'] = 'pt-br';           // Default language
$GLOBALS['dicionario'] = array();     // Loaded dictionary
```

## Main Functions

### carregar_dicionario()

Loads the language dictionary from a JSON file.

**Signature:**
```php
function carregar_dicionario($lang = 'pt-br', $base = '')
```

**Parameters:**
- `$lang` (string) - Optional - Language code (default: 'pt-br')
- `$base` (string) - Optional - Base path relative to library directory

**Return:**
- (array) - Associative array with translations or empty array if file not found

**JSON File Structure:**
```json
{
    "welcome_message": "Welcome to the system",
    "login_button": "Login",
    "logout_button": "Logout",
    "error_required_field": "This field is required",
    "success_save": "Data saved successfully"
}
```

**Usage Example:**
```php
// Load Portuguese dictionary
$dict_pt = carregar_dicionario('pt-br');

// Load English dictionary
$dict_en = carregar_dicionario('en');

// Load from specific subdirectory
$dict = carregar_dicionario('pt-br', '/../langs');
```

**Internal Operation:**
```php
// 1. Builds file path
$caminhoBase = realpath(__DIR__ . $base) . '/';
$caminhoArquivo = $caminhoBase . $lang . '.json';
// Result: /path/to/bibliotecas/pt-br.json

// 2. Checks existence
if (file_exists($caminhoArquivo)) {
    // 3. Reads and decodes JSON
    $jsonContent = file_get_contents($caminhoArquivo);
    $dicionario = json_decode($jsonContent, true);
}
```

---

### __t()

Translates a language key using the custom dictionary with placeholder support.

**Signature:**
```php
function __t($key, $replacements = [])
```

**Parameters:**
- `$key` (string) - **Required** - Translation key
- `$replacements` (array) - Optional - Associative array with values to replace placeholders

**Return:**
- (string) - Translated text or the key itself if not found

**Supported Placeholder Formats:**
- `{placeholder}` - Braces format
- `:placeholder` - Colon format

**Usage Example:**
```php
// Simple translation
echo __t('welcome_message');
// Output: "Welcome to the system"

// With placeholders
echo __t('hello_user', ['name' => 'John']);
// If dictionary has: "hello_user": "Hello, {name}!"
// Output: "Hello, John!"

// Multiple placeholders
echo __t('user_stats', [
    'posts' => 5,
    'comments' => 12
]);
// If dictionary has: "user_stats": "You have {posts} posts and {comments} comments"
// Output: "You have 5 posts and 12 comments"

// Key not found returns the key itself
echo __t('nonexistent_key');
// Output: "nonexistent_key"
```

**Example with Different Placeholder Formats:**
```php
// Dictionary: "greeting": "Hello, {name}! You have :count messages"

echo __t('greeting', [
    'name' => 'Maria',
    'count' => 3
]);
// Output: "Hello, Maria! You have 3 messages"
```

---

### set_lang()

Sets the language to be used and reloads the corresponding dictionary.

**Signature:**
```php
function set_lang($lang)
```

**Parameters:**
- `$lang` (string) - **Required** - Language code (e.g., 'en', 'pt-br', 'es')

**Return:**
- (void) - No return, updates global variables

**Side Effects:**
- Updates `$GLOBALS['lang']` with new language
- Reloads `$GLOBALS['dicionario']` with new dictionary

**Usage Example:**
```php
// Switch to English
set_lang('en');
echo __t('welcome_message');
// Output: "Welcome to the system"

// Switch to Portuguese
set_lang('pt-br');
echo __t('welcome_message');
// Output: "Bem-vindo ao sistema"

// Switch to Spanish
set_lang('es');
echo __t('welcome_message');
// Output: "Bienvenido al sistema"
```

**Usage with User Preferences:**
```php
// Detect browser language
$browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$supported_langs = ['pt', 'en', 'es'];

if(in_array($browser_lang, $supported_langs)) {
    set_lang($browser_lang);
} else {
    set_lang('en'); // Fallback to English
}
```

---

## Common Use Cases

### 1. Multilingual Login System
```php
// Allow user to choose language
if(isset($_POST['language'])) {
    set_lang($_POST['language']);
    $_SESSION['user_language'] = $_POST['language'];
}

// Form HTML
?>
<form method="post">
    <h2><?php echo __t('login_title'); ?></h2>
    
    <label><?php echo __t('username'); ?>:</label>
    <input type="text" name="username" placeholder="<?php echo __t('username_placeholder'); ?>">
    
    <label><?php echo __t('password'); ?>:</label>
    <input type="password" name="password">
    
    <button type="submit"><?php echo __t('login_button'); ?></button>
    
    <select name="language" onchange="this.form.submit()">
        <option value="pt-br">Português</option>
        <option value="en">English</option>
        <option value="es">Español</option>
    </select>
</form>
<?php
```

### 2. Dynamic Validation Messages
```php
// Form validation with translated messages
function validate_form($data) {
    $errors = [];
    
    if(empty($data['name'])) {
        $errors[] = __t('error_name_required');
    }
    
    if(empty($data['email'])) {
        $errors[] = __t('error_email_required');
    } elseif(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = __t('error_email_invalid');
    }
    
    if(strlen($data['password']) < 8) {
        $errors[] = __t('error_password_too_short', ['min' => 8]);
    }
    
    return $errors;
}

// Dictionary en.json:
// "error_password_too_short": "Password must be at least {min} characters"
```

### 3. System Notifications
```php
// Notification system
function notify_user($type, $data) {
    switch($type) {
        case 'new_comment':
            $message = __t('notification_new_comment', [
                'user' => $data['author'],
                'post' => $data['post_title']
            ]);
            break;
            
        case 'post_approved':
            $message = __t('notification_post_approved', [
                'title' => $data['title']
            ]);
            break;
            
        case 'new_message':
            $message = __t('notification_new_message', [
                'from' => $data['sender'],
                'count' => $data['quantity']
            ]);
            break;
    }
    
    return $message;
}

// Dictionary:
// "notification_new_comment": "{user} commented on '{post}'"
// "notification_post_approved": "Your post '{title}' was approved!"
// "notification_new_message": "You have {count} new messages from {from}"
```

### 4. Administrative Interface
```php
// Multilingual admin panel
class AdminPanel {
    
    public function renderMenu() {
        $menu = [
            'dashboard' => __t('menu_dashboard'),
            'users' => __t('menu_users'),
            'posts' => __t('menu_posts'),
            'settings' => __t('menu_settings'),
            'logout' => __t('menu_logout')
        ];
        
        foreach($menu as $key => $label) {
            echo '<a href="admin.php?page=' . $key . '">' . $label . '</a>';
        }
    }
    
    public function renderStats($stats) {
        echo '<div class="stats">';
        echo '<div>' . __t('total_users', ['count' => $stats['users']]) . '</div>';
        echo '<div>' . __t('total_posts', ['count' => $stats['posts']]) . '</div>';
        echo '<div>' . __t('total_comments', ['count' => $stats['comments']]) . '</div>';
        echo '</div>';
    }
}
```

### 5. Multilingual Emails
```php
// Send email in user's language
function send_welcome_email($user) {
    // Set user language
    set_lang($user['preferred_lang']);
    
    $subject = __t('email_welcome_subject');
    $body = __t('email_welcome_body', [
        'name' => $user['name'],
        'site' => 'Conn2Flow'
    ]);
    
    send_email($user['email'], $subject, $body);
    
    // Restore default system language
    set_lang('pt-br');
}
```

## Dictionary Structure

### Recommended Organization

```json
{
    "_comment": "General",
    "app_name": "Conn2Flow",
    "welcome": "Welcome",
    
    "_comment": "Authentication",
    "login_title": "Login to System",
    "logout": "Logout",
    "username": "Username",
    "password": "Password",
    
    "_comment": "Validation",
    "error_required": "Required field",
    "error_invalid_email": "Invalid email",
    "error_password_min": "Password must be at least {min} characters",
    
    "_comment": "Success",
    "success_save": "Data saved successfully",
    "success_delete": "Item deleted",
    
    "_comment": "Interface",
    "button_save": "Save",
    "button_cancel": "Cancel",
    "button_delete": "Delete"
}
```

### Naming Conventions

| Prefix | Usage | Example |
|--------|-------|---------|
| `error_` | Error messages | `error_not_found` |
| `success_` | Success messages | `success_created` |
| `button_` | Button texts | `button_submit` |
| `label_` | Form labels | `label_email` |
| `title_` | Page/section titles | `title_dashboard` |
| `menu_` | Menu items | `menu_settings` |
| `notification_` | Notifications | `notification_new_message` |
| `email_` | Email templates | `email_reset_password` |

## Comparison with Gettext

### Why Not Use Gettext?

| Aspect | Custom System (__t) | Gettext |
|--------|---------------------|---------|
| **Format** | JSON (easy edit) | .po/.mo (compiled) |
| **Setup** | Simple, no config | Requires PHP extension and compilation |
| **Performance** | JSON read | Faster (compiled) |
| **Flexibility** | Total control | More rigid |
| **Pluralization** | Manual | Automatic |
| **Fallback** | Returns key | Can return empty |

### Advantages of Custom System

1. **Simplicity**: Does not require PHP extension or external tools
2. **Portability**: Works in any PHP environment
3. **Easy Editing**: JSON files editable in any editor
4. **Versioning**: Easy to track changes in Git
5. **Debugging**: Returns key if translation not found

## Patterns and Best Practices

### 1. File Organization
```
gestor/bibliotecas/
├── lang.php
├── langs/
│   ├── pt-br.json
│   ├── en.json
│   ├── es.json
│   └── fr.json
```

### 2. Dictionary Caching
```php
// For better performance, consider caching
function load_dictionary_with_cache($lang) {
    $cache_key = 'dict_' . $lang;
    
    if(isset($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }
    
    $dict = carregar_dicionario($lang);
    $_SESSION[$cache_key] = $dict;
    
    return $dict;
}
```

### 3. Automatic Language Detection
```php
// Detect and set language automatically
function detect_user_language() {
    // 1. Check saved preference
    if(isset($_SESSION['user_lang'])) {
        return $_SESSION['user_lang'];
    }
    
    // 2. Check cookie
    if(isset($_COOKIE['preferred_lang'])) {
        return $_COOKIE['preferred_lang'];
    }
    
    // 3. Detect from browser
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $supported = ['pt', 'en', 'es', 'fr'];
    
    if(in_array($browser_lang, $supported)) {
        return $browser_lang;
    }
    
    // 4. Default fallback
    return 'pt-br';
}

// Apply at application start
set_lang(detect_user_language());
```

### 4. Translation Validation
```php
// Script to check missing keys
function validate_translations() {
    $languages = ['pt-br', 'en', 'es'];
    $base = carregar_dicionario('pt-br'); // Reference language
    
    foreach($languages as $lang) {
        if($lang === 'pt-br') continue;
        
        $dict = carregar_dicionario($lang);
        $missing = array_diff_key($base, $dict);
        
        if(!empty($missing)) {
            echo "Language $lang - Missing keys: " . count($missing) . "\n";
            print_r(array_keys($missing));
        }
    }
}
```

## Limitations and Considerations

### 1. Pluralization
The system does not have automatic pluralization support. Solutions:

```php
// Manual solution with multiple keys
"item_singular": "{count} item"
"item_plural": "{count} items"

// Usage:
$key = ($count == 1) ? 'item_singular' : 'item_plural';
echo __t($key, ['count' => $count]);
```

### 2. Context
No native support for context (same key, different translations):

```php
// Solution: use different keys
"button_save_new": "Create"
"button_save_edit": "Update"
```

### 3. Performance
For large systems, consider:
- Dictionary caching in memory/file
- Lazy loading of dictionary parts
- Compiling dictionaries to PHP arrays

## See Also

- [LIBRARY-VARIABLES.md](./LIBRARY-VARIABLES.md) - System variable management
- [LIBRARY-USER.md](./LIBRARY-USER.md) - User preferences
- [Gettext Documentation](https://www.gnu.org/software/gettext/) - Traditional alternative

---

**Last Update**: October 2025  
**Documented by**: Conn2Flow Team
