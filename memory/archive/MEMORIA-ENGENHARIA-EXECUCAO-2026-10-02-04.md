# Memória de Engenharia — Execução (arquivo de 2026-10-02 a 2026-10-04)

> Seções movidas na íntegra de `sdd/04-memory-engineering-execution.md` na poda de 2026-10-07 (REQ-260 / BATCH-269).

### 2026-10-02 — BATCH-222 (req-214): menus do painel, consentimento e execução no cliente

- **Biblioteca nova só carrega se estiver em `$_GESTOR['bibliotecas-dados']` (`config.php`)**. `gestor_incluir_biblioteca()` com nome desconhecido só gera aviso no log; teste com inclusão simulada não pega.
- **Código novo no `global.js` roda em documento simulado nos testes** (`vm.runInNewContext` com `window` e `document` de mentira): conferir a existência da API antes de usar.
- **Rodar a suíte em ordem aleatória** (`--order-by=random`) antes de fechar: acha dependência de estado entre testes (gerenciador de ganchos carregado por outro teste).
- **Menu do painel**: item atual por `aria-current="page"` (servidor) ou pelo prefixo mais longo do endereço (script); `window.gestorMenuPosicionarAtual()`.
- **Execução dos módulos no cliente**: o core dá `modulo_distribuido_contexto()`, `modulo_distribuido_url_publica()` e o gancho `modulo-distribuido` / `db.escrita`; o empacotador e as rotinas locais são do projeto.

### 2026-10-02 — BATCH-221 (req-213): módulos distribuídos, melhorias

- **Função chamada por gancho carrega as próprias bibliotecas.** O `HookManager` só registra o erro do callback em modo de desenvolvimento; fora dele, função indefinida dentro do gancho some e o fluxo segue como se o gancho não existisse (login distribuído com segundo fator ia para o painel).
- **`project:verify <id>`** compara por hash o código do destino com a origem e roda ao fim do `project:update-all` (`--no-verify` pula; `--strict` devolve 1). É o que substitui a comparação manual depois de deploys cruzados.
- **Cadastro de instalações distribuídas é do projeto**: o core lê o provedor em `$_CONFIG['modulo-distribuido']['installations-provider']` (`array`, `false` = desativada, `null` = desconhecida, cai no `.env`).
- **cURL com handle estático** reaproveita conexão e TLS entre chamadas da mesma requisição: 31,9 ms para 4,6 ms por consulta remota no Lab.
- **Teste que usa classe do CLI** precisa dos `require_once` de `cli/src/Contracts` e do comando: o autoload do PHPUnit não cobre `cli/`.

### 2026-10-02 — BATCH-220 (req-212): modal do editor visual

- **Folha de framework do painel não entra no iframe do editor visual Tailwind, nem em camada.** Abaixo das camadas do Tailwind ela perde para o reset (`base`); acima, volta a reger o conteúdo. A interface do motor dentro do iframe tem de trazer os próprios estilos (inline ou no `<style>` do motor).
- **Modal de edição**: sem `#html-editor-modal` no documento, o motor cria o portátil (`ensureFallbackModal`). No iframe do painel (sem `raiz`) o botão de imagem fala com a janela pai; na página pública usa o seletor ao vivo.
- **Dirigir o editor no Playwright**: `iframe#iframe-preview` → `window.htmlEditor.selectElement(el)` e `.editSelected()`; clicar no elemento seleciona o filho e a barra pode não aparecer. `document.querySelector('img')` acha imagens da própria interface do editor: usar `section img`.
- **Editor ao vivo**: `window.postMessage({type:'c2f-toolbar:edit-start', page_id}, origin)`, com o `page_id` do `src` do `#c2f-site-toolbar`.

### 2026-10-02 — BATCH-219 (req-211): módulos distribuídos, revisão e integração

- **Allowlist de tabelas por regex não segura SQL**: `FROM (t)`, `JOIN (t)`, `STRAIGHT_JOIN t`, `(TABLE t)`, `{OJ t}` e vírgula depois de `JOIN ... ON` nomeiam tabela sem o formato `FROM nome`. `modulo_distribuido_sql_tabelas()` percorre tokens e recusa o que não reconhece. Ao mexer nela, rodar os casos de `ModuloDistribuidoReq092Test`.
- **`gestor_start()` chama o protocolo distribuído em toda requisição** (`prefixo_normalizar`, `cookie_contexto`, `central_rota`, `proxy_rota`). Sem `MODULO_DISTRIBUIDO_*` no `.env`, todas retornam cedo.
- **Lab**: tenant `distribuido-conn2flow.local` (usuário Hestia `distribuido`, banco `distribuido_db`), projeto `conn2flow-site-distribuido-lab` no `environment.json`. Segredos em `/root/conn2flow-req092-lab.json` no Lab. Roteiros em `conn2flow-site/sdd/validation/modulos-distribuidos/`.
- **Worktree nova com `core.autocrlf`** mostra milhares de arquivos como modificados sem diferença real (`git diff --ignore-cr-at-eol` vazio). Não é trabalho pendente.

### 2026-10-02 — BATCH-218 (req-210): manutenção no deploy, retirada de módulo e Lab compartilhado

- **500 no deploy por SSH tinha duas causas**: sistema pela metade e `gestor.php` ilegível entre o `sudo rsync` (arquivo nasce `root`) e o `chown` do fim da etapa. `rsync --chown` fecha a segunda; a manutenção (`temp/maintenance.json`) cobre a primeira. Diagnóstico: `nginx/domains/<site>.error.log` no destino.
- **`escapeshellarg` no Windows troca `"` e `%` por espaço.** Conteúdo com aspas vai para o destino em base64.
- **Renomear migração mantendo a versão** deixa a cópia antiga no destino e o Phinx recusa ("Duplicate migration"): o `rsync` não apaga. Migração renomeada recebe versão nova.
- **Módulo que sai do core** deixa `.min.js` no destino, e o `arquivo-estatico` serve o minificado quando ele existe. Remover as sobras no destino.
- **Dois agentes publicando no mesmo Lab se desfazem**: o `rsync -u` não repõe arquivo que o outro entregou com data mais nova, e a retirada por dono marca `status='D'` no que a árvore de quem publica não entrega (componentes novos do outro lote foram retirados assim). Antes de publicar: `git worktree list` no core e no projeto, e as notas `*COORDENACAO-LAB*` do projeto. Com outro lote em validação, não rodar o pipeline; no máximo entregar arquivo que o outro não alterou.
- **Sondar o deploy**: laço de `curl` a cada segundo na página e no cabeçalho `X-C2F-Maintenance`, contando os códigos.

### 2026-10-02 — BATCH-217 (req-209): prévia de widgets e toque

- **Prévia do editor de páginas**: o widget chega por AJAX (`html-editor-widget-render`). CSS autoral vai junto na resposta; controlador público precisa iniciar o que chega depois da carga (`MutationObserver`).
- **Fomantic**: o ícone é `chartline`; `chart line` não desenha nada. Conferir o nome em `assets/vendor/fomantic-ui/*/semantic.min.css`. O dashboard desenha o SVG pelo mesmo nome (`dashboard_gerar_svg_modulo`).
- **Excluir e status pelo painel** agem por GET com `_csrf_token` na URL.
- **JS extra de módulo**: `<modulo>.<tipo>.js` é servido em `<modulo>/<tipo>.js` (`gestor_pagina_javascript_incluir(['tipo' => …])`).
- **`docs:build`**: `layout` aceita mapa por idioma; `tailwind_sources` na configuração entra em toda página de docs, mas multiplica o CSS do cabeçalho por página: num projeto o `PaginasData.json` foi de 67 MB a 167 MB e a opção foi desligada lá. Trocar o layout das docs recompila todas as páginas (5 a 10 min no Lab).

### 2026-10-02 — BATCH-216 (req-208): módulo `cookie-consent`

- **Módulo de widget novo sem tocar no `html-editor.php`**: `alvo` e `alvos_modelos` com o id do módulo e `widget_js_include`; o alvo desconhecido cai no caminho padrão do editor.
- **Tag do controlador público para a pré-visualização**: `gestor_pagina_javascript_incluir(['tipo' => 'widget', 'modulo_id' => …], false, true)` devolve a tag sem incluí-la na página.
- **Ícone do módulo**: `icone` é Fomantic, `icone_tailwind` é Lucide.
- **Teste com contagem fixa**: `Req203LanguageAgnosticResourcesTest` conta as linhas de `user_profiles_modules.json`; módulo novo pede ajuste.
- **`resources:sync` no core termina com erro de `dist/` e saída 0** quando não há `PUBLIC_PATH`: é aviso, os recursos foram compilados.

### req-215 / BATCH-223 — rotina local e cópia de execução contratada (2026-10-03)

- **Ação `rotina` do canal**: `modulo_distribuido_rotina($nome, $args)` no Central (null fora de contexto); o cliente só executa o declarado em `routines` do manifesto da cópia. Teste: `ModuloDistribuidoRotinaReq215Test`.
- **`modulo_distribuido_execucao_ativa($modulo)`** é a trava de contratação usada no roteador, widgets, ganchos, cron e gateways; cópia com `panel: false` não é interceptada pelo proxy.
- **Parâmetros do endereço seguem ao iframe** por `modulo_distribuido_consulta_canonica()` nas duas pontas (campo `query` do ticket).
- **`modulo_distribuido_http_post` devolve false para resposta não 2xx**: recusa do cliente chega ao Central como falha de transporte.

### req-216 / BATCH-224 — catálogo local e estado da conta (2026-10-03)

- **Catálogo local**: sem `MODULO_DISTRIBUIDO_MODULES`/`TABLES` no `.env`, valem os de `project/distributed-modules.json`. Sem `app-id` a lista é vazia (senão o Central proxia os próprios módulos e o iframe mostra só a moldura).
- **Estado da conta**: `$_CONFIG['modulo-distribuido']['account-provider']`; no cliente, cache em `distributed_exchanges` com id `hash('sha256','conta|'.$app)` (a coluna `id` tem 64 caracteres: prefixo estoura e a linha não grava). Teste: `ModuloDistribuidoContaReq216Test`.
- **Suspenso**: `banco_distribuido_iniciar([... 'somente-leitura' => true])` e `modulo_distribuido_rotina(..., ['leitura' => true])`; link externo dentro do HTML do painel com `:&#47;&#47;` (`modulo_distribuido_href_externo()`) para o reescritor do proxy não prefixar.

### req-217 / BATCH-225 — confirmação de origem e chave de sessão (2026-10-03)

- **Todo envio passa por `modulo_distribuido_enviar`**: com `confirmacao-origem` ligada (padrão do `config.php`), ele obtém a sessão (`modulo_distribuido_sessao_saida`, memória por conexão em `WeakMap` + linha `sessao-saida`) e assina com a chave dela. Configuração de canal precisa de `peer` (o `app_id` no Central; `central` no cliente).
- **Receptor**: `modulo_distribuido_receber()`; `abrir`/`confirmar` vão ao lado Central quando a instalação não tem `app-id` (roteamento em `api.php`).
- **Retomada**: 401 (`http_post` guarda o status em `$GLOBALS['_MODULO_DISTRIBUIDO_HTTP_STATUS']`) reabre a sessão e repete uma vez; o 401 vem antes de executar qualquer coisa.
- **Precisa de dois workers PHP**: `abrir` espera o `confirmar` do outro lado chegar ao próprio servidor. Servidor PHP embutido (um processo) trava.
- Teste: `ModuloDistribuidoOrigemReq217Test` (duas pontas em SQLite e um atacante).

### req-218 / BATCH-226 — imagens do layout no painel distribuído (2026-10-03)

- **O roteador passa `caminho` para minúsculas**: para montar um endereço com o nome original do arquivo, use `$_SERVER['REQUEST_URI']`. Redirecionamento das pastas estáticas (`MODULO_DISTRIBUIDO_PASTAS_ESTATICAS`) usa o endereço original sem o prefixo e cai no `caminho` se o destino não for do mesmo host.

### req-219 / BATCH-227 — painel em Tailwind: controles, editor HTML e admin-paginas (2026-10-04)

- Padrão (DEC-132): variante `<id>-tailwind` que guarda os ganchos do JS + ponte vanilla em `controles.js` (só entra sem `$.fn.dropdown`). `HtmlEditorTailwindReq219Test` compara variante e original (marcadores, ids, nomes, `data-tab`, placeholders, classes-gancho).
- Ponte: `tab` precisa chamar `onLoad` (o editor atualiza o CodeMirror nele) e ativar por grupo de irmãos (abas aninhadas); modal vai para o `body` (o do editor mora em pai `.hidden`); `ia.js` exige `.form()`.
- Página Tailwind com componentes montados em runtime: `tailwind_bundle` no JSON **e** `$_GESTOR['tailwind-page-bundle'] = true` no PHP da opção. Sem o flag, os 15 sidecars entram depois e `grid-cols-1` vence `md:grid-cols-2`.
- CSS de página com `.hidden{display:none !important}` vence a aba ativa da ponte; não usar em página Tailwind.
- `$.formSubmitNormal` e `interface.js` não existem na página Tailwind: enviar por `form.requestSubmit()` (passa pela validação do `interface-tailwind.js`). Seletores `.ui.form`/`.x.button` quebram na variante.
- Hooks de projeto injetam marcação Fomantic (ex.: "Workspace Social" do site): `controles.css` esconde `.ui.modal`/`.ui.dimmer` sem `.active`.
- E2E: o iframe de pré-visualização carrega o framework da página editada (Fomantic no Lab); filtrar `request.frame() === page.mainFrame()`. Fixture do Req196 avalia `interface_formulario_campos` sem o gestor: guardar chamadas novas com `function_exists`.
- Pipeline: ainda é preciso rodar `project:update-all` duas vezes quando muda JS (minificação depois da cópia).

### req-219 / BATCH-229 — fatia 6, integração da req-220 e revisão (2026-10-04)

- Pipeline: minificação é a etapa 1 de `project:update-all` e `manager:update-all`. A ordem antiga (depois da cópia) deixava o `.min.js` velho sob o hash novo do `asset-versions.json`; o navegador cacheava o velho. Uma rodada basta agora.
- `interface_status_selo($status)` substitui a mensagem de status escrita à mão; `interface-listar-tailwind` tem `id="_gestor-interface-listar"` para os JS de módulo.
- Comentário em recurso NÃO pode citar placeholder (`#x#`, `@[[x]]@`): `modelo_var_troca` pega a primeira ocorrência, que fica dentro do comentário (SEO quebrou assim). Teste de contrato cobre.
- Frentes paralelas: worktree própria por agente, trava do Lab com `mkdir ../.c2f-lab-lock` (skill `c2f-tailwind-module-migration`). Ao integrar branch de outro agente, o conflito típico é só `asset-versions.json` (pega um lado e o pipeline regenera).
- O editor reabre na última aba usada (`localStorage`): roteiro de navegador precisa voltar à aba antes de clicar em botão dela.
