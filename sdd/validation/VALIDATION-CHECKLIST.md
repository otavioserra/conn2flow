# Validation Checklist

## BATCH-213 — README e descrição do GitHub (req-205)

- [x] `README.md` e `README-PT-BR.md` com a mesma estrutura; 39 links relativos em cada, todos existentes.
- [x] `conn2flow.com/`, `/plataforma/` e `/pro/` respondem 200.
- [x] Descrição, site e tópicos do repositório atualizados no GitHub.
- [ ] Revisão humana do texto e mesclagem.

Detalhes: [BATCH-213](../implementation/BATCH-213.md).

## BATCH-208 — Recuperação de arquivos do servidor (req-200)

- [x] Inventário unitário: projeto editado, core ausente, precedência, arquivo fora do manifesto, filtros e pastas privadas.
- [x] ZIP: teste de extração recusa `.env`, `autenticacoes/`, `logs/`, `backups/`, `temp/`, `..` e caminhos absolutos; confere hash do arquivo aceito.
- [x] Motor comum: `manter`, `mesclar`, `sobrescrever` na descida sem regra de deploy local.
- [x] PHPUnit focado da req-198/199 e testes novos: 54 testes, 264 asserções (dois avisos de depreciação).
- [x] `docs:audit` sem avisos nos arquivos tocados; `git diff --check` limpo.
- [ ] Homologação HTTP e CLI no tenant isolado depois que a rota nova for integrada ao ambiente. Nenhum deploy no tenant compartilhado durante este lote.

## BATCH-200 — Página inicial e layout por perfil (req-196)

- [x] Migração Phinx aplicada e revertida no MariaDB 11.8.8 isolado; colunas `pagina_inicial` VARCHAR NULL e `layouts_users_profiles` LONGTEXT NULL, com registros legados preservados.
- [x] Perfil grava apenas rotas ativas; login valida a rota novamente e conserva fallback `dashboard/`.
- [x] CRUDs aceitam apenas pares de layout e perfil existentes no idioma corrente e exigem layout padrão ativo ao ligar o toggle; roteador aplica o par compatível e conserva o fallback.
- [x] `resources:sync`: exit 0, 2.924 recursos, incluindo dez componentes bilíngues; manifests legados omitem o campo, preservando mapeamentos criados no Gestor.
- [x] Biblioteca registrada no bootstrap e incluída por `gestor_incluir_biblioteca`; HTML em componentes de recursos; selects de Layout e Perfil gerados por `interface_formulario_campos` com Fomantic-UI; página inicial buscada por AJAX (mínimo de 2 caracteres, máximo de 20 resultados).
- [x] PHPUnit: 1.289 testes, 10.152 asserções, exit 0 com `OPENSSL_CONF` válido; `Req196LayoutPorPerfilTest`: 6 testes, 16 asserções, inclusive sincronização SQL e renderização de componentes.
- [x] Vitest: 34 arquivos, 455 testes, exit 0; autocomplete AJAX, toggle e repetidor dos dois CRUDs exercitados no DOM; sintaxe PHP/JS aprovada.
- [x] MariaDB 11.8 isolado: 11 verificações dos validadores Layout/Perfil, campo de acesso e destino de login passaram com registros ativos, inativos e de outro idioma.
- [x] `project:update-all conn2flow-site-local`: oito estágios, exit 0; `assets:minify --verificar` e `git diff --check`: exit 0.
- [x] Playwright no Lab: três formulários HTTP 200 sem erros de console; busca AJAX retornou oito sugestões para `dash`; perfil salvo e recarregado com `dashboard/`; `publisher-pages` gravou JSON; toggle desligado no `admin-paginas` gravou `NULL`.
- [x] Rota temporária autenticada usou layout 3D mapeado e visitante usou layout padrão. Os dois registros de página, o vínculo de publicação e o perfil de teste foram removidos em transação.
- [x] Destino de login válido e fallback exercitados por testes PHP em banco isolado. Não houve login interativo com usuário temporário; a sessão administrativa do Lab foi obtida por `auth:cookie`.

## BATCH-197 — Ativação de parcelamento no PaymentIntent Stripe (req-193)

- [x] Payload legado sem `payment_method_options` quando a opção não é informada.
- [x] Opção habilitada serializa `payment_method_options[card][installments][enabled]`.
- [x] Teste PHPUnit focado (3 testes/5 asserções), `php -l` e `git diff --check` aprovados.

## BATCH-184 — Migração do acervo de docs, ondas 1 e 2 (req-179)

- [x] `docs:audit --json`: 0 erros; `legacy` = 0 em pt-br e en; 41 bibliotecas e 4 conceitos da onda 1 documentados do código.
- [x] PHPUnit das docs (`DocsBuildReq178Test`, `DocsToolingReq177Test`): 16/16 (100 asserções) no último commit de ferramental do batch.
- [x] Comportamentos citados nas docs conferidos executando o código quando possível (`formato.php`, callouts, extrator).
- [x] Lab: `docs:build` + `project:update-all conn2flow-site-local`; `page:inspect` sem erros de console em `/docs/reference/libraries/cron/` e `/docs/reference/modules/usuarios/`; screenshots conferidos.
- [ ] Revisão manual do Humano no Lab (lista de testes no relatório final).

## BATCH-183 — Parser docs:build (req-178)

- [x] `vendor/bin/phpunit --filter "DocsBuildReq178Test|DocsToolingReq177Test"`: 12/12 (84 asserções).
- [x] Suíte completa no Windows: 1.231/1.232 — única falha `CoreHelpersTest` (openssl.cnf, ambiente).
- [x] `docs:build --project=conn2flow-site-local` idempotente (2ª execução: 0 arquivos).
- [x] Lab: `project:update-all` sem órfãos; HTTP 200 em `/docs/`, índices, 3 docs, `/en/docs/...` e `/docs/llms.txt`; `page:inspect` sem erros de console; screenshots pt-br/en conferidos (`temp/docs-*.png`).

## BATCH-181 e BATCH-182 — Limpeza do ai-workspace e ferramentas de documentação (req-176, req-177)

- [x] Branch `legacy/ai-workspace-pre-docs` preserva `agents-history/`, `prompts/`, `templates/`; 179 arquivos removidos da `main`, `scripts/` intocado.
- [x] `php cli/c2f.php docs:audit --json`: 8 docs piloto (pt-br + en) com score 0; 0 erros.
- [x] `php cli/c2f.php docs:extract --all --check`: exit 0.
- [x] `vendor/bin/phpunit --filter DocsToolingReq177Test`: 8/8 (34 asserções).
- [x] Suíte completa no Windows: 1.227/1.228 — única falha `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` (`openssl.cnf` ausente no PHP WinGet, ambiente, conhecida).

## BATCH-180 — Renovação silenciosa de CSRF e retry transparente (req-175)

- [x] `gestor_csrf_resposta_invalida()` devolve `code: CSRF_INVALID_OR_EXPIRED` no JSON e o cabeçalho `X-Gestor-Csrf-Error` nos ramos JSON e HTML.
- [x] Rota `_gestor-csrf-token/` isenta de CSRF: visitante e usuário logado recebem `200` com token; login expirado recebe `401 AUTH_EXPIRED` com `X-Gestor-Auth-Redirect`, sem token; resposta `no-store`.
- [x] `global.js`: renovação única com fila; retry transparente em `fetch` e XHR (o que cobre o `$.ajax`); propagação para a `<meta>`, `gestor.csrfToken`, campos ocultos e a página hospedeira.
- [x] 403 legítimo (ACL) não entra em retry; no máximo uma repetição por requisição; XHR síncrono e GET ficam fora.
- [x] `visibilitychange` com limite de 30 s; a checagem proativa não redireciona.
- [x] Cache-bust: `global.min.js` regenerado e owner `global` do `asset-versions.json` atualizado.
- [x] PHPUnit focado 15/15; Vitest focado 23/23.
- [x] PHPUnit completo 1.220/1.220 (4 skipped, `OPENSSL_CONF` explicitado), exit 0; Vitest completo 449/449, exit 0; `git diff --check` exit 0.
- [ ] Homologação runtime (expirar sessão e confirmar retry transparente em tela real).

## BATCH-179 — Sincronização automática de hooks em deploy de projeto (req-174)

- [x] O fluxo normal de `atualizacoes-banco-de-dados.php`, com ou sem `--project`, executa `atualizacoes_hooks_sincronizar()` depois de migrações/dados.
- [x] O resumo final reporta hooks processados por módulos, plugins, projeto e total.
- [x] `project:sync-hooks <projeto-id>` está registrado e usa `--hooks-only` sobre o transporte compartilhado SSH/Host/Docker.
- [x] Idempotência e remoção declarativa cobertas por teste comportamental: 3 testes, 27 asserções, exit 0.
- [x] PHPUnit completo: 1.205 testes, 7.908 asserções, 4 pulados, exit 0 (com `OPENSSL_CONF` do PHP 8.5 explicitado).
- [x] Vitest completo: 30 arquivos, 426 testes, exit 0.
- [x] Sintaxe PHP/Bash/JSON e `git diff --check`: exit 0.
- [x] Lab HestiaCP: Deploy em `conn2flow-site-local` concluiu com HTTP 200, `Hooks => total=80 (módulos=12, plugins=0, projeto=68)`, confirmando a sincronização automática de hooks sem necessidade de intervenção manual no banco.

## BATCH-178 — Query String em 301 e Expurgamento de Blocos no Widget Forms (req-173)

- [x] Roteador 301 repassa `'querystring' => true` na chamada de `gestor_roteador_erro()` em `gestor/gestor.php`.
- [x] Concatenação extraída para `gestor_redirecionar_montar_url()` em `gestor/bibliotecas/gestor.php`, tratando com segurança destinos já parametrizados (`&`), sem deixar `?` órfão.
- [x] `forms_widget_limpar_fragmentos()` criada e aplicada no retorno de `forms_widget_render_inline()` em `gestor/modulos/forms/forms.widget.php`, expurgando `option-choice`, `option-select`, `password-toggle` e `<template>` vazio, além de sanitizar marcadores residuais `@[[item#*]]@`, `@[[option#*]]@` e `@[[password#*]]@`.
- [x] Selects, rádios, checkboxes e alternância de visibilidade de senha permanecem 100% operacionais no formulário gerado.
- [x] PHPUnit focado REQ-173: 12 testes, 33 asserções, exit 0 (`Req173Redirecionamento301Test` e `Req173FormsWidgetFragmentosTest`).
- [x] PHPUnit completo: 1.202 testes, 7.881 asserções, 4 pulados, exit 0.
- [x] Vitest: 30 arquivos, 426 testes, exit 0.
- [x] `assets:minify --verificar`: 0 derivados desatualizados; `git diff --check`: exit 0.
- [x] Runtime Lab (`conn2flow.local`): confirmado 301 preservando parâmetros/UTMs, checkout sem contorno renderizando 0 marcadores crus.

## BATCH-177 — Fallback reCAPTCHA v3/v2 em autenticação Tailwind (req-172)

- [x] Interceptador nativo cobre login, OAuth, cadastro e recuperação de senha, com ações fixas no cliente e submissão nativa após obter o token.
- [x] Backend usa ação esperada definida pelo servidor e solicita reCAPTCHA v2 quando o v3 falha ou fica abaixo do score aceito.
- [x] Resposta v2 é validada por `gestor_captcha_validar(null, ['v2' => true])` e, quando aprovada, libera a validação de credenciais.
- [x] PHPUnit focado: 147 testes/825 asserções. PHPUnit completo: 1.190 testes/7.848 asserções, 4 pulados, exit 0.
- [x] Vitest focado: 81 testes. Vitest completo: 30 arquivos/426 testes, exit 0.
- [x] `php -l`, `node --check`, `assets:minify --verificar`, `resources:sync` e `git diff --check`: aprovados.
- [x] Lab HestiaCP: `project:update-all conn2flow-site-local` concluiu oito estágios; Playwright confirmou v3 inválido → checkbox v2 → validação de credenciais em `conn2flow.local` com chaves oficiais de teste.
- [x] Configuração temporária do Lab restaurada e review findings-first concluído sem finding bloqueante.

## BATCH-176 — Cloudflare Turnstile (req-171)

- [x] `php -l` nos 6 PHP alterados/criados e `node --check` nos 3 JS de autoria: sem erros.
- [x] PHPUnit com `OPENSSL_CONF` válido: 1.187 testes, 7.843 asserções, 4 pulados, exit 0. Os 6 testes de `CaptchaTurnstileTest` cobrem sucesso, bloqueio, rede, JSON inválido, segredo inválido, token ausente e Google.
- [x] Vitest: 423/423, exit 0.
- [x] `assets:minify --verificar`: 0 derivados desatualizados; `resources:sync`: 2.882 recursos, exit 0; `git diff --check`: exit 0.
- [x] Lab HestiaCP: `project:update-all conn2flow-site-local` concluiu 8 estágios; SQL `paginas` confirmou `admin-environment` em `en`/`pt-br` com Turnstile. `dist/` opcional não foi publicado por ausência de `PUBLIC_PATH`.
- [x] Playwright em `https://conn2flow.local/` com chaves oficiais de teste: `/signin/`, `/signup/`, `/forgot-password/` e `/contact/` HTTP 200, widget e `cf-turnstile-response` presentes, zero erros de console. Capturas em `temp/batch-176-*-turnstile.png`.
- [x] Painel: seletor e bloco Turnstile visíveis na aba Usuário; teste AJAX retornou “Chaves e token do Turnstile válidos.”; salvar retornou HTTP 200/`status=success` e gravou as quatro variáveis esperadas. `.env` do Lab restaurado após os testes.
- [x] Limite da inspeção: o `page:inspect` padrão usa `networkidle`, que expirou com o script da Cloudflare; Playwright com `domcontentloaded` e captura visual confirmou a renderização. O helper `auth:cookie --project` falhou na montagem de aspas SSH no Windows; o gerador existente foi executado diretamente no Lab.

Use este checklist para validar batches no conn2flow sem perder de vista o baseline operacional do repositÃ³rio.

## Onboarding SDD repo-wide

- [x] CLAUDE.md instalado na raiz do repositÃ³rio
- [x] .claude/ instalado com agents, rules, skills e settings do Claude Code
- [x] .github/copilot-instructions.md instalado
- [x] .github/instructions/, .github/prompts/, .github/skills/ e .github/agents/ com artefatos SDD do Copilot
- [x] sdd/scripts/hooks/ criado com hooks de sessÃ£o SDD
- [x] sdd/human-requests/ ativo
- [x] sdd/README.md, process/, implementation/, validation/ e decisions/ criados
- [x] sdd/00-baseline-architecture.md criado com preservaÃ§Ã£o do legado

## Checklist mÃ­nimo por batch

- [ ] O batch estÃ¡ registrado em sdd/implementation/BATCH-INDEX.md
- [ ] O impacto foi comparado contra sdd/00-baseline-architecture.md
- [ ] A menor validaÃ§Ã£o executÃ¡vel do slice foi definida antes de editar mais do que o necessÃ¡rio
- [ ] Scripts, tasks ou paths alterados continuam coerentes com dev-environment/data/environment.json
- [ ] NÃ£o houve reescrita ampla do legado sem mudanÃ§a normativa aprovada
- [ ] O review findings-first foi feito quando a mudanÃ§a ficou pronta para avaliaÃ§Ã£o

## Quando o batch tocar operaÃ§Ã£o local

- [ ] Validar a task do VS Code mais prÃ³xima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execuÃ§Ã£o correspondente
- [ ] Se tocar sincronizaÃ§Ã£o de projeto, validar source/target/path no environment.json
- [ ] Se tocar plugins, validar o fluxo na Ã¡rvore dev-plugins/

## EvidÃªncia mÃ­nima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendÃªncias ou riscos restantes

## Regra final

Se nÃ£o houver validaÃ§Ã£o executÃ¡vel no slice atual, o batch deve registrar explicitamente por que a validaÃ§Ã£o ficou documental ou manual.

## ValidaÃ§Ãµes de Batches Arquivados

Para manter o checklist de validaÃ§Ãµes leve e eficiente (teto de 25 blocos ativos na REQ-051), as validaÃ§Ãµes anteriores foram arquivadas:
- **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)** (BATCH-001 a BATCH-017)
- **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)** (BATCH-018 a BATCH-053)
- **[validation-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-054-093.md)** (BATCH-054 a BATCH-093)
- **[validation-094-110.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-094-110.md)** (BATCH-094 a BATCH-110)
- **[validation-111-134.md](archive/validation-111-134.md)** (17 blocos históricos entre BATCH-111 e BATCH-134; ordem documental preservada)
- **[validation-136-173.md](archive/validation-136-173.md)** (BATCH-136 a BATCH-173)
