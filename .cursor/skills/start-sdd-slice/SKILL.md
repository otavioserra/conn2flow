---
name: start-sdd-slice
description: Inicia uma nova rodada em repositório SDD. Use quando a demanda for nova, vier de sdd/human-requests ou ainda não estiver classificada entre change request, implementação, review ou validação.
disable-model-invocation: true
argument-hint: "[demanda]"
---

# Início de slice SDD

Trate `$ARGUMENTS` como a demanda nova a ser classificada e executada.

## Antes de agir

1. Carregue `sdd-workflow`.
2. Identifique a âncora mais concreta da tarefa: sdd, batch, review, validation, decision, arquivo de código ou `sdd/human-requests/`.
3. Leia o contexto mínimo inicial: `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.
4. Se a entrada vier de `sdd/human-requests/`, leia primeiro esse intake.

## Regra de execução

1. Classifique cedo: change request, implementação, review ou validação.
2. Edite sdd numerados apenas quando requisito, contrato, aceite ou decisão realmente mudar.
3. Implemente o menor slice aprovado.
4. Valide cedo com a menor checagem capaz de falsificar o batch atual.

## Demanda atual

$ARGUMENTS