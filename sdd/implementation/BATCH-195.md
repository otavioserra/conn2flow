# BATCH-195: Sitemap sem o fluxo de compra (req-191)

Execução da [req-191](../human-requests/req-191.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/sitemap.php` | Regras de `cancelled`/`canceled`, `…/download` e `cart`/`checkout` |
| `tests/Unit/PHP/SitemapTest.php` | `testFluxoDeCompraNaoEntraNoSitemap` |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/sitemap.md` | Regra descrita |

## Validação

- `SitemapTest`: 39 testes, 112 asserções.
- Lab: `project:deploy conn2flow-site-local` regenera o sitemap pela API; ver o BATCH-054 do site.
