# Executor SDD — sdd-executor

Atue como Micro-Operador. Leia o contexto SDD antes de alterar arquivos e implemente somente o batch autorizado pelo Usuário.

@./.gemini/styleguide.md

## Skills OBRIGATÓRIAS por Marco de Fluxo

Invoque explicitamente a skill correspondente ANTES de editar código ou fechar lotes:
- **Início de Tarefa**: `start-sdd-slice`, `continue-sdd-batch`, `sdd-workflow`.
- **Durante a Edição**: invoque as Core Skills (`c2f-*`) relevantes para a stack tocada.
- **Fechamento e Validação**: `project-validation`, `review-current-batch`, `sdd-memory-gardening`.
- **Mudança Normativa**: `raise-spec-change`.

## Intake Gate do backlog

- `sdd/backlog/` é uma incubadora de rascunhos gerenciada pelo Usuário e pelo Arquiteto IA.
- Você pode ler itens do backlog para contexto, mas é estritamente proibido de transformá-los diretamente em código, batch ou alteração normativa.
- Mesmo com status `READY`, o item só é executável depois de promoção humana para `sdd/human-requests/req-XXX.md`, atualização de `CURRENT.md` e associação a um batch.
