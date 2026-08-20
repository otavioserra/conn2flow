# Projeto Spec-Driven Development

- Trate `sdd/README.md` e os sdd numerados como fonte normativa.
- Antes de editar código ou sdd, leia `sdd/README.md`, `sdd/process/00-START-HERE.md`, `sdd/process/01-WORKFLOW.md`, `sdd/implementation/BATCH-INDEX.md`, o batch atual, `sdd/validation/VALIDATION-CHECKLIST.md` e `sdd/decisions/DECISION-LOG.md`.
- Use `sdd/human-requests/` apenas como intake humano não normativo. Se a demanda vier como caminho de arquivo Markdown ou como a própria pasta, leia esse material primeiro e depois classifique a demanda no artefato SDD correto.
- **Memórias de Engenharia**: No início de cada sessão, leia obrigatoriamente `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` e `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` para alinhar contexto antes de qualquer alteração.
- **Manutenção da Memória de Execução**: Ao término de cada tarefa, atualize `sdd/MEMORIA-ENGENHARIA-EXECUCAO.md` com novos aprendizados, bugs resolvidos e particularidades do ambiente. Nunca modifique `sdd/MEMORIA-ENGENHARIA-CHEFIA.md` sem instrução explícita do usuário humano.
- Classifique a demanda cedo: change request, implementação de batch, review ou validação.
- Não reescreva os sdd numerados para comentários pequenos de review.
- Edite sdd numerados apenas quando requisito, contrato, critério de aceite ou decisão aprovada realmente mudar.
- Mantenha o trabalho em batches pequenos com alvo de validação explícito.

## Skills OBRIGATÓRIAS por Marco de Fluxo

Invoque explicitamente a skill correspondente ANTES de editar código ou fechar lotes:
- **Início de Tarefa**: `/start-sdd-slice` (nova demanda), `/continue-sdd-batch` (retomar batch), `sdd-workflow` (alinhar fluxo).
- **Durante a Edição**: invoque as Core Skills (`c2f-*`) relevantes para a stack tocada (banco, variáveis, recursos, layout, etc.).
- **Fechamento e Validação**: `project-validation` (estratégia de testes), `/review-current-batch` (review findings-first), `sdd-memory-gardening` (podar memórias).
- **Mudança Normativa**: `/raise-spec-change` (se houver alteração de contrato/requisito).

## Otimização de Contexto e Arquivamento

- Mantenha `sdd/decisions/DECISION-LOG.md`, `sdd/implementation/BATCH-INDEX.md` e `sdd/validation/VALIDATION-CHECKLIST.md` com no máximo 10 itens correntes ou ativos.
- Mantenha também `sdd/human-requests/` enxuto, preservando no máximo 10 requisições correntes ou recentes fora de `archive/`.
- Mova históricos antigos para a subpasta `archive/` correspondente: `sdd/decisions/archive/`, `sdd/human-requests/archive/`, `sdd/implementation/archive/` ou `sdd/validation/archive/`.
- Nos arquivos principais, substitua o histórico arquivado por tabelas Markdown resumidas com 1 linha por item e link direto para o arquivo em `archive/`.
- Ao carregar contexto inicial, priorize os arquivos principais e abra itens em `archive/` apenas quando o batch, a requisição ativa ou um link de rastreabilidade exigir.

## Intake Gate do backlog

- `sdd/backlog/` é uma incubadora de rascunhos administrada pelo Usuário e pelo Arquiteto IA.
- O Executor pode ler itens para contexto, mas é estritamente proibido de implementá-los, abrir batch de execução ou alterar código diretamente a partir deles.
- Um item, inclusive `READY`, só se torna executável após promoção humana explícita para `sdd/human-requests/req-XXX.md`, atualização de `CURRENT.md` e associação a um batch.


## 📋 Protocolo de Transparência & Checklist Vivo (Live Todo List)

- Ao iniciar qualquer requisição ou lote, renderize imediatamente a lista completa de tarefas (`Todo List`) com caixas de seleção `[ ]`.
- A cada término de etapa/comando relevante, atualize e re-exiba a lista marcando `[x]` nas etapas concluídas e destacando a etapa atual (`⏳ [EM ANDAMENTO]`).
- Nunca execute sequências longas de comandos sem atualizar o status visual para o usuário.

## 🛡️ Espectro de 3 Níveis de Autonomia de IA

1. **Nível 1: SUPERVISIONADO (Padrão Mandatório / Human-in-the-Loop)**:
   - O agente implementa código e executa testes, mas **NÃO realiza commit, push ou deploy automático**.
   - O desenvolvedor revisa e aprova as mudanças no chat/IDE antes da consolidação.

2. **Nível 2: AUTÔNOMO MONITORADO (Live Autopilot / Glass-Box no Chat)**:
   - Ativado quando a requisição contiver `modo: autonomo_monitorado` ou o usuário autorizar expressamente o acompanhamento contínuo na tela.
   - O agente executa a esteira completa com **Live Todo List (`[ ]` ➔ `[x]`) visível e atualizado em tempo real**:
     * Criação de branch/worktree isolada (`feat/req-XXX`).
     * Codificação e compilação de recursos (`c2f resources:sync`).
     * Execução de testes automatizados (`c2f db:test`).
     * **DEPLOY EXCLUSIVAMENTE EM AMBIENTE DE TESTE LOCAL** (`c2f manager:update-all` ou Docker local).
     * ⛔ **REGRA INVIOLÁVEL DE SEGURANÇA: NUNCA REALIZAR DEPLOY AUTOMÁTICO EM AMBIENTE DE PRODUÇÃO OU SERVIDORES REMOTOS.**
     * Commit semântico e push na branch de trabalho.
     * Relatório final com logs de execução e evidências de validação.

3. **Nível 3: AUTÔNOMO HEADLESS (Background Silencioso / Black-Box)**:
   - Ativado quando a requisição contiver `modo: autonomo_headless`.
   - O agente executa toda a esteira em segundo plano isolado via MCP Hub / Git Worktrees, emitindo notificação e relatório consolidado apenas ao término.

