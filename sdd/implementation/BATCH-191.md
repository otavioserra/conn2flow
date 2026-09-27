# BATCH-191: Correções simples da reescrita das docs e do deploy da req-186

Execução da [req-187](../human-requests/req-187.md).

**Status**: `complete`.

## Entregas

| Item | Arquivo | Correção |
|---|---|---|
| 1 | `cli/src/Commands/AuthCookieCommand.php` | Citação POSIX (`posixQuote`) no comando remoto do `generateOverSsh` |
| 2 | `gestor/bibliotecas/gestor.php` | `gestor_variaveis_globais()` ordena: global → módulo atual → demais |
| 3 | `gestor/modulos/admin-atualizacoes/admin-atualizacoes.php` | Ramo `?plano=` usa `$dirLogs` |
| 4 | `gestor/config.php` | Removidos `api-cliente` e `cpanel` do registro de bibliotecas |
| Docs | `ai-workspace/{pt-br,en}/docs/reference/…` | `gestor.md`, `libraries/index.md` e `modules/admin-atualizacoes.md` sem os avisos dos bugs corrigidos |

## Commits

- `7c480e5e` — itens 1 e 2 (com teste do item 1).
- Commit seguinte do BATCH-191: itens 3 e 4, `CorrecoesSimplesReq187Test`, docs e SDD.

## Validação

- `ProjectSshDeployReq034Test`: 25 testes OK. A asserção nova falharia no código antigo (10 chamadas `escapeshellarg($…)` no bloco SSH).
- `CorrecoesSimplesReq187Test`: 3 testes / 44 asserções OK; sem as correções dos itens 3 e 4, 2 falhas (`Biblioteca registrada sem arquivo: api-cliente.php`).
- Lab (`conn2flow-site-local`):
  - `auth:cookie` a partir do Windows: cookie gerado (antes: "Missing required --gestor or --result option");
  - `/documentation/` → "Documentação", `/en/documentation/` → "Documentation" (antes: "Checkout");
  - `subscriptions-config/` e `subscriptions-service-stages/adicionar/` com o próprio título.
- `docs:audit`: 0 erros.
