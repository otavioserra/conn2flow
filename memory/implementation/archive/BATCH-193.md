# BATCH-193: Segurança do `interface` — listagem sem SQL injection e excluir/status com CSRF

Execução da [req-189](../../human-requests/archive/req-189.md) (itens A1 e A2 da req-181).

**Status**: `complete`.

## Entregas

| Item | Onde | Mudança |
|---|---|---|
| A1 | `gestor/bibliotecas/interface.php` (`interface_listar_ajax`) | Colunas, ordenação e busca extra só da sessão do servidor; `interface_listar_coluna_segura()`; termo escapado com `%`/`_` literais |
| A2 | `interface.php` (`interface_excluir_iniciar`, `interface_status_iniciar`, botões) | `interface_acao_get_exigir_csrf()` nas ações por GET; `interface_url_csrf()` nos botões do cabeçalho e do rodapé |
| A2 | `gestor/assets/interface/interface.js`, `interface-v2/interface-v2.js` (+ `.min.js`) | `window.interfaceUrlCsrf()` nos links de status/excluir e no modal de exclusão |
| — | `gestor/modulos/interface/interface.json` | Variável `alert-csrf-action-invalid` (pt-br e en) |
| Docs | `reference/libraries/interface.md`, `seguranca.md` (pt-br e en) | Avisos removidos; comportamento novo descrito; `docs:extract` do `interface` |

## Commits

- `f3511524` (código e `InterfaceSegurancaReq189Test`); commit seguinte com docs e SDD.

## Validação

- `InterfaceSegurancaReq189Test`: 5 testes, 43 asserções. O `interface.php` roda em processo isolado com dublês. No código antigo, o `SELECT senha` do request entraria no `ORDER BY`.
- Suíte PHP completa: 1257 testes OK. Vitest: 449 OK. `docs:audit`: 268 docs, 0 avisos.
- Lab (`project:sync-core conn2flow-site-local`):
  - `?opcao=status` sem token ou com token errado → redireciona e o produto continua Ativo;
  - com o token da sessão → Inativo, e volta a Ativo;
  - botões da edição saem com `_csrf_token`;
  - AJAX da listagem: busca normal e com apóstrofo (`d'Ouro`, antes erro de SQL) devolvem o produto;
  - injeção por `columns[1][data]`, `columnsExtraSearch` e termo `zzz' OR '1'='1` volta vazia, sem erro e sem vazamento.

## Observações

- **Links de excluir/status montados à mão em módulos de projeto** (fora dos botões do `interface`) passam a ser recusados sem o token. Devem usar `interface_url_csrf()` ou `window.interfaceUrlCsrf()`, como a doc descreve.
- **O `project:update-all` do Lab ficou bloqueado** por duas migrações do site com a mesma versão `20260928120000`: `remove_legacy_checkout_variables` (e-commerce, já commitada e aplicada) e `add_social_post_idempotency_key` (outro agente, não commitada). A segunda precisa ser renumerada por quem a criou. Por isso a validação deste lote usou `project:sync-core`.
