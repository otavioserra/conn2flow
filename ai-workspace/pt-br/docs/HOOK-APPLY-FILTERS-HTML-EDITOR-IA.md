# Hook Apply Filters — html-editor.php e ia.php

> **Versão:** 1.0.0 | **Data:** 31 de março de 2026  
> **Projeto:** Conn2Flow Core (conn2flow)

---

## Sumário

- [Visão Geral](#visão-geral)
- [html-editor.php — Filter de Templates](#html-editorphp--filter-de-templates)
- [ia.php — Filter de Prompts](#iaphp--filter-de-prompts)
- [admin-prompts-ia.php — Campo id_usuarios no INSERT](#admin-prompts-iaphp--campo-id_usuarios-no-insert)
- [Como Usar os Filters em Projetos](#como-usar-os-filters-em-projetos)
- [Arquivos Modificados](#arquivos-modificados)

---

## Visão Geral

Esta atualização adiciona pontos de extensão via `hook_apply_filters()` em duas bibliotecas core do Conn2Flow:

1. **`html-editor.php`** — permite filtrar o WHERE de carregamento de templates HTML
2. **`ia.php`** — permite filtrar o WHERE de carregamento de prompts de IA

Estes filters permitem que projetos (como o Conn2Flow Site) injetem restrições de multi-usuário, registros globais, ou qualquer outra lógica de filtragem sem modificar o código core.

Além disso, o módulo `admin-prompts-ia.php` foi atualizado para incluir o campo `id_usuarios` no INSERT de novos prompts, permitindo rastreamento de propriedade por usuário.

---

## html-editor.php — Filter de Templates

**Arquivo:** `gestor/bibliotecas/html-editor.php`

O carregamento de templates no editor HTML agora passa o WHERE por um filter hook antes de executar a query:

```php
// Build WHERE clause
$where_templates = "WHERE status = 'A' AND framework_css = '" . banco_escape_field($framework_css) . "' "
    . "AND language = '" . banco_escape_field($idioma) . "' "
    . "AND target = '" . banco_escape_field($alvo) . "'";

// Hook: permite filtrar o WHERE de templates (ex: multi-usuário)
$where_templates = hook_apply_filters('html-editor', 'templates.load.where', $where_templates);

$retorno_bd = banco_select([
    'tabela' => 'templates',
    'campos' => [...],
    'extra' => $where_templates
]);
```

### Parâmetros do Filter

| Parâmetro | Valor |
|-----------|-------|
| Namespace | `html-editor` |
| Hook ID | `templates.load.where` |
| Valor filtrado | String WHERE SQL |

### Exemplo de Callback

```php
// No hooks.json:
// "filters": { "html-editor": { "templates.load.where": "meu_callback" } }

function meu_callback($where) {
    // Adicionar filtro por usuário
    return $where . " AND id_usuarios = '5'";
}
```

---

## ia.php — Filter de Prompts

**Arquivo:** `gestor/bibliotecas/ia.php`

O carregamento de prompts na biblioteca de IA agora passa o WHERE por um filter hook:

```php
// Build WHERE clause for prompts
$where_prompts = "WHERE alvo = '" . banco_escape_field($alvo) . "' "
    . "AND status = 'A' AND language = '" . $_GESTOR['linguagem-codigo'] . "'";

// Hook: permite filtrar o WHERE de prompts (ex: multi-usuário)
$where_prompts = hook_apply_filters('ia', 'prompts.load.where', $where_prompts);

$prompts = banco_select(Array(
    'tabela' => 'prompts_ia',
    'campos' => Array('id', 'nome'),
    'extra' => $where_prompts
));
```

### Parâmetros do Filter

| Parâmetro | Valor |
|-----------|-------|
| Namespace | `ia` |
| Hook ID | `prompts.load.where` |
| Valor filtrado | String WHERE SQL |

### Exemplo de Callback

```php
// No hooks.json:
// "filters": { "ia": { "prompts.load.where": "meu_callback_prompts" } }

function meu_callback_prompts($where) {
    return $where . " AND id_usuarios = '5'";
}
```

---

## admin-prompts-ia.php — Campo id_usuarios no INSERT

**Arquivo:** `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

Na função `admin_prompts_ia_adicionar()`, o campo `id_usuarios` foi adicionado ao array de campos do INSERT:

```php
$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios'];
$campos[] = Array($campo_nome, $campo_valor, $campo_sem_aspas_simples);
```

Isso garante que novos prompts criados sejam vinculados ao usuário que os criou, permitindo:
- Filtragem multi-usuário via hooks
- Contagem de limites por usuário
- Registros globais baseados em `id_usuarios`

> **Nota:** A tabela `prompts_ia` precisa da coluna `id_usuarios` (adicionada via migration no projeto conn2flow-site). Projetos que não utilizam multi-usuário não são afetados, pois a coluna permite NULL e tem default `1`.

---

## Como Usar os Filters em Projetos

Para utilizar estes filters em um projeto Conn2Flow:

### 1. Registrar no hooks.json do projeto

```json
{
    "controllers": {
        "html-editor": "meu-controller.php",
        "ia": "meu-controller.php"
    },
    "filters": {
        "html-editor": {
            "templates.load.where": "nome_da_funcao_callback"
        },
        "ia": {
            "prompts.load.where": "nome_da_funcao_callback_prompts"
        }
    }
}
```

### 2. Implementar os callbacks no controller

```php
function nome_da_funcao_callback($where) {
    // Sua lógica de filtragem
    return $where . " AND sua_condicao";
}

function nome_da_funcao_callback_prompts($where) {
    return $where . " AND sua_condicao";
}
```

### 3. Importante

- O callback **deve** retornar a string WHERE (modificada ou não)
- Se não houver callback registrado, o `hook_apply_filters()` retorna o valor original sem alteração
- Múltiplos callbacks podem ser encadeados (cada um recebe o resultado do anterior)

---

## Arquivos Modificados

| Arquivo | Tipo de Mudança | Descrição |
|---------|-----------------|-----------|
| `gestor/bibliotecas/html-editor.php` | Modificado | Adicionado `hook_apply_filters('html-editor', 'templates.load.where', $where)` |
| `gestor/bibliotecas/ia.php` | Modificado | Adicionado `hook_apply_filters('ia', 'prompts.load.where', $where)` |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modificado | Adicionado `id_usuarios` ao INSERT de novos prompts |
