---
name: 'Spec-Driven SDD'
description: 'Use ao editar sdd, reviews, batches, decisions, validation ou change requests em repositórios SDD.'
applyTo: 'sdd/**/*.md'
---

- Os sdd numerados são a fonte normativa.
- Trate `sdd/human-requests/` apenas como intake humano não normativo; qualquer consolidação deve ir para `change-requests/`, `reviews/`, `implementation/`, `validation/`, `decisions/` ou sdd numerados quando aprovado.
- Use `sdd/change-requests/` para mudança de requisito, `sdd/reviews/` para feedback de round, `sdd/implementation/` para batches, `sdd/validation/` para evidências e `sdd/decisions/` para racional.
- Não reescreva os sdd numerados para comentários de review que não mudam o requisito.
- No início de cada sessão, leia `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` e `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` para alinhar contexto.
- Ao término de cada tarefa, atualize `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` com aprendizados, bugs resolvidos e particularidades do ambiente. Nunca modifique `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` sem instrução explícita do usuário humano.

## Otimização de Contexto e Arquivamento

- Mantenha `sdd/decisions/DECISION-LOG.md`, `sdd/implementation/BATCH-INDEX.md` e `sdd/validation/VALIDATION-CHECKLIST.md` com no máximo 10 itens correntes ou ativos.
- Mantenha também `sdd/human-requests/` enxuto, preservando no máximo 10 requisições correntes ou recentes fora de `archive/`.
- Mova históricos antigos para a subpasta `archive/` correspondente: `sdd/decisions/archive/`, `sdd/human-requests/archive/`, `sdd/implementation/archive/` ou `sdd/validation/archive/`.
- Nos arquivos principais, substitua o histórico arquivado por tabelas Markdown resumidas com 1 linha por item e link direto para o arquivo em `archive/`.
- Ao carregar contexto inicial, priorize os arquivos principais e abra itens em `archive/` apenas quando o batch, a requisição ativa ou um link de rastreabilidade exigir.
