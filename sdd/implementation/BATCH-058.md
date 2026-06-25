# BATCH-058 — Sistema de Recuperação e Engenharia Reversa de Recursos (Pull System)

- **Intake**: [req-058.md](../human-requests/req-058.md)
- **Status**: ready-for-intake
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-058

## Contexto

Este lote implementa a sincronização reversa (Pull System), permitindo baixar um dump zip dos dados via API (`_api/project/recover`) e decompilá-los de volta em arquivos físicos (HTML/CSS/MD) e metadados estruturados (externos ou inline) no repositório local.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Adição de sub-endpoint `recover` ao handler de projetos | `gestor/controladores/api/api.php` | `php -l` |
| 2 | Novo controlador de recuperação no servidor | `gestor/controladores/recuperacoes/recuperacao-dados-recursos.php` | `php -l` |
| 3 | Novo descompilador de recursos genérico no cliente | `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php` | `php -l` |
| 4 | Novo script de Pull e tarefas do VS Code | `ai-workspace/en/scripts/projects/recover-project.sh`, `.vscode/tasks.json` | Syntax + manual execution test |
| 5 | Testes unitários para descompilador e validação | `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` | `composer test` ou PHPUnit OK |

## Notas de execução

- O descompilador local deve carregar recursivamente as definições em `tables_config.json` e manifestos de módulos (`[modulo].json`).
- Tratar e converter campos serializados (como JSON no banco) de volta para arrays estruturados.
- Saneamento de chaves auto-incremento e metadados de build (`versao`, `checksum`, `user_modified`, `project`) para evitar ruído no Git.
- Preservar a consistência removendo BOM de arquivos físicos gerados.
