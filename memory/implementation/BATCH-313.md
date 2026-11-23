---
id: "BATCH-313"
title: "REQ-304 — Mensageria do Core: MAIL_REDIRECT_TO, senha fora do log, fila multi-canal e admin-comunicacao"
status: "implemented-pending-review"
date: "2026-11-23"
author: "executor"
target_repo: "conn2flow"
request: "REQ-304"
topology: "triade"
autonomy: "autonomo_monitorado"
summary_short: "Senha SMTP mascarada, redirecionamento de teste travado em produção, AltBody/timeout, tabela comunicacao_mensagens, contrato comunicacao.drivers, fila híbrida com rate limit e retry, módulo admin-comunicacao; PHPUnit 32/32 e Lab 11/11."
---

# BATCH-313 — REQ-304 (Categoria C: biblioteca, migration e telas Tailwind)

- **Requisição:** [REQ-304](../human-requests/req-304.md).
- **Branch:** `feat/req-304-comunicacao-fila-e-redirect`, criada sobre `91017a3e` (a `origin/main` está 2 commits à frente, só skills/MDD da REQ-083, sem sobreposição). **Sem commit** (autônomo monitorado sem pedido de commit).
- **Árvore compartilhada:** a `main` local já tinha WIP sem commit da REQ-301 (`config.php`, `pagamentos-gateways.php`, `mercadopago.php`...) e da REQ-303. Esse trabalho veio junto para a branch como mudança não commitada e não foi alterado. Em `gestor/config.php` convivem o hunk da REQ-301 (3 linhas, `bibliotecas-dados`) e o desta REQ (bloco `email`). Na hora do commit, separar por hunk.
- **Concorrência:** o revisor do BATCH-312 gravou em `memory/reviews/` e no `VALIDATION-CHECKLIST.md` às 16:29–16:30, durante esta sessão. Nada dele foi reescrito.

## Alterações

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/comunicacao.php` (1.2.0) | `comunicacao_email()` vira fachada: `comunicacao_email_preparar()` (layout, assinatura, variáveis, imagens) → `comunicacao_enviar('email', ...)`. Novas: `comunicacao_email_servidor()` (credencial resolvida na hora do envio, nunca persistida), `comunicacao_email_montar_envio()`, `comunicacao_email_transmitir()` (redirecionamento, `Timeout`, `AltBody`, `X-Original-To`), `comunicacao_redirecionamento_status()/destino()`, `comunicacao_redirecionar()`, `comunicacao_html_para_texto()`, `comunicacao_texto_sem_segredo()`, fábrica de mailer injetável. Log de erro com `comunicacao_mascarar_segredos($server)` no lugar de `print_r($server)` |
| `gestor/bibliotecas/comunicacao-fila.php` (novo) | Contrato `comunicacao.drivers` (`hook_apply_filters('comunicacao','drivers')`), driver nativo `email`, fachada `comunicacao_enviar()`, modo híbrido, idempotência (com tratamento de corrida no índice único), rate limit por minuto, worker com reserva atômica (D-040), backoff 1/5/15/60 min, recuperação de travados (>10 min), retenção, listagem/estatísticas/cancelar/reenviar. SQL por executor injetável (banco.php em produção, PDO/SQLite nos testes) e relógio injetável |
| `gestor/db/migrations/20261123120000_create_comunicacao_mensagens_table.php` (novo) | Tabela `comunicacao_mensagens` (utf8mb4 no MySQL) com índices: idempotência (único), fila `(status, proxima_tentativa_em, prioridade)`, painel `(canal, status, data_criacao)`, retenção `(status, data_criacao)`, rate limit `(reservado_em)`, `id_hosts` |
| `gestor/config.php` | `$_CONFIG['email']`: `ambiente` (APP_ENV; sem ele, `production`, salvo `DEVELOPMENT_ENV=true`), `redirect_to`, `redirect_permitir_producao`, `timeout` (15), `fila` (modo, rate_limit 30, max_tentativas 4, retencao_dias 90) |
| `gestor/autenticacoes.exemplo/dominio/.env` | `EMAIL_TIMEOUT`, `APP_ENV`, `MAIL_REDIRECT_TO`, `MAIL_REDIRECT_ALLOW_PRODUCTION`, `COMUNICACAO_*` documentados |
| `gestor/modulos/admin-environment/*` (1.0.6) | Campo `MAIL_REDIRECT_TO` na aba de e-mail (pt-br/en), estado real (ativo / travado em produção / desligado + APP_ENV), validação no salvar, 7 variáveis por idioma |
| `gestor/modulos/admin-comunicacao/*` (novo, 1.0.1) | Painel Tailwind: banner de modo teste, 4 indicadores, aba Fila & histórico (filtros por status, canal, busca e período; paginação; modal com metadados e preview em `iframe sandbox=""`; reenviar e cancelar) e aba Configurações (modo, limite, tentativas, retenção, `MAIL_REDIRECT_TO`, e-mail de teste). 93 variáveis por idioma. Rotina `comunicacao-fila-processar` (`minutario`, `* * * * *`) em `admin-comunicacao.cron.php` |
| `gestor/resources/{pt-br,en}/modules.json`, `user_profiles_modules.json` | Módulo no grupo `administracao-sistema` (ícone `mail`) e acesso do perfil `administradores` |
| Derivados do pipeline | `db/data/{Modulos,Paginas,Variaveis,CronTarefas,UsuariosPerfisModulos}Data.json`, `schema-metadata.json`, `*.min.js`, `*.precompiled.css`, `minify-manifest.json`, `.tailwind-build-manifest.json` |
| `tests/Unit/PHP/ComunicacaoFilaReq304Test.php` (novo) | 32 testes / 158 asserções |
| `memory/validation/req304-browser.cjs` + `evidence-req304/` | Roteiro Playwright repetível do Lab e evidências |

## Decisões de execução

1. **Produção por padrão.** O Core não lia `APP_ENV` em lugar nenhum. Sem ele, a instalação é tratada como produção (redirecionamento travado), salvo `DEVELOPMENT_ENV=true`. Por isso o Lab recebeu `APP_ENV=staging`.
2. **Retorno retrocompatível.** `comunicacao_email()` devolve `true` quando a mensagem foi enviada ou aceita para nova tentativa, e `false` quando o e-mail está desligado ou a falha é definitiva (sem tentativas). Os 7 chamadores testam só a veracidade.
3. **Credencial efêmera não entra na fila.** Teste de SMTP do admin-environment (`EMAIL_TESTS`) e `servidor` passado pelo chamador: envio imediato, histórico gravado, `max_tentativas = 1`. A senha não pode ser persistida para retry.
4. **O redirecionamento é aplicado no envio, não no enfileiramento.** A trava de produção vale no momento real do SMTP. O histórico guarda o assunto original; o prefixo `[TESTE → ...]` existe só na mensagem enviada.
5. **Retenção automática** (DELETE de `enviado`/`cancelado` acima de N dias) foi pedida explicitamente pela REQ-304. `falhou` e `pendente` nunca são removidos; `0` desliga.
6. **Sem migration aplicada,** tudo segue síncrono como antes (`comunicacao_fila_disponivel()`).
7. **Ajax `listar` é reservado.** `interface_ajax_finalizar()` despacha `listar` para a listagem genérica. A opção do painel chama-se `mensagens` (fatal achado no Lab, rodada 2).

## Validação

- `php -l` em todos os PHP novos/alterados e `node --check` nos JS: sem erro.
- `ComunicacaoFilaReq304Test`: **32/32, 158 asserções** — senha fora do log e do `payload_json`; máscara recursiva; TO/CC/BCC → 1 destinatário, prefixo e `X-Original-To` sem duplicar; bloqueio em produção com aviso; produção implícita sem APP_ENV; flag explícita; endereço inválido; histórico com original; AltBody (head/style/script fora, listas, links) e AltBody do chamador; timeout 15/7; híbrido (sucesso, falha → fila +1 min, fila explícita, `sempre_fila`, rate limit + worker no minuto seguinte); API pública retrocompatível; e-mail desligado; sem tabela → síncrono; idempotência; reserva atômica; backoff até `falhou` com limpeza de temporários; prioridade; travados; retenção; contrato da rotina; cancelar/reenviar; listagem/filtros/estatísticas; driver `whatsapp` por hook real (HookManager); canal sem driver.
- Suíte completa (PHP 8.5.8, Windows): **2.069 testes, 21 falhas + 1 erro = 22**, a mesma contagem da linha de base do BATCH-312 (2.037 testes). Os 22 ficam em `DashboardLousa*`, `DashboardWidgetsPermissoes*`, `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` e `ManutencaoReq210Test` (texto de `api.php`), sem relação com o lote. O +32 é desta suíte.

## Lab (`conn2flow-site-local`, https://conn2flow.local/)

- **Rodada 1:** `php cli/c2f.php project:update-all conn2flow-site-local` (Categoria C, lote do Core levado ao projeto pelo mesmo caminho dos BATCH-282/310). Saída 0 em 49 s. Phinx `20261123120000 CreateComunicacaoMensagensTable: migrated`. `SYNC_FIM`: `modulos +2`, `paginas +2 ~3`, `variaveis +200`, `cron_tarefas +1`, `usuarios_perfis_modulos +1`. Hash: 861 arquivos iguais à origem. Origem do site: `feat/req-156-seo-whatsapp-canonicals`, igual ao publicado no BATCH-149, sujo só em `memory/`.
- **`.env` do Lab:** backup `autenticacoes/conn2flow.local/.env.req304-antes.bak`; acrescentados `APP_ENV=staging` e `MAIL_REDIRECT_TO=otavio@conn2flow.com`. O Lab já tinha SMTP real (`mail.conn2flow.com`, `EMAIL_ACTIVE=true`) e `DEVELOPMENT_ENV=false`.
- **Navegador (rodada 1):** HTTP 500 no carregamento da lista. `php-error.log`: `TypeError` em `interface_listar_ajax()` chamado por `interface_ajax_finalizar()` → colisão com a opção reservada `listar`. Corrigido (decisão 7).
- **Rodada 2:** `project:update-all conn2flow-site-local --skip-css` (só PHP/JS). Saída 0 em 37 s, 861 arquivos conferidos.
- **`node memory/validation/req304-browser.cjs`: 11/11, console sem erro** ([resultado.json](../validation/evidence-req304/resultado.json)):
  - admin-environment: `#mail_redirect_to` = `otavio@conn2flow.com`, estado "Redirecionamento ATIVO", APP_ENV `staging`;
  - admin-comunicacao: banner "MODO TESTE ATIVO ... otavio@conn2flow.com"; overflow 0 a 1280 e a 390 px; nenhum marcador cru;
  - Configurações carregadas da mesma fonte: híbrido / 30 / 4 / 90 / staging;
  - **e-mail de teste para `test_user_validacao@testuser.com`**: id 1, `enviado` pelo SMTP do Lab; linha do histórico "otavio@conn2flow.com — redirecionado de test_user_validacao@testuser.com";
  - modal: destinatário efetivo e original corretos, `iframe sandbox=""`, preview de 511 bytes, fechamento por Escape;
  - reenvio manual: nova linha (id 2, `enviado`), original intacta; a busca por `testuser.com` acha as duas.
- **Rotina agendada no Lab:** `cron-23-11-2026.log` → `[19:50:01] comunicacao-fila-processar [sucesso] 7ms :: recuperadas=0 esgotadas=0 processadas=0 enviadas=0 reagendadas=0 falhas=0 ignoradas=0 removidas=0 limite_restante=30` (sincronizada pelo pipeline, rodando no tick `minutario`).
- Capturas: `admin-environment-email.png`, `admin-comunicacao-{desktop,config,modal,mobile}.png` em `memory/validation/evidence-req304/`.

## Pendências

- **Confirmar a chegada na caixa `otavio@conn2flow.com`** das duas mensagens com assunto `[TESTE → test_user_validacao@testuser.com] Teste de comunicação ...` (o SMTP aceitou; a caixa só o operador vê).
- **Capa do módulo** (`gestor/assets/modulos/covers/admin-comunicacao.webp` + `manifest.json`, padrão `c2f-module-visual-assets`): esta sessão não tem gerador de imagem.
- **Agendador do Lab dispara `minutario` a cada 5 min** (`*/5` no crontab do HestiaCP), e não a cada minuto. O envio híbrido não depende dele. Já o retry e a fila andam em passos de 5 min no Lab. Ajustar para `* * * * *` é decisão de operação.
- **Preview de 511 bytes** indica que no banco do Lab o `layout-emails` não envolveu o corpo (o e-mail saiu só com o HTML da mensagem). Comportamento anterior ao lote, a conferir à parte.
- **Conexão `mysqli` em `utf8` (3 bytes):** a tabela nasce utf8mb4, mas emoji no assunto/HTML pode falhar no INSERT pela conexão. Nesse caso a mensagem sai direto, sem histórico. Tema da skill `c2f-mysql-utf8-emoji-encoding`.
- **Pré-existente, preservado sem mudança:** em `comunicacao_email_servidor()`, `isset($_CONFIG['email']['server']['secure'])` é verdadeiro também para `EMAIL_SECURE=false`, então o SMTPS fica sempre ligado. Manter o comportamento evitou regressão; correção merece REQ própria.
- Revisão técnica (`c2f-reviewer-agent`: diff, CSRF, `variables.json`, `ai:sync`, `css:audit`), commit por hunk, merge e homologação humana.

## Relatório REQ-081

Entregue na resposta final do executor desta rodada (2026-11-23), seções 1–5.

## Rodada 2 — Pós-revisão (2026-11-23)

- **Método e CSRF.** Comunicação exige POST para salvar, testar, cancelar e reenviar. Ambiente Config exige POST para salvar e lê `MAIL_REDIRECT_TO` do corpo. Os dois painéis passam pela proteção CSRF e pela permissão do roteador. No Lab, as cinco ações responderam 405 com `Allow: POST` por GET e 403 por POST sem token. O perfil `consumidores` recebeu 302 ao abrir Comunicação e o painel não foi renderizado.
- **Admissão e reserva.** HTTP e worker usam a mesma trava MySQL por banco para contar e reservar. A trava é liberada antes de chamar o driver. O UPDATE do worker revalida o vencimento. A finalização confere status, horário de reserva e número anterior de tentativas. A retenção preserva as reservas do minuto corrente. Se a cota estiver cheia, o envio híbrido entra na fila. O envio com credencial efêmera retorna falha quando não há capacidade, pois a credencial não pode ser persistida.
- **Concorrência.** Os testes de fila exercitam a tentativa concorrente de worker durante envio HTTP e a disputa entre finalização tardia e recuperação. No Lab, duas conexões MySQL verificaram que a segunda sessão recebe 0 com a trava mantida e 1 após a liberação. A sonda MySQL não envia mensagens.
- **REQ-203.** A expectativa do catálogo global passou de 39 para 40 vínculos, incluindo `admin-comunicacao` em `administradores`. Os testes preservam as verificações de contrato e de localização dos recursos.
- **CSS.** Recompilação dos dois recursos de origem, seguida de `css:rebuild --project=conn2flow-site-local --tipo=paginas --id=admin-comunicacao --todos --confirmar-remoto` após a última sincronização. A auditoria incorpora `controles.css` para layouts administrativos Tailwind, descarta marcadores de template e usa seletores de dados no JS das abas. Ambos os idiomas passaram com 115 classes usadas, zero descobertas, zero resíduos Fomantic e assinatura atual. [Evidência de CSS](../validation/evidence-req304/pos-revisao-css-audit.json)
- **PHPUnit.** Filtro dos dois lotes e da REQ-203 com 65 testes, 607 asserções e quatro depreciações da configuração. Suíte completa com 2.078 testes, 21 falhas, um erro e nove skips. As 22 ocorrências são as mesmas da base do BATCH-313. Commit de código `99079212` com apenas o bloco de e-mail de `gestor/config.php`.

O Git Bash foi necessário no Windows para o pipeline por SSH. A execução com `--skip-css` repôs as sementes SQL e exigiu uma última regeneração direcionada para restaurar as assinaturas. Esses passos ficam como insumo da rodada para o relatório REQ-081 ao AI Workspace.
