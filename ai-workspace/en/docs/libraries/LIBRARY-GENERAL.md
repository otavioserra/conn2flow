# Library: geral.php

> 🛠️ General utility functions and miscellaneous tools

## Overview

The `geral.php` library provides general-purpose utility functions that do not fit into specific categories. Currently, it contains functions for basic text manipulation.

**Location**: `gestor/bibliotecas/geral.php`  
**Version**: 1.0.0  
**Total Functions**: 1

## Dependencies

- No direct dependencies on other libraries
- Uses global variable `$_GESTOR`
- Depends on function `existe()` (presumably from another library)

## Global Variables

```php
$_GESTOR['biblioteca-geral'] = Array(
    'versao' => '1.0.0',
);
```

## Main Functions

### geral_nl2br()

Converts newlines to HTML `<br>` tags.

**Signature:**
```php
function geral_nl2br($string = '')
```

**Description:**
This function is a wrapper for PHP's native `nl2br()` function, with additional check for string existence using the `existe()` function.

**Parameters:**
- `$string` (string) - Optional - String to be processed (default: empty string)

**Return:**
- (string) - String with newlines converted to HTML `<br>` tags, or the original string if empty

**Usage Example:**
```php
// Text with newlines
$text = "First line
Second line
Third line";

$html_text = geral_nl2br($text);
echo $html_text;

// Output:
// First line<br />
// Second line<br />
// Third line
```

**Display Context Example:**
```php
// Store user text
$description = $_POST['description'];
// "This is a product
// with multiple lines
// of description"

// Display in HTML preserving newlines
echo '<div class="description">' . geral_nl2br($description) . '</div>';

// Generated HTML:
// <div class="description">
// This is a product<br />
// with multiple lines<br />
// of description
// </div>
```

**Example with Empty String:**
```php
$text = '';
$result = geral_nl2br($text);
// Returns: '' (empty string)

$text = null;
$result = geral_nl2br($text);
// Returns: null or '' depending on existe() implementation
```

---

## Common Use Cases

### 1. Displaying Comments/Descriptions
```php
// User comment from database
$comment = banco_select_one("SELECT text FROM comments WHERE id = 1");

// Display preserving formatting
echo '<div class="comment">';
echo geral_nl2br($comment['text']);
echo '</div>';
```

### 2. Address Formatting
```php
// Multi-line address
$address = "123 Flower St
Downtown District
New York - NY
ZIP: 10001";

echo '<address>' . geral_nl2br($address) . '</address>';
```

### 3. System Message Display
```php
// Formatted message
$message = "Welcome to the system!

You have 3 new notifications.
Click here to view.";

echo '<div class="alert">' . geral_nl2br($message) . '</div>';
```

### 4. Content Preview with Newlines
```php
// Article preview
$preview = substr($article['content'], 0, 200);

if(strpos($preview, "\n") !== false) {
    echo geral_nl2br($preview) . '...';
} else {
    echo $preview . '...';
}
```

---

## Comparison with Native nl2br()

### Differences

| Aspect | `geral_nl2br()` | Native `nl2br()` |
|--------|-----------------|------------------|
| Existence check | Yes (uses `existe()`) | No |
| Return for empty string | Original string | Empty string with potential warning |
| Global variable usage | No (declaration only) | No |

### When to Use geral_nl2br()

Use `geral_nl2br()` when:
- You want to ensure empty/null strings are handled gracefully
- You are working within the Conn2Flow ecosystem
- You want consistency with other system functions

### When to Use Native nl2br()

Use native `nl2br()` when:
- You are certain the string is valid
- You are in a context outside the Conn2Flow system
- You need fine control over the `is_xhtml` parameter

---

## Implementation Details

### existe() Function

The `geral_nl2br()` function depends on `existe()`, which presumably checks if a variable exists and is not empty. Typical implementation:

```php
// Presumed implementation (not documented in this file)
function existe($var) {
    return isset($var) && $var != '' && $var !== null;
}
```

### nl2br() Processing

PHP's native `nl2br()` function:
- Inserts `<br />` before `\n`, `\r\n`, and `\r`
- Does not remove original newlines
- Is safe for use in HTML

---

## Important Notes

1. **HTML Sanitization**: `geral_nl2br()` does not escape HTML. If the string contains user content, consider using `htmlspecialchars()` first:
   ```php
   $safe_text = htmlspecialchars($user_text);
   $formatted_text = geral_nl2br($safe_text);
   ```

2. **Performance**: For large volumes of text, consider storing the version with `<br>` in the database if the text does not change frequently.

3. **Compatibility**: The function depends on `existe()` which must be available in the system.

4. **XHTML vs HTML5**: The function uses `nl2br()` which by default generates XHTML tags (`<br />`). In HTML5, `<br>` is also valid.

---

## Advanced Examples

### Combination with Other Formatting Functions

```php
// Process user text safely
$raw_text = $_POST['description'];

// 1. Clean potentially dangerous HTML
$clean_text = htmlspecialchars($raw_text, ENT_QUOTES, 'UTF-8');

// 2. Convert newlines to HTML
$formatted_text = geral_nl2br($clean_text);

// 3. Limit size (if necessary)
$final_text = substr($formatted_text, 0, 500);

echo $final_text;
```

### Usage in Templates

```php
// In a template system
$template = '
<div class="post">
    <h2>{title}</h2>
    <div class="content">
        {content}
    </div>
</div>
';

// Prepare data
$data = Array(
    'title' => $post['title'],
    'content' => geral_nl2br($post['description'])
);

// Render
foreach($data as $key => $value) {
    $template = str_replace('{'.$key.'}', $value, $template);
}

echo $template;
```

### Conditional Processing

```php
// Apply nl2br only if there are newlines
function smart_text_format($text) {
    if(existe($text)) {
        // Check for newlines
        if(preg_match('/[\r\n]/', $text)) {
            return geral_nl2br($text);
        }
        return $text;
    }
    return '';
}

$text1 = "Text on one line";
$text2 = "Text with\nmultiple\nlines";

echo smart_text_format($text1); // Without <br>
echo smart_text_format($text2); // With <br>
```

---

## Future Expansions

The `geral.php` library is positioned to receive additional utility functions that do not fit into other categories, such as:

- String manipulation functions
- Array utilities
- Generic validation helpers
- Type conversion functions
- Debug utilities

---

## See Also

- [LIBRARY-FORMAT.md](./LIBRARY-FORMAT.md) - Specific data formatting
- [LIBRARY-HTML.md](./LIBRARY-HTML.md) - HTML generation
- [LIBRARY-INTERFACE.md](./LIBRARY-INTERFACE.md) - Interface components

---

**Last Update**: October 2025  
**Documented by**: Conn2Flow Team
