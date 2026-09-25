# BATCH-181: Snapshot e Limpeza do Legado do `ai-workspace`

Execução da [req-176](../human-requests/req-176.md). FEAT-014, fase 0.

## Atividades

- [x] Branch local `legacy/ai-workspace-pre-docs` criada em `5b4348ab` (HEAD da `main`). Contém `agents-history/`, `prompts/` e `templates/` nos dois idiomas. **Push pendente (operador).**
- [x] Removidos da working tree: `ai-workspace/{pt-br,en}/{agents-history,prompts,templates}/`, 179 arquivos. As deleções ficam **fora do stage**, para revisão no VS Code.
- [x] `scripts/`, `docs/`, `snippets/` e `utils/` intocados.
- [x] `ai-workspace/{pt-br,en}/README.md` reescritos para a estrutura nova, apontando o contrato de docs e a branch de snapshot.
- [x] `.github/agents/Conn2Flow.agent.md` e `Conn2Flow-Without-Tests.agent.md`: retiradas as linhas de `agents-history`, `prompts`, `templates` e `git` (esta última já inexistente), e a de `docs` aponta para o contrato novo.

## Notas

1. **Os scripts `ai-workspace/en/scripts/translates/*.sh` já estavam quebrados antes deste lote.** Eles leem `ai-workspace/prompts/translates/...`, um caminho sem o segmento de idioma que não existia na árvore. A limpeza não altera o comportamento deles. Ficam para uma onda futura: corrigir ou aposentar.
2. O grep de referências vivas às pastas removidas (`cli/`, `gestor/`, `.vscode/`, `.github/`, `tests/`, `package.json`, skills) ficou vazio após os ajustes acima. Referências em `sdd/` são histórico e foram mantidas.

## Validação

- `git ls-tree -d legacy/ai-workspace-pre-docs ai-workspace/pt-br/` lista as três pastas.
- A suíte PHPUnit roda no fechamento conjunto com o BATCH-182 e o BATCH-183 (ver [VALIDATION-CHECKLIST](../validation/VALIDATION-CHECKLIST.md)).
