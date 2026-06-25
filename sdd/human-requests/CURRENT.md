# Current Human Request

- **Intake ativo**: [req-063.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-063.md) (BATCH-063 `complete`, 2026-06-25).

- **Status**: BATCH-063 concluído. Causa de `arquivos=0` confirmada (registros que usam template padrão gravam `html`/`css` nulos no banco); descompilador blindado contra arquivo em branco e com log `RDR_DEBUG_FILE_EMPTY`. Validação: `RecuperacaoDadosRecursosTest` 17/17, `composer test` 65/65.

- **Pendências**: Pull runtime com o operador (confirmar `RDR_DEBUG_FILE_EMPTY` no console e ausência de arquivos em branco em `resources/`).
