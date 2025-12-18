# Library: formato.php

> 📝 Data formatting functions (dates, numbers, text)

## Overview

The `formato.php` library provides a comprehensive set of functions for data formatting and conversion, including:
- Date and time formatting
- Conversion between number formats (float, int)
- Text formatting with specific patterns
- Bidirectional conversion between Brazilian and international formats

**Location**: `gestor/bibliotecas/formato.php`  
**Version**: 1.1.0  
**Total Functions**: 12

## Dependencies

- No direct dependencies on other libraries
- Uses global variable `$_GESTOR`

## Global Variables

```php
$_GESTOR['biblioteca-formato'] = Array(
    'versao' => '1.1.0',
);
```

## Main Functions

### formato_dado()

Formats data according to the specified type using parameter array.

**Signature:**
```php
function formato_dado($params = false)
```

**Parameters:**
- `valor` (string) - **Required** - Value of data to be formatted
- `tipo` (string) - **Required** - Type of formatting to be applied

**Available Formatting Types:**
- `'float-para-texto'` - Converts float to Brazilian format (00.000,00)
- `'texto-para-float'` - Converts Brazilian text to float
- `'int-para-texto'` - Converts integer to Brazilian format (00.000.000)
- `'texto-para-int'` - Converts Brazilian text to integer
- `'data'` - Converts datetime to date (DD/MM/YYYY)
- `'dataHora'` - Converts datetime to date and time (DD/MM/YYYY HH:MM)
- `'datetime'` - Converts Brazilian date to SQL datetime
- `'date'` - Converts Brazilian date to SQL date

**Return:**
- (string) - Formatted value or empty string if invalid parameters

**Usage Example:**
```php
// Format float to Brazilian text
$formatted_value = formato_dado(Array(
    'valor' => 1234.56,
    'tipo' => 'float-para-texto'
));
// Returns: "1.234,56"

// Format datetime to date
$formatted_date = formato_dado(Array(
    'valor' => '2025-10-27 14:30:00',
    'tipo' => 'data'
));
// Returns: "27/10/2025"

// Convert Brazilian date to SQL datetime
$sql_datetime = formato_dado(Array(
    'valor' => '27/10/2025 14:30',
    'tipo' => 'datetime'
));
// Returns: "2025-10-27 14:30:00"
```

---

### formato_dado_para()

Simplified version of `formato_dado()` with direct parameters.

**Signature:**
```php
function formato_dado_para($tipo, $valor)
```

**Parameters:**
- `tipo` (string) - **Required** - Formatting type
- `valor` (string) - **Required** - Value to be formatted

**Return:**
- (string) - Formatted value or empty string

**Usage Example:**
```php
$value = formato_dado_para('float-para-texto', 1500.75);
// Returns: "1.500,75"

$date = formato_dado_para('data', '2025-10-27 14:30:00');
// Returns: "27/10/2025"
```

---

## Helper Functions

### formato_data_hora_array()

Converts date/time string to associative array.

**Signature:**
```php
function formato_data_hora_array($data_hora_padrao_datetime_ou_padrao_date)
```

**Parameters:**
- `$data_hora_padrao_datetime_ou_padrao_date` (string) - Date in SQL format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)

**Return:**
- (array) - Associative array with date/time components

**Return Format:**
```php
// For full datetime:
Array(
    'dia' => '27',
    'mes' => '10',
    'ano' => '2025',
    'hora' => '14',
    'min' => '30',
    'seg' => '00'
)

// For date only:
Array(
    'dia' => '27',
    'mes' => '10',
    'ano' => '2025'
)
```

**Usage Example:**
```php
$date_array = formato_data_hora_array('2025-10-27 14:30:00');
echo $date_array['dia']; // "27"
echo $date_array['mes']; // "10"
echo $date_array['ano']; // "2025"
echo $date_array['hora']; // "14"
```

---

### formato_data_hora_padrao_datetime()

Converts Brazilian date to SQL datetime format.

**Signature:**
```php
function formato_data_hora_padrao_datetime($dataHora, $semHora = false)
```

**Parameters:**
- `$dataHora` (string) - Date in Brazilian format (DD/MM/YYYY HH:MM)
- `$semHora` (bool) - Optional - If true, returns only date without time

**Return:**
- (string) - Date in SQL format (YYYY-MM-DD HH:MM:SS or YYYY-MM-DD)

**Usage Example:**
```php
// With time
$datetime = formato_data_hora_padrao_datetime('27/10/2025 14:30');
// Returns: "2025-10-27 14:30:00"

// Without time
$date = formato_data_hora_padrao_datetime('27/10/2025 14:30', true);
// Returns: "2025-10-27"
```

---

### formato_data_hora_from_datetime_to_text()

Converts SQL datetime to custom or standard text format.

**Signature:**
```php
function formato_data_hora_from_datetime_to_text($data_hora, $format = false)
```

**Parameters:**
- `$data_hora` (string) - Date in SQL format (YYYY-MM-DD HH:MM:SS)
- `$format` (string) - Optional - Custom format using placeholders

**Available Placeholders:**
- `D` - Day
- `ME` - Month
- `A` - Year
- `H` - Hour
- `MI` - Minute
- `S` - Second

**Return:**
- (string) - Formatted date or empty string if invalid date

**Usage Example:**
```php
// Standard format (D/ME/A HhMI)
$date = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45');
// Returns: "27/10/2025 14h30"

// Custom format
$date = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45', 'D-ME-A at H:MI');
// Returns: "27-10-2025 at 14:30"

// Another example
$date = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45', 'A/ME/D H:MI:S');
// Returns: "2025/10/27 14:30:45"
```

---

### formato_data_from_datetime_to_text()

Converts SQL datetime to date in Brazilian format (date only, no time).

**Signature:**
```php
function formato_data_from_datetime_to_text($data_hora)
```

**Parameters:**
- `$data_hora` (string) - Date in SQL format (YYYY-MM-DD HH:MM:SS)

**Return:**
- (string) - Date in DD/MM/YYYY format

**Usage Example:**
```php
$date = formato_data_from_datetime_to_text('2025-10-27 14:30:00');
// Returns: "27/10/2025"
```

---

### formato_float_para_texto()

Formats float number to Brazilian standard.

**Signature:**
```php
function formato_float_para_texto($float, $sem_descimal = false)
```

**Parameters:**
- `$float` (float) - Number to be formatted
- `$sem_descimal` (bool) - Optional - Currently not used

**Return:**
- (string) - Number formatted in Brazilian standard (00.000,00)

**Usage Example:**
```php
$value = formato_float_para_texto(1234.56);
// Returns: "1.234,56"

$value = formato_float_para_texto(1000000.99);
// Returns: "1.000.000,99"
```

---

### formato_texto_para_float()

Converts text in Brazilian format to float.

**Signature:**
```php
function formato_texto_para_float($texto)
```

**Parameters:**
- `$texto` (string) - Number in Brazilian format (00.000,00)

**Return:**
- (string) - Number in float format (0000.00)

**Usage Example:**
```php
$float = formato_texto_para_float('1.234,56');
// Returns: "1234.56"

$float = formato_texto_para_float('1.000.000,99');
// Returns: "1000000.99"
```

---

### formato_int_para_texto()

Formats integer number to Brazilian standard with thousand separators.

**Signature:**
```php
function formato_int_para_texto($int)
```

**Parameters:**
- `$int` (int) - Integer number to be formatted

**Return:**
- (string) - Number formatted in Brazilian standard (00.000.000)

**Usage Example:**
```php
$value = formato_int_para_texto(1234567);
// Returns: "1.234.567"

$value = formato_int_para_texto(1000);
// Returns: "1.000"
```

---

### formato_texto_para_int()

Removes thousand separator dots from Brazilian format.

**Signature:**
```php
function formato_texto_para_int($texto)
```

**Parameters:**
- `$texto` (string) - Number in Brazilian format (00.000.000)

**Return:**
- (string) - Number without separators

**Usage Example:**
```php
$int = formato_texto_para_int('1.234.567');
// Returns: "1234567"
```

---

### formato_zero_a_esquerda()

Adds leading zeros to complete the specified number of digits.

**Signature:**
```php
function formato_zero_a_esquerda($num, $dig)
```

**Parameters:**
- `$num` (int|string) - Number to be formatted
- `$dig` (int) - Total number of digits desired

**Return:**
- (string) - Number with leading zeros

**Usage Example:**
```php
$num = formato_zero_a_esquerda(42, 5);
// Returns: "00042"

$num = formato_zero_a_esquerda(7, 3);
// Returns: "007"

$num = formato_zero_a_esquerda(12345, 3);
// Returns: "12345" (does not change if already has more digits)
```

---

### formato_colocar_char_meio_numero()

Inserts a character in the middle of a number.

**Signature:**
```php
function formato_colocar_char_meio_numero($num, $char = '-')
```

**Parameters:**
- `$num` (int|string) - Number to be formatted
- `$char` (string) - Optional - Character to insert (default: '-')

**Return:**
- (string) - Number with character inserted in the middle

**Usage Example:**
```php
$num = formato_colocar_char_meio_numero(123456);
// Returns: "123-456"

$num = formato_colocar_char_meio_numero(123456, '/');
// Returns: "123/456"

$num = formato_colocar_char_meio_numero(12345678, ' ');
// Returns: "1234 5678"
```

---

## Common Use Cases

### 1. Monetary Value Formatting
```php
// Display price in Brazilian format
$price_db = 1599.90; // From database
$price_display = formato_float_para_texto($price_db);
echo "R$ " . $price_display; // "R$ 1.599,90"

// Save price typed by user
$price_typed = "1.599,90";
$price_save = formato_texto_para_float($price_typed);
// Saves to database: 1599.90
```

### 2. Date Formatting for Display
```php
// Date coming from database
$date_db = '2025-10-27 14:30:00';

// Display date only
$date = formato_dado_para('data', $date_db);
echo $date; // "27/10/2025"

// Display date and time
$date_time = formato_dado_para('dataHora', $date_db);
echo $date_time; // "27/10/2025 14h30"

// Custom format
$date_custom = formato_data_hora_from_datetime_to_text($date_db, 'D/ME/A at H:MI');
echo $date_custom; // "27/10/2025 at 14:30"
```

### 3. Form Processing
```php
// Save form date to database
$date_form = "27/10/2025 14:30"; // Comes from form
$date_save = formato_dado_para('datetime', $date_form);
// INSERT: "2025-10-27 14:30:00"

// Display database date in form
$date_db = "2025-10-27 14:30:00";
$date_form = formato_dado_para('dataHora', $date_db);
// Form displays: "27/10/2025 14h30"
```

### 4. Identification Number Formatting
```php
// Format product code
$code = 42;
$code_formatted = formato_zero_a_esquerda($code, 6);
echo "Product: " . $code_formatted; // "Product: 000042"

// Format protocol number
$protocol = 123456;
$protocol_formatted = formato_colocar_char_meio_numero($protocol);
echo "Protocol: " . $protocol_formatted; // "Protocol: 123-456"
```

### 5. Date Component Manipulation
```php
// Extract components from a date
$date_db = '2025-10-27 14:30:00';
$components = formato_data_hora_array($date_db);

echo "Day: " . $components['dia'];     // "27"
echo "Month: " . $components['mes'];   // "10"
echo "Year: " . $components['ano'];    // "2025"
echo "Hour: " . $components['hora'];   // "14"
echo "Min: " . $components['min'];     // "30"

// Useful for date dropdowns
```

## Patterns and Conventions

### Brazilian vs International Format

| Type | Brazilian Format | International/SQL Format |
|------|-------------------|--------------------------|
| Date | DD/MM/YYYY | YYYY-MM-DD |
| Date and Time | DD/MM/YYYY HH:MM | YYYY-MM-DD HH:MM:SS |
| Float | 1.234,56 | 1234.56 |
| Integer | 1.234.567 | 1234567 |

### Conversion Flow

```
┌─────────────────────────────────────────────────────────┐
│ User Input (Brazilian Format)                          │
│ • Date: 27/10/2025                                      │
│ • Value: 1.234,56                                       │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Conversion to SQL/International Format                 │
│ • formato_data_hora_padrao_datetime()                  │
│ • formato_texto_para_float()                           │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Database Storage                                       │
│ • Date: 2025-10-27                                      │
│ • Value: 1234.56                                        │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Conversion for Display (Brazilian Format)              │
│ • formato_data_from_datetime_to_text()                 │
│ • formato_float_para_texto()                           │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ User Display (Brazilian Format)                        │
│ • Date: 27/10/2025                                      │
│ • Value: 1.234,56                                       │
└─────────────────────────────────────────────────────────┘
```

## Important Notes

1. **Input Validation**: Functions do not perform extensive input validation. Ensure to validate data before formatting.

2. **Time Zone**: Date/time functions do not handle time zones. Use native PHP functions if timezone conversions are needed.

3. **Float Precision**: Float conversion uses 2 decimal places by default. For different precision, use `number_format()` directly.

4. **Performance**: For bulk formatting, consider caching or pre-processing.

5. **Compatibility**: All functions are compatible with PHP 7.0+.

## Version History

- **v1.1.0** - Current version with all functions documented
- **v1.0.0** - Initial library version

## See Also

- [LIBRARY-DATABASE.md](./LIBRARY-DATABASE.md) - For database operations
- [LIBRARY-FORM.md](./LIBRARY-FORM.md) - For form validation
- [LIBRARY-INTERFACE.md](./LIBRARY-INTERFACE.md) - For interface components

---

**Last Update**: October 2025  
**Documented by**: Conn2Flow Team
