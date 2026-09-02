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

## 📦 Regra dos 10 Ativos na Raiz SDD

Além da memória de execução, a raiz das pastas de controle tem janela fixa de **10 arquivos ativos**:

- `sdd/human-requests/`: no máximo **10 requisições** soltas, além de `CURRENT.md` e `README.md`. As demais vão para `sdd/human-requests/archive/`.
- `sdd/implementation/`: no máximo **10 relatórios de lote** soltos, além de `BATCH-INDEX.md`. Os demais vão para `sdd/implementation/archive/`.
- `DECISION-LOG.md`, `BATCH-INDEX.md` e `VALIDATION-CHECKLIST.md` seguem com no máximo 10 itens correntes, com o histórico resumido em tabela apontando para `archive/`.

**Nunca mova arquivos manualmente**: ao arquivar, todo link de markdown que apontava para o caminho antigo em `BATCH-INDEX.md`, `VALIDATION-CHECKLIST.md`, `DECISION-LOG.md` e `CURRENT.md` precisa ser reescrito para `archive/`. Use o comando determinístico do Core, que move e reescreve os links numa única operação:

```bash
php cli/c2f.php ai:archive-sdd --repo=<caminho-do-repo> --keep=10 --dry-run   # inspeção
php cli/c2f.php ai:archive-sdd --repo=<caminho-do-repo> --repair-links        # execução
```

O comando falha (exit 1) enquanto houver link relativo órfão sob `sdd/`. Links remanescentes que apontem para arquivos inexistentes devem ser reportados ao Arquiteto, não silenciados.

---

## 🧠 Poda da Memória de Execução

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
