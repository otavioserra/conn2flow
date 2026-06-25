# BATCH-061 — Renomeação para project_tables_config.json e Filtragem de Manifesto

- **Intake**: [req-061.md](../human-requests/req-061.md)
- **Status**: complete
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-061

## Contexto

Este lote separa explicitamente o arquivo de configuração global do core (`resources/tables_config.json`) do arquivo de configuração customizada do projeto (`resources/project_tables_config.json`). Também corrige o manifesto transitório `project-schema-metadata.json` para que ele contenha apenas as tabelas declaradas no arquivo de projeto, evitando levar tabelas do core para o servidor como se fossem customizações do projeto.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Compilador lê `project_tables_config.json` em modo projeto e filtra `project-schema-metadata.json` | `atualizacao-dados-recursos.php` | `php -l` + teste focado |
| 2 | Descompilador lê `tables_config.json` + `project_tables_config.json` em ordem | `recuperacao-dados-recursos.php` | `php -l` + teste focado |
| 3 | Testes adaptados para o novo nome do arquivo de projeto | `RecuperacaoDadosRecursosTest.php` | PHPUnit focado |
| 4 | Documentação pt-br/en atualizada | `CONN2FLOW-SISTEMA-RECURSOS.md`, `CONN2FLOW-RESOURCES-SYSTEM.md` | Doc review |

## Evidência de validação

- `php -l` OK em `atualizacao-dados-recursos.php`, `recuperacao-dados-recursos.php` e `RecuperacaoDadosRecursosTest.php`.
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: 13 tests, 72 assertions.
- `composer test` OK: 61 tests, 214 assertions, 4 skipped e 1 deprecation PHPUnit preexistente.
- `git diff --check -- . ':(exclude)sdd/human-requests/CURRENT.md'` OK. O `git diff --check` completo acusa uma linha em branco final no intake humano `CURRENT.md`, já alterado antes da execução e não editado pelo executor.
