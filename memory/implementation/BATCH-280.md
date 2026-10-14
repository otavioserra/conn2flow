---
id: "BATCH-280"
title: "REQ-271: pagamentos_com_trava dentro do stripe.php e exclusão do pagamentos.php"
status: "complete"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "Trava transacional movida para stripe.php; pagamentos.php removido; fixtures do core e do site carregam stripe."
summary_medium: "O fatal do checkout vinha de gestor_incluir_biblioteca('pagamentos'), que não existe no mapa do config.php e não carregava nada.\nFunção movida sem alteração para stripe.php; Lab sincronizado com --skip-css e sem novo fatal. Permissões de dist corrigidas e sobra remota removida."
---

# BATCH-280 — REQ-271: trava de pagamentos no `stripe.php`

- Projeto: `conn2flow`
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Branch: `main` (árvore compartilhada), sem commit: aguarda revisão humana.
- Requisição: [REQ-271](../human-requests/req-271.md)
- Site coordenado: REQ-126 / BATCH-120 do `conn2flow-site`
- Topologia e autonomia: `dupla`, `autonomo_monitorado`
- Status: `complete`

## Live Todo

- [x] Ler REQ-271, REQ-126 e o código (`pagamentos.php`, `stripe.php`, `config.php`, fixture REQ-265).
- [x] `pagamentos_com_trava($gateway, $callback)` movida sem alteração para `gestor/bibliotecas/stripe.php`, logo após `stripe_transacao_referencias`.
- [x] `git rm gestor/bibliotecas/pagamentos.php`.
- [x] Fixture `tests/Fixtures/req265-payments.php` carrega por `gestor_incluir_biblioteca('stripe')` e verifica que a função existe e que `pagamentos.php` sumiu.
- [x] Suíte completa no `lab`.
- [x] Pipeline do site no Lab com `--skip-css` (Categoria A).
- [x] Permissões de `dist` e sobra remota, via `ssh lab` (14:58 UTC): `dist` e `dist/subscriptions/js.js` em `755 admin:www-data`; sobra `bibliotecas/pagamentos.php` removida. Curl depois: `js.js` 200, checkout 200; log sem fatal novo.
- [x] Homologação assinada em 14/10/2026 pelo agente, por instrução expressa do Humano (checkout `aceleracao` aprovado por ele no Lab).

## Causa

`config.php:361` só mapeia `'stripe' => Array('stripe.php')`. Não há entrada `pagamentos`, então `gestor_incluir_biblioteca('pagamentos')` voltava sem carregar nada e `subscriptions.hooks.php:195` estourava `Call to undefined function pagamentos_com_trava()`. O log do Lab registra o fatal às 14:29 e 14:30 UTC de 2026-10-14.

## Arquivos

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/stripe.php` | + `pagamentos_com_trava()` (corpo idêntico ao original) |
| `gestor/bibliotecas/pagamentos.php` | removido (`git rm`) |
| `tests/Fixtures/req265-payments.php` | stub de `gestor_incluir_biblioteca` carrega só `stripe`; duas verificações novas |

`tests/Unit/PHP/PaymentsReq265Test.php` não mudou: ele só executa a fixture e devolve a saída.

## Evidências

- `php -l gestor/bibliotecas/stripe.php`: sem erros.
- `PaymentsReq265Test` (lab): 1 teste, OK.
- `vendor/bin/phpunit --no-coverage` (lab): 1.788 testes, 20.825 asserções, **1 falha anterior e alheia**: `MemoryDualReq266Test::testSyncRequiresAll45SkillsAcrossTheFiveKits` (41/45 skills num kit). Continua falhando com a árvore de skills limpa depois do commit `7f8edac5` (REQ-075, migração das skills de governança), que entrou durante a sessão; este lote não toca em skills nem no `AiSyncCommand`.
- `./c2f project:update-all conn2flow-site-local --skip-css`: saída 0 em 35 s; banco `+0 ~2 =8326`, órfãos 0; assets 611 atuais.
- Lab, código publicado: `stripe.php` com 1 definição de `pagamentos_com_trava`; `php -r` carregando o `stripe.php` publicado devolve `function_exists = true`.
- A conferência por hash aponta `SOBRA bibliotecas/pagamentos.php` no destino (o rsync não apaga). Removida em seguida pelo `ssh lab`.
- `php-error.log` do Lab: nenhum novo `pagamentos_com_trava` depois do deploy (última ocorrência às 14:30 UTC; pipeline às ~14:50 UTC).

## Permissões do `dist/` duráveis

Depois do `chmod` manual, o pipeline seguinte devolveu `dist/` a `750 admin:admin`: o `rsync -az` de `ai-workspace/en/scripts/projects/publish-project-assets.sh` copia as permissões da origem. A publicação passa a usar `--chmod=D755,F644`. Testes do publicador (`PipelineMinificaAntesReq219Test`, `ProjectAssetsReq264Test`, `ProjectSshPublicPathReq050Test`): 20 OK. Republicado: `dist` 755, arquivos 644, nenhum sem leitura para outros; `dist/subscriptions/js.js` 200.

## Pendências humanas

1. Checkout de ponta a ponta com cartão de teste do Stripe em `https://conn2flow.local/subscription-checkout/?plan=aceleracao`.
2. Revisão e commit (`git add gestor/bibliotecas/stripe.php gestor/bibliotecas/pagamentos.php tests/Fixtures/req265-payments.php memory/...`).
