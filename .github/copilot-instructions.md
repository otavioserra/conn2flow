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


## 📋 Protocolo de Transparência & Checklist Vivo (Live Todo List)

- Ao iniciar qualquer requisição ou lote, renderize imediatamente a lista completa de tarefas (`Todo List`) com caixas de seleção `[ ]`.
- A cada término de etapa/comando relevante, atualize e re-exiba a lista marcando `[x]` nas etapas concluídas e destacando a etapa atual (`⏳ [EM ANDAMENTO]`).
- Nunca execute sequências longas de comandos sem atualizar o status visual para o usuário.

## 🛡️ Modos de Autonomia de IA & Trava de Deploy

- **Modo SUPERVISIONADO (Padrão Mandatório)**:
  * O agente implementa código e roda testes, mas **NÃO realiza commit ou deploy automático**.
  * O desenvolvedor revisa e aprova as mudanças no chat/IDE.

- **Modo AUTÔNOMO (Apenas quando explicitado na requisição / usuário)**:
  * Permitido quando a requisição contiver `modo: autonomo` ou o usuário autorizar expressamente.
  * O agente pode: criar branch/worktree (`feat/req-XXX`), codificar, compilar (`c2f resources:sync`), rodar testes (`c2f db:test`), commitar e executar **DEPLOY EXCLUSIVAMENTE EM AMBIENTE DE TESTE LOCAL** (`c2f manager:update-all` ou Docker local).
  * ⛔ **REGRA INVIOLÁVEL DE SEGURANÇA: NUNCA REALIZAR DEPLOY AUTOMÁTICO NO AMBIENTE DE PRODUÇÃO OU SERVIDORES REMOTOS.**
