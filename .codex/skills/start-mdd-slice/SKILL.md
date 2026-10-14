---
name: start-mdd-slice
description: "LEIA ANTES de iniciar uma nova requisição humana (req-XXX.md) ou novo lote de trabalho. Se não ler: o lote inicia sem escopo delimitado, sem contrato de validação e invade limites de outros batches."
user-invocable: true
---

# Início de slice MDD

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Receber uma nova requisição humana em `memory/human-requests/` ou abrir um novo `BATCH-XXX.md` em `memory/implementation/`.
- **SKIP APENAS SE**: O batch já tiver sido iniciado anteriormente (onde o comando correto é `/continue-mdd-batch`).
- **CONSEQUÊNCIA DE IGNORAR**: Execução sem planejamento atômico, mistura de responsabilidades entre múltiplos lotes e falta de critérios de aceite claros.

---

Trate `$ARGUMENTS` como a demanda nova a ser classificada e executada.

## Antes de agir

1. Carregue `mdd-workflow`.
2. Identifique a âncora mais concreta da tarefa: sdd, batch, review, validation, decision, arquivo de código ou `memory/human-requests/`.
3. Leia o contexto mínimo inicial: `memory/README.md`, `memory/process/00-START-HERE.md`, `memory/process/01-WORKFLOW.md`, `memory/implementation/BATCH-INDEX.md`, o batch atual, `memory/validation/VALIDATION-CHECKLIST.md` e `memory/decisions/DECISION-LOG.md`.
4. Se a entrada vier de `memory/human-requests/`, leia primeiro esse intake.

## Regra de execução

1. Classifique cedo: change request, implementação, review ou validação.
2. Edite documentos normativos numerados apenas quando requisito, contrato, aceite ou decisão realmente mudar.
3. Implemente o menor slice aprovado.
4. Valide cedo com a menor checagem capaz de falsificar o batch atual.

## Demanda atual

$ARGUMENTS
