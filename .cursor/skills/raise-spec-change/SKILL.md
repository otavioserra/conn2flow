---
name: raise-spec-change
description: Abre uma rodada de mudança normativa em repositório SDD. Use quando requisito, contrato, critério de aceite ou decisão estrutural realmente precisar mudar.
disable-model-invocation: true
argument-hint: "[mudança-requisitada]"
---

# Mudança normativa

Trate `$ARGUMENTS` como um pedido de mudança normativa.

## Procedimento

1. Carregue `sdd-workflow`.
2. Confirme o impacto em `sdd/`, decisions, implementation e validation.
3. Registre a mudança em `sdd/change-requests/` antes de consolidar no sdd numerado.
4. Só implemente depois que a mudança normativa ficar explícita.

## Pedido atual

$ARGUMENTS