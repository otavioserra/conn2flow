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

### Histórico anterior

BATCH-168, BATCH-174, BATCH-175, BATCH-178 e BATCH-180 (2026-09-15 a 2026-09-25: CSRF em XHR e renovação silenciosa, controle de acessos, suíte no ambiente `lab`, roteador e widgets) foram podados em 2026-10-02 por limite de tamanho. O registro integral está nos relatórios desses lotes em `sdd/implementation/` (ou `archive/`) e na versão `97a6bc9e` deste arquivo.

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.

BATCH-155 a BATCH-167 (2026-09-02 e 2026-09-03: SSH e bootstrap do CLI, checksum e fim de linha, paridade visual do Tailwind, fila de scripts, worker que se matava, sessão e cgroup) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md).

BATCH-181 a BATCH-214 (2026-09-25 a 2026-10-01: docs como código, deploy e migrações, atualização segura com manifesto, snapshot e rollback, formulários do `interface`, sementes declarativas, recursos sem idioma, layout por perfil 1:N) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md) na poda de 2026-10-06.

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

### req-240 / BATCH-249 e REQ-106 / BATCH-100 — harmonização do painel, atualizações e arquivos (2026-10-05 e 06)

**Ponto de retomada (2026-10-06):** tudo em `main` e no Lab; lotes `implemented-pending-homologation`. Não há trabalho pela metade. Próximo passo é do Engenheiro Chefe: homologar pelo roteiro em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md` e abrir a requisição nova com os achados dele. A REQ-241 (atualização automatizada) está redigida e não implementada. Sobras: worktrees `conn2flow-req240` e `conn2flow-site-req106` (têm junções para `node_modules`; não remover com `rm -rf`) e dois `.bak-*` soltos no site.

- **"Círculo preto" é o ícone Lucide `circle` de fallback**: `interface-listar-tailwind.js` (`opcao.lucide || 'circle'`, alimentado por `interface_botao_tailwind_icone()`), `admin-tailwind.js` (nome Fomantic fora do mapa `nomes`) e `data-lucide="circle"` literal. Ícone novo em módulo: tradução no mapa do `interface.php`; `IconesLucideReq240Test` acusa o que faltar.
- **`.c2fc-abas` não tem regra CSS.** A folha é `c2fc-abas-lista` (+ `c2fc-anexa`), `c2fc-aba` (ativa por `aria-selected`) e `c2fc-painel-aba`. `c2fc-alerta` é `flex`: caixa sem `.content` vira bloco pela regra `:has`.
- **Roteiro de validação nunca mira coisa real para provar uma recusa.** Uma sondagem de "excluir fora do escopo" apontava para `contents/favicon`; rodada como administrador apagou a pasta do Lab. Alvo destrutivo é sempre caminho inexistente. Recuperação: `project:sync-files` (`contents/` do projeto é versionado).
- **`admin-arquivos`**: escopo e cota vêm dos filtros `admin-arquivos` / `escopo` e `cota-bytes` (o site responde no `multiusuario.hooks.php`; pasta do usuário é `<id>/`, com `files/` dentro). O `layout-iframe-tailwindcss` é também o da barra de edição do site: nada de margem ou script nele; o que é do painel em iframe entra pelo roteador (`gestor.php`).
- **Rota de API não preenche `gestor_usuario()`** (devolve `_anonimo`): o dono sai de `api_token_validar_memoizado(api_bearer_token())`. Token de teste: gerar e revogar pela tela `perfil-usuario` (`api-token-gerar` / `api-token-revogar`); perfil restrito do Lab não tem a aba.
- **Lucide troca o `<i>` por `<svg>`**: referência jQuery guardada antes da carga aponta para elemento descartado. A variante `menu` de select do `interface` é Fomantic e não funciona em página Tailwind.
- **Dados do site**: operações e acessos por perfil vêm de `db/data/*Data.json` estáticos; `modulos_operacoes` tem chave única `(id, language)`. Módulo removido deixa o diretório no destino (o `rsync` não apaga) e diretório local vazio é recriado lá.
- **Ferramentas**: `ln -s` no Git Bash copia em vez de ligar (usar `New-Item -ItemType Junction`); PHPUnit em `../conn2flow-req234/vendor/bin/phpunit`, e o do site com `--configuration sdd/validation/req238-phpunit.xml`; manifestos de módulo divergem em escapar barras no JSON; `perl -0` com `\n\n` não casa em CRLF; `$(` dentro de `perl -e` no bash é expandido pelo shell.
- **Alerta de tamanho**: perto de 300 linhas; a poda é obrigatória aos 300.

### req-242 / BATCH-251 e REQ-107 / BATCH-101 — refinamentos da auditoria humana do painel (2026-10-06)

- **Worktree nova nasce em CRLF e os checksums versionados são de conteúdo LF.** Compilar assim troca versão e checksum de centenas de recursos. Antes do `resources:sync`: `git ls-files -z gestor | grep -z -E "\.(html|css|json|js|php)$" | xargs -0 sed -i 's/$//'` (fora os `*.part-*.json`). O `git status` passa a listar milhares de arquivos sem diferença; a lista real é `git diff --name-only`.
- **`node_modules` e `vendor` da árvore principal do core estão vazios (2026-10-06).** Dependências íntegras: `conn2flow-req232/node_modules` (Tailwind 4.3.3, o mesmo dos pré-compilados versionados; o `package-lock` diz 4.3.0), `conn2flow-req235/node_modules` (Playwright com navegador instalado) e `conn2flow-req234/vendor`. Junção: `New-Item -ItemType Junction`; desfazer com `cmd /c rmdir`.
- **Junção dentro de `gestor/` do projeto vira link simbólico no `rsync` do pipeline** (código 23, "could not make way for new symlink"). Pasta ignorada que o empacotador precisa (`gestor/assets/3d-catalog`) só fica ligada durante o `build.py`.
- **Manifesto de módulo é fonte do Tailwind** quando as utilities vivem nas variáveis (`perfil-usuario`). O compilador regravava o manifesto com `\/` e a classe com barra (`ring-sky-600/20`) sumia do CSS na mesma rodada; agora grava com `JSON_UNESCAPED_SLASHES`.
- **Classe-gancho do Fomantic que também é utility do Tailwind**: `inline fields` deixava a grade em `display: inline` (controles empilhados em `galleries` e `menus`); `pl-9` perde para o padding de `.c2fc-campo-entrada` (folha sem camada vence utility).
- **Select do painel**: dentro de ancestral com `overflow` o painel passa a `position: fixed` (`c2fc-flutuante`), reposicionado na rolagem. A ponte não tem `dropdown('change values')`: para trocar opções, preencher o `<select>` nativo e chamar `c2fControles.de(select).atualizar()`.
- **Tema Tailwind do projeto troca a fonte padrão** (`--font-sans: Inter` no site, sem a fonte carregada no painel): o layout administrativo fixa a própria família em `body:has(> #c2f-admin-shell)`.
- **Estado do repositório não bate com a última compilação**: o `resources:sync` completo atualiza versão e checksum de recursos que ninguém do lote tocou (HTML commitado sem compilar) e o `build.py` do site traz mudanças antigas para `gestor-distribuido/`. É atraso real, não ruído do ambiente: conferir um checksum armazenado contra `md5` de LF e de CRLF antes de concluir.
- **`build.py` recoloca `modulos-grupos-distribuido` em `gestor/project/distributed-modules.json`** (o registro em `gestor-distribuido/project/` ainda lista o módulo aposentado na REQ-106) e o `ArquivosApiReq106Test` falha: linha desfeita à mão, pendência registrada.
- **PHPUnit em ordem aleatória** falha em parte das sementes com `Cannot redeclare gestor_roteador_layout_perfil()` (`Req204LayoutMultiPerfilTest` contra outro teste que avalia a mesma função). Ordem padrão: 1.629 sem falhas.
- Roteiro de navegador: `sdd/validation/req242/req242-browser.cjs` (112 conferências, core e site) e `shot.cjs` (captura com passos). `C2F_PLAYWRIGHT` aponta o pacote.
