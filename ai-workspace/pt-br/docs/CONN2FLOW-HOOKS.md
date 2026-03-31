# Sistema de Hooks do Conn2Flow

## Visão Geral

O sistema de hooks do Conn2Flow é inspirado no mecanismo de actions e filters do WordPress. Ele permite que módulos e projetos **interceptem e reajam a eventos** de outros módulos **sem modificar o código-fonte** do módulo emissor.

> **Analogia:** Pense nos hooks como tomadas elétricas. O módulo central instala a tomada (`hook_do_action`). Qualquer módulo pode "plugar" sua função nessa tomada via JSON — quando o evento acontece, todos os plugues conectados são acionados.

---

## Conceitos Fundamentais

### Action vs Filter

| Tipo       | Finalidade                                    | Retorno              | Função PHP                |
|------------|-----------------------------------------------|----------------------|---------------------------|
| **Action** | Side-effect puro (log, e-mail, widget, etc.)  | `void`               | `hook_do_action()`        |
| **Filter** | Transformação de valor (modifica dado)        | `mixed` (novo valor) | `hook_apply_filters()`    |

### Namespace e Evento

Todo hook é identificado por dois componentes:

```
namespace . evento
```

- **Namespace** → identifica o módulo ou contexto que emite o evento (ex: `admin-paginas`, `produtos`, `global`).
- **Evento** → descreve a ação específica (ex: `adicionar.banco`, `editar.pagina`, `excluir.banco`).

Namespace especial `*` → registra o hook para **qualquer** namespace.

---

## Arquitetura

```
JSON do módulo/projeto
        │
        ▼
atualizacoes_hooks_sincronizar()   ← pipeline de deploy/atualização
        │
        ▼
tabela `hooks` (banco de dados)
        │
        ▼
hook_do_action() / hook_apply_filters()
        │
        ▼
HookManager::getInstance()->doAction() / applyFilters()
        │ lazy-load por namespace+evento
        ▼
callback PHP executado
```

### HookManager (Singleton)

- Classe `HookManager`, instância única por requisição.
- **Lazy loading**: só consulta o banco quando `hook_do_action()` for chamado pela primeira vez para aquele namespace+evento.
- Inclui automaticamente o arquivo PHP do controller mapeado no JSON antes de executar o callback.
- Logs de erro em `logs/hooks-errors.log`.

---

## Tabela `hooks`

Criada pela migration `20260630100000_create_hooks_table.php`.

| Coluna             | Tipo         | Descrição                                                        |
|--------------------|--------------|------------------------------------------------------------------|
| `id_hooks`         | INT (PK)     | Chave primária                                                   |
| `modulo`           | VARCHAR(255) | ID do módulo que registrou o hook (NULL = projeto)               |
| `plugin`           | VARCHAR(255) | ID do plugin (se o módulo for de plugin)                         |
| `namespace`        | VARCHAR(255) | Namespace alvo (ex: `paginas`, `global`, `*`)                    |
| `evento`           | VARCHAR(255) | Evento específico (ex: `editar`, `adicionar.banco`)              |
| `callback`         | VARCHAR(500) | Nome da função PHP a ser chamada                                 |
| `tipo`             | VARCHAR(10)  | `action` ou `filter`                                             |
| `prioridade`       | SMALLINT     | Ordem de execução — menor valor = executado primeiro (padrão: 10)|
| `habilitado`       | TINYINT      | `1` = ativo, `0` ou NULL = desabilitado                          |
| `projeto`          | TINYINT      | `1` = veio de `project/hooks/hooks.json`                         |
| `status`           | CHAR(1)      | `A` = ativo, `I` = inativo                                       |
| `data_criacao`     | DATETIME     | Criação automática                                               |
| `data_modificacao` | DATETIME     | Atualização automática                                           |

---

## API de Execução (4 Funções Globais)

### `hook_do_action()`

```php
hook_do_action(string $namespace, string $evento, mixed ...$args): void
```

Executa todos os callbacks de **action** registrados para o namespace+evento.

**Importante:** Callbacks com menos parâmetros do que os `$args` passados são compatíveis — os argumentos extras são ignorados. Callbacks com mais parâmetros obrigatórios recebem `null` nos parâmetros ausentes (via `ReflectionFunction`).

---

### `hook_apply_filters()`

```php
hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed
```

Aplica todos os **filters** registrados para o namespace+evento sobre `$value` e retorna o valor transformado.

---

### `hook_has_actions()`

```php
hook_has_actions(string $namespace, string $evento): bool
```

Verifica se há alguma action registrada (útil para evitar processamento desnecessário).

---

### `hook_has_filters()`

```php
hook_has_filters(string $namespace, string $evento): bool
```

Verifica se há algum filter registrado.

---

## Eventos Nativos da Plataforma

A biblioteca `interface.php` dispara automaticamente os seguintes hooks para **qualquer módulo** que use o sistema de interface padrão:

- **Padrão**:
| Namespace     | Evento                | Quando é disparado                               | Args                  |
|---------------|-----------------------|--------------------------------------------------|-----------------------|
| `{modulo-id}` | `{opcao}.{evento}`    | Praticamente todos os eventos são mapeados       | —                     |

- **Exemplos de CRUD**:
| Namespace     | Evento                | Quando é disparado                               | Args                  |
| `{modulo-id}` | `adicionar.pre-banco` | Antes do INSERT (validação/modificação de dados) | —                     |
| `{modulo-id}` | `adicionar.banco`     | Após INSERT bem-sucedido                         | `$id`, `$dados[]`     |
| `{modulo-id}` | `adicionar.parametros`| Antes de renderizar a página de adição (GET)     | —                     |
| `{modulo-id}` | `adicionar.pagina`    | Após renderizar a página de adição (GET)         | —                     |
| `{modulo-id}` | `editar.pre-banco`    | Antes do UPDATE                                  | —                     |
| `{modulo-id}` | `editar.banco`        | Após UPDATE bem-sucedido                         | `$id`, `$dados[]`     |
| `{modulo-id}` | `editar.parametros`   | Antes de renderizar a página de edição (GET)     | —                     |
| `{modulo-id}` | `editar.pagina`       | Após renderizar a página de edição (GET)         | —                     |
| `{modulo-id}` | `excluir.banco`       | Após DELETE bem-sucedido                         | `$id`                 |
| `{modulo-id}` | `status.banco`        | Após alteração de status                         | `$id`, `$novo_status` |
| `{modulo-id}` | `clonar.banco`        | Após clone/duplicação                            | `$id`, `$dados[]`     |
| `{modulo-id}` | `clonar.parametros`   | Antes de renderizar a página de clonagem (GET)   | —                     |
| `{modulo-id}` | `clonar.pagina`       | Após renderizar a página de clonagem (GET)       | —                     |

> `{modulo-id}` é o valor de `$_GESTOR['modulo-id']` do módulo em execução (ex: `admin-paginas`, `produtos`).

---

## Registrando Hooks via JSON

A fonte de verdade são os arquivos JSON. **Nunca registre hooks diretamente no banco** — eles serão sobrescritos na próxima sincronização.

### 1. Hook de Módulo (em `modulos/<modulo>/<modulo>.json`)

```json
{
    "hooks": {
        "controllers": {
            "admin-paginas": "meu-modulo.hooks.php"
        },
        "actions": {
            "admin-paginas": {
                "adicionar.banco": "meu_modulo_pagina_adicionada_hook",
                "editar.banco": {
                    "callback": "meu_modulo_pagina_editada_hook",
                    "prioridade": 5,
                    "habilitado": 1
                }
            }
        },
        "filters": {
            "admin-paginas": {
                "titulo.formatar": "meu_modulo_formatar_titulo_filter"
            }
        }
    }
}
```

### 2. Hook de Projeto (em `project/hooks/hooks.json`)

```json
{
    "controllers": {
        "admin-paginas": "admin-paginas.hooks.php"
    },
    "actions": {
        "admin-paginas": {
            "adicionar.pagina": {
                "callback": "projeto_pagina_adicionada_hook",
                "prioridade": 5,
                "habilitado": 1
            },
            "excluir.banco": "projeto_pagina_excluida_hook"
        }
    },
    "filters": {}
}
```

### 3. Formatos de Definição de Callback

#### String simples (prioridade 10, habilitado por padrão)
```json
"adicionar.banco": "nome_da_funcao"
```

#### Objeto com opções
```json
"adicionar.banco": {
    "callback": "nome_da_funcao",
    "prioridade": 5,
    "habilitado": 1
}
```

#### Array de callbacks (vários listeners no mesmo evento)
```json
"adicionar.banco": [
    "primeira_funcao",
    {
        "callback": "segunda_funcao",
        "prioridade": 20,
        "habilitado": 1
    }
]
```

---

## O Campo `habilitado`

O campo `habilitado` no JSON controla se o hook será **carregado** pelo `HookManager` em runtime.

- `"habilitado": 1` → hook é executado normalmente.
- `"habilitado": 0` → hook é sincronizado no banco MAS **não é carregado** (`loadFromDb` filtra com `AND habilitado=1`).

Isso permite desativar temporariamente um callback sem remover sua definição do JSON. Para reativar, basta mudar para `1` e fazer um novo deploy/sincronização.

---

## Estrutura de Arquivos

```
project/
└── hooks/
    ├── hooks.json                       ← definição de hooks do projeto
    └── controllers/
        └── admin-paginas.hooks.php      ← funções callback (controller de hooks)

modulos/
└── meu-modulo/
    ├── meu-modulo.json                  ← contém seção "hooks"
    └── meu-modulo.hooks.php             ← funções callback (controller de hooks)
```

---

## Pipeline de Sincronização

A sincronização é executada automaticamente em dois momentos:

1. **Deploy do projeto** → `api.php` chama `atualizacoes_hooks_sincronizar()` após receber o pacote de atualização via `/_api/project/update`.
2. **Atualização completa do sistema** → `atualizacoes-sistema.php` `hookAfterAll()` chama `atualizacoes_hooks_sincronizar()` (módulos + plugins + projeto).

A sincronização é **idempotente** — pode ser executada múltiplas vezes sem efeitos colaterais.

```php
// Sincronizar tudo (módulos + plugins + projeto), recomendável! 
atualizacoes_hooks_sincronizar();

// Sincronizar apenas o projeto caso necessário (hooks específicos do projeto, sem tocar nos hooks dos módulos/plugins)
atualizacoes_hooks_sincronizar(['apenas_projeto' => true]);

// Sincronizar apenas módulos/plugins (sem projeto)
atualizacoes_hooks_sincronizar(['apenas_modulos' => true]);
```

---

## Casos de Uso Práticos

### Caso 1 — Integração Social ao Adicionar Página

**Cenário:** O módulo `social-connections` precisa ser notificado sempre que uma nova página for criada em `admin-paginas` para exibir um widget de configuração de redes sociais.

**`project/hooks/hooks.json`:**
```json
{
    "controllers": {
        "admin-paginas": "admin-paginas.hooks.php"
    },
    "actions": {
        "admin-paginas": {
            "adicionar.pagina": {
                "callback": "social_connections_paginas_adicionar_pagina_hook",
                "prioridade": 5,
                "habilitado": 1
            }
        }
    },
    "filters": {}
}
```

**Alterar o conteúdo da página usando hook: `project/hooks/controllers/admin-paginas.hooks.php`:**
```php
function social_connections_paginas_adicionar_pagina_hook(): void {
    global $_GESTOR;

    $_GESTOR['pagina'] .= '<div class="social-integration-widget">';
    $_GESTOR['pagina'] .= '<h3>Integração Social</h3>';
    $_GESTOR['pagina'] .= '<p>Configure conexões sociais para esta página.</p>';
    $_GESTOR['pagina'] .= '</div>';
}
```

**Como funciona:**
Quando `admin-paginas` renderiza a tela de adição (GET), `interface.php` dispara:
```php
hook_do_action('admin-paginas', 'adicionar.pagina');
```
O `HookManager` carrega o controller e executa `social_connections_paginas_adicionar_pagina_hook()` para incluir o widget de integração social na página.

---

### Caso 2 — Auditoria ao Editar Qualquer Registro

**Cenário:** Um módulo de auditoria quer registrar em log qualquer edição feita em páginas.

**`modulos/auditoria/auditoria.json`:**
```json
{
    "hooks": {
        "controllers": {
            "admin-paginas": "auditoria.hooks.php"
        },
        "actions": {
            "admin-paginas": {
                "editar.banco": "auditoria_paginas_editar_hook"
            }
        }
    }
}
```

**`modulos/auditoria/auditoria.hooks.php`:**
```php
function auditoria_paginas_editar_hook(string $id, array $dados = []): void {
    global $_GESTOR;
    $logPath = $_GESTOR['logs-path'] . 'auditoria.log';
    $ts = date('Y-m-d H:i:s');
    $msg = "[{$ts}] Página ID={$id} editada. Nome: " . ($dados['nome'] ?? '?') . PHP_EOL;
    file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
}
```

**Como é acionado pelo emissor (`admin-paginas.php`):**
```php
hook_do_action($_GESTOR['modulo-id'], 'editar.banco', $id, [
    'nome'    => $nome,
    'caminho' => $caminho,
]);
```

---

### Caso 3 — Filter para Modificar Título

**Cenário:** O projeto precisa formatar automaticamente os títulos de páginas ao salvar.

**`project/hooks/hooks.json`:**
```json
{
    "controllers": {
        "admin-paginas": "formatacao.hooks.php"
    },
    "filters": {
        "admin-paginas": {
            "titulo.salvar": {
                "callback": "formatacao_titulo_capitalizar_filter",
                "prioridade": 1
            }
        }
    },
    "actions": {}
}
```

**`project/hooks/controllers/formatacao.hooks.php`:**
```php
function formatacao_titulo_capitalizar_filter(string $titulo): string {
    return mb_convert_case(trim($titulo), MB_CASE_TITLE, 'UTF-8');
}
```

**Uso no módulo emissor:**
```php
$titulo = hook_apply_filters($_GESTOR['modulo-id'], 'titulo.salvar', $titulo_raw);
// $titulo agora está formatado antes de ir para o banco
```

---

### Caso 4 — Hook Global (Wildcard `*`)

Caso precise de um middleware que reaja a um evento disparado por **qualquer módulo**, use o namespace `*`:

**Cenário:** Um módulo de métricas quer contabilizar **qualquer** tipo de adição no sistema.

**`modulos/metricas/metricas.json`:**
```json
{
    "hooks": {
        "controllers": {
            "*": "metricas.hooks.php"
        },
        "actions": {
            "*": {
                "adicionar.banco": "metricas_contabilizar_adicao_hook"
            }
        }
    }
}
```

**`modulos/metricas/metricas.hooks.php`:**
```php
function metricas_contabilizar_adicao_hook(string $id = null): void {
    // Executado para QUALQUER módulo que dispare 'adicionar.banco'
    metricas_incrementar_contador('total_adicoes');
}
```

---

### Caso 5 — Desabilitando Temporariamente um Hook

Em `hooks.json`, mude `"habilitado": 0`. Na próxima sincronização, o hook ainda existirá no banco mas não será carregado em runtime:

```json
"adicionar.pagina": {
    "callback": "social_connections_paginas_adicionar_pagina_hook",
    "prioridade": 5,
    "habilitado": 0
}
```

Para reativar: `"habilitado": 1` + novo deploy.

---

## Criando um Novo Hook num Módulo

### Passo 1 — Emitir o evento no módulo emissor

```php
hook_do_action($_GESTOR['modulo-id'], 'meu-evento', $dado_relevante);
```

### Passo 2 — Registrar o listener no JSON do receptor

Em `project/hooks/hooks.json` ou `modulos/meu-modulo/meu-modulo.json`:
```json
{
    "controllers": {
        "meu-namespace": "meu-modulo.hooks.php"
    },
    "actions": {
        "meu-namespace": {
            "meu-evento": {
                "callback": "minha_funcao_hook",
                "prioridade": 10,
                "habilitado": 1
            }
        }
    }
}
```

### Passo 3 — Criar o arquivo controller com a função callback ou alterar um existente

```php
// project/hooks/controllers/meu-modulo.hooks.php
function minha_funcao_hook(mixed $dado = null): void {
    // lógica do hook
}
```

### Passo 4 — Sincronizar

1. Se for um hook do core do Conn2Flow (módulos) em ambiente de desenvolvimento, execute a tarefa do VS Code:
- **`🛠️ Manager - Update => All - Test Environment`** (para sincronizar os módulos)

2. Se for um hook específico do projeto em ambiente de desenvolvimento (produção), execute a tarefa do VS Code:
- **`🗃️ Projects - Deploy Current Project`** (para sincronizar o projeto)

---

## Boas Práticas

| ✅ Fazer | ❌ Evitar |
|----------|-----------|
| Registrar hooks via JSON | Inserir diretamente na tabela `hooks` |
| Usar prioridades para controlar ordem | Assumir ordem de execução sem prioridade |
| Callbacks sem estado global | Compartilhar estado entre hooks via variáveis globais desnecessariamente |
| Nomes descritivos: `{modulo}_{namespace}_{evento}_hook` | Nomes genéricos: `minha_funcao` |
| Aceitar `mixed ...$args` para compatibilidade futura | Parâmetros obrigatórios sem defaults quando possível evitar |
| Usar `"habilitado": 0` para desativar | Deletar o hook do JSON (perde o registro) |

---

## Arquivos Relevantes

| Arquivo | Função |
|---------|--------|
| `gestor/bibliotecas/hooks.php` | Biblioteca central — `HookManager` e 4 funções globais |
| `gestor/db/migrations/20260630100000_create_hooks_table.php` | Migration da tabela `hooks` |
| `gestor/controladores/atualizacoes/atualizacoes-hooks.php` | Sincronização de hooks no pipeline de atualização |
| `gestor/bibliotecas/interface.php` | Emite hooks nativos da interface (`adicionar.banco`, `editar.pagina`, etc.) |
