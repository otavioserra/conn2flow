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


