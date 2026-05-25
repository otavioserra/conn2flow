# 00 Start Here

Use este arquivo como ponto de partida para qualquer nova demanda dentro do `conn2flow` apÃ³s a implantaÃ§Ã£o do SDD repo-wide.

## Passo 1: resolver a origem da demanda

Se a demanda chegar por `sdd/human-requests/`, trate esse material apenas como intake humano nÃ£o normativo.

Regra de resoluÃ§Ã£o da pasta:

1. `sdd/human-requests/CURRENT.md`
2. `sdd/human-requests/README.md`
3. o arquivo `.md` mais recente

## Passo 2: leitura mÃ­nima obrigatÃ³ria

1. `sdd/README.md`
2. `sdd/00-baseline-architecture.md`
3. `sdd/process/01-WORKFLOW.md`
4. `sdd/implementation/BATCH-INDEX.md`
5. `sdd/validation/VALIDATION-CHECKLIST.md`
6. `sdd/decisions/DECISION-LOG.md`

## Passo 3: classificar cedo

Classifique a demanda antes de editar cÃ³digo:

- mudanÃ§a normativa: abrir em `sdd/change-requests/`
- implementaÃ§Ã£o incremental: abrir ou continuar batch em `sdd/implementation/`
- review findings-first: registrar em `sdd/reviews/` quando precisar de artefato persistente
- validaÃ§Ã£o: seguir `sdd/validation/VALIDATION-CHECKLIST.md`

## Passo 4: proteger o baseline

Antes de qualquer alteraÃ§Ã£o estrutural, releia `sdd/00-baseline-architecture.md` e confirme se a mudanÃ§a:

- preserva o legado aprovado
- muda comportamento de modo explÃ­cito
- evita refactor amplo sem justificativa normativa

## Passo 5: trabalhar em slices pequenos

- mantenha batches pequenos
- defina alvo de validaÃ§Ã£o explÃ­cito
- nÃ£o abra um segundo slice antes de estabilizar o primeiro

## PrÃ³ximo intake esperado

Depois desta implantaÃ§Ã£o inicial, o prÃ³ximo intake funcional esperado Ã© o `Plano 1`, focado em tarefas e scripts de sincronizaÃ§Ã£o de projetos.
