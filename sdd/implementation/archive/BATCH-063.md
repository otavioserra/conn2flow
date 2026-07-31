# BATCH-063 — Depuração e Logging Explicativo na Descompilação de Arquivos

- **Intake**: [req-063.md](../human-requests/req-063.md)
- **Status**: in-progress
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-063

## Contexto

Durante a descompilação de recursos na recuperação de projeto (`recover-project.sh`), o descompilador reporta `arquivos=0` para tabelas como `galleries` e `menus`.
Isto ocorre porque no banco de dados os campos físicos (`html` e `css`) para os registros correspondentes estão nulos ou vazios (por exemplo, quando usam os templates padrão do sistema sem customização), não gerando arquivos físicos no disco. Isto é o comportamento correto do descompilador para evitar criar arquivos em branco desnecessários.

Este lote orienta o executor a:
1. Depurar o fluxo de extração de arquivos físicos, garantindo que o descompilador de fato leia corretamente as definições do `project_tables_config.json`.
2. Adicionar logs claros no console (modo não silencioso) caso a geração de um arquivo seja omitida devido a um campo nulo ou vazio (ex: `RDR_DEBUG_FILE_EMPTY tabela=galleries id=x campo=html`).
3. Ajustar e estender a suíte de testes unitários para validar essa emissão de logs e que nenhum arquivo em branco seja criado.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Adição de log `RDR_DEBUG_FILE_EMPTY` na omissão de arquivos com campos nulos/vazios | `recuperacao-dados-recursos.php` | `php -l` + log no console |
| 2 | Cobertura de teste para campos de arquivos vazios/nulos e presença do log | `RecuperacaoDadosRecursosTest.php` | PHPUnit focado |
| 3 | Execução e validação de regressão da suíte de testes | `tests/Unit/PHP/` | `composer test` |
