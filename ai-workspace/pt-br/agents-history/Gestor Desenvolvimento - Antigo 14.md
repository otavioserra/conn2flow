# Gestor Desenvolvimento - Antigo 14 (Março 2026)

## Implementação Completa do Sistema de Hooks

## Contexto e Objetivos

Esta sessão de desenvolvimento teve como objetivo projetar e implementar do zero um sistema de hooks completo para a plataforma Conn2Flow, inspirado no mecanismo de `do_action` / `apply_filters` do WordPress. O sistema foi desenhado para permitir que módulos e projetos reajam a eventos de outros módulos **sem modificar o código-fonte** do módulo emissor — promovendo extensibilidade e desacoplamento arquitetural.

Os requisitos principais foram:
- Registro de hooks exclusivamente via JSON (fonte de verdade) — nunca direto no banco.
- Sincronização automática com a tabela `hooks` no pipeline de atualização/deploy.
- Lazy loading por namespace+evento no runtime para não impactar performance.
- Suporte a dois tipos: **action** (side-effect, `void`) e **filter** (transformação de valor).
- Campo `habilitado` no JSON para ativar/desativar callbacks sem removê-los.

---

## Escopo Detalhado Realizado

### 1. Migration: Tabela `hooks`

**Arquivo**: `gestor/db/migrations/20260630100000_create_hooks_table.php`

Criada via Phinx, a tabela armazena todas as entradas de hooks sincronizadas a partir dos JSONs de módulos e do projeto. Colunas principais:

| Coluna       | Tipo/Referência             | Notas                                              |
|--------------|-----------------------------|----------------------------------------------------|
| `id_hooks`   | INT PK                      | Chave primária auto-increment                      |
| `modulo`     | VARCHAR(255), nullable      | NULL quando o hook é de projeto                    |
| `plugin`     | VARCHAR(255), nullable      | Preenchido quando o módulo é de plugin             |
| `namespace`  | VARCHAR(255)                | Ex: `admin-paginas`, `produtos`, `*`               |
| `evento`     | VARCHAR(255)                | Ex: `adicionar.banco`, `editar.pagina`             |
| `callback`   | VARCHAR(500)                | Nome da função PHP                                 |
| `tipo`       | VARCHAR(10), default=action | `action` ou `filter`                               |
| `prioridade` | SMALLINT, default=10        | Menor = executa primeiro                           |
| `habilitado` | TINYINT, default=1          | 0 = sincronizado mas não carregado em runtime      |
| `projeto`    | TINYINT, nullable           | 1 = veio de `project/hooks/hooks.json`             |
| `status`     | CHAR(1), default=A          | A = ativo                                          |

Índices: `idx_hooks_lookup` em `(namespace, evento, tipo, status, habilitado)` — otimiza o `loadFromDb`.

---

### 2. Biblioteca Central: `hooks.php`

**Arquivo**: `gestor/bibliotecas/hooks.php`

Estruturada em três camadas:

#### A. Classe `HookManager` (Singleton)

O coração do sistema. Instância única por requisição HTTP, com lazy loading:

```php
// Só consulta o banco na primeira chamada para aquele namespace+evento
HookManager::getInstance()->doAction($ns, $evt, ...$args)
```

**Lazy loading** (`ensureLoaded` → `loadFromDb`):
```sql
WHERE namespace='...' AND evento='...' AND status='A' AND habilitado=1
ORDER BY prioridade ASC, id_hooks ASC
```

**Resolução automática de controllers**: Ao carregar um hook do banco, o `HookManager` determina e inclui o arquivo PHP que contém as funções callback:
- Hook de projeto → lê `project/hooks/hooks.json`, busca `controllers[namespace]`, inclui de `project/hooks/controllers/`
- Hook de módulo → lê `modulos/<modulo>/<modulo>.json`, busca `hooks.controllers[namespace]`, inclui do diretório do módulo
- Inclusão via `require_once` com cache interno (sem duplicação)

**Compatibilidade de argumentos com `ReflectionFunction`**: Durante o desenvolvimento, foi descoberto um bug crítico — callbacks com zero parâmetros recebiam os `$args` do `doAction` e quebravam com "Too few arguments". A solução foi usar `ReflectionFunction` para detectar quantos parâmetros o callback exige e preencher os ausentes com `null`:

```php
$ref    = new \ReflectionFunction($cb['callback']);
$needed = $ref->getNumberOfRequiredParameters();
if (count($argsToCall) < $needed) {
    $argsToCall = array_pad($argsToCall, $needed, null);
}
```

Isso tornou os callbacks **completamente flexíveis**: podem aceitar 0, 1 ou N parâmetros, independente do que o emissor passa.

#### B. 4 Funções Globais de API

```php
hook_do_action(string $namespace, string $evento, mixed ...$args): void
hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed
hook_has_actions(string $namespace, string $evento): bool
hook_has_filters(string $namespace, string $evento): bool
```

#### C. 3 Funções de Registro (pipeline de atualização)

```php
hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): void
hooks_registrar_projeto(): void
hooks_inserir_callbacks(?string $modulo, ?string $plugin, string $namespace, string $evento, mixed $callbackDef, string $tipo, ?int $projeto): void
```

A função `hooks_inserir_callbacks` suporta **3 formatos de definição de callback** no JSON:
- String simples: `"minha_funcao"`
- Objeto: `{"callback": "minha_funcao", "prioridade": 5, "habilitado": 1}`
- Array (múltiplos): `["funcao_a", {"callback": "funcao_b", "prioridade": 20}]`

O campo `habilitado` é lido do JSON e persistido no banco. O `loadFromDb` filtra por `habilitado=1`, então o controle é 100% declarativo via JSON.

---

### 3. Controller de Atualização: `atualizacoes-hooks.php`

**Arquivo**: `gestor/controladores/atualizacoes/atualizacoes-hooks.php`

Função central `atualizacoes_hooks_sincronizar(array $opcoes = [])` com 3 modos:
- Sem opções: sincroniza módulos + plugins + projeto (operação completa, idempotente)
- `['apenas_projeto' => true]`: sincroniza somente `project/hooks/hooks.json` — usada no deploy de projeto
- `['apenas_modulos' => true]`: sincroniza somente módulos/plugins — usada na atualização completa do sistema

Lógica de sincronização:
1. **Módulos**: Varre `$_GESTOR['modulos-path']`, lê `<modulo>.json` de cada diretório, chama `hooks_registrar_modulo` se existir seção `"hooks"`.
2. **Plugins**: Varre `$_GESTOR['plugins-path']`, entra em cada `modules/`, repete o processo passando o ID do plugin.
3. **Projeto**: Lê `project/hooks/hooks.json`, deleta os hooks de projeto existentes no banco, re-insere todos.

---

### 4. Integração no Pipeline de Atualização

#### `atualizacoes-sistema.php` — Atualização Completa

**Modificação**: Na função `hookAfterAll()`, adicionada chamada após todas as atualizações de módulos/plugins:
```php
atualizacoes_hooks_sincronizar();
```

#### `api.php` — Deploy de Projeto

**Modificação**: Na função `api_executar_atualizacao_banco()` (endpoint `/_api/project/update`), adicionada chamada ao final do processamento:
```php
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);
```

Isso garante que ao fazer deploy do `conn2flow-site`, o `hooks.json` do projeto seja sincronizado automaticamente na tabela `hooks`.

---

### 5. Integração nos Módulos Core: `interface.php`

**Arquivo**: `gestor/bibliotecas/interface.php`

Foram adicionados disparos de `hook_do_action` em todos os pontos estratégicos do ciclo de vida da interface padrão. Qualquer módulo que use o sistema de interface do Conn2Flow automaticamente emite estes hooks:

| Momento | Evento disparado | Args |
|---------|-----------------|------|
| POST antes do INSERT | `{opcao}.pre-banco` | — |
| Após INSERT/UPDATE/DELETE/Status/Clone | `{opcao}.banco` | `$id`, `$dados[]` |
| GET na renderização de página | `{opcao}.pagina` | — |

**Decisão de design**: O `namespace` usado é sempre `$_GESTOR['modulo-id']`, o ID do módulo que está sendo executado na requisição. Isso significa que um hook registrado para namespace `admin-paginas` só dispara quando o módulo `admin-paginas` está ativo — evitando colisões.

---

### 6. Integração no Módulo `admin-paginas`

**Arquivo**: `gestor/modulos/admin-paginas/admin-paginas.php`

Os hooks do `interface.php` cobrem a maioria dos casos. Mas o `admin-paginas` também tem lógica própria de clone/adicionar que foi instrumentada diretamente:

```php
// Após INSERT específico do admin-paginas
hook_do_action($_GESTOR['modulo-id'], 'adicionar.banco', $id, [
    'nome' => $nome, 'caminho' => $caminho, ...
]);

// Após clone
hook_do_action($_GESTOR['modulo-id'], 'clonar.banco', $id, [...]);
```

---

### 7. Documentação Técnica: `CONN2FLOW-HOOKS.md`

Criada documentação completa em dois idiomas:
- **PT-BR**: `ai-workspace/pt-br/docs/CONN2FLOW-HOOKS.md`
- **EN**: `ai-workspace/en/docs/CONN2FLOW-HOOKS.md`

Cobertura:
- Conceitos (Action vs Filter, Namespace+Evento, wildcard `*`)
- Arquitetura completa (diagrama ASCII do fluxo)
- Tabela `hooks` (todos os campos)
- 4 funções globais de API com assinaturas
- Tabela de eventos nativos do `interface.php` (incluindo `*.pre-banco`, `*.parametros`, `*.pagina`)
- 3 formatos de definição de callback no JSON
- Campo `habilitado` e como funciona em runtime
- Pipeline de sincronização com os 3 modos
- 5 casos de uso práticos com código completo
- Guia passo-a-passo para criar um hook
- Tabela de boas práticas

---

## Arquivos Criados/Modificados no Repositório `conn2flow`

### Criados
| Arquivo | Tipo |
|---------|------|
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Novo |
| `gestor/bibliotecas/hooks.php` | Novo |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Novo |
| `ai-workspace/pt-br/docs/CONN2FLOW-HOOKS.md` | Novo |
| `ai-workspace/en/docs/CONN2FLOW-HOOKS.md` | Novo |

### Modificados
| Arquivo | Modificação |
|---------|-------------|
| `gestor/bibliotecas/interface.php` | Adicionados disparos de `hook_do_action` em 5 pontos do ciclo de vida |
| `gestor/modulos/admin-paginas/admin-paginas.php` | Adicionados `hook_do_action` em adicionar e clonar |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | `hookAfterAll()` chama `atualizacoes_hooks_sincronizar()` |
| `gestor/controladores/api/api.php` | `api_executar_atualizacao_banco()` chama `atualizacoes_hooks_sincronizar(['apenas_projeto' => true])` |

---

## Bugs Encontrados e Corrigidos

### Bug 1: "Too few arguments to function"

**Sintoma**: Funções callback registradas com 0 parâmetros (ex: `function meu_hook(): void`) quebravam com `Fatal error: Too few arguments` quando o emissor passava argumentos via `hook_do_action(..., $id, $dados)`.

**Causa raiz**: O `call_user_func_array` passava todos os `$args` diretamente para o callback sem checar quantos parâmetros ele aceita.

**Solução**: Uso de `\ReflectionFunction` para inspecionar a assinatura do callback em runtime:
```php
$needed = $ref->getNumberOfRequiredParameters();
if (count($argsToCall) < $needed) {
    $argsToCall = array_pad($argsToCall, $needed, null);
}
```
Esta abordagem também protege o lado contrário (mais args do que o callback aceita) porque `call_user_func_array` já ignora args extras.

### Bug 2: Campo `habilitado` não persistido do JSON

**Sintoma**: Ao definir `"habilitado": 0` no `hooks.json`, o hook ainda era inserido no banco com `habilitado=1`.

**Causa raiz**: A função `hooks_inserir_callbacks()` na implementação inicial hardcodava `'habilitado' => '1'` independente do que viesse no JSON.

**Solução**: Refatoração completa da função para parsear o campo `habilitado` nos 3 formatos de callback (string, objeto, array de objetos) e usá-lo no `banco_insert_name`.

---

## Decisões de Design

1. **JSON como fonte de verdade**: Hooks nunca são registrados diretamente no banco. A tabela `hooks` é reconstruída a cada sincronização e deve ser tratada como cache — nunca editada manualmente.

2. **Lazy loading por namespace+evento**: Evita carregar todos os hooks na inicialização. O banco só é consultado quando `hook_do_action` é chamado pela primeira vez para um par específico.

3. **Namespace `*` (wildcard)**: Permite criar middlewares que reagem a eventos de qualquer módulo (ex: módulo de auditoria global, métricas). O `HookManager` carrega automaticamente ambos o namespace específico e o `*` para cada evento.

4. **Isolamento de controllers**: O arquivo PHP com as funções callback fica no diretório do módulo que **recebe** o evento (não no que emite). O `HookManager` inclui o arquivo automaticamente via `require_once` ao primeiro uso, sem necessidade de includes manuais.

5. **ReflectionFunction**: Opção mais robusta que exigir que todos os callbacks tenham a mesma assinatura. Permite callbacks "enxutos" sem parâmetros mesmo quando o emissor passa dados.

---

## Lições Aprendidas

- **Idempotência é essencial em sincronizações**: As funções `hooks_registrar_modulo` e `hooks_registrar_projeto` sempre deletam os hooks existentes antes de re-inserir. Isso garante consistência mesmo em re-execuções.
- **ReflectionFunction tem custo de performance**: Em produção com muitos callbacks por evento, o `ReflectionFunction` é chamado uma vez por execução do event (não por request, pois o lazy load evita repetição). O custo é aceitável.
- **O wildcard `*` precisa de duas consultas**: `ensureLoaded` consulta o namespace específico E o `*` separadamente. Isso foi necessário para manter o lazy loading granular sem duplicar entradas no banco.

## Próximos Passos Sugeridos

1. **Cache em arquivo**: Para ambientes de alto tráfego, considerar cache em `temp/` dos hooks carregados por namespace+evento para eliminar a consulta ao banco no runtime.
2. **Manager UI**: Criar uma tela admin para visualizar todos os hooks registrados na tabela (namespace, evento, callback, módulo, prioridade, habilitado) com botão de sincronização manual.
3. **Hooks de plugin**: Testar especificamente a resolução de controllers de plugins (path `plugins-path/<plugin>/modules/<modulo>/<arquivo>`).
4. **Testes unitários**: Criar testes para `HookManager::doAction` e `applyFilters` com cenários de wildcard, ReflectionFunction padding e habilitado=0.
