# BL-010 — Débito de migração (bibliotecas paralelas v1/v2) e código morto

- **Tipo**: Architecture / Maintainability
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA (manutenibilidade / risco indireto de segurança)
- **Origem**: análise sistêmica 2026-07-31 (código real)
- **Componentes**: `bibliotecas/` e `modulos/` do core

## Contexto observado

Convivem versões paralelas de componentes centrais, sinal de migrações inacabadas:

- `banco.php` (1627 linhas, concatenação) **e** `banco-v2.php` (2453 linhas, PDO/prepared).
- `interface.php` (5674 linhas) **e** `interface-v2.php` (3014 linhas).
- `admin-paginas` **e** `admin-paginas-v2`.

Consequências: dois padrões de acesso a dados e de UI ativos ao mesmo tempo, dobrando a superfície de manutenção e dificultando aplicar correções de segurança de forma consistente (uma correção precisa ser feita em dois lugares, ou é esquecida em um).

Há também **código morto/legado** que confunde a leitura de segurança — ex.: `banco_smartstripslashes()` tem todo o corpo comentado e só faz `return (string)$str` ([banco.php:57-71](../../../gestor/bibliotecas/banco.php)); o parâmetro `["cost" => 9]` passado a `password_hash(..., PASSWORD_ARGON2I, ...)` é ignorado pelo Argon2i (cost é do bcrypt) ([perfil-usuario.php:494](../../../gestor/modulos/perfil-usuario/perfil-usuario.php)).

## Proposta de melhoria (a validar)

1. Definir a versão canônica de cada par (provável: `-v2`) e um plano de migração incremental com data de corte para aposentar a antiga.
2. Registrar em `DECISION-LOG` qual versão é a alvo e marcar a legada como deprecada (sem novas features).
3. Remover código morto e parâmetros ignorados; ajustar `password_hash` para Argon2id com `memory_cost`/`time_cost`/`threads` explícitos.
4. Mapear os consumidores de cada lib para dimensionar a migração antes de qualquer batch.

## Critérios de aceite (rascunho)

- Decisão registrada sobre a versão canônica de cada par v1/v2.
- Plano incremental de migração com inventário de consumidores.
- Código morto removido; hashing de senha com parâmetros efetivos.

> Item de backlog — não autorizado para implementação até promoção para `sdd/human-requests/`.
