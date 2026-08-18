---
name: continue-sdd-batch
description: Retoma um batch SDD em andamento. Use quando houver delta operacional novo, mudanças humanas em sdd ou batches, ou quando for preciso continuar a mesma rodada sem reiniciar a classificação.
disable-model-invocation: true
argument-hint: "[delta-operacional]"
---

# Continuidade de batch SDD

Trate `$ARGUMENTS` como o delta operacional desde a última rodada.

## Antes de continuar

1. Releia primeiro os artefatos ou arquivos explicitamente citados no delta.
2. Releia `sdd/implementation/BATCH-INDEX.md`, o batch atual e `sdd/validation/VALIDATION-CHECKLIST.md`.
3. Se o delta mudar requisito, recarregue `sdd-workflow` e mova para change request antes de reescrever sdd numerado.
4. Se o delta for só feedback de round, mantenha sdd numerados estáveis.

## Delta atual

$ARGUMENTS