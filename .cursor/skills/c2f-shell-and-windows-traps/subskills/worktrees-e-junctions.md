# Armadilha 18 — Remover worktree com junctions (receita completa)

Subskill de `c2f-shell-and-windows-traps`. Leia antes de remover uma worktree no Windows que tenha junctions (`vendor`, `node_modules` ou atalhos para a árvore principal). As regras críticas também estão na Armadilha 18 da skill-mãe.

No Windows, faça toda a inspeção e remoção em uma única sessão do Prompt de Comando (CMD). Execute os blocos por etapa, substituindo os caminhos de exemplo por caminhos absolutos verificados.

1. Na árvore principal, confirme a worktree registrada e proteja a árvore principal e as origens dos pipelines. Confirme que o caminho e seus diretórios ancestrais são reais, que todos os commits da worktree estão integrados à `main` e que não há trabalho pendente, inclusive arquivos ignorados que precisam ser preservados.

   ```bat
   cd /d "C:\Projetos\repo"
   git worktree list --porcelain
   git -C "C:\Projetos\repo-lote" status --short --untracked-files=all --ignored=matching
   git -C "C:\Projetos\repo-lote" merge-base --is-ancestor HEAD main
   echo %ERRORLEVEL%
   ```

   O último comando deve retornar 0. Pare se houver trabalho único, destino incerto ou caminho protegido.

2. Inventarie os links e os diretórios reais, um nível de cada vez. Repita os comandos em cada diretório real, inclusive subpastas de projeto e instalações reais de `vendor` e `node_modules`. Inspecione esses nomes pelo pai antes de entrar. Verifique também as outras worktrees e os destinos mostrados por `dir /AL`. Se outra árvore depender da worktree alvo, suspenda a remoção até resolver a referência e conferir a integridade dos destinos.

   ```bat
   if exist "C:\Projetos\repo-lote\" (echo CAMINHO-OK) else (echo CAMINHO-AUSENTE)
   dir /AL "C:\Projetos\repo-lote"
   echo %ERRORLEVEL%
   dir /A:D-L "C:\Projetos\repo-lote"
   dir /AL "C:\Projetos\repo-lote\projeto"
   dir /AL "C:\Projetos\repo"
   ```

   Nunca use `/S` para a enumeração. Registre o conteúdo e o destino de `vendor` e `node_modules` nas árvores que permanecerão, para comparar após a remoção.

   **Erro de consulta não é zero links (FB-007).** Leia a resposta de cada `dir /AL` antes de contar:

   | Resposta | Significado | Ação |
   | --- | --- | --- |
   | Lista com `<JUNCTION>` ou `<SYMLINKD>` | há links | anote caminho e destino de cada um |
   | `Arquivo não encontrado` / `File Not Found` (ERRORLEVEL 1), com `CAMINHO-OK` no mesmo diretório | zero links ali | siga para o próximo diretório |
   | `A sintaxe do nome do arquivo, do nome do diretório ou do rótulo do volume está incorreta`, `O sistema não pode encontrar o caminho especificado`, `Acesso negado` ou `CAMINHO-AUSENTE` | a consulta falhou | pare; corrija o caminho ou as aspas e repita |

   Nunca conte as linhas de uma saída de erro como zero links. O inventário vale só para um caminho validado no mesmo passo.

   Não monte esse inventário pelo Git Bash com `cmd //c "dir /AL /S /B \"C:\...\""`. As aspas escapadas chegam erradas ao CMD, que responde com erro de sintaxe, e isso parece "0 junctions" (medido no BATCH-093 em 7 auditorias). Abra um CMD de verdade. Se precisar de uma segunda leitura, `Get-ChildItem -LiteralPath "<pasta>" -Force -Attributes ReparsePoint` no PowerShell serve só para conferir: a remoção continua na sessão CMD.

3. Remova cada entrada identificada como `<JUNCTION>` por seu caminho literal, individualmente e sem `/S`. Se houver outro tipo de reparse point ou falha de remoção, pare e preserve a árvore.

   ```bat
   rmdir "C:\Projetos\repo-lote\vendor"
   rmdir "C:\Projetos\repo-lote\projeto\node_modules"
   ```

4. Repita `dir /AL` em todos os diretórios reais inventariados. Só prossiga com a ausência confirmada de links, lida pela tabela do passo 2: erro de caminho ou de acesso não é ausência.

5. Execute `git worktree remove` com o caminho conferido. Se o Git recusar a remoção, preserve a árvore e investigue. Após o sucesso, confira `git worktree prune --dry-run --verbose` e execute `git worktree prune` se a prévia listar apenas metadados descartáveis.

   ```bat
   git worktree remove "C:\Projetos\repo-lote"
   git worktree prune --dry-run --verbose
   git worktree prune
   ```

6. Confirme a ausência do alvo em `git worktree list --porcelain`. Em cada árvore restante, compare o conteúdo de `vendor` e `node_modules` com o inventário anterior e confira se as junctions mantidas resolvem para os destinos esperados.

   ```bat
   git worktree list --porcelain
   dir "C:\Projetos\repo\vendor"
   dir "C:\Projetos\repo\node_modules"
   ```

**Comandos proibidos nesta limpeza:** `rmdir /S`, `Remove-Item -Recurse`, `rm -rf` e enumeração no PowerShell seguida de exclusão no CMD. Nunca use `git worktree remove --force` para atropelar trabalho não integrado.

Quando precisar criar uma junction para dependências compartilhadas, aponte para a árvore principal. Confira a rotina de manutenção em `mdd-memory-gardening`.
