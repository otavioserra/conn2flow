# Hook Apply Filters para Multi-Usuário - Antigo 1 (Março 2026)

## Adição de hook_apply_filters em html-editor.php, ia.php e id_usuarios em admin-prompts-ia.php

## Contexto e Objetivos

Esta sessão de desenvolvimento fez parte de uma implementação maior do sistema Multi-Usuários Hooks v2.0 no projeto Conn2Flow Site. As modificações no repositório core (`conn2flow`) foram necessárias para adicionar **pontos de extensão** (filter hooks) em duas bibliotecas centrais, permitindo que projetos downstream injetem lógica de filtragem sem modificar código core.

O trabalho no repositório core consistiu em 3 modificações cirúrgicas.

---

## Escopo Detalhado Realizado

### Modificação 1: html-editor.php — Filter Hook para Templates

**Arquivo:** `gestor/bibliotecas/html-editor.php`

**Problema:** A query de carregamento de templates no html-editor era fixa — não havia como um projeto filtrar quais templates são visíveis para um usuário.

**Solução:** Extraiu-se a construção do WHERE para uma variável dedicada e inseriu-se `hook_apply_filters()` antes da execução da query:

```php
$where_templates = "WHERE status = 'A' AND framework_css = '...' AND language = '...' AND target = '...'";

// Hook: permite filtrar o WHERE de templates (ex: multi-usuário)
$where_templates = hook_apply_filters('html-editor', 'templates.load.where', $where_templates);

$retorno_bd = banco_select([...]);
```

**Impacto:** Nenhum para projetos que não registram filter hooks — `hook_apply_filters()` retorna o valor original se não há callback registrado.

---

### Modificação 2: ia.php — Filter Hook para Prompts

**Arquivo:** `gestor/bibliotecas/ia.php`

**Problema:** A query de carregamento de prompts de IA era fixa — mesma situação da html-editor.php.

**Solução:** Mesmo padrão — variável `$where_prompts` + `hook_apply_filters()`:

```php
$where_prompts = "WHERE alvo = '...' AND status = 'A' AND language = '...'";

// Hook: permite filtrar o WHERE de prompts (ex: multi-usuário)
$where_prompts = hook_apply_filters('ia', 'prompts.load.where', $where_prompts);

$prompts = banco_select([...]);
```

---

### Modificação 3: admin-prompts-ia.php — Campo id_usuarios no INSERT

**Arquivo:** `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

**Problema:** Ao criar um novo prompt, o campo `id_usuarios` não era preenchido, impossibilitando o rastreamento de propriedade por usuário.

**Solução:** Adicionada uma linha ao array de campos do INSERT na função `admin_prompts_ia_adicionar()`:

```php
$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios'];
$campos[] = Array($campo_nome, $campo_valor, $campo_sem_aspas_simples);
```

**Pré-requisito:** A coluna `id_usuarios` deve existir na tabela `prompts_ia` (migration criada no repositório conn2flow-site).

---

## Arquivos Modificados

| Arquivo | Mudança |
|---------|---------|
| `gestor/bibliotecas/html-editor.php` | `hook_apply_filters('html-editor', 'templates.load.where', $where_templates)` |
| `gestor/bibliotecas/ia.php` | `hook_apply_filters('ia', 'prompts.load.where', $where_prompts)` |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | `id_usuarios` adicionado ao INSERT |

---

## Decisão de Design

Os filter hooks foram posicionados nas **bibliotecas core** (não nos módulos) porque templates e prompts são carregados em **todos os contextos** da aplicação — não apenas nos módulos admin. Um usuário restrito não deve ver templates/prompts de outros em nenhum lugar.

---

## Estado ao Final da Sessão

- ✅ `html-editor.php` com hook_apply_filters
- ✅ `ia.php` com hook_apply_filters
- ✅ `admin-prompts-ia.php` com id_usuarios no INSERT
- ⬜ Commit/Push pendente

_Sessão Detalhada - Referência para Agente Futuro (Hook Apply Filters)_
