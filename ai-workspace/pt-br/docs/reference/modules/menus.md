---
title: "Módulo menus"
description: "Menus de navegação curados no painel e renderizados por widget em layouts e páginas, com árvore de itens tipados e variações por perfil de visitante."
section: reference
order: 30
module: menus
sources:
  - gestor/modulos/menus/menus.php
  - gestor/modulos/menus/menus.widget.php
  - gestor/modulos/menus/menus.widget.js
  - gestor/modulos/menus/menus.json
  - gestor/db/migrations/20260701110000_create_menus_table.php
verified_at: 5b4348ab
---

# Módulo `menus`

O módulo `menus` cria menus de navegação (cabeçalho, rodapé, barra lateral, trilha, dropdown, mobile) **sem escrever PHP**. O operador monta uma árvore de itens no painel, escolhe um template visual e insere o menu em qualquer layout ou página com um marcador de widget. O HTML final é montado no servidor a cada requisição, sempre com os links canônicos atuais das páginas.

## Como usar

1. **Criar o menu** em *Menus → Adicionar* (`/menus/adicionar/`). Informe o nome e escolha um template; os modelos disponíveis são os registros de `templates` com `target = 'menus'`.
2. **Montar a árvore** na edição (`/menus/editar/?id=...`): adicione itens, arraste para reordenar e aninhar, e busque páginas pelo nome. A pré-visualização (aba de preview) chama a rota AJAX `widget-preview`, que usa o mesmo renderizador do site.
3. **Inserir no layout ou página** com o wrapper do widget. O `grupo_slug` é o `id` do menu:

```html
<!-- widgets#menus->render({"grupo_slug": "menu-principal"}) < -->
<nav>…mockup estático, só para quem edita o layout…</nav>
<!-- widgets#menus->render({"grupo_slug": "menu-principal"}) > -->
```

> [!NOTE]
> O conteúdo entre as duas marcas é só um **mockup** para o designer. Em runtime o widget sempre o substitui pelo template salvo **no banco**. Se o menu não existir, estiver inativo ou tiver o template vazio, o widget devolve string vazia, e o mockup também não aparece.

## Tipos de item

| `type` | Comportamento |
|---|---|
| `pagina` | Aponta para uma página pelo `page_id` (slug). URL e rótulo vêm do banco no idioma atual; um `label` preenchido no item prevalece. |
| `link-custom` | Rótulo e URL livres; aceita `target` (`_self`/`_blank`). |
| `link-action` | Como o `link-custom`, pensado para ações (logout, modais) via `css_classes`. |
| `cabecalho` | Agrupador com filhos; sem URL cai para `#`. |
| `publicador` | Expande em runtime as publicações de um publisher (`publisher_id`), limitadas por `count` (padrão 5) e ordenadas por `order_by` (`date_desc`, `date_asc`, `title_asc`, `title_desc`). Substitui filhos manuais. |
| `separador` | Divisor visual com rótulo opcional. |

Uma árvore antiga salva como lista de slugs (formato inicial) continua funcionando: cada string vira um item `pagina`.

## Visibilidade por visitante

`fields_schema.availability` define se o menu é o mesmo para todos (`todos`) ou muda conforme quem visita (`condicional`). No modo condicional, as `conditions` são avaliadas **em ordem, e a primeira que casa vence**:

| Condição | Casa quando |
|---|---|
| `publico` | o visitante **não** está logado |
| `logado` | há usuário autenticado com token e permissão válidos |
| `perfil_usuario` | o usuário logado pertence a um dos `profile_ids` (aceita o id puro ou o hash `sha256` dele) |

Cada condição tem um `slug`, e seus itens ficam em `fields_schema.menus[<slug>]`. Se nenhuma casar, vale `menus.visible_to_all`. O template pode ter um HTML diferente por condição, usando os blocos `<!-- menu-conditional-<slug> < -->`; sem eles, usa `<!-- menu-visible < -->` ou o template inteiro.

> [!CAUTION]
> Visibilidade de menu é **apresentação, não controle de acesso**. Esconder um link não protege a página de destino; a permissão precisa estar na própria página ou no módulo.

## Contrato do template

Um template de menu é HTML com blocos-modelo que o renderizador repete:

| Bloco | Uso |
|---|---|
| `<!-- item < -->` … `<!-- item > -->` | Item sem filhos |
| `<!-- item-parent < -->` … `<!-- item-parent > -->` | Item com filhos; os filhos entram em `[[item#children]]`, recursivamente |
| `<!-- item-separator < -->` … `<!-- item-separator > -->` | Separador |
| `<!-- no-item < -->` … `<!-- no-item > -->` | Exibido quando o menu não tem itens |

Variáveis por item: `[[item#label]]`, `[[item#url]]`, `[[item#target]]`, `[[item#slug]]`, `[[item#css_classes]]` e `[[item#children]]`. A forma com arrobas do banco (`@[[item#url]]@`) também é aceita, e as arrobas são consumidas na troca.

Regras que valem saber:
- **Sem `item-parent`**, a árvore é achatada: os filhos saem no mesmo nível, com o bloco `item`. Nenhum item se perde.
- **Sem `item-separator`**, o separador é desenhado com o bloco `item`, vazio.
- O HTML renderizado entra no lugar do **primeiro** bloco `item` (ou `item-parent`); os demais blocos-modelo são removidos.
- CSS, CSS compilado e `html_extra_head` do menu são injetados no `<head>` uma vez só, mesmo com o menu repetido na página (`gestor_pagina_recursos_incluir()`).

Templates que acompanham o core: `menus-horizontal-navbar`, `menus-vertical-sidebar`, `menus-footer-colunas`, `menus-dropdown`, `menus-breadcrumb` e `menus-mobile-hamburguer`.

### Comportamento no navegador

Quando o widget aparece numa página, `menus.widget.js` é incluído automaticamente. Por delegação de eventos, ele:
- alterna a lista `.menu-mobile-list` ao clicar em `.menu-mobile-btn` (atualizando `aria-expanded`);
- abre e fecha `.group-hover-sub-menu` no hover, como reserva para quando as variantes de grupo do Tailwind não chegam ao CSS final.

## Dados

Tabela `menus`: `id` (slug), `name`, `language`, `fields_schema` (JSON), `html`, `css`, `css_compiled`, `html_extra_head`, `status`, `versao`, `plugin`, `user_modified` e `system_updated`, mais as datas.

```json
{
  "template_id": "menus-vertical-sidebar",
  "availability": "todos",
  "conditions": [],
  "menus": {
    "visible_to_all": [
      { "type": "pagina", "page_id": "comece-a-construir", "label": "", "children": [] },
      { "type": "cabecalho", "label": "Referência", "children": [
        { "type": "pagina", "page_id": "docs-reference-libraries-modelo", "children": [] }
      ]}
    ]
  }
}
```

Por ser configuração do site, um menu pode ser versionado como **recurso** de projeto: declare a tabela `menus` com `sync_resources` no `resources/project_tables_config.json` do projeto. Os registros ficam em `resources/<idioma>/menus.json` e o HTML em `resources/<idioma>/menus/<id>/<id>.html`. É o formato que o `c2f docs:build` gera para a barra lateral da documentação.

## Veja também

- [Biblioteca modelo.php](../libraries/modelo.md): as funções de string sobre as quais os templates do Gestor são montados.
- [Como escrever documentação](../../guides/documentation.md)
