# BATCH-059 — Refinamentos, Overrides de Projeto e Sincronização Inteligente de Contents (Pull System)

- **Intake**: [req-059.md](../human-requests/req-059.md)
- **Status**: complete
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-059

## Contexto

Este lote implementa refinamentos críticos ao Pull System (BATCH-058):
1. Renomeia o script CLI do servidor para evitar colisões com o descompilador do cliente.
2. Adiciona suporte para overrides de `scope` e `modulo` em `tables_config.json` a nível de projeto (permitindo sincronizar tabelas de módulos do Core sem tocar nos JSONs originais do Core).
3. Implementa a recuperação seletiva e inteligente de mídias/uploads da pasta `contents/` baseada em hashes MD5 e timestamps, com touch de mtime e logs de conflito/choque para proteção de modificações locais.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Renomeação de CLI servidor e API zippando `contents/` | `api.php`, `recuperacao-banco-de-dados.php` | `php -l` + `git status` |
| 2 | Compilador e descompilador com suporte a overrides no `tables_config.json` | `atualizacao-dados-recursos.php`, `recuperacao-dados-recursos.php` | `php -l` + run `Update => Core` |
| 3 | Descompilador com cópia inteligente de `contents/`, touch e relatório de conflitos | `recuperacao-dados-recursos.php` | `php -l` |
| 4 | Script shell `recover-project.sh` e VS Code Tasks atualizados | `recover-project.sh`, `.vscode/tasks.json` | Syntax + manual execution test |
| 5 | Atualização e extensão da suíte de testes unitários | `RecuperacaoDadosRecursosTest.php` | `composer test` |

## Notas de execução

- **Mapeamento de Scope/Modulo**: Em `coletarConfigsTabelas()` e `rdr_coletar_configs()`, se a tabela global carregar `scope === 'module'` e `modulo !== null`, calcular a pasta base (`base_dir`) como `<gestorDir>/modulos/<modulo>/resources`.
- **Cópia Inteligente**: Calcular `md5_file` antes de copiar. Se idêntico, pular. Se diferente, sobrescrever se e somente se `remote_mtime > local_mtime`. Se local for mais novo ou de mesma data, pular a cópia, registrar o conflito via `RDR_CONFLITO` e acumular no sumário final.
- **Timestamp Touch**: Usar `touch()` local com a data do servidor ao realizar a cópia para manter os relógios de arquivos sincronizados em futuros pulls/deploys.

## Evidência de validação

- `php -l` OK em `api.php`, `recuperacao-banco-de-dados.php`, `atualizacao-dados-recursos.php`, `recuperacao-dados-recursos.php` e `RecuperacaoDadosRecursosTest.php`.
- `.vscode/tasks.json` validado com `ConvertFrom-Json`.
- `git diff --check` OK.
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: 11 testes, 65 assertions.
- `composer test` OK: 59 testes, 207 assertions, 4 skipped e 1 deprecation PHPUnit preexistente.
- `bash -n ai-workspace/en/scripts/projects/recover-project.sh` não executado neste Windows porque o comando `bash` encaminha para WSL e não há distribuição instalada.
