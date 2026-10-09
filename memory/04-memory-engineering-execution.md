# Engineering Memory - Execution

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

### REQ-263 / BATCH-272 — site lento depois de publicar: faltava índice no caminho de `paginas` (2026-10-08)

- **Sintoma**: tudo em ~70 ms e, depois de uma publicação, toda página PHP em 0,4 a 0,9 s, nos dois ambientes do mesmo servidor de banco; arquivo estático normal.
- **Causa**: `WHERE caminho=… AND language=…` em `gestor.php` só tinha o índice de `language`. O InnoDB examinava as ~1.000 páginas do idioma e, como a consulta pede `html` e os CSS, lia o conteúdo de todas (68 MB por visita). Rápido só enquanto isso cabia no `innodb_buffer_pool_size` (128 MB no ambiente de teste; a tabela tem 220 MB).
- **Como achar**: comparar `Innodb_buffer_pool_reads` antes e depois de uma requisição (número fixo e alto por visita denuncia varredura); ligar o registro de consultas lentas em tabela por alguns segundos (`log_output=TABLE`, `long_query_time=0.02`) e devolver a configuração.
- **Hipóteses erradas que custaram tempo**: ociosidade, WSL e opcache. O opcache se confere por dentro do pool com `cgi-fcgi` e um script fora da pasta do site.
- **Regra**: consulta que roda em toda requisição e pede coluna de conteúdo precisa de índice que leve a uma linha. `caminho` é TEXT: índice com prefixo (`'limit' => ['caminho' => 191]` no Phinx).
- **Teste que recorta código-fonte por `"\n}\n"`** quebra quando o arquivo está com CRLF (checkout com `autocrlf`): normalizar o fim de linha antes de recortar.

### Histórico anterior

BATCH-168, BATCH-174, BATCH-175, BATCH-178 e BATCH-180 (2026-09-15 a 2026-09-25: CSRF em XHR e renovação silenciosa, controle de acessos, suíte no ambiente `lab`, roteador e widgets) foram podados em 2026-10-02 por limite de tamanho. O registro integral está nos relatórios desses lotes em `sdd/implementation/` (ou `archive/`) e na versão `97a6bc9e` deste arquivo.

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.

BATCH-155 a BATCH-167 (2026-09-02 e 2026-09-03: SSH e bootstrap do CLI, checksum e fim de linha, paridade visual do Tailwind, fila de scripts, worker que se matava, sessão e cgroup) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md).

BATCH-181 a BATCH-214 (2026-09-25 a 2026-10-01: docs como código, deploy e migrações, atualização segura com manifesto, snapshot e rollback, formulários do `interface`, sementes declarativas, recursos sem idioma, layout por perfil 1:N) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md) na poda de 2026-10-06.

BATCH-216 a BATCH-229 (2026-10-02 a 2026-10-04: módulo `cookie-consent`, prévia de widgets e toque, manutenção no deploy e Lab compartilhado, módulos distribuídos, modal do editor visual, menus do painel, rotina e catálogo locais, chave de sessão, e a migração do painel para Tailwind com controles, editor HTML e `admin-paginas`) foram movidos, na íntegra, para [`archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-02-04.md`](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-02-04.md) na poda de 2026-10-07. As regras da migração para Tailwind vivem na skill `c2f-tailwind-module-migration`.

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

### req-243 / BATCH-252 e REQ-108 / BATCH-102 — rodada 2 da auditoria humana do painel (2026-10-06)

- **Select do painel fecha no `mousedown`**: o `mouseup` e o `click` do mesmo gesto caem no que está atrás, e botão antigo do painel age em `mouseup`. `_engolirClique()` segura os três na captura. Teste de unidade só com `click` não pega: o roteiro de navegador pegou.
- **Controle acompanha script**: `select.value = x` e troca de opções redesenham o gatilho (acessor por instância + `MutationObserver`); select com `data-c2f-select` inserido depois da carga é montado sozinho, fora de `<template>` e de controle pronto. Clonar linha de DOM já montado continua errado: clone de `<template>`.
- **`interface_historico()` zerava `javascript-vars['interface']`**: campo montado pelo módulo antes do histórico (seletor de imagem) ficava sem configuração em registro com histórico. Agora mescla.
- **Classe-gancho do Fomantic que é utility do Tailwind, mais uma**: `outline` (`edit outline icon`) desenha contorno no SVG do Lucide. O painel tira a classe ao converter e a folha zera o contorno.
- **Folha sem camada vence utility também no `display`**: o `h1` da listagem tem `display: flex` da folha; para esconder por utility é preciso `!` (`[&+section>h1]:hidden!`).
- **Item flexível informa `display: flex`** mesmo com `inline-flex` na folha: medição de roteiro confere `flex-direction`, não o valor exato.
- **`@[[variavel]]@` em componente global só resolve se existir no catálogo** (`resources/<idioma>/variables.json`, com `modulo`): variável ausente aparece crua na tela, sem erro. `RefinamentosReq243Test` confere as do componente de configuração.
- **Formulário "incomum"** (`interface-opcao = adicionar-incomum`) não tem variante própria: em página Tailwind usa a do formulário de inclusão.
- **Lab com dois agentes publicando**: (1) `touch` em `git ls-files gestor` antes de `project:update-all`, senão o `rsync -u` deixa no destino o arquivo mais novo do outro lote (399 diferentes numa rodada); (2) `auth:cookie` do outro agente derruba a sessão: usar `--out` próprio e `C2F_COOKIES`; (3) `sync-core` + `sync-files` + `sync-db` não repõem os recursos do core no banco: só o pipeline completo; (4) a trava `.c2f-lab-lock` só vale se o outro agente a conferir. Com cache do Tailwind quente o pipeline leva cerca de um minuto; frio, uns quinze.
- **Worktree**: normalizar também `cli/` e `tests/` para LF, senão `CssRegeneracaoTest` falha por fim de linha. `environment.json` copiado para a worktree com o `path` do projeto apontando para a worktree do site evita mexer na configuração da árvore principal.
- **`build.py` do site** precisa da junção `gestor/assets/3d-catalog` só durante a execução; sem ela acusa 22 diferenças falsas.
- **Conteúdo que vai para dentro de `<textarea>` ou `value` vai escapado**: o `publisher-pages` entregava cru o HTML da publicação e os valores dos campos; um `</textarea>` no conteúdo fechava o campo e o resto do editor ia parar fora do componente (aba SEO sem painel, "cada hora aparece uma coisa"). Sintoma para reconhecer: painel de aba pendurado direto no `body`. Roteiro de tela com abas troca de aba e recarrega; conferir só a carga inicial não pega.
- **Requisição resumida perde o sintoma**: "vazamento abaixo do editor" não levava a "depende da aba clicada e do F5". Sem reprodução, pedir o relato original antes de dar o item por feito.
- Roteiro de navegador dos dois repositórios: `sdd/validation/req243/req243-browser.cjs` (140 conferências).

### req-245 / BATCH-254 — atualização automática do sistema (2026-10-06)

- **Rotina de módulo**: chave `cron` na raiz de `<modulo>.json` (`id`, `nome`, `descricao`, `frequencia`, `hora`, `funcao`, `ativo`) e callback em `<modulo>.cron.php`, que nunca inclui o controlador. O painel pode incluir o arquivo de cron para reaproveitar funções. Ligar e desligar pelo módulo: `cron_tarefas.ativo` com `user_modified = 1`, senão a sincronização dos manifestos devolve o valor do arquivo.
- **Configuração por instalação fora do pacote**: `autenticacoes/<domínio>/` (`$_GESTOR['AUTH_PATH_SERVER']`), ao lado do `.env`.
- **Atualizar em segundo plano fora da API**: `atualizacoes_execucao_argv` + `_novo_id` + `_php_cli` + `_disparar`, e `_estado` para ler o resultado (`success`, `rolled_back`, `locked`, `error-*`). Domínio na engine de rotinas: `$_CRON['SERVER_NAME']`.
- **Checagem manual não pode valer como a do período**: marcas separadas (`ultima_checagem` informa, `ultima_automatica` vence). O defeito só apareceu disparando a rotina no Lab logo depois de usar a tela.
- **Abas nativas**: `data-c2f-abas` com `data-c2f-aba` / `data-c2f-painel`; o controle esconde com o atributo `hidden` e emite `mudou`. Abas aninhadas no mesmo elemento se misturam: manter cada conjunto no próprio invólucro.
- **`/tmp` do Git Bash não é o `/tmp` do Python do Windows**: arquivo de trabalho vai no scratchpad, com caminho `C:/…`.
- Roteiro: `sdd/validation/req245/req245-browser.cjs` (19 conferências); a rotina pela engine se exercita pelo AJAX `disparar` do `admin-cron`.

### Widget de apresentação no Dashboard — precedência entre folhas Tailwind (2026-10-06)
- **Duas folhas de utilities compiladas em separado não se somam, disputam**: ficam na mesma camada e vence a que vem depois. Folha parcial (template, 4 KB) depois da completa faz `grid-cols-1` vencer `md:grid-cols-3`. A folha completa tem de ser a última; no iframe do Dashboard o compilador do navegador entra depois das folhas do widget.
- **Defeito de widget com vários estados se procura em todos**: o primeiro slide saía certo e os seguintes não. Roteiro navega pela seta e compara o estilo computado elemento a elemento com a página pública na mesma largura (`sdd/validation/req244/widget-apresentacao-precedencia.cjs`).
- **"Funciona como página" pode ser só falta de publicação**: a página do Lab estava sem a branch que introduzia o defeito. Antes de comparar, conferir quais branches estão publicadas.
- **Selects em bloco clonado**: a cópia traz a casca do controle sem eventos; `observarSelects()` refaz a cópia que não tem a propriedade `c2fcViva`.

### Configurações por widget no Dashboard (2026-10-06)
- **Opção nova de widget** entra em `normalizeOptions()` (lista fechada de valores) e em `applyOptions()`; o pop-up é `#dashboard-widget-config-modal` no componente `dashboard-cards-tailwind`, com `data-widget-option="<nome>"` em cada controle.
- **Select do painel usa `c2fc-campo-selecao`**, não `c2fc-campo-entrada`: a guarda `IconesLucideReq240Test` reprova.
- **Roteiro que mexe em preferência do usuário guarda e devolve o estado inicial**: o Engenheiro Chefe usa o mesmo usuário no Lab ao mesmo tempo. Alternar chave por clique sem ler o estado antes inverte o que ele deixou.
- **Foto logo depois de clicar numa chave animada** pega a transição no meio: esperar 400 ms antes da evidência.

### REQ-247 / BATCH-256 — permissões e layout por perfil na área de widgets (2026-10-06)
- **Operação de módulo**: declarar em `gestor/resources/<idioma>/module_operations.json` e ligar ao perfil em `gestor/resources/user_profiles_modules_operations.json` pelo `id` da operação. `gestor_acesso('<operacao>', '<modulo>')` cai no acesso ao módulo quando a operação não está cadastrada: guarda nova só vale depois do `resources:sync`.
- **Bloco por permissão sai no servidor**: marcador `<!-- nome < -->` no componente e `modelo_tag_del` no PHP. Esconder por JS deixaria o HTML dos controles no navegador.
- **`Req203LanguageAgnosticResourcesTest` conta os vínculos globais**: vínculo novo em `user_profiles_modules_operations.json` pede ajuste da contagem.
- **Função extraída por expressão regular em teste antigo** (`DashboardWidgetStylesReq238Test`): função nova chamada dentro dela precisa de simulação no teste.
- **`auth:cookie --user=<id>`** gera sessão de outro usuário para roteiro com dois papéis; a sessão anterior do mesmo usuário cai.
- **Janela estreitada depois da carga deixa o menu lateral aberto por cima**: no roteiro em 390 px, clicar com `dispatchEvent('click')` ou carregar já na largura.

### Integração da REQ-247 depois da Fase B (2026-10-06)
- **Reverter uma mesclagem não a desfaz para o Git**: os commits continuam ancestrais e a próxima mesclagem da mesma linha não traz o conteúdo de volta. Para tirar algo que entrou por engano e voltar depois: antes de mesclar de novo, reverter o revert. Medido: 36 conflitos sem isso, 9 (só gerados) com isso.
- **Versão do Tailwind muda centenas de gerados**: conferir `node_modules/tailwindcss/package.json` contra o `package-lock.json` antes de `resources:sync`. A worktree `conn2flow-req243` usava 4.3.3 emprestado; a árvore principal usa 4.3.0.
- **`.md` em CRLF altera dados de IA** (`ModosIaData.json`, `PromptsIaData.json`) no `resources:sync`: descartar essas duas alterações ou normalizar também `.md`.
- **Pipeline do projeto com o acervo de docs** passa de 10 minutos: rodar em segundo plano.

### REQ-248 / BATCH-257 — grade e lousa na área de widgets (2026-10-06)
- **Pedido de "trocar por algo novo" pode ser "acrescentar"**: a lousa foi escrita substituindo a grade e o Engenheiro Chefe pediu os dois modos. Quando o que existe está aprovado, propor modo novo ao lado antes de substituir.
- **Lousa**: `arrange(lista, colunas, primeiro)` é a função pura do posicionamento; `layout()` só aplica nos cards (não recarrega iframe); `commit()` grava as posições e só é chamado em edição do usuário, nunca por mudança de largura.
- **Ouvintes em `document` acumulam entre testes** do Vitest que reiniciam o módulo: o ouvinte antigo roda antes e mexe em elemento que não é mais o da tela. Ler o elemento na hora (`getElementById`) em vez de guardar a referência.
- **Guarda antiga que proíbe uma propriedade na folha inteira** (`grid-auto-rows` na REQ-233): restringir a guarda ao modo que ela protege, não contornar pondo a propriedade no JS.
- **Tailwind oficial é 4.3.3** (`package.json` e `package-lock.json`). `npm install --package-lock-only` atualiza o lock sem tocar em `node_modules`.
- **Heredoc do Bash com Python que tem aspas triplas ou barra invertida falha**: script em arquivo, pela ferramenta de escrita.

### REQ-249 / BATCH-258 — navegação dos widgets para fora do quadro (2026-10-06)
- **`base target="_top"` não vence `target` escrito no link**: widget com `target="_self"` (menus) continua navegando dentro. O destino é forçado no clique, em captura, dentro do documento isolado.
- **Isolamento sem `allow-forms` barra todo envio de formulário**, sem erro no console.
- **Botão que chama a API com a sessão não funciona em documento isolado** (origem opaca, sem cookie): o módulo detecta `gestor.dashboardWidget` e leva a página de fora ao lugar onde a ação funciona.
- **Relato de "ainda não funciona" depois de uma correção**: sondar o HTML real do caso (atributos do link, tipo do elemento, script que trata o clique) antes de mexer; aqui eram três causas diferentes.
- **Roteiro que supõe um modo ou preferência do usuário do Lab** fixa o estado no começo e devolve no fim: o humano muda o próprio Dashboard entre uma execução e outra.
- **Teste que compara arquivo-fonte com `\n`** falha em cópia de trabalho CRLF: normalizar a worktree inteira antes de culpar a mudança.

### Instalação da linha 3.1 no Lab (2026-10-06)
- **`https://v3.1-conn2flow.local/`**, projeto `conn2flow-v31-local`, usuário HestiaCP `u11`. Criada pelo host-manager do site (REQ-112 / BATCH-106 no `conn2flow-site`). Publicar com o core e o site na branch `3.1`; roteiros com `C2F_BASE` apontando para ela e cookie de `auth:cookie --project=conn2flow-v31-local`.
- **Instalação nova não tem widgets no Dashboard**: `sdd/validation/req248/preparar-area-de-widgets.cjs` monta um de cada tipo que tiver registro.
- **Domínio `.local` novo no Lab** nasce escutando no IP da máquina; para abrir pelo Windows (`127.0.0.1`) a escuta do nginx do domínio precisa ficar sem IP, como nas contas antigas. A porta 443 do Windows é do proxy Caddy (`conn2flow-caddy`, distribuição Ubuntu do WSL, `/home/otavio/conn2flow-stack/Caddyfile`): domínio novo precisa de bloco ali e de certificado assinado pela autoridade local em `certs/` (`ca.crt`/`ca.key`), depois `docker restart conn2flow-caddy`. Todas as distribuições WSL dividem a rede: porta em uso sem processo visível no Lab é de outra distribuição.
- **`ssh_public_path` no projeto** quebra a etapa de assets do pipeline em origem Windows (rsync lê `C:/` como remoto): deixar sem, como no `conn2flow-site-local`.
- **Cache do Tailwind do pipeline é por conteúdo**: trocar de branch numa worktree CRLF e normalizar depois recompila tudo (12 minutos no site). Normalizar para LF antes da primeira publicação da branch; com cache, a publicação leva cerca de 1 minuto.

### REQ-250 / BATCH-259 — fase 1 da lousa, linha 3.1 (2026-10-07)
- **Objeto livre** é item do layout com `id: 'objeto'` e atributos em `object`; `drawObject()` desenha por texto e atributo (nada vira HTML). Tipo novo entra em `OBJECT_TYPES`, `normalizeObject`, `drawObject`, nos campos `data-object-for` do pop-up e em `dashboard_widgets_objeto_normalizar`.
- **Listas iguais no cliente e no servidor** (fontes): teste lê a lista do JS e compara com a do PHP.
- **Pop-up aberto a partir de outro pop-up** precisa de camada acima; os dois com `z-50` deixam o segundo atrás. Só o roteiro de navegador pegou.
- **Google Fonts `css2`**: pedir peso que a família não tem devolve erro para a folha inteira; Bebas Neue vai sem pesos.
- **happy-dom busca folha e iframe na rede**: `window.happyDOM.settings.disableCSSFileLoading` e `disableIframePageLoading` nos testes que criam `<link>` ou `iframe` com endereço.
- **Seletor com aspas aninhadas** (`[data-x='[y="z"]']`) não funciona no happy-dom: achar pelo atributo e filtrar em JS.

### REQ-251 / BATCH-260 — fase 2 da lousa, linha 3.1 (2026-10-07)

- **Lousa do sistema** mora em `dashboard_boards` (único por `id` + `language`) e o histórico em `dashboard_boards_versions` (20 por lousa). Excluir é status `D`; o identificador não volta a ficar livre.
- **Ações AJAX novas do Dashboard** passam por `dashboard_lousas_acao()`: guarda de permissão antes de qualquer consulta e mensagem genérica na falha.
- **`error_log()` grava mesmo com `log_errors=0`**: teste que roda PHP em processo à parte com `2>&1` deve ler a resposta na última linha, não na saída inteira.
- **Instalação da 3.1 só tinha o administrador**: para testar recusa foi criado no banco local o usuário 2, `usuario-de-roteiro` (perfil `cloud-nano`); `auth:cookie --project=conn2flow-v31-local --user=2`.
- **Arquivo novo de migração** entra no pacote do `project:update-all` como qualquer outro; a tabela nasce na própria rodada.

### REQ-252 / BATCH-261 — widget "Lousa" em páginas, linha 3.1 (2026-10-07)

- **Widget fora do iframe muda de comportamento**: `position: fixed` e medidas pela janela (`vh`) deixam de ser contidos. `contain: layout paint` na caixa prende o conteúdo fixo; medida pela janela continua sendo a do visitante.
- **Roteiro que passa não basta**: 24/24 com a página coberta por um widget. Abrir a imagem antes de dar o lote por validado.
- **Funções puras de layout** do Dashboard ficam em `dashboard-layout.php` (painel e widget incluem). Teste que extraía trecho de `dashboard.php` passa a dar `require` nesse arquivo.
- **Widget novo de módulo**: entrada em `resources.<idioma>.widgets` do JSON do módulo (`id`, `name`, `icon`, `tabela`), `<módulo>.widget.php` com `<módulo>_render($params)` e, se houver script, `<módulo>.widget.js` incluído por `gestor_pagina_javascript_incluir([tipo => widget])`. A tabela dos registros precisa de `id`, `name`, `language` e `status`.
- **Troca de marcadores com conteúdo de terceiros**: `strtr` numa passada só; `str_replace` em sequência relê o que acabou de entrar.
- **Página de teste por SQL** na instalação local: copiar `layout_id`, `tipo`, `sem_permissao` e `framework_css` de uma página pública; `project` nulo.

### REQ-253 / BATCH-262 — módulo `dashboard-pages`, linha 3.1 (2026-10-07)

- **Módulo novo, o que registrar**: `<módulo>.json` (`tabela`, `bibliotecas`, páginas com `tailwind_dependencies` copiadas de um módulo vizinho), entrada em `resources/<idioma>/modules.json`, `user_profiles_modules.json` e, se houver operação, `module_operations.json` + `user_profiles_modules_operations.json`. `Req203LanguageAgnosticResourcesTest` conta essas listas.
- **Módulo cuja tabela é `paginas`** (como `publisher-pages`): tabela de apoio para o vínculo e `where` com `id IN (SELECT page_id ...)` na listagem.
- **Status e excluir por GET exigem `_csrf_token`** (`interface_acao_get_exigir_csrf`). Roteiro pega o endereço com token do botão da tela de edição (`a[href*="opcao=status"]`, `button.excluir[data-href]`).
- **Envio de formulário da interface no roteiro**: `document.querySelector('input[name="..."]').form.requestSubmit()`.
- **Modelo de sistema** (`templates`) entra por `resources/<idioma>/templates/<id>/<id>.html` e `.css`, com `target` no JSON do módulo.
- **PHPUnit com 1 erro "que some"**: `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` falha sempre que a suíte roda com `MSYS_NO_PATHCONV=1` no ambiente (o caminho de `OPENSSL_CONF` chega ao PHP no formato do Bash). Não exportar essa variável no mesmo comando da suíte; guardar a saída de toda execução.
- **Instalação da 3.1 não tem senha de administrador anotada**: o acesso do agente é por `auth:cookie`.

### REQ-254 / BATCH-263 — editor HTML e clonar no `dashboard-pages` (2026-10-07)

- **Módulo de página sem editor HTML não é aceito**: o Engenheiro Chefe quer o editor em todo módulo que publica página, e clonar onde for simples. Ver como referência antes de enxugar.
- **Editor HTML num módulo**: `html-editor` em `bibliotecas`, `#html-editor#` na página, `html_editor_componente([editar|adicionarEditar, modulo, alvo, alvos_modelos, layout_id])`, depois `#pagina-html#`, `#pagina-css#`, `#pagina-css-compiled#` e `#pagina-html-extra-head#`. O pedido traz `html`, `css`, `css_compiled` e `html_extra_head`. No cliente: `html_editor_get_html/set_html/set_css/refresh_preview`. Dependências Tailwind da página: copiar a lista completa de um módulo que já usa o editor.
- **Clonar** é a opção `clonar` com a mesma marca de gravação do adicionar (`adicionar-banco`): formulário preenchido pela origem e a mesma função de inserção.
- **Carga assíncrona antes de salvar**: troca de modelo por AJAX seguida de envio grava o conteúdo antigo; segurar o `submit` enquanto houver carga pendente e reenviar ao terminar.
- **Hash de senha do painel**: `password_hash` Argon2 puro, sem tempero por instalação; hash gerado em qualquer PHP com Argon2 vale no campo `usuarios.senha`.

### REQ-255 / BATCH-264 — modelos e modo IA do `dashboard-pages` (2026-10-07)

- **Módulo com editor precisa do pacote completo**: vários modelos (3 a 4), `ai_prompts_targets` e `ai_modes` (arquivo `resources/<idioma>/ai_modes/<id>/<id>.md`, com `target` e `default`) no JSON do módulo. A aba Modelos do editor filtra por `alvos_modelos` e pelo framework CSS.
- **Aba do editor no roteiro**: `a[data-tab="modelos"]`; procurar pelo texto "Modelos" clica no item do menu lateral.
- **Lab da 3.1 sem servidor de IA**: o Assistente IA só mostra o aviso; modo de IA não dá para exercitar ali.
- **Alerta de vírus do Windows** aparece em comandos que rodam PHPUnit (scripts temporários executados) e Chromium sem tela; saída faltando pode ser bloqueio.

### REQ-258 / BATCH-267 — modelos de lousa prontos, linha 3.1 (2026-10-07)

- **Modelo de lousa** é um modelo do cadastro (`templates`, alvo `dashboard-boards`) cujo HTML é o arranjo em JSON. Lido por `dashboard_widgets_layout_ler`, o mesmo caminho das lousas. Widget no modelo traz só o tipo; o registro é o primeiro ativo da instalação.
- **Recurso novo num módulo que já tem JSON grande**: inserir as chaves por texto antes de uma chave existente, com o mesmo estilo de barra do arquivo, em vez de regravar o JSON inteiro.
- **Requisições 256 e 257 são da linha 3.0** (miniaturas e aba Modelos); a 3.1 pulou esses números. A `main` não foi trazida para a `3.1` por decisão do Engenheiro Chefe.
- **Pipeline da 3.1 pode passar de 10 minutos** depois de mexer na árvore do site: rodar em segundo plano e esperar o aviso.

### REQ-259 / BATCH-268 — medição da lousa publicada, linha 3.1 (2026-10-07)

- **Medição sem acoplar o core ao GA4**: o `analytics-manager` do site escuta o evento de página `c2f:analytics` (`detail.event`, `detail.data`) no gatilho "evento personalizado". O core só emite; quem envia ao GA4 é o módulo.
- **Conferência de "não fala com terceiros"**: comparar com uma página sem o recurso e olhar o tipo do pedido (`resourceType`); o layout e o conteúdo dos widgets pedem imagem e fonte a outros servidores por conta própria.
- **Arquivo de relatório para outra equipe** vai no repositório privado do site (`sdd/reviews/`), não no core, que é público.
### REQ-256 / BATCH-265 — miniaturas dos modelos (2026-10-07)

- **Modelo novo precisa de miniatura**: `thumbnail` no recurso (`templates/images/<idioma>/<id>.webp`) e o arquivo em `gestor/assets/templates/images/`. `TemplatesMiniaturasReq256Test` falha sem isso. Gerador e passo a passo em `sdd/validation/req256/README.md`.
- **Prévia crua não serve de miniatura**: é preciso repetir o bloco de item, tirar os blocos de estado vazio e preencher os marcadores; depois **abrir as folhas de conferência**, porque o gerador não sabe se ficou bom.
- **Enquadrar miniatura**: nada de `zoom` com largura reduzida (cola o conteúdo à esquerda). Medir a caixa do conteúdo pintado, escalar com `transform` e centrar com margem; reduzir quando passa do quadro. O Engenheiro Chefe quer a área útil inteira, mesmo menor.
- **Chave repetida em JSON**: a segunda vence. Recurso que já tinha `"thumbnail": ""` precisa ter a linha vazia removida.
- **Árvore de trabalho nova**: junções de `node_modules` e `vendor` com `cmd //c "mklink /J ..."`, cópia de `dev-environment/data/environment.json`, e o primeiro `project:update-all` recompila o CSS inteiro (mais de 10 minutos): rodar em segundo plano.
- **Linha 3.0**: trabalho de lançamento vai em árvore própria a partir de `origin/3.0`, entregue em `3.0` e `main`.

### REQ-257 / BATCH-266 — aba Modelos na inclusão e corte das miniaturas (2026-10-07)

- **Aba Modelos do editor filtra por framework CSS.** Em tela sem campo de framework o valor só existe depois que um modelo é carregado; na inclusão o editor mandava `fomantic-ui` e não vinha nada. Framework vazio agora significa "não sei" e o servidor não filtra.
- **Miniatura de modelo é 4:3** (580 × 435): o cartão usa `aspect-4/3` com `object-cover`. Conferir a miniatura já recortada na proporção do cartão, não o arquivo inteiro.
- **Antes de dizer que a imagem está certa, descobrir onde e como ela é exibida** (proporção da caixa, `object-fit`): o arquivo estava certo e o cartão cortava.
- **Botão "Selecionar Modelo" do editor responde a `mouseup`**, não a `click`: no roteiro, `dispatchEvent(new MouseEvent('mouseup', {bubbles: true, button: 0}))`.
- **Não listar `git status` inteiro numa árvore nova**: fim de linha marca centenas de arquivos; usar `git diff --ignore-cr-at-eol --name-only`.

### Integração da 3.1 na 3.0 (DEC-134, 2026-10-07)

- **Unir duas linhas**: branch de integração na árvore da linha nova, `git merge --no-ff --no-commit origin/main`; índices do SDD se resolvem por união dos dois lados; dados gerados (`TemplatesData`, manifestos) se refazem com `resources:sync` e `assets:minify` depois do merge.
- **Antes do merge**, descartar mudança só de fim de linha nos arquivos que o outro lado também mexe (`git checkout HEAD -- sdd`), senão o merge recusa.
- **Modelo cujo conteúdo não é HTML** (arranjo de lousa em JSON) precisa de desenho próprio no gerador de miniaturas: `lousaAmostra()` em `sdd/validation/req256/gerar-miniaturas.cjs`.
- **Validar a união nos dois Labs** antes de enviar: roteiros da linha nova na instalação dela e, na instalação de lançamento, tabelas criadas, telas novas respondendo e os roteiros que não dependem de dado preparado.

### REQ-260 / BATCH-269 — camada de provedores de IA (2026-10-07)

- **Falar com IA é pela `ia-provedores.php`**: `ia_provedor_servidor_do_banco($linha)` e depois `ia_provedor_gerar_texto()` ou `ia_provedor_gerar_imagem()`. Nada de `curl` próprio nem de chave no endereço; `ia_enviar_prompt()` já usa a biblioteca.
- **Teste de conexão sem limite de saída**: modelo que raciocina gasta o limite antes de responder e o teste falha à toa.
- **Biblioteca nova precisa de registro** em `$_GESTOR['bibliotecas-dados']` (`gestor/config.php`), senão `gestor_incluir_biblioteca()` não inclui nada e não avisa.
- **Célula condicional com marcador dentro** (`<!-- x < -->#tipo#<!-- x > -->`): trocar o marcador na célula antes de colocá-la na linha; a troca feita antes na linha não alcança o que entra depois.
- **Rolagem em 390 px no roteiro**: abrir a página já na largura de celular. Redimensionar a janela aberta deixa o menu lateral do painel no estado de desktop e acusa rolagem que não existe.
- **Pedido do editor à IA no roteiro**: `ajaxOpcao=html-editor-ia-requests` em tela com editor (`admin-paginas/adicionar/`), com `server_id`, `mode`, `prompt` e `data`; volta `status: Ok` e `data.html_gerado`.

### REQ-261 / BATCH-270 — ponto de extensão geral e conexão com o banco em pedido de IA (2026-10-07)

- **Recurso para todas as telas do painel**: hook `interface` / `pagina` (módulo e opção como argumentos), disparado no `interface_finalizar()` depois dos pontos por módulo. O callback sai cedo quando não se aplica.
- **Espera longa por serviço externo derruba a conexão com o banco**: o `banco_query()` só conecta quando não há conexão e não reconecta a que caiu; a gravação depois da espera falha com erro só no log. Antes de espera longa, soltar a conexão (`ia_provedor_banco_soltar()` é o modelo). Sintoma: registro que some de vez em quando, nos pedidos mais demorados.
- **O mesmo título de comentário abre dois blocos em `interface.php`** ("Disparar hook de página"): em teste de fonte, usar a última ocorrência.

### REQ-262 / BATCH-271 — pontos de extensão do uso de IA (2026-10-07)

- **Cota, crédito, auditoria e medição de IA se ligam em `ia-provedores` / `pedido.autorizar` (filtro) e `pedido.concluido` (ação)**; não se altera quem chama a camada. Quem chama informa `recurso` e `referencia` no pedido.
- **`banco_select()` devolve coluna calculada pela expressão inteira** (`SUM(x) AS total` é a chave) e separa colunas por vírgula: expressão com vírgula parte a coluna em duas. Defeito achado no módulo de créditos do site.
- **Teste sem rede da camada de provedores**: servidor do tipo compatível apontando para `http://127.0.0.1:9/v1` dá falha de comunicação imediata e exercita o caminho de erro sem sair da máquina.
