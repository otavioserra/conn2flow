---
name: sdd-workflow
description: Use quando o repositÃ³rio seguir Spec-Driven Development e a tarefa tocar sdd numerados, batches, reviews, validation, decisions ou change requests.
user-invocable: false
---

# SDD workflow

Use esta skill quando o projeto for guiado por sdd versionados.

## Leitura mÃ­nima inicial

Comece por `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.

Se a tarefa apontar para `sdd/human-requests/*.md` ou para a pasta `sdd/human-requests/`, leia primeiro esse intake humano. Quando vier apenas a pasta, use a seguinte ordem determinÃ­stica:

1. `CURRENT.md`
2. `README.md`
3. o arquivo `.md` mais recente

Depois leia apenas os sdd numerados e arquivos de cÃ³digo que controlam o slice atual.

## ClassificaÃ§Ã£o da demanda

1. MudanÃ§a de requisito ou contrato:
   - registre em `sdd/change-requests/`
   - avalie impacto nos sdd numerados, decisions, batches e validation
2. Feedback de review sem mudanÃ§a normativa:
   - registre em `sdd/reviews/`
   - mantenha os sdd numerados estÃ¡veis
3. ImplementaÃ§Ã£o incremental:
   - confira o batch atual em `sdd/implementation/`
   - implemente o menor slice aprovado
   - valide e atualize `sdd/validation/` quando necessÃ¡rio
4. ValidaÃ§Ã£o ou spec drift check:
   - comece pela menor checagem automatizada
   - registre evidÃªncia e pendÃªncias nos artefatos certos

## Regras de ouro

- Os sdd numerados sÃ£o a fonte normativa.
- `sdd/human-requests/` nunca Ã© fonte normativa; ele sÃ³ alimenta change requests, reviews, batches, decisions ou validaÃ§Ã£o.
- NÃ£o reescreva os sdd numerados para comentÃ¡rios pequenos de review.
- NÃ£o abra o prÃ³ximo batch antes de o atual estar estÃ¡vel e revisÃ¡vel.
