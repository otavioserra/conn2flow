---
id: "BATCH-283"
title: "Passe de navegador confiável para acesso anônimo no site restrito"
status: "implemented-pending-homologation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Cookie assinado (HMAC) deixa um navegador autorizado navegar deslogado com SITE_RESTRICTED_ACCESS ligado; autorizar, revogar este e revogar todos no admin-environment."
summary_medium: "Passe v1|criado|expira|nonce assinado com chave derivada do USUARIO_HASH_PASSWORD e o salt SITE_RESTRICTED_BYPASS_SALT; o porteiro aceita o passe só para quem está sem sessão, depois do noindex.\nPHPUnit 1.823 (19 novos), E2E no Lab 19/19."
---

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
REQ-274; BATCH-283; topologia dupla; autonomo_monitorado; branch `feat/req-274` (a partir da `main` na 3.0.3).

## Live Todo

- [x] Funções puras do passe em `gestor/bibliotecas/gestor.php`.
- [x] Chaves no `config.php` (cookie com sufixo do host, salt, validade).
- [x] Porteiro em `gestor_roteador_acesso_restrito()`.
- [x] `admin-environment`: três ações AJAX, bloco na tela (pt-br/en), JS, variáveis e dicas.
- [x] PHPUnit e E2E no Lab.
- [x] Relatório, commit e push.
- [ ] Release (outro agente) e homologação humana.

## Como funciona

| Peça | Detalhe |
| --- | --- |
| Cookie | `_C2F_RESTRICTED_BYPASS` + sufixo do host (`cookie-restricted-bypass`), `HttpOnly`, `SameSite=Lax`, `Secure` por `gestor_cookie_is_secure()`, caminho `/` |
| Valor | `base64url(v1\|criado\|expira\|nonce).hmac_sha256(payload\|salt)`; chave = `sha256('c2f-restricted-bypass\|' . USUARIO_HASH_PASSWORD)`. Sem segredo, nem emite nem aceita |
| Validade | `SITE_RESTRICTED_BYPASS_DAYS` (padrão 30), limitada a 1–365; criado no futuro (> 5 min) é recusado |
| Revogar todos | novo `SITE_RESTRICTED_BYPASS_SALT` (32 hex) gravado no `.env` pelo `admin_environment_env_write()` |
| Porteiro | dentro de `if(!gestor_permissao_token())`, antes do envio ao `signin/`: `if(gestor_site_acesso_restrito_bypass_atual()) return;`. O `X-Robots-Tag: noindex, nofollow` já saiu antes. Visitante segue anônimo; o passe não abre o painel |

Funções novas: `gestor_site_acesso_restrito_bypass_cookie_nome()`, `_segredo()`, `_salt()`, `_gerar($validadeDias, $salt, $agora, $segredo)`, `_validar($token, $salt, $agora, $segredo)`, `_atual()`.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `SiteAcessoRestritoBypassReq274Test` | 19 testes: emissão/validação, assinatura e payload adulterados, outro segredo, salt trocado, expiração, limites de validade, lixo, sem segredo, segredo derivado, nome do cookie, salt config/env, cookie do navegador, contrato do porteiro (ordem noindex → sem sessão → passe → signin), ações e tela nos dois idiomas |
| PHPUnit do core (`ssh lab`) | 1.823 testes; as 2 falhas da primeira rodada (`RefinamentosReq243Test`: botão sem `data-c2f-dica`) corrigidas, filtro com 62 OK |
| E2E no Lab (`conn2flow.local`, acesso restrito ligado só durante o teste, `.env` restaurado do backup `/root/req274-env-antes.bak`) | 19/19: anônimo → 302; tela com bloco e estado `inativo`; autorizar → `success` e cookie `HttpOnly`; só com o passe → `/pt-br/` e `/pt-br/pro/` 200 com `noindex`; passe não abre `admin-environment/`; passe forjado → 302; revogar este expira o cookie; revogar todos → passe antigo 302 e salt novo no `.env` |
| Pipeline `project:update-all conn2flow-site-local --skip-css` | saída 0 |

## Observações

- Teste com `curl` no Git Bash: com `MSYS_NO_PATHCONV=1`, o `curl.exe` não acha cookie em `/tmp/...`; usar `cygpath -m`. O AJAX do painel exige `X-CSRF-Token` (meta `csrf-token`), que o `global.js` injeta no jQuery.
- A hora do estado sai no fuso do PHP do servidor (`date()`).
