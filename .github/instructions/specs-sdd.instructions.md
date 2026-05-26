---
name: 'Spec-Driven Specs'
description: 'Use ao editar specs, reviews, batches, decisions, validation ou change requests em repositórios SDD.'
applyTo: 'specs/**/*.md'
---

- Os specs numerados são a fonte normativa.
- Trate `specs/human-requests/` apenas como intake humano não normativo; qualquer consolidação deve ir para `change-requests/`, `reviews/`, `implementation/`, `validation/`, `decisions/` ou specs numerados quando aprovado.
- Use `specs/change-requests/` para mudança de requisito, `specs/reviews/` para feedback de round, `specs/implementation/` para batches, `specs/validation/` para evidências e `specs/decisions/` para racional.
- Não reescreva os specs numerados para comentários de review que não mudam o requisito.