# Projeto: Sistema de Hooks do Conn2Flow

> **Documento de projeto** — descreve toda a arquitetura, decisões de design e lista de implementação antes do início do desenvolvimento.

---

## 1. Visão Geral

O objetivo é criar um **sistema de hooks estilo WordPress** para o Conn2Flow, permitindo que módulos interceptem e reajam a eventos de outros módulos (e do próprio gestor) sem modificar o código-fonte deles.

Casos de uso imediatos:
- `social-connections` captura `paginas.editar` → publica automaticamente nas redes sociais
- `social-connections` captura `paginas.adicionar` → publica post de lançamento
- `social-connections` captura `paginas.status` → notifica alteração de publicação
- Projeto-específico: lógica customizada via `project/hooks/hooks.json` sem tocar no repositório

---

## 2. Escopo dos Hooks

O sistema suporta quatro direções de comunicação:

| Tipo | De | Para | Descrição |
|---|---|---|---|
| Módulo → Módulo | `social-connections` | `paginas` | Um módulo escuta eventos de outro |
| Módulo → Global | Qualquer módulo | Sistema | Um módulo escuta eventos globais do gestor |
| Global → Módulo | Sistema | Qualquer módulo | O gestor dispara eventos que módulos podem capturar |
| Projeto → Qualquer | `project/hooks/hooks.json` | Qualquer | Lógica de projeto não versionada |

---

## 3. Estrutura de Arquivos

### 3.1 Arquivos do Repositório (`conn2flow`)

```
gestor/
  bibliotecas/
    hooks.php                            # [IMPLEMENTAR] Biblioteca HookManager completa
  db/
    migrations/
      20260217100000_add_hooks_to_modulos_table.php   # [JÁ EXISTE] coluna hooks em modulos
      XXXXXXXXXXXXXXXXX_create_hooks_table.php         # [CRIAR] tabela hooks principal
  gestor.php                             # [MODIFICAR] gestor_hooks() para carregar e registrar
```

### 3.2 Arquivos do Projeto (fora do repositório — em produção)

```
gestor/
  project/
    hooks/
      hooks.json                         # Registry de hooks globais/projeto (NÃO versionado)
      controllers/
        <arquivo>.php                    # Controllers PHP de hooks de projeto (NÃO versionados)
```

> **Nota:** A pasta `gestor/project/` já é excluída do repositório. Os arquivos dentro dela são gerados/mantidos em produção, não comitados.

### 3.3 Arquivos por Módulo (dentro do repositório do projeto `conn2flow-site`)

```
gestor/modulos/<modulo-id>/
  <modulo-id>.json                       # [MODIFICAR] adicionar campo "hooks"
  <modulo-id>.hooks.php                  # [CRIAR por módulo] controller de hooks
```

Exemplo: `social-connections`
```
gestor/modulos/social-connections/
  social-connections.json                # hooks: controllers, actions, filters
  social-connections.hooks.php           # [CRIAR] implementa as funções de hook
```

---

## 4. Estrutura JSON dos Hooks

### 4.1 No JSON de cada módulo (`<modulo>.json`)

O campo `"hooks"` já foi validado no `social-connections.json`. Estrutura padrão:

```json
"hooks": {
    "controllers": {
        "<namespace>": "<arquivo>.hooks.php"
    },
    "actions": {
        "<namespace>": {
            "<evento>": "<nome_da_funcao_php>"
        }
    },
    "filters": {
        "<namespace>": {
            "<evento>": "<nome_da_funcao_php>"
        }
    }
}
```

**Regras:**
- `controllers`: mapa de `namespace → arquivo PHP` contendo as funções callback
- `actions`: mapa de `namespace → evento → função` — executadas como side-effect (não alteram dados)
- `filters`: mapa de `namespace → evento → função` — recebem um valor, o transformam e retornam
- Uma mesma função pode aparecer em `actions` e `filters` diferentes

**Exemplo `social-connections.json` (já existente):**
```json
"hooks": {
    "controllers": {
        "paginas": "social-connections.hooks.php"
    },
    "actions": {
        "paginas": {
            "editar": "social_connections_paginas_editar_hook",
            "adicionar": "social_connections_paginas_adicionar_hook",
            "status": "social_connections_paginas_status_hook",
            "excluir": "social_connections_paginas_excluir_hook"
        }
    },
    "filters": {}
}
```

### 4.2 No `project/hooks/hooks.json`

Mesma estrutura do módulo, mas com suporte a arrays de callbacks por evento:

```json
{
    "version": "1.0",
    "controllers": {
        "paginas": "project-paginas-hooks.php",
        "global": "project-global-hooks.php"
    },
    "actions": {
        "paginas": {
            "editar": ["project_paginas_pos_edicao", "project_paginas_notificar"],
            "adicionar": "project_paginas_nova_pagina"
        },
        "global": {
            "request": "project_global_request_hook"
        }
    },
    "filters": {
        "paginas": {
            "html": "project_paginas_html_filter"
        }
    }
}
```

---

## 5. Tabela de Banco de Dados: `hooks`

### 5.1 Estrutura

| Campo | Tipo | Descrição |
|---|---|---|
| `id_hooks` | INT PK AUTO | Identificador único |
| `modulo` | VARCHAR(255) | ID do módulo que registrou o hook (NULL = projeto) |
| `plugin` | VARCHAR(255) NULL | ID do plugin, se o módulo for de plugin |
| `namespace` | VARCHAR(255) | Namespace alvo (ex.: `paginas`, `global`) |
| `evento` | VARCHAR(255) | Evento específico (ex.: `editar`, `adicionar`) |
| `callback` | VARCHAR(500) | Nome da função PHP a ser chamada |
| `tipo` | ENUM('action','filter') | Tipo do hook |
| `prioridade` | INT(3) DEFAULT 10 | Ordem de execução (menor = primeiro) |
| `habilitado` | TINYINT(1) DEFAULT 1 | 1 = ativo, NULL/0 = desativado |
| `projeto` | TINYINT(1) NULL | 1 = veio do project/hooks/hooks.json |
| `status` | CHAR(1) DEFAULT 'A' | 'A' = ativo, 'I' = inativo |
| `data_criacao` | DATETIME | Criação automática |
| `data_modificacao` | DATETIME | Atualização automática |

### 5.2 Índices (cache via INDEX, sem cache de arquivo)

```sql
INDEX idx_hooks_lookup (namespace, evento, tipo, status, habilitado)
INDEX idx_hooks_modulo (modulo, status)
INDEX idx_hooks_prioridade (prioridade)
```

> **Decisão de design:** sem cache em arquivo. O INDEX composto `(namespace, evento, tipo, status, habilitado)` garante performance O(log n) nas buscas e elimina necessidade de cache de arquivo.

### 5.3 Coluna `hooks` na tabela `modulos` (já existe — uso futuro)

A migração `20260217100000_add_hooks_to_modulos_table.php` já adicionou a coluna `hooks` (TINYINT) na tabela `modulos`. Essa coluna era usada por 2 outros mecanismos de hooks pré-existentes e será futuramente migrada para o novo sistema. **Ela não é usada como filtro** no pipeline de registro do novo sistema de hooks.

**Decisão de design:** A fonte de verdade é sempre o arquivo JSON do módulo (campo `"hooks"`). A função `hooks_registrar_modulo()` varre os arquivos JSON de todos os módulos instalados diretamente, independentemente do valor da coluna `modulos.hooks`. Módulos sem o campo `"hooks"` no JSON simplesmente não têm hooks registrados.

---

## 6. Biblioteca `gestor/bibliotecas/hooks.php`

### 6.1 Classe `HookManager` (Singleton)

O `HookManager` **não expõe métodos de registro/remoção de hooks** via código. O registro é feito exclusivamente através dos arquivos JSON de cada módulo/projeto, sincronizado na tabela `hooks` pelo pipeline de atualização. Em runtime, a classe apenas **executa** os callbacks e faz **lazy loading** por evento a partir do banco.

```php
class HookManager {
    private static $instance = null;

    // Cache em memória por requisição: [namespace][evento] = [prioridade => [callbacks]]
    private array $loaded  = [];   // eventos já carregados do banco nesta requisição
    private array $actions = [];   // [namespace][evento][prioridade][] = callback
    private array $filters = [];   // [namespace][evento][prioridade][] = callback
    private array $controllersIncluded = []; // evita require_once duplicado

    public static function getInstance(): self

    // Execução pública
    public function doAction(string $ns, string $evt, ...$args): void
    public function applyFilters(string $ns, string $evt, mixed $value, ...$args): mixed
    public function hasActions(string $ns, string $evt): bool
    public function hasFilters(string $ns, string $evt): bool

    // Carregamento lazy (privado)
    private function ensureLoaded(string $ns, string $evt): void
    private function resolveControllerPath(string $modulo, ?string $plugin, string $arquivo): string
}
```

**Funcionamento do lazy loading (`ensureLoaded()`):**
1. Verifica se `$loaded[$ns][$evt]` já está marcado — se sim, retorna imediatamente (zero queries extras por evento)
2. Busca na tabela `hooks` WHERE `namespace = $ns` AND `evento = $evt` AND `status='A'` AND `habilitado=1` ORDER BY `prioridade ASC`
3. Para cada registro:
   - Resolve o caminho do controller PHP e faz `require_once` (se não incluído ainda)
   - Verifica `is_callable($callback)` — ignora silenciosamente se não existir (loga em dev)
   - Registra internamente em `$actions` ou `$filters` conforme `tipo`
4. Marca `$loaded[$ns][$evt] = true`

> O mesmo evento em namespace `*` também é carregado como parte do `ensureLoaded()`.

### 6.2 Funções Globais de API (apenas execução)

O registro de hooks **não é feito via código** — é feito pelos JSONs e sincronizado pelo pipeline de atualização. A API global expõe apenas funções de **execução e consulta**:

```php
function hook_do_action(string $namespace, string $evento, ...$args): void
function hook_apply_filters(string $namespace, string $evento, mixed $value, ...$args): mixed
function hook_has_actions(string $namespace, string $evento): bool
function hook_has_filters(string $namespace, string $evento): bool
```

### 6.3 Carregamento Lazy por Evento

Não há função de pré-carregamento global. O carregamento acontece **sob demanda**, na primeira vez que `hook_do_action()` ou `hook_apply_filters()` é chamada para um determinado `namespace+evento` na requisição atual.

**Fluxo completo de uma chamada `hook_do_action('paginas', 'editar', $id, $dados)`:**
1. `hook_do_action()` delega para `HookManager::getInstance()->doAction('paginas','editar', $id, $dados)`
2. `doAction()` chama `ensureLoaded('paginas','editar')` internamente
3. `ensureLoaded()` verifica se já carregou esse par — se não, faz a query no banco
4. Controllers PHP dos hooks encontrados são incluídos via `require_once`
5. Callbacks são executados em ordem de prioridade, dentro de `try/catch`
6. Adicionalmente, `ensureLoaded('*','editar')` é verificado para hooks de namespace global

**Resolução de caminho do controller:**

| Origem | Caminho |
|---|---|
| Módulo (sem plugin) | `$_GESTOR['modulos-path'] . $modulo . '/' . $arquivo` |
| Módulo (com plugin) | `$_GESTOR['plugins-path'] . $plugin . '/modules/' . $modulo . '/' . $arquivo` |
| Projeto | `$_GESTOR['ROOT_PATH'] . '/project/hooks/controllers/' . $arquivo` |


---

## 7. Pipeline de Registro (Atualização de Sistema)

O registro/sincronização de hooks na tabela `hooks` acontece em dois momentos:

1. **Atualização do sistema gestor** — ao rodar `atualizacoes-sistema.php`, ao final do processo (após deploy de arquivos e banco), o sistema invoca `atualizacoes-hooks.php` para reprocessar todos os hooks dos módulos instalados.
2. **Atualização do projeto** — ao rodar `api_project_update()` em `api.php`, ao final da atualização, o mesmo `atualizacoes-hooks.php` é invocado para registrar os hooks do projeto (`project/hooks/hooks.json`).

### 7.1 Controlador `atualizacoes-hooks.php`

Arquivo: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Este controlador é o ponto central de sincronização. Ele:
1. Varre todos os módulos instalados (lendo diretórios em `modulos-path` e `plugins-path`)
2. Para cada módulo com `<modulo>.json` contendo o campo `"hooks"`, chama `hooks_registrar_modulo()`
3. Se existir `project/hooks/hooks.json`, chama `hooks_registrar_projeto()`

Deve ser idempotente — pode ser chamado múltiplas vezes sem efeitos colaterais.

### 7.2 Integração em `atualizacoes-sistema.php`

No arquivo `gestor/controladores/atualizacoes/atualizacoes-sistema.php`, nos hooks de ciclo de vida já existentes (`hookAfterDb`, `hookAfterAll`), invocar o controlador de hooks:

```php
function hookAfterAll(array &$context): void {
    // Após deploy completo: sincronizar tabela hooks
    require_once dirname(__FILE__) . '/atualizacoes-hooks.php';
    atualizacoes_hooks_sincronizar();
}
```

### 7.3 Integração em `api.php` (`api_project_update`)

Na função `api_project_update()` em `gestor/controladores/api/api.php`, após a cópia dos arquivos do projeto e execução do banco (`api_executar_atualizacao_banco`), adicionar a sincronização de hooks:

```php
// Após api_executar_atualizacao_banco()
require_once $_GESTOR['controladores-path'] . 'atualizacoes/atualizacoes-hooks.php';
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);
```

O parâmetro `apenas_projeto` limita a sincronização ao `project/hooks/hooks.json`, sem reprocessar hooks de todos os módulos (que já foram sincronizados na última atualização do sistema).

### 7.4 Função `hooks_registrar_modulo()`

```php
/**
 * Registra/atualiza os hooks de um módulo na tabela hooks.
 * Remove hooks antigos do módulo que não estão mais no JSON.
 * Fonte de verdade: arquivo JSON do módulo.
 */
function hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): void
```

**Algoritmo:**
1. Deletar da tabela `hooks` WHERE `modulo = $modulo` AND `projeto IS NULL`
2. Para cada entry em `actions`, iterar namespace → evento → callback(s):
   - Suporta callback como string simples `"func"` ou array `["func1","func2"]`
   - Suporta callback como objeto `{"callback": "func", "prioridade": 5}`
   - Inserir cada callback como linha separada na tabela `hooks` com `tipo = 'action'`
3. Para cada entry em `filters`: mesmo processo com `tipo = 'filter'`

### 7.5 Função `hooks_registrar_projeto()`

```php
/**
 * Registra/atualiza os hooks do projeto (project/hooks/hooks.json).
 * Remove hooks de projeto antigos e re-insere os do JSON atual.
 */
function hooks_registrar_projeto(): void
```

**Algoritmo:**
1. Verifica se `$_GESTOR['ROOT_PATH'] . '/project/hooks/hooks.json'` existe — se não, retorna silenciosamente
2. Deletar da tabela `hooks` WHERE `projeto = 1`
3. Processar o JSON com a mesma lógica de `hooks_registrar_modulo()`, inserindo com `projeto = 1`, `modulo = NULL`

---

## 8. Integração com `gestor_hooks()` e `gestor.php`

A função `gestor_hooks()` em `gestor.php` não faz nada pois a biblioteca `hooks` já é incluída pelo `config.php`. Pode remover a mesma. **Sem pré-carregamento:**

---

## 9. Integração nos Módulos: Exemplo `paginas`

Nos pontos de dispatch dentro dos módulos, adicionar:

```php
// Após salvar a página com sucesso
hook_do_action('paginas', 'editar', $id_pagina, $dados_pagina);
```

```php
// Após criar uma nova página
hook_do_action('paginas', 'adicionar', $id_pagina, $dados_pagina);
```

```php
// Ao alterar o status de uma página
hook_do_action('paginas', 'status', $id_pagina, $novo_status);
```

```php
// Antes de excluir (permite que o hook cancele ou processe antes)
hook_do_action('paginas', 'excluir', $id_pagina);
```

Para filtros (transformar dados antes de salvar):

```php
// Filtrar o HTML de uma página antes de renderizar
$html = hook_apply_filters('paginas', 'html', $html_original, $id_pagina);
```

---

## 10. Controller de Hooks: `social-connections.hooks.php`

Arquivo a ser criado em: `conn2flow-site/gestor/modulos/social-connections/social-connections.hooks.php`

```php
<?php
/**
 * Controller de Hooks: social-connections
 * Funções registradas na tabela hooks e carregadas pelo HookManager.
 */

function social_connections_paginas_editar_hook($id_pagina, $dados_pagina = []): void {
    // 1. Verificar se há conexões sociais ativas
    // 2. Verificar configurações de auto-post do usuário/projeto
    // 3. Buscar dados completos da página
    // 4. Gerar conteúdo para post via IA ou template
    // 5. Postar nas redes sociais configuradas
    // 6. Registrar log da operação
}

function social_connections_paginas_adicionar_hook($id_pagina, $dados_pagina = []): void {
    // Lógica específica para novos conteúdos publicados
}

function social_connections_paginas_status_hook($id_pagina, $novo_status): void {
    // Notificar alterações relevantes de status (publicado, despublicado)
}

function social_connections_paginas_excluir_hook($id_pagina): void {
    // Ações antes/durante exclusão de página (remover posts agendados, etc.)
}
```

---

## 11. Migração: `create_hooks_table`

Arquivo: `gestor/db/migrations/YYYYMMDDHHMMSS_create_hooks_table.php`

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateHooksTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('hooks')) return;

        $table = $this->table('hooks', ['id' => 'id_hooks']);
        $table
            ->addColumn('modulo',           'string',  ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('plugin',           'string',  ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('namespace',        'string',  ['limit' => 255, 'null' => false])
            ->addColumn('evento',           'string',  ['limit' => 255, 'null' => false])
            ->addColumn('callback',         'string',  ['limit' => 500, 'null' => false])
            ->addColumn('tipo',             'string',  ['limit' => 10,  'null' => false,  'default' => 'action'])
            ->addColumn('prioridade',       'integer', ['limit' => 3,   'null' => false,  'default' => 10])
            ->addColumn('habilitado',       'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => true, 'default' => 1])
            ->addColumn('projeto',          'integer', ['limit' => MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
            ->addColumn('status',           'char',    ['limit' => 1,   'null' => true,   'default' => 'A'])
            ->addColumn('data_criacao',     'datetime',['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime',['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Índice principal de lookup (substitui cache de arquivo)
            ->addIndex(['namespace', 'evento', 'tipo', 'status', 'habilitado'], ['name' => 'idx_hooks_lookup'])
            ->addIndex(['modulo', 'status'],    ['name' => 'idx_hooks_modulo'])
            ->addIndex(['prioridade'],          ['name' => 'idx_hooks_prioridade'])
            ->addIndex(['projeto'],             ['name' => 'idx_hooks_projeto'])
            ->create();
    }
}
```

---

## 12. Recursos Adicionais Sugeridos

### 12.1 Variantes before/after

Convenção de nomenclatura para eventos que suportam antes e depois:

```php
hook_do_action('paginas', 'editar.before', $id, $dados); // antes de salvar
// ... lógica principal ...
hook_do_action('paginas', 'editar.after', $id, $dados);  // após salvar com sucesso
```

Os hooks no JSON se declaram com o evento `editar.before` ou `editar.after`:
```json
"actions": {
    "paginas": {
        "editar.before": "social_connections_paginas_pre_edicao_hook",
        "editar.after": "social_connections_paginas_pos_edicao_hook"
    }
}
```

### 12.2 Namespace global (`*`)

Hook que captura TODOS os eventos de qualquer namespace:

```json
"actions": {
    "*": {
        "request": "meu_hook_global_request"
    }
}
```

Implementação no `doAction()`: além de disparar handlers do namespace específico, verificar se existem handlers registrados no namespace `*`.

### 12.3 Prioridade configurável no JSON

```json
"actions": {
    "paginas": {
        "editar": {
            "callback": "social_connections_paginas_editar_hook",
            "prioridade": 5
        }
    }
}
```

Quando o valor do evento for um objeto com `callback` e `prioridade`, usar a prioridade customizada (padrão: 10).

### 12.4 Campo `habilitado` por módulo

Permite desabilitar um hook específico pelo admin do gestor sem removê-lo da tabela:
- `habilitado = 1` → ativo
- `habilitado = NULL` ou `0` → desativado
- Útil para debug e para desligar hooks temporariamente

### 12.5 Log de execução de hooks (opcional)

Para ambiente de desenvolvimento (`$_GESTOR['development-env'] == true`), registrar em `gestor/logs/hooks.log`:

```
[2026-03-30 14:22:01] ACTION paginas.editar => social_connections_paginas_editar_hook (9ms)
[2026-03-30 14:22:01] ACTION paginas.editar => project_paginas_notificar (2ms)
```

### 12.6 Segurança

- Callbacks só são aceitos se `is_callable($callback)` retornar `true` após o `require_once` do controller
- Caminhos de controllers são resolvidos internamente (nunca vindos do request do usuário)
- Cada execução de hook é envolto em `try/catch` para isolar falhas e não quebrar o fluxo principal
- Erro no hook → log em `gestor/logs/hooks-errors.log` + continua execução

---

## 13. Lista de Implementação (Todo)

```markdown
- [ ] 1. Criar migração `create_hooks_table.php` no repositório conn2flow
- [ ] 2. Implementar `gestor/bibliotecas/hooks.php` completo
         (HookManager com lazy loading + hook_do_action + hook_apply_filters
          + hook_has_actions + hook_has_filters
          + hooks_registrar_modulo + hooks_registrar_projeto)
- [ ] 3. Atualizar `gestor_hooks()` em `gestor.php` para incluir a biblioteca (sem pre-load)
- [ ] 4. Criar `gestor/controladores/atualizacoes/atualizacoes-hooks.php`
         (função atualizacoes_hooks_sincronizar() que varre JSONs e chama hooks_registrar_modulo/projeto)
- [ ] 5. Modificar `atualizacoes-sistema.php`: chamar atualizacoes-hooks.php em hookAfterAll()
- [ ] 6. Modificar `api.php` (api_project_update): chamar atualizacoes-hooks.php após atualização do banco
- [ ] 7. Criar estrutura `gestor/project/hooks/hooks.json` (template vazio) e `controllers/` na instalação
- [ ] 8. Criar `social-connections.hooks.php` no repositório conn2flow-site
- [ ] 9. Adicionar chamadas de `hook_do_action()` nos pontos de dispatch do módulo `paginas`
- [ ] 10. Validar funcionamento end-to-end: editar página → hook social-connections disparado
```

---

## 14. Pontos a Confirmar antes da Implementação

1. **Variantes before/after**: são opcionais e declaradas explicitamente no JSON (`"editar.before"`, `"editar.after"`). Não são geradas automaticamente para todo evento — confirmado pela convenção da seção 12.1.
2. **Múltiplos callbacks por evento**: suportado — string simples, array de strings, ou objeto `{callback, prioridade}` — conforme seção 7.4.
3. **`project/hooks/hooks.json` bootstrap**: criar automaticamente com estrutura vazia na primeira execução de `atualizacoes_hooks_sincronizar()` se o diretório `project/hooks/` existir mas o JSON não. Ou apenas ignorar silenciosamente se o arquivo não existir.
4. **Módulo `paginas`** (`conn2flow-site`): localizar os pontos exatos no `paginas.php` onde adicionar `hook_do_action()` — isso será feito durante a implementação (item 9 da todo list).
5. **Admin UI de hooks**: feature futura — tela no gestor para listar, habilitar/desabilitar e inspecionar hooks registrados na tabela `hooks`.
