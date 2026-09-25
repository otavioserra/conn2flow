---
title: "Módulo pages-index"
description: "Busca e listagem paginada das páginas públicas, com resumo, destaque de termos e navegação pelo teclado."
section: reference
module: pages-index
sources:
  - gestor/modulos/pages-index/pages-index.php
  - gestor/modulos/pages-index/pages-index.js
  - gestor/modulos/pages-index/pages-index.widget.php
  - gestor/modulos/pages-index/pages-index.widget.js
  - gestor/modulos/pages-index/pages-index.json
  - gestor/modulos/pages-index/resources
  - gestor/db/migrations/20260715120000_create_forms_search_and_pages_index_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: 837c383f
---

# Módulo `pages-index`

Cria índices pesquisáveis das páginas públicas do site. O widget lê `paginas` diretamente e oferece título, resumo, URL e data, sem depender de publicador ou campos personalizados.

## Como usar

1. Abra `pages-index/` e `pages-index/adicionar/`; informe nome, escolha template de alvo `pages-index` e ajuste HTML/CSS.
2. Configure itens por página, ordenação e exibição de busca, seletor de ordem, carregar mais e métricas. Mapeie variáveis do template para `title`, `summary`, `url`, `date`.
3. Salve. Edição e clonagem usam `pages-index/editar/?id=<slug>` e `pages-index/clonar/?id=<slug>`. As rotas são iguais nos dois idiomas, relativas à raiz do idioma.
4. Insira o widget ou use a página fornecida `pages-index-search/?search=termo`, cujo HTML chama o grupo `padrao`.

```html
<!-- widgets#pages-index->render({"grupo_slug":"padrao"}) < -->
<div>Mockup</div>
<!-- widgets#pages-index->render({"grupo_slug":"padrao"}) > -->
```

O registro deve estar ativo no idioma atual e ter HTML preenchido; caso contrário, retorna vazio sem usar o mockup. A opção de template `-modificado` conserva HTML/CSS salvos; seu sufixo é removido antes da gravação.

## Referência técnica

### Controlador e dados

O controlador configura `listar` e despacha `adicionar`, `editar`, `clonar`; status e exclusão pertencem à interface compartilhada. O único campo obrigatório verificado na criação é `name`. Edição registra histórico, backups de conteúdo, versão e `user_modified`.

AJAX administrativo: `template-load` lê template ativo do idioma/alvo e devolve HTML/CSS/framework/variáveis; `campos-load` devolve os quatro campos fixos; `widget-preview` renderiza os dados enviados. Não há `hooks`/`hooks.api` no JSON nem operação especial na semente `ModulosOperacoesData.json`.

A tabela `pages_index` contém `id_pages_index` (chave numérica), `id` (até 100), `name` (até 255), `fields_schema` JSON, `html`/`css_compiled`/`html_extra_head` MEDIUMTEXT, `css` TEXT; além de `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. Há índice único em `(id, language)` e índices em plugin/idioma. Não tem `publisher_id`.

```json
{
  "template_id":"pages-index-lista",
  "items_per_page":10,
  "order_by":"date_desc",
  "show_search_input":true,
  "show_sorting_select":true,
  "show_load_more_btn":true,
  "show_metrics":true,
  "variable_mapping":{"titulo":"title","resumo":"summary"}
}
```

Os quatro controles têm padrão verdadeiro. `items_per_page` ausente, vazio ou negativo vira 10; zero não consulta páginas. `rule`, `count` e `selected_items` ainda aparecem no JS administrativo herdado, mas não controlam o renderizador deste módulo.

### Consulta e renderização

Filtro: `status='A'`, idioma atual, `tipo='pagina'`, `sem_permissao=1`. Busca com `LIKE` em `nome` ou `html`; ordenação por nome (`title_asc|title_desc`) ou modificação (`date_asc|date_desc`, padrão descendente). O filtro não exclui a própria página de índice.

Resumo: remove tags do HTML, normaliza espaços, decodifica entidades e corta em 200 caracteres mais reticências (ou bytes sem mbstring). Não renderiza widgets antes de resumir. Campos de item: `title`, `summary`, `url` e `date`; mapeamento inexistente usa o nome da variável, valor inexistente vira vazio.

O primeiro bloco `item` é repetido e `no-item` atende o estado vazio. Blocos condicionais: `search-input`, `sort-select`, `load-more`, `metrics`, delimitados como `<!-- item < -->` / `<!-- item > -->`. Variáveis globais: `grupo_slug`, `items_per_page`, `ordenacao`, `search`, os quatro `show_*`, `page_count`, `page_total`. Marcadores aceitam `[[...]]` e `@[[...]]@`. `search` recebe escape HTML; os campos de item não.

Templates: `pages-index-lista`, `pages-index-grid`, `pages-index-timeline`, `pages-index-agenda`, `pages-index-grid-imagem`, `pages-index-lista-imagem`. Os nomes dos modelos com imagem não acrescentam um campo de imagem à consulta: o contrato continua com quatro campos.

### Navegador e AJAX público

`pages_index_render_ajax()` recebe `ajaxRegistroId`, `params[busca]`, `params[pagina]`, `params[ordenacao]` via roteador de widgets; devolve `status=Ok`, `html`, `tem_mais`, `total`. Página abaixo de 1 vira 1 e ordem desconhecida vira `date_desc`. O JS envia `ajaxOpcao=pages-index-load` e `ajaxWidgets` à URL corrente.

O contêiner precisa de `.conn2flow-pages-index`, `data-grupo-slug`, `data-ordenacao`; controles usam `.pages-index-items|search|sort|load-more`. Estado vazio: `.pages-index-empty`. Métricas: `[data-page-count]`, `[data-page-total]`. Cada resultado deve ser um filho da lista.

A busca tem debounce de 300 ms, cancelamento com AbortController e token contra respostas antigas. O cache em memória usa grupo, termo em minúsculas, página e ordem. `history.replaceState` sincroniza `search`; destaque usa nós de texto e `mark`, preservando tags. Setas selecionam resultados e Enter abre o link. A URL com `search` provoca nova consulta após a renderização inicial.

Buscas não vazias são encaminhadas ao helper `forms_search_registrar_busca`, quando disponível: na carga inicial e em AJAX na página 1. Paginação posterior não registra; cache do navegador também evita nova chamada.

## Limitações confirmadas

> [!CAUTION]
> O filtro público não verifica `data_publicacao_inicio/fim`. Uma página ativa e pública agendada pode entrar no índice antes de sua janela. O resumo decodifica entidades depois de remover tags e é inserido sem novo escape: não considere esse processamento um sanitizador HTML.

> [!WARNING]
> `%/_` no termo continuam curingas de LIKE. A busca em HTML pode casar nomes de tags ou atributos que não aparecem no resumo. O carregamento inicial com `search` e sua confirmação AJAX podem gerar duas chamadas ao registrador de busca.

> [!WARNING]
> O botão carregar mais é removido do HTML inicial sem próxima página e o JS não o recria. Em erro de rede, o cliente não apresenta mensagem nem restaura a página incrementada; o próximo clique pode pular resultados.

## Veja também

- [Índice de publicações](publisher-index.md)
- [Menus](menus.md)
- [Contrato de documentação](../../guides/documentation.md)
