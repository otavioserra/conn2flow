# BATCH-042 - Controle de Métricas no publisher-index e Suíte de Testes

## Escopo do Lote
Este lote implementa o controle `show_metrics` no módulo `publisher-index` e reforça a cobertura automatizada do widget:
1. Toggle administrativo para exibir/ocultar métricas de paginação.
2. Bloco condicional `metrics` no renderer público e nos templates físicos.
3. Testes PHPUnit para helpers Unicode, resolução de métricas e integração MySQL dedicada.

---

## Progresso por Slice

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Schema/defaults, CRUD e JS administrativo (`show_metrics`) | complete | `node --check` + PHPUnit OK |
| 2 | Renderer PHP e templates com bloco condicional `metrics` | complete | `php -l` + PHPUnit OK |
| 3 | Testes unitários e integração MySQL gated por `CONN2FLOW_RUN_DB_TESTS` | complete | `composer test` + teste MySQL dedicado OK |

---

## Checklist de Implementação

### 1. Controle `show_metrics`
- [x] Adicionar `show_metrics => true` aos defaults do `fields_schema` em adicionar, editar e clonar.
- [x] Adicionar `show_metrics: true` ao schema inicial do `publisher-index.js`.
- [x] Hidratar, persistir e usar `#show_metrics` no preview/salvamento.
- [x] Incluir checkbox `#show_metrics` nas 6 páginas CRUD (`pt-br`/`en`, adicionar/editar/clonar).
- [x] Registrar labels de recurso em `publisher-index.json`.

### 2. Widget e Templates
- [x] Ler `show_metrics` com `publisher_index_widget_bool(..., true)`.
- [x] Resolver a variável global `[[show_metrics]]`/`@[[show_metrics]]@`.
- [x] Aplicar `publisher_index_widget_bloco_condicional($output, 'metrics', $show_metrics)`.
- [x] Envolver `.publisher-index-metrics` com `<!-- metrics < --> ... <!-- metrics > -->` nos templates `lista`, `grid`, `timeline` e `agenda`, em `pt-br` e `en`.
- [x] Corrigir a cláusula de busca para casar também nomes gravados com barra literal (`\u00xx`) em `LIKE` do MySQL.

### 3. Testes
- [x] Cobrir `publisher_index_widget_unicode_escape()` para termos acentuados com e sem barra.
- [x] Cobrir `publisher_index_widget_corrigir_unicode()` para strings normais e corrompidas.
- [x] Cobrir resolução de métricas globais e remoção/preservação do bloco `metrics`.
- [x] Adicionar teste integrado MySQL, protegido por `CONN2FLOW_RUN_DB_TESTS=1` e `CONN2FLOW_DB_DATABASE=conn2flow_test`, validando busca disjuntiva, `INNER JOIN` e contagem.

---

## Evidência de Validação (BATCH-042) - 2026-06-15

- `php -l gestor/modulos/publisher-index/publisher-index.php` -> OK.
- `php -l gestor/modulos/publisher-index/publisher-index.widget.php` -> OK.
- `php -l tests/Integration/PublisherIndexWidgetTest.php` -> OK.
- `node --check gestor/modulos/publisher-index/publisher-index.js` -> OK.
- `composer test` -> OK (`36 tests`, `97 assertions`, `2 skipped`; skips esperados para testes gated).
- Teste MySQL dedicado:
  - `CONN2FLOW_RUN_DB_TESTS=1`
  - `CONN2FLOW_DB_CONNECTION=mysqli`
  - `CONN2FLOW_DB_HOST=127.0.0.1`
  - `CONN2FLOW_DB_DATABASE=conn2flow_test`
  - `CONN2FLOW_DB_USERNAME=root`
  - `CONN2FLOW_DB_PASSWORD=root123`
  - `vendor/bin/phpunit --filter testBuscaComMysqlTemFiltroDisjuntivoEInnerJoin tests/Integration/PublisherIndexWidgetTest.php` -> OK (`1 test`, `8 assertions`).
- Pendência (operador): deploy `Update => Core` e validação manual no navegador do toggle "Exibir métricas de paginação" em registros existentes e novos.

