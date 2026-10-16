# Registro de Mudanças (Changelog)

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não Lançado]

## [3.0.8] - 2026-10-16

### Fixed
- Deploy: o transporte rsync via SSH agora garante a gravação dos arquivos com o usuário e grupo de destino (`--chown`) mesmo em conexões autenticadas como root (`REQ-281`).
- Atualizador pelo painel: liberação do `deploy.lock` sempre que uma etapa web do atualizador retornar erro, não apenas em erros fatais do PHP (`REQ-280`).
- Atualizador pelo painel: detecção de processos donos mortos da trava via `posix_kill($pid, 0)` quando `/proc` for inacessível sob restrições de `open_basedir` do PHP (`REQ-280`).

## [3.0.7] - 2026-10-15

### Fixed
- Atualizador pelo painel: prevenção de queda da conexão de banco em servidores com `wait_timeout` curto (`REQ-279`). A sessão PDO de atualização e a conexão do gestor durante as etapas web agora elevam seu `wait_timeout` de sessão para pelo menos 8 horas (exclusivo da sessão, sem alterar configuração global), prevenindo erros `2006 MySQL server has gone away` durante leituras extensas de JSON após as migrações.

## [3.0.6] - 2026-10-15

### Fixed
- Atualizador pelo painel: entrega garantida dos arquivos do próprio atualizador mesmo com manifesto desatualizado (`REQ-278`). Ao aplicar atualizações pelo painel, os arquivos vitais do atualizador do core (`controladores/atualizacoes/`, `modulos/admin-atualizacoes/`, `deploy-lock`, `db-data`, `instalacao-*`, `atualizacoes-*`) agora são sempre entregues e atualizados quando a nova versão diferir, impedindo a execução de rotinas antigas nas etapas subsequentes e preservando a cópia anterior no snapshot de entrega.

## [3.0.5] - 2026-10-15

### Fixed
- Atualizador pelo painel: prevenção de trava órfã e exaustão de memória na sincronização do sistema (`REQ-277`). Registra manipuladores de encerramento nas etapas web (`start`, `deploy`, `db`) para liberar o `deploy.lock` automaticamente em caso de erro fatal, eleva o `memory_limit` para pelo menos 1 GB durante as etapas pesadas, detecta processos donos mortos no Linux (`/proc`), e adiciona opção na interface para destravar o painel e tentar novamente com segurança.

## [3.0.4] - 2026-10-15

### Added
- Visibilidade de páginas e links diretos privados (`REQ-275`): `admin-paginas` e `publisher-pages` agora oferecem controle de visibilidade pública (padrão) ou acesso exclusivo via link direto privado (`?acesso=<hash>`). Páginas não visíveis são omitidas do sitemap e widgets de índice, respondem 404 para visitantes comuns, liberam a navegação na sessão com o link válido e para administradores, e contam com regeneração de link em um clique.
- Acesso Restrito ao Site: passe de navegador confiável para navegação anônima (`REQ-274`). Permite que administradores autorizem o navegador atual diretamente em `admin-environment` para navegar pelas páginas públicas como visitante deslogado mesmo com `SITE_RESTRICTED_ACCESS` ativo para o público geral. Conta com cookie HttpOnly assinado com HMAC-SHA256, prazo de validade configurável, revogação do navegador atual e revogação global imediata por rotação de salt no `.env`.

## [3.0.3] - 2026-10-15

### Added
- Formulários: novo tipo de campo "Domínio" (`domain`) com validação FQDN (RFC 1035/1123) no cliente e servidor, mensagens de validação internacionalizadas e suporte resiliente a contêineres de erro em formulários Tailwind (`REQ-273`, `REQ-130`).

### Changed
- Formulários: `formulario.js` desacoplado de utilitários visuais; manipula exclusivamente classes semânticas de estado (`has-error`, `c2fc-campo-invalido`, `c2fc-campo-msg-erro`, `c2fc-contador-excedido`, `c2fc-form-msg-erro`), com estilização transferida para o componente `form-ui.css` (`REQ-131`).

### Fixed
- Dashboard: negação de acesso na rota `dashboard/` exibe a tela de acesso negado (403) em vez de causar loop infinito de redirecionamento quando o perfil não tem permissão ao painel (`REQ-131`).
- Instalador: garante a vinculação do perfil `administradores` para administradores criados sem perfil atribuído na instalação (`REQ-131`).

## [3.0.2] - 2026-10-14

### Fixed
- Atualização do sistema com tabelas de projeto: o sincronizador completa o contrato com o `project-schema-metadata.json`; tabelas do projeto como `publisher` eram tratadas como novas e falhavam com chave duplicada (`REQ-272`).
- Atualização do sistema: falha na sincronização do banco agora falha a atualização, em vez de terminar como sucesso com as sementes antigas (`REQ-272`).

## [3.0.1] - 2026-10-14

### Fixed
- Deploy pela API: a tela de manutenção (`req-210`) respondia 503 à verificação pós-deploy (`req-198`), e todo deploy de projeto era desfeito. A manutenção agora é desligada antes da verificação (`REQ-272`).
- Atualização do sistema pelo painel vinda da 2.10.x: o sincronizador do banco carrega o `db-data.php` por conta própria; o processo antigo já tinha o `gestor.php` carregado, as sementes não eram sincronizadas e o painel ficava com os layouts da versão anterior (`REQ-272`).
- Sincronização do banco: booleanos das sementes (`"sem_permissao": false`) gravados como 0/1; o PDO enviava `false` como texto vazio e o MariaDB/MySQL em modo estrito recusava registros novos (`REQ-272`).

## [3.0.0] - 2026-10-14

Versão principal: o painel administrativo passa para Tailwind CSS, o Dashboard ganha a área de widgets e a lousa, e a atualização do sistema fica segura, automática e reversível.

### Added
- Painel: todos os módulos administrativos migrados do Fomantic-UI para Tailwind CSS v4, com biblioteca de controles, diálogos no lugar dos nativos, listagem Tailwind (ordenação por várias colunas), telas de inclusão, edição e visualização e design system harmonizado (`req-190`, `req-219` a `req-224`, `req-238` a `req-243`).
- Dashboard: grade de módulos em três tamanhos com capas isométricas, área de widgets com layout por perfil, layouts salvos, permissões e configuração por widget, modos grade e lousa livre, janela cheia e opções flutuantes (`req-226`, `req-227`, `req-236`, `REQ-247`, `REQ-248`).
- Lousa: objetos livres (texto, forma, imagem, ícone, botão), esconder por largura de tela, imagem de fundo e Google Fonts; lousas nomeadas do sistema com duplicar e versões; widget "Lousa" para qualquer página; módulo "Páginas de Lousa" (`dashboard-pages`) com editor HTML, modelos e modo IA; modelos de lousa prontos; medição pelo evento `c2f:analytics` (`REQ-250` a `REQ-255`, `REQ-258`, `REQ-259`, `DEC-134`).
- Atualizações: manifesto por camada, trava de deploy, backup total, snapshot, verificação de saúde e volta automática; resolução de choques por arquivo no painel, na API e no CLI (`c2f update:conflicts`, `update:resolve`, `update:rollback`); atualização do sistema pela API em segundo plano (`c2f update:core`); atualização automática com abas Manual e Automático (`req-197` a `req-201`, `req-245`).
- Core: página inicial e layout por perfil de usuário (1:N, compilação multi-layout); tabelas de sistema com autoria em `resources/` e sementes sem idioma; `GestorState` com `gestor_get/set/has` controlados; tabelas de semente particionadas com manifesto validado (`req-196`, `req-202` a `req-204`, `req-229`, `req-234`).
- IA: camada de provedores para Gemini, Claude, OpenAI e compatíveis (texto e imagem, chave no cabeçalho) e pontos de extensão para autorizar o pedido de IA e registrar o consumo (`req-260`, `req-262`).
- Extensibilidade: ponto de extensão geral das telas do painel (`interface` / `pagina`) (`req-261`).
- Módulos: `presentations` (apresentações em slides) e `cookie-consent` (aviso e preferências, consentimento lido no servidor) (`req-208`, `req-209`, `req-214`).
- Módulos distribuídos: login padrão, handshake seguro e ponte, catálogo local, estado da conta pelo canal, confirmação de origem e chave de sessão (`REQ-092`, `req-213` a `req-218`).
- Pagamentos (Stripe): cupom na criação da assinatura, itens avulsos na primeira fatura e parcelamento opcional no PaymentIntent (`req-192`, `req-193`, `req-195`).
- Modelos: miniaturas WebP de todos os modelos do cadastro de modelos (`req-256`).
- Documentação: contrato de documentação, `c2f docs:audit`, `docs:extract` e `docs:build` publicando o Markdown do core como recursos do sistema, referência das bibliotecas com filtro e índice (`req-176` a `req-179`, `req-184` a `req-186`, `req-207`).
- CLI: `--skip-css` em `project:update-all` e `manager:update-all`; conferência por hash do código publicado; `db:check-migrations` (`REQ-269`, `req-213`, `req-197`).

### Changed
- Desempenho: índice de idioma + caminho em `paginas`; a busca da página lê uma linha, não todas as páginas do idioma (`req-263`).

### Fixed
- Segurança: listagem sem SQL injection e excluir/status com token CSRF; página com `sem_permissao = 0` passa a exigir login (`req-189`, `REQ-268`).
- Pagamentos: reconciliação do Stripe serializada por gateway e marca resolvida nos e-mails; a trava de pagamentos foi para o `stripe.php` (a biblioteca avulsa `pagamentos.php` derrubava o checkout) (`REQ-265`, `REQ-271`).
- Pipeline: `dist/` publicado com permissões de leitura (755/644); falha ao gravar `Data.json` interrompe a compilação; dono do core preservado na atualização do banco do projeto (`REQ-271`, `req-206`).
- Widgets do Dashboard: links, formulários e navegação saem do quadro; paridade de estilo no iframe do widget (`REQ-244`, `REQ-249`).
- Sitemap: carrinho, checkout e desfechos do checkout fora do índice (`req-191`).

## [2.10.13] - 2026-09-25

### Added
- Segurança & Core: Renovação silenciosa de CSRF (`renovarCsrf()`) com fila de retry automático sob `fetch`, `jQuery.ajax` e `XMLHttpRequest` no `global.js` (`BATCH-180` / `req-175`).
- Segurança & Core: Rota de sistema `_gestor-csrf-token` no roteador do core com códigos de máquina `CSRF_INVALID_OR_EXPIRED`, marcação em cabeçalhos e isenção defensiva (`BATCH-180` / `req-175`).
- UX & Formulários: Atualização proativa de CSRF ao retornar o foco à aba via evento `visibilitychange`, evitando perda de preenchimento em formulários (`BATCH-180` / `req-175`).

## [2.10.12] - 2026-09-22

### Added
- Segurança & Formulários: Suporte nativo ao Cloudflare Turnstile no Core (`admin-environment`, módulo `auth` e widget `forms`) (`BATCH-176` / `req-171`).
- Autenticação & Tailwind: Fluxo unificado e fallback de reCAPTCHA v3/v2 e Turnstile nas interfaces de login sob Tailwind CSS (`BATCH-177` / `req-172`).
- Roteamento & Formulários: Preservação de query strings em redirecionamentos 301 e contenção estrutural de fragmentos no widget `forms` (`BATCH-178` / `req-173`).
- DevOps: Sincronização automática da tabela `hooks` em deploys de projeto (`project:update-all`) e comando dedicado `project:sync-hooks` (`BATCH-179` / `req-174`).

## [2.10.11] - 2026-09-18

### Added
- Segurança: Interceptador nativo de `XMLHttpRequest` no `global.js` injetando automaticamente o header `X-CSRF-Token` em chamadas `same-origin` (`BATCH-168` / `req-163`).
- Segurança: Camada de site restrito redirecionando visitantes não autenticados para `/acessar-sistema` com preservação de rotas essenciais (`BATCH-168` / `req-163`).
- Transporte: Blindagem de caminhos no Git Bash (`MSYS_NO_PATHCONV=1`) e pareamento cwRsync/Cygwin com flag `-T`, eliminando quebras `dup()` em pipes no Windows (`BATCH-169`, `BATCH-171`).
- Transporte: Encapsulamento atômico de sessões sob `sudo -u <tenant> sh -c` nas camadas Bash e PHP CLI, atendendo permissões restritas `750` em servidores HestiaCP/Linux (`BATCH-172`, `BATCH-173`).
- Segurança & CLI: Sanitização de escapes ANSI no Tailwind CLI no Windows (`BATCH-170`) e desacoplamento de limites de tentativas para erros de validação vs autenticação (`BATCH-174`).

## [2.10.10] - 2026-09-03

### Added
- Admin Cron: Disparo desacoplado de tarefas manuais via sessão CLI independente (`setsid`), prevenindo erro `502 Bad Gateway` ao reiniciar serviços como o PHP-FPM (`BATCH-166` / `REQ-039`).
- Pipeline CSS & CLI: Resolução de Tailwind global pelo PATH no Linux, propagação de `NODE_PATH` em `css-regenerar.php` e detecção de caminhos em instalações planas no `c2f css:rebuild` (`REQ-053`).
- Transporte de Projeto: Sincronização automática do launcher `c2f` e diretório `cli/` para alvos SSH e projeto mestre via `sync-core-to-project.sh`.

## [2.10.9] - 2026-09-03

### Added
- Editor HTML: Encapsulamento de cards de modelos em tags `<template>` inertes (`req-161`) e interpolação defensiva de variáveis em layouts (`req-162`), prevenindo requisições 404 por `{{thumbnail}}`.
- CLI & Pipeline: Transporte SSH em `css:audit`, `assets:publish` e `project:update-all`, além de suporte a gestor distribuído com `chown` automático.

### Fixed
- Admin Cron: Inicialização correta de `admin-cron.js` com ouvinte de `DOMContentLoaded` (`BATCH-165` / `REQ-038`), restabelecendo a tabela de tarefas e interatividade dos botões.
- Paridade CSS: Paridade visual em 3 camadas no Tailwind CSS e persistência de baseline acumulado de templates inseridos (`req-158`, `req-159`, `req-160`).

## [2.10.8] - 2026-09-02

### Changed
- Infraestrutura de GitHub Actions: `softprops/action-gh-release` atualizado para `v3` (Node 24 nativo), completando a migração total de todas as actions para Node 24 (`checkout@v5`, `setup-node@v5`, `cache@v5`, `action-gh-release@v3`) em `release-gestor.yml` e `release-instalador.yml` (`req-157` / `BATCH-160`).

## [2.10.7] - 2026-09-02

### Changed
- Infraestrutura de GitHub Actions: Atualizadas as ações `actions/checkout`, `actions/setup-node`, `actions/cache` e `softprops/action-gh-release` para suporte nativo ao Node 24 em `release-gestor.yml` e `release-instalador.yml` (`req-157` / `BATCH-160`).

## [2.10.6] - 2026-09-02

### Changed
- Esteira CI/CD de Release: Removidas as etapas redundantes de compilação remota de recursos Tailwind em `.github/workflows/release-gestor.yml` para consumir diretamente os artefatos pré-compilados do release local (`req-156` / `BATCH-159`).

## [2.10.5] - 2026-09-02

### Added
- Documentação para paridade visual estrita do Tailwind CSS entre Página Pública, Pré-Visualizador e Editor HTML Visual (`req-156` / `BATCH-158`).

### Changed
- Limiares de memory gardening atualizados para 50KB/75KB e catálogo de skills de IA sincronizado.

## [2.10.4] - 2026-09-02

### Fixed
- Checksums MD5 Multiplataforma: Normalização de fim de linha em LF na compilação de recursos e atualização do `AdminCronReq032Test::testChecksumHtmlCoincideComMd5DoArquivo` para validação de checksums resiliente entre Windows (CRLF) e Linux CI (LF) (`req-155` / `BATCH-157`).

## [2.10.3] - 2026-09-02

### Added
- Suíte automatizada de testes de integridade dos templates Tailwind (`TemplatesTailwindIntegrityTest`) cobrindo HTML, sidecars e utilities essenciais de todos os 72 modelos (`req-154` / `BATCH-156`).

### Fixed
- Cascata no Preview de Templates Tailwind: Isolada a folha visual do Fomantic CSS no iframe do editor para evitar que regras sem camada vençam as utilities do Tailwind, e preservado o baseline da página na inserção de seções (`req-154` / `BATCH-156`).
- Gate de Validação de Checksum de Recursos: Zerados os checksums manuais em `admin-cron` permitindo o cálculo determinístico no build (`req-153` / `BATCH-155`).

## [2.10.2] - 2026-09-02

### Added
- Engine e Módulo de Tarefas Cron (`admin-cron`): Implementado o motor autônomo de execução de tarefas agendadas em segundo plano (`cron.php`), migração do banco (`cron_tarefas`) e interface CRUD de gerenciamento (REQ-152 / BATCH-154 e REQ-032 / BATCH-026).

### Fixed
- Compatibilidade TLS/SSL no Windows: Adicionada a flag `--ssl-no-revoke` em requisições curl para evitar falhas de revogação do Schannel durante o deploy e renovação de tokens no Windows.

## [2.10.1] - 2026-08-31

### Added
- Inicialização de agentes sem prompt e identificação explícita do repositório para handoffs mais claros entre múltiplos repositórios.

### Changed
- Documentação de README e changelog da raiz enxugada, com guias detalhados de ambiente de desenvolvimento e notas de release legadas movidos para documentos dedicados no workspace de IA.

## [2.10.0] - 2026-08-31

### Added
- Dependências automáticas de sistema no Tailwind: Os modais de tempo de execução do sistema (`interface_alerta`, dimmer, modal de confirmação) são agora automaticamente reconhecidos e estilizados em todas as páginas Tailwind, sem necessidade de declaração manual.
- Biblioteca de assets externos servida localmente: Dependências de SortableJS, DataTables, jQuery e Google Fonts hospedadas no disco local, eliminando dependências de CDNs públicas, aumentando a privacidade e mitigando riscos de supply-chain.
- Pipeline de minificação em tempo de build: Minificação nativa de JS/CSS em lote via Terser/CSS durante o build, reduzindo os pacotes de assets em mais de 50%.
- Compatibilidade total com o Instalador Web v2.0.0: Integração completa com travas concorrentes de instalação, recuperação após timeout e detecção apurada de Nginx/Apache.

### Changed
- Tabelas administrativas responsivas: Envelopamento com rolagem horizontal contida em resoluções amplas (>=1200px), evitando armadilhas de rolagem vertical.
- Padronização de campos de editor de texto do legado `tinymce` para `editor-texto`.

### Fixed
- Estilização de modais em telas de login e páginas públicas Tailwind quando alertas do sistema são disparados.
- Resolução de templates de galerias assegurando que customizações alcancem as páginas ativas.

## [2.9.51] - 2026-08-26

### Added
- Seleção em lote no seletor de arquivos, densidade responsiva em galerias e edição rápida de legendas e links.
- Posicionamento vertical de imagem (`top`, `center` ou `bottom`) na administração de galerias, templates e widgets públicos.

### Changed
- Grids de galeria com suporte a layouts compactos de 6 e 10 colunas com proporções previsíveis de miniaturas.
- Limpeza de release migrada de scripts locais para jobs pós-sucesso do GitHub Actions com push atômico de tag e branch.

### Fixed
- Normalização de MIME e validação de imagem na interface administrativa.
- Entrega no Editor ao Vivo para widgets vazios e HTML seguro de administradores.

## [2.9.39] - 2026-08-21

### Added
- **Editor ao Vivo & Barra de Ferramentas (Editbar)**: Barra flutuante contextual no site publicado permitindo edição in-place com mapeamento reativo ao vivo de nós do DOM, travas de widgets, isolamento de eventos e escudo contra cliques acidentais.
- **Painéis Flutuantes & Modais no Editor ao Vivo**:
  - Inserção de Elementos ("+"): Inserção contextual de blocos estruturais, widgets, formulários e embeds.
  - Templates de Sessão e Backups: Criação instantânea de snapshots e restauração direta de revisões.
  - Assistente de IA com CodeMirror: Interface de prompt com editor CodeMirror embutido e preview streaming.
  - Painel de Código Customizado: Edição direta de HTML, CSS (com debounce ao vivo), JavaScript e Extra Head.
- **Área de Transferência Persistente**: Clipboard salvo em localStorage permitindo copiar blocos estruturais em uma página e colar/substituir em outra página ou aba com remapeamento automático de IDs de widgets.
- **Painel de Opções Visuais do Editor**:
  - Barra Lateral CSS: Classes Tailwind agrupadas por variante, classes customizadas, editor CSS inline e inspetor ao vivo com `getComputedStyle()`.
  - Barra de Navegação de Elementos: Trilha de navegação hierárquica (breadcrumbs) com seleção de nós-filhos.
- **Modernização de Layouts em Tailwind CSS v4**:
  - Novo `layout-administrativo-tailwind`: Barra lateral responsiva com ícones Lucide, redimensionamento dinâmico (220–450px) persistido em `localStorage`, filtro de busca sem acento e navegação completa por teclado (Setas Cima/Baixo/Esc).
  - Novo `layout-pagina-sem-permissao-tailwind`: Layout público e migração de todas as 15 telas de identidade e autenticação para Tailwind puro na paleta azul (`sky`) do Conn2Flow.
  - Novo Painel de Perfil de Usuário: Interface moderna em abas com gerenciamento de sessões ativas, revogação remota e histórico de acessos.
- **Sincronização Declarativa e Sistema Pull (Engenharia Reversa)**:
  - Motor declarativo configurado em `tables_config.json` e `project_tables_config.json` suportando `sync_resources`, tipos de campo especiais (`json`, `file:<ext>`), `forcar_atualizacao`, `deletar` e checagens MD5.
  - Motor de Pull Reverso: Endpoint `/_api/project/recover`, script `recuperar-projeto.sh` e descompilador de recursos suportando subpastas estruturadas (`<tabela>/<id>/<id>.<ext>`) e resolução de conflitos em `contents/`.
- **Mídia Embutida, Streaming e Visualizador Híbrido de PDF**:
  - Wrapper atômico de embed (`.conn2flow-embed-wrapper`) com alças de redimensionamento e proteção de clique.
  - Visualizador híbrido de PDF com runtime PDF.js nativo (`pdf-viewer.js` com canvas, zoom e toolbar), Google Viewer e fallback nativo `<object>`.
  - Streaming de mídia via headers HTTP Range (206 Partial Content) para compatibilidade Safari/iOS, sanitização de espaços em nomes de arquivo e auto-dimensionamento responsivo.
- **Gerenciador Físico de Arquivos (Admin-Arquivos)**:
  - Transição para hierarquia de pastas do sistema de arquivos físico (CRUD completo de pastas e subpastas).
  - Uploads desbloqueados e criação de pastas dentro do modal seletor (modo iframe) com persistência do último diretório navegado.
- **Novos Módulos, Widgets e Funcionalidades**:
  - Módulo `forms-search`: Formulários de busca pública com autocomplete AJAX e temas em lente.
  - Módulo `pages-index`: Listagem de páginas índice com destaques, filtros, sincronização de URL e paginação dinâmica.
  - Transferência de publicações entre publicadores no `publisher-pages` com ajuste automático de URL e registro de redirecionamento 301.
  - Customização de rótulos e ordem de módulos em `modulos-grupos` com suporte a sobreposição por componentes de projeto.
  - Mapeamento de pares de ícones Fomantic e Lucide para módulos derivados de projetos via migration Phinx `20260821100000_alter_modulos_update_icones_projetos`.
- **Integrações de Gateways de Pagamento**:
  - Biblioteca PayPal 3.1.0: Checkout transparente nativo com Card Fields e Hosted Fields.
  - Biblioteca Core do Stripe: Integração completa com Stripe (Payment Element, Cobrança, Assinaturas, Webhooks HMAC e catálogo de Produtos/Preços).
- **Segurança e Tokens de Acesso Pessoal (PAT)**:
  - Geração e validação de tokens `c2f_pat_` com hashing SHA-256 e códigos de recuperação 2FA.
  - Tolerância a desvios de schema com gates de degradação graciosa (`gestor_schema_tabela_existe` e `gestor_schema_campo_existe`).
- **SEO, Sitemap XML e Robots**:
  - Metadados dedicados de SEO e Open Graph por página/publicação (`imagem_destaque`, `og_titulo`, `og_descricao`, `meta_descricao`, `meta_keywords`) com aba SEO no Editor HTML e no Editbar.
  - Gerador dinâmico de sitemap entregando `assets/sitemap.xml` com filtros de rotas não indexáveis e limpeza automática de redirecionamentos 301.
  - Geração automatizada de `assets/robots.txt`.
- **Subsistema CLI Moderno**: CLI orientada a objetos em `/cli` e executável `c2f` com catálogo completo de comandos.

### Changed
- **Arquitetura de Recursos e Variáveis**: Extração de marcações HTML para componentes em `resources/` e utilitários de apresentação para variáveis de sistema (`@[[classe-...]]@`).
- **Governança de Autonomia de IA**: Formalização do Espectro de 3 Níveis de Autonomia (Supervisionado, Autônomo Monitorado, Autônomo Headless) e catálogo de Skills do Conn2Flow.
- **Alternância de Botão de Menu**: Visibilidade contextual de abertura/fechamento no layout administrativo Tailwind e `admin-tailwind.js`.

### Fixed
- **Recarregamento Limpo em CSRF / Sessão Expirada**: `gestor_csrf_resposta_invalida()` forçando recarregamento limpo / `location.replace` ao retornar a `/signin/`, eliminando loops de expiração de token pelo bfcache.
- **Eliminação de Avisos de Ícones Lucide**: Sanitização em duas camadas em PHP e JS prevenindo que nomes compostos legados do Fomantic cheguem a `data-lucide`.
- **Eliminação de Loops de Cookies para Crawlers e Bots**: Fim definitivo do ciclo infinito de redirecionamentos de cookies para robôs de busca (`gestor_cookie_verificacao_desfecho`) e cabeçalhos anti-indexação em rotas de sistema.
- **Desacoplamento do PHP 8.5**: Eliminação de falso erro 429 durante deploy desacoplando a linha 2.x de sintaxe exclusiva do PHP 8.5.

## [2.9.0] - 2026-06-16

### Added
- **Autenticação de Dois Fatores (2FA)**: Suporte nativo a 2FA via aplicativos autenticadores (TOTP) e e-mail nos perfis de usuários.
- **Login Sem Senha via OTP**: Autenticação por e-mail sem necessidade de senha utilizando códigos de uso único (OTP).
- **Gerenciador de Chaves de API**: Configuração dedicada de chaves de API nas opções de ambiente, com suporte a perfis de acesso e proteção por 2FA.
- **Guia de Integração OAuth**: Assistente interativo passo a passo para configuração de integrações OAuth com Google, Facebook, Apple e GitHub.
- **Editor HTML Visual - Painel Styler**: Painel avançado com 20 grupos de formatação (Texto, Layout, Caixa, Aparência) e paletas de cores circulares nativas.
- **Drag & Drop Interativo**: Placeholders visuais piscantes indicando a posição de encaixe dos elementos e ghost follower indicando o formato do elemento inserido.
- **Clipboard Interno**: Botões de Copiar e Colar com atalhos de teclado `Ctrl+C` e `Ctrl+V` nativos no editor.
- **Ferramenta Embrulhar (Wrap)**: Funcionalidade para envelopar o elemento selecionado em tags estruturais (div, section, article, etc.).
- **Esqueletos de Widgets Dinâmicos**: Renderização realista de widgets via endpoint `html-editor-widget-render` para exibir a estrutura de layouts no editor visual.
- **Curadoria Manual no publisher-index**: Suporte para curadoria e ordenação manual de publicações na interface administrativa (CRUD).
- **Contadores de Métricas**: Métricas dinâmicas exibindo "Exibindo X de Y publicações" ao carregar itens por AJAX no `publisher-index`.

### Changed
- **Layout do Styler do Editor**: Colunas invertidas posicionando os controles visuais à esquerda e tags do CodeMirror à direita, com toolbar reposicionável em telas cheias.
- **Refatoração do Editor Visual**: Extração de 26 funções de simulação para `html-editor-modules.js` para simplificar e modularizar o `html-editor-interface.js`.
- **Correções de Temporal Dead Zone (TDZ)**: Movimentação do `contentPageTabHandler()` e da inicialização do `.tab()` do Fomantic para o fim do ready do JQuery.
- **Preservação de Overlays no Editor**: Substituição da reescrita destrutiva de `body.innerHTML` por substituições cirúrgicas em nós de texto (via TreeWalker) ao parsear variáveis `[[widgets#...]]`.

### Fixed
- **Busca Unicode no publisher-index**: Filtro inteligente de termos com acentuação e caracteres especiais nos títulos das publicações.
- **Paginação de Duplicados no publisher-index**: Remoção de páginas de índice e duplicadas nos resultados da busca.
- **Escape de Caracteres Especiais em Widgets**: Correção do bug que convertia `->` e aspas das variáveis `[[widgets#...]]` em entidades HTML (`&gt;`), quebrando o renderizador do backend.
- **Prefixagem Dinâmica de Imagens**: Detecção automática de campos do tipo `image` no `publisher-index` e `publisher-highlights` para adicionar o prefixo de URL raiz do gestor.

---

## Arquivos Históricos

O histórico de versões anteriores está catalogado e mantido em arquivos dedicados:
- [Versões Legadas v2 (v2.0.21 a v2.8.4)](ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v2-legacy.md)
- [Versões Iniciais v1 (v1.0.0 a v1.16.0)](ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v1.md)
- [Evolução Detalhada de Commits](ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md)
