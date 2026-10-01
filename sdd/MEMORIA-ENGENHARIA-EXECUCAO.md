# Memória de Engenharia — Execução

> **Propósito**: contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: é proibido podar abaixo de 50 KB / 200 linhas; emitir alerta preventivo nesse patamar, podar obrigatoriamente ao atingir 75 KB / 300 linhas e mirar ~25 KB, preservando 20 a 25 tarefas e aprendizados recentes. O fim da sessão ou do batch não aciona poda.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors, transportes e mounts.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.
- Suíte PHP no Windows (PHP 8.5 WinGet): sem `openssl.cnf` o `CoreHelpersTest` falha. Exportar
  `OPENSSL_CONF=<dir do php>\extras\ssl\openssl.cnf` antes do `composer test` dá verde; o `lab`
  (WSL/HestiaCP) segue como referência. Divergência só no Windows é do ambiente.

## Tarefas recentes

### 2026-10-01 — BATCH-210 (req-202): sementes declarativas e compilação seletiva

- A configuração de tabela no descritor `gestor/modulos/usuarios/usuarios.json` prevalece sobre `tables_config.json` ao gerar schema; mantenha as regras de preservação alinhadas nos dois lugares.
- `resources:sync --only=... --skip-css --no-origin-update --no-assets` grava apenas os Data.json selecionados e pula Tailwind; a publicação opcional de `dist/` ainda pode avisar sem `PUBLIC_PATH`, sem invalidar a sincronização.
- Builds `--resource=<id>` precisam reter entradas e sidecars não selecionados no `.tailwind-build-manifest.json`; validar hit, mudança de HTML e miss com runner isolado.
- `tailwind_recursos_compilar()` ainda consulta `--help` para validar a versão em cache hit, mas não executa build CSS quando o fingerprint e o output hash correspondem.

### 2026-10-01 — BATCH-211 (req-203): recursos globais sem idioma

- `language_agnostic: true` faz a coleta dinâmica usar uma única origem na raiz de `gestor/resources/`; registros não devem receber nem manter uma coluna `language` sintetizada.
- Para validar `resources:sync` sem sobrescrever `gestor/db/data/*.json` já alterados no checkout, execute `main()` em teste com `RESOURCES_DIR`, `DB_DATA_DIR`, `GESTOR_DIR` e `MODULES_DIR` temporários, usando os contratos e seeds reais como entrada.
- As duas definições repetidas de `usuarios_perfis_modulos` e `usuarios_perfis_modulos_operacoes` em `tables_config.json` eram chaves JSON duplicadas; consolide-as antes de confiar no valor interpretado pelo PHP.

### 2026-10-01 — BATCH-212 (req-204): layout por perfil 1:N e compilação multi-layout

- **Página bundle descarta o CSS do layout no runtime.** Sob um layout trocado por perfil, o que vale é o CSS da própria página: ele precisa ter sido compilado com esse layout. O carimbo `/*! c2f-layouts:a,b */` no início do `css_precompiled` diz quais; sem o layout no carimbo, o roteador mantém o sidecar do layout.
- **A sincronização regrava `css_precompiled` a partir do disco** mesmo em página `user_modified`, e zera a procedência: a página mapeada é recompilada pelo `css:rebuild` a cada pipeline. O carimbo entra na decisão de "já coerente".
- **Captura do `css_compiled` no editor é delta contra o baseline.** Com mais de um layout, o baseline tem de ser a interseção das cascatas; folha com `media="not all"` não pinta e continua legível por `sheet.cssRules`.
- **Árvore compartilhada com outro lote em andamento não serve de origem para pipeline.** A req-202 no meio do caminho foi levada ao Lab por um `project:update-all` e sobrescreveu usuário, módulos e permissões. Worktree limpa: `environment.json` copiado, junção de `node_modules` e `vendor`, shebangs em LF.
- **Numeração de requisição colide em árvore compartilhada**: arquivo `req-XXX.md` sem commit pode ser sobrescrito por outro agente. Reservar com commit e push, como manda o fluxo.
- **Alerta de tamanho**: este arquivo passou de 200 linhas; a poda é obrigatória aos 300.

### 2026-09-30 — BATCH-202/203 (req-198): manifesto por camada, choques, snapshot e rollback

- **Tenant isolado para testar atualização do sistema:** `project-test` → `c2f-teste.local` (usuário Hestia `c2ftest`) no Lab. Artefato montado da worktree sem composer, enviado para `conn2flow-github/` do tenant e aplicado com `atualizacoes-sistema.php --local-artifact --domain=c2f-teste.local` como `c2ftest`.
- **Lab = WSL com Caddy (Docker) em 80/443; o nginx do Hestia fica no 8443.** Domínio fora do Caddyfile recebe 200 vazio do Caddy: não é o site. Vhost novo precisa de `listen 8443 ssl` sem IP (o template põe `192.168.178.206:`) para atender em `127.0.0.1:8443`.
- **`/bin/sh` é o dash:** `set -o pipefail` sai com código 2 e o `2>/dev/null` esconde. Pipe que precisa de `pipefail` roda pelo bash.
- **Verificação HTTP pós-atualização não pode forçar `127.0.0.1:443`:** Hestia de produção escuta no IP público. Ordem: DNS normal, `127.0.0.1`; sem conexão = aviso; `--health-url`/`--health-ip` para casos especiais. Esperar ~3 s: o OPcache do FPM serve o código antigo até `revalidate_freq`.
- **Bootstrap do atualizador:** o pai roda o script INSTALADO; o filho, o novo. Biblioteca nova tem de vir do staging primeiro, senão o filho chama função que a instalada não tem. A linha de `atualizacoes_execucoes` é do filho.
- **Dump antes do banco restaura a própria linha `running`:** depois de `--com-banco`, marcar a execução.
- **`resources:sync` na worktree** reescreve tokens de asset de 31 módulos (CRLF) e para no Tailwind (sem CLI): para variável nova, inserir no `VariaveisData.json` sem reformatar (JSON indent 4, `\/`, CRLF) e desfazer o resto.
- **Login do admin do tenant é o e-mail** (`usuario` = e-mail no instalador headless).
- **BATCH-204:** token de API do tenant = PAT (`c2f_pat_`) gerado pelo `perfil-usuario` (`ajaxOpcao=api-token-gerar`, o valor vem na RAIZ da resposta, não em `data`), gravado em `devProjects.project-test.api.access_token`. O Windows não resolve `c2f-teste.local`: chamar a API com `curl --resolve c2f-teste.local:8443:127.0.0.1`.
- **BATCH-207:** a sincronização pula tabela sem mudança de checksum (`SKIP_NO_CHECKSUM_CHANGE`): a linha de base do manifesto de recursos só nasce para as tabelas processadas — use `--force-all` na primeira. No banco do tenant a coluna de idioma de `variaveis` é `language` (o Data.json também traz `linguagem_codigo`). `status()` é método final do `TestCase` do PHPUnit: não use esse nome em helper de teste.
- **BATCH-209:** no PHP-FPM do HestiaCP o `open_basedir` não inclui `/dev`: `proc_open` com `['file','/dev/null']` falha; use pipes. Processo em segundo plano disparado pela web: `nohup setsid sh -c '…' &`, com o PHP CLI achado em `PHP_BINDIR` (`PHP_BINARY` é o FPM). A API passa pelo Gestor que está sendo atualizado: quem acompanha precisa tolerar 5xx por alguns segundos. O atualizador instalado é trocado pelo bootstrap: cópia à mão de uma biblioteca some na próxima atualização se o artefato for anterior à correção.
- **BATCH-205:** `assets:minify` na worktree acusa dezenas de derivados "stale" só por CRLF (o manifesto tem sha1 do conteúdo em LF). Para um JS só: terser `--compress --mangle` sobre o conteúdo em LF e atualizar só a entrada dele no `minify-manifest.json`. `c2f` já tem `project:update-system` com alias `update:system`: a req-201 não pode usar esse nome.
- **Nada que o filho do bootstrap chama no começo pode carregar a biblioteca instalada** (fixa a versão antiga pelo `function_exists`). E o bootstrap troca o atualizador instalado antes do filho rodar: atualizador de teste quebrado trava o tenant até recolocar o script à mão.

### 2026-09-30 — BATCH-201 (req-197): trava de deploy, backup, checagem de migrações

- **Trava:** `gestor/bibliotecas/deploy-lock.php` é pura (sem Gestor), porque roda no atualizador independente, na API e no CLI. Criação atômica com `fopen 'x'`; trava vencida é tirada do caminho com `rename` atômico e assumida.
- **Pipeline:** a trava é por DESTINO (host e caminho do SSH, ou a pasta local), não por id: ids diferentes apontam para o mesmo Lab. Fica em `GIT/.c2f-deploy-locks/`, porque cada worktree tem o seu `dev-environment/data/` (ignorado pelo git) e as travas precisam se enxergar. A pasta é a mesma no Windows e no WSL (testado).
- **Bootstrap da atualização do sistema:** o pai reexecuta o script novo como filho. O pai pega a trava e passa `--lock-token`; o filho adota a trava. Na primeira atualização, a biblioteca vem do staging (o filho roda sobre a instalação antiga).
- **Respostas por `exit`:** `api_response_*` encerra com `exit`, que não roda `finally`; a liberação da trava na API fica num `register_shutdown_function`.
- **Testes do atualizador:** `ATUALIZACOES_SISTEMA_SEM_EXECUCAO` carrega as funções sem executar, e `$_GESTOR['ROOT_PATH']` aponta para uma instalação falsa em temp.
- **`db/` não é mais apagado pelo CLI:** tem dois donos e guarda o manifesto da req-194.
- **Worktree do core:** não tem `vendor/` nem `dev-environment/data/`. Rodar o PHPUnit com `php ../conn2flow/vendor/bin/phpunit --configuration phpunit.xml` e copiar o `environment.json` (fica ignorado). As falhas `CoreHelpersTest` e `Stripe*Test` na worktree são do ambiente e aparecem também sem as mudanças.

### 2026-09-29 — BATCH-198 (req-194): migrações obsoletas no ambiente em execução

- **`db/migrations` do servidor tem dois donos.** A pasta do projeto (ex.: `conn2flow-site/gestor`)
  só tem as migrações do projeto; as do core chegam pelo sync do core ou pela atualização do sistema.
  Espelhar (`rsync --delete`) ou apagar a pasta antes de extrair apagaria as do outro dono. A limpeza é
  por dono, com `db/.c2f-migrations-<core|projeto>.json` (`atualizacoes-migracoes.php`).
- **`project:update-all` roda o banco (etapa 2) antes dos arquivos (etapa 4).** Lixo no destino trava a
  etapa 2; por isso a limpeza roda entre a 1 e a 2 (`synchronize-project.sh --migrations-only`).
- **`--backup` da atualização do sistema chama `backupTotal()`, que não existe** (fatal). Registrado
  no BL-028, não corrigido no hotfix.

### 2026-09-29 — BATCH-197 (REQ-193): opção de parcelas em PaymentIntent

- `stripe_criar_payment_intent()` aceita `installments` opt-in e só acrescenta `payment_method_options.card.installments.enabled=true` quando estritamente habilitado; sem a opção o payload anterior permanece igual.
- Validação do escopo de biblioteca: PHPUnit 3 testes/5 asserções, `php -l` e `git diff --check` aprovados. País, limite máximo, juros e exibição no Payment Element pertencem ao consumidor e não foram homologados pela API/sandbox neste batch.
- `ai:archive-sdd --keep=10 --repair-links` moveu REQ-183/BATCH-187 mas saiu com código 1 por seis links órfãos preexistentes; não reparar/reescrever esses arquivos sem escopo explícito.

### 2026-09-28 — BATCH-194 (req-190): formulários do `interface` em Tailwind

- Botões de cabeçalho/rodapé são montados em PHP (`interface_botoes_html`): as utilities só chegam ao
  pré-compilado porque estão no `<template data-c2f-botoes>` dos componentes `interface-formulario-*-tailwind`.
  Classe nova no PHP sem entrar lá sai sem estilo; o `InterfaceBotoesTailwindTest` confere.
- O `interface-tailwind.js` não tinha o clique do `.excluir` (era do `interface.js`, que não carrega no modo
  Tailwind) nem o token CSRF da req-189 na confirmação.
- `resources:sync` também regenera os pré-compilados de layout e páginas que agregam o componente alterado.

### 2026-09-28 — BATCH-192 (req-188): rota com ponto, sitemap no deploy, exclusão de órfãs

- **URL de página com ponto no último segmento dava 404.** O roteador usava `pathinfo()` no
  caminho inteiro (`2.10/` → extensão `10`) e mandava para o servidor de estáticos. Agora caminho
  terminado em `/` nunca é arquivo.
- **Sitemap: só o deploy pela API (`project:deploy` → `/_api/project/update`) conhece o domínio.**
  O `project:update-all` por SSH roda o atualizador em CLI, sem `config.php`; não gere sitemap ali.
- **Excluir registro de tabela do core a partir do projeto:** bloco `"paginas": {"nome": "paginas",
  "deletar": [...]}` no `resources/project_tables_config.json`. Os arquivos globais são lidos antes
  dos módulos, então as regras de `paginas` continuam as do `admin-paginas`, e as listas `deletar`
  são somadas. Chave natural de `paginas`: `language`, `modulo`, `id`.
- **Todo `ai:archive-sdd` do core gera páginas órfãs no site** (a URL muda para `…/archive/…`).
  Depois do `docs:build`, comparar o banco do Lab com `pages.json` e pôr as antigas no `deletar`.

### 2026-09-26 — BATCH-190/191 (req-186/187): módulo `documentation` e deploy no Lab

- **A tabela `templates` não tem coluna `modulo`**, apesar de a `natural_key_columns` do contrato
  citar `modulo`. Templates de módulo vão para o `TemplatesData.json` como globais de mesmo id.
  Migração que move recursos para um módulo só mexe em `paginas`.
- **Migração nova só roda na 2ª rodada do `project:update-all`.** A etapa 2 (banco) roda antes da
  etapa 4 (arquivos), com a migração ainda antiga no remoto. Migração corrigida: `project:sync-files`
  antes de rodar de novo.
- **`project:sync-core` sobrescreve o `db/data/` do projeto remoto com o do core**, inclusive o
  `schema-metadata.json`. Na etapa 2, tabela só do projeto (`menus`) cai no modo `pk` com `id`
  repetido entre idiomas e dá `Duplicate entry` (BL-023). Contorno: `project:sync-files` e depois
  `project:sync-db`.
- **`escapeshellarg()` não serve para comando que roda num `sh` remoto**: no Windows gera aspas
  duplas e apaga as internas. Use citação POSIX (`SshRemoteTransport::posixQuote`).
- **Consultar o banco do Lab:** não há `c2f db:query`. Script PHP somente leitura no scratchpad
  (inclui `config.php` + `bibliotecas/banco.php`), `scp` para `/tmp`, `sudo -u admin php ...` e `rm`.
- **`page:inspect` não devolve o HTML**, só status, erros de console, estilos e screenshot. Para ler
  texto da página autenticada: `curl -sk -b temp/agent-cookies.txt <url>`.

### 2026-09-26 — BATCH-184 (req-179): 41 bibliotecas e conceitos reescritos do código

- **Doc legada não é fonte**: dezenas de afirmações falsas (funções inexistentes, checksum "SHA-256"
  que é MD5, cron "por expressão"). Confirme rodando (`php -r`, `--help`) e cite o comportamento real.
- **Classe usada só no template do menu não ganha CSS**: `menus` não passa pelo `css:rebuild`. O
  `docs:build` injeta o template como mockup no bloco do widget (`DocsBuilder::withMenuMockup()`).
  Medir com `c2f page:inspect --computed=...` antes de concluir que "está estilizado".
- **Parsedown funde `>` separados por linha em branco**: `blockQuoteContinue()` sobrescrito no renderer.
- **Extrator de docblock**: tipo com espaço (`array<string, int>`) exige leitura com colchetes balanceados.
- **`EMAIL_SECURE=false` não desliga TLS** (`isset` na chave que sempre existe): só SMTPS/465 funciona.
- **Deploy não regenera `sitemap.xml`** nem agenda o cron no servidor: as docs publicadas pelo pipeline
  ficam fora do sitemap até uma edição no painel.
- Achados de segurança da leitura: A1–A11 na `req-181` (nada corrigido; aguarda o Humano).
- Trabalho paralelo: uma requisição por onda/tema (req-180, 182–185), com regras de escopo de arquivos
  e **um único dono do pipeline**; a árvore de trabalho é compartilhada, então `git add` só dos seus caminhos.

### 2026-09-25 — BATCH-181/182/183 (req-176/177/178): docs como código

- **Docs = Markdown em `ai-workspace/<lang>/docs/{guides,concepts,reference,whats-new}`**, mesmo caminho nos dois
  idiomas; `c2f docs:audit` (ranking), `docs:extract` (bloco de funções) e `docs:build --project=<id>` (recursos).
- **Página do Gestor interpreta `@[[x#y]]@` em qualquer HTML**: doc que MOSTRA marcador precisa de `&#64;[[`.
  O build faz isso e só depois troca o token interno por `@[[pagina#url-raiz]]@`.
- **Tailwind lê o texto bruto do HTML**: `[&_p]:x` sai `[&amp;_p]` no atributo e nunca é gerado. E `hidden`
  perde para `inline-flex` na cascata v4 — esconda removendo o elemento.
- **URL com extensão cai em `<gestor>/assets/`** (arquivo-estatico) — `/docs/llms.txt` sem rota nova.
- **Busca do `publisher-index` é `JSON_SEARCH` em todos os `fields_values`** → texto completo de graça.
- **Heredoc do Git Bash colapsou `\\` DE NOVO**, até com `<<'EOF'` + Python. Qualquer barra invertida: Edit/Write.
- Parsedown 1.7.4 embutido em `cli/lib/parsedown/` com patch `?array $Block` (PHP 8.4+); ver README lá.

### 2026-09-25 — Planejamento FEAT-014 (docs Core + `/docs/` no site), sem código

- **Publisher, publisher-pages, menus e publisher-index já viram recurso por arquivo** via
  `sync_resources` em `resources/project_tables_config.json` — comprovado no `transformamp`. Nenhum
  desses módulos tem `hooks.api`; `/_api/{modulo}/{acao}` exige criar o hook.
- **`ai-workspace/{en,pt-br}/scripts/` é código vivo** (83 referências de `cli/`, `.vscode` etc.,
  inclusive `scripts/lib/project-transport.sh`). Limpeza do `ai-workspace` nunca inclui `scripts/`.
- `publisher-index` não filtra por valor de campo; o Core não tem lib de Markdown nem dependência de runtime no `composer.json`.
- Plano completo: `conn2flow-ai-workspace/sdd/backlog/FEAT-014-*.md` (+ `ARCH-007` para atualização de kits).

### 2026-09-25 — BATCH-180 (req-175): renovação silenciosa de CSRF

- **O token CSRF não tem TTL: é variável de sessão.** O cookie de sessão vence `SESSION_LIFETIME`
  (10800 s) após a CRIAÇÃO e nunca é renovado; a linha em `sessoes` é varrida por inatividade
  (1/51 requisições). Para reproduzir: `SESSION_LIFETIME=120` ou apagar o cookie no DevTools.
- **`$.ajax` se cobre pelo envelope do XHR.** Ouvintes de CAPTURA no próprio XHR disparam antes do
  `onload` do jQuery (ordem at-target do DOM): seguram o 403, renovam e reabrem o mesmo objeto.
  O reenvio vai numa macrotask (`setTimeout 0`) — microtask reabriria o XHR entre `readystatechange`
  e `load` da resposta original.
- **403 sem marca não é CSRF.** `X-Gestor-Csrf-Error` sai nos dois ramos (JSON e HTML) porque
  `fetch`/XHR sem `Accept: application/json` recebem a página HTML; ACL nunca entra em retry.
- **Não existe `global/global.json`.** Cache-bust de `gestor/assets/*` = `assets:minify` + owner em
  `asset-versions.json`, regenerável sozinho por `php gestor/controladores/agents/arquitetura/atualizacao-versoes-assets.php`.
- `seguranca.php` não entra no bootstrap do PHPUnit: teste que o usa faz `require_once` explícito.

### 2026-09-22 — BATCH-178 (req-173): o que o roteador esquece e o que o widget desenha sem querer

- **`paginas_301` responde 301 de verdade** (`gestor_roteador_erro()` chama `http_response_code()`);
  o que se perdia era a query string, porque a chamada do ramo 301 não passava `'querystring' => true`.
  Medir antes de acusar: o BL-016 nasceu afirmando 302 e estava errado.
- **Função que termina em `header()` + `exit` é inverificável.** A montagem da URL saiu para
  `gestor_redirecionar_montar_url()` só por isso; a regra de `?` x `&` passou a ter teste.
- **Os blocos-modelo dos templates de forms nunca foram usados.** `forms_widget_options_html()` e
  `forms_widget_wrap_password()` procuram `option-choice`/`password-toggle` DENTRO do bloco `item`
  (é o item que chega a `forms_widget_render_field()`), e nos templates do core eles estão FORA.
  O widget sempre caiu nos modelos embutidos no PHP; o bloco do fim do arquivo só vazava para a tela.
- **Limpeza de marcador tem de ser cirúrgica**: remover bloco desconhecido inteiro apagaria markup do
  autor do template. Só os três blocos conhecidos saem inteiros; dos demais sai o comentário.
- **A suíte no Windows falha em `CoreHelpersTest`** por `openssl.cnf` ausente (PHP 8.5 + OpenSSL 3.5),
  independentemente do lote. Rodar no Lab (Linux, PHP 8.5.10) dá 1202/1202; não perseguir esse erro.
- **`git diff --check` reclama de linha só com TAB**, e o estilo do `bibliotecas/gestor.php` usa TAB em
  linha em branco. Em código novo, linha em branco vazia.

### 2026-09-15 — BATCH-168 (req-163): XHR com CSRF e site restrito

- **`gestor_usuario_perfil()` lê o cookie `authprofile`, que NÃO é assinado** — nunca use para autorizar; `gestor_usuario()` vem do JWT validado.
- **`$.ajax` passa pelo envelope de `XMLHttpRequest.prototype`**: registre os cabeçalhos em `setRequestHeader` ou o token do prefilter duplica.
- **PHP 8.5 do host (WinGet) sem `pdo_sqlite`/`OPENSSL_CONF`**: 16 erros falsos. Rode com `PHP_INI_SCAN_DIR=<ini temporário>` + `OPENSSL_CONF=<php>\extras\ssl\openssl.cnf`, sem editar o `php.ini`.
- **`executionOrder="depends,defects"` alterna verde/2 falhas** em `ForcarAtualizacaoTest` (`static $meta` de `schemaMetadata()` congelado por `ProjectIdentityPassthroughTest`). Pré-existente; compare com `--order-by=default` e `--exclude-filter` antes de culpar o lote.
- **`./c2f assets:minify` via Git Bash falha no `exec` do `npx`**; `php cli/c2f.php assets:minify` funciona.

### 2026-09-18 — BATCH-174 (req-169): controle de acessos e suíte no ambiente `lab`

- **Proteção antiabuso precisa distinguir erro humano de robô.** `formulario_acesso_falha()` era
  chamada igual em validação de campo e em reCAPTCHA reprovado, então digitação errada queimava a
  mesma cota de envios válidos. O parâmetro `origem` separa os dois tetos, com padrão `abuso` para
  não alterar chamadores fora do núcleo.
- **Mensagem de bloqueio sem prazo gera suporte.** `autenticacao_acesso_verificar()` passou a
  devolver `tempo_bloqueio` e as telas substituem `#bloqueio_liberacao#` pela data e hora. Projetos
  com tela de login própria precisam adotar o marcador para exibir o prazo.
- **A suíte do núcleo deve rodar no `lab`, não no PHP do Windows.** O host Windows falha em
  `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` por ausência de `openssl.cnf` e exige
  habilitar `pdo_sqlite`/`sqlite3` no php.ini. No `lab` (WSL Ubuntu com PHP 8.5 do HestiaCP), o
  repositório é visível em `/mnt/c/...` e, após `apt-get install php8.5-sqlite3`, a suíte fecha
  limpa: 1181 testes, 7828 asserções, 0 erros. Use o `lab` como ambiente de referência para
  validação PHP; divergência ali é do código, divergência só no Windows é do ambiente.

### 2026-09-18 — BATCH-175 (req-170) e correções do smoke test

- **Marcador que depende de variável precisa ser trocado no fim do pipeline.**
  `gestor_pagina_variaveis()` roda depois do controlador do módulo, então trocar um marcador enquanto
  o módulo monta a página não alcança texto que só será injetado adiante. `pagina-marcadores-finais`,
  aplicado em `gestor_pagina_ultimas_operacoes()`, resolve para as duas origens (componente e
  variável). Vale para qualquer módulo com o mesmo problema.
- **Em `perfil-usuario`, `pagina_celula($nome,false,true)` REMOVE a célula.** O ramo
  `if($acesso['permitido'])` é o do acesso liberado, não o do bloqueio. Código novo que dependa do
  estado de bloqueio vai no `else` — ou fora do `if`.
- **Texto de tela pode estar em três lugares.** Componente do núcleo, página do módulo e variável. O
  que um projeto renderiza depende de ele ter tela própria. Alterar só o componente não alcança quem
  usa a variável; conferir no banco do projeto (`variaveis`, com `user_modified`) antes de dar como
  entregue.
- **Cache estático + reordenação do PHPUnit produz falha fantasma.** Teste que escreve o próprio
  contrato precisa forçar a releitura; senão o primeiro a chamar fixa o estado para a classe toda e o
  resultado passa a depender da rodada anterior. Validar sempre com DUAS execuções seguidas, sem
  apagar `.phpunit.result.cache`.

### Histórico anterior

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.

BATCH-155 a BATCH-167 (2026-09-02 e 2026-09-03: SSH e bootstrap do CLI, checksum e fim de linha, paridade visual do Tailwind, fila de scripts, worker que se matava, sessão e cgroup) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md).
