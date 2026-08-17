---
name: sdd-workflow
description: Use quando o repositório seguir Spec-Driven Development e a tarefa tocar sdd numerados, batches, reviews, validation, decisions ou change requests.
user-invocable: false
---

# SDD workflow

Use esta skill quando o projeto for guiado por sdd versionados.

## Leitura mínima inicial

Comece por `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.

Se a tarefa apontar para `sdd/human-requests/*.md` ou para a pasta `sdd/human-requests/`, leia primeiro esse intake humano. Quando vier apenas a pasta, use a seguinte ordem determinística:

1. `CURRENT.md`
2. `README.md`
3. o arquivo `.md` mais recente

Depois leia apenas os sdd numerados e arquivos de código que controlam o slice atual.

## Classificação da demanda

1. Mudança de requisito ou contrato:
   - registre em `sdd/change-requests/`
   - avalie impacto nos sdd numerados, decisions, batches e validation
2. Feedback de review sem mudança normativa:
   - registre em `sdd/reviews/`
   - mantenha os sdd numerados estáveis
3. Implementação incremental:
   - confira o batch atual em `sdd/implementation/`
   - implemente o menor slice aprovado
   - valide e atualize `sdd/validation/` quando necessário
4. Validação ou spec drift check:
   - comece pela menor checagem automatizada
   - registre evidência e pendências nos artefatos certos

## Regras de ouro

- Os sdd numerados são a fonte normativa.
- `sdd/human-requests/` nunca é fonte normativa; ele só alimenta change requests, reviews, batches, decisions ou validação.
- Não reescreva os sdd numerados para comentários pequenos de review.
- Não abra o próximo batch antes de o atual estar estável e revisável.