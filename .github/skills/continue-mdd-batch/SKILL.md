---
name: continue-mdd-batch
description: "LEIA ANTES de retomar o desenvolvimento de um batch que já está em andamento. Se não ler: etapas já concluídas são reexecutadas, o checklist de validação é corrompido e o foco do lote se perde."
user-invocable: true
---

# Continuidade de batch MDD

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Continuar ou retomar um lote MDD em andamento (`in-progress`), seja após pausa na conversa ou em nova sessão.
- **SKIP APENAS SE**: Início de uma nova demanda do zero (onde o comando correto é `/start-mdd-slice`).
- **CONSEQUÊNCIA DE IGNORAR**: Perda do fio condutor da tarefa, reexecução desnecessária de etapas já validadas e conflito nos checklists de validação.

---

Trate `$ARGUMENTS` como o delta operacional desde a última rodada.

## Antes de continuar

1. Releia primeiro os artefatos ou arquivos explicitamente citados no delta.
2. Releia `memory/implementation/BATCH-INDEX.md`, o batch atual e `memory/validation/VALIDATION-CHECKLIST.md`.
3. Se o delta mudar requisito, recarregue `mdd-workflow` e mova para change request antes de reescrever sdd numerado.
4. Se o delta for só feedback de round, mantenha documentos normativos numerados estáveis.

## Delta atual

$ARGUMENTS
