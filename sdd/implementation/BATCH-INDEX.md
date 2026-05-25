# Batch Index

Este arquivo controla o estado dos batches do `conn2flow` no modelo SDD.

## Status usados aqui

- `complete`: batch fechado e validado
- `ready-for-intake`: prÃ³ximo slice reservado, aguardando intake humano classificado
- `in-progress`: implementaÃ§Ã£o em andamento
- `blocked`: depende de decisÃ£o, requisito ou validaÃ§Ã£o adicional

## Batches

| Batch | Status | Escopo | Alvo de validaÃ§Ã£o | ObservaÃ§Ãµes |
| --- | --- | --- | --- | --- |
| BATCH-000 | complete | Onboarding do SDD repo-wide no `conn2flow` | Kits Claude/Copilot instalados, controle `sdd/` criado, baseline registrado | Fechado em 2026-05-25 |
| BATCH-001 | complete | Plano 1: tarefas e scripts de sincronização de projetos | VALIDATION-CHECKLIST.md#batch-001 | Implementado e validado em 2026-05-25 (composto em 3 tarefas: Core & Project, Project e Core) |
| BATCH-DATA-001 | blocked | Batch-Data-001: Reestruturação e Otimização de Dados e Sincronização | VALIDATION-CHECKLIST.md#batch-data-001 | Projeto de Arquitetura concluído. AGUARDANDO AUTORIZAÇÃO PARA IMPLEMENTAÇÃO. |

## Regra operacional

NÃ£o abra um novo batch funcional sem atualizar este Ã­ndice. Se o escopo mudar de forma normativa, registre primeiro a mudanÃ§a em `sdd/change-requests/`.
