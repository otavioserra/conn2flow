---
name: sdd-memory-gardening
description: "LEIA SOMENTE quando a memória de execução atingir o alerta de 50 KB / 200 linhas ou o teto de 75 KB / 300 linhas. É proibido podar arquivos saudáveis ou acionar esta skill apenas pelo fim da sessão."
user-invocable: false
---

# Memory Gardening MDD (compatível com satélites SDD)

> 🚫 PROIBIDO PODAR se a memória de execução estiver abaixo de 50 KB ou 200 linhas. Ignorar a skill no final da sessão caso o arquivo esteja saudável.

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Quando `04-memory-engineering-execution.md` atingir 50 KB ou 200 linhas (alerta preventivo). A poda torna-se obrigatória ao atingir 75 KB ou 300 linhas.
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

## MDD: faxina segura de worktrees em seis passos

Esta seção é manutenção de worktrees, independente do gatilho de poda de memória. O nome da skill permanece compatível; memory/ é a raiz MDD da matriz e dos satélites; sdd/ permanece compatível para projetos legados. Não pode memória saudável para fechar lote.

1. Inventarie com git worktree list --porcelain; confirme raízes absolutas, autores/datas, origens em environment.json e no pipeline. Exclua árvore principal e qualquer worktree usada como origem de projeto.
2. Atualize refs com git fetch e confira commits fora de origin/main (git log origin/main..HEAD na worktree), alterações staged/unstaged com git diff --ignore-cr-at-eol e git diff --cached --ignore-cr-at-eol, além de git ls-files --others --exclude-standard. Os três requisitos são cumulativos: zero commits exclusivos, zero alteração real e zero arquivo não rastreado. Não use --force para contornar mudanças; liste trabalho pendente ao humano.
3. No Windows, valide o alvo e inventarie junctions (cmd /c "dir /AL <pasta>") e referências recebidas de outras worktrees. Reaponte dependências antes da remoção; desfaça somente cada junction com cmd /c "rmdir <pasta>\<atalho>" sem /S, seguindo Armadilha 18. Não componha operações de exclusão entre shells.
4. Confirme ausência dos links e execute git worktree remove no caminho literal autorizado. Se Git recusar por sujeira, preserve a árvore e reporte; não use --force, clean, reset nem exclusão recursiva manual.
5. Confira vendor/node_modules das árvores principais e demais worktrees; só então apague branch local integrada com git branch -d (nunca -D) e rode git worktree prune.
6. Registre árvores removidas/preservadas, motivos e integridade dos destinos. Atualize árvore principal com git merge --ff-only origin/main apenas se estiver limpa e a atualização estiver autorizada; divergência exige avaliação, nunca mescla automática de trabalho alheio.

Na governança MDD, aplique o teto ativo de 50 KB da memory/02-policy.md antes de ampliar documento; preserve original e síntese. O CLI legado ai:archive-sdd só deve operar em sdd/ até suportar memory/ explicitamente: não o execute na matriz migrada presumindo compatibilidade.


## REQ-071: canonical engineering memories

MDD repositories use memory/03-memory-engineering-chief.md and memory/04-memory-engineering-execution.md. A preventive 50 KiB ceiling triggers maintenance planning; 75 KiB is a critical alert. Archive pruned execution history under memory/raw/archive/, preserving full originals and traceable summaries before reducing the active file to about 25 KiB and 20–25 recent records. Keep unresolved items, update index.md and repair links. Use raw/active/ for temporary shareable observations. Do not prune as part of a filename migration. Legacy SDD projects retain their directory; new boilerplates use the numbered filenames.
