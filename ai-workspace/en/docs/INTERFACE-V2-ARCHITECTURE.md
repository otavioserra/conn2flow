# Interface V2 — Architecture Documentation

## Overview

`interface-v2` is a complete OOP rewrite of the procedural `interface.php` (v1.1.5, 5513 lines) library.
It provides a **fluent builder API** with method chaining for constructing administrative interfaces:
listings, forms, CRUD operations, validations, history, backups, alerts, and widgets.

**Key Numbers:**
- `interface.php` (v1): **5513 lines**, ~50+ procedural functions, nested arrays
- `interface-v2.php` (v2): **~3015 lines**, OOP with enums, value objects, facade pattern
- `interface-v2.js` (v2): **~530 lines**, ES6+ classes replacing ~1122-line procedural jQuery code
- `admin-paginas-v2.php`: **~600 lines** vs `admin-paginas.php`: **~1277 lines** (53% reduction)

## Requirements

- **PHP 8.5+** (pipe operator, clone with named assignments, `#[NoDiscard]`)
- **Fomantic-UI** (frontend CSS framework)
- **jQuery** (frontend JS — compatible with existing ecosystem)
- **DataTables.js** (server-side listing)

## PHP 8.5 Features Used

### Pipe Operator (`|>`)
Used for chaining string transformations elegantly:
```php
$resultado = $formato
    |> (fn(string $f) => str_replace('D', $dia, $f))
    |> (fn(string $f) => str_replace('ME', $mes, $f))
    |> (fn(string $f) => str_replace('A', $ano, $f));
```

### Clone with Named Assignments
Used in value objects for immutability:
```php
return clone($this, array_filter([
    'nome' => $nome,
    'formato' => $formato,
], fn($v) => $v !== null));
```

### `#[NoDiscard]` Attribute
Applied to methods whose return value must be used:
```php
#[NoDiscard("O valor formatado deve ser utilizado.")]
public static function dataHora(string $dataHora): string { ... }
```

### Other Modern PHP Features
- Enums (`FormatoTipo`, `RegraValidacao`, `OperacaoCrud`, `TipoCampo`)
- Readonly properties in value objects
- Named arguments throughout
- Match expressions instead of switch
- Arrow functions (`fn()`)
- Spread operator (`...$args`, `[...$a, ...$b]`)
- Null coalescing (`??`, `??=`)
- `str_contains()` instead of `strpos()`

## Architecture

### Class Diagram

```
┌──────────────────────────────────────────────────┐
│                    ENUMS                         │
├──────────────┬───────────────┬───────────────────┤
│ FormatoTipo  │ RegraValidacao│ OperacaoCrud      │
│ TipoCampo    │               │                   │
└──────────────┴───────────────┴───────────────────┘

┌──────────────────────────────────────────────────┐
│               VALUE OBJECTS                      │
├──────────────┬───────────────┬───────────────────┤
│ ColunaConfig │ BotaoConfig   │ AcaoConfig        │
│ CampoConfig  │ ValidacaoConfig│                  │
└──────────────┴───────────────┴───────────────────┘

┌──────────────────────────────────────────────────┐
│            UTILITY CLASSES                       │
├──────────────┬───────────────┬───────────────────┤
│ Formatador   │ Alerta        │ Historico         │
│ Interface    │ Interface     │ Interface         │
├──────────────┼───────────────┼───────────────────┤
│ Backup       │ Componentes   │ Botoes            │
│ Interface    │ Interface     │ Interface         │
└──────────────┴───────────────┴───────────────────┘

┌──────────────────────────────────────────────────┐
│          FACADE (Main Class)                     │
│                                                  │
│  InterfaceV2                                     │
│  ├── Factory: criar()                            │
│  ├── Config: banco(), where(), coluna(), acao()  │
│  ├── Form: campo(), select(), validacao()        │
│  ├── Layout: metaDado(), variavel(), botao()     │
│  ├── Execute: listar(), editar(), adicionar()    │
│  ├── Connect: editarIniciar(), excluirIniciar()  │
│  ├── AJAX: processarAjax()                       │
│  └── Finish: finalizar()                         │
└──────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────┐
│          GLOBAL FUNCTION                         │
│  interface_v2() → InterfaceV2 singleton          │
└──────────────────────────────────────────────────┘
```

### Design Patterns

| Pattern | Usage |
|---------|-------|
| **Facade** | `InterfaceV2` hides complexity of multiple subsystems |
| **Builder** | Fluent method chaining for configuration |
| **Value Object** | Immutable configs (`ColunaConfig`, `BotaoConfig`, etc.) |
| **Singleton** | `interface_v2()` returns single instance per request |
| **Strategy** | Enums + match expressions for format dispatching |

## File Structure

```
gestor/
├── bibliotecas/
│   └── interface-v2.php          # Main PHP library (3015 lines)
├── assets/
│   └── interface-v2/
│       ├── interface-v2.js       # ES6+ frontend (530 lines)
│       └── interface-v2.css      # V2-specific styles (55 lines)
├── config.php                    # Library registration (line 292)
└── modulos/
    └── admin-paginas-v2/         # Test/demo module
        ├── admin-paginas-v2.json # Module config
        └── admin-paginas-v2.php  # Module logic (600 lines)
```

## Usage Guide

### 1. Module Registration

In your module's JSON config, add `interface-v2` to the libraries:
```json
{
    "bibliotecas": ["interface-v2", "html", "html-editor"]
}
```

### 2. Basic Listing

```php
function my_module_interfaces_padroes(): void {
    $iv2 = interface_v2();

    $iv2
        ->banco(nome: 'my_table', id: 'id', status: 'status')
        ->where("language='pt-br'")
        ->rodape(true)

        ->coluna('nome', 'Nome', ordem: 'asc')
        ->coluna('data_modificacao', 'Data', formato: FormatoTipo::DataHora, procuravel: false)

        ->acao('editar', icone: 'edit', tooltip: 'Editar', cor: 'basic blue', url: 'editar/')
        ->acaoExcluir(tooltip: 'Excluir')

        ->botao('adicionar', rotulo: 'Novo', tooltip: 'Adicionar', icone: 'plus', cor: 'blue', url: 'adicionar/')

        ->listar();
}
```

### 3. Edit Form with Validation

```php
function my_module_editar(): void {
    $iv2 = interface_v2();

    $iv2
        ->validacao('nome', RegraValidacao::TextoObrigatorio, 'Nome do Campo')
        ->validacao('email', RegraValidacao::Email, 'E-mail')

        ->select(id: 'tipo', nome: 'tipo', opcoes: [['valor' => 'a', 'texto' => 'Opção A']])

        ->metaDado('Data Criação', FormatadorInterface::dataHora($data))
        ->metaDado('Versão', $versao)

        ->botao('adicionar', rotulo: 'Novo', ...)
        ->botao('excluir', rotulo: 'Excluir', ...)

        ->variavel('campo-css', $css_value)

        ->editar();
}
```

### 4. Start Function Pattern

```php
function my_module_start(): void {
    global $_GESTOR;

    $iv2 = interface_v2();
    gestor_incluir_bibliotecas();

    // Configure banco (needed for delete/status)
    $modulo = $_GESTOR['modulo#' . $_GESTOR['modulo-id']];
    $iv2->banco(
        nome: $modulo['tabela']['nome'],
        id: $modulo['tabela']['id'],
        status: $modulo['tabela']['status'],
    );

    if ($_GESTOR['ajax']) {
        $iv2->processarAjax();

        match ($_GESTOR['ajax-opcao'] ?? '') {
            'custom-action' => my_module_ajax_custom(),
            default => null,
        };
    } else {
        my_module_interfaces_padroes();

        match ($_GESTOR['opcao']) {
            'adicionar' => (function () use ($iv2) {
                $iv2->adicionarIniciar();
                my_module_adicionar();
            })(),
            'editar' => (function () use ($iv2) {
                $iv2->editarIniciar();
                my_module_editar();
            })(),
            'excluir' => (function () use ($iv2) {
                $iv2->excluirIniciar();
                $iv2->excluirFinalizar();
            })(),
            'status' => (function () use ($iv2) {
                $iv2->statusIniciar();
                $iv2->statusFinalizar();
            })(),
            default => null,
        };

        $iv2->finalizar();
    }
}
```

### 5. Server-Side Validation

```php
$iv2
    ->validarServidor('campo_nome', RegraValidacao::TextoObrigatorio, 'Nome', min: 3)
    ->validarServidor('campo_email', RegraValidacao::Email, 'E-mail');
```

### 6. Formatting Data

```php
// Direct usage
$formatted = FormatadorInterface::dataHora('2024-01-15 14:30:00');
// "15/01/2024 14h30"

$money = FormatadorInterface::dinheiroReais(1234.56);
// "R$ 1.234,56"

// With pipe operator (PHP 8.5)
$resultado = $dado
    |> FormatadorInterface::dataHora(...)
    |> fn($v) => FormatadorInterface::encapsular($v, 'span', 'data-class');

// Via dispatcher
$result = FormatadorInterface::formatar($dado, FormatoTipo::OutraTabela, [
    'tabela' => 'modulos',
    'campo_valor' => 'id',
    'campo_texto' => 'nome',
]);
```

## JavaScript Architecture (interface-v2.js)

### Classes

| Class | Purpose |
|-------|---------|
| `InputDebounce` | Timer-based input debouncing |
| `LoadingManager` | Loading modal with counter + min display time |
| `AlertManager` | Alert modal wrapper |
| `DeleteConfirm` | Delete confirmation modal |
| `FormManager` | Form initialization, validation, server-side verify |
| `HistoryLoader` | Paginated AJAX history loading |
| `ImagePickWidget` | Image picker iframe modal |
| `TemplatePickWidget` | Template picker iframe modal |
| `DataTableManager` | Full DataTable server-side configuration |
| `CommonUI` | Static helpers (Ctrl+S, tooltips, dropdowns) |

### JS Namespace

V2 reads config from `gestor['interface-v2']` with fallback to `gestor.interface` for v1 component compatibility.

### jQuery Compatibility

Legacy jQuery plugins are still exposed for backward compatibility:
```javascript
$.dropdown, $.formReiniciar, $.formSubmit, $.formSubmitNormal, $.input_delay_to_change
```

## V1 vs V2 Comparison

### Listing Configuration

**V1 (80+ lines of nested arrays):**
```php
$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
    'banco' => Array(
        'nome' => $modulo['tabela']['nome'],
        'campos' => Array('nome', 'tipo', 'modulo', 'caminho', ...),
        'id' => $modulo['tabela']['id'],
        'status' => $modulo['tabela']['status'],
        'where' => "language='pt-br'",
    ),
    'tabela' => Array(
        'colunas' => Array(
            Array('id' => 'nome', 'nome' => 'Nome', 'ordenar' => 'asc'),
            Array('id' => 'tipo', 'nome' => 'Tipo', 'formatar' => Array(...)),
            // ... many more nested arrays
        ),
    ),
    'opcoes' => Array(
        'editar' => Array('url' => 'editar/', 'tooltip' => '...', ...),
        // ... more nested arrays
    ),
    'botoes' => Array(
        'adicionar' => Array('url' => '...', 'rotulo' => '...', ...),
    ),
);
```

**V2 (50 lines of fluent API):**
```php
interface_v2()
    ->banco(nome: 'paginas', id: 'id', status: 'status')
    ->where("language='pt-br'")
    ->rodape(true)
    ->coluna('nome', 'Nome', ordem: 'asc')
    ->coluna('tipo', 'Tipo', formato: FormatoTipo::OutroConjunto, formatoParams: [...])
    ->acao('editar', icone: 'edit', tooltip: 'Editar', cor: 'basic blue', url: 'editar/')
    ->acaoExcluir(tooltip: 'Excluir')
    ->botao('adicionar', rotulo: 'Novo', ...)
    ->listar();
```

### AJAX Flow

**V1:**
```php
interface_ajax_iniciar();
switch($_GESTOR['ajax-opcao']){
    case 'custom': my_handler(); break;
}
interface_ajax_finalizar();
```

**V2:**
```php
interface_v2()->processarAjax();
match ($_GESTOR['ajax-opcao'] ?? '') {
    'custom' => my_handler(),
    default => null,
};
```

## Migration Guide

1. Change `"interface"` to `"interface-v2"` in module JSON `bibliotecas` array
2. Replace `$_GESTOR['interface'][...]['finalizar'] = Array(...)` with fluent API calls
3. Replace `interface_iniciar()` / `interface_finalizar()` with `$iv2->listar()` / `$iv2->finalizar()`
4. Replace `interface_ajax_iniciar()` / `interface_ajax_finalizar()` with `$iv2->processarAjax()`
5. Replace `interface_alerta(Array(...))` with `AlertaInterface::mostrar(...)`
6. Replace `interface_formatar_dado(Array(...))` with `FormatadorInterface::formatar(...)`
7. Replace `interface_historico_incluir(Array(...))` with `HistoricoInterface::incluir(...)`
8. Replace `interface_backup_campo_incluir(Array(...))` with `BackupInterface::incluir(...)`

## Compatibility Notes

- V2 uses `gestor['interface-v2']` JS namespace (separate from v1)
- V2 uses `-interface-v2-` session keys (no collision with v1)
- Both v1 and v2 can coexist in the same installation
- `ImagePick` and `TemplatesHosts` delegate to v1 functions when available
- The `registrar()` method provides backward compatibility with the v1 pipeline
