# Signup Hooks User-Profile - Legacy 1 (March 2026)

## Adding hook_do_action and hook_apply_filters to perfil_usuario_signup()

## Context and Objectives

This session was part of the Host Manager v0.3.0 implementation in the conn2flow-site repository. The modifications to the core repository (`conn2flow`) were required to add **extension points** in the user registration function, allowing downstream modules (such as host-manager) to intercept signup without modifying the `perfil-usuario` module code.

The work on the core repository consisted of 2 surgical modifications in a single function.

---

## Detailed Scope

### Modification 1: Action Hook `signup.banco`

**File:** `gestor/modulos/perfil-usuario/perfil-usuario.php`  
**Function:** `perfil_usuario_signup()`

**Problem:** There was no way for external modules to react to new user creation. Signup was a closed process that ended in a fixed redirect.

**Solution:** Added `hook_do_action()` immediately after `banco_last_id()`:

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

**Impact:** None for projects that don't register hooks — `hook_do_action()` is a no-op if no callbacks are registered.

---

### Modification 2: Filter Hook `signup.redirect`

**File:** `gestor/modulos/perfil-usuario/perfil-usuario.php`  
**Function:** `perfil_usuario_signup()`

**Problem:** The post-signup redirect was fixed (`dashboard/` or `redirecionar-local` session). Modules couldn't alter the destination (e.g., redirect to paid plan checkout).

**Solution:** Refactored the redirect logic to resolve the URL to a variable and apply `hook_apply_filters()`:

```php
$signup_redirect = 'dashboard/';

if (existe(gestor_sessao_variavel("redirecionar-local"))) {
    $signup_redirect = gestor_sessao_variavel("redirecionar-local");
    gestor_sessao_variavel_del("redirecionar-local");
}

$signup_redirect = hook_apply_filters('perfil-usuario', 'signup.redirect', $signup_redirect, $id_usuarios);

gestor_redirecionar($signup_redirect);
```

**Impact:** Without registered callbacks, `hook_apply_filters()` returns the original value — behavior identical to before.

**Subtle behavioral change:** The `redirecionar-local` session variable is now resolved **before** the filter (previously it was resolved inside `gestor_redirecionar()`). The session cleanup was done with `gestor_sessao_variavel_del()` inside the conditional block.

---

## Modified File

| File | Change |
|------|--------|
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | +`hook_do_action('perfil-usuario', 'signup.banco', ...)` after `banco_last_id()`; redirect refactor with `hook_apply_filters('perfil-usuario', 'signup.redirect', ...)` |

---

## Design Decisions

- **`gestor_incluir_biblioteca('hooks')` inside the function:** The module may be loaded in contexts where the library isn't available (public pages). The inclusion is idempotent.
- **Data from `$_REQUEST`:** The plan is a user intent (from the form), not a database record. The receiver validates and persists.
- **`banco_escape_field` on email:** Extra sanitization for third-party callbacks that may use the data in queries.

---

## Dependencies

- `gestor/bibliotecas/hooks.php` — HookManager, `hook_do_action()`, `hook_apply_filters()`
- `hooks` database table — records are populated via `atualizacoes_hooks_sincronizar()` during deploy

---

## Session Final State

- ✅ `perfil_usuario_signup()` with hook_do_action (signup.banco)
- ✅ `perfil_usuario_signup()` with hook_apply_filters (signup.redirect)
- ✅ PHP syntax validated
- ⬜ Commit/Push pending

_Detailed Session - Future Agent Reference (Signup Hooks)_
