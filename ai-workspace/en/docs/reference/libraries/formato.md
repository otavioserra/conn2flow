---
title: "formato.php library"
description: "Conversions between the database format and the Brazilian one: dates, numbers with decimal comma and thousands separator, and the formato_dado() dispatcher."
section: reference
order: 170
sources:
  - gestor/bibliotecas/formato.php
  - gestor/bibliotecas/interface.php
verified_at: 9ea84638
---

# `formato.php` library

Converts values between the database format (`YYYY-MM-DD HH:MM:SS`, decimal point) and the **Brazilian** format (`DD/MM/YYYY`, decimal comma, dot as thousands separator). The formats are fixed: there is no per-language variation. [interface.php](interface.md) uses it to display values in listings and in the history.

## Dates

| Function | Input → output |
|---|---|
| `formato_data_from_datetime_to_text($dt)` | `2026-09-26 14:30:00` → `26/09/2026` |
| `formato_data_hora_from_datetime_to_text($dt, $format = false)` | `2026-09-26 14:30:00` → `26/09/2026 14h30` |
| `formato_data_hora_padrao_datetime($br, $noTime = false)` | `26/09/2026 14:30` → `2026-09-26 14:30:00` (or `2026-09-26`) |
| `formato_data_hora_br_para_datetime($s)` | Accepts `DD/MM/YYYY[ HH:MM[:SS]]`, `YYYY-MM-DD…` and the `T` of `datetime-local`; returns `YYYY-MM-DD HH:MM:SS` or `null` |
| `formato_data_hora_datetime_para_input($dt)` | `2026-09-26 14:30:00` → `2026-09-26T14:30` (value for `<input type="datetime-local">`); `''` for empty or `0000-00-00` |
| `formato_data_hora_array($dt)` | Array with `dia`, `mes`, `ano` and, if there is a time, `hora`, `min`, `seg` |

In `formato_data_hora_from_datetime_to_text()`, `$format` replaces the codes `D`, `ME`, `A`, `H`, `MI` and `S` (default `D/ME/A HhMI`).

> [!WARNING]
> The `$format` codes are replaced **anywhere in the text**, including inside words: `'Data: D'` becomes `'26ata: 26'`. Use only codes and separators in the format and build the rest outside.

Only `formato_data_hora_br_para_datetime()` and `formato_data_hora_datetime_para_input()` validate their input. The others split the string by position and raise warnings (and meaningless results) with a value outside the expected pattern.

## Numbers

| Function | Example |
|---|---|
| `formato_float_para_texto($f)` | `1234.5` → `1.234,50` (always 2 decimals; the 2nd parameter is ignored) |
| `formato_texto_para_float($t)` | `1.234,50` → `1234.50` (string) |
| `formato_int_para_texto($i)` | `1234567` → `1.234.567` |
| `formato_texto_para_int($t)` | `1.234.567` → `1234567` (string) |
| `formato_zero_a_esquerda($n, $digits)` | `(7, 3)` → `007` |
| `formato_colocar_char_meio_numero($n, $char = '-')` | `123456` → `123-456` |

- `formato_texto_para_float()` treats every dot as a thousands separator: `'10.5'` becomes `105.`. Without a comma, it raises a warning and returns the number with a trailing dot (`'10'` → `10.`).
- `formato_colocar_char_meio_numero()` with a single-digit number ends in an error (`str_split` with length 0).

## `formato_dado()`

```php
formato_dado(['valor' => $v, 'tipo' => 'float-para-texto']);
formato_dado_para('dataHora', $v);   // shortcut
```

Types: `float-para-texto`, `texto-para-float`, `int-para-texto`, `texto-para-int`, `data`, `dataHora`, `datetime` and `date`. An unknown type returns the value unchanged; without `valor` or `tipo`, it returns `''`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/formato.php` by `c2f docs:extract` — 14 functions. Do not edit inside this block.

- `formato_data_hora_array(string $data_hora_padrao_datetime_ou_padrao_date): array` — [line 33](../../../../../gestor/bibliotecas/formato.php#L33)
- `formato_data_hora_padrao_datetime(string $dataHora, bool $semHora = false): string` — [line 75](../../../../../gestor/bibliotecas/formato.php#L75)
- `formato_data_hora_br_para_datetime(string $str): string|null` — [line 96](../../../../../gestor/bibliotecas/formato.php#L96)
- `formato_data_hora_datetime_para_input(string $str): string` — [line 124](../../../../../gestor/bibliotecas/formato.php#L124)
- `formato_data_hora_from_datetime_to_text(string $data_hora, string|false $format = false): string` — [line 143](../../../../../gestor/bibliotecas/formato.php#L143)
- `formato_data_from_datetime_to_text(string $data_hora): string` — [line 196](../../../../../gestor/bibliotecas/formato.php#L196)
- `formato_float_para_texto(float $float, bool $sem_descimal = false): string` — [line 219](../../../../../gestor/bibliotecas/formato.php#L219)
- `formato_texto_para_float(string $texto): string` — [line 234](../../../../../gestor/bibliotecas/formato.php#L234)
- `formato_int_para_texto(int $int): string` — [line 270](../../../../../gestor/bibliotecas/formato.php#L270)
- `formato_texto_para_int(string $texto): string` — [line 284](../../../../../gestor/bibliotecas/formato.php#L284)
- `formato_zero_a_esquerda(int|string $num, int $dig): string` — [line 300](../../../../../gestor/bibliotecas/formato.php#L300)
- `formato_colocar_char_meio_numero(int|string $num, string $char = '-'): string` — [line 328](../../../../../gestor/bibliotecas/formato.php#L328)
- `formato_dado_para(string $tipo, mixed $valor): string` — [line 356](../../../../../gestor/bibliotecas/formato.php#L356)
- `formato_dado(array|false $params = false): string` — [line 391](../../../../../gestor/bibliotecas/formato.php#L391)

<!-- c2f:extract:end -->
