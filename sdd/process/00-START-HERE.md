# 00 Start Here

Use este arquivo como ponto de partida para qualquer nova demanda dentro do `conn2flow` após a implantação do SDD repo-wide.

## Passo 1: resolver a origem da demanda

Se a demanda chegar por `sdd/human-requests/`, trate esse material apenas como intake humano não normativo.

Regra de resolução da pasta:

1. `sdd/human-requests/CURRENT.md`
2. `sdd/human-requests/README.md`
3. o arquivo `.md` mais recente

## Passo 2: leitura mínima obrigatória

1. `sdd/README.md`
2. `sdd/00-baseline-architecture.md`
3. `sdd/process/01-WORKFLOW.md`
4. `sdd/implementation/BATCH-INDEX.md`
5. `sdd/validation/VALIDATION-CHECKLIST.md`
6. `sdd/decisions/DECISION-LOG.md`

## Passo 3: classificar cedo

Classifique a demanda antes de editar código:

- mudança normativa: abrir em `sdd/change-requests/`
- implementação incremental: abrir ou continuar batch em `sdd/implementation/`
- review findings-first: registrar em `sdd/reviews/` quando precisar de artefato persistente
- validação: seguir `sdd/validation/VALIDATION-CHECKLIST.md`

## Passo 4: proteger o baseline

Antes de qualquer alteração estrutural, releia `sdd/00-baseline-architecture.md` e confirme se a mudança:

- preserva o legado aprovado
- muda comportamento de modo explícito
- evita refactor amplo sem justificativa normativa

## Passo 5: trabalhar em slices pequenos

- mantenha batches pequenos
- defina alvo de validação explícito
- não abra um segundo slice antes de estabilizar o primeiro

## Próximo intake esperado

Depois desta implantação inicial, o próximo intake funcional esperado é o `Plano 1`, focado em tarefas e scripts de sincronização de projetos.
