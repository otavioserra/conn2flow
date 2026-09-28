---
name: c2f-hooks-system
description: "LEIA ANTES de criar, interceptar ou disparar ações (Actions) e filtros (Filters) de extensibilidade. Se não ler: hooks não disparam por registro ausente em hooks.json, parâmetros são perdidos ou causam efeitos colaterais silenciosos."
user-invocable: false
---

# Sistema de Hooks do Conn2Flow (`gestor/bibliotecas/hooks.php`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar ou consumir pontos de extensão via `hook_do_action()` ou `hook_apply_filters()`, ou registrar manipuladores em `hooks.json` / `project/hooks/`.
- **SKIP APENAS SE**: Código interno fechado de um módulo sem necessidade de interoperabilidade com outros módulos ou plugins.
- **CONSEQUÊNCIA DE IGNORAR**: Filtros ignorados silenciosamente por falta de registro em `hooks.json`, corrupção do retorno de filtros ou acoplamento monolítico indevido.

---

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

---

## 6. Sincronização de Hooks no Deploy e Comando Dedicado

### 6.1 Sincronização Automática no Pipeline de Deploy

- A tabela `hooks` do banco de dados é sincronizada automaticamente durante a etapa de banco de dados (`atualizacoes-banco-de-dados.php`) executada por:
  - `./c2f project:update-all <projeto-id>` (pipeline completo de um projeto)
  - `./c2f manager:update-all` (pipeline completo de todos os projetos)
- A função `atualizacoes_hooks_sincronizar()` é chamada internamente, lendo todos os `hooks.json` de módulos e do projeto, e recriando a tabela `hooks` com os registros atualizados.
- **Idempotência Garantida**: A sincronização é idempotente — executar múltiplas vezes produz o mesmo resultado. A tabela é reconstruída a partir dos JSONs, que são a fonte da verdade.

### 6.2 Comando Dedicado `c2f project:sync-hooks <projeto-id>`

- Para atualizar hooks **sem executar o pipeline completo** (sem migrações, sem CSS rebuild, sem resources:sync), use:
```bash
./c2f project:sync-hooks <projeto-id>
```
- Este comando foi implementado na REQ-174/BATCH-179 especificamente para permitir atualizações isoladas de hooks durante desenvolvimento.
- **Caso de Uso Principal**: Após editar um `hooks.json` de módulo ou do projeto, rodar apenas `project:sync-hooks` para refletir as mudanças no banco sem o overhead do pipeline completo.
- **Verificação Rápida**: Após a sincronização, confirme no banco:
```sql
SELECT * FROM hooks WHERE namespace = 'meu-modulo' ORDER BY prioridade;
```

> [!IMPORTANT]
> Nunca insira ou atualize registros diretamente na tabela `hooks` via SQL. A tabela é **sobrescrita integralmente** a cada sincronização. Alterações manuais serão perdidas no próximo deploy.
