# Gestor Desenvolvimento - Antigo 15

## Sistema de Hooks: Dispatch Points, HookManager, banco_select Fix e Integrações Multi-Usuário

## Contexto e Objetivos

Esta sessão de desenvolvimento implementou no core do Conn2Flow (`conn2flow`) todas as peças de infraestrutura necessárias para suportar o sistema de hooks de projeto e multi-usuário do `conn2flow-site`. O trabalho foi executado em paralelo com implementações extensas no conn2flow-site (Catálogo 3D v1.7.14/v1.7.15, módulo Arquivos, Social Networks v2.0) ao longo de ~2 semanas (16/03 a 31/03/2026).

**21 arquivos modificados** no core, distribuídos em ~20 commits.

### Objetivos

1. Criar a biblioteca `hooks.php` com o `HookManager` singleton
2. Adicionar dispatch points na `interface.php` para todos os eventos CRUD
3. Criar controller de sincronização de hooks (JSON → banco)
4. Criar migration da tabela `hooks`
5. Corrigir bug no `banco_select` que ignorava campos string
6. Integrar sincronização de hooks no deploy de projetos e atualizações
7. Adicionar hooks de filtro no `html-editor.php` e `ia.php`

---

## Implementações Detalhadas

### 1. Criação do `hooks.php` — HookManager

**Arquivo criado**: `gestor/bibliotecas/hooks.php`

O `HookManager` é uma classe singleton que gerencia todo o lifecycle dos hooks: carregamento do banco, resolução de controllers, e execução de callbacks.

**Lazy Loading**: A primeira chamada a `hook_do_action('admin-paginas', 'adicionar.banco')` dispara uma query ao banco buscando todos os hooks registrados para `namespace='admin-paginas' AND evento='adicionar.banco' AND habilitado=1`. O resultado é cacheado em memória — chamadas subsequentes para o mesmo namespace+evento não fazem nova query.

**Auto-Include de Controllers**: Quando o hook é carregado, o `HookManager` faz `require_once` do arquivo controller mapeado no registro do banco. Isso garante que a função callback estará disponível antes da execução.

**ReflectionFunction para Padding**: Problema encontrado durante desenvolvimento: callbacks declarados sem parâmetros (`function meu_hook()`) eram chamados com argumentos (`hook_do_action('ns', 'evt', $id, $dados)`), gerando warnings do PHP. A solução usa `ReflectionFunction` para detectar quantos parâmetros o callback declara e ajustar a lista de argumentos antes de `call_user_func_array()`.

```php
$reflection = new \ReflectionFunction($callback);
$requiredParams = $reflection->getNumberOfRequiredParameters();
$totalParams = $reflection->getNumberOfParameters();
$callArgs = array_slice($args, 0, max($totalParams, 1));
while (count($callArgs) < $requiredParams) {
    $callArgs[] = null;
}
call_user_func_array($callback, $callArgs);
```

**4 Funções Globais Expostas**:
- `hook_do_action(string $namespace, string $evento, mixed ...$args): void`
- `hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed`
- `hook_has_actions(string $namespace, string $evento): bool`
- `hook_has_filters(string $namespace, string $evento): bool`

---

### 2. Dispatch Points na `interface.php`

**Arquivo modificado**: `gestor/bibliotecas/interface.php`

A `interface.php` é a biblioteca que processa TODAS as operações CRUD de todos os módulos do sistema. Cada operação (adicionar, editar, excluir, clonar, listar, status) já seguia um fluxo interno padronizado. Adicionei chamadas de hooks em 5 pontos desse fluxo:

| Ponto | Tipo | Descrição |
|-------|------|-----------|
| `{opcao}.pre-banco` | Action | Antes da operação de banco (INSERT/UPDATE/DELETE) |
| `{opcao}.banco` | Action | Após operação de banco bem-sucedida (com $id e $dados) |
| `{opcao}.parametros` | Action | Antes de renderizar a página (GET request) |
| `{opcao}.pagina` | Action | Após renderizar a página (GET request) |
| `{opcao}.where` | Filter | Permite modificar cláusulas WHERE de listagens |

O filter `.where` é especialmente importante para o multi-usuário — permite injetar `AND id_usuarios = ?` nas queries de listagem de qualquer módulo sem modificar o código do módulo.

---

### 3. Controller `atualizacoes-hooks.php`

**Arquivo criado**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Implementa `atualizacoes_hooks_sincronizar($opcoes = [])`:

1. Itera por todos os módulos em `gestor/modulos/` lendo `*.json` → seção `hooks`
2. Lê `project/hooks/hooks.json` (hooks de projeto)
3. Para cada definição de hook:
   - Constrói registro com `namespace`, `evento`, `callback`, `tipo`, `prioridade`, `habilitado`, `modulo/projeto`
   - Verifica existência no banco por chave composta
   - INSERT se novo, UPDATE se alterado, mantém se idêntico
4. Remove do banco hooks que não existem mais nos JSONs fonte
5. Opção `['apenas_projeto' => true]` restringe sincronização para apenas hooks de projeto (usado no deploy)

**Integração**:
- `api.php` → Na rota `/_api/project/update` chama `atualizacoes_hooks_sincronizar(['apenas_projeto' => true])`
- `atualizacoes-sistema.php` → No `hookAfterAll()` chama `atualizacoes_hooks_sincronizar()` (todos os hooks)

---

### 4. Migration `create_hooks_table`

**Arquivo criado**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

Tabela `hooks` com:
- `id_hooks` INT PK AUTO_INCREMENT
- `modulo` VARCHAR(255) — ID do módulo (NULL = hook de projeto)
- `plugin` VARCHAR(255) — ID do plugin (se aplicável)
- `namespace` VARCHAR(255) — Namespace-alvo
- `evento` VARCHAR(255) — Evento específico
- `callback` VARCHAR(500) — Nome da função PHP
- `tipo` VARCHAR(10) — `action` ou `filter`
- `prioridade` SMALLINT DEFAULT 10 — Menor = executa primeiro
- `habilitado` TINYINT DEFAULT 1
- `projeto` TINYINT DEFAULT 0 — 1 se veio de `project/hooks/hooks.json`
- `status` CHAR(1) DEFAULT 'A'
- `data_criacao`, `data_modificacao` DATETIME

Índice composto `idx_hooks_lookup` em `(namespace, evento, habilitado)` para otimizar lazy loading.

---

### 5. Fix `banco_select` — Campos String

**Arquivo modificado**: `gestor/bibliotecas/banco.php`

**Bug**: `banco_select('tabela', 'id, nome, status')` ignorava o segundo parâmetro e retornava `SELECT * FROM tabela`. A verificação interna era `if (is_array($campos))`, ignorando strings.

**Fix**: Adicionado `elseif (is_string($campos) && !empty($campos))` para aceitar strings como lista de campos.

Este bug foi encontrado quando o módulo `arquivos.hooks.php` tentava fazer select com campos específicos para a API de listagem e recebia todos os campos da tabela.

---

### 6. Dispatch Points em `admin-paginas.php`

**Arquivo modificado**: `gestor/modulos/admin-paginas/admin-paginas.php`

Adição de chamadas `hook_do_action()` e `hook_apply_filters()` nos pontos CRUD do módulo de páginas. Este módulo é o primeiro a ser usado diretamente pelos hooks de multi-usuário do conn2flow-site, então serviu como referência de implementação.

---

### 7. Modificações em `perfil-usuario.php`

**Arquivo modificado**: `gestor/modulos/perfil-usuario/perfil-usuario.php`

Ajustes para exibir as novas operações de módulos (acesso-completo, acesso-restrito) na interface de edição de perfis. As operações são carregadas de `modulos_operacoes` e exibidas como checkboxes vinculados ao perfil.

---

### 8. Hooks em `html-editor.php` (Unstaged)

**Arquivo modificado**: `gestor/bibliotecas/html-editor.php`

Dispatch points para filtrar listas de templates e prompts no editor HTML:
```php
$templates = hook_apply_filters('editor-html', 'templates.listar', $templates);
$prompts = hook_apply_filters('editor-html', 'prompts.listar', $prompts);
```

Permite que hooks de projeto do conn2flow-site filtrem quais templates/prompts são visíveis para cada perfil de usuário.

---

### 9. Hooks em `ia.php` (Unstaged)

**Arquivo modificado**: `gestor/bibliotecas/ia.php`

Filter para prompts IA quando carregados por módulos de IA:
```php
$prompts = hook_apply_filters('ia', 'prompts.carregar', $prompts);
```

---

### 10. Dispatch Points em `admin-prompts-ia.php` (Unstaged)

**Arquivo modificado**: `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php`

Hooks de isolamento para o módulo de prompts IA (listar, adicionar, editar), análogo ao `admin-paginas`.

---

## Decisões Técnicas

### Lazy Loading vs Eager Loading

Optou-se por lazy loading (query ao banco somente quando o hook é efetivamente chamado) em vez de carregar todos os hooks no início da requisição. Motivo: a maioria das requisições só dispara 2-3 hooks diferentes, e carregar todos os ~40+ hooks registrados seria desperdício.

### ReflectionFunction vs Type Checking

Para o padding de argumentos, `ReflectionFunction` foi escolhido em vez de verificar tipos manualmente. Embora reflection tenha overhead, ela é chamada apenas uma vez por callback (resultado cacheado internamente pelo HookManager).

### Controller Auto-Include

Controllers são incluídos via `require_once` e não `include_once` para garantir que erros de sintaxe ou arquivo não encontrado gerem exceções tratáveis. O caminho do controller é relativo ao diretório do módulo ou `project/hooks/controllers/`.

### Separação core/projeto

O core (`hooks.php`, `interface.php`) apenas dispara eventos e aplica filtros. A lógica de negócio (isolamento multi-usuário, limites de plano) fica inteiramente nos hooks de projeto (`conn2flow-site/gestor/project/hooks/`). Isso garante que projetos single-tenant não precisam de nenhuma modificação para funcionar.

---

## Bugs Encontrados e Resolvidos

| # | Bug | Causa | Solução |
|---|-----|-------|---------|
| 1 | `banco_select` ignora campos string | Verificação `is_array()` apenas | Adicionado `is_string()` branch |
| 2 | Callbacks sem params recebem args | PHP warning em `call_user_func_array` | `ReflectionFunction` padding |
| 3 | Módulo `3d-catalog` (inicia com dígito) | Hook loader falhava no `require` | Fallback com path completo |

---

## Arquivos Criados/Modificados (Inventário Completo)

| Arquivo | Tipo | Status |
|---------|------|--------|
| `gestor/bibliotecas/hooks.php` | Criado | ✅ Commitado |
| `gestor/bibliotecas/interface.php` | Modificado | ✅ Commitado |
| `gestor/bibliotecas/banco.php` | Modificado | ✅ Commitado |
| `gestor/bibliotecas/html-editor.php` | Modificado | ⏳ Unstaged |
| `gestor/bibliotecas/ia.php` | Modificado | ⏳ Unstaged |
| `gestor/controladores/api/api.php` | Modificado | ✅ Commitado |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Criado | ✅ Commitado |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Modificado | ✅ Commitado |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Criado | ✅ Commitado |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Modificado | ✅ Commitado |
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | Modificado | ✅ Commitado |
| `gestor/modulos/admin-prompts-ia/admin-prompts-ia.php` | Modificado | ⏳ Unstaged |
