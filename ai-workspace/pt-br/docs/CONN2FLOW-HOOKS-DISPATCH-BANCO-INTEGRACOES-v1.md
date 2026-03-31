# Conn2Flow Core — Hooks Dispatch Points, banco_select Fix e Integrações v1 (Março 2026)

## Visão Geral

Este documento registra as modificações feitas no repositório core `conn2flow` (branch `main`) durante a implementação do sistema de hooks para projetos, correção do `banco_select` e integrações necessárias para suportar o sistema multi-usuário do `conn2flow-site`.

**Total**: 21 arquivos modificados em ~20 commits (16/03 a 31/03/2026).

**Documentação relacionada**: Para detalhes completos do sistema de hooks, consulte `CONN2FLOW-HOOKS.md`.

---

## 1. Biblioteca `hooks.php` — HookManager (Novo)

**Arquivo**: `gestor/bibliotecas/hooks.php`

Implementação completa do `HookManager` — classe singleton que gerencia o carregamento e execução de hooks registrados no banco de dados.

### Funcionalidades Implementadas

- **Singleton pattern**: `HookManager::getInstance()` garante uma única instância por requisição
- **Lazy loading**: Hooks só são carregados do banco quando `doAction()` ou `applyFilters()` são chamados pela primeira vez para um namespace+evento específico
- **Controller auto-include**: O arquivo PHP do controller é automaticamente incluído (via `require_once`) antes da primeira execução do callback
- **`ReflectionFunction` para padding de argumentos**: Callbacks com menos parâmetros que os argumentos passados recebem apenas os que declaram. Callbacks com mais parâmetros obrigatórios recebem `null` nos argumentos ausentes. Isso permite que hooks antigos não quebrem quando novos argumentos são adicionados ao evento.
- **Logs de erro**: Falhas de execução logadas em `logs/hooks-errors.log`
- **4 funções globais**: `hook_do_action()`, `hook_apply_filters()`, `hook_has_actions()`, `hook_has_filters()`

### Detalhe: ReflectionFunction Argument Padding

Problema encontrado: um callback declarado como `function meu_hook()` (sem parâmetros) era chamado com `hook_do_action('ns', 'evento', $id, $dados)`. PHP gerava warning: `Too few/many arguments`.

Solução:
```php
$reflection = new \ReflectionFunction($callback);
$requiredParams = $reflection->getNumberOfRequiredParameters();
$totalParams = $reflection->getNumberOfParameters();

// Se args passados > params declarados → trunca
$callArgs = array_slice($args, 0, max($totalParams, 1));

// Se args passados < params obrigatórios → pad com null
while (count($callArgs) < $requiredParams) {
    $callArgs[] = null;
}

call_user_func_array($callback, $callArgs);
```

---

## 2. Biblioteca `interface.php` — Hook Dispatch Points (Modificado)

**Arquivo**: `gestor/bibliotecas/interface.php`

Adição de pontos de dispatch de hooks no ciclo de vida da interface de módulos. A `interface.php` é a biblioteca central que processa todas as operações CRUD de todos os módulos do sistema.

### 5 Tipos de Dispatch Points Adicionados

| Evento | Quando Dispara | Argumentos |
|--------|----------------|------------|
| `{opcao}.pre-banco` | Antes do INSERT/UPDATE/DELETE | — |
| `{opcao}.banco` | Após operação de banco bem-sucedida | `$id`, `$dados[]` |
| `{opcao}.parametros` | Antes de renderizar a página (GET) | — |
| `{opcao}.pagina` | Após renderizar a página (GET) | — |
| `{opcao}.where` | Filter: permite modificar cláusulas WHERE de listagens | `$where` → retorna `$where` modificado |

Onde `{opcao}` é a operação atual: `adicionar`, `editar`, `excluir`, `clonar`, `listar`, `status`.

### Exemplo de Dispatch no Código

```php
// Antes do INSERT:
hook_do_action($modulo_id, $opcao . '.pre-banco');

// Após INSERT bem-sucedido:
hook_do_action($modulo_id, $opcao . '.banco', $id, $dados);

// Antes de renderizar:
hook_do_action($modulo_id, $opcao . '.parametros');

// Filter WHERE para listagens:
$where = hook_apply_filters($modulo_id, $opcao . '.where', $where);
```

---

## 3. Controller `atualizacoes-hooks.php` (Novo)

**Arquivo**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Função `atualizacoes_hooks_sincronizar($opcoes = [])` que sincroniza hooks do JSON para o banco de dados.

### Fluxo de Sincronização

1. **Lê JSONs fonte**: `modulos/*/module.json` (hooks de módulo) + `project/hooks/hooks.json` (hooks de projeto)
2. **Para cada hook definido**:
   - Verifica se já existe no banco (por `namespace + evento + callback + modulo/projeto`)
   - Se novo → INSERT
   - Se alterado (prioridade, habilitado) → UPDATE
   - Se removido do JSON → DELETE do banco
3. **Opção `apenas_projeto`**: Quando `$opcoes['apenas_projeto'] === true`, sincroniza apenas hooks de `project/hooks/hooks.json` (usado no deploy de projeto para não afetar hooks de módulos)

### Integração no Deploy

**Arquivo modificado**: `gestor/controladores/api/api.php`

Na rota de deploy de projeto (`/_api/project/update`), após extrair e aplicar os arquivos:

```php
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);
```

**Arquivo modificado**: `gestor/controladores/atualizacoes/atualizacoes-sistema.php`

No `hookAfterAll()` (executado após todas as atualizações do sistema):

```php
atualizacoes_hooks_sincronizar();  // sincroniza módulos + projeto
```

---

## 4. Migration `create_hooks_table` (Novo)

**Arquivo**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

Cria a tabela `hooks` com as colunas documentadas em `CONN2FLOW-HOOKS.md`:
- `id_hooks` (PK), `modulo`, `plugin`, `namespace`, `evento`, `callback`, `tipo`, `prioridade`, `habilitado`, `projeto`, `status`, `data_criacao`, `data_modificacao`
- Índice composto: `namespace + evento + habilitado` para otimizar lazy loading

---

## 5. Biblioteca `banco.php` — Fix `banco_select` (Modificado)

**Arquivo**: `gestor/bibliotecas/banco.php`

### Bug Corrigido

A função `banco_select()` aceitava apenas arrays para o parâmetro `$campos` (fields a selecionar). Quando um módulo passava uma string simples (ex: `banco_select('tabela', 'id, nome, status')`), a função falhava silenciosamente e retornava `SELECT * FROM tabela` em vez dos campos especificados.

### Causa

Verificação interna `if (is_array($campos))` impedia processamento de strings. A string era ignorada e o default `*` era usado.

### Correção

```php
// Antes:
if (is_array($campos)) {
    $select = implode(', ', $campos);
}

// Depois:
if (is_array($campos)) {
    $select = implode(', ', $campos);
} elseif (is_string($campos) && !empty($campos)) {
    $select = $campos;
}
```

---

## 6. Módulo `admin-paginas` — Dispatch Points (Modificado)

**Arquivo**: `gestor/modulos/admin-paginas/admin-paginas.php`

Adição de chamadas `hook_do_action()` e `hook_apply_filters()` nos pontos estratégicos do CRUD de páginas, permitindo que hooks de projeto interceptem operações de criação, edição e listagem de páginas.

---

## 7. Módulo `perfil-usuario` — Display Modes (Modificado)

**Arquivo**: `gestor/modulos/perfil-usuario/perfil-usuario.php`

Modificações para suportar exibição de operações de módulos vinculadas ao perfil, incluindo as novas operações de acesso completo/restrito criadas no conn2flow-site.

---

## 8. Biblioteca `html-editor.php` — Hooks para Filtros (Modificado, Unstaged)

**Arquivo**: `gestor/bibliotecas/html-editor.php`

Adição de dispatch points para filtrar listas de templates e prompts IA no editor HTML:

```php
// Filtra templates disponíveis no selector do editor
$templates = hook_apply_filters('editor-html', 'templates.listar', $templates);

// Filtra prompts IA disponíveis no selector do editor
$prompts = hook_apply_filters('editor-html', 'prompts.listar', $prompts);
```

Isso permite que hooks de projeto restrinjam quais templates e prompts são visíveis para cada usuário.

---

## 9. Biblioteca `ia.php` — Hooks para Prompts (Modificado, Unstaged)

**Arquivo**: `gestor/bibliotecas/ia.php`

Adição de hook filter para filtrar prompts IA quando carregados para uso por módulos de IA:

```php
$prompts = hook_apply_filters('ia', 'prompts.carregar', $prompts);
```

---

## 10. Módulo `admin-prompts-ia` — Isolamento (Modificado, Unstaged)

**Arquivo**: `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

Adição de dispatch points de hooks para suportar isolamento multi-usuário de prompts IA (listar, adicionar, editar).

---

## Resumo de Arquivos Modificados no Core

| Arquivo | Tipo | Status |
|---------|------|--------|
| `gestor/bibliotecas/hooks.php` | Criado | Commitado |
| `gestor/bibliotecas/interface.php` | Modificado | Commitado |
| `gestor/bibliotecas/banco.php` | Modificado | Commitado |
| `gestor/bibliotecas/html-editor.php` | Modificado | Unstaged |
| `gestor/bibliotecas/ia.php` | Modificado | Unstaged |
| `gestor/controladores/api/api.php` | Modificado | Commitado |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Criado | Commitado |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Modificado | Commitado |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Criado | Commitado |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Modificado | Commitado |
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | Modificado | Commitado |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modificado | Unstaged |

**Nota**: Arquivos marcados como "Unstaged" foram modificados mas ainda não commitados no momento da documentação. Serão incluídos no próximo commit.
