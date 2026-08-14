# BATCH-111 — Reversão do bloqueio de analytics e fim do laço de verificação de cookie

Change request: [CR-001](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/change-requests/CR-001-reverter-bloqueio-analytics.md)
Validação: [VALIDATION-CHECKLIST.md#batch-111](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md)
Decisão: DEC-106
Reverte: req-109 §3 e §4 (Módulo 2 do BATCH-109)

---

## 1. Reversão do bloqueio de analytics

- Removido o bloco de `gestor_pagina_extra_head_e_javascript()` que zerava `project-javascript` e
  filtrava as filas de head/JS.
- Removida a função `gestor_rastreamento_remover()` (código morto após a reversão) e os 3 casos de
  teste que a cobriam.
- Removido o neutralizador de `fbq`/`dataLayer`/`gtag` do `global.js` e o flag
  `gestor.rastreamentoBloqueado`, mais os 3 casos de teste correspondentes.
- `gestor_pagina_sistema_sem_rastreamento()` renomeada para **`gestor_pagina_rota_sistema()`**: sem o
  bloqueio, o nome antigo descrevia um comportamento que deixou de existir. Ela continua viva porque
  o `sitemap_pagina_elegivel()` (BATCH-110) a usa para excluir rotas utilitárias do sitemap.

## 2. Fim do laço (a correção que resolve o problema de verdade)

A decisão saiu de dentro de `gestor_cookie_verificacao()` e virou
**`gestor_cookie_verificacao_desfecho()`**, função pura na biblioteca `gestor`, com três desfechos:

| Desfecho | Quando | Efeito |
| --- | --- | --- |
| `ignorar` | robô, ou cookie já presente | nada acontece |
| `emitir` | página pública, **ou qualquer rota de sistema** | `Set-Cookie` e segue renderizando |
| `redirecionar` | fluxo que precisa provar o cookie (login/cadastro) | round-trip por `_gestor-cookie-verify/` |

A linha decisiva é a rota de sistema cair em `emitir` mesmo com `exigir_sessao = true`. Sem ela, a
`cookies-is-mandatory/` reentra na verificação ao ser renderizada e o laço se fecha sobre si mesmo.

Extrair para função pura não foi estética: o laço é um defeito medido em produção, e a diretriz de
blindagem de bugs da Chefia pede um teste que falhe se ele voltar. Agora existe.

**`gestor_permissao()` não chama mais `gestor_cookie_verificacao(true)`** — a chamada que o BATCH-109
tinha acrescentado ali era redundante. Quem chega sem sessão vai para `/signin/` logo abaixo, e é o
`/signin/` que faz a prova de cookie; quem chega com sessão já tem o cookie de autenticação e a
função retornaria sem efeito. O único resultado prático era um salto a mais antes do login.

## 3. Rotas de sistema fora do índice

`noindex, nofollow` no `<head>` e `X-Robots-Tag` no cabeçalho HTTP para as rotas de sistema. Enquanto
o laço existiu, `cookies-is-mandatory/` foi entregue a buscador e a coletor no lugar da página pedida
— e ela é `tipo=page` com `sem_permissao`, ou seja, nada impedia a indexação.

## 4. Tokens de robô em duas camadas

- **`gestor_crawler_tokens_padrao()`** — baseline embutido, **sempre ativo**, ampliado de 29 para 50
  tokens. Entraram os bots de anúncio e auditoria que faltavam: `adsbot-google`,
  `mediapartners-google`, `googleother`, `google-extended`, `storebot-google`, `chrome-lighthouse`,
  `gtmetrix`, `ahrefsbot`, `semrushbot`, `mj12bot`, `dotbot`, `screaming frog`, `petalbot`,
  `amazonbot`, `uptimerobot`, `pingdom`, `statuscake`, `better uptime`.
- **`gestor_crawler_tokens_extra()`** — lista do operador, **desligada por padrão**, lida de
  `CRAWLER_TOKENS_EXTRA_ATIVO` / `CRAWLER_TOKENS_EXTRA` no `.env`.
- **`gestor_crawler_tokens_normalizar()`** — aceita vírgula, ponto e vírgula ou quebra de linha,
  normaliza para minúsculas e remove vazios e duplicatas.
- UI em **Ambiente → Configurações do Site** (pt-br/en): toggle, textarea e o baseline exibido como
  referência somente leitura, para o operador não recadastrar o que já existe.

**Por que o baseline continua embutido e sempre ativo:** a detecção tem dois usos. O uso "isentar do
round-trip de cookie" deixou de existir com a correção estrutural. O uso "entregar só o `<head>` com
OpenGraph em página protegida" (req-109 §10) continua. Se a lista inteira fosse desligada por padrão,
o preview de link de página protegida voltaria a mostrar a tela de login por padrão — regressão
silenciosa do que o BATCH-109 consertou. O baseline é composto de identificadores estáveis (WhatsApp,
`facebookexternalhit`, Googlebot não mudam de nome há anos), então não gera manutenção recorrente.

---

## Arquivos alterados

| Arquivo | O quê |
| --- | --- |
| `gestor/gestor.php` | reversão do M2, desfecho da verificação, remoção do round-trip em `gestor_permissao()`, `noindex` |
| `gestor/bibliotecas/gestor.php` | `gestor_cookie_verificacao_desfecho()`, tokens em duas camadas, rename, remoção de `gestor_rastreamento_remover()` |
| `gestor/bibliotecas/sitemap.php` | acompanha o rename |
| `gestor/config.php` | `crawler-tokens-extra-ativo` e `crawler-tokens-extra` |
| `gestor/assets/global/global.js` | remoção do neutralizador de coletores |
| `gestor/modulos/admin-environment/admin-environment.php` | leitura, template e gravação dos tokens |
| `gestor/modulos/admin-environment/admin-environment.js` | coleta dos dois campos novos |
| `gestor/modulos/admin-environment/resources/{pt-br,en}/…` | seção "Robôs e Rastreadores" |
| `gestor/modulos/admin-environment/admin-environment.json` | cache-bust `1.0.0→1.0.1` |
| `tests/Unit/PHP/CrawlersOpenGraphTest.php` | −3 casos do bloqueio, +10 casos (laço, baseline, tokens) |
| `tests/Unit/JS/global-csrf.test.js` | −3 casos do bloqueio |

## Pendência que continua em aberto

O `cookies-is-mandatory/` já indexado precisa sair dos buscadores. O `noindex` faz isso naturalmente
na próxima passagem do robô; se houver pressa, dá para pedir remoção pelo Search Console. Não é ação
de código — fica com o operador.
