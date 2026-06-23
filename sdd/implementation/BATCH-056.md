# BATCH-056 — Sincronização Declarativa de Recursos, Deleção e Atualização Forçada (Módulos e Globais)

- **Intake**: [req-056.md](../human-requests/req-056.md)
- **Status**: complete (2026-06-23)
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-056
- **Decisão**: DEC-064

## Contexto

Estende a esteira declarativa fundada no BATCH-029 (`tabela.config`, `deletar`, `schema-metadata.json`).
Generaliza a sincronização para qualquer tabela (módulo ou global) declarar regras, conversões
(`json`/`file:ext`), deleções e atualização forçada de forma puramente declarativa no manifesto JSON.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | `config` objeto\|array + consolidar `deletar`/`forcar_atualizacao` no contrato | `atualizacao-dados-recursos.php` (`normalizarConfigTabela`, `gerarSchemaMetadata`) | `php -l` + regenerar `schema-metadata.json` via `SDD_NO_AUTORUN` |
| 2 | Varredura dinâmica `sync_resources` + `field_types` (`json`/`file:ext`) + `[Pascal]Data.json` dinâmico | `atualizacao-dados-recursos.php` (`coletarConfigsTabelas`, `coletarRecursos`, `atualizarDados`) | `php -l` + smoke de geração com fixtures temporárias |
| 3 | `tables_config.json` global: `config` array + novos parâmetros | `gestor/resources/tables_config.json` | `json_decode` + regeneração |
| 4 | `forcar_atualizacao` no atualizador (bypass `project`/`user_modified` + reset→0) | `atualizacoes-banco-de-dados.php` (`schemaMetadata`, `sincronizarTabela`) | `php -l` + teste de regressão (stubs/SQLite) |
| 5 | `composer test` + documentação técnica (6 docs × pt-br/en) | `ai-workspace/{pt-br,en}/docs/*.md` | suíte verde + revisão |

## Contrato consolidado (saída)

`schema-metadata.json` ganha o mapa de topo `forcar_atualizacao` (espelhando `deletar`):
`{ "<tabela>": [ {"pk": v} | {"natural_key": {col: val, ...}} ] }`.

## Notas de execução

- `normalizarConfigTabela()` mudou de retorno `?array` (1 config) para `array` (lista de configs). Nenhum teste/consumidor externo dependia da assinatura antiga (só `gerarSchemaMetadata` a chamava).
- `coletarConfigsTabelas()` virou o ponto único de coleta (global + módulos), reaproveitado pelo contrato e pela varredura dinâmica. O contexto de varredura (`scope`/`modulo`/`base_dir`/`inline`) é descartado antes de gravar o contrato.
- O contrato `schema-metadata.json` ganhou o mapa de topo `forcar_atualizacao` (espelha `deletar`); regenerado preservando as 17 tabelas.
- A varredura dinâmica pula as 9 tabelas fixas do pipeline para não sobrescrever os `*Data.json` gerados pela coleta clássica.
- `forcar_atualizacao` aplicado nos 3 caminhos de update de `sincronizarTabela` via helper `$isForced()` (PK e/ou chave natural). Reset de `user_modified=0` só quando a coluna existe no schema (`allowedCols`).
- Teste com SQLite passa o PDO direto (não usa `banco.php`/mysqli); `SHOW COLUMNS`/`SHOW VARIABLES` falham silenciosamente no SQLite (catch) → sem filtro de coluna + fallback de packet, suficiente para exercitar o UPDATE.
- Colateral: `${var}`→`{$var}` no atualizador (deprecation PHP 8.4 exposta ao incluir o arquivo no PHPUnit).
