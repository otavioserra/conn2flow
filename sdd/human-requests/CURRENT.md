# Current Human Request

- **Intake ativo**: [req-065.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-065.md) (BATCH-065 `complete`, 2026-06-25).

- **Status**: BATCH-065 concluído. Compilador e descompilador resolvem a coluna de ID dinamicamente (`$cfg['id']`, fallback `'id'`) na varredura de recursos, corrigindo `arquivos=0` para tabelas com ID customizado (ex.: `publisher_pages` com `"id":"page_id"`). Validação: `RecuperacaoDadosRecursosTest` 19/19, `composer test` 67/67.

- **Pendências**: BATCH-064 (versões de GitHub Actions) segue `ready-for-intake`. Pull/deploy runtime de tabela com ID customizado pendente com o operador.
