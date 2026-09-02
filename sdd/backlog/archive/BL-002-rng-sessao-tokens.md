# BL-002 — Geração criptograficamente fraca de IDs de sessão e tokens

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: ALTA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `bibliotecas/gestor.php`, `bibliotecas/oauth2.php`

## Contexto observado

Identificadores sensíveis são gerados com `md5(uniqid(rand(), true))` / `md5(uniqid(mt_rand(), true))`:

- ID de sessão: `gestor_sessao_iniciar()` → `md5(uniqid(rand(), true))` ([gestor.php:1358](../../../gestor/bibliotecas/gestor.php)).
- Cookie de verificação: `gestor_cookie_verificacao()` → `md5(uniqid(rand(), true))` ([gestor.php:1162](../../../gestor/bibliotecas/gestor.php)).
- `pubID` de access/refresh token OAuth2: `md5(uniqid(mt_rand(), true))` ([oauth2.php:89,124](../../../gestor/bibliotecas/oauth2.php)).

`uniqid()` é baseado em timestamp (microtempo) e `rand()`/`mt_rand()` não são CSPRNG. O espaço de entropia real é pequeno e parcialmente previsível — abre margem para predição/fixação de sessão e adivinhação de `pubID` de token.

**Inconsistência interna**: o token CSRF já usa a fonte correta — `bin2hex(random_bytes(32))` em [seguranca.php:109](../../../gestor/bibliotecas/seguranca.php). O padrão seguro já existe no código; falta aplicá-lo aos demais.

## Proposta de melhoria (a validar)

1. Substituir a geração de ID de sessão, cookie-verify e `pubID` por `bin2hex(random_bytes(N))` (ou `random_int`).
2. Centralizar num helper único (ex.: `seguranca_token_aleatorio($bytes)`) e reutilizar em todos os pontos.
3. Avaliar rotação do ID de sessão após login bem-sucedido (mitiga fixação).

## Critérios de aceite (rascunho)

- Nenhum identificador de segurança derivado de `uniqid`/`rand`/`mt_rand`.
- IDs com pelo menos 128 bits de entropia de CSPRNG.
- Cobertura de teste garantindo unicidade/formato.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
