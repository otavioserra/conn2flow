---
name: 'Spec-Driven MDD'
description: 'Use ao editar sdd, reviews, batches, decisions, validation ou change requests em repositórios MDD.'
applyTo: 'memory/**/*.md'
---

- Os sdd numerados são a fonte normativa.
- Trate `memory/human-requests/` apenas como intake humano não normativo; qualquer consolidação deve ir para `change-requests/`, `reviews/`, `implementation/`, `validation/`, `decisions/` ou sdd numerados quando aprovado.
- Use `memory/change-requests/` para mudança de requisito, `memory/reviews/` para feedback de round, `memory/implementation/` para batches, `memory/validation/` para evidências e `memory/decisions/` para racional.
- Não reescreva os sdd numerados para comentários de review que não mudam o requisito.
- No início de cada sessão, leia `memory/03-memory-engineering-chief.md` e `memory/04-memory-engineering-execution.md` para alinhar contexto.
- Ao término de cada tarefa, atualize `memory/04-memory-engineering-execution.md` com aprendizados, bugs resolvidos e particularidades do ambiente. Nunca modifique `memory/03-memory-engineering-chief.md` sem instrução explícita do usuário humano.

## Otimização de Contexto e Arquivamento

- Mantenha `memory/decisions/DECISION-LOG.md`, `memory/implementation/BATCH-INDEX.md` e `memory/validation/VALIDATION-CHECKLIST.md` com no máximo 10 itens correntes ou ativos.
- Mantenha também `memory/human-requests/` enxuto, preservando no máximo 10 requisições correntes ou recentes fora de `archive/`.
- Mova históricos antigos para a subpasta `archive/` correspondente: `memory/decisions/archive/`, `memory/human-requests/archive/`, `memory/implementation/archive/` ou `memory/validation/archive/`.
- Nos arquivos principais, substitua o histórico arquivado por tabelas Markdown resumidas com 1 linha por item e link direto para o arquivo em `archive/`.
- Ao carregar contexto inicial, priorize os arquivos principais e abra itens em `archive/` apenas quando o batch, a requisição ativa ou um link de rastreabilidade exigir.
