# BL-015 — Pipeline de projeto não sincroniza a tabela `hooks`

- **Tipo**: Bug / DevOps / Hooks
- **Status**: PROMOTED (promovido para [req-174.md](../human-requests/req-174.md), BATCH-179)
- **Severidade sugerida**: ALTA (hook novo ou alterado fica inerte em silêncio depois de um deploy bem-sucedido)
- **Origem**: Achado do BATCH-046 do `conn2flow-site` (REQ-053), 2026-09-22
- **Componentes**: `ai-workspace/en/scripts/dev-environment/updates-manager-database.sh`,
  `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`,
  `gestor/controladores/atualizacoes/atualizacoes-hooks.php`, `cli/src/Commands/ProjectUpdateAllCommand.php`

## Contexto observado

1. O `conn2flow-site` acrescentou em `modulos/host-manager/host-manager.json` o hook
   `subscriptions/checkout.payment-confirmed ➔ host_manager_checkout_payment_hook`.
2. `c2f project:update-all conn2flow-site-local` (`deploy_mode: ssh`) terminou com sucesso quatro vezes, mas a
   tabela `hooks` do Lab continuou com a última sincronização de 2026-09-18: o hook novo não estava lá.
3. O evento foi emitido, `hook_do_action()` não encontrou ouvinte e nada foi registrado — nem em
   `logs/hooks-errors.log`. A conta de hospedagem ficou parada em `pending-payment`.
4. Depois de executar `atualizacoes_hooks_sincronizar()` à mão no servidor (bootstrap do `cron.php`), a tabela
   recebeu o hook e o mesmo evento enfileirou a conta normalmente.

## Causa

`atualizacoes_hooks_sincronizar()` só é chamada em dois lugares:

- `atualizacoes-sistema.php`, no `hookAfterAll()` — caminho do `manager:update-all` (núcleo);
- `controladores/api/api.php:353` — deploy de projeto por upload na API.

O pipeline de projeto (`project:update-all` / `project:sync-db`) chama `updates-manager-database.sh`, que executa
apenas `atualizacoes-banco-de-dados.php`. Esse script não sincroniza hooks. O defeito vale para os três modos de
execução do script (`ssh`, `host` e `docker`), não só para o SSH — no SSH ele é mais visível porque o servidor
não recebe o `manager:update-all` do ambiente local.

## Proposta

1. Sincronizar hooks ao final da etapa de banco do projeto: `atualizacoes-banco-de-dados.php` chama
   `atualizacoes_hooks_sincronizar()` depois do upsert e das migrações (mesma ordem do `hookAfterAll`), ou o
   `updates-manager-database.sh` executa uma segunda chamada remota dedicada.
2. Registrar no relatório final do pipeline quantos hooks foram inseridos, atualizados e removidos, como já é
   feito para as demais tabelas — hoje a ausência é invisível.
3. Opcional: comando `c2f project:sync-hooks <projectID>` para reparar servidores já implantados.

## Critérios de aceite (rascunho)

- Hook novo declarado no JSON de um módulo de projeto aparece na tabela `hooks` após um único
  `project:update-all`, nos modos `ssh`, `host` e `docker`.
- Hook removido do JSON sai da tabela no mesmo deploy.
- O relatório do pipeline mostra o resumo da sincronização de hooks.
- Teste automatizado cobrindo a chamada da sincronização no fluxo do projeto.

## Contorno até a correção

Executar no servidor, dentro de `conn2flow-gestor/`, um script PHP com o bootstrap do `cron.php`
(`$_CRON['ROOT_PATH']`, `$_CRON['SERVER_NAME']`, `require config.php`) que inclua
`controladores/atualizacoes/atualizacoes-hooks.php` e chame `atualizacoes_hooks_sincronizar()`.
