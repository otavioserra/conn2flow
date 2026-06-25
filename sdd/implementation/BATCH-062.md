# BATCH-062 — Fallback de Idioma e Subpastas por ID em Recursos Dinâmicos

- **Intake**: [req-062.md](../human-requests/req-062.md)
- **Status**: complete
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-062

## Contexto

Este lote ajusta o Pull System para dois casos operacionais encontrados em tabelas customizadas de projeto:

1. tabelas sem coluna de idioma, como `arquivos`, agora usam `pt-br` como fallback no descompilador;
2. campos `file:<ext>` passam a usar a estrutura física `<resources_dir|tabela>/<id>/<id>.<ext>`, alinhada ao padrão de recursos do core.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Fallback `pt-br` para registros sem `language`/`linguagem_codigo` | `recuperacao-dados-recursos.php` | PHPUnit focado |
| 2 | Leitura/gravação de `file:<ext>` em subpasta por ID | `atualizacao-dados-recursos.php`, `recuperacao-dados-recursos.php` | `php -l` + PHPUnit focado |
| 3 | Testes atualizados para fallback e novo layout físico | `RecuperacaoDadosRecursosTest.php` | PHPUnit focado |
| 4 | Documentação pt-br/en atualizada | `CONN2FLOW-SISTEMA-RECURSOS.md`, `CONN2FLOW-RESOURCES-SYSTEM.md` | Doc review |

## Evidência de validação

- `php -l` OK em `atualizacao-dados-recursos.php`, `recuperacao-dados-recursos.php` e `RecuperacaoDadosRecursosTest.php`.
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: 15 tests, 78 assertions.
- `composer test` OK: 63 tests, 220 assertions, 4 skipped e 1 deprecation PHPUnit preexistente.
- `git diff --check -- . ':(exclude)sdd/human-requests/CURRENT.md'` OK. O `git diff --check` completo ainda herda uma linha em branco final em `sdd/human-requests/CURRENT.md`, arquivo de intake humano já alterado antes da execução.
