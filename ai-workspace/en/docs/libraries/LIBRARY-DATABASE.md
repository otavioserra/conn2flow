# Library: banco.php

> 🗄️ Complete MySQL/MySQLi database operations

## Overview

The `banco.php` library is the **fundamental base** of all data operations in the Conn2Flow system. It provides a complete abstraction layer for interaction with MySQL/MySQLi databases, including:
- Connection management
- CRUD Operations (Create, Read, Update, Delete)
- Query construction helpers
- Escape and security functions
- Field and table utilities

**Location**: `gestor/bibliotecas/banco.php`  
**Version**: 1.2.0  
**Total Functions**: 45  
**Database Type**: MySQLi

## Dependencies

- **PHP Extension**: MySQLi
- **Global Variables**: `$_BANCO`, `$_GESTOR`

## Global Variables

```php
// Library configuration
$_GESTOR['biblioteca-banco'] = Array(
    'versao' => '1.2.0',
);

// Connection configuration (defined at initialization)
$_BANCO = Array(
    'tipo' => 'mysqli',
    'host' => 'localhost',
    'usuario' => 'db_user',
    'senha' => 'db_password',
    'nome' => 'db_name',
    'conexao' => null,  // MySQLi connection object
    'RECONECT' => 0,    // Reconnection counter
);

// Temporary arrays for operations
$_BANCO['update-campos'] = Array();        // Fields for UPDATE
$_BANCO['insert-name-campos'] = Array();   // Fields for INSERT
```

---

## Connection Functions

### banco_conectar()

Establishes connection to the MySQL database using MySQLi.

**Signature:**
```php
function banco_conectar()
```

**Parameters:**
- None (uses global variable `$_BANCO`)

**Return:**
- (void) - Terminates execution with error if it fails

**Behavior:**
- Uses credentials from `$_BANCO`
- Sets UTF-8 charset
- Enables MySQLi error reporting
- Kills execution in case of failure

**Usage Example:**
```php
// Usually called automatically by functions
// But can be called manually
banco_conectar();
```

**Notes:**
- The connection is automatically established when needed
- Errors are displayed with debug trace

---

### banco_ping()

Checks if the database connection is active.

**Signature:**
```php
function banco_ping()
```

**Parameters:**
- None

**Return:**
- (void) - Increments reconnection counter if it fails

**Usage Example:**
```php
// Check connection before critical operation
banco_ping();
```

---

### banco_fechar_conexao()

Closes the database connection.

**Signature:**
```php
function banco_fechar_conexao()
```

**Parameters:**
- None

**Return:**
- (void)

**Usage Example:**
```php
// Close connection at the end of script
banco_fechar_conexao();
```

**Notes:**
- Removes the `$_BANCO['conexao']` variable
- Useful for long-running scripts

---

## Basic Query Functions

### banco_query()

Executes a SQL query on the database.

**Signature:**
```php
function banco_query($query)
```

**Parameters:**
- `$query` (string) - **Required** - SQL query to be executed

**Return:**
- (mysqli_result|bool) - Query result or false in case of error

**Usage Example:**
```php
$result = banco_query("SELECT * FROM usuarios WHERE status='A'");

if ($result) {
    // Process result
}
```

**Notes:**
- Connects automatically if necessary
- Logs errors in error_log
- Returns false in case of exception

---

### banco_sql()

Executes query and returns all results as a two-dimensional array.

**Signature:**
```php
function banco_sql($sql)
```

**Parameters:**
- `$sql` (string) - **Required** - SQL Query

**Return:**
- (array|null) - Array with all results or null if empty

**Usage Example:**
```php
$users = banco_sql("SELECT id, nome, email FROM usuarios WHERE status='A'");

if ($users) {
    foreach ($users as $user) {
        echo $user[0]; // ID
        echo $user[1]; // Name
        echo $user[2]; // Email
    }
}
```

---

### banco_sql_names()

Executes query and returns results as an associative array with field names.

**Signature:**
```php
function banco_sql_names($sql, $campos)
```

**Parameters:**
- `$sql` (string) - **Required** - SQL Query
- `$campos` (string) - **Required** - Field names separated by comma or '*'

**Return:**
- (array|null) - Associative array with results or null

**Usage Example:**
```php
$sql = "SELECT id, nome, email FROM usuarios WHERE status='A'";
$users = banco_sql_names($sql, 'id,nome,email');

if ($users) {
    foreach ($users as $user) {
        echo $user['id'];
        echo $user['nome'];
        echo $user['email'];
    }
}

// With all fields
$all = banco_sql_names("SELECT * FROM usuarios", '*');
```

**Notes:**
- Applies `banco_smartstripslashes()` on non-numeric fields
- More readable than `banco_sql()`

---

## SELECT Functions

### banco_select()

Main function for data selection with structured parameters.

**Signature:**
```php
function banco_select($params = false)
```

**Parameters (Associative Array):**
- `campos` (array|string) - **Required** - Array of fields or '*'
- `tabela` (string) - **Required** - Table name
- `extra` (string) - **Optional** - WHERE, ORDER BY, LIMIT clauses, etc.
- `unico` (bool) - **Optional** - If true, returns only first result (one-dimensional array)

**Return:**
- (array|null) - Array of results or null

**Usage Example:**
```php
// Simple selection
$users = banco_select(Array(
    'campos' => Array('id', 'nome', 'email'),
    'tabela' => 'usuarios',
    'extra' => "WHERE status='A' ORDER BY nome LIMIT 10"
));

// Return all fields
$products = banco_select(Array(
    'campos' => '*',
    'tabela' => 'produtos',
    'extra' => "WHERE categoria='electronics'"
));

// Return unique result
$user = banco_select(Array(
    'campos' => Array('id', 'nome', 'email'),
    'tabela' => 'usuarios',
    'extra' => "WHERE id='123'",
    'unico' => true
));

// Access data
if ($user) {
    echo $user['nome'];
    echo $user['email'];
}

// Multiple results
if ($users) {
    foreach ($users as $u) {
        echo $u['nome'] . '<br>';
    }
}
```

**Notes:**
- Returns associative array with field names
- With `unico => true`, directly returns the first record
- Ideal for most SELECT queries

---

### banco_select_name()

Simplified version of select with direct parameters.

**Signature:**
```php
function banco_select_name($campos, $tabela, $extra)
```

**Parameters:**
- `$campos` (string) - **Required** - Fields separated by comma or '*'
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - Extra clauses

**Return:**
- (array|null) - Associative array of results

**Usage Example:**
```php
$users = banco_select_name(
    'id,nome,email',
    'usuarios',
    "WHERE status='A' ORDER BY nome"
);
```

**Notes:**
- Applies `banco_smartstripslashes()` automatically
- Simpler interface than `banco_select()`

---

### banco_select_editar()

Selects data for editing, returning only the first record.

**Signature:**
```php
function banco_select_editar($campos, $tabela, $extra)
```

**Parameters:**
- `$campos` (string) - **Required** - Fields separated by comma
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - Extra clauses

**Return:**
- (array|null) - Associative array with first result

**Usage Example:**
```php
$user = banco_select_editar(
    'id,nome,email,telefone',
    'usuarios',
    "WHERE id='123'"
);

if ($user) {
    echo $user['nome'];
}

// Check if result found
global $_GESTOR;
if ($_GESTOR['banco-resultado'] === true) {
    // Record found
} else {
    // No record
}
```

**Notes:**
- Sets `$_GESTOR['banco-resultado']` to true or false
- Useful for edit forms

---

### banco_select_campos_antes_iniciar()

Stores data of a record for later comparison.

**Signature:**
```php
function banco_select_campos_antes_iniciar($campos, $tabela, $extra)
```

**Parameters:**
- `$campos` (string) - **Required** - Fields to store
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - Extra clauses

**Return:**
- (bool) - true if found, false otherwise

**Usage Example:**
```php
// Store previous values
$exists = banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='123'"
);

if ($exists) {
    // Use banco_select_campos_antes() to access values
}
```

**Notes:**
- Stores in `$_GESTOR['banco-antes']`
- Use with `banco_select_campos_antes()` to retrieve

---

### banco_select_campos_antes()

Retrieves value stored by `banco_select_campos_antes_iniciar()`.

**Signature:**
```php
function banco_select_campos_antes($campo)
```

**Parameters:**
- `$campo` (string) - **Required** - Field name

**Return:**
- (mixed|null) - Field value or null

**Usage Example:**
```php
// Store previous state
banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='123'"
);

// Retrieve values
$previous_name = banco_select_campos_antes('nome');
$previous_email = banco_select_campos_antes('email');

// Compare with new values
if ($new_name != $previous_name) {
    // Name was changed
}
```

**Use Case:**
- Detect changes in fields
- Create audit logs
- Validations based on previous state

---

## UPDATE Functions

### banco_update()

Executes UPDATE SQL directly.

**Signature:**
```php
function banco_update($campos, $tabela, $extra)
```

**Parameters:**
- `$campos` (string) - **Required** - Assignments string (field='value')
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - WHERE clause and others

**Return:**
- (void)

**Usage Example:**
```php
banco_update(
    "nome='John Doe', email='john@email.com', status='A'",
    'usuarios',
    "WHERE id='123'"
);
```

**Notes:**
- For direct use, without builders
- Prefer `banco_update_campo()` + `banco_update_executar()`

---

### banco_update_campo()

Adds field to UPDATE builder.

**Signature:**
```php
function banco_update_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)
```

**Parameters:**
- `$nome` (string) - **Required** - Field name
- `$valor` (mixed) - **Required** - Field value
- `$sem_aspas_simples` (bool) - **Optional** - If true, does not add quotes
- `$escape_field` (bool) - **Optional** - If true, escapes value (default: true)

**Return:**
- (void) - Stores in `$_BANCO['update-campos']`

**Usage Example:**
```php
// Fields with quotes (strings)
banco_update_campo('nome', 'John Doe');
banco_update_campo('email', 'john@email.com');

// Fields without quotes (numbers, NULL)
banco_update_campo('idade', 25, true);
banco_update_campo('ativo', 1, true);

// Execute UPDATE
banco_update_executar('usuarios', "WHERE id='123'");
```

---

### banco_update_executar()

Executes UPDATE with fields accumulated by `banco_update_campo()`.

**Signature:**
```php
function banco_update_executar($tabela, $extra = '')
```

**Parameters:**
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - WHERE clause

**Return:**
- (void)

**Usage Example:**
```php
// Accumulate fields
banco_update_campo('nome', $_POST['nome']);
banco_update_campo('email', $_POST['email']);
banco_update_campo('telefone', $_POST['telefone']);
banco_update_campo('atualizado_em', 'NOW()', true, false);

// Execute
banco_update_executar('usuarios', "WHERE id='" . $_POST['id'] . "'");

// Fields are automatically cleared after execution
```

**Notes:**
- Clears `$_BANCO['update-campos']` after execution
- Ideal for dynamic forms

---

### banco_update_varios()

Updates multiple records with different values in a single query.

**Signature:**
```php
function banco_update_varios($campos, $tabela, $campo_nome, $id_nome)
```

**Parameters:**
- `$campos` (array) - **Required** - Array of arrays [id, value]
- `$tabela` (string) - **Required** - Table name
- `$campo_nome` (string) - **Required** - Field to be updated
- `$id_nome` (string) - **Required** - ID field name

**Return:**
- (void)

**Usage Example:**
```php
// Update order of multiple items
$campos = Array(
    Array('1', '10'),  // ID 1 -> order 10
    Array('2', '20'),  // ID 2 -> order 20
    Array('3', '30'),  // ID 3 -> order 30
);

banco_update_varios(
    $campos,
    'produtos',
    'ordem',     // field to update
    'id'         // identifier field
);

// Generates optimized SQL:
// UPDATE `produtos` SET `ordem` = CASE `id`
// WHEN '1' THEN '10'
// WHEN '2' THEN '20'
// WHEN '3' THEN '30'
// ELSE `ordem`
// END
```

**Notes:**
- Much more efficient than multiple UPDATEs
- Automatically splits into batches if SQL > 1MB
- Uses CASE WHEN for performance

---

## INSERT Functions

### banco_insert()

Inserts record with auto-increment ID (legacy).

**Signature:**
```php
function banco_insert($campos, $tabela)
```

**Parameters:**
- `$campos` (string) - **Required** - Values separated by comma
- `$tabela` (string) - **Required** - Table name

**Return:**
- (void)

**Usage Example:**
```php
// Inserts VALUES('0', ...)
banco_insert("'John','john@email.com','A'", 'usuarios');
```

**Notes:**
- Adds '0' for auto-increment ID
- Prefer `banco_insert_name()` for modern code

---

### banco_insert_name()

Inserts record specifying field names.

**Signature:**
```php
function banco_insert_name($dados, $tabela)
```

**Parameters:**
- `$dados` (array) - **Required** - Array of arrays [name, value, no_quotes]
- `$tabela` (string) - **Required** - Table name

**Return:**
- (void)

**Usage Example:**
```php
$data = Array(
    Array('nome', 'John Doe', false),
    Array('email', 'john@email.com', false),
    Array('idade', 25, true),  // no quotes
    Array('status', 'A', false)
);

banco_insert_name($data, 'usuarios');

// Generates: INSERT INTO usuarios (nome,email,idade,status) 
//            VALUES ('John Doe','john@email.com',25,'A')
```

---

### banco_insert_name_campo()

Adds field to INSERT builder.

**Signature:**
```php
function banco_insert_name_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)
```

**Parameters:**
- `$nome` (string) - **Required** - Field name
- `$valor` (mixed) - **Required** - Field value
- `$sem_aspas_simples` (bool) - **Optional** - No quotes (numbers, NULL)
- `$escape_field` (bool) - **Optional** - Escape value

**Return:**
- (void)

**Usage Example:**
```php
banco_insert_name_campo('nome', 'John Doe');
banco_insert_name_campo('email', 'john@email.com');
banco_insert_name_campo('idade', 25, true);
banco_insert_name_campo('criado_em', 'NOW()', true, false);

// Retrieve accumulated fields
$campos = banco_insert_name_campos();

// Insert
banco_insert_name($campos, 'usuarios');
```

---

### banco_insert_name_campos()

Returns and clears accumulated fields for INSERT.

**Signature:**
```php
function banco_insert_name_campos()
```

**Parameters:**
- None

**Return:**
- (array) - Array of fields or empty array

**Usage Example:**
```php
// Accumulate fields
banco_insert_name_campo('nome', 'John');
banco_insert_name_campo('email', 'john@email.com');

// Retrieve
$campos = banco_insert_name_campos();

// Insert
banco_insert_name($campos, 'usuarios');
```

---

### banco_insert_name_varios()

Inserts multiple records at once.

**Signature:**
```php
function banco_insert_name_varios($params = false)
```

**Parameters (Associative Array):**
- `tabela` (string) - **Required** - Table name
- `campos` (array) - **Required** - Field configuration array
  - `nome` (string) - Field name
  - `valores` (array) - Array of values
  - `sem_aspas_simples` (bool) - Optional

**Return:**
- (void)

**Usage Example:**
```php
banco_insert_name_varios(Array(
    'tabela' => 'produtos',
    'campos' => Array(
        Array(
            'nome' => 'nome',
            'valores' => Array('Product 1', 'Product 2', 'Product 3')
        ),
        Array(
            'nome' => 'preco',
            'valores' => Array('10.50', '20.00', '15.75'),
            'sem_aspas_simples' => false
        ),
        Array(
            'nome' => 'estoque',
            'valores' => Array(100, 200, 150),
            'sem_aspas_simples' => true
        )
    )
));

// Generates: INSERT INTO produtos (nome,preco,estoque) VALUES
// ('Product 1','10.50',100),
// ('Product 2','20.00',200),
// ('Product 3','15.75',150)
```

**Notes:**
- Very efficient for imports and migrations
- All value arrays must have the same size

---

### banco_insert_varios()

Inserts multiple records with auto-increment ID (legacy).

**Signature:**
```php
function banco_insert_varios($campos, $tabela)
```

**Parameters:**
- `$campos` (array) - Array of value strings
- `$tabela` (string) - Table name

**Return:**
- (void)

**Usage Example:**
```php
$campos = Array(
    "'John','john@email.com'",
    "'Mary','mary@email.com'",
    "'Peter','peter@email.com'"
);

banco_insert_varios($campos, 'usuarios');
```

---

### banco_last_id()

Returns the last inserted ID.

**Signature:**
```php
function banco_last_id()
```

**Parameters:**
- None

**Return:**
- (int) - ID of the last inserted record

**Usage Example:**
```php
banco_insert_name($data, 'usuarios');
$new_id = banco_last_id();

echo "User created with ID: " . $new_id;
```

---

## DELETE Functions

### banco_delete()

Executes DELETE SQL.

**Signature:**
```php
function banco_delete($tabela, $extra)
```

**Parameters:**
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Required** - WHERE clause and others

**Return:**
- (void)

**Usage Example:**
```php
// Delete specific user
banco_delete('usuarios', "WHERE id='123'");

// Delete multiple with condition
banco_delete('logs', "WHERE data < '2024-01-01'");

// WARNING: without WHERE deletes everything!
banco_delete('temp_data', '');
```

**⚠️ Attention:**
- Always use WHERE to avoid deleting the entire table
- For soft delete, prefer UPDATE with status='D'

---

### banco_delete_varios()

Deletes multiple records using IN.

**Signature:**
```php
function banco_delete_varios($tabela, $campo_ids, $array_ids)
```

**Parameters:**
- `$tabela` (string) - **Required** - Table name
- `$campo_ids` (string|array) - **Required** - ID field name(s)
- `$array_ids` (array) - **Required** - IDs to delete

**Return:**
- (void)

**Usage Example:**
```php
// Delete by one field
$ids = Array('1', '2', '3', '5', '8');
banco_delete_varios('usuarios', 'id', $ids);
// DELETE FROM usuarios WHERE id IN ('1','2','3','5','8')

// Delete by multiple fields
$campos = Array('campo1', 'campo2');
$values = Array(
    'campo1' => Array('1', '2'),
    'campo2' => Array('A', 'B')
);
banco_delete_varios('tabela', $campos, $values);
// DELETE FROM tabela WHERE campo1 IN ('1','2') AND campo2 IN ('A','B')
```

---

## Result Data Functions

### banco_num_rows()

Returns number of rows in a result.

**Signature:**
```php
function banco_num_rows($result)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result

**Return:**
- (int) - Number of rows

**Usage Example:**
```php
$result = banco_query("SELECT * FROM usuarios");
$total = banco_num_rows($result);

echo "Found: " . $total . " users";
```

---

### banco_num_fields()

Returns number of fields in a result.

**Signature:**
```php
function banco_num_fields($result)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result

**Return:**
- (int) - Number of fields

**Usage Example:**
```php
$result = banco_query("SELECT id, nome, email FROM usuarios LIMIT 1");
$num_fields = banco_num_fields($result);

echo "Selected fields: " . $num_fields; // 3
```

---

### banco_field_name()

Returns name of a specific field from the result.

**Signature:**
```php
function banco_field_name($result, $num_field)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result
- `$num_field` (int) - **Required** - Field index (starts at 0)

**Return:**
- (string) - Field name

**Usage Example:**
```php
$result = banco_query("SELECT id, nome, email FROM usuarios LIMIT 1");

echo banco_field_name($result, 0); // 'id'
echo banco_field_name($result, 1); // 'nome'
echo banco_field_name($result, 2); // 'email'
```

---

### banco_fields_names()

Returns array with all field names of a table.

**Signature:**
```php
function banco_fields_names($table)
```

**Parameters:**
- `$table` (string) - **Required** - Table name

**Return:**
- (array|null) - Array of field names or null

**Usage Example:**
```php
$fields = banco_fields_names('usuarios');

if ($fields) {
    foreach ($fields as $field) {
        echo $field . '<br>';
    }
}
// Output:
// id
// nome
// email
// status
// criado_em
```

---

### banco_row()

Returns next row as numeric array.

**Signature:**
```php
function banco_row($result)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result

**Return:**
- (array|null) - Numeric array or null

**Usage Example:**
```php
$result = banco_query("SELECT id, nome FROM usuarios");

while ($row = banco_row($result)) {
    echo $row[0] . ' - ' . $row[1] . '<br>';
}
```

---

### banco_row_array()

Returns next row as array (numeric and associative).

**Signature:**
```php
function banco_row_array($result)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result

**Return:**
- (array|null) - Mixed array or null

**Usage Example:**
```php
$result = banco_query("SELECT id, nome FROM usuarios LIMIT 1");
$row = banco_row_array($result);

echo $row[0];        // By index
echo $row['nome'];   // By name
```

---

### banco_fetch_assoc()

Returns next row as associative array.

**Signature:**
```php
function banco_fetch_assoc($result)
```

**Parameters:**
- `$result` (mysqli_result) - **Required** - Query result

**Return:**
- (array|null) - Associative array or null

**Usage Example:**
```php
$result = banco_query("SELECT * FROM usuarios");

while ($user = banco_fetch_assoc($result)) {
    echo $user['id'];
    echo $user['nome'];
    echo $user['email'];
}
```

---

## Utility Functions

### banco_escape_field()

Escapes string to prevent SQL injection.

**Signature:**
```php
function banco_escape_field($field)
```

**Parameters:**
- `$field` (string) - **Required** - String to be escaped

**Return:**
- (string) - Escaped string

**Usage Example:**
```php
$name = banco_escape_field($_POST['nome']);
$email = banco_escape_field($_POST['email']);

$sql = "INSERT INTO usuarios (nome, email) VALUES ('$name', '$email')";
banco_query($sql);
```

**⚠️ Important:**
- Always escape user input values
- Not necessary when using `banco_update_campo()` and similar (they do it automatically)

---

### banco_smartstripslashes()

Removes slashes from string intelligently.

**Signature:**
```php
function banco_smartstripslashes($str)
```

**Parameters:**
- `$str` (string) - **Required** - String to process

**Return:**
- (string) - Processed string

**Usage Example:**
```php
$text = banco_smartstripslashes($text_from_db);
```

**Notes:**
- Used internally by SELECT functions
- Converts to string

---

### banco_campos_virgulas()

Converts array of fields into comma-separated string.

**Signature:**
```php
function banco_campos_virgulas($campos)
```

**Parameters:**
- `$campos` (array) - **Required** - Array of fields

**Return:**
- (string) - Fields separated by comma

**Usage Example:**
```php
$fields = Array('id', 'nome', 'email', 'status');
$fields_str = banco_campos_virgulas($fields);

echo $fields_str; // 'id,nome,email,status'

// Usage in SELECT
$sql = "SELECT " . $fields_str . " FROM usuarios";
```

---

### banco_total_rows()

Counts total records in a table.

**Signature:**
```php
function banco_total_rows($tabela, $extra = null)
```

**Parameters:**
- `$tabela` (string) - **Required** - Table name
- `$extra` (string) - **Optional** - WHERE clause

**Return:**
- (int) - Total records

**Usage Example:**
```php
// General total
$total = banco_total_rows('usuarios');
echo "Total users: " . $total;

// With condition
$active = banco_total_rows('usuarios', "WHERE status='A'");
echo "Active users: " . $active;
```

---

### banco_campos_nomes()

Returns complete information about fields of a table.

**Signature:**
```php
function banco_campos_nomes($tabela)
```

**Parameters:**
- `$tabela` (string) - **Required** - Table name

**Return:**
- (array) - Array of associative arrays with field information

**Usage Example:**
```php
$fields = banco_campos_nomes('usuarios');

foreach ($fields as $field) {
    echo "Field: " . $field['Field'] . '<br>';
    echo "Type: " . $field['Type'] . '<br>';
    echo "Null: " . $field['Null'] . '<br>';
    echo "Key: " . $field['Key'] . '<br>';
    echo "Default: " . $field['Default'] . '<br>';
    echo "Extra: " . $field['Extra'] . '<br>';
    echo '<br>';
}
```

**Returned Fields:**
- `Field` - Field name
- `Type` - Field type (varchar, int, etc.)
- `Null` - YES/NO
- `Key` - PRI (Primary), MUL (Multiple), etc.
- `Default` - Default value
- `Extra` - auto_increment, etc.

---

### banco_tabelas_lista()

Lists all database tables.

**Signature:**
```php
function banco_tabelas_lista()
```

**Parameters:**
- None

**Return:**
- (array) - Array with table names

**Usage Example:**
```php
$tables = banco_tabelas_lista();

foreach ($tables as $table) {
    echo $table . '<br>';
}
```

---

### banco_retirar_acentos()

Removes accents and special characters from string.

**Signature:**
```php
function banco_retirar_acentos($var, $retirar_espaco = true)
```

**Parameters:**
- `$var` (string) - **Required** - String to process
- `$retirar_espaco` (bool) - **Optional** - If true, replaces spaces with hyphen

**Return:**
- (string) - Normalized string

**Usage Example:**
```php
$title = "Programming in São Paulo 2025!";
$slug = banco_retirar_acentos($title);
// Returns: "programming-in-sao-paulo-2025"

// Keep spaces
$name = banco_retirar_acentos("José da Silva", false);
// Returns: "jose da silva"
```

**Transformations:**
- Converts to lowercase
- Removes accents (á -> a, ç -> c, etc.)
- Removes special characters
- Replaces spaces with hyphen (optional)
- Removes duplicate hyphens

---

## Advanced Functions

### banco_identificador()

Generates unique identifier for a record (slug).

**Signature:**
```php
function banco_identificador($params = false)
```

**Parameters (Associative Array):**
- `id` (string) - **Required** - Base string for the identifier
- `tabela` (array) - **Required** - Table configuration
  - `nome` (string) - Table name
  - `campo` (string) - Identifier field name
  - `id_nome` (string) - ID field name
  - `id_valor` (string) - Optional - ID to exclude (for editing)
  - `status` (string) - Optional - Status field name
  - `sem_status` (bool) - Optional - Ignore status check
  - `where` (string) - Optional - Additional WHERE clause
- `sem_traco` (bool) - **Optional** - Remove hyphens from result

**Return:**
- (string) - Unique identifier

**Usage Example:**
```php
// Create slug for product
$identifier = banco_identificador(Array(
    'id' => 'Notebook Dell Inspiron 15',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'slug',
        'id_nome' => 'id'
    )
));
// Returns: "notebook-dell-inspiron-15"
// If already exists, returns: "notebook-dell-inspiron-15-1"

// For editing (do not check the record itself)
$identifier = banco_identificador(Array(
    'id' => 'Notebook Dell Inspiron 15',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'slug',
        'id_nome' => 'id',
        'id_valor' => '123'  // ID of record being edited
    )
));

// Without hyphens
$identifier = banco_identificador(Array(
    'id' => 'Product ABC',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'codigo',
        'id_nome' => 'id'
    ),
    'sem_traco' => true
));
// Returns: "productabc" or "productabc1"
```

**Notes:**
- Removes accents automatically
- Adds numeric suffix if already exists (-1, -2, etc.)
- Limits size to 90 characters
- Useful for friendly URLs and unique codes

---

### banco_identificador_unico()

Recursive helper function for `banco_identificador()`.

**Signature:**
```php
function banco_identificador_unico($params = false)
```

**Notes:**
- Internal use
- Checks identifier availability
- Increments suffix until finding a unique one

---

### banco_insert_update()

Inserts or updates record automatically.

**Signature:**
```php
function banco_insert_update($params = false)
```

**Parameters (Associative Array):**
- `dados` (array) - **Required** - Associative array [field => value]
- `tabela` (array) - **Required** - Table configuration
  - `nome` (string) - Table name
  - `id` (string) - ID field name
  - `extra` (string) - Optional - Additional WHERE
- `dadosTipo` (array) - **Optional** - Data types [field => type]
  - Types: 'bool', 'int', (default: string)

**Return:**
- (void)

**Usage Example:**
```php
// Form data
$data = Array(
    'id' => $_POST['id'],
    'nome' => $_POST['nome'],
    'email' => $_POST['email'],
    'idade' => $_POST['idade'],
    'ativo' => isset($_POST['ativo']) ? 1 : 0
);

// Special types
$types = Array(
    'idade' => 'int',
    'ativo' => 'bool'
);

// If ID exists, UPDATE, else INSERT
banco_insert_update(Array(
    'dados' => $data,
    'tabela' => Array(
        'nome' => 'usuarios',
        'id' => 'id'
    ),
    'dadosTipo' => $types
));

// With additional WHERE
banco_insert_update(Array(
    'dados' => $data,
    'tabela' => Array(
        'nome' => 'usuarios',
        'id' => 'id',
        'extra' => "AND empresa='XYZ'"
    ),
    'dadosTipo' => $types
));
```

**Behavior:**
- Checks if record with ID exists
- If exists: UPDATE (removes ID from data)
- If not exists: INSERT
- Automatically escapes string values
- Handles NULL for empty bool and int

**Use Case:**
- Unified creation/editing forms
- Data synchronization
- Imports with upsert

---

### banco_erro_debug()

Generates debug trace for database errors.

**Signature:**
```php
function banco_erro_debug()
```

**Parameters:**
- None

**Return:**
- (string) - HTML with backtrace

**Usage Example:**
```php
// Used internally in error messages
// Not necessary to call directly
```

**Notes:**
- Shows file, line and function of error
- Useful for debugging
- Already used automatically in connection errors

---

## Common Use Cases

### 1. Complete User CRUD

```php
// CREATE
banco_insert_name_campo('nome', 'John Doe');
banco_insert_name_campo('email', 'john@email.com');
banco_insert_name_campo('senha', md5('password123'));
banco_insert_name_campo('status', 'A');
$campos = banco_insert_name_campos();
banco_insert_name($campos, 'usuarios');
$new_id = banco_last_id();

// READ
$user = banco_select(Array(
    'campos' => Array('id', 'nome', 'email', 'status'),
    'tabela' => 'usuarios',
    'extra' => "WHERE id='$new_id'",
    'unico' => true
));

// UPDATE
banco_update_campo('nome', 'John Peter Doe');
banco_update_campo('email', 'johnpeter@email.com');
banco_update_executar('usuarios', "WHERE id='$new_id'");

// DELETE
banco_delete('usuarios', "WHERE id='$new_id'");
```

### 2. Listing with Pagination

```php
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Total records
$total = banco_total_rows('produtos', "WHERE status='A'");

// Page products
$products = banco_select(Array(
    'campos' => Array('id', 'nome', 'preco', 'estoque'),
    'tabela' => 'produtos',
    'extra' => "WHERE status='A' ORDER BY nome LIMIT $offset, $per_page"
));

// Total pages
$total_pages = ceil($total / $per_page);
```

### 3. Edit Form with Change Detection

```php
$id = $_POST['id'];

// Store previous values
banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='$id'"
);

// Process form
$new_name = $_POST['nome'];
$previous_name = banco_select_campos_antes('nome');

if ($new_name != $previous_name) {
    // Create change log
    banco_insert_name(Array(
        Array('tabela', 'usuarios'),
        Array('registro_id', $id),
        Array('campo', 'nome'),
        Array('valor_anterior', $previous_name),
        Array('valor_novo', $new_name),
        Array('usuario_id', $_SESSION['usuario_id'])
    ), 'logs_auditoria');
}

// Update
banco_update_campo('nome', $new_name);
banco_update_campo('email', $_POST['email']);
banco_update_executar('usuarios', "WHERE id='$id'");
```

### 4. Bulk Import

```php
// Prepare data
$names = Array();
$emails = Array();
$ages = Array();

foreach ($csv_data as $row) {
    $names[] = $row['nome'];
    $emails[] = $row['email'];
    $ages[] = $row['idade'];
}

// Insert all at once
banco_insert_name_varios(Array(
    'tabela' => 'usuarios',
    'campos' => Array(
        Array('nome' => 'nome', 'valores' => $names),
        Array('nome' => 'email', 'valores' => $emails),
        Array('nome' => 'idade', 'valores' => $ages, 'sem_aspas_simples' => true),
        Array('nome' => 'status', 'valores' => array_fill(0, count($names), 'A'))
    )
));
```

### 5. Batch Order Update

```php
// Receive new order via AJAX
$new_order = $_POST['ordem']; // Array of [id => position]

$campos = Array();
foreach ($new_order as $id => $position) {
    $campos[] = Array($id, $position);
}

// Update all at once
banco_update_varios(
    $campos,
    'menu_itens',
    'ordem',  // field to update
    'id'      // identifier field
);
```

### 6. Unique Slug Generation

```php
$title = $_POST['titulo'];

$slug = banco_identificador(Array(
    'id' => $title,
    'tabela' => Array(
        'nome' => 'artigos',
        'campo' => 'slug',
        'id_nome' => 'id',
        'id_valor' => isset($_POST['id']) ? $_POST['id'] : null
    )
));

// Insert or update with unique slug
$data = Array(
    'id' => isset($_POST['id']) ? $_POST['id'] : uniqid(),
    'titulo' => $title,
    'slug' => $slug,
    'conteudo' => $_POST['conteudo']
);

banco_insert_update(Array(
    'dados' => $data,
    'tabela' => Array('nome' => 'artigos', 'id' => 'id')
));
```

### 7. Search with Multiple Filters

```php
$where = Array();
$where[] = "status='A'";

if (!empty($_GET['categoria'])) {
    $category = banco_escape_field($_GET['categoria']);
    $where[] = "categoria='$category'";
}

if (!empty($_GET['busca'])) {
    $search = banco_escape_field($_GET['busca']);
    $where[] = "(nome LIKE '%$search%' OR descricao LIKE '%$search%')";
}

if (!empty($_GET['preco_min'])) {
    $min_price = (float)$_GET['preco_min'];
    $where[] = "preco >= $min_price";
}

$where_sql = implode(' AND ', $where);

$products = banco_select(Array(
    'campos' => '*',
    'tabela' => 'produtos',
    'extra' => "WHERE $where_sql ORDER BY nome"
));
```

---

## Patterns and Best Practices

### Security

1. **Always escape user input:**
```php
// ❌ WRONG - SQL Injection
$name = $_POST['nome'];
$sql = "SELECT * FROM usuarios WHERE nome='$name'";

// ✅ CORRECT
$name = banco_escape_field($_POST['nome']);
$sql = "SELECT * FROM usuarios WHERE nome='$name'";

// ✅ BETTER - Use high-level functions
$users = banco_select(Array(
    'campos' => '*',
    'tabela' => 'usuarios',
    'extra' => "WHERE nome='" . banco_escape_field($_POST['nome']) . "'"
));
```

2. **Use prepared statements via builders:**
```php
// ✅ Fields are automatically escaped
banco_update_campo('nome', $_POST['nome']);
banco_update_campo('email', $_POST['email']);
banco_update_executar('usuarios', "WHERE id='" . banco_escape_field($_POST['id']) . "'");
```

### Performance

1. **Use multiple INSERTs instead of loops:**
```php
// ❌ SLOW - Multiple queries
foreach ($data as $item) {
    banco_insert_name(Array(
        Array('nome', $item['nome']),
        Array('valor', $item['valor'])
    ), 'tabela');
}

// ✅ FAST - One query
banco_insert_name_varios(Array(
    'tabela' => 'tabela',
    'campos' => Array(
        Array('nome' => 'nome', 'valores' => array_column($data, 'nome')),
        Array('nome' => 'valor', 'valores' => array_column($data, 'valor'))
    )
));
```

2. **Use batch UPDATE:**
```php
// ❌ SLOW
foreach ($updates as $id => $value) {
    banco_update("campo='$value'", 'tabela', "WHERE id='$id'");
}

// ✅ FAST
$campos = Array();
foreach ($updates as $id => $value) {
    $campos[] = Array($id, $value);
}
banco_update_varios($campos, 'tabela', 'campo', 'id');
```

3. **Select only necessary fields:**
```php
// ❌ Fetches unnecessary data
$users = banco_select(Array('campos' => '*', 'tabela' => 'usuarios'));

// ✅ Fetches only necessary
$users = banco_select(Array(
    'campos' => Array('id', 'nome'),
    'tabela' => 'usuarios'
));
```

### Organization

1. **Use high-level functions when possible:**
```php
// ✅ GOOD - Prefer banco_select()
$data = banco_select(Array(
    'campos' => Array('id', 'nome'),
    'tabela' => 'usuarios',
    'extra' => "WHERE status='A'"
));

// Instead of banco_query() + manual processing
```

2. **Group related operations:**
```php
// ✅ GOOD - Grouped operations
banco_update_campo('nome', $name);
banco_update_campo('email', $email);
banco_update_campo('telefone', $phone);
banco_update_campo('atualizado_em', 'NOW()', true, false);
banco_update_executar('usuarios', "WHERE id='$id'");
```

---

## Limitations and Considerations

### Compatibility

- **MySQLi Only**: Does not support other databases (PostgreSQL, SQLite)
- **PHP 5.4+**: Some functions may require newer versions

### Transactions

- No native support for transactions
- For transactions, use `banco_query()` directly:

```php
banco_query("START TRANSACTION");
try {
    banco_insert_name($data1, 'tabela1');
    banco_insert_name($data2, 'tabela2');
    banco_query("COMMIT");
} catch (Exception $e) {
    banco_query("ROLLBACK");
}
```

### Prepared Statements

- The library does not use native MySQLi prepared statements
- Uses manual escape via `mysqli_real_escape_string()`
- Secure when used correctly, but less elegant than PDO

### Persistent Connection

- Does not use persistent connections
- Each script creates a new connection
- For high performance, consider external connection pooling

---

## See Also

- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Main CMS functions
- [LIBRARY-AUTHENTICATION.md](./LIBRARY-AUTHENTICATION.md) - Authentication and security
- [LIBRARY-FORMAT.md](./LIBRARY-FORMAT.md) - Data formatting
- [Knowledge System](../CONN2FLOW-KNOWLEDGE-SYSTEM.md) - General documentation

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
