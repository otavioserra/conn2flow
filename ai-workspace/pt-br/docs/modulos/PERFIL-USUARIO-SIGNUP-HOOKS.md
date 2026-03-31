# Hooks de Signup no Módulo perfil-usuario

## Visão Geral

A partir da v0.3.0 do Host Manager, o módulo `perfil-usuario` (core) ganhou **dois pontos de extensão** na função `perfil_usuario_signup()` — um action hook e um filter hook. Eles permitem que qualquer módulo ou projeto downstream intercepte o processo de cadastro de novos usuários para executar lógica adicional (criação de contas, provisionamento de recursos, etc.) e, opcionalmente, alterar a URL de redirecionamento pós-cadastro.

Nenhuma funcionalidade existente foi alterada. Os hooks são **transparentes** — se nenhum callback estiver registrado, o comportamento é idêntico ao anterior.

---

## Hooks Adicionados

### Action: `perfil-usuario/signup.banco`

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

**Posição no código:** Imediatamente após `$id_usuarios = banco_last_id()` — o usuário já existe no banco com ID válido.

**Momento no fluxo:**
```
Validação → reCAPTCHA → Criar Usuário → banco_last_id()
    ↓
    ★ hook_do_action('perfil-usuario', 'signup.banco', ...)
    ↓
Planos (desativado) → Hosts (desativado) → Login → Token → Email → Redirect
```

**Argumentos passados:**

| Arg | Tipo | Descrição |
|-----|------|-----------|
| `$id_usuarios` | int | ID numérico do registro recém-criado |
| `$dados['nome']` | string | Nome completo (já sanitizado) |
| `$dados['email']` | string | Email (sanitizado via `banco_escape_field`) |
| `$dados['id']` | string | Slug gerado pelo `banco_identificador()` |
| `$dados['plano']` | string\|null | Parâmetro `$_REQUEST['plano']` (pode ser null) |
| `$dados['domain']` | string\|null | Parâmetro `$_REQUEST['domain']` (pode ser null) |

**Casos de uso previstos:**
- Criar conta de hospedagem vinculada ao novo usuário
- Registrar o usuário em sistemas externos (CRM, mailing list)
- Enviar notificações internas para admins
- Inicializar dados padrão para o novo usuário

---

### Filter: `perfil-usuario/signup.redirect`

```php
$signup_redirect = 'dashboard/';

if (existe(gestor_sessao_variavel("redirecionar-local"))) {
    $signup_redirect = gestor_sessao_variavel("redirecionar-local");
    gestor_sessao_variavel_del("redirecionar-local");
}

$signup_redirect = hook_apply_filters('perfil-usuario', 'signup.redirect', $signup_redirect, $id_usuarios);

gestor_redirecionar($signup_redirect);
```

**Posição no código:** Logo antes do `gestor_redirecionar()` final — é a última operação da função antes do redirect HTTP.

**Momento no fluxo:**
```
Login → Token → Email
    ↓
    ★ hook_apply_filters('perfil-usuario', 'signup.redirect', $url, $id)
    ↓
gestor_redirecionar($url)
```

**Argumentos:**

| Arg | Tipo | Descrição |
|-----|------|-----------|
| `$signup_redirect` | string | URL atual de redirecionamento |
| `$id_usuarios` | int | ID do usuário |
| **Retorno** | string | URL (modificada ou não) |

**Lógica de prioridade da URL:**
1. Se existe `redirecionar-local` na sessão → usa essa URL (e a limpa)
2. Senão → usa `dashboard/`
3. O filter pode sobrescrever qualquer uma dessas

**Mudança em relação ao comportamento anterior:**

| Antes (v0.2.0) | Depois (v0.3.0) |
|-----------------|-----------------|
| `if (existe(sessao)) { gestor_redirecionar(); } else { gestor_redirecionar('dashboard/'); }` | Resolve `$signup_redirect` → aplica filter → `gestor_redirecionar($signup_redirect)` |

A sessão `redirecionar-local` continua sendo respeitada — agora é resolvida **antes** do filter, que pode opcionalmente substituí-la.

---

## Decisões de Design

### Por que `gestor_incluir_biblioteca('hooks')` dentro da função?

O módulo `perfil-usuario` pode ser carregado em contextos onde a biblioteca de hooks ainda não foi incluída (ex: páginas públicas sem módulos admin). O `gestor_incluir_biblioteca('hooks')` é **idempotente** — se já foi incluída, não tem efeito.

### Por que os dados do plano vêm do `$_REQUEST`, não do banco?

Neste ponto do fluxo, o plano é apenas uma **intenção** — o usuário selecionou o plano na UI, mas nenhum registro de plano foi criado no banco (os blocos de planos/hosts estão desativados: `$desativado_planos = true`). O receptor do hook (ex: host-manager) é quem valida e persiste a informação.

### Por que `banco_escape_field` no email?

O email já passou pelo `interface_validacao_campos_obrigatorios()` com regra `email-obrigatorio`, mas como estamos passando o dado via array para callbacks de terceiros, o escape extra garante que o dado está sanitizado contra SQL injection em qualquer uso posterior.

---

## Compatibilidade

- **Sem hooks registrados:** Comportamento idêntico ao anterior — `hook_do_action()` é no-op, `hook_apply_filters()` retorna o valor original.
- **Sem biblioteca hooks:** Se o `gestor_incluir_biblioteca('hooks')` falhar por algum motivo, as funções `hook_do_action()` e `hook_apply_filters()` não existirão e causarão fatal error. Risco mitigado porque a biblioteca hooks é parte do core e sempre disponível.

---

## Arquivo Modificado

| Arquivo | Mudança |
|---------|---------|
| `gestor/modulos/perfil-usuario/perfil-usuario.php` | Adicionados `hook_do_action('perfil-usuario', 'signup.banco', ...)` e `hook_apply_filters('perfil-usuario', 'signup.redirect', ...)` na função `perfil_usuario_signup()` |

---

*Documentação gerada pelo agente Conn2Flow em 31/03/2026.*
