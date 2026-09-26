---
title: "Biblioteca formato.php"
description: "Conversões entre o formato do banco e o brasileiro: datas, números com vírgula e milhar, e o despachante formato_dado()."
section: reference
order: 170
sources:
  - gestor/bibliotecas/formato.php
  - gestor/bibliotecas/interface.php
verified_at: 9ea84638
---

# Biblioteca `formato.php`

Converte valores entre o formato do banco (`AAAA-MM-DD HH:MM:SS`, ponto decimal) e o formato **brasileiro** (`DD/MM/AAAA`, vírgula decimal, ponto de milhar). Os formatos são fixos: não há variação por idioma. A [interface.php](interface.md) a usa para exibir valores nas listagens e no histórico.

## Datas

| Função | Entrada → saída |
|---|---|
| `formato_data_from_datetime_to_text($dt)` | `2026-09-26 14:30:00` → `26/09/2026` |
| `formato_data_hora_from_datetime_to_text($dt, $formato = false)` | `2026-09-26 14:30:00` → `26/09/2026 14h30` |
| `formato_data_hora_padrao_datetime($br, $semHora = false)` | `26/09/2026 14:30` → `2026-09-26 14:30:00` (ou `2026-09-26`) |
| `formato_data_hora_br_para_datetime($s)` | Aceita `DD/MM/AAAA[ HH:MM[:SS]]`, `AAAA-MM-DD…` e o `T` do `datetime-local`; devolve `AAAA-MM-DD HH:MM:SS` ou `null` |
| `formato_data_hora_datetime_para_input($dt)` | `2026-09-26 14:30:00` → `2026-09-26T14:30` (valor de `<input type="datetime-local">`); `''` para vazio ou `0000-00-00` |
| `formato_data_hora_array($dt)` | Array com `dia`, `mes`, `ano` e, se houver hora, `hora`, `min`, `seg` |

Em `formato_data_hora_from_datetime_to_text()`, o `$formato` troca os códigos `D`, `ME`, `A`, `H`, `MI` e `S` (padrão `D/ME/A HhMI`).

> [!WARNING]
> Os códigos do `$formato` são trocados **em qualquer lugar do texto**, inclusive dentro de palavras: `'Data: D'` vira `'26ata: 26'`. Use só códigos e separadores no formato e monte o resto fora.

Só `formato_data_hora_br_para_datetime()` e `formato_data_hora_datetime_para_input()` validam a entrada. As demais partem a string por posição e geram *warnings* (e resultados sem sentido) com um valor fora do padrão esperado.

## Números

| Função | Exemplo |
|---|---|
| `formato_float_para_texto($f)` | `1234.5` → `1.234,50` (sempre 2 casas; o 2º parâmetro é ignorado) |
| `formato_texto_para_float($t)` | `1.234,50` → `1234.50` (string) |
| `formato_int_para_texto($i)` | `1234567` → `1.234.567` |
| `formato_texto_para_int($t)` | `1.234.567` → `1234567` (string) |
| `formato_zero_a_esquerda($n, $digitos)` | `(7, 3)` → `007` |
| `formato_colocar_char_meio_numero($n, $char = '-')` | `123456` → `123-456` |

- `formato_texto_para_float()` trata todo ponto como separador de milhar: `'10.5'` vira `105.`. Sem vírgula, gera *warning* e devolve o número com um ponto no fim (`'10'` → `10.`).
- `formato_colocar_char_meio_numero()` com um número de um só dígito termina em erro (`str_split` com tamanho 0).

## `formato_dado()`

```php
formato_dado(['valor' => $v, 'tipo' => 'float-para-texto']);
formato_dado_para('dataHora', $v);   // atalho
```

Tipos: `float-para-texto`, `texto-para-float`, `int-para-texto`, `texto-para-int`, `data`, `dataHora`, `datetime` e `date`. Um tipo desconhecido devolve o valor sem alteração; sem `valor` ou `tipo`, devolve `''`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/formato.php` por `c2f docs:extract` — 14 funções. Não edite dentro deste bloco.

- `formato_data_hora_array(string $data_hora_padrao_datetime_ou_padrao_date): array` — [linha 33](../../../../../gestor/bibliotecas/formato.php#L33)
- `formato_data_hora_padrao_datetime(string $dataHora, bool $semHora = false): string` — [linha 75](../../../../../gestor/bibliotecas/formato.php#L75)
- `formato_data_hora_br_para_datetime(string $str): string|null` — [linha 96](../../../../../gestor/bibliotecas/formato.php#L96)
- `formato_data_hora_datetime_para_input(string $str): string` — [linha 124](../../../../../gestor/bibliotecas/formato.php#L124)
- `formato_data_hora_from_datetime_to_text(string $data_hora, string|false $format = false): string` — [linha 143](../../../../../gestor/bibliotecas/formato.php#L143)
- `formato_data_from_datetime_to_text(string $data_hora): string` — [linha 196](../../../../../gestor/bibliotecas/formato.php#L196)
- `formato_float_para_texto(float $float, bool $sem_descimal = false): string` — [linha 219](../../../../../gestor/bibliotecas/formato.php#L219)
- `formato_texto_para_float(string $texto): string` — [linha 234](../../../../../gestor/bibliotecas/formato.php#L234)
- `formato_int_para_texto(int $int): string` — [linha 270](../../../../../gestor/bibliotecas/formato.php#L270)
- `formato_texto_para_int(string $texto): string` — [linha 284](../../../../../gestor/bibliotecas/formato.php#L284)
- `formato_zero_a_esquerda(int|string $num, int $dig): string` — [linha 300](../../../../../gestor/bibliotecas/formato.php#L300)
- `formato_colocar_char_meio_numero(int|string $num, string $char = '-'): string` — [linha 328](../../../../../gestor/bibliotecas/formato.php#L328)
- `formato_dado_para(string $tipo, mixed $valor): string` — [linha 356](../../../../../gestor/bibliotecas/formato.php#L356)
- `formato_dado(array|false $params = false): string` — [linha 391](../../../../../gestor/bibliotecas/formato.php#L391)

<!-- c2f:extract:end -->
