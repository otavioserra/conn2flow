# BATCH-192: Ajustes das docs online, sitemap no deploy e exclusão de órfãs

Execução da [req-188](../../human-requests/archive/req-188.md).

**Status**: `complete`.

## Entregas

| Item | Onde | Mudança |
|---|---|---|
| 1 | `gestor/gestor.php` | `caminho-extensao` vazio quando o caminho termina em `/` |
| 2 | site: `modulos/documentation/resources/*/templates/docs-sidebar` e `docs-article` | Botões `data-docs-expand-all`/`data-docs-collapse-all` e o JS que abre/fecha todos os grupos |
| 3 | `cli/src/Support/Docs/SddSource.php`, `DocsBuilder.php`; site: rótulo `archive` no manifesto | `archive/` coletado; ordenado depois dos ativos; subgrupo "Arquivo" no menu |
| 5 | `SddSource::resumo()` | Resumo sem marcação Markdown, cortado na palavra, com `…` |
| 6 | `gestor/controladores/api/api.php` | `api_project_sitemap_regenerar()` depois do banco e dos hooks; campo `sitemap` na resposta |
| 7 | site: `resources/project_tables_config.json` | `deletar` com as 19 páginas e 19 publicações órfãs (17 do primeiro arquivamento, mais req-178 e BATCH-182 do arquivamento deste lote) (`paginas` só com a lista; as regras continuam as do `admin-paginas`, lido depois) |
| Docs | `ai-workspace/{pt-br,en}/docs` | `projects`, `deploy-a-project`, `sitemap`, `api/project`, `documentation` |

## Commits

- Core: `ad58064f` (código e `DocsReq188Test`), `36051f60` (docs), commit do SDD deste lote.
- Site (`feat/req-055`): commit deste lote (templates, manifesto, `project_tables_config.json`, docs regeneradas).

## Validação

- `DocsReq188Test`: 5 testes. Suíte PHP completa: 1252 testes, 9812 asserções, sem falhas.
- `docs:build`: 596 páginas (eram 305; +291 do arquivo). `archive/req-030.md` excluído pelo filtro de conteúdo sensível.
- Lab, `project:update-all conn2flow-site-local` 8/8:
  - `DELETE_DONE paginas removidos=17` e `publisher_pages removidos=17`;
  - `schema-metadata.json`: `paginas` segue `core:admin-paginas`, `natural_key`, com os campos preservados de sempre.
- Rotas no Lab:
  - `whats-new` `2.10/`, `2.10.13/`, `en/…/2.9/` e `2.9.51/`: 200 (antes 404);
  - `docs/sdd/human-requests/archive/req-169/` e `implementation/archive/BATCH-174/`: 200;
  - `docs/sdd/human-requests/req-169/` (antiga): 302 para `/404`.
- Resumo da req-187 no cabeçalho: "Origem: Humano, 2026-09-26: …", sem marcação.
- `page:inspect`: botões presentes (24×24), filtro ao lado, sem erro de console.
- `project:deploy conn2flow-site-local` (API): `"sitemap": "updated"`. O `sitemap.xml` tem 635 URLs, 596 de docs, todas com `https://conn2flow.local/` e nenhuma com `localhost`.

## Pendências

- A sincronização por SSH (`project:update-all`) não passa pela API e não regenera o sitemap (anotado no BL-019).
- A exclusão automática de páginas removidas continua com o Humano (BL-025). A cada `ai:archive-sdd` do core, as URLs antigas dos itens arquivados precisam entrar na lista `deletar` do site.
