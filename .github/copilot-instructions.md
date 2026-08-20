# Instruções do Copilot — Spec-Driven Development

- Trate `sdd/README.md` e especificações numeradas como fonte normativa.
- Leia `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch ativo e `sdd/validation/VALIDATION-CHECKLIST.md` antes de editar código.
- Memórias de Engenharia: leia `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` e `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` no início da sessão.

## Skills OBRIGATÓRIAS por Marco de Fluxo

Invoque explicitamente a skill correspondente ANTES de editar código ou fechar lotes:
- **Início de Tarefa**: `start-sdd-slice`, `continue-sdd-batch`, `sdd-workflow`.
- **Durante a Edição**: invoque as Core Skills (`c2f-*`) relevantes para a stack tocada.
- **Fechamento e Validação**: `project-validation`, `review-current-batch`, `sdd-memory-gardening`.
- **Mudança Normativa**: `raise-spec-change`.

## Intake Gate do backlog

- `sdd/backlog/` é incubadora de rascunhos. É proibido implementar itens diretamente dali.
- Um item só se torna executável após promoção humana para `sdd/human-requests/req-XXX.md` e associação a um batch.
