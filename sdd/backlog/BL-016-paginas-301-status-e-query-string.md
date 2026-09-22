# BL-016 — Redirecionamento de `paginas_301` descarta a query string

- **Tipo**: Bug / Roteamento / SEO
- **Status**: PROMOTED (promovido para [req-173.md](../human-requests/req-173.md), BATCH-178)
- **Severidade sugerida**: BAIXA (a URL aposentada redireciona certo, mas perde os parâmetros)
- **Origem**: Análise da REQ-054 do `conn2flow-site` (descomissionamento do Pro Manager), 2026-09-22
- **Componentes**: `gestor/gestor.php` (roteador, ~linha 2240; `gestor_roteador_erro()`),
  `gestor/bibliotecas/gestor.php` (`gestor_redirecionar()`, linha 2477)

## Contexto observado

1. A tabela `paginas_301` associa um caminho antigo a uma página (`id_paginas`). O roteador, ao não achar o
   caminho, consulta essa tabela e chama `gestor_roteador_erro(['codigo' => 301, 'redirect' => <caminho novo>])`.
2. O status HTTP está correto: `gestor_roteador_erro()` chama `http_response_code(301)` antes do
   `Location`. Medido no Lab em 2026-09-22: `/pro-checkout/` responde **301**.
3. O roteador não repassa `querystring` para `gestor_roteador_erro()`, então `?plan=starter` é descartado:
   `/pro-checkout/?plan=starter` chega ao destino sem o plano.

## Por que importa

Uma URL aposentada que carregava contexto (`?plan=`, `?ref=`, UTM de campanha) entrega o visitante numa
página genérica. Quem clicou num anúncio de um plano específico cai na lista de planos, e a origem da
campanha se perde na analítica.

## Proposta

Repassar a query string original no redirecionamento de `paginas_301` (o roteador já tem `gestor_querystring()`
e `gestor_roteador_erro()` já aceita a chave `querystring`; falta passá-la nesse ponto).

## Critérios de aceite (rascunho)

- A query string da requisição original é preservada no `Location` (o status 301 já está correto).
- Os demais usos de `gestor_redirecionar()` continuam respondendo como hoje.
- Teste automatizado do redirecionamento com e sem query string.
