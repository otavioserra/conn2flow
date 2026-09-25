---
title: "Módulo publisher-highlights"
description: "Blocos de destaques de publicações com curadoria automática ou manual, templates e mapeamento de campos."
section: reference
module: publisher-highlights
sources:
  - gestor/modulos/publisher-highlights/publisher-highlights.php
  - gestor/modulos/publisher-highlights/publisher-highlights.js
  - gestor/modulos/publisher-highlights/publisher-highlights.widget.php
  - gestor/modulos/publisher-highlights/publisher-highlights.json
  - gestor/modulos/publisher-highlights/resources
  - gestor/db/migrations/20260701100000_create_publisher_highlights_table.php
  - gestor/db/migrations/20260611110000_add_css_compiled_and_html_extra_head_to_widgets_tables.php
  - gestor/db/migrations/20260707100000_add_project_to_widget_tables.php
  - gestor/db/data/ModulosOperacoesData.json
verified_at: dd893291
---

# Módulo `publisher-highlights`

Monta blocos de destaques de um publicador, limitados por quantidade, usando seleção automática ou uma lista de publicações curada pelo operador. Cada registro guarda o HTML/CSS que o widget renderiza no site.

## Como usar

1. Entre em `publisher-highlights/` e abra `publisher-highlights/adicionar/`. Informe nome e publicador; selecione template de alvo `publisher-highlights`.
2. Configure regra automática (`latest`), quantidade e ordenação, ou regra manual (`manual`), com busca de publicações e etiquetas reordenáveis por arraste. Trocar o publicador limpa os itens selecionados.
3. Vincule variáveis `[[item#...]]` aos campos do publicador, ajuste HTML/CSS e confira a prévia com dados reais.
4. Salve. Edição e clonagem usam `publisher-highlights/editar/?id=<slug>` e `publisher-highlights/clonar/?id=<slug>`. Os caminhos são iguais nos recursos dos dois idiomas, relativos à raiz do idioma. A listagem oferece ativar/desativar e excluir.
5. Insira o bloco na página ou layout:

```html
<!-- widgets#publisher-highlights->render({"grupo_slug":"destaques"}) < -->
<div>Mockup de destaques</div>
<!-- widgets#publisher-highlights->render({"grupo_slug":"destaques"}) > -->
```

O registro precisa estar ativo, no idioma corrente e ter HTML preenchido. Caso contrário, o retorno é vazio; o mockup não é fallback. Na edição, a opção de template `-modificado` mantém o código salvo sem carregar novamente o original; o sufixo não é persistido no esquema.

## Referência técnica

### Controlador, permissões e AJAX

O fluxo normal configura `listar` e despacha `adicionar`, `editar`, `clonar`, cercados pela interface compartilhada. `status` e `excluir` são ações configuradas para a interface. Nome e publicador são obrigatórios. A edição registra histórico e backup dos campos de conteúdo alterados, incrementa versão e marca `user_modified=1`.

| Caso AJAX | Contrato |
|---|---|
| `template-load` | Recebe `params.template_id`; lê template ativo no idioma e alvo, retorna `modelo`, HTML/CSS, framework e variáveis `item` |
| `publisher-load` | Recebe `params.publisher_id`; retorna campos padrão e personalizados, filtrados pelos vínculos do `template_map` quando existem |
| `publisher-pages-search` | Recebe publicador e `q`; busca nome/id, até 50 resultados, com diagnóstico SQL no retorno |
| `publisher-pages-fetch` | Recebe publicador e `ids`; devolve nomes dos registros ativos selecionados |
| `widget-preview` | Renderiza HTML/CSS e esquema recebidos sem exigir gravação do registro |

O JSON não declara `hooks`/`hooks.api`; a semente `ModulosOperacoesData.json` não contém operação especial para este módulo. O CRUD usa a interface administrativa. Não há função pública `render_ajax` nem arquivo `.widget.js` neste módulo: a seleção é renderizada no servidor.

### Tabela e esquema

`publisher_highlights` tem chave `id_publisher_highlights`, `id` e `publisher_id` até 100 caracteres, `name` até 255, `fields_schema` JSON, `html`/`css_compiled`/`html_extra_head` MEDIUMTEXT e `css` TEXT. Também guarda `id_usuarios`, `plugin`, `project`, `language`, `status`, `versao`, `data_criacao`, `data_modificacao`, `user_modified`, `system_updated`. O índice único é `(id, language)`; publicador, plugin e idioma têm índices. `publisher_id` é o slug textual. A migration de compatibilidade acrescenta CSS compilado/head quando faltam; `project` vem de migration posterior compartilhada.

```json
{
  "template_id":"publisher-highlights-noticias-grid-cards",
  "rule":"latest",
  "count":4,
  "order_by":"date_desc",
  "selected_items":[],
  "variable_mapping":{"imagem":"imagem_destaque","resumo":"descricao"}
}
```

Padrões do renderizador: `rule=latest`, `count=4`, `order_by=date_desc`; quantidade menor que 1 vira 1. `template_id` vive no JSON, sem coluna própria. Fora do modo manual, o JS grava uma cópia com `selected_items=[]`.

A consulta usa `paginas LEFT JOIN publisher_pages` por id e idioma, filtra `paginas.publisher_id`, página ativa e idioma corrente. Portanto, pode incluir uma página sem registro correspondente em `publisher_pages`. A regra automática ordena por nome (`title_asc`, `title_desc`) ou data de modificação (`date_asc`, `date_desc`) e limita no SQL. A manual consulta os slugs, recompõe a ordem de `selected_items` e limita a `count` em PHP; não aplica `order_by`.

### Template e variáveis

O primeiro bloco `<!-- item < -->` … `<!-- item > -->` é repetido. Sem resultados, o primeiro `no-item` permanece e `item` é removido; sem `no-item`, o widget inteiro retorna vazio. Havendo resultados sem bloco `item`, devolve a estrutura do template sem repetição.

`[[item#nome]]` usa `variable_mapping[nome]`, ou `nome` diretamente; campo ausente vira vazio. Campos padrão: `page_id`, `titulo`, `url`, `data` (modificação formatada). Os pares `id`/`value` de `publisher_pages.fields_values` são mesclados e podem sobrescrever esses nomes. Campos declarados como `image` recebem a raiz do site quando relativos; HTTP(S), `//`, `data:` e o marcador explícito da raiz são tratados pelo helper de URL.

Templates fornecidos: `publisher-highlights-noticias-lista-simples`, `publisher-highlights-noticias-grid-cards`, `publisher-highlights-artigos-editorial`, `publisher-highlights-lives-video-destaque`, `publisher-highlights-notas-mosaico`, `publisher-highlights-principal-carousel`. O carousel embarcado usa rolagem horizontal e CSS scroll-snap; este módulo não inclui um controlador JS de slides.

## Defeitos e limites observados

> [!WARNING]
> No ramo com publicações e bloco `item`, a chamada final de `publisher_highlights_widget_montar_saida()` passa somente HTML e CSS. `css_compiled` e `html_extra_head` são perdidos nesse ramo, embora sejam encaminhados nos ramos de estado vazio e template sem `item`.

> [!WARNING]
> A substituição de `item` usa `preg_replace` com o conteúdo como replacement: sequências como `$1` podem ser interpretadas como referências de captura. A regex dos campos consome `[[item#...]]`, mas não as arrobas externas; HTML que ainda tenha `@[[item#...]]@` pode produzir arrobas residuais. O carregamento de template pelo CRUD remove o cerco antes da edição.

> [!CAUTION]
> O widget não verifica permissões individuais nem agendamento das páginas e insere valores sem escape HTML contextual. Use conteúdo confiável e controle o acesso na própria página; curadoria e status do bloco não protegem os dados das publicações.

## Veja também

- [Definições de publicação](publisher.md)
- [Índice paginado](publisher-index.md)
- [Menus](menus.md)
