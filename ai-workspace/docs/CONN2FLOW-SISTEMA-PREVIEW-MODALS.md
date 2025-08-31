# CONN2FLOW - Sistema de Preview e Modals: Lições Aprendidas

## 📋 Contexto

Durante implementação do sistema de preview TailwindCSS nos módulos administrativos, descobrimos padrões críticos de funcionamento que devem ser seguidos para garantir operação correta.

**Módulos Afetados:** `admin-layouts`, `admin-componentes`, `admin-paginas`  
**Versão:** v1.15.0+  
**Data:** Agosto 2025

---

## 🚨 Problemas Descobertos

### 1. Ordem Crítica de Dependências
**❌ Problema:** CodeMirror carregado antes dos modals quebra inicialização dos elementos DOM.

**Sintomas:**
- Modal de preview não aparece
- Elementos não são encontrados pelo JavaScript
- Console errors sobre elementos undefined

**Causa Raiz:** Scripts tentam acessar elementos DOM antes deles existirem no HTML.

### 2. Uso Incorreto de gestor_componente()
**❌ Problema:** Tentativa de usar parâmetro 'variaveis' inexistente na função.

**Código Problemático:**
```php
// ❌ ERRO: parâmetro 'variaveis' não existe
$modal = gestor_componente(Array(
    'id' => 'modal-preview',
    'modulo' => 'admin-layouts',
    'variaveis' => Array('titulo' => 'Preview') // ❌ NÃO FUNCIONA
));
```

**Descoberta:** `gestor_componente()` retorna HTML bruto que precisa de `modelo_var_troca()` para substituir placeholders.

### 3. Sistema de Preview Incompleto
**❌ Problema:** Sistema de preview não considerava campo `framework_css`.

**Impacto:**
- Preview sempre usava FomanticUI mesmo com TailwindCSS selecionado
- Inconsistência entre desenvolvimento e resultado final
- Formulários sem validação obrigatória do framework

---

## ✅ Soluções Implementadas

### 1. Ordem Correta de Dependências
```php
// ✅ SOLUÇÃO: Modal ANTES do CodeMirror
$_GESTOR['dependencias']['assets']['final'] = Array(
    // 1. Modal PRIMEIRO (elementos DOM devem existir)
    'components/modal-preview-' . $_GESTOR['modulo-id'] . '.php',
    
    // 2. CodeMirror DEPOIS (pode acessar elementos existentes)
    'assets/codemirror/lib/codemirror.js',
    'assets/codemirror/mode/xml/xml.js',
    'assets/codemirror/mode/css/css.js',
    'assets/codemirror/mode/javascript/javascript.js',
    'assets/codemirror/mode/htmlmixed/htmlmixed.js',
    
    // 3. Scripts customizados POR ÚLTIMO
    'assets/modulos/' . $_GESTOR['modulo-id'] . '/script.js'
);
```

### 2. Padrão Correto gestor_componente()
```php
// ✅ PADRÃO CORRETO
// Etapa 1: Obter HTML do componente (SEM parâmetro 'variaveis')
$modal = gestor_componente('modal-preview');

// Etapa 2: Substituições individuais com modelo_var_troca
$modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(
    'modulo' => $_GESTOR['modulo-id'],
    'id' => 'modal-preview-title'
)));
$modal = modelo_var_troca($modal, '#modal-id#', 'modal-preview-' . $_GESTOR['modulo-id']);
$modal = modelo_var_troca($modal, '#desktop#', gestor_variaveis(Array(
    'modulo' => $_GESTOR['modulo-id'],
    'id' => 'modal-desktop-preview'
)));

// Etapa 3: Exibir componente
echo $modal;
```

### 3. Sistema de Preview Completo
**Endpoint de Preview (preview.php):**
```php
<?php
require_once '../../../gestor.php';

$nome = $_POST['nome'] ?? '';
$html = $_POST['html'] ?? '';
$css = $_POST['css'] ?? '';
$framework_css = $_POST['framework_css'] ?? 'fomantic';

// ✅ Respeitar framework selecionado
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

**JavaScript de Preview:**
```javascript
function previsualizar() {
    // FormData captura automaticamente todos campos, incluindo framework_css
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
            alert('Erro ao gerar preview');
        }
    });
}
```

### 4. Validação de Framework CSS
```php
// ✅ Validação obrigatória em módulos visuais
Array(
    'regra' => 'selecao-obrigatorio',
    'campo' => 'framework_css',
    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
    'identificador' => 'framework_css',
)
```

### 5. Botões Personalizados com Callback
```php
'botoes_rodape' => [
    'previsualizar' => [
        'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-button-preview')),
        'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-preview')),
        'icon' => 'plus circle',
        'cor' => 'positive',
        'callback' => 'previsualizar', // função JavaScript
    ],
],
```

---

## 🔧 Implementação em Ambas Funções

**Padrão Descoberto:** Modal deve estar em AMBAS funções (adicionar E editar).

### Função adicionar()
```php
function modulo_adicionar() {
    // ... lógica de adicionar
    
    // Modal ANTES do CodeMirror
    $_GESTOR['dependencias']['assets']['final'] = Array(/* ordem correta */);
    
    // Componente modal
    $modal = gestor_componente('modal-preview');
    $modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(/*...*/)));
    echo $modal;
    
    // ... resto da função
}
```

### Função editar($id)
```php
function modulo_editar($id) {
    // ... lógica de editar
    
    // MESMO padrão da função adicionar
    $_GESTOR['dependencias']['assets']['final'] = Array(/* mesma ordem */);
    
    // MESMO componente modal
    $modal = gestor_componente('modal-preview');
    $modal = modelo_var_troca($modal, '#titulo#', gestor_variaveis(Array(/*...*/)));
    echo $modal;
    
    // ... resto da função
}
```

---

## 📊 Padrões de Referência

### Módulo admin-paginas (Referência Correta)
- ✅ Modal incluído ANTES do CodeMirror
- ✅ `gestor_componente()` usado corretamente
- ✅ `modelo_var_troca()` aplicado individualmente
- ✅ Sistema funcional em ambas funções

### Módulos Corrigidos
- ✅ **admin-layouts:** Padrão aplicado e testado
- ✅ **admin-componentes:** Padrão aplicado e testado

---

## 🚨 Armadilhas Comuns

### ❌ Nunca Fazer
```php
// ERRO 1: parâmetro 'variaveis' não existe
gestor_componente(Array('variaveis' => Array(/*...*/)));

// ERRO 2: CodeMirror antes do modal
$_GESTOR['dependencias']['assets']['final'] = Array(
    'assets/codemirror/lib/codemirror.js', // ❌ PRIMEIRO
    'components/modal-preview.php'          // ❌ DEPOIS
);

// ERRO 3: usar função inexistente
substitua_variaveis($component, Array(/*...*/)); // ❌ NÃO EXISTE
```

### ✅ Sempre Fazer
```php
// ✅ gestor_componente SEM 'variaveis'
$component = gestor_componente('nome-componente');

// ✅ modelo_var_troca individual
$component = modelo_var_troca($component, '#placeholder#', 'valor');

// ✅ Modal ANTES de qualquer biblioteca externa
// ✅ Implementar em AMBAS funções (adicionar E editar)
// ✅ Validar framework_css como obrigatório
```

---

## 🎯 Checklist de Validação

### Para Novos Módulos Admin
- [ ] Modal incluído ANTES do CodeMirror
- [ ] `gestor_componente()` usado sem 'variaveis'
- [ ] `modelo_var_troca()` aplicado individualmente
- [ ] Campo `framework_css` obrigatório
- [ ] Endpoint `preview.php` funcional
- [ ] JavaScript `previsualizar()` implementado
- [ ] Padrão aplicado em AMBAS funções

### Para Auditoria de Módulos Existentes
```bash
# Verificar ordem de dependências
grep -A10 -B10 "modal.*codemirror\|codemirror.*modal" gestor/modulos/admin-*/admin-*.php

# Buscar uso incorreto de 'variaveis'
grep -r "gestor_componente.*variaveis" gestor/modulos/admin-*/

# Verificar endpoints de preview
find gestor/controladores/modulos/admin-*/ -name "preview.php"
```

---

## 🏆 Resultados

**Antes da Correção:**
- ❌ Sistema de preview não funcionava
- ❌ Modals não apareciam
- ❌ Framework CSS ignorado
- ❌ Erros JavaScript no console

**Depois da Correção:**
- ✅ Sistema de preview funcional
- ✅ Modals aparecem corretamente
- ✅ Framework CSS respeitado
- ✅ Zero erros JavaScript
- ✅ Padrão documentado para futuros módulos

---

## 📚 Documentação Relacionada

- **Template Atualizado:** `ai-workspace/templates/modulos/modulo_id.md`
- **Suporte Framework CSS:** `CONN2FLOW-FRAMEWORK-CSS.md`
- **Migração Multilíngue:** `CONN2FLOW-ADAPTACAO-POS-INSTALACAO.md`

---

**Documento criado:** 31 de Agosto de 2025  
**Autor:** GitHub Copilot AI  
**Base de Conhecimento:** Implementação prática admin-layouts, admin-componentes, admin-paginas  
**Status:** ✅ Implementado e validado
