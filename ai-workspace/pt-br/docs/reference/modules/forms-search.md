---
title: "Módulo forms-search"
description: "Formulário GET, sugestões de páginas e registro de buscas."
section: reference
module: forms-search
sources:
  - gestor/modulos/forms-search/forms-search.php
  - gestor/modulos/forms-search/forms-search.js
  - gestor/modulos/forms-search/forms-search.widget.php
  - gestor/modulos/forms-search/forms-search.widget.js
  - gestor/modulos/forms-search/forms-search.json
  - gestor/modulos/forms-search/resources
  - gestor/db/migrations/20260715120000_create_forms_search_and_pages_index_tables.php
verified_at: 7bf08fe1
---

# Módulo `forms-search`

Cria formulários de busca para páginas públicas. O envio navega por GET até a página de resultados; o widget também oferece sugestões AJAX enquanto a pessoa digita. O registro dos termos pesquisados ocorre pelo índice de páginas.

## Como usar

Abra `forms-search/` e `forms-search/adicionar/`; escolha um template, adicione campos e configure `form_action`, normalmente `pages-index-search/`. Confira em `forms-search/view/`, edite em `forms-search/editar/` ou clone em `forms-search/clonar/`. Coloque o widget em uma página:

```html
<!-- widgets#forms-search->render({"form_id":"busca"}) < -->
<div>Mockup</div>
<!-- widgets#forms-search->render({"form_id":"busca"}) > -->
```

As rotas são iguais nos dois idiomas. O destino padrão é uma página que contém o widget `pages-index`.

## Referência técnica

O controlador oferece `listar`, `visualizar`, `adicionar`, `editar`, `clonar`; AJAX administrativo `template-load` e `widget-preview`. Status e exclusão são da interface compartilhada. `forms_search` contém `id_forms_search` numérico, `id` até 100, `name` até 255, `description`, `template_id`, JSON `fields_schema`, `module`, `plugin`, `project`, `language`, `status`, `version`, HTML/CSS e recursos compilados, datas e flags. `(id, language)` é único. A tabela `forms_search_submissions` registra `form_id`, `name`, `id`, JSON `fields_values`, idioma, status, versão e datas; não armazena envios deste formulário como o módulo `forms`.

O schema aceita `form_action` e `fields` com `type`, `name`, `label`, `placeholder`, `options` e `required`. O widget procura um registro ativo no idioma atual e usa seu HTML ou um template ativo de alvo `forms-search`; se faltar CSS pré-compilado próprio, herda o do template. Repete `<!-- item < -->`, seleciona blocos `type-*` e substitui as variáveis `item#*`, `form_id` e `form_action`. Força o primeiro `<form>` a `method="get"`, garante `name="search"` e contêiner `.forms-search-results`. Destino vazio vira `pages-index-search/`; URLs HTTP(S) e relativas ao protocolo passam intactas. Templates: `forms-search-contato-basico`, `forms-search-newsletter-newsletter`, `forms-search-registro-usuario`, `forms-search-pesquisa-satisfacao` e `forms-search-suporte-tecnico`. Sem hooks ou `hooks.api` declarados no JSON.

O AJAX público `forms-search-autocomplete` é despachado por `ajaxWidgets` para `forms_search_render_ajax()`. Exige ao menos três caracteres, consulta `paginas` ativas do idioma, `tipo='pagina'`, `sem_permissao=1`, com `LIKE` em título ou HTML; ordena pelo título e pagina 30 resultados. Devolve `title`, resumo de até 180 caracteres, `url`, `tem_mais` e `pagina`. O JS usa debounce de 300 ms, cache, AbortController, seleção por teclado e opção de carregar mais. O envio do formulário navega para `?search=...`; ele não usa o processador POST nem o CAPTCHA de `forms`.

`forms_search_registrar_busca()` é chamado por `pages-index`: grava o termo em `forms_search_submissions`, com `form_id` igual ao slug do índice e `fields_values` como lista contendo `{ "id":"search", "value":"..." }`. Sugestões AJAX sozinhas não chamam esse registrador.

## Limitações confirmadas

> [!CAUTION]
> A consulta de sugestões não considera a janela de publicação das páginas. `%` e `_` digitados continuam curingas de `LIKE`; a busca em HTML encontra texto de marcação e atributos. O parâmetro de ação permite URL absoluta, inclusive externa, para o GET.

> [!WARNING]
> A migração de `forms_search` não criou `css_precompiled`; o widget verifica a existência dessa coluna e recorre ao template. O logger não tem deduplicação por termo ou requisição; a renderização inicial do índice e o AJAX podem registrar a mesma busca.

## Veja também

- [Índice de páginas](pages-index.md)
- [Formulários](forms.md)
