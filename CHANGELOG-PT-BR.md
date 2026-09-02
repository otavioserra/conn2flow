# Registro de Mudanças (Changelog)

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não Lançado]

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
