# Decision Log (Archive: DEC-031 to DEC-040)

> As decisões correntes mais recentes permanecem em `sdd/decisions/DECISION-LOG.md`. Este arquivo guarda o histórico movido para manter o log corrente enxuto (≤10 itens).

## DEC-031 - 2026-06-05 - accepted

Registro de Alvo de IA, Variáveis Globais e Modos de IA para o Módulo de Galerias (req-019 / BATCH-019). Decisões desta rodada:
1. **Registro do Alvo `galleries`**: Adicionar `ai_prompts_targets` e `ai_modes` em `galleries.json` para que a rotina `atualizacao-dados-recursos.php` compile e registre o alvo `galleries` nos arquivos `AlvosIaData.json` e `ModosIaData.json` em ambos os idiomas (`pt-br` e `en`).
2. **Criação de Prompts/Modos de IA**: Criar os arquivos de prompt/modo em markdown para o alvo `galleries` (`galleries.md` em `pt-br` e `en`) contendo as regras de estruturação (repetição de items via `<!-- item < -->`, controle condicional de setas/pontinhos, repetição de `dot-item` e atributos `data-*` do contêiner).
3. **Mapeamento de Variáveis Globais vs Itens**: No html-editor, unificar a aba de variáveis. A função `galleries_variaveis_template()` em `galleries.php` retornará tanto variáveis de item quanto variáveis globais de controle (como `show_arrows`, `show_dots`, `autoplay`, `autoplay_speed`, `loop`), estas últimas marcadas com `'global' => true`.
4. **Tratamento no HTML Editor e Ajax IA**: No `html-editor.php`, se a variável possuir `'global' => true`, o mapeamento do template gerará o placeholder `[[VAR_ID]]` (sem o prefixo `item#`). No AJAX de IA (`html_editor_ajax_ia_requests`), processar `menus` e `galleries` injetando a lista de variáveis correspondentes na substituição do marcador `{{variables}}` no prompt do Modo de IA.

## DEC-032 - 2026-06-08 - accepted

Integração de Compilação Tailwind CSS CLI para o Core do Sistema e Pipeline de Release (req-020 / BATCH-020). Decisões desta rodada:
1. **Pasta de Compilação do Core**: Definir a pasta `gestor/assets/tailwindcss/` para abrigar a estrutura do compilador Tailwind CSS v4 para o core/manager. O arquivo de entrada será `input.css` contendo a diretiva `@import "tailwindcss";` e a diretiva `@config "../../../tailwind.config.js";`, e o output compilado será gerado em `output.css` via npx.
2. **Integração em Ambientes Locais**: Atualizar os scripts de sincronização de desenvolvimento `synchronize-manager.sh` (sincronização do manager com Docker local) e `sync-core-to-project.sh` (sincronização do core com projetos de testes) para ler a variável de configuração `devEnvironment.tailwindcss/cli` do arquivo `environment.json`. Caso configurada, os scripts deverão executar o compilador na pasta de origem do core antes de realizar a transmissão (rsync), tratando erros de compilação.
3. **Pipeline de Release**: Modificar o workflow de release `release-gestor.yml` do GitHub Actions para configurar o Node.js v20 e rodar o compilador do Tailwind CSS CLI antes de gerar o pacote ZIP, garantindo que o `output.css` seja incluído no commit de release e no pacote compactado distribuído.

## DEC-033 - 2026-06-08 - accepted

Adoção de Estrutura de Testes Unitários e E2E centralizada na raiz do repositório (req-022 / BATCH-022). Decisões desta rodada:
1. **Pasta de Testes Centralizada**: Criar a pasta `tests/` na raiz do repositório (`conn2flow/tests/`), separando as suítes de teste em `Unit/` (algoritmos PHP/JS puros e isolados), `Integration/` (fluxos que requerem banco ou Docker) e `E2E/` (fluxos de navegação e interface real). A pasta será excluída dos pacotes de release ZIP do gestor.
2. **Frameworks e Configuração**: Adotar o PHPUnit para testes backend em PHP (com arquivo `bootstrap.php` para carregar dependências do core e `phpunit.xml`), o Vitest para testes rápidos de JS frontend, e o Playwright para testes funcionais E2E simulando as interfaces CRUD e visualizadores em navegadores headless.
3. **Atalhos no Workspace**: Registrar tarefas de automação no arquivo `.vscode/tasks.json` para facilitar a execução rápida de cada suíte de testes por parte de operadores humanos e agentes autônomos.

## DEC-034 - 2026-06-08 - accepted

Correção HTML, Automação de Campos e Lançamento v2.8.0 (req-021 / BATCH-021). Decisões desta rodada:
1. **Inicialização do Quill em publisher-pages**: No carregamento do formulário de edição do publicador páginas, inicializar o input hidden do campo HTML com o valor corrente do Quill editor para garantir o envio correto dos dados mesmo se o usuário salvar sem editar o texto.
2. **Botão de Automação de Campos em publisher**: Incluir o botão "Adicionar todos os campos" que insere em lote as variáveis do template que não foram associadas ainda. O nome amigável do campo é derivado via remoção de underlines e capitalização de cada termo.
3. **Preenchimento Automático de Prompts**: Ao adicionar um campo associado ao template, pré-preencher o seletor correspondente. Se o campo adicionado for dinâmico e não possuir mapeamento de prompt legado, gerar o marcador `[[publisher#tipo#id]]` como fallback padrão no input prompt.

## DEC-035 - 2026-06-09 - accepted

Unificação do Pré-visualizador de HTML Externo nos Módulos (req-022 / BATCH-022). Decisões desta rodada:
1. **Unificação do Visualizador**: Substituir a geração de iframe/srcdoc duplicada e hardcoded no sucesso do AJAX `opcao: 'widget-preview'` nos arquivos de Javascript de destaques (`publisher-highlights.js`), menus (`menus.js`) e galerias (`galleries.js`).
2. **Biblioteca de Editor HTML**: Consumir a nova função de biblioteca unificada `window.previewExternalHtmlConteudo({ htmlDoUsuario, cssDoUsuario, framework })` exposta globalmente por `html-editor-interface.js`.
3. **Mapeamento de Parâmetros**:
   - `htmlDoUsuario`: Conteúdo retornado pela resposta AJAX (`dados.html`).
   - `cssDoUsuario`: CSS personalizado do editor, obtido a partir da chamada para `window.html_editor_get_css()` (passado via variável `css` local do escopo).
   - `framework`: Identificar qual framework CSS está ativo por meio da variável global `gestor.html_editor.framework_css`.
   - **Garantia de Contingência**: Implementar fallback seguro mantendo a estrutura CDN do TailwindCSS hardcoded antiga se `window.previewExternalHtmlConteudo` não for detectada no escopo.

## DEC-036 - 2026-06-09 - accepted

Otimização do CSS Automático com Filtragem de Redundâncias do Tailwind (req-023 / BATCH-023). Decisões desta rodada:
1. **Remoção de Redundâncias**: Filtrar o CSS compilado dinamicamente gerado pelo Tailwind CDN no editor antes de ser inserido no campo `CodeMirrorCssCompiled` e gravado no banco de dados.
2. **API Nativa do Navegador**: Usar a API `document.styleSheets` no JavaScript do painel administrativo para ler os seletores textuais do `system-output.css` (e `output.css`) carregado no iframe do previewer e armazená-los em um `Set` de controle.
3. **Extração e PURGE Dinâmicos**: Acessar as regras de estilo de `tailwindStyleElement.sheet.cssRules`. Filtrar as regras mantendo apenas os seletores que não existem no `Set` de seletores globais. Para media queries (`@media`), filtrar individualmente as suas sub-regras. 
4. **Compatibilidade v3/v4**: A extração via `sheet.cssRules` garante suporte tanto ao Tailwind v3 (que injeta estilos no DOM) quanto ao Tailwind v4 (que insere regras dinamicamente via `insertRule`).

## DEC-037 - 2026-06-10 - accepted

Links Individuais, Controles de Exibição de Galerias e Correções Visuais (req-024 / BATCH-024). Decisões desta rodada:
1. **Estrutura de Links**: Cada item de imagem em `selected_items` no módulo `galleries` suportará metadados de link: `link_type` (`nenhum`, `pagina`, `link-custom`, `link-css-classes`, `publicador`), `link_page_id`, `link_url`, `link_target`, `link_css_classes`, `link_publisher_id`, `link_order_by`. A serialização é feita na curadoria do painel e salva no `fields_schema`.
2. **Resolução de Links no Widget**: Em `galleries.widget.php`, carregar slugs de páginas e buscar publicações de forma consolidada e eficiente, retornando `link-url`, `link-target` e `link-css-classes` por item. Imagens sem link resolvem para `javascript:void(0);`.
3. **Parâmetros Dinâmicos de Altura/Margem**: Inclusão de `height` (default 300) e `margin_lateral` (default 0) nos controles globais de exibição de galeria. Injetá-los dinamicamente nas tags raiz/imagem dos templates.
4. **Correções de Layout**: Ajuste fino de padding/margem nos submenus recursivos de `menus-horizontal-navbar`, legenda do `galleries-masonry` e tags horizontais coladas no `publisher-highlights.js`.

## DEC-038 - 2026-06-10 - accepted

Autocomplete de Páginas em Galerias, Ajuste de Menu e Preparação de Release (req-025 / BATCH-025). Decisões desta rodada:
1. **Buscador de Páginas Isolado**: Substituir o dropdown estático de páginas nas galerias pelo buscador AJAX autocomplete multilíngue clonado do Menus. Para evitar colisões em múltiplas linhas de curadoria simultâneas, os inputs e elementos de sugestões do autocomplete serão identificados utilizando o ID do item curado (ex: `manual_search_type_${it.id}`).
2. **Exclusão de Link Visual**: Configurar a classe `pointer-events-none cursor-default` nos links do widget se o tipo de link for `'nenhum'`, impedindo a interatividade e a indicação de ponteiro de link (hand cursor).
3. **Alinhamento do Submenu**: Ajustar o estilo CSS do submenu navbar horizontal para `display: flex !important; justify-content: space-between; align-items: center;` para manter a setinha do submenu alinhada horizontalmente no mesmo bloco.
4. **Miniaturas Ampliadas**: Ampliar a proporção de exibição da imagem curada no painel administrativo de `64x48px` para `200x140px` para melhor visualização.
5. **Workflow de Release**: Atualizar a data oficial da versão `2.8.0` para `2026-06-10` nos changelogs e incluir o descritivo de otimização de CSS (BATCH-023) e novas correções de links/layouts (BATCH-024/025) no workflow `.github/workflows/release-gestor.yml`.

## DEC-039 - 2026-06-10 - accepted

Ajuste do Modo de IA de Destaques e Preservação de Template Modificado (req-026 / BATCH-026). Decisões desta rodada:
1. **Modo de IA de Destaques e Galerias**: Atualização dos prompts do modo de IA de destaques (`publisher-highlights.md` em `pt-br` e `en`) para incluir regras claras de uso do bloco condicional opcional `<!-- no-item < -->` e variáveis adicionais, e de galerias (`galleries.md` em `pt-br` e `en`) para incluir as variáveis de link individual (`[[item#link-url]]`, `[[item#link-target]]`, `[[item#link-css-classes]]`) e a regra obrigatória de envelopar cada imagem curada em uma tag de âncora `<a>`.
2. **Identificador Suffix do Dropdown de Modelos**: No dropdown de seleção de modelo (`template_id`), se o registro atual em edição ou clonagem possuir código HTML ou CSS customizado no banco de dados, gerar e selecionar uma opção sufixada `[template_id]-modificado` (ex: `menus-horizontal-navbar-modificado`), com o rótulo `[Nome do Modelo] - (Modificado)`.
3. **Preservação de Código Customizado**: No carregamento inicial das telas de edição/clonagem, se o modelo selecionado for a versão `-modificado`, o script JavaScript do módulo correspondente (`menus.js`, `galleries.js`, `publisher-highlights.js`) não disparará a chamada AJAX de carregamento de modelo padrão (`loadTemplate()`), mantendo intacto o HTML/CSS carregado do banco.
4. **Alternância entre Modelo Padrão e Modificado e Extração de Variáveis**: O JavaScript armazenará em cache o HTML/CSS inicial recuperado do DOM no carregamento. Caso o usuário mude a seleção para a versão limpa do modelo, o template original será carregado via AJAX (`loadTemplate`). Se retornar para a versão `-modificado`, o HTML/CSS original em cache do registro será restaurado no editor. Em `publisher-highlights.js`, ao carregar/re-selecionar a variante `-modificado`, as variáveis `[[item#X]]` serão extraídas localmente do HTML do banco via expressão regular no cliente para manter o painel de mapeamento de variáveis populado.
5. **Consistência do Banco de Dados**: Antes do envio do formulário (submit) ou serialização (`fields_schema`), o sufixo `-modificado` será removido de qualquer referência a `template_id` no JavaScript para garantir que o identificador de modelo gravado no banco de dados permaneça limpo e compatível com as consultas existentes.
6. **Resolução de Framework CSS do Template**: Para evitar que o pré-visualizador (`live widget-preview`) falhe ao abrir um modelo `-modificado` devido à falta da variável de estilo global, o PHP selecionará o `framework_css` dos templates e o disponibilizará no dropdown como um atributo de dados `data-framework` nos elementos `<option>`. O JavaScript lerá este atributo no page load e em eventos `change` para inicializar a variável `gestor.html_editor.framework_css` de forma síncrona.

## DEC-040 - 2026-06-10 - accepted

Resolução de Framework CSS e Variáveis de Destaques de Modelo Modificado (req-027 / BATCH-027). Decisões corretivas desta rodada:
1. **Atributo `data-framework` nos Três Módulos**: Injetar síncronamente o `framework_css` (ex: `tailwindcss`) a partir da tabela de templates em todos os `<option>` (incluindo a opção `-modificado` gerada) de templates no PHP para `menus.php`, `galleries.php` e `publisher-highlights.php`.
2. **Sincronização de Runtime no JS**: No `ready` e no listener `change` do dropdown `#template_id` dos arquivos `menus.js`, `galleries.js` e `publisher-highlights.js`, ler o `data-framework` da opção ativa e atualizar a variável global `gestor.html_editor.framework_css`.
3. **Extração Client-side no Highlights**: Implementar a rotina `extractVariablesFromHtml` no `publisher-highlights.js` que processa o HTML com regex `/\[\[item#([a-zA-Z0-9_\-]+)\]\]/g` no ready e na re-seleção da opção `-modificado`, populando `availableItemVars` e disparando `renderItemVars()` / `syncEditorVariables()` síncronamente no cliente.

