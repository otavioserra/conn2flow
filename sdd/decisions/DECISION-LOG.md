# Decision Log

## DEC-001 - 2026-05-25 - accepted

Adotar SDD repo-wide no `conn2flow` como camada de controle para mudanças novas, sem tentar substituir a arquitetura vigente do repositório.

## DEC-002 - 2026-05-25 - accepted

Suportar os dois executores de IA no mesmo repositório:

- Claude Code via `CLAUDE.md` e `.claude/`
- GitHub Copilot via `.github/`

Os dois devem convergir para os mesmos artefatos em `sdd/`.

## DEC-003 - 2026-05-25 - accepted

Tratar `sdd/00-baseline-architecture.md` como referência primária do estado atual aprovado do legado. Mudanças futuras devem declarar o delta em relação a essa base, em vez de assumir que o legado pode ser descartado.

## DEC-004 - 2026-05-25 - accepted

Tratar `sdd/human-requests/` como intake humano não normativo, com resolução padrão por `CURRENT.md`, depois `README.md`, depois o arquivo `.md` mais recente.

## DEC-005 - 2026-05-25 - accepted

Definir como próximo intake funcional esperado o `Plano 1`, focado em tarefas e scripts de sincronização de projetos.

## DEC-006 - 2026-05-25 - accepted

Adotar fallbacks estruturados e mapeamento dinâmico na sincronização e atualização de projetos específicos:
1. Usar `path_tests` como fallback automático para `target` no `synchronize-project.sh`.
2. Derivar o `dockerPath` substituindo o prefixo local por `/var/www/sites/` se ausente no `updates-manager-database.sh`.
3. Isolar os dados dinâmicos do projeto de destino no `sync-core-to-project.sh` por meio de exclusões no rsync.

## DEC-007 - 2026-05-25 - accepted

Adotar arquitetura baseada em contrato de esquema (schema contract) por meio de um arquivo central compilado `db/data/schema-metadata.json`, gerado a partir do nó "tabela" dos descritores de módulos (ex: `admin-paginas.json`) e do descritor global dedicado `tables_config.json`. O script de deploy consumirá apenas esse contrato, eliminando códigos hardcoded.

## DEC-008 - 2026-05-25 - accepted

Padronizar a coluna identificadora de idioma de banco de dados e arquivos de recursos de desenvolvimento para o nome unificado `language` em todas as tabelas (substituindo o termo legado `linguagem_codigo`). Esta padronização exige também a atualização/migração de todos os módulos ativos do sistema que atualmente consomem a coluna antiga, garantindo a compatibilidade de runtime do Conn2Flow.

## DEC-009 - 2026-05-25 - accepted

Implementar uma estratégia de loteamento dinâmico com threshold de segurança de 30% em relação ao limite `max_allowed_packet` obtido em tempo de execução, dividindo payloads volumosos de HTML/CSS em chunks seguros para evitar estouro de buffer de rede.

## DEC-010 - 2026-05-25 - accepted

Utilizar a nomenclatura dedicada `data-hooks.php` para a execução sequencial em pipeline de ganchos locais nos módulos ou globais, garantindo isolamento em relação aos hooks de execução do sistema (`hooks.php`).

## DEC-011 - 2026-05-25 - accepted

Substituir deleções automáticas de registros órfãos por uma estratégia imperativa de deleção controlada via chave dedicada "deletar" declarada nos arquivos descritores de metadados de recursos de desenvolvimento, evitando exclusão acidental de dados de diferentes projetos de banco de dados.

## DEC-012 - 2026-05-25 - accepted

Padronizar todo o registro de mensagens de logs dos scripts de sincronização de banco e geração de dados para utilizar exclusivamente a biblioteca oficial do sistema `log.php` e sua função nativa `log_disco()`, eliminando implementações duplicadas ou logs soltos.

## DEC-013 - 2026-05-25 - accepted

Adotar a arquitetura de Widgets Envelopados (Wrappers) usando comentários HTML no formato `<!-- widgets#IDENTIFICADOR < --> ... <!-- widgets#IDENTIFICADOR > -->` para interceptar marcações estáticas e utilizá-las como templates de renderização dinâmica no core do Conn2Flow.

## DEC-014 - 2026-05-25 - accepted

Fica decidido que a vinculação entre os blocos de destaques (`publisher_highlights`) e os tipos de publicação (`publisher`), bem como a seleção manual de publicações no JSON de curadoria (`fields_schema->selected_items`), utilizará estritamente IDs textuais/alfanuméricas (ex: `'noticias'` para o tipo de publicação, e os slugs/`id` das publicações). Não serão utilizados IDs numéricos auto-incrementados de banco de dados (`id_publisher` ou `id_publisher_pages`) nessas relações para manter a consistência de runtime do Conn2Flow.

## DEC-015 - 2026-05-25 - accepted

A estrutura de template HTML e CSS do módulo `publisher-highlights` será armazenada diretamente em colunas específicas (`html` e `css`) da tabela `publisher_highlights`, sendo totalmente editável via painel administrativo usando o `html-editor.php`. Em tempo de execução, o renderizador dinâmico do widget lerá o template estritamente do banco de dados — sem fallback para o mockup do arquivo físico. Se as colunas estiverem vazias ou o registro não existir, o widget retornará vazio, impedindo que mockups estáticos de preview sejam indevidamente exibidos em produção.

## DEC-016 - 2026-05-25 - accepted

O desenvolvimento do módulo `publisher-highlights` no core do Conn2Flow espelhará a estrutura básica do módulo `publisher` original (como organização de pacotes, deploy automático via arquivo descritor de módulo, registro de templates locais na pasta de recursos por idioma, e a tela de edição CRUD). A tela de edição CRUD removerá o campo `path_prefix` e adicionará a vinculação dinâmica de placeholders `@[[item#...]]@` com as colunas reais do publicador selecionado.

## DEC-017 - 2026-05-26 - accepted

Estender o `fields_schema` do módulo `publisher-highlights` com a chave opcional `order_by` para configurar a ordenação da regra "Automática (últimas publicações)". Valores aceitos:

- `title_asc` -> `ORDER BY p.nome ASC`
- `title_desc` -> `ORDER BY p.nome DESC`
- `date_asc` -> `ORDER BY p.data_modificacao ASC`
- `date_desc` (padrão, retrocompatível) -> `ORDER BY p.data_modificacao DESC`

A ordenação é ignorada quando `rule = 'manual'`, pois nesse caso a ordem é definida pelo array `selected_items`. Esta extensão é registrada em [03-wrapped-widgets-and-publisher-highlights.md](../03-wrapped-widgets-and-publisher-highlights.md).

## DEC-018 - 2026-05-26 - accepted

Para a regra de alimentação "Manual" do `publisher-highlights`, substituir a entrada via `<textarea>` com slugs por linha por um dropdown Fomantic UI `.ui.multiple.search.selection.dropdown` populado dinamicamente por AJAX a partir das páginas ativas do publicador selecionado. A serialização final para o `fields_schema.selected_items` continua sendo a lista ordenada de slugs textuais, mantendo a compatibilidade com [DEC-014](#dec-014---2026-05-25---accepted) e com o widget renderer.

## DEC-019 - 2026-05-28 - accepted

Decidiu-se componentizar a lógica do dropdown manual do Fomantic UI para busca e seleção múltipla de itens em uma classe Javascript reutilizável `PublisherHighlightsCustomDropdown` no arquivo `gestor/assets/interface/jquery-custom-dropdown.js`, removendo a lógica inline do módulo e centralizando as responsabilidades de inicialização (`init`), reconfiguração (`reset`), busca incremental (`search`), hidratação (`hydrate`), atualização visual (`refresh`) e tradução contextual das mensagens de aviso baseada no idioma ativo do gestor.

## DEC-020 - 2026-05-28 - accepted

A integração do dropdown manual de itens curados no módulo `publisher-highlights` controlará a inicialização do componente visual sob demanda (apenas quando o modo manual estiver visível) para evitar conflitos de ciclo de vida e concorrência com o formulário. O fluxo de busca é desmembrado em duas fases manuais (busca incremental e hidratação na edição/clonagem), com eventos de escuta direta (change e click) para manter a perfeita sincronização com o preview de widget e com o `fields_schema.selected_items` persistido no banco de dados.

## DEC-022 - 2026-06-04 - accepted

Inicialização do módulo `menus` (req-015 / BATCH-015), a partir de uma cópia do módulo `publisher-highlights`. Decisões aprovadas pelo Engenheiro Chefe nesta rodada:

1. **Desacoplamento do publisher**: o módulo `menus` nasce sem nenhum acoplamento ao módulo `publisher`. A tabela `menus` (migração `20260701110000_create_menus_table.php`) é análoga a `publisher_highlights` porém **sem a coluna `publisher_id`**. O backend `menus.php` remove validação obrigatória, gravação e leitura de `publisher_id`, e remove as funções/AJAX acoplados ao publisher (`publisher-load`). Mantém o CRUD de `name`/`fields_schema`/`html`/`css` e o `template_id` dentro de `fields_schema` ([DEC-014](#dec-014---2026-05-25---accepted)/[DEC-015](#dec-015---2026-05-25---accepted) preservados).
2. **Itens do menu = páginas do site**: a curadoria manual de itens (`fields_schema.selected_items`, lista ordenada de slugs textuais) passa a referenciar diretamente páginas ativas da tabela `paginas` (sem `publisher_pages`). Os AJAX foram renomeados para `pages-search` e `pages-fetch`, varrendo `paginas` no idioma corrente. O widget (`menus.widget.php`) renderiza os itens expondo `[[item#label]]`, `[[item#url]]` e `[[item#slug]]`, sem regra `latest`/`order_by` nem herança de publisher.
3. **Templates visuais próprios**: os 6 templates de destaques (notícias/artigos/lives/notas/carrossel) foram **removidos** do clone e substituídos por 6 templates próprios de menu (`menus-horizontal-navbar`, `menus-vertical-sidebar`, `menus-footer-colunas`, `menus-dropdown`, `menus-breadcrumb`, `menus-mobile-hamburguer`), em `pt-br` e `en`, com framework `tailwindcss` e blocos `<!-- item -->` / `<!-- no-item -->`.
4. **Alvo próprio no html-editor**: o módulo usa `alvo = 'menus'` no `html_editor_componente`. Como esse alvo não é mapeado no `switch` do `html-editor.php`, ele cai no caso `default` (editor HTML/CSS limpo, sem controles de publisher), **sem necessidade de modificar o core compartilhado** nesta rodada. A prévia ao vivo continua disponível pela aba "Pré-Visualização" (AJAX `widget-preview`).

Esta é uma inicialização preliminar: em lotes futuros o autocomplete será expandido para montar a árvore de menus multi-nível. Registro do módulo em `gestor/db/data/ModulosData.json` (grupo `administracao-gestor`, ícone `sitemap`, "Menus do Site" / "Site Menus").

## DEC-021 - 2026-06-04 - accepted

Supera [DEC-018](#dec-018---2026-05-26---accepted), [DEC-019](#dec-019---2026-05-28---accepted) e [DEC-020](#dec-020---2026-05-28---accepted) (req-014). O dropdown múltiplo do Fomantic UI e o componente reutilizável `PublisherHighlightsCustomDropdown` (`gestor/assets/interface/jquery-custom-dropdown.js`) são removidos por sucessivos problemas de sincronização de estado, concorrência de callbacks `onAdd`/`onRemove` e perda da ordem cronológica de cliques. A curadoria manual passa a usar um componente de autocomplete construído do zero no próprio módulo: um `<input type="text">` de busca, lista flutuante de sugestões alimentada por AJAX (`publisher-pages-search`) e tags (Fomantic UI Labels) renderizadas em um contêiner. A reordenação manual das tags usa drag-and-drop via biblioteca `Sortable.js` (CDN). Permanece inalterado o contrato de serialização: `fields_schema.selected_items` continua sendo a lista ordenada de slugs textuais ([DEC-014](#dec-014---2026-05-25---accepted)), agora derivada diretamente da ordem física das tags. Cada adição, remoção ou reordenação atualiza `selected_items` e dispara `scheduleWidgetPreview`.

## DEC-023 - 2026-06-04 - accepted

Hierarquia multi-nível de menus e editor drag-and-drop estilo WordPress (req-016 / BATCH-016). Decisões aprovadas pelo Engenheiro Chefe nesta rodada:

1. **Contrato da árvore de itens (estende [DEC-022](#dec-022---2026-06-04---accepted))**: `fields_schema.selected_items` deixa de ser uma lista plana de slugs e passa a ser uma **árvore ordenada de objetos tipados**. Cada nó tem `id` (id de UI), `type`, `label`, `url`, `css_classes` e `children` (array recursivo); nós do tipo `pagina` adicionam `page_id` (slug da página). O contrato de IDs textuais de [DEC-014](#dec-014---2026-05-25---accepted) é preservado: o tipo `pagina` referencia o slug textual em `page_id`, sem IDs numéricos. **Retrocompatibilidade**: um `selected_items` no formato antigo (lista de strings) é interpretado como itens `pagina` de nível raiz.

2. **Cinco tipos de item**: `pagina` (vincula página física, link resolvido dinamicamente pelo `page_id`), `link-custom` (rótulo + URL livres), `cabecalho` (apenas rótulo, agrupa submenu), `link-action` (rótulo + URL + classes CSS para disparar scripts/modais no frontend) e `separador` (linha divisória sem rótulo/link).

3. **Filtro de tipo de página no autocomplete**: a busca de páginas (`pages-search`) passa a aceitar o parâmetro `tipo` (`pagina` | `sistema` | `ambos`, default `pagina`), filtrando `paginas.tipo`. A UI expõe rádios inline (Fomantic) que recarregam a busca ao mudar.

4. **Componente de drag-and-drop próprio (vanilla + Fomantic)**: o editor de árvore é construído do zero com **Pointer Events em JS vanilla** e visual **Fomantic-UI**, sem jQuery UI / nestedSortable / Sortable.js. O modelo interno de edição é uma **lista plana com `depth`** (padrão WordPress); o arraste é bidimensional — vertical define a ordem, o deslocamento horizontal define a profundidade (filho/irmão), com *clamp* em `depth(item acima)+1`. A dependência CDN do `Sortable.js` é removida do módulo `menus`. A conversão flat↔árvore ocorre nas fronteiras (hidratação, submit e preview).

5. **Renderização recursiva no widget**: além de `no-item` e `item` (folha), os templates suportam o delimitador `item-parent` (`<!-- item-parent < --> ... <!-- item-parent > -->`) com o placeholder especial `[[item#children]]`, onde a renderização recursiva dos filhos é injetada. A função `menus_render_level()` percorre a árvore; o tipo `pagina` resolve `label`/`url` do banco em tempo de render (links canônicos sempre atualizados). Templates sem `item-parent` permanecem funcionais (a árvore é achatada via DFS sobre o bloco `item`). Variáveis expostas: `[[item#label]]`, `[[item#url]]`, `[[item#slug]]`, `[[item#css_classes]]` e `[[item#children]]`.

`fields_schema` permanece coluna `json` — a árvore cabe sem migração de banco.

## DEC-024 - 2026-06-05 - accepted

Ajustes e correções do módulo `menus` (req-017 / BATCH-017), sem mudança de contrato de dados. Decisões desta rodada:

1. **Reintegração da aba "Variáveis"/"Simular" para o alvo `menus`**: o html-editor compartilhado passa a tratar `menus` como um alvo da família de variáveis `[[item#X]]` (mesma família do [DEC-022](#dec-022---2026-06-04---accepted)/[DEC-023](#dec-023---2026-06-04---accepted) e do `publisher-highlights`). No backend, `html-editor.php` ganha um `case 'menus'` (carrega o componente de simulação próprio e os controles de variáveis) e `html_editor_publisher_controls` monta o `template_map` a partir de `target_variables`. No frontend, o helper `alvoUsaItemVars()` unifica a detecção (`publisher-highlights` + `menus`) nos pontos que dependem da forma da variável, mantendo `publisher`/`paginas` inalterados. As variáveis dos menus são **fixas** (`label`, `url`, `slug`, `css_classes`, `children`), declaradas em `menus_variaveis_template()` — diferente do `publisher-highlights`, cujas variáveis são dinâmicas conforme o publicador vinculado.

2. **Componente de simulação próprio com árvore mockada**: criado `html-editor-menus-simulation` (pt-br/en) contendo uma **árvore JSON** de itens mockados (página, cabeçalho com filhos, link-custom, link-action e separador, em dois níveis), análogo aos buckets do `publisher-highlights` porém hierárquico. A simulação no `html-editor-interface.js` **espelha em JS a lógica recursiva** do `menus.widget.php` (blocos `item`/`item-parent`/`no-item`, `[[item#children]]` recursivo, montagem da base). Há um fallback embutido no JS (`MENUS_SIM_FALLBACK`) para que a simulação funcione mesmo antes de o componente ser registrado no banco pelo pipeline.

3. **Hover dos submenus por geometria CSS**: a quebra de `:hover` ao mover o mouse do item pai para o submenu é resolvida **eliminando os gaps** (`mt-1`/`ml-1`) nos templates `menus-dropdown` e `menus-horizontal-navbar` (pt-br/en) — o submenu cola no gatilho, que é seu ancestral `.group`, preservando a ponte de hover. Optou-se pela correção CSS (não JS) por ser mais simples e válida tanto no preview quanto no site público.

4. **Leitura robusta de dropdown Fomantic e placeholder do DnD**: o alternador de tipo de item lê o valor via `dropdown('get value')` (o `<select>` convertido pelo Fomantic não responde a `.val()`, o que fazia o tipo cair sempre em `pagina`); o placeholder do drag-and-drop passa a ter a altura de um item real e exibe "Solte o item aqui" / "Drop item here" entre setas ← / →.

Nenhuma mudança em contrato de dados, schema de banco ou no contrato de `selected_items` (mantém [DEC-023](#dec-023---2026-06-04---accepted)).

## DEC-025 - 2026-06-05 - accepted

Tipo de Item Publicador no Módulo de Menus e Correções (req-018 / BATCH-018). Decisões desta rodada:

1. **Tipo de Item `publicador`**: Adiciona-se o tipo de item `publicador` que funciona como nó gerador automático de sub-itens (filhos) correspondentes às publicações ativas do publicador selecionado. O nó armazena `publisher_id`, `count` (limite) e `order_by` (ordenamento). No widget (`menus.widget.php`), os filhos dinâmicos são pesquisados no banco de dados e populados na árvore em tempo de renderização. O editor de menus visual (menus.js) impede o aninhamento manual de sub-itens sob o nó do tipo `publicador` e exibe campos condicionais de seleção.
2. **Correções de UI do Menus**: 
   - Ao alterar o `template_id`, garantir que os editores CodeMirror do HTML Editor sejam atualizados corretamente mesmo quando focados.
   - Corrigir a alternância de exibição de campos dinâmicos no formulário de adição (ocultando busca de páginas e exibindo rótulo/URL/classes para links, cabeçalhos, etc.).
   - Integrar os placeholders `[[item#slug]]` e `[[item#css_classes]]` em todos os 12 templates padrões do módulo menus.
3. **Simulação do Publicador no HTML Editor**: O motor de simulação (`html-editor-interface.js`) gerará de forma randômica `$count` sub-itens do tipo `pagina` sob o nó `publicador` para simular o preview de forma idêntica.

## DEC-026 - 2026-06-05 - accepted

Criação do Módulo de Galerias de Imagens (req-018 / BATCH-018). Decisões desta rodada:

1. **Estrutura Base**: O módulo `galleries` será clonado de `publisher-highlights` e inteiramente desacoplado de publishers (sem coluna `publisher_id`). A persistência usará a tabela `galleries` (Phinx migration) e o módulo será registrado em `ModulosData.json` com o ícone `"images"`.
2. **Seleção de Imagens em Lote**: Em vez de seleção única, o gerenciador de arquivos modal (`admin-arquivos`) enviará os dados de imagem via `postMessage`. O listener em `galleries.js` interceptará e adicionará o arquivo à lista de curadoria visual, mantendo o modal **aberto** para que o usuário selecione múltiplas imagens seguidas em lote. O modal deve ser fechado apenas manualmente pelo usuário.
3. **Campos por Imagem**: Cada imagem na lista de curadoria exibirá seu thumbnail, nome do arquivo, um campo editável para **Legenda** (`legenda`) e um botão de exclusão. A ordenação será feita por drag-and-drop usando Sortable.js. O JSON serializado em `fields_schema` conterá os metadados (`id`, `caminho`, `imgSrc`, `nome`, `legenda`).
4. **Widget Renderizador**: O widget `galleries.widget.php` lerá os metadados do banco e renderizará as imagens sequencialmente substituindo `[[item#img-src]]`, `[[item#caminho]]`, `[[item#nome]]` e `[[item#legenda]]`. templates de visualização (grid, carousel, masonry, slider) serão providos em `resources/templates/`. Simulação e aba de variáveis estarão integradas no editor HTML.

## DEC-027 - 2026-06-05 - accepted

Contrato de Target em Link-Custom e Variável `[[item#target]]` (req-019 / BATCH-019). Decisões desta rodada:
1. **Atributo Target**: Adicionar campo de seleção `target` (`_self` ou `_blank`) no formulário e no painel de edição do tipo `link-custom` em `menus.js`.
2. **Variável de Template**: Expor a variável `[[item#target]]` no renderizador (`menus.widget.php`) e simulação, permitindo que os templates de menu definam `target="[[item#target]]"` nas tags de link `<a>`.

## DEC-028 - 2026-06-05 - accepted

Introdução do Bloco `item-separator` em Templates de Menus (req-019 / BATCH-019). Decisões desta rodada:
1. **Delimitador de Separador**: Criar suporte para o bloco de template `<!-- item-separator < --> ... <!-- item-separator > -->` no renderizador `menus.widget.php`.
2. **Textos em Separadores**: Permitir que separadores opcionalmente possuam rótulo (`label`) configurado, renderizando-os como divisores visuais estilizados contendo texto ou apenas linhas físicas.

## DEC-029 - 2026-06-05 - accepted

Controles de Galeria e Resolução de Imagem (req-019 / BATCH-019). Decisões desta rodada:
1. **Resolução de Imagem Pública**: No widget público (`galleries.widget.php`), priorizar o campo `caminho` (caminho original) para renderização da imagem, utilizando `imgSrc` apenas no painel administrativo para carregamento rápido das miniaturas.
2. **Campos de Controle**: Adicionar no schema e no formulário de galerias opções de exibir setas (`show_arrows`), exibir pontinhos (`show_dots`), autoplay (`autoplay`), autoplay speed (`autoplay_speed`) e loop (`loop`).
3. **Template de Controles**: Suportar os blocos `controls-arrows`, `controls-dots` e `dot-item` (este último repetido para cada imagem no widget) para emitir opcionalmente a marcação dos controles conforme a configuração selecionada.

## DEC-030 - 2026-06-05 - accepted

Comportamento Dinâmico em JS Público de Widgets (req-019 / BATCH-019). Decisões desta rodada:
1. **Arquitetura de JS de Widget**: Adicionar suporte a interatividade pública de widgets incluindo scripts dinâmicos de comportamento em tempo de renderização (`menus.widget.js` e `galleries.widget.js`).
2. **Lógica de Slider e Dropdown**: Os scripts tratarão a abertura de submenus no dropdown por eventos de hover do JS, o toggle do menu mobile hambúrguer e a movimentação suave e controle (setas/dots/autoplay) do carrossel/slider de galerias via jQuery/CSS, garantindo retrocompatibilidade caso recursos específicos do Tailwind CSS v3 falhem no site final.

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

