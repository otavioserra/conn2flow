# Memória de Engenharia — Execução

> **Propósito**: contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: é proibido podar abaixo de 50 KB / 200 linhas; emitir alerta preventivo nesse patamar, podar obrigatoriamente ao atingir 75 KB / 300 linhas e mirar ~25 KB, preservando 20 a 25 tarefas e aprendizados recentes. O fim da sessão ou do batch não aciona poda.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors, transportes e mounts.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-09-02 — BATCH-161, homologação: mais três causas na mesma paridade

- **`sem-motor` na captura = `html-editor.js` ausente NAQUELE iframe.** `HtmlEditorCssCapture` só era
  injetado no editor visual; no preview a captura falhava sempre e, pela regra de preservação,
  mantinha o valor anterior — vazio em página nova. Quem criava pelo `adicionar/`, montava com
  modelos, conferia no preview e salvava gravava `css_compiled` VAZIO: 360 classes aplicadas, 0 byte,
  e o CRUD parecendo perfeito porque lá o Tailwind Browser compila em runtime. **Regra de leitura do
  aviso: o `motivo` diz exatamente o que faltou — leia-o antes de supor.**
- **Carregar o motor sem ligar a UI**: `window.__c2fHtmlEditorNoAutoInit = true` ANTES do script
  (BATCH-075, o mesmo truque da Editbar). Dá a API de captura sem instanciar o editor sobre o body.
- **Trava de salvamento que GERA em vez de recusar**: detecta HTML mudado depois da captura, troca
  para a visualização, espera a captura AVISAR (callback `aoConcluir`) e reenvia. Tempo fixo erraria
  nos dois sentidos. Interceptar precisa ser em capture phase com `stopImmediatePropagation` — o
  handler de submit do `formulario.js` é jQuery, fase de bubble.
- **O CSS AUTORAL do layout (coluna `css` de `layouts`) não chegava ao editor** — só o
  `css_precompiled` era lido. `body{background:#000}` pintava a publicada e não o editor: era o
  sintoma relatado desde o começo. Vai como folha própria FORA do baseline (não é derivado; dentro
  dele a captura descartaria regras) e na posição do runtime.
- **O layout pode ser TROCADO no select do CRUD**: o baseline entregue na abertura morre nesse
  instante. Rota própria devolve as duas camadas do layout novo, preservando o que a sessão acumulou.
- **`className` de SVG não é string** (`SVGAnimatedString`): script que varre classes pelo DOM gera
  falso positivo `[object SVGAnimatedString]`. Verifique antes de reportar classe "sem regra".
- **Comparar publicada x editor exige lembrar que a publicada é `página + layout`** e os editores só
  a página. Perdi duas rodadas até o operador insistir nisso. Confira a origem de cada classe antes
  de concluir de onde vem a falta.

### 2026-09-02 — BATCH-161 (req-160): o baseline contra o qual se filtra tem de ser o do runtime

- **Filtrar a captura contra um baseline que o runtime não recebe apaga CSS em silêncio.** O
  BATCH-156 somou o sidecar do template ao baseline (para o editor renderizar certo) e com isso ele
  entrou no conjunto contra o qual `HtmlEditorCssCapture` filtra: as regras do template saíram do
  `css_compiled` e o runtime, que só recebe `layout.css_precompiled + css_compiled`, ficou sem elas.
  **Corrigir um lado da cascata pode quebrar o outro — confira sempre quem CONSOME o artefato.**
- **Medir cobertura de classes acha isto rápido**: conte as classes aplicadas no DOM contra os
  seletores que as folhas entregam. Publicada 424/13 sem regra x preview 405/5 — a diferença nomeou
  o culpado sem ler uma linha de código.
- **`css_precompiled` NÃO pode ser gravado do POST** (`CssProcedenciaGravacaoTest`, CR-002):
  "derivado gravado do formulário devolveria ao operador a capacidade de descolar CSS de HTML".
  Tentei por ali e a suíte barrou — **quando um teste antigo reprova sua abordagem, leia a decisão
  que ele protege antes de pensar em contorná-lo.** A correção certa era anterior: separar o que se
  emite como baseline.
- **Duas folhas resolvem o que um campo novo não resolvia**: `data-c2f-tailwind-role="baseline"` (o
  que o runtime entrega, alvo do filtro) e `data-c2f-css-role="session-overlay"` (o sidecar da
  sessão, fora do filtro). `baselineStyles()` só reconhece `[data-c2f-tailwind-role="baseline"]` e
  `[data-tailwind-role]` — a marca do overlay é o que o mantém fora.
- **Defeito de TRANSIÇÃO não aparece medindo ESTADO.** O BATCH-158 mediu 15/15 e 18/18 com o
  conteúdo já estabilizado e passou por cima deste: ele nasce no ato de inserir o template. Teste o
  ciclo (inserir → salvar → publicar), não só o resultado.
- **O tenant do Lab é descartável, os repositórios são a semente.** `ssh lab` (192.168.1.108,
  HestiaCP), `sudo -i` para root, logs unificados em
  `/home/admin/web/conn2flow.local/conn2flow-gestor/logs/` (`php-error.log`). Página criada online
  vive só no banco da VM: o espelho do container pode ter conteúdo COMPLETAMENTE diferente — comparei
  3 seções locais contra 9 na VM e quase regenerei CSS sobre o conteúdo errado.
- **`git checkout` normaliza EOL**: script Python que reescreve arquivo no Windows deixa o git
  vendo alteração com diff vazio. Confirme com `git diff` antes de achar que mudou algo.

### 2026-09-02 — BATCH-160 (req-159): utility de animação que nunca existiu

- **No Tailwind v4, `animate-<nome>` só nasce se `--animate-<nome>` estiver no `@theme`.** Sem o
  token, a classe é descartada em SILÊNCIO na compilação: o template parece certo no código e não
  anima na tela. `animate-fade-in-up` estava assim em 4 templates (14 usos nos dois idiomas),
  medido `animation-name: none` / `0s`. Vale para qualquer utility que dependa de token.
- **O contraste entre dois templates deu a causa mais rápido que ler o compilador**: `sessao-com-abas`
  animava porque traz o `@keyframes` num `<style>` embutido no HTML; os quatro quebrados não traziam
  nada. Quando um caso funciona e outro não, compare os dois antes de investigar o pipeline.
- **Classe sem regra nem sempre é defeito**: `tab-btn`, `sidebar-item`, `c-header-nav-btn` são hooks
  de `querySelector` ou têm `<style>` local. Antes de reportar, cheque se a classe é usada como
  ESTILO ou como seletor de JS.
- **Thumbnail ausente em layouts/componentes é esperado**: só as sessões declaram `thumbnail` no
  manifesto (são as que aparecem no seletor de inserção). O teste de integridade só exige o arquivo
  quando o campo existe — não confunda ausência de campo com artefato faltando.
- **`system-input.css` é a fonte única do tema**: `tailwind_recursos_browser_contract()` DERIVA o
  `browser-contract.css` dele, removendo `@import "tailwindcss"` e `@source`. Definir ali alcança
  build offline, editor visual e Editbar de uma vez — preferível a `<style>` por template.
- **`tailwind_recursos_input_central()` SUBSTITUI o contrato do core pelo do projeto quando
  `contents/tailwindcss/input.css` existe** — não estende. Token novo no core não chega a projeto com
  input próprio; ao mexer no tema central, verifique quais projetos têm o seu.
- **Valide a guarda por mutação**: removi a regra do sidecar e confirmei que os testes falham
  nomeando template e classe. Teste que nunca falhou não prova nada.

### 2026-09-02 — BATCH-158 (req-158, cadastrada antes como req-156): paridade visual e fim do CDN no cliente

- **Uma folha sem camada vence QUALQUER coisa em `@layer`, independentemente da ordem.** O BATCH-156
  tirou o `semantic.min.css` do preview e deixou a mesma tag no editor visual: título 72->24px,
  peso 900->700, texto do CTA branco->rgb(65,131,196). Quando os dois ambientes divergem, compare o
  `<head>` que cada um monta antes de procurar no conteúdo.
- **Camada NÃO resolve `html{font-size}`.** O Fomantic declara `html{font-size:14px}` e o Tailwind v4
  mede spacing, tipografia e raio em `rem`: TODA medida encolhe por exatos 14/16 = 0,875
  (72->63, 128->112, 48->42, 16->14). Nenhuma `@layer` corrige, porque não existe regra do Tailwind
  disputando `html{font-size}` — vence por ausência de concorrente. **Fator uniforme de 0,875 num
  A/B = alguém mexeu na raiz do `rem`.** Exige restaurar a raiz fora de camada.
- **Declare a ordem das camadas (`@layer a, b, c;`) em vez de confiar na posição das folhas.** Uma
  camada é ordenada pela PRIMEIRA menção do nome, e o Tailwind Browser registra as dele de forma
  assíncrona, ao compilar.
- **O BATCH-146 varreu as tags do PHP; as que o CLIENTE monta escaparam.** Iframes `srcdoc`, Editbar
  e previews de widget tinham 24 tags em 4 CDNs com versões próprias. Ao migrar, confira os arquivos
  que só aquele consumidor usa (`closetag.js`/`closebrackets.js` não estavam no registro):
  `assets_externos_url()` cai no CDN em SILÊNCIO quando o local não existe.
- **Banco do container Docker pode ser espelho desatualizado do que a VM serve.** O `conn2flow_site`
  local tinha `css_compiled` do Tailwind v3 (11 de 19 páginas); a rota real
  (`conn2flow.local` -> 192.168.1.108, `deploy_mode: ssh`) já entregava v4. **Meça a rota real antes
  de concluir sobre o ambiente 'produção'** — eu quase reportei um defeito que não existia lá.
  Marcadores: v3 tem `--tw-border-spacing-x`/`--tw-ordinal` e zero `@layer`; v4 tem `--tw-rotate-x`/
  `--tw-border-style`. Gradiente v4 interpola em `oklab`, v3 em sRGB.
- **`gestor_css_procedencia_assinatura()` não considerava o compilador; o build offline sempre
  considerou** (`tailwind_recursos_fingerprint()`). Assinatura passou a `v2:` com `compilador`: toda
  `v1:` deixa de casar e entra na fila do `css:rebuild`, sem migração de dados. Ao mudar a
  assinatura, atualize TODOS os leitores (`css-regenerar`, `css-auditoria`, `publisher-pages`) —
  gravador e leitor divergentes deixam todo recurso permanentemente stale.
- **Outro agente sobrescreveu meu `req-156.md`** com escopo alheio (BATCH-159) no meio da sessão.
  Recuperado do git (`git show <commit>:<caminho>`) e recadastrado como `req-158`, sem tocar no
  arquivo nem no batch do outro agente. **Confira `git log -- <arquivo>` antes de editar artefato SDD
  que você criou horas antes.**
- **`docker cp` com caminho `/tmp/...` vira `C:/Users/.../Temp/...`** pelo path conversion do MSYS:
  use `MSYS_NO_PATHCONV=1`. E `sed` sobre string com barra invertida a corrompe — use Write/Edit.
- **Tailwind Browser não compila sob `file://`**: um harness que mede utilities precisa servir por
  HTTP, senão utilities ausentes do baseline medem 0px e parecem regressão.

### 2026-09-02 — BATCH-159 (req-156): release remoto consome derivados locais

- O `manager:release` local é a fronteira de geração dos derivados de recursos Tailwind. O workflow
  `release-gestor.yml` deve apenas testar o estado commitado, remover fontes de autoria e empacotar;
  recompilar no runner Linux invalida o cache gerado no Windows e pode criar um ZIP posterior aos
  testes.
- Remover junto `Generate resources and per-resource Tailwind CSS` e `Commit Resources Updates`:
  manter só a primeira elimina a recompilação, mas ainda deixaria um commit remoto sem propósito.
  `release-instalador.yml` já segue esse padrão enxuto.
- Validação registrada: YAML lint nos dois workflows; PHPUnit 1073/1073; Vitest 382/382. O Vitest
  pode imprimir `ECONNREFUSED 127.0.0.1:3000` em teardown do happy-dom e ainda encerrar com sucesso;
  conferir o resumo final e o exit code antes de classificar como falha.

### 2026-09-02 — BATCH-157 (REQ-035 / req-155): checksum derivado E dependente do fim de linha

- **O checksum do recurso é DERIVADO, não autoria.** `atualizacao-dados-recursos.php` grava o md5 do
  HTML de volta no manifesto (`ORIGIN_UPDATE_MODULE`). Teste que exige campo VAZIO num campo que o
  pipeline preenche briga com o pipeline; o invariante certo é a COINCIDÊNCIA com o arquivo.
- **`buildChecksum()` calcula o md5 dos BYTES do disco, e o disco varia por plataforma.** Índice em
  LF + `core.autocrlf` no Windows = árvore em CRLF (`git ls-files --eol` → `i/lf w/crlf`). São 233
  quebras de linha neste arquivo: hash completamente distinto do que o runner Linux calcula.
  **Sintoma:** CI acusando "checksum divergente" com dois hashes plausíveis e nada errado no
  recurso. **Antes de regravar o hash, rode `git ls-files --eol`** — regravar no Windows só repete
  o laço na release seguinte.
- **Simular o runner é barato e conclusivo**: converter o arquivo para LF em disco, rodar a suíte,
  `git checkout` depois. Provou a correção sem esperar o CI.
- **`git checkout` de um arquivo com `autocrlf=input` devolve LF** — e é essa forma canônica que o
  `resources:sync` deve compilar. O `PaginasData.json` estava com `
` DENTRO do HTML servido em
  runtime pela mesma causa.
- **`schema-metadata.json` muda todo sync** (`generated_at`): esse arquivo sozinho no `git status`
  não indica mudança real.
- **`resources:sync` do núcleo reporta `Tarefas de Cron: 0`**: nenhum módulo do core declara a chave
  `cron`. Tarefas de projeto são compiladas por `project:sync-resources`.
- **Outro agente commitou por cima do meu lote** (`3d705ee7` arrastou `AdminCronReq032Test.php` junto
  do release 2.10.3). Antes de preparar commit, confira `git log -1 -- <arquivo>`: a árvore pode ter
  ficado limpa porque alguém já levou a alteração, não porque nada foi feito.


### 2026-09-02 — BATCH-156 (req-154): templates Tailwind no preview

- Os 72 modelos por idioma, 36 Tailwind, sidecars, `TemplatesData.json` e banco estavam íntegros.
  A causa era a cascata do iframe: Tailwind pré-compilado em `@layer` seguido por Fomantic sem camada;
  CSS não estratificado vence utilities estratificadas. A/B: `py-20` 80→70px, `gap-12` 48→42px e
  CTA branco→transparente. Em preview Tailwind, preserve scripts legados, mas não `semantic.min.css`.
- Ao inserir seção, nunca substitua o baseline da página pelo sidecar do fragmento: concatene ambos.
  Substituição integral de página pode trocar o baseline. `resources:sync --force` recompilou 237/237.
- Guarda atual: 72 HTMLs/thumbnails + 36 sidecars por idioma e presença no CSS de cada utility usada
  das famílias padding, margin, gap, spacing, background, rounded e shadow.

### 2026-09-02 — BATCH-155 (req-153 / REQ-034): transporte SSH e bootstrap CLI

- Tenants saíram do `conn2flow-app` para VM/HestiaCP. Verifique `environment.json`/`docker ps`; SSH
  remoto exige destino declarativo, `BatchMode`, argumentos citados para o shell remoto,
  `sudo rsync` e devolução de posse por `chown`.
- `config.php` não pode impor `SERVER_NAME=localhost` no CLI: o sufixo do cookie fica incorreto e a
  sessão é ignorada com 302 silencioso. Checksum de `<modulo>.json` pertence ao compilador;
  `ORIGIN_UPDATE_MODULE` o repõe, então a guarda aceita vazio ou md5 coincidente com o arquivo.

### Histórico anterior

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.
