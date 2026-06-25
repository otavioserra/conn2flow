# BATCH-065 — Suporte a Colunas Customizadas de ID em Recursos Dinâmicos

- **Intake**: [req-065.md](../human-requests/req-065.md)
- **Status**: in-progress
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-065

## Contexto

Quando uma tabela customizada (como `publisher_pages`) declara uma coluna diferente de `id` para seu identificador lógico (como `"id": "page_id"`), o descompilador falha ao gerar os arquivos físicos porque tenta extrair a chave literal `'id'` (que não existe no dump cru do banco). Da mesma forma, o compilador e seu cache de indexação ignoram o registro na leitura de metadados.

Este lote orienta o executor a introduzir a resolução dinâmica do nome da coluna de ID (através de `$cfg['id'] ?? 'id'`) em toda a pipeline de varredura dinâmica, tanto na compilação quanto na descompilação.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Resolução dinâmica da coluna de ID no descompilador | `recuperacao-dados-recursos.php` | `php -l` |
| 2 | Resolução dinâmica da coluna de ID e cache no compilador | `atualizacao-dados-recursos.php` | `php -l` |
| 3 | Caso de teste unitário com ID customizado | `RecuperacaoDadosRecursosTest.php` | PHPUnit focado |
| 4 | Execução completa de regressão de testes | `tests/Unit/PHP/` | `composer test` |
