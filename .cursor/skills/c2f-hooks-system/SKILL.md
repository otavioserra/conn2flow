---
name: c2f-hooks-system
description: Use ao emitir, filtrar ou registrar eventos do sistema de Hooks Conn2Flow (Actions, Filters, JSON de hooks e controllers em PHP).
user-invocable: false
---

# Sistema de Hooks do Conn2Flow (`gestor/bibliotecas/hooks.php`)

Consulte e aplique as seguintes convenções ao trabalhar com o sistema de Actions e Filters no Conn2Flow:

## 1. Conceito Central: Actions vs. Filters

* **Actions (`hook_do_action`)**: Usado para efeitos colaterais (logs, notificações, widgets). Não possui retorno.
  ```php
  hook_do_action('admin-paginas', 'adicionar.banco', $id, $dados);
  ```
* **Filters (`hook_apply_filters`)**: Usado para transformação de dados. Retorna o valor modificado.
  ```php
  $titulo = hook_apply_filters('admin-paginas', 'titulo.salvar', $titulo_bruto);
  ```
* **Verificações Prévias**: `hook_has_actions('namespace', 'evento')` e `hook_has_filters('namespace', 'evento')`.

---

## 2. Eventos Nativos de Interface (`interface.php`)

A plataforma dispara hooks automáticos para os módulos que usam o sistema padrão de interface:
* `adicionar.pre-banco`, `adicionar.banco` (`$id`, `$dados`), `adicionar.parametros`, `adicionar.pagina`.
* `editar.pre-banco`, `editar.banco` (`$id`, `$dados`), `editar.parametros`, `editar.pagina`.
* `excluir.banco` (`$id`), `status.banco` (`$id`, `$novoStatus`), `clonar.banco` (`$id`, `$dados`).

---

## 3. Registro de Hooks via JSON (Fonte Única da Verdade)

**Nunca insira diretamente na tabela `hooks`** — a tabela é sobrescrita pela sincronização `atualizacoes_hooks_sincronizar()`.

### A. Em Módulos (`modulos/<modulo>/<modulo>.json`):
```json
{
    "hooks": {
        "controllers": {
            "admin-paginas": "meu-modulo.hooks.php"
        },
        "actions": {
            "admin-paginas": {
                "adicionar.banco": {
                    "callback": "meu_modulo_page_added_hook",
                    "prioridade": 5,
                    "habilitado": 1
                }
            }
        },
        "filters": {}
    }
}
```

### B. No Projeto (`project/hooks/hooks.json`):
```json
{
    "controllers": {
        "admin-paginas": "admin-paginas.hooks.php"
    },
    "actions": {
        "admin-paginas": {
            "adicionar.pagina": "projeto_page_added_hook"
        }
    },
    "filters": {}
}
```

---

## 4. Estrutura dos Controladores PHP (Callbacks)

* No Projeto: `project/hooks/controllers/<namespace>.hooks.php`.
* Nos Módulos: `modulos/<modulo>/<modulo>.hooks.php`.

Exemplo de callback:
```php
function meu_modulo_page_added_hook(string $id, array $dados = []): void {
    global $_GESTOR;
    // Lógica do hook sem alterar o módulo emissor
}
```

---

## 5. Boas Práticas
* **Sincronização Idempotente**: Execute `atualizacoes_hooks_sincronizar()` (ou via tarefas de deploy) para aplicar alterações de JSON no banco.
* **Desativação Temporária**: Use `"habilitado": 0` no JSON para desativar um hook sem remover o registro.
* **Wildcard `*`**: Use `*` no namespace para registrar ouvintes globais em qualquer módulo.
