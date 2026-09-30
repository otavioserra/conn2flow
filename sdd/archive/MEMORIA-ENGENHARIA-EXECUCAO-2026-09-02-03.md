# Memória de Engenharia — Execução (arquivo: 2026-09-02 e 2026-09-03)

Podado da memória principal em 2026-09-30 (limite de 300 linhas), sem alteração de conteúdo. BATCH-155 a BATCH-167.

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
