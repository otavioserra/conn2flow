# 01 Workflow

Este arquivo descreve como uma demanda deve transitar entre intake humano, mudança normativa, implementação, review e validação dentro do `conn2flow`.

## Fluxo padrão

1. Entrada em `sdd/human-requests/`
2. Classificação da demanda
3. Mudança normativa ou abertura de batch
4. Implementação incremental
5. Review findings-first
6. Validação e fechamento

## Como classificar a demanda

### Caso 1: muda requisito, contrato ou critério de aceite

- registrar primeiro em `sdd/change-requests/`
- atualizar sdd numerados apenas depois da mudança ficar clara
- registrar impacto em `sdd/decisions/DECISION-LOG.md` quando houver decisão estrutural

### Caso 2: não muda requisito, só implementa um slice aprovado

- registrar ou continuar um batch em `sdd/implementation/`
- atualizar `sdd/implementation/BATCH-INDEX.md`
- implementar o menor slice plausível

### Caso 3: precisa de review

- fazer review findings-first
- se precisar persistir o round, registrar em `sdd/reviews/`
- não reescrever spec numerado por feedback pequeno de review

### Caso 4: precisa apenas validar

- usar `sdd/validation/VALIDATION-CHECKLIST.md`
- registrar evidência, pendências e regressão observada

## Regras de transição

- `human-requests/` nunca é fonte normativa
- `change-requests/` existe para mudanças antes de tocar sdd numerados
- `implementation/` controla batches pequenos e revisáveis
- `reviews/` armazena feedback e findings quando esse material precisar persistir
- `validation/` concentra checklist e evidência de aceite
- `decisions/` registra racional e exceções estruturais

## Regra especial deste repositório

Como o `conn2flow` já possui um legado amplo e operacional, toda mudança deve ser comparada contra `sdd/00-baseline-architecture.md` antes de propor consolidações, remoções amplas ou simplificações de fluxo.

## Estado atual do workflow

- `BATCH-000`: onboarding do SDD repo-wide concluído
- próximo passo de negócio esperado: intake do `Plano 1` em `sdd/human-requests/`
