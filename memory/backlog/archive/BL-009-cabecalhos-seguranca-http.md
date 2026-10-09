# BL-009 — Ausência de cabeçalhos de segurança HTTP

- **Tipo**: Security
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA
- **Origem**: análise de segurança 2026-07-31 (código real)
- **Componentes**: `gestor.php` (bootstrap de resposta), respostas de página do painel/site

## Contexto observado

Busca em todo o `gestor/` por `Content-Security-Policy`, `X-Frame-Options`, `Strict-Transport-Security`, `X-Content-Type-Options`, `Referrer-Policy` e `Permissions-Policy` retorna **zero ocorrências**. O sistema não emite cabeçalhos de segurança de resposta.

Impacto:
- Sem `X-Frame-Options`/CSP `frame-ancestors`: risco de clickjacking no painel (agravado pelo módulo distribuído que usa iframes — precisa de allow-list, não ausência total).
- Sem `Content-Security-Policy`: XSS explora sem contenção de origem de script.
- Sem `X-Content-Type-Options: nosniff`: MIME sniffing.
- Sem HSTS: downgrade para HTTP.

## Proposta de melhoria (a validar)

1. Emitir um conjunto base de cabeçalhos no bootstrap de resposta HTML: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options`/CSP `frame-ancestors` coerente com o iframe do módulo distribuído, e `Strict-Transport-Security` quando em HTTPS.
2. Introduzir CSP inicialmente em modo `Report-Only` para medir quebras antes de forçar.
3. Tornar os valores configuráveis por `.env` (o site público e o painel podem ter políticas distintas).

## Critérios de aceite (rascunho)

- Respostas HTML do painel e do site trazem os cabeçalhos base.
- CSP validada em Report-Only sem quebrar o Editor Visual/iframe distribuído antes de enforce.
- Configurável por ambiente.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
