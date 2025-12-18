# Library: interface.php

> 🎨 UI Components and Interface Elements

## Overview

The `interface.php` library provides 52 functions to generate user interface components, including menus, forms, tables, modals, alerts, and other visual elements of the CMS.

**Location**: `gestor/bibliotecas/interface.php`  
**Total Functions**: 52

## Main Categories

### Menus and Navigation
- `interface_menu_principal()` - System main menu
- `interface_menu_lateral()` - Collapsible sidebar menu
- `interface_breadcrumb()` - Breadcrumb navigation
- `interface_menu_usuario()` - User dropdown menu

### Forms
- `interface_form_input()` - Input field
- `interface_form_select()` - Select/dropdown
- `interface_form_textarea()` - Text area
- `interface_form_checkbox()` - Checkbox
- `interface_form_radio()` - Radio button
- `interface_form_file()` - File upload

### Tables
- `interface_tabela()` - Data table
- `interface_tabela_linha()` - Table row
- `interface_tabela_celula()` - Table cell
- `interface_tabela_header()` - Table header
- `interface_tabela_paginacao()` - Pagination controls

### Visual Components
- `interface_card()` - Card/panel
- `interface_modal()` - Modal window
- `interface_alerta()` - Alert message
- `interface_badge()` - Badge/label
- `interface_botao()` - Styled button
- `interface_tabs()` - Tabs
- `interface_accordion()` - Accordion/expandable

### Data Widgets
- `interface_grafico()` - Chart (Chart.js)
- `interface_estatistica()` - Statistic card
- `interface_progresso()` - Progress bar
- `interface_timeline()` - Timeline

## Usage Examples

### Table with Pagination

```php
$data = banco_select(Array(
    'campos' => Array('id', 'name', 'email', 'status'),
    'tabela' => 'users',
    'extra' => "LIMIT 20"
));

echo interface_tabela(Array(
    'colunas' => Array('ID', 'Name', 'Email', 'Status'),
    'dados' => $data,
    'acoes' => Array('edit', 'delete'),
    'paginacao' => true
));
```

### Statistic Card

```php
echo interface_card(Array(
    'titulo' => 'Sales Today',
    'valor' => '$ 12,500',
    'icone' => 'fa-shopping-cart',
    'cor' => 'success',
    'variacao' => '+15%'
));
```

### Confirmation Modal

```php
echo interface_modal(Array(
    'id' => 'modal-confirm',
    'titulo' => 'Confirm Deletion',
    'conteudo' => 'Do you really want to delete this item?',
    'botoes' => Array(
        Array('texto' => 'Cancel', 'classe' => 'btn-secondary'),
        Array('texto' => 'Delete', 'classe' => 'btn-danger', 'acao' => 'confirmDeletion()')
    )
));
```

### Complete Form

```php
echo interface_form_input(Array(
    'nome' => 'fullname',
    'label' => 'Full Name',
    'obrigatorio' => true,
    'placeholder' => 'Enter your name'
));

echo interface_form_select(Array(
    'nome' => 'state',
    'label' => 'State',
    'opcoes' => Array('NY' => 'New York', 'CA' => 'California'),
    'obrigatorio' => true
));

echo interface_form_textarea(Array(
    'nome' => 'notes',
    'label' => 'Notes',
    'linhas' => 5
));

echo interface_botao(Array(
    'texto' => 'Save',
    'tipo' => 'submit',
    'classe' => 'btn-primary'
));
```

## Patterns

### Responsiveness
All components are responsive and adapt to mobile devices.

### Accessibility
Components include ARIA attributes for accessibility.

### Themes
Support custom themes via CSS variables.

---

## See Also

- [LIBRARY-HTML.md](./LIBRARY-HTML.md) - HTML Generation
- [LIBRARY-FORM.md](./LIBRARY-FORM.md) - Validation

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
