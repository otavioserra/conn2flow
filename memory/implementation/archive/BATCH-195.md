# BATCH-195: Sitemap sem o fluxo de compra e deploy por API sem estouro de memória (req-191)

Execução da [req-191](../../human-requests/archive/req-191.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/sitemap.php` | Regras de `cancelled`/`canceled`, `…/download` e `cart`/`checkout` |
| `tests/Unit/PHP/SitemapTest.php` | `testFluxoDeCompraNaoEntraNoSitemap` |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/sitemap.md` | Regra descrita |
| `gestor/controladores/api/api.php` | `api_memoria_minima()` antes da sincronização de banco |
| `tests/Unit/PHP/ApiMemoriaMinimaTest.php` | Eleva, não reduz, ilimitado fica |

## Validação

- `SitemapTest`: 39 testes, 112 asserções.
- `ApiMemoriaMinimaTest`: 3 testes.
- Lab: o deploy dava HTTP 500 (`Allowed memory size of 134217728 bytes exhausted` em `atualizacoes-banco-de-dados.php:659`); com a correção, HTTP 200 e `sitemap: updated`. Sitemap com 648 URLs, sem `cart`/`checkout`/`subscription-checkout`; órfãs req-180/181 e BATCH-184/185 removidas pela lista `deletar`.
