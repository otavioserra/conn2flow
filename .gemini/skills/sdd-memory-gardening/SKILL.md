---
name: sdd-memory-gardening
description: "LEIA ANTES de finalizar a sessão ou quando a memória de execução ultrapassar os limites (50 linhas / 5 KB). Se não ler: prompt bloat degrada a atenção dos modelos de IA e desperdiça tokens."
user-invocable: false
---

# Memory Gardening SDD

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Finalizar uma sessão de trabalho, concluir um batch ou quando `MEMORIA-ENGENHARIA-EXECUCAO.md` exceder ~5 KB ou 50 linhas.
- **SKIP APENAS SE**: Sessão de consulta simples sem alteração de contexto ou aprendizado operacional relevante.
- **CONSEQUÊNCIA DE IGNORAR**: Degradação cognitiva do agente por excesso de contexto (prompt bloat), aumento de custos de inferência e esquecimento de diretrizes críticas.

---

1. Meça bytes e linhas e leia a memória de execução completa.
2. Preserve somente as 3 a 5 tarefas mais recentes e pendências ativas.
3. Destile regras recorrentes para skills Core ou específicas do projeto.
4. Nunca altere a memória de Chefia sem instrução humana explícita.
5. Reescreva a memória visando cerca de 5 KB e obrigatoriamente menos de 10 KB.
6. Valide frontmatter, descoberta das skills e o diff Git recuperável.
7. Registre tamanhos e evidências no checklist do batch.
