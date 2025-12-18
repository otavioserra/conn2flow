# CONN2FLOW - Preview and Modals System: Lessons Learned

## 📋 Context

During the implementation of the TailwindCSS preview system in administrative modules, we discovered critical operating patterns that must be followed to ensure correct operation.

**Affected Modules:** `admin-layouts`, `admin-componentes`, `admin-paginas`  
**Version:** v1.15.0+  
**Date:** August 2025

---

## 🚨 Discovered Problems

### 1. Critical Dependency Order
**❌ Problem:** CodeMirror loaded before modals breaks DOM element initialization.

**Symptoms:**
- Preview modal does not appear
- Elements are not found by JavaScript
- Console errors about undefined elements

**Root Cause:** Scripts try to access DOM elements before they exist in HTML.

### 2. Incorrect Use of gestor_componente()
**❌ Problem:** Attempt to use non-existent 'variaveis' parameter in the function.

**Problematic Code:**
```php
// ❌ ERROR: 'variaveis' parameter does not exist
$modal = gestor_componente(Array(
    'id' => 'modal-preview',
    'modulo' => 'admin-layouts',
    'variaveis' => Array('titulo' => 'Preview') // ❌ DOES NOT WORK
));
```

**Discovery:** `gestor_componente()` returns raw HTML that needs `modelo_var_troca()` to replace placeholders.

### 3. Incomplete Preview System
**❌ Problem:** Preview system did not consider `framework_css` field.

**Impact:**
- Preview always used FomanticUI even with TailwindCSS selected
- Inconsistency between development and final result
- Forms without mandatory framework validation

---

## ✅ Implemented Solutions

### 1. Correct Dependency Order
```php
// ✅ SOLUTION: Modal BEFORE CodeMirror
$_GESTOR['dependencias']['assets']['final'] = Array(
    // 1. Modal FIRST (DOM elements must exist)
    'components/modal-preview-' . $_GESTOR['modulo-id'] . '.php',
    
    // 2. CodeMirror AFTER (can access existing elements)
    'assets/codemirror/lib/codemirror.js',
    'assets/codemirror/mode/xml/xml.js',
    'assets/codemirror/mode/css/css.js',
    'assets/codemirror/mode/javascript/javascript.js',
    'assets/codemirror/mode/htmlmixed/htmlmixed.js',
    
    // 3. Custom scripts LAST
    'assets/modulos/' . $_GESTOR['modulo-id'] . '/script.js'
);
```

### 2. Correct Pattern gestor_componente()
```php
// ✅ CORRECT PATTERN
// Step 1: Get component HTML (WITHOUT 'variaveis' parameter)
$modal = gestor_componente('modal-preview');

// Step 2: Individual replacements with modelo_var_troca
$modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(
    'modulo' => $_GESTOR['modulo-id'],
    'id' => 'modal-preview-title'
)));
$modal = modelo_var_troca($modal, '#modal-id#', 'modal-preview-' . $_GESTOR['modulo-id']);
$modal = modelo_var_troca($modal, '#desktop#', gestor_variaveis(Array(
    'modulo' => $_GESTOR['modulo-id'],
    'id' => 'modal-desktop-preview'
)));

// Step 3: Display component
echo $modal;
```

### 3. Complete Preview System
**Preview Endpoint (preview.php):**
```php
<?php
require_once '../../../gestor.php';

$nome = $_POST['nome'] ?? '';
$html = $_POST['html'] ?? '';
$css = $_POST['css'] ?? '';
$framework_css = $_POST['framework_css'] ?? 'fomantic';

// ✅ Respect selected framework
$cssFramework = '';
if ($framework_css === 'tailwindcss') {
    $cssFramework = '<script src="https://cdn.tailwindcss.com"></script>';
} else {
    $cssFramework = '<link rel="stylesheet" href="' . $_GESTOR['url-raiz'] . 'assets/fomantic/semantic.min.css">';
}

$preview = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Preview: $nome</title>
    $cssFramework
    <style>$css</style>
</head>
<body>
    $html
</body>
</html>";

echo $preview;
?>
```

**Preview JavaScript:**
```javascript
function previsualizar() {
    // FormData automatically captures all fields, including framework_css
    var formData = new FormData($('#form-' + moduloAtual)[0]);
    
    $.ajax({
        url: 'controladores/modulos/' + moduloAtual + '/preview.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#preview-frame').attr('src', 'data:text/html;charset=utf-8,' + encodeURIComponent(response));
            $('#modal-preview-' + moduloAtual).modal('show');
        },
        error: function() {
            alert('Error generating preview');
        }
    });
}
```

### 4. CSS Framework Validation
```php
// ✅ Mandatory validation in visual modules
Array(
    'regra' => 'selecao-obrigatorio',
    'campo' => 'framework_css',
    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
    'identificador' => 'framework_css',
)
```

### 5. Custom Buttons with Callback
```php
'botoes_rodape' => [
    'previsualizar' => [
        'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-button-preview')),
        'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-preview')),
        'icon' => 'plus circle',
        'cor' => 'positive',
        'callback' => 'previsualizar', // JavaScript function
    ],
],
```

---

## 🔧 Implementation in Both Functions

**Discovered Pattern:** Modal must be in BOTH functions (add AND edit).

### Function adicionar()
```php
function modulo_adicionar() {
    // ... add logic
    
    // Modal BEFORE CodeMirror
    $_GESTOR['dependencias']['assets']['final'] = Array(/* correct order */);
    
    // Component modal
    $modal = gestor_componente('modal-preview');
    $modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(/*...*/)));
    echo $modal;
    
    // ... rest of function
}
```

### Function editar($id)
```php
function modulo_editar($id) {
    // ... edit logic
    
    // SAME pattern as add function
    $_GESTOR['dependencias']['assets']['final'] = Array(/* same order */);
    
    // SAME component modal
    $modal = gestor_componente('modal-preview');
    $modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(/*...*/)));
    echo $modal;
    
    // ... rest of function
}
```

---

## 📊 Reference Patterns

### Module admin-paginas (Correct Reference)
- ✅ Modal included BEFORE CodeMirror
- ✅ `gestor_componente()` used correctly
- ✅ `modelo_var_troca()` applied individually
- ✅ System functional in both functions

### Corrected Modules
- ✅ **admin-layouts:** Pattern applied and tested
- ✅ **admin-componentes:** Pattern applied and tested

---

## 🚨 Common Pitfalls

### ❌ Never Do
```php
// ERROR 1: 'variaveis' parameter does not exist
gestor_componente(Array('variaveis' => Array(/*...*/)));

// ERROR 2: CodeMirror before modal
$_GESTOR['dependencias']['assets']['final'] = Array(
    'assets/codemirror/lib/codemirror.js', // ❌ FIRST
    'components/modal-preview.php'          // ❌ AFTER
);

// ERROR 3: use non-existent function
substitua_variaveis($component, Array(/*...*/)); // ❌ DOES NOT EXIST
```

### ✅ Always Do
```php
// ✅ gestor_componente WITHOUT 'variaveis'
$component = gestor_componente('nome-componente');

// ✅ modelo_var_troca individual
$component = modelo_var_troca($component, '#placeholder#', 'valor');

// ✅ Modal BEFORE any external library
// ✅ Implement in BOTH functions (add AND edit)
// ✅ Validate framework_css as mandatory
```

---

## 🎯 Validation Checklist

### For New Admin Modules
- [ ] Modal included BEFORE CodeMirror
- [ ] `gestor_componente()` used without 'variaveis'
- [ ] `modelo_var_troca()` applied individually
- [ ] `framework_css` field mandatory
- [ ] `preview.php` endpoint functional
- [ ] `previsualizar()` JavaScript implemented
- [ ] Pattern applied in BOTH functions

### For Existing Modules Audit
```bash
# Check dependency order
grep -A10 -B10 "modal.*codemirror\|codemirror.*modal" gestor/modulos/admin-*/admin-*.php

# Search incorrect use of 'variaveis'
grep -r "gestor_componente.*variaveis" gestor/modulos/admin-*/

# Check preview endpoints
find gestor/controladores/modulos/admin-*/ -name "preview.php"
```

---

## 🏆 Results

**Before Correction:**
- ❌ Preview system did not work
- ❌ Modals did not appear
- ❌ CSS Framework ignored
- ❌ JavaScript errors in console

**After Correction:**
- ✅ Preview system functional
- ✅ Modals appear correctly
- ✅ CSS Framework respected
- ✅ Zero JavaScript errors
- ✅ Documented pattern for future modules

---

## 📚 Related Documentation

- **Updated Template:** `ai-workspace/templates/modulos/modulo_id.md`
- **CSS Framework Support:** `CONN2FLOW-FRAMEWORK-CSS.md`
- **Multilingual Migration:** `CONN2FLOW-ADAPTACAO-POS-INSTALACAO.md`

---

**Document created:** August 31, 2025  
**Author:** GitHub Copilot AI  
**Knowledge Base:** Practical implementation admin-layouts, admin-componentes, admin-paginas  
**Status:** ✅ Implemented and validated
