# 01 Workflow

Este arquivo descreve como uma demanda deve transitar entre intake humano, mudanÃ§a normativa, implementaÃ§Ã£o, review e validaÃ§Ã£o dentro do `conn2flow`.

## Fluxo padrÃ£o

1. Entrada em `sdd/human-requests/`
2. ClassificaÃ§Ã£o da demanda
3. MudanÃ§a normativa ou abertura de batch
4. ImplementaÃ§Ã£o incremental
5. Review findings-first
6. ValidaÃ§Ã£o e fechamento

## Como classificar a demanda

### Caso 1: muda requisito, contrato ou critÃ©rio de aceite

- registrar primeiro em `sdd/change-requests/`
- atualizar sdd numerados apenas depois da mudanÃ§a ficar clara
- registrar impacto em `sdd/decisions/DECISION-LOG.md` quando houver decisÃ£o estrutural

### Caso 2: nÃ£o muda requisito, sÃ³ implementa um slice aprovado

- registrar ou continuar um batch em `sdd/implementation/`
- atualizar `sdd/implementation/BATCH-INDEX.md`
- implementar o menor slice plausÃ­vel

### Caso 3: precisa de review

- fazer review findings-first
- se precisar persistir o round, registrar em `sdd/reviews/`
- nÃ£o reescrever spec numerado por feedback pequeno de review

### Caso 4: precisa apenas validar

- usar `sdd/validation/VALIDATION-CHECKLIST.md`
- registrar evidÃªncia, pendÃªncias e regressÃ£o observada

## Regras de transiÃ§Ã£o

- `human-requests/` nunca Ã© fonte normativa
- `change-requests/` existe para mudanÃ§as antes de tocar sdd numerados
- `implementation/` controla batches pequenos e revisÃ¡veis
- `reviews/` armazena feedback e findings quando esse material precisar persistir
- `validation/` concentra checklist e evidÃªncia de aceite
- `decisions/` registra racional e exceÃ§Ãµes estruturais

## Regra especial deste repositÃ³rio

Como o `conn2flow` jÃ¡ possui um legado amplo e operacional, toda mudanÃ§a deve ser comparada contra `sdd/00-baseline-architecture.md` antes de propor consolidaÃ§Ãµes, remoÃ§Ãµes amplas ou simplificaÃ§Ãµes de fluxo.

## Estado atual do workflow

- `BATCH-000`: onboarding do SDD repo-wide concluÃ­do
- prÃ³ximo passo de negÃ³cio esperado: intake do `Plano 1` em `sdd/human-requests/`
