---
title: "Módulo publisher-index"
description: "Índices de publicações com busca, ordenação, curadoria manual e paginação por AJAX."
section: reference
module: publisher-index
sources:
  - gestor/modulos/publisher-index/publisher-index.php
  - gestor/modulos/publisher-index/publisher-index.js
  - gestor/modulos/publisher-index/publisher-index.widget.php
  - gestor/modulos/publisher-index/publisher-index.widget.js
  - gestor/modulos/publisher-index/publisher-index.json
  - gestor/modulos/publisher-index/resources
  - gestor/db/migrations/20260611120000_create_publisher_index_table.php
  - gestor/db/migrations/20260707100000_add_project_to_widget_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: dd893291
---

# Módulo `publisher-index`

Cria índices públicos de um publicador, com primeira página renderizada no servidor e busca, ordenação e carregamento incremental pelo navegador. Cada registro guarda seu próprio HTML/CSS e configurações de seleção; o template escolhido serve de ponto de partida.

## Como usar

1. Acesse `publisher-index/` e `publisher-index/adicionar/`. Informe nome e publicador, selecione um template de alvo `publisher-index` e ajuste o HTML no editor.
2. Escolha seleção automática (`latest`) ou manual (`manual`). Na manual, busque publicações e arraste as etiquetas para ordenar. Trocar o publicador limpa a seleção manual.
3. Mapeie variáveis `item` para os campos da publicação; configure itens por página, busca, ordenação, botão carregar mais e métricas.
4. Salve e confira `publisher-index/editar/?id=<slug>`. A clonagem usa `publisher-index/clonar/?id=<slug>`. Esses caminhos são iguais nos JSONs pt-br/en, relativos à raiz do idioma. A listagem também oferece status e exclusão.
5. Insira em página ou layout:

```html
<!-- widgets#publisher-index->render({"grupo_slug":"noticias"}) < -->
<div>Mockup do índice</div>
<!-- widgets#publisher-index->render({"grupo_slug":"noticias"}) > -->
```

O widget procura o slug ativo no idioma atual. Registro ausente ou HTML vazio produz string vazia, sem fallback ao mockup. CSS, CSS compilado e HTML extra head entram pelo pipeline de recursos. A opção de template com sufixo `-modificado` preserva o HTML/CSS salvo; o sufixo é removido antes de persistir `template_id` no esquema.

## Referência técnica

### Operações e AJAX

O controlador configura `listar` e despacha `adicionar`, `editar`, `clonar`; `status` e `excluir` são ações da interface compartilhada. Nome e publicador são obrigatórios. Alterações incrementam versão, marcam `user_modified` e registram histórico e backups de conteúdo.

| Caso AJAX administrativo | Função |
|---|---|
| `template-load` | Lê template ativo do idioma e alvo correto; devolve HTML, CSS, framework e variáveis `item` |
| `publisher-load` | Devolve campos padrão `titulo`, `url`, `data` e campos do publicador; se houver vínculos no `template_map`, filtra os campos personalizados pelos vínculos |
| `publisher-pages-search` | Busca por nome/id, até 50 resultados; retorna também diagnóstico com SQL |
| `publisher-pages-fetch` | Hidrata nomes dos slugs selecionados |
| `widget-preview` | Renderiza HTML/CSS/esquema recebidos usando o renderizador inline |

O AJAX público é `publisher_index_render_ajax()`, encaminhado pelo roteador de widgets. O JS envia `ajax=sim`, `ajaxOpcao=publisher-index-load`, `ajaxRegistroId`, `ajaxWidgets` e `params[busca|pagina|ordenacao]` para a URL da própria página. A resposta contém `status`, `html`, `tem_mais`, `total`. Página menor que 1 vira 1; ordenação fora da lista permitida vira `date_desc`.

Não há `hooks`/`hooks.api` declarados no JSON nem operação específica semeada para o módulo em `ModulosOperacoesData.json`. O acesso ao CRUD passa pela interface administrativa; o widget tem seu fluxo público próprio.

### Dados

`publisher_index` possui chave numérica `id_publisher_index`; `id` e `publisher_id` são strings de até 100 caracteres (o segundo é slug, não chave numérica); `name` até 255; `fields_schema` JSON; `html`, `css_compiled`, `html_extra_head` MEDIUMTEXT e `css` TEXT. Completam a tabela `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. O índice único é `(id, language)`; há índices em publicador, plugin e idioma. `project` vem da migration posterior compartilhada.

```json
{
  "template_id":"publisher-index-lista",
  "rule":"latest",
  "selected_items":[],
  "order_by":"date_desc",
  "items_per_page":10,
  "show_search_input":true,
  "show_sorting_select":true,
  "show_load_more_btn":true,
  "show_metrics":true,
  "variable_mapping":{"resumo":"descricao"}
}
```

`items_per_page` ausente, vazio ou negativo assume 10; **zero não consulta publicações**. `count` ainda circula no CRUD com padrão 4, mas é vestigial: não limita este renderizador. Os quatro controles visuais têm padrão verdadeiro. No PHP, strings verdadeiras aceitas são `true`, `1`, `yes`, `on` (sem distinção de caixa).

A seleção automática junta `paginas` e `publisher_pages` por id e idioma, exige página ativa e `paginas.publisher_id` correspondente. Ordenações: `date_desc`, `date_asc` por data de modificação; `title_asc`, `title_desc` por nome. A busca consulta nome e JSON válido de `fields_values`, incluindo variantes de escapes Unicode legados.

A seleção manual restringe pelos slugs de `selected_items`, recompõe a ordem em PHP e pagina com `array_slice`; ignora a ordenação escolhida. Sua busca usa `mb_stripos` no título/campos, excluindo `page_id`, `url`, `data`. As duas modalidades podem diferir na comparação de acentos e nos curingas da busca SQL.

### Contrato do template

O primeiro bloco `<!-- item < -->` … `<!-- item > -->` repete por publicação; `no-item` é o estado vazio. `search-input`, `sort-select`, `load-more`, `metrics` são blocos condicionais. As variáveis globais são `grupo_slug`, `publisher_id`, `items_per_page`, `ordenacao`, os quatro `show_*`, `page_count` e `page_total`, com `[[...]]` ou `@[[...]]@`.

`[[item#campo]]` resolve por `variable_mapping`, ou pelo próprio nome se não houver mapeamento; ausente vira vazio. Cada item fornece `page_id`, `titulo`, `url`, `data` mais os campos de `fields_values` (pares `id`/`value`). Campos personalizados podem sobrescrever os quatro nomes padrão. Imagens relativas recebem a raiz do site; URLs HTTP(S), `//` e `data:` são preservadas.

O JS depende de `.conn2flow-publisher-index` com `data-grupo-slug` e `data-ordenacao`, e dos elementos `.publisher-index-items`, `.publisher-index-search`, `.publisher-index-sort`, `.publisher-index-load-more`. Métricas usam `[data-page-count]` e `[data-page-total]`; o estado vazio deve ter classe `.publisher-index-empty`. A busca tem debounce de 300 ms. Cada item deve ser um filho da lista para a contagem visual funcionar.

Templates embarcados: `publisher-index-lista`, `publisher-index-grid`, `publisher-index-timeline`, `publisher-index-agenda`, `publisher-index-grid-imagem`, `publisher-index-lista-imagem`.

## Limitações confirmadas

> [!CAUTION]
> As consultas públicas filtram status e idioma, mas não verificam permissões individuais nem agendamento das páginas. Não use a inclusão/exclusão de itens deste widget como controle de acesso. Os valores dos campos são inseridos no HTML sem escape contextual: templates e conteúdo devem ser confiáveis.

> [!WARNING]
> Na seleção manual sem busca, `total` é `count(selected_items)`, mesmo quando alguns slugs não existem ou estão inativos. As métricas podem superar os itens renderizados.

> [!WARNING]
> O cliente descarta pedidos enquanto outro está carregando, sem enfileirar a busca mais recente. Também incrementa a página antes da resposta e não a restaura em erro. O bloco carregar mais é removido do HTML inicial quando não há próxima página; o JS não recria esse botão depois.

## Veja também

- [Definições de publicação](publisher.md)
- [Menus](menus.md)
- [Biblioteca banco](../libraries/banco.md)
