---
title: "banco.php library"
label: "Database"
description: "The Gestor database layer (MySQLi): connection, queries, structured SELECT, accumulated INSERT/UPDATE, unique identifiers and distributed mode."
section: reference
order: 10
sources:
  - gestor/bibliotecas/banco.php
  - gestor/config.php
  - gestor/bibliotecas/modulo-distribuido.php
verified_at: a6e51e29
---

# The `banco.php` library

`banco.php` is the data access layer of the whole Gestor. It is always loaded (`$_GESTOR['bibliotecas']`) and talks to **MySQL/MariaDB through MySQLi**, building SQL by string concatenation. There are no prepared statements: safety depends on escaping every value with `banco_escape_field()`.

> [!NOTE]
> The file header and a few messages say it is *deprecated* in favor of `banco-v2.php`. That is **leftover**. `banco-v2` (prepared statements, PHP 8.5 syntax) was an experiment of the 3.0.x line and was **removed from the 2.x line** in req-108 (commit `2c9f7a358`). In the current version, `banco.php` is the official and only database layer.

## Configuration and connection

The connection uses `$_BANCO`, filled in `gestor/config.php` from `.env`:

| Key | `.env` variable | Default |
|---|---|---|
| `tipo` | `DB_CONNECTION` | `mysqli` (the only implemented type) |
| `host` | `DB_HOST` | `localhost` |
| `nome` | `DB_DATABASE` | — |
| `usuario` / `senha` | `DB_USERNAME` / `DB_PASSWORD` | — |

- The connection is **lazy**. `banco_query()` and `banco_escape_field()` call `banco_conectar()` the first time; there is no need to connect by hand.
- `banco_conectar()` enables `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`, opens the connection and sets the `utf8` charset. If the connection fails, it **ends the request with `die()`**, printing the error and the stack built by `banco_erro_debug()` (HTML with `file:line => function` for each frame).
- `banco_fechar_conexao()` closes and removes `$_BANCO['conexao']`.
- `banco_ping()` tests the connection with `mysqli_ping()` and, when it is down, only increments `$_BANCO['RECONECT']`: it **does not reconnect**.

> [!WARNING]
> The connection charset is `utf8` (utf8mb3 in MySQL), which does not accept emoji and other 4-byte characters. See the `c2f-mysql-utf8-emoji-encoding` skill before storing free user text.

## Running SQL

- `banco_query($query)` runs any SQL. With `$_BANCO['distribuido']` enabled, it **does not use the local database**: it forwards the statement to `banco_distribuido_query()` (distributed modules, req-005), which returns a `BancoResultadoRemoto` for SELECT or `true`/`false` for writes. Locally, a `mysqli_sql_exception` is caught, logged with `error_log()` (including the SQL) and becomes `false`. No error reaches the screen.
- `banco_sql($sql)` runs and returns every row with **numeric and named indexes** (`mysqli_fetch_array`), or `null` without results.
- `banco_sql_names($sql, $campos)` runs and returns associative rows using the names in `$campos` (comma-separated list, or `*`).
- `banco_linhas_afetadas()` returns how many rows the last write changed (or `null` in distributed mode). It supports "only the first request claims the action": `UPDATE … WHERE <not done yet>` affects 1 row for whoever arrives first and 0 for the others.
- `banco_last_id()` returns the `AUTO_INCREMENT` of the last INSERT (in distributed mode, the id reported by the remote installation).

### Reading a raw result

The wrappers accept a `mysqli_result` or a `BancoResultadoRemoto`:
- `banco_num_rows($result)` returns 0 for `false`. This avoids the PHP 8.1+ `TypeError` when the query failed;
- `banco_num_fields($result)`, `banco_field_name($result, $i)` and `banco_fields_names($table)`: the last one runs `SELECT * … LIMIT 1` and returns the column names, or `null` when there are no columns;
- `banco_row($result)` returns an indexed row;
- `banco_row_array($result)` returns an indexed and associative row;
- `banco_fetch_assoc($result)` returns an associative row.

## Structured SELECT: `banco_select()`

This is the recommended way to query:

```php
$paginas = banco_select(Array(
    'tabela' => 'paginas',
    'campos' => Array('id', 'nome', 'caminho'),
    'extra'  => "WHERE status='A' AND language='".banco_escape_field($lang)."' ORDER BY nome",
));
// [ ['id' => 'x', 'nome' => 'X', 'caminho' => 'x/'], ... ]  or  null
```

- `campos` is an array (joined with commas) or a string, or `*`.
- `extra` receives everything after `FROM` (JOIN, WHERE, ORDER BY, LIMIT). The table may have an alias: `'tabela' => 'paginas AS p'`.
- `unico` returns only the first row as a flat array, instead of a list.
- Without results the return is **`null`**, not an empty array.

> [!IMPORTANT]
> **Result keys are the exact text of the requested fields.** With `'campos' => ['p.id', 'p.nome']`, the keys are `'p.id'` and `'p.nome'` (this is what the menus widget does). The names come from an `explode(',')` of the fields, not from the database. Expressions containing a comma, such as `CONCAT(a,b)`, break the mapping. Use `AS` without commas, or `banco_sql()`.

> [!WARNING]
> Single-row mode is enabled by `isset($unico)`. So **`'unico' => false` also returns a single row**. To get the list, omit the key.

Positional variants, used by legacy code:
- `banco_select_name($campos, $tabela, $extra)` returns associative rows (non-numeric values cast to string);
- `banco_select_editar($campos, $tabela, $extra)` returns **only the first row** and sets `$_GESTOR['banco-resultado']` (`true`/`false`).

### Keeping the "before" of an edit

The CRUD history pattern:

1. `banco_select_campos_antes_iniciar($campos, $tabela, $extra)` reads the record and stores its first row in `$_GESTOR['banco-antes']` (returns `true`/`false`).
2. `banco_select_campos_antes($campo)` returns a field's old value (or `null`) to compare with `$_REQUEST` and build the history with `interface_historico_incluir()`.

## Writing

### INSERT

The most common way is to accumulate fields and insert once:

```php
banco_insert_name_campo('id', $id);                 // escapes and quotes
banco_insert_name_campo('versao', '1', true);       // no quotes (number, NOW(), NULL)
banco_insert_name_campo('html', $html, false, false); // no escaping (already escaped)
banco_insert_name(banco_insert_name_campos(), 'paginas');
```

- `banco_insert_name_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)` accumulates in `$_BANCO['insert-name-campos']`, escaping the value by default.
- `banco_insert_name_campos()` returns the accumulated fields and **clears** the list.
- `banco_insert_name($dados, $tabela)` takes `[[name, value, no_quotes], …]` and builds `INSERT INTO t (…) VALUES (…)`. It **does not escape**: either the values came from `banco_insert_name_campo()` or you escape them first (as modules do with `banco_escape_field($_REQUEST[...])`). Items without a name or value are skipped.
- `banco_insert_name_varios(['tabela' => …, 'campos' => [['nome' => …, 'valores' => [...], 'sem_aspas_simples' => true], …]])` runs a multi-row INSERT, organized **by column**. `null` becomes `NULL`. It does not escape.

Legacy functions **without callers in the core**, which depend on the physical column order and escape nothing:
- `banco_insert($campos, $tabela)`: `VALUES('0', …)`;
- `banco_insert_tudo($campos, $tabela)` and `banco_insert_id($campos, $tabela)`: `VALUES(…)`;
- `banco_insert_varios($campos, $tabela)` and `banco_insert_varios_tudo($campos, $tabela)`: several rows. Both concatenate into an uninitialized variable (PHP 8 warning).

### UPDATE

- `banco_update($campos, $tabela, $extra)` runs `UPDATE t SET <fields> <extra>`. `$campos` is the ready string (`"nome='x', versao=versao+1"`).
- `banco_update_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)` accumulates in `$_BANCO['update-campos']`, and `banco_update_executar($tabela, $extra = '')` runs and clears it. With nothing accumulated, it does nothing.
- `banco_update_varios($campos, $tabela, $campo_nome, $id_nome)` updates many records with `CASE id WHEN … THEN …`, splitting into several queries above ~1 MB. Values are **not escaped**, and the function concatenates into an uninitialized `$sql_fechar`. No callers in the core.

### Insert or update

`banco_insert_update(['tabela' => ['nome' => …, 'id' => …, 'extra' => …], 'dados' => [...], 'dadosTipo' => [...]])` looks up the record by `dados[<id>]`: UPDATE if it exists, INSERT otherwise. With `dadosTipo`, `bool` stores `1`/`NULL` and `int` stores the number unquoted (empty becomes `NULL`); everything else is escaped and quoted. It **does not filter by language**, neither when looking up nor when updating.

### DELETE

- `banco_delete($tabela, $extra)` runs `DELETE FROM t <extra>`. Without a `WHERE` in `$extra`, it empties the table.
- `banco_delete_varios($tabela, $campo_ids, $array_ids)` builds `DELETE … WHERE field IN (…)`.

> [!CAUTION]
> `banco_delete_varios()` calls `count()` on `$campo_ids` before knowing whether it is an array. With a field name as a string, PHP 8 throws `TypeError`. It also does not escape the ids. It has no callers in the core: do not use it.

## Unique identifiers (slugs)

CRUDs generate the record's textual `id` from its name:

```php
$id = banco_identificador(Array(
    'id' => $_REQUEST['nome'],
    'tabela' => Array(
        'nome' => 'paginas', 'campo' => 'id', 'id_nome' => 'id_paginas',
        'where' => "language='".$_GESTOR['linguagem-codigo']."'",
    ),
));
```

1. `banco_identificador()` normalizes the text with `banco_retirar_acentos()`, cuts it at ~90 characters (by word) and, when the text already ends in `-<number>`, starts from that number.
2. `banco_identificador_unico()` tries `id`, `id-1`, `id-2`… until it finds a free one, ignoring records with `status='D'`. `tabela` options:
   - `id_valor` excludes the record itself, when editing;
   - `status` sets another status column;
   - `sem_status` disables the status filter;
   - `where` adds a condition.
   With `sem_traco`, hyphens are removed from the result.

> [!CAUTION]
> When it picks a free id, `banco_identificador_unico()` **physically deletes** (`DELETE`) the soft-deleted record (`status='D'`) with the same id, to avoid hitting a `UNIQUE` index. A record "in the trash" disappears when another one is born with the same name.

`banco_retirar_acentos($var, $retirar_espaco = true)` replaces accents with ASCII, strips punctuation, turns parentheses and brackets into hyphens and spaces into hyphens (optional), and collapses hyphens.

> [!WARNING]
> `banco_retirar_acentos()` applies `strtolower()` **before** replacing accents, and `strtolower()` does not affect multibyte letters. Uppercase accented letters become **uppercase** ASCII: `"MIGRAÇÕES"` results in `"migraCOes"`. That is where ids with uppercase letters in the middle come from.

## Utilities

- `banco_escape_field($field)` escapes with `mysqli_real_escape_string()`, connecting if needed. Without an active MySQLi connection it **throws `LogicException`** instead of returning the value unescaped.
- `banco_campos_virgulas($campos)` joins an array with commas (`''` when empty).
- `banco_total_rows($tabela, $extra = null)` runs `SELECT count(*)` and returns the total. No callers in the core.
- `banco_campos_nomes($tabela)` runs `SHOW COLUMNS` and returns each column's metadata (`Field`, `Type`, `Null`, `Key`, `Default`, `Extra`).
- `banco_campo_existe($campo, $tabela)` tells whether the column exists. Modules use it to work both before and after a migration (for example, `css_source_hash`).
- `banco_tabelas_lista()` runs `SHOW TABLES` and returns the names.
- `banco_smartstripslashes($str)` only casts to string. It is kept for compatibility; do not use it in new code.

## See also

- [Gestor libraries](index.md)
- Skills `c2f-database-operations`, `c2f-database-testing` and `c2f-mysql-utf8-emoji-encoding`.

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/banco.php` by `c2f docs:extract` — 47 functions. Do not edit inside this block.

- `banco_escape_field(string $field): string` — [line 30](../../../../../gestor/bibliotecas/banco.php#L30)
- `banco_smartstripslashes(mixed $str): string` — [line 57](../../../../../gestor/bibliotecas/banco.php#L57)
- `banco_erro_debug(): string` — [line 69](../../../../../gestor/bibliotecas/banco.php#L69)
- `banco_conectar(): void` — [line 95](../../../../../gestor/bibliotecas/banco.php#L95)
- `banco_ping(): void` — [line 121](../../../../../gestor/bibliotecas/banco.php#L121)
- `banco_fechar_conexao(): void` — [line 142](../../../../../gestor/bibliotecas/banco.php#L142)
- `banco_query(string $query): mysqli_result|bool` — [line 165](../../../../../gestor/bibliotecas/banco.php#L165)
- `banco_linhas_afetadas(): int|null` — [line 206](../../../../../gestor/bibliotecas/banco.php#L206)
- `banco_num_rows(mixed $result): int` — [line 228](../../../../../gestor/bibliotecas/banco.php#L228)
- `banco_num_fields(mysqli_result $result): int` — [line 255](../../../../../gestor/bibliotecas/banco.php#L255)
- `banco_field_name(mysqli_result $result, int $num_field): string` — [line 276](../../../../../gestor/bibliotecas/banco.php#L276)
- `banco_fields_names(string $table): array|null` — [line 299](../../../../../gestor/bibliotecas/banco.php#L299)
- `banco_row(mysqli_result $result): array|null` — [line 330](../../../../../gestor/bibliotecas/banco.php#L330)
- `banco_row_array(mysqli_result $result): array|null` — [line 350](../../../../../gestor/bibliotecas/banco.php#L350)
- `banco_fetch_assoc(mysqli_result $result): array|null` — [line 370](../../../../../gestor/bibliotecas/banco.php#L370)
- `banco_sql(string $sql): array|null` — [line 391](../../../../../gestor/bibliotecas/banco.php#L391)
- `banco_sql_names(string $sql, string $campos): array|null` — [line 419](../../../../../gestor/bibliotecas/banco.php#L419)
- `banco_select(array|false $params = false): array|null` — [line 471](../../../../../gestor/bibliotecas/banco.php#L471)
- `banco_select_name(string $campos, string $tabela, string $extra): array|null` — [line 542](../../../../../gestor/bibliotecas/banco.php#L542)
- `banco_select_editar(string $campos, string $tabela, string $extra): array|null` — [line 599](../../../../../gestor/bibliotecas/banco.php#L599)
- `banco_select_campos_antes_iniciar(string $campos, string $tabela, string $extra): bool` — [line 664](../../../../../gestor/bibliotecas/banco.php#L664)
- `banco_select_campos_antes(string $campo): mixed|null` — [line 724](../../../../../gestor/bibliotecas/banco.php#L724)
- `banco_update(string $campos, string $tabela, string $extra): void` — [line 750](../../../../../gestor/bibliotecas/banco.php#L750)
- `banco_update_campo(string $nome, string $valor, bool $sem_aspas_simples = false, bool $escape_field = true): void` — [line 776](../../../../../gestor/bibliotecas/banco.php#L776)
- `banco_update_executar(string $tabela, string $extra = ''): void` — [line 806](../../../../../gestor/bibliotecas/banco.php#L806)
- `banco_update_varios(array $campos, string $tabela, string $campo_nome, string $id_nome): void` — [line 845](../../../../../gestor/bibliotecas/banco.php#L845)
- `banco_insert(string $campos, string $tabela): void` — [line 882](../../../../../gestor/bibliotecas/banco.php#L882)
- `banco_insert_name(array $dados, string $tabela): void` — [line 898](../../../../../gestor/bibliotecas/banco.php#L898)
- `banco_insert_name_campo(string $nome, string $valor, bool $sem_aspas_simples = false, bool $escape_field = true): void` — [line 940](../../../../../gestor/bibliotecas/banco.php#L940)
- `banco_insert_name_campos(): array` — [line 967](../../../../../gestor/bibliotecas/banco.php#L967)
- `banco_insert_name_varios(array|false $params = false): void` — [line 994](../../../../../gestor/bibliotecas/banco.php#L994)
- `banco_insert_varios(array $campos, string $tabela): void` — [line 1064](../../../../../gestor/bibliotecas/banco.php#L1064)
- `banco_insert_varios_tudo(array $campos, string $tabela): void` — [line 1091](../../../../../gestor/bibliotecas/banco.php#L1091)
- `banco_insert_id(string $campos, string $tabela): void` — [line 1118](../../../../../gestor/bibliotecas/banco.php#L1118)
- `banco_insert_tudo(string $campos, string $tabela): void` — [line 1133](../../../../../gestor/bibliotecas/banco.php#L1133)
- `banco_last_id(): int|null` — [line 1147](../../../../../gestor/bibliotecas/banco.php#L1147)
- `banco_delete(string $tabela, string $extra): void` — [line 1166](../../../../../gestor/bibliotecas/banco.php#L1166)
- `banco_delete_varios(string $tabela, array|string $campo_ids, array $array_ids): void` — [line 1183](../../../../../gestor/bibliotecas/banco.php#L1183)
- `banco_campos_virgulas(array $campos): string` — [line 1222](../../../../../gestor/bibliotecas/banco.php#L1222)
- `banco_total_rows(string $tabela, string|null $extra = null): int` — [line 1250](../../../../../gestor/bibliotecas/banco.php#L1250)
- `banco_campos_nomes(string $tabela): array` — [line 1272](../../../../../gestor/bibliotecas/banco.php#L1272)
- `banco_campo_existe(string $campo, string $tabela): bool` — [line 1304](../../../../../gestor/bibliotecas/banco.php#L1304)
- `banco_retirar_acentos(string $var, bool $retirar_espaco = true): string` — [line 1332](../../../../../gestor/bibliotecas/banco.php#L1332)
- `banco_identificador_unico(array|false $params = false): string` — [line 1376](../../../../../gestor/bibliotecas/banco.php#L1376)
- `banco_identificador(array|false $params = false): string` — [line 1448](../../../../../gestor/bibliotecas/banco.php#L1448)
- `banco_insert_update(array|false $params = false): void` — [line 1527](../../../../../gestor/bibliotecas/banco.php#L1527)
- `banco_tabelas_lista(): array` — [line 1628](../../../../../gestor/bibliotecas/banco.php#L1628)

<!-- c2f:extract:end -->
