# BATCH-170 — Sanitização ANSI no Tailwind CLI e governança de terminal rsync (req-165)

- **Status**: complete
- **Intake**: [req-165.md](../human-requests/req-165.md)
- **Data**: 2026-09-17
- **Classificação**: correção incremental de cache multiplataforma e governança Windows
- **Modo de autonomia**: supervisionado (sem commit, push ou deploy)

## Objetivo

Manter o fingerprint do cache Tailwind idêntico entre Windows e Linux quando o CLI colore a
versão exibida por `--help`, e registrar duas armadilhas operacionais do Windows: sequências ANSI
em saídas capturadas e handles inválidos herdados por terminais reaproveitados no rsync/SSH.

## Implementação

1. `tailwind_recursos_cli_version()` passou a delegar a extração para
   `tailwind_recursos_cli_version_from_output()`, que remove sequências ANSI antes da regex de
   versão.
2. `TailwindRecursosTest` cobre saída Linux sem cor e duas formas de saída ANSI observáveis no
   Windows, todas retornando `4.3.3`.
3. `c2f-shell-and-windows-traps` ganhou as armadilhas 7 e 8: painel novo para rsync que falha com
   `dup() in/out/err failed` e sanitização obrigatória de ANSI em parsers de CLI.
4. A skill foi atualizada nos cinco espelhos (`.codex`, `.claude`, `.cursor`, `.gemini`,
   `.github`) com MD5 idêntico. `.claude/skills/*` permanece ignorado pela política existente do
   repositório.
5. O manifesto Tailwind foi regenerado com `tailwind_version: "4.3.3"`; a repetição da
   sincronização confirmou reaproveitamento integral do cache.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php -l` nos arquivos PHP alterados | **2/2 sem erro** |
| `TailwindRecursosTest` | **17/17**, 106 asserções, exit 0 |
| Primeira `resources:sync` após manifesto contaminado | **237 compilados**, versão reparada para `4.3.3`, exit 0 |
| Segunda `resources:sync` | **0 para compilar, 237 em cache**, 2.856 recursos, exit 0 |
| PHPUnit completo (`--order-by=default`) | **1.178/1.178**, 7.801 asserções, 4 skipped, exit 0 |
| Paridade dos cinco espelhos da skill | MD5 `67314EDC70AFB575D1C1AE815F44C5AC` |

### Ressalvas de ambiente

- O PHP 8.5.8 do host traz as DLLs de SQLite desabilitadas e tinha `OPENSSL_CONF` apontando para um
  XAMPP inexistente. A rodada válida usou `PHP_INI_SCAN_DIR` temporário com
  `pdo_sqlite`/`sqlite3` e o `openssl.cnf` da instalação WinGet; o arquivo temporário foi removido e
  nenhum `php.ini` ou `.env` foi alterado.
- A ordem `defects` reproduziu o acoplamento preexistente entre `ProjectIdentityPassthroughTest` e
  `ForcarAtualizacaoTest` via cache estático de schema, já registrado no BATCH-168. A ordem padrão
  canônica passou integralmente.
- `resources:sync` sincronizou os recursos com sucesso; a publicação opcional em `dist/` apenas
  avisou que `PUBLIC_PATH` não está configurado neste host.

## Resultado

O Windows e o Linux passam a produzir a mesma versão semântica no fingerprint. Depois de reparar
uma vez o manifesto previamente contaminado com `unknown`, execuções subsequentes preservam os
237 recursos em cache. O lote está pronto para revisão humana; nenhum commit ou push foi feito.
