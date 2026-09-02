---
name: sdd-memory-gardening
description: "LEIA SOMENTE quando a memória de execução atingir o alerta de 50 KB / 200 linhas ou o teto de 75 KB / 300 linhas. É proibido podar arquivos saudáveis ou acionar esta skill apenas pelo fim da sessão."
user-invocable: false
---

# Memory Gardening SDD

> 🚫 PROIBIDO PODAR se a memória de execução estiver abaixo de 50 KB ou 200 linhas. Ignorar a skill no final da sessão caso o arquivo esteja saudável.

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Quando `MEMORIA-ENGENHARIA-EXECUCAO.md` atingir 50 KB ou 200 linhas (alerta preventivo). A poda torna-se obrigatória ao atingir 75 KB ou 300 linhas.
- **SKIP APENAS SE**: O arquivo estiver abaixo de 50 KB e 200 linhas. Encerramento de sessão ou conclusão de batch, isoladamente, nunca acionam esta skill.
- **CONSEQUÊNCIA DE IGNORAR**: Degradação cognitiva do agente por excesso de contexto (prompt bloat), aumento de custos de inferência e esquecimento de diretrizes críticas.

---

1. Meça bytes e linhas e leia a memória de execução completa (ou rode `c2f ai:prune-memories`).
2. Se o arquivo estiver abaixo de 50 KB e 200 linhas, pare e registre que a memória está saudável; não reescreva o conteúdo.
3. Entre 50 KB / 200 linhas e 75 KB / 300 linhas, emita alerta preventivo e planeje a manutenção sem poda automática.
4. Ao atingir 75 KB ou 300 linhas, execute a poda obrigatória.
5. Preserve as 20 a 25 tarefas, aprendizados e pendências mais recentes.
6. Destile regras recorrentes para skills Core ou específicas do projeto.
7. Nunca altere a memória de Chefia sem instrução humana explícita.
8. Reescreva a memória visando cerca de 25 KB.
9. Valide frontmatter, descoberta das skills e o diff Git recuperável.
10. Registre tamanhos e evidências no checklist do batch.
