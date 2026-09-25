---
title: "Módulo admin-paginas"
description: "Autoria de páginas e telas de sistema, rotas, permissões, SEO, agendamento e histórico."
section: reference
module: admin-paginas
sources:
  - gestor/modulos/admin-paginas/admin-paginas.php
  - gestor/modulos/admin-paginas/admin-paginas.js
  - gestor/modulos/admin-paginas/admin-paginas.json
  - gestor/modulos/admin-paginas/resources
  - gestor/gestor.php
  - gestor/db/data/ModulosOperacoesData.json
  - gestor/db/migrations/20250723165530_create_paginas_table.php
  - gestor/db/migrations/20250814130000_alter_paginas_add_updated_flags.php
  - gestor/db/migrations/20250827110010_alter_paginas_add_framework_css.php
  - gestor/db/migrations/20250903160000_alter_recursos_add_plugin_id.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20250918130000_add_html_extra_head_to_tables.php
  - gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php
  - gestor/db/migrations/20260127163000_add_publisher_id_to_paginas_table.php
  - gestor/db/migrations/20260712100000_add_publish_window_to_paginas.php
  - gestor/db/migrations/20260813120000_add_seo_metadata_to_paginas.php
  - gestor/db/migrations/20260814100000_add_meta_seo_to_paginas.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
  - gestor/db/migrations/20250723165531_create_paginas_301_table.php
verified_at: 837c383f
---

# Módulo `admin-paginas`

Administra os registros de páginas do site e de telas de sistema: conteúdo, layout, caminho, vínculo a módulo/opção, permissões, datas e metadados SEO. O conteúdo publicado é o registro de `paginas`; a tela incorpora o editor HTML compartilhado.

## Como usar

1. Abra `admin-paginas/`. O filtro inicial é `tipo=pagina`; selecione sistema ou ambos para ver telas administrativas. `module_id` filtra módulo quando o tipo não é página.
2. Em `admin-paginas/adicionar/`, informe nome, caminho terminado em barra, layout, framework CSS e tipo. Para `sistema`, informe módulo, opção e, quando aplicável, raiz do módulo.
3. Edite conteúdo no editor, configure SEO/compartilhamento e, se necessário, datas de publicação, criação e modificação. O seletor de imagem de destaque guarda o caminho físico relativo do arquivo.
4. A permissão `permissao-pagina` habilita alterar `sem_permissao` (página sem exigência de permissão). Sem ela, o controle é removido e o backend não grava sua alteração.
5. Salve e revise `admin-paginas/editar/?id=<slug>`. Clonar usa `admin-paginas/clonar/?id=<slug>`. As rotas declaradas são iguais em pt-br/en, relativas à raiz do idioma. A listagem oferece status, exclusão e edição.

O JS formata caminhos sem acentos, em minúsculas, com hífens e barra final. Alterar nome/caminho pode reformatar o endereço; revise antes de salvar. Para publicações com campos estruturados, use o módulo `publisher-pages`.

## Referência técnica

### Despacho, permissões e hooks

O controlador configura `listar` e despacha `adicionar`, `editar`, `clonar`; a interface compartilhada trata `status` e `excluir`. O único AJAX próprio é `editor-html-switch`: `editor_checked=sim` grava a variável booleana do módulo como verdadeira; qualquer outro valor grava falsa, retornando `status=Ok`.

`ModulosOperacoesData.json` semeia `modificar-permissao-da-pagina`, operação `permissao-pagina`, nos dois idiomas. Não há widget público próprio nem templates de conteúdo declarados neste JSON; ele fornece páginas administrativas e o componente `lista-pagina-ou-sistema`.

O PHP dispara ações no escopo `admin-paginas`: `adicionar.banco`, `editar.banco`, `clonar.banco`, após a escrita, passando id e dados. A edição inclui `alteracoes`; criação/clonagem incluem nome, caminho, tipo e módulo. O JSON não declara `hooks.api` nem inscrições `hooks`: disparar uma ação não implica haver consumidor registrado.

### Persistência e rotas antigas

Criação/clonagem geram slug único no idioma, status `A`, versão 1 e autor atual. A duplicidade de caminho é verificada no idioma. A edição compara campos, guarda backups de conteúdo anterior quando não vazio, registra histórico, marca `user_modified=1` e incrementa versão. Renomear pode mudar `id`, salvo presença de `_gestor-nao-alterar-id`.

Marcadores de HTML/CSS/head são convertidos de `[[...]]` para `@[[...]]@` ao gravar. O controlador calcula `css_source_hash` a partir de HTML, CSS e layout pelo helper de procedência; isso não equivale a compilar CSS novo nessa função.

Quando `caminho` muda, resolve o id numérico e chama `gestor_pagina_301_registrar(id_paginas, caminhoAntigo)`. O sitemap é sincronizado após criar/clonar/editar e por callbacks de status/exclusão; uma exceção nessa sincronização é registrada e não interrompe o CRUD.

### Tabelas e colunas

| Tabela/grupo | Colunas e tipos das migrations |
|---|---|
| `paginas`: identidade | `id_paginas` numérico; `id_usuarios` inteiro nullable, padrão 1; `nome`, `id`, `layout_id` strings até 255; `language` até 10, padrão pt-br |
| Roteamento | `caminho` TEXT; `tipo`, `modulo`, `opcao` strings até 255; `raiz`, `sem_permissao` inteiros pequenos nullable; `publisher_id` até 255 |
| Conteúdo | `html`, `css`, `css_precompiled`, `css_compiled`, `html_extra_head`, `html_updated`, `css_updated` MEDIUMTEXT; `framework_css` até 50; `css_source_hash` até 64 |
| SEO | `imagem_destaque` até 500, `og_titulo` até 255, `og_descricao` e `meta_descricao` TEXT, `meta_keywords` até 500 |
| Estado/origem | `status` char (padrão A), `versao` inteiro (padrão 1), `user_modified`, `system_updated`, `file_version`, `checksum`, `plugin`, `project` |
| Datas | `data_criacao`, `data_modificacao`; `data_publicacao_inicio` e `data_publicacao_fim` nullable |
| `paginas_301` | `id_paginas_301`, `id_paginas`, `caminho` TEXT, `data_criacao` |

A unicidade da página é `(id, language)`, não apenas `id`. As migrations não definem os ENUMs e as foreign keys apresentados na doc antiga. `tipo` e `framework_css` são strings; `caminho` não é VARCHAR(500).

### Datas e SEO

As datas passam por `formato_data_hora_br_para_datetime()`. Na criação, datas de criação/modificação não interpretadas usam `NOW()`; janela vazia permanece nula. Na edição, limpar janela exige o parâmetro `data_publicacao_inicio_limpar` ou `data_publicacao_fim_limpar`; só enviar campo vazio não remove a data anterior.

O roteador exige início nulo ou `<= NOW()` e fim nulo ou `>= NOW()`, além de status ativo. Fora da janela, a consulta normal não encontra a página. Não há status separado “agendado” neste controlador.

SEO inclui `og_titulo`, `og_descricao`, `meta_descricao`, `meta_keywords` e imagem de destaque. Keywords são normalizadas pelo helper; na edição, os campos enviados vazios podem limpar os valores antigos.

## Limitações e legado

> [!WARNING]
> Os inputs de agendamento da tela de edição não são preenchidos com as datas existentes, e o SELECT do controlador não inclui as colunas da janela. A tela também não fornece os parâmetros explícitos de limpeza esperados pelo backend. Não interprete um input vazio como ausência de agendamento salvo.

> [!WARNING]
> A atualização das datas fica dentro de `if(isset($editar['dados']))`: datas isoladas não iniciam esse acumulador. A assinatura CSS não vazia normalmente o inicia, mas isso depende do resultado do helper. Não há validação local de que início seja anterior ao fim.

> [!CAUTION]
> HTML, CSS e head são conteúdo autoral livre. Esse CRUD não é um sanitizador de HTML para autores não confiáveis. A validação do servidor cobre nome/caminho; vários requisitos exibidos na validação da interface (layout, tipo e framework) não são repetidos na lista local de campos obrigatórios do backend.

O legado descrevia `raiz` como raiz do site e tradução do id do módulo para `admin-pages`. O formulário usa essa opção no bloco de configuração de módulo; o id real e as rotas continuam `admin-paginas` também em inglês.

## Veja também

- [Definições de publicação](publisher.md)
- [Busca de páginas](pages-index.md)
- [Biblioteca banco](../libraries/banco.md)
