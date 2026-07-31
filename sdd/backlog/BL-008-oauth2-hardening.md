# BL-008 — Hardening do OAuth2: validação de token e limite de sessões

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `bibliotecas/oauth2.php`

## Contexto observado

1. **`pubIDValidation` (HMAC) não é conferido na validação do access token**: `oauth2_validar_token()` valida a assinatura JWT e confere existência na tabela, mas **não** recomputa/compara o `pubIDValidation` ([oauth2.php:259-320](../../gestor/bibliotecas/oauth2.php)). Já o fluxo de refresh **confere** o HMAC ([oauth2.php:460-468](../../gestor/bibliotecas/oauth2.php)). Assimetria: a defesa extra existe mas não é aplicada no caminho mais usado (validação de acesso).
2. **Limite de 5 tokens ativos nega login em vez de rotacionar**: `oauth2_gerar_token_client_credentials()` retorna `false` quando o usuário atinge `maximo-tokens-usuario` ([oauth2.php:53-63](../../gestor/bibliotecas/oauth2.php)). Na prática, logins repetidos (multi-dispositivo, app mobile do BATCH-008) passam a falhar silenciosamente até os tokens expirarem.

## Proposta de melhoria (a validar)

1. Conferir `pubIDValidation` também em `oauth2_validar_token()` (paridade com o refresh), rejeitando tokens cujo HMAC não bate mesmo com JWT válido.
2. Ao atingir o limite de tokens, **revogar o mais antigo** (rotação FIFO) em vez de negar o login — ou tornar o comportamento configurável e retornar erro semântico claro (já parcialmente tratado no `api-auth.php` do BATCH-008).
3. Avaliar vincular o token a device/fingerprint para revogação seletiva.

## Critérios de aceite (rascunho)

- Access token com HMAC inválido é rejeitado mesmo com assinatura JWT válida.
- Novo login sob limite de sessões não falha silenciosamente.
- Testes cobrindo validação e rotação.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
