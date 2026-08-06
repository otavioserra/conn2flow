# BL-004 — Proteção CSRF existe mas não é aplicada (código morto)

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA/ALTA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `bibliotecas/seguranca.php`, formulários/AJAX autenticados, `bibliotecas/formulario.php`, `bibliotecas/interface*.php`

## Contexto observado

A biblioteca `seguranca.php` implementa `gestor_csrf_token()` e `gestor_csrf_validar()` corretamente (token de 32 bytes, `hash_equals`) ([seguranca.php:105-128](../../gestor/bibliotecas/seguranca.php)). Porém, uma busca por uso em todo o `gestor/` retorna **apenas o próprio arquivo de definição** — nenhuma rota, formulário ou endpoint AJAX autenticado chama `gestor_csrf_validar()`.

Ou seja, a defesa CSRF está pronta mas **desligada**. As ações de estado (mudança de dados no painel, AJAX autenticado por cookie) dependem só do cookie de sessão `SameSite=Lax`. `Lax` mitiga parte dos vetores, mas não cobre POST cross-site em todos os cenários nem navegadores antigos.

## Proposta de melhoria (a validar)

1. Emitir o token CSRF no render de formulários/painel e num meta/JS acessível ao AJAX autenticado.
2. Validar `gestor_csrf_validar()` no fechamento AJAX genérico e no processamento de formulários que alteram estado.
3. Isentar explicitamente apenas endpoints máquina-a-máquina que já usam Bearer/HMAC (API `_api`, canal distribuído).
4. Reforçar `SameSite` para `Strict` onde o fluxo permitir.

## Critérios de aceite (rascunho)

- Ações de estado autenticadas por cookie exigem token CSRF válido.
- Endpoints Bearer/HMAC seguem funcionando sem CSRF (documentado).
- Teste cobrindo rejeição de POST sem token e aceite com token.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
