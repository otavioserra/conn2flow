# Hooks Signup Perfil-Usuário - Antigo 1 (Março 2026)

## Adição de hook_do_action e hook_apply_filters em perfil_usuario_signup()

## Contexto e Objetivos

Esta sessão fez parte da implementação da v0.3.0 do Host Manager no repositório conn2flow-site. As modificações no repositório core (`conn2flow`) foram necessárias para adicionar **pontos de extensão** na função de cadastro de usuários, permitindo que módulos downstream (como o host-manager) interceptem o signup sem modificar o código do módulo `perfil-usuario`.

O trabalho no repositório core consistiu em 2 modificações cirúrgicas numa única função.

---

## Escopo Detalhado Realizado

### Modificação 1: Action Hook `signup.banco`

**Arquivo:** `gestor/modulos/perfil-usuario/perfil-usuario.php`  
**Função:** `perfil_usuario_signup()`

**Problema:** Não havia como módulos externos reagirem à criação de um novo usuário. O signup era um processo fechado que terminava num redirect fixo.

**Solução:** Adicionado `hook_do_action()` imediatamente após `banco_last_id()`:

```php
gestor_incluir_biblioteca('hooks');

hook_do_action('perfil-usuario', 'signup.banco', $id_usuarios, [
    'nome'   => $nome,
    'email'  => banco_escape_field($_REQUEST['email']),
    'id'     => $id,
    'plano'  => $_REQUEST['plano'] ?? null,
    'domain' => $_REQUEST['domain'] ?? null,
]);
```

**Impacto:** Nenhum para projetos que não registram hooks — `hook_do_action()` é no-op se nenhum callback está registrado.

---

### Modificação 2: Filter Hook `signup.redirect`

**Arquivo:** `gestor/modulos/perfil-usuario/perfil-usuario.php`  
**Função:** `perfil_usuario_signup()`

**Problema:** O redirect pós-cadastro era fixo (`dashboard/` ou sessão `redirecionar-local`). Módulos não podiam alterar o destino (ex: redirecionar para checkout de plano pago).

**Solução:** Refatorada a lógica de redirect para resolver a URL numa variável e aplicar `hook_apply_filters()`:

```php
$signup_redirect = 'dashboard/';

if (existe(gestor_sessao_variavel("redirecionar-local"))) {
    $signup_redirect = gestor_sessao_variavel("redirecionar-local");
    gestor_sessao_variavel_del("redirecionar-local");
}

$signup_redirect = hook_apply_filters('perfil-usuario', 'signup.redirect', $signup_redirect, $id_usuarios);

gestor_redirecionar($signup_redirect);
```

**Impacto:** Sem callbacks registrados, `hook_apply_filters()` retorna o valor original — comportamento idêntico ao anterior.

**Mudança comportamental sutil:** A sessão `redirecionar-local` agora é resolvida **antes** do filter (anteriormente era resolvida dentro do `gestor_redirecionar()`). A limpeza da sessão foi feita com `gestor_sessao_variavel_del()` dentro do bloco condicional.

---

## Arquivo Modificado

| Arquivo | Mudança |
|---------|---------|
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | +`hook_do_action('perfil-usuario', 'signup.banco', ...)` após `banco_last_id()`; refator de redirect com `hook_apply_filters('perfil-usuario', 'signup.redirect', ...)` |

---

## Decisões de Design

- **`gestor_incluir_biblioteca('hooks')` dentro da função:** O módulo pode ser carregado em contextos onde a biblioteca não está disponível (páginas públicas). A inclusão é idempotente.
- **Dados de `$_REQUEST`:** O plano é uma intenção do usuário (vem do form), não um registro no banco. O receptor valida e persiste.
- **`banco_escape_field` no email:** Sanitização extra para callbacks de terceiros que podem usar o dado em queries.

---

## Dependências

- `gestor/bibliotecas/hooks.php` — HookManager, `hook_do_action()`, `hook_apply_filters()`
- Tabela `hooks` no banco — registros são populados via `atualizacoes_hooks_sincronizar()` durante deploy

---

## Estado ao Final da Sessão

- ✅ `perfil_usuario_signup()` com hook_do_action (signup.banco)
- ✅ `perfil_usuario_signup()` com hook_apply_filters (signup.redirect)
- ✅ Sintaxe PHP validada
- ⬜ Commit/Push pendente

_Sessão Detalhada - Referência para Agente Futuro (Hooks Signup)_
