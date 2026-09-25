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

### 2026-09-03 — BATCH-167 (REQ-040): sessao nao e cgroup

- **`setsid` NAO tira o processo do cgroup.** Ele cria sessao e grupo de processos novos; o cgroup
  continua sendo o do pai. `systemctl restart php8.5-fpm` encerra a unidade INTEIRA, e o filho vai
  junto. Para escapar de verdade: `systemd-run --scope` (cgroup proprio) ou `ssh` (cgroup de
  sessao do `sshd`). Foi o erro do BATCH-166 — desacoplou do que nao importava.
- **Sondar a capacidade, nao supor.** `systemd-run --scope` depende de autorizacao do systemd e o
  pool roda sem privilegio: numa instalacao tipica ele e NEGADO. E a sonda precisa usar o MESMO
  prefixo do disparo real — sondar com flags diferentes aprova uma configuracao que falha adiante,
  em background, sem ninguem ver.
- **Estrategia de fallback que nao protege precisa DIZER isso.** Manter `setsid` como ultimo
  recurso e certo (a maioria dos disparos nao reinicia servico), anuncia-lo como protecao e pior
  que nao ter protecao: esconde a exposicao.
- **O nucleo NAO popula `$_GESTOR['config']`.** Essa chave e uma convencao que o config-loader do
  Host Manager cria para si. Codigo do nucleo que le so dali fica inerte, sem erro visivel — foi o
  caso de `cron_php_binary` e `cron_tarefas_desacopladas` do BATCH-166. Ler `$_ENV` -> `getenv()`
  -> `$_GESTOR['config']`.
- **Separar montagem de disponibilidade torna o comando testavel.** Uma funcao que monta a linha e
  outra que verifica o binario: sem isso, nada de systemd e verificavel num host de
  desenvolvimento Windows.
- **Teste que conta ocorrencias acha assimetria de envelope.** A contagem de `'isolado' =>` no
  arquivo revelou tres retornos de erro precoces sem as chaves que o sucesso declara.

### 2026-09-03 — BATCH-166 (REQ-039): a rotina que matava o worker que a chamou

- **O disparo manual e o tick agendado NAO sao o mesmo caminho.** A esteira de provisionamento do
  HestiaCP chama `systemctl restart php8.5-fpm`. Pelo cron ela roda no CLI e sempre funcionou; pelo
  botao "Disparar agora" ela roda DENTRO do pool FPM e a reinicializacao mata o proprio worker da
  requisicao — `502 Bad Gateway` e tenant parcial deixado no painel. Ao portar uma rotina de CLI
  para a tela, pergunte o que ela faz com o processo que a hospeda.
- **`nohup` nao basta; `setsid` e a peca central.** Sem uma sessao nova, o filho continua no grupo de
  processos do pool e o restart o mata junto com o pai. O `&` faz o `sh -c` retornar de imediato,
  entao `proc_close()` nao bloqueia a resposta ao navegador.
- **`PHP_BINARY` sob PHP-FPM aponta para o binario do POOL (`php-fpm`)**, nao para o CLI. Usa-lo
  rodaria o cron sob o SAPI errado. A ordem correta e `config.cron_php_binary` ->
  `PHP_BINDIR . '/php'` executavel -> `PHP_BINARY` so se o processo ja for CLI -> `php` no PATH.
- **Nao coloque id de tarefa de modulo de projeto dentro do nucleo.** A REQ nomeava
  `host-manager-provisionamento`, que vive no `conn2flow-site`. A decisao virou DECLARACAO da propria
  tarefa (`parametros.execucao = "desacoplada"`), e um teste guarda a ausencia desse nome no nucleo.
- **`parametros` do banco pode estar congelado.** A sincronizacao so o reescreve quando
  `user_modified` esta vazio (D-036): basta o operador ter pausado a tarefa UMA vez para a
  declaracao nova nunca chegar, reexpondo o 502 em silencio. Como a declaracao e de SEGURANCA, o
  manifesto do modulo entrou como segunda fonte, com leitura pontual pelo campo `modulo` e o nome
  validado por regex antes de virar caminho.
- **Confirmado de novo: `<modulo>.php` termina em `<modulo>_start()` e abre a interface.** O primeiro
  teste deste lote deu `Call to undefined function hook_do_action()` a partir de
  `interface_finalizar()`. A saida e a mesma do BATCH-028 no host-manager: extrair para um include
  sem efeito colateral (`includes/admin-cron-dispatch.php`).
- **Nao grave status no caminho desacoplado.** Quem registra duracao e resultado e o processo CLI ao
  terminar; um placeholder escrito no disparo sobrescreveria o resultado real.
- **Indisponibilidade de CLI degrada, nao recusa.** Windows, `proc_open` bloqueado ou `cron.php`
  ausente voltam ao caminho sincrono anterior, com o motivo em log — recusar o disparo seria
  regressao onde ele funcionava.

### 2026-09-03 — BATCH-165 (REQ-038): script enfileirado que nunca rodava

- **`<!-- pagina#js -->` esta no `<head>` de TODOS os layouts do gestor** (linha 30 de 102 no
  `layout-administrativo-tailwind`). Todo modulo do core sobrevive a isso por acidente de estilo:
  usa `$(document).ready` ou expoe um objeto global chamado por `onclick` inline. `admin-cron.js`
  era o unico com logica de DOM no corpo de uma IIFE — `document.getElementById(...)` na primeira
  instrucao devolvia `null` SEMPRE, a IIFE retornava cedo e a tela ficava estatica.
- **"O script nunca foi injetado" e "o script rodou cedo demais" produzem a MESMA tela.** O intake
  trazia o primeiro diagnostico; a chamada de `gestor_pagina_javascript_incluir()` estava la desde o
  commit que criou o modulo. Antes de adicionar o que ja existe, confirme com `git log -S`.
- **O contraste achou a causa mais rapido que ler o pipeline**: `perfil-usuario` usa o MESMO layout
  Tailwind e funciona — a unica diferenca era o `$(document).ready`. Quando um caso funciona e outro
  nao, compare os dois antes de investigar a infraestrutura.
- **Expressao de funcao NOMEADA evita reindentar o arquivo inteiro.**
  `(function iniciarPainelCron(){ if (readyState==='loading') { addEventListener('DOMContentLoaded',
  iniciarPainelCron, {once:true}); return; } ... })()` referencia a si mesma: 8 linhas de diff em vez
  de 503 reindentadas. O `{once:true}` tambem mantem o teste isolado — sem ele, os ouvintes de cada
  caso se acumulam no mesmo `document` do happy-dom e um clique dispara N fetches.
- **`.min.js` e a variante PREFERIDA em runtime.** Corrigir so o arquivo de autoria nao muda nada no
  navegador: `assets:minify` faz parte da correcao, nao do fechamento. Confira com
  `grep -o "DOMContentLoaded[^)]*)" <modulo>.min.js` que o minificador preservou a auto-referencia.
- **Homologacao local de `/admin-cron/` nao e possivel hoje**: o mirror `conn2flow-gestor`
  (projeto `project-test`) NAO esta montado em `dev-environment/data/sites/localhost/public_html/`,
  e nenhum banco local (`conn2flow`, `conn2flow_new`, `conn2flow_site`) tem a pagina `admin-cron`
  instalada. Validacao de tela desse modulo exige a VM.

### 2026-09-02 — BATCH-161, homologação: mais três causas na mesma paridade

- `sem-motor` na captura = `html-editor.js` ausente NAQUELE iframe; leia o `motivo` do aviso antes
  de supor. Motor sem UI: `window.__c2fHtmlEditorNoAutoInit = true` antes do script.
- Trava de salvamento que GERA em vez de recusar: espera o callback `aoConcluir` da captura (tempo
  fixo erra nos dois sentidos); interceptar em capture phase com `stopImmediatePropagation`.
- CSS AUTORAL do layout (`layouts.css`) vai como folha própria FORA do baseline; troca de layout no
  select do CRUD invalida o baseline da abertura.
- `className` de SVG é `SVGAnimatedString`; publicada = página + layout, editores só a página.

### 2026-09-02 — BATCH-161 (req-160): o baseline contra o qual se filtra tem de ser o do runtime

- Filtrar a captura contra baseline que o runtime não recebe apaga CSS em silêncio: confira sempre
  quem CONSOME o artefato. Contar classes aplicadas x seletores entregues nomeia o culpado.
- `css_precompiled` NÃO se grava do POST (CR-002): quando teste antigo reprova a abordagem, leia a
  decisão que ele protege. Solução: folhas `baseline` (filtradas) e `session-overlay` (fora do filtro).
- Defeito de TRANSIÇÃO não aparece medindo ESTADO: teste o ciclo inserir → salvar → publicar.
- `ssh lab` (192.168.1.108, HestiaCP), logs em `/home/admin/web/conn2flow.local/conn2flow-gestor/logs/`;
  página criada online vive só no banco da VM, o espelho local pode divergir.

### 2026-09-02 — BATCH-160 (req-159): utility de animação que nunca existiu

- Tailwind v4: `animate-<nome>` só nasce com `--animate-<nome>` no `@theme`; sem token é descartada
  em SILÊNCIO. Compare o caso que funciona com o que falha antes de investigar o pipeline.
- `system-input.css` é a fonte única do tema (deriva o `browser-contract.css`); o `input.css` de
  projeto SUBSTITUI o do core, não estende. Valide guarda por mutação.

### 2026-09-02 — BATCH-158 (req-158): paridade visual e fim do CDN no cliente

- Folha sem camada vence qualquer `@layer`; `html{font-size:14px}` do Fomantic encolhe tudo por
  0,875 (fator uniforme = raiz do `rem` alterada). Declare a ordem com `@layer a, b, c;`.
- Tags de CDN montadas pelo CLIENTE escapam da varredura do PHP; `assets_externos_url()` cai no CDN
  em silêncio quando o local não existe.
- Meça a rota real (VM) antes de concluir sobre produção: o banco do container pode ser espelho
  velho. Ao mudar assinatura de procedência, atualize TODOS os leitores.
- Confira `git log -- <arquivo>` antes de editar artefato SDD recente (outro agente sobrescreveu).

### 2026-09-02 — BATCH-159 (req-156): release remoto consome derivados locais

- `manager:release` local gera os derivados; o workflow só testa e empacota. `ECONNREFUSED` no
  teardown do happy-dom não é falha: confira resumo e exit code.

### 2026-09-02 — BATCH-157 (REQ-035 / req-155): checksum derivado E dependente do fim de linha

- Checksum de recurso é DERIVADO (`ORIGIN_UPDATE_MODULE`); o invariante é coincidir com o arquivo.
- md5 dos bytes varia com `autocrlf` (`i/lf w/crlf`): rode `git ls-files --eol` antes de regravar
  hash. `schema-metadata.json` muda todo sync (`generated_at`) sem mudança real.

### 2026-09-02 — BATCH-156 (req-154): templates Tailwind no preview

- Pré-compilado em `@layer` seguido de Fomantic sem camada perde; ao inserir seção, concatene o
  baseline da página com o sidecar do fragmento.

### 2026-09-02 — BATCH-155 (req-153 / REQ-034): transporte SSH e bootstrap CLI

- SSH remoto exige destino declarativo, `BatchMode`, `sudo rsync` e `chown`; `config.php` não pode
  impor `SERVER_NAME=localhost` no CLI (cookie ignorado com 302 silencioso).

### 2026-09-02 — BATCH-164 (req-162): variáveis em atributos no editor visual

- Marcador em `src`/`href` não entra cru no DOM (o `#` vira fragmento). Restaure só se o valor ainda
  for o resolvido pelo backend; `cloneNode(true)` dispara mídia — serialize via `<template>`.

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
